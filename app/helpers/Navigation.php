<?php
require_once __DIR__ . '/Language.php';

class Navigation {
    public static function getNavLinks($role, $lang) {
        // Dashboard link for all roles
        $links = [['url' => '../dashboard/index.php', 'label' => Language::getText('dashboard', $lang)]];
        
        switch ($role) {
            case 'System Admin':
                $links = array_merge($links, [
                    ['url' => '../users/index.php', 'label' => Language::getText('user_management', $lang)],
                    ['url' => '../settings/system.php', 'label' => Language::getText('system_settings', $lang)],
                    ['url' => '../reports/system.php', 'label' => Language::getText('system_reports', $lang)],
                    ['url' => '../backup/index.php', 'label' => Language::getText('data_backup', $lang)],
                    ['url' => '../security/index.php', 'label' => Language::getText('security', $lang)]
                ]);
                break;
            case 'Admin':
                $links = array_merge($links, [
                    ['url' => '../pos/index.php', 'label' => 'POS'],
                    ['url' => '../inventory/index.php', 'label' => 'Inventory'],
                    ['url' => '../customers/index.php', 'label' => 'Customers'],
                    ['url' => '../customers/credit.php', 'label' => 'Credit Management'],
                    ['url' => '../reports/daily.php', 'label' => 'Reports'],
                    ['url' => '../users/index.php', 'label' => Language::getText('user_management', $lang)]
                ]);
                break;
            case 'Owner':
                $links = array_merge($links, [
                    ['url' => '../pos/index.php', 'label' => 'POS'],
                    ['url' => '../inventory/index.php', 'label' => 'Inventory'],
                    ['url' => '../customers/index.php', 'label' => 'Customers'],
                    ['url' => '../customers/credit.php', 'label' => 'Credit Management'],
                    ['url' => '../reports/daily.php', 'label' => 'Reports'],
                    ['url' => '../suppliers/index.php', 'label' => 'Suppliers']
                ]);
                break;
            case 'Cashier':
                $links = array_merge($links, [
                    ['url' => '../pos/index.php', 'label' => Language::getText('point_of_sale', $lang)],
                    ['url' => '../customers/index.php', 'label' => Language::getText('customer_lookup', $lang)],
                    ['url' => '../inventory/stock-check.php', 'label' => Language::getText('stock_check', $lang)],
                    ['url' => '../pos/returns.php', 'label' => Language::getText('returns_exchanges', $lang)]
                ]);
                break;
            case 'Customer':
                $links = array_merge($links, [
                    ['url' => '../customer/orders.php', 'label' => Language::getText('order_history', $lang)],
                    ['url' => '../customer/loyalty.php', 'label' => Language::getText('loyalty_points', $lang)],
                    ['url' => '../customer/credit.php', 'label' => Language::getText('credit_balance', $lang)]
                ]);
                break;
        }
        
        return $links;
    }
    
    public static function renderNav($role, $lang) {
        $links = self::getNavLinks($role, $lang);
        echo '<nav class="top-nav"><div class="nav-links">';
        foreach ($links as $link) {
            echo '<a href="' . $link['url'] . '?lang=' . $lang . '">' . $link['label'] . '</a>';
        }
        echo '</div></nav>';
    }
}
?>