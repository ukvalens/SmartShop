<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Language.php';

class AuthController {
    private $conn;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }
    
    public function register($fullName, $email, $phoneNumber, $password, $role) {
        // Check if email exists
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (full_name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullName, $email, $phoneNumber, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Registration successful'];
        }
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT user_id, full_name, email, role, password, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] === 'inactive') {
                return ['success' => false, 'message' => 'Account is inactive'];
            }
            
            if ($user['role'] === 'Pending') {
                return ['success' => false, 'message' => 'Account pending admin approval. Please contact administrator.'];
            }
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                return ['success' => true, 'role' => $user['role']];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    public function logout() {
        session_destroy();
        header('Location: ../auth/login.php');
        exit;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getRedirectUrl($role) {
        return '../dashboard/index.php';
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'user_id' => $_SESSION['user_id'],
                'full_name' => $_SESSION['full_name'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
    
    public function generateResetToken($email) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $this->conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expires, $email);
            
            if ($stmt->execute()) {
                return ['success' => true, 'token' => $token];
            }
        }
        return ['success' => false, 'message' => 'Email not found'];
    }
    
    public function resetPassword($token, $newPassword) {
        // Debug: Check if token exists
        $stmt = $this->conn->prepare("SELECT user_id, email, reset_token_expires FROM users WHERE reset_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Token not found in database'];
        }
        
        $user = $result->fetch_assoc();
        $now = date('Y-m-d H:i:s');
        
        if ($user['reset_token_expires'] < $now) {
            return ['success' => false, 'message' => 'Token expired at ' . $user['reset_token_expires'] . ', current time: ' . $now];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashedPassword, $token);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Password reset successful'];
        }
        
        return ['success' => false, 'message' => 'Failed to update password'];
    }
}