<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'active-shipments';
?>
<div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
    <button class="shipping-tab-btn <?php echo $sub == 'active-shipments' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('stats-active', this)">الشحنات النشطة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'delivered-shipments' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('stats-delivered', this)">الشحنات المسلمة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'delayed-shipments' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('stats-delayed', this)">الشحنات المتأخرة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'total-revenue' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('stats-revenue', this)">إجمالي الإيرادات</button>
    <button class="shipping-tab-btn <?php echo $sub == 'real-time-status' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('stats-realtime', this)">حالة العمليات</button>
</div>

<div id="stats-active" class="shipping-internal-tab" style="display: <?php echo $sub == 'active-shipments' ? 'block' : 'none'; ?>;">
    <?php
    global $wpdb;
    $active_shipments = $wpdb->get_results("SELECT s.*, c.name as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.status != 'delivered' AND s.is_archived = 0");
    ?>
    <div class="shipping-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h4 style="margin:0;">الشحنات النشطة حالياً</h4>
            <span class="shipping-badge shipping-badge-high">إجمالي: <?php echo count($active_shipments); ?></span>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الشحنة</th><th>العميل</th><th>المسار</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php if(empty($active_shipments)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">لا توجد بيانات متاحة حالياً.</td></tr>
                    <?php else: foreach($active_shipments as $shp): ?>
                        <tr>
                            <td><strong><?php echo $shp->shipment_number; ?></strong></td>
                            <td><?php echo esc_html($shp->customer_name); ?></td>
                            <td><?php echo esc_html($shp->origin . ' → ' . $shp->destination); ?></td>
                            <td><span class="shipping-badge"><?php echo $shp->status; ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="stats-delivered" class="shipping-internal-tab" style="display: <?php echo $sub == 'delivered-shipments' ? 'block' : 'none'; ?>;">
    <?php
    $delivered_shipments = $wpdb->get_results("SELECT s.*, c.name as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.status = 'delivered' ORDER BY s.delivery_date DESC LIMIT 50");
    ?>
    <div class="shipping-card">
        <h4>الشحنات المسلمة مؤخراً</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الشحنة</th><th>العميل</th><th>تاريخ التسليم</th></tr></thead>
                <tbody>
                    <?php if(empty($delivered_shipments)): ?>
                        <tr><td colspan="3" style="text-align:center; padding:20px;">لا توجد شحنات مسلمة مسجلة.</td></tr>
                    <?php else: foreach($delivered_shipments as $shp): ?>
                        <tr>
                            <td><strong><?php echo $shp->shipment_number; ?></strong></td>
                            <td><?php echo esc_html($shp->customer_name); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($shp->delivery_date)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="stats-delayed" class="shipping-internal-tab" style="display: <?php echo $sub == 'delayed-shipments' ? 'block' : 'none'; ?>;">
    <?php
    $delayed_shipments = $wpdb->get_results($wpdb->prepare("SELECT s.*, c.name as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.status != 'delivered' AND s.delivery_date < %s", current_time('mysql')));
    ?>
    <div class="shipping-card" style="border-right: 5px solid #e53e3e;">
        <h4 style="color:#e53e3e;">تنبيه الشحنات المتأخرة</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الشحنة</th><th>العميل</th><th>الموعد الفائت</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php if(empty($delayed_shipments)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">لا توجد شحنات متأخرة حالياً.</td></tr>
                    <?php else: foreach($delayed_shipments as $shp): ?>
                        <tr>
                            <td><strong><?php echo $shp->shipment_number; ?></strong></td>
                            <td><?php echo esc_html($shp->customer_name); ?></td>
                            <td style="color:#e53e3e;"><?php echo date('Y-m-d', strtotime($shp->delivery_date)); ?></td>
                            <td><span class="shipping-badge" style="background:#fff5f5; color:#c53030;"><?php echo $shp->status; ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="stats-revenue" class="shipping-internal-tab" style="display: <?php echo $sub == 'total-revenue' ? 'block' : 'none'; ?>;">
    <?php
    $total_revenue = $wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}shipping_invoices WHERE status = 'paid'");
    ?>
    <div class="shipping-card">
        <h4>إجمالي الإيرادات (المحصلة)</h4>
        <div style="font-size: 2em; font-weight: 800; color: #27ae60;"><?php echo number_format($total_revenue ?: 0, 2); ?> SAR</div>
    </div>
</div>

<div id="stats-realtime" class="shipping-internal-tab" style="display: <?php echo $sub == 'real-time-status' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>📊 حالة العمليات المباشرة</h4>
        <?php
        $status_counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM {$wpdb->prefix}shipping_shipments WHERE is_archived = 0 GROUP BY status");
        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">
            <?php foreach ($status_counts as $sc): ?>
                <div style="background: #f8fafc; padding: 20px; border-radius: 12px; text-align: center; border: 1px solid #e2e8f0;">
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 5px;"><?php echo strtoupper($sc->status); ?></div>
                    <div style="font-size: 24px; font-weight: 800; color: var(--shipping-primary-color);"><?php echo $sc->count; ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($status_counts)): ?>
                <p style="text-align: center; color: #94a3b8; padding: 20px;">لا توجد عمليات جارية حالياً.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
