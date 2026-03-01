<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'calculator';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; margin-bottom: 0;">
        <button class="shipping-tab-btn <?php echo $sub == 'calculator' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-calc', this)">الحاسبة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'transport-costs' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-transport', this)">تكاليف النقل</button>
    <button class="shipping-tab-btn <?php echo $sub == 'extra-charges' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-extra', this)">رسوم إضافية</button>
        <button class="shipping-tab-btn <?php echo $sub == 'special-offers' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-offers', this)">عروض خاصة</button>
    </div>
    <button class="shipping-btn" onclick="document.getElementById('modal-add-pricing').style.display='flex'">+ إضافة سعر</button>
</div>

<div id="pricing-calc" class="shipping-internal-tab" style="display: <?php echo $sub == 'calculator' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>حاسبة الشحن المتطورة</h4>
        <p>حساب التكلفة التقديرية بناءً على الوزن، الحجم، والوجهة.</p>
    </div>
</div>

<div id="pricing-transport" class="shipping-internal-tab" style="display: <?php echo $sub == 'transport-costs' ? 'block' : 'none'; ?>;">
    <?php
    global $wpdb;
    $pricing = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_pricing ORDER BY id DESC");
    ?>
    <div class="shipping-card">
        <h4>قوائم أسعار الخدمات والتشغيل</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>اسم الخدمة</th><th>التكلفة الأساسية</th><th>رسوم إضافية</th></tr></thead>
                <tbody>
                    <?php if(empty($pricing)): ?>
                        <tr><td colspan="3" style="text-align:center; padding:20px;">لا توجد قواعد تسعير مسجلة.</td></tr>
                    <?php else: foreach($pricing as $p): ?>
                        <tr>
                            <td><strong><?php echo esc_html($p->service_name); ?></strong></td>
                            <td><?php echo number_format($p->base_cost, 2); ?></td>
                            <td><?php echo number_format($p->additional_fees, 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="pricing-extra" class="shipping-internal-tab" style="display: <?php echo $sub == 'extra-charges' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>الرسوم الإضافية والخدمات</h4>
        <p>تحديد أسعار التغليف، التأمين، والخدمات الخاصة.</p>
    </div>
</div>

<div id="pricing-offers" class="shipping-internal-tab" style="display: <?php echo $sub == 'special-offers' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>العروض الخاصة والخصومات</h4>
        <p>إدارة الحملات الترويجية وخصومات كبار العملاء.</p>
    </div>
</div>

<div id="modal-add-pricing" class="shipping-modal" style="display: none;">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h4>إضافة قاعدة تسعير</h4>
            <button onclick="document.getElementById('modal-add-pricing').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-add-pricing">
                <input type="hidden" name="action" value="shipping_add_pricing">
                <?php wp_nonce_field('shipping_pricing_action', 'nonce'); ?>

                <div class="shipping-form-group">
                    <label>اسم الخدمة</label>
                    <input type="text" name="service_name" class="shipping-input" placeholder="مثال: شحن جوي سريع" required>
                </div>
                <div class="shipping-form-group">
                    <label>التكلفة الأساسية</label>
                    <input type="number" step="0.01" name="base_cost" class="shipping-input" placeholder="0.00" required>
                </div>
                <div class="shipping-form-group">
                    <label>رسوم إضافية</label>
                    <input type="number" step="0.01" name="additional_fees" class="shipping-input" placeholder="0.00" required>
                </div>
                <div class="shipping-form-group">
                    <label>تفاصيل العروض</label>
                    <textarea name="special_offer_details" class="shipping-textarea" placeholder="خصم 10% للكميات..."></textarea>
                </div>

                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ السعر</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('form-add-pricing')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الحفظ...';

    fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('تم حفظ قاعدة التسعير بنجاح');
            location.reload();
        } else {
            alert(res.data);
            btn.disabled = false; btn.innerText = 'حفظ السعر';
        }
    });
});
</script>
