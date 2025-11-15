<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>SmartSHOP</h4>
            <p>Your Smart Shopping Solution</p>
            <div class="contact-info">
                <p>📧 ukwitegetsev9@gmail.com</p>
                <p>📞 0780468216</p>
            </div>
        </div>
        
        <div class="footer-section">
            <h4>Our Partners</h4>
            <ul>
                <li>🏪 Inyama Ltd - Premium Meat Supplier</li>
                <li>🌾 Ubwiyunge Co - Fresh Produce</li>
                <li>💧 Amazi Fresh - Dairy Products</li>
                <li>📱 MTN Rwanda - Mobile Money</li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Services</h4>
            <ul>
                <li>Point of Sale System</li>
                <li>Inventory Management</li>
                <li>Customer Loyalty Program</li>
                <li>Mobile Money Integration</li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> SmartSHOP. All rights reserved. | Powered by SmartSHOP POS System</p>
    </div>
</footer>

<style>
.site-footer {
    background: var(--secondary);
    color: white;
    padding: 2rem 0 1rem;
    width: 100%;
    margin-top: 3rem;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    padding: 0 2rem;
}

.footer-section h4 {
    color: var(--accent);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.contact-info p {
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.footer-bottom {
    text-align: center;
    padding: 1rem 2rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin-top: 2rem;
    font-size: 0.8rem;
    color: rgba(255,255,255,0.8);
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
    }
}
</style>