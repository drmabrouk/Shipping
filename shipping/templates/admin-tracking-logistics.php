<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'live-tracking';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; margin-bottom: 0;">
        <button class="shipping-tab-btn <?php echo $sub == 'live-tracking' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-live', this)">تتبع مباشر</button>
    <button class="shipping-tab-btn <?php echo $sub == 'routes' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-routes', this)">مسارات الشحن</button>
    <button class="shipping-tab-btn <?php echo $sub == 'stop-points' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-stops', this)">نقاط التوقف</button>
    <button class="shipping-tab-btn <?php echo $sub == 'warehouse' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-warehouse', this)">المستودعات</button>
        <button class="shipping-tab-btn <?php echo $sub == 'fleet' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-fleet', this)">الأسطول</button>
    </div>
    <button class="shipping-btn" onclick="document.getElementById('modal-add-route').style.display='flex'">+ إضافة مسار</button>
</div>

<div id="logistic-live" class="shipping-internal-tab" style="display: <?php echo $sub == 'live-tracking' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>خريطة التتبع المباشر</h4>
        <div style="background:#eee; height:300px; display:flex; align-items:center; justify-content:center;">(نظام GPS متصل)</div>
    </div>
</div>

<div id="logistic-routes" class="shipping-internal-tab" style="display: <?php echo $sub == 'routes' ? 'block' : 'none'; ?>;">
    <?php
    global $wpdb;
    $routes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_logistics ORDER BY id DESC");
    ?>
    <div class="shipping-card">
        <h4>مسارات الشحن المسجلة</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>اسم المسار</th><th>نقاط التوقف</th></tr></thead>
                <tbody>
                    <?php if(empty($routes)): ?>
                        <tr><td colspan="2" style="text-align:center; padding:20px;">لا توجد مسارات مسجلة.</td></tr>
                    <?php else: foreach($routes as $r): ?>
                        <tr>
                            <td><strong><?php echo esc_html($r->route_name); ?></strong></td>
                            <td><?php echo nl2br(esc_html($r->stop_points)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="logistic-stops" class="shipping-internal-tab" style="display: <?php echo $sub == 'stop-points' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>نقاط التوقف والمحطات</h4>
        <p>إدارة محطات الترانزيت ونقاط التفريغ.</p>
    </div>
</div>

<div id="logistic-warehouse" class="shipping-internal-tab" style="display: <?php echo $sub == 'warehouse' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>إدارة المستودعات</h4>
        <p>متابعة المخزون، المساحات المتاحة، وعمليات التخزين.</p>
    </div>
</div>

<div id="logistic-fleet" class="shipping-internal-tab" style="display: <?php echo $sub == 'fleet' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>إدارة الأسطول</h4>
        <p>بيانات الشاحنات، السائقين، وجداول الصيانة.</p>
    </div>
</div>

<div id="modal-add-route" class="shipping-modal" style="display: none;">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h4>إضافة مسار شحن جديد</h4>
            <button onclick="document.getElementById('modal-add-route').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-add-route">
                <input type="hidden" name="action" value="shipping_add_route">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>

                <div class="shipping-form-group">
                    <label>اسم المسار</label>
                    <input type="text" name="route_name" class="shipping-input" placeholder="مثال: القاهرة - الإسكندرية" required>
                </div>
                <div class="shipping-form-group">
                    <label>نقاط التوقف</label>
                    <textarea name="stop_points" class="shipping-textarea" placeholder="نقطة 1، نقطة 2..."></textarea>
                </div>

                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ المسار</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('form-add-route')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الحفظ...';

    fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('تم حفظ المسار بنجاح');
            location.reload();
        } else {
            alert(res.data);
            btn.disabled = false; btn.innerText = 'حفظ المسار';
        }
    });
});
</script>
