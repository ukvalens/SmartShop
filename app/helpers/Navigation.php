<?php
require_once __DIR__ . '/Language.php';

class Navigation {
    public static function getNavLinks($role, $lang) {
        // Dashboard link for all roles
        $links = [['url' => '../dashboard/index.php', 'label' => Language::get('dashboard', $lang)]];
        
        switch ($role) {
            case 'System Admin':
                $links = array_merge($links, [
                    ['url' => '../users/index.php', 'label' => Language::get('user_management', $lang)],
                    ['url' => '../settings/system.php', 'label' => Language::get('system_settings', $lang)],
                    ['url' => '../reports/system.php', 'label' => Language::get('system_reports', $lang)],
                    ['url' => '../backup/index.php', 'label' => Language::get('data_backup', $lang)],
                    ['url' => '../security/index.php', 'label' => Language::get('security', $lang)]
                ]);
                break;
            case 'Admin':
                $links = array_merge($links, [
                    ['url' => '../users/index.php', 'label' => Language::get('user_management', $lang)],
                    ['url' => '../settings/system.php', 'label' => Language::get('system_settings', $lang)],
                    ['url' => '../reports/system.php', 'label' => Language::get('system_reports', $lang)],
                    ['url' => '../backup/index.php', 'label' => Language::get('data_backup', $lang)],
                    ['url' => '../security/index.php', 'label' => Language::get('security', $lang)]
                ]);
                break;
            case 'Owner':
            case 'Manager':
                $links = array_merge($links, [
                    ['url' => '../suppliers/index.php', 'label' => Language::get('supplier_management', $lang)],
                    ['url' => '../inventory/index.php', 'label' => Language::get('inventory_management', $lang)],
                    ['url' => '../pos/index.php', 'label' => Language::get('point_of_sale', $lang)],
                    ['url' => '../customers/index.php', 'label' => Language::get('customer_service', $lang)],
                    ['url' => '../customers/credit.php', 'label' => Language::get('credit_management', $lang)],
                    ['url' => '../reports/daily.php', 'label' => Language::get('daily_reports', $lang)]
                ]);
                break;
            case 'Cashier':
                $links = array_merge($links, [
                    ['url' => '../pos/index.php', 'label' => Language::get('point_of_sale', $lang)],
                    ['url' => '../customers/index.php', 'label' => Language::get('customer_lookup', $lang)],
                    ['url' => '../inventory/stock-check.php', 'label' => Language::get('stock_check', $lang)],
                    ['url' => '../pos/returns.php', 'label' => Language::get('returns_exchanges', $lang)]
                ]);
                break;
            case 'Customer':
                $links = array_merge($links, [
                    ['url' => '../customer/orders.php', 'label' => Language::get('order_history', $lang)],
                    ['url' => '../customer/loyalty.php', 'label' => Language::get('loyalty_points', $lang)],
                    ['url' => '../customer/credit.php', 'label' => Language::get('credit_balance', $lang)]
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