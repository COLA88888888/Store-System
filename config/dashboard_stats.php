<?php

function dashboardScalar($conn, $sql)
{
    if (!$conn) {
        return 0;
    }
    try {
        $result = @mysqli_query($conn, $sql);
        if (!$result) {
            return 0;
        }
        $row = mysqli_fetch_row($result);
        return $row ? ($row[0] ?? 0) : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

function getDashboardQuickStats($conn)
{
    $stats = [
        'total_sales' => 0.00,
        'total_profit' => 0.00,
        'total_products' => 0,
        'low_stock_count' => 0,
        'total_categories' => 0,
        'total_imports' => 0,
        'import_value' => 0.00,
        'vat_collected' => 0.00,
        'expired_count' => 0,
        'near_expired_count' => 0,
    ];

    if (!$conn) {
        return $stats;
    }

    // 1. Total sales revenue from tbsale_save
    $stats['total_sales'] = (float) dashboardScalar($conn, 'SELECT SUM(sale_amount) FROM st_tbsale_save');
    
    // 2. Total profit (sales amount - estimated cost)
    $stats['total_profit'] = (float) dashboardScalar($conn, 'SELECT SUM(sale_amount - COALESCE(sale_discount_bill, 0)) FROM st_tbsale_save');
    
    // 3. Total products from products table
    $stats['total_products'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM st_products');
    
    // 4. Low stock products (qty <= 5)
    $stats['low_stock_count'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM st_products WHERE qty <= 5');
    
    // 5. Total categories from categories table
    $stats['total_categories'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM st_categories');
    
    // 6. Total imports count from imports
    $stats['total_imports'] = (int) dashboardScalar($conn, 'SELECT COUNT(*) FROM st_imports');
    
    // 7. Total imported value from imports
    $stats['import_value'] = (float) dashboardScalar($conn, 'SELECT SUM(total_cost) FROM st_imports');
    
    // 8. VAT Collected
    $stats['vat_collected'] = 0.00;
    
    // 9. Expired count
    $stats['expired_count'] = 0;
    
    // 10. Near-expired count
    $stats['near_expired_count'] = 0;

    return $stats;
}
?>
