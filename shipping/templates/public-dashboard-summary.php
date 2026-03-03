<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$is_officer = current_user_can('manage_options');
?>

<?php if ($is_officer): ?>
<div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
    <button class="shipping-tab-btn shipping-active" onclick="shippingOpenInternalTab('dashboard-overview', this)">نظرة عامة</button>
    <button class="shipping-tab-btn" onclick="shippingOpenInternalTab('dashboard-active', this)">شحنات نشطة</button>
    <button class="shipping-tab-btn" onclick="shippingOpenInternalTab('dashboard-delivered', this)">شحنات مسلمة</button>
    <button class="shipping-tab-btn" onclick="shippingOpenInternalTab('dashboard-delayed', this)">شحنات متأخرة</button>
    <button class="shipping-tab-btn" onclick="shippingOpenInternalTab('dashboard-ops', this)">حالة العمليات</button>
</div>

<div id="dashboard-overview" class="shipping-internal-tab">
    <div class="shipping-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin-bottom: 30px;">
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">إجمالي العملاء</div>
            <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-primary-color);"><?php echo esc_html($stats['total_customers'] ?? 0); ?></div>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">شحنات نشطة</div>
            <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-secondary-color);"><?php echo esc_html($stats['active_shipments'] ?? 0); ?></div>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">طلبات جديدة</div>
            <div style="font-size: 2.5em; font-weight: 900; color: #2ecc71;"><?php echo esc_html($stats['new_orders'] ?? 0); ?></div>
        </div>
        <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
            <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">إجمالي الإيرادات</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #27ae60; margin-top: 10px;"><?php echo number_format($stats['total_revenue'] ?? 0, 0); ?> <span style="font-size: 0.4em;">SAR</span></div>
        </div>
    </div>

    <div class="shipping-grid" style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px;">
        <div class="shipping-card">
            <h4>توزيع حالات الشحن</h4>
            <div style="height: 300px;"><canvas id="shipmentStatusChart"></canvas></div>
        </div>
        <div class="shipping-card">
            <h4>توجه الإيرادات (آخر 7 أيام)</h4>
            <div style="height: 300px;"><canvas id="revenueTrendChart"></canvas></div>
        </div>
    </div>
</div>

<div id="dashboard-active" class="shipping-internal-tab" style="display: none;">
    <?php
    $active_shipments = $wpdb->get_results("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.status != 'delivered' AND s.is_archived = 0");
    ?>
    <div class="shipping-card">
        <h4 style="margin-bottom: 20px;">الشحنات النشطة حالياً</h4>
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

<div id="dashboard-delivered" class="shipping-internal-tab" style="display: none;">
    <?php
    $delivered_shipments = $wpdb->get_results("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.status = 'delivered' ORDER BY s.delivery_date DESC LIMIT 50");
    ?>
    <div class="shipping-card">
        <h4 style="margin-bottom: 20px;">الشحنات المسلمة مؤخراً</h4>
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

<div id="dashboard-delayed" class="shipping-internal-tab" style="display: none;">
    <?php
    $delayed_shipments = $wpdb->get_results($wpdb->prepare("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.status != 'delivered' AND s.delivery_date < %s", current_time('mysql')));
    ?>
    <div class="shipping-card" style="border-right: 5px solid #e53e3e;">
        <h4 style="color:#e53e3e; margin-bottom: 20px;">تنبيه الشحنات المتأخرة</h4>
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

<div id="dashboard-ops" class="shipping-internal-tab" style="display: none;">
    <div class="shipping-card">
        <h4 style="margin-bottom: 20px;">حالة العمليات المباشرة</h4>
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
<?php endif; ?>

<script>
function shippingDownloadChart(chartId, fileName) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const link = document.createElement('a');
    link.download = fileName + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

(function() {
    <?php if (!$is_officer): ?>
    return;
    <?php endif; ?>
    window.shippingCharts = window.shippingCharts || {};

    const initSummaryCharts = function() {
        if (typeof Chart === 'undefined') {
            setTimeout(initSummaryCharts, 200);
            return;
        }

        const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };

        const createOrUpdateChart = (id, config) => {
            if (window.shippingCharts[id]) {
                window.shippingCharts[id].destroy();
            }
            const el = document.getElementById(id);
            if (el) {
                window.shippingCharts[id] = new Chart(el.getContext('2d'), config);
            }
        };

        // 1. Shipment Status Distribution
        createOrUpdateChart('shipmentStatusChart', {
            type: 'doughnut',
            data: {
                labels: ['نشطة', 'مسلمة', 'متأخرة', 'معلقة'],
                datasets: [{
                    data: [<?php echo (int)($stats['active_shipments'] ?? 0); ?>, <?php echo (int)($stats['delivered_shipments'] ?? 0); ?>, <?php echo (int)($stats['delayed_shipments'] ?? 0); ?>, <?php echo (int)($stats['new_orders'] ?? 0); ?>],
                    backgroundColor: ['#4299E1', '#48BB78', '#F56565', '#ECC94B']
                }]
            },
            options: chartOptions
        });

        // 2. Revenue Trend (Last 7 Days)
        fetch(ajaxurl + '?action=shipping_get_billing_report')
        .then(r => r.json()).then(res => {
            if (res.success) {
                createOrUpdateChart('revenueTrendChart', {
                    type: 'line',
                    data: {
                        labels: res.data.daily.map(d => d.date),
                        datasets: [{
                            label: 'الإيرادات اليومية',
                            data: res.data.daily.map(d => d.total),
                            borderColor: '#F63049',
                            backgroundColor: 'rgba(246, 48, 73, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: chartOptions
                });
            }
        });
    };

    if (document.readyState === 'complete') initSummaryCharts();
    else window.addEventListener('load', initSummaryCharts);
})();
</script>
