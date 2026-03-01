<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'documentation';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; margin-bottom: 0;">
        <button class="shipping-tab-btn <?php echo $sub == 'documentation' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-docs', this)">الوثائق</button>
    <button class="shipping-tab-btn <?php echo $sub == 'invoices' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-invoices', this)">الفواتير</button>
    <button class="shipping-tab-btn <?php echo $sub == 'duties-taxes' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-taxes', this)">الرسوم والضرائب</button>
        <button class="shipping-tab-btn <?php echo $sub == 'status' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-status', this)">حالة التخليص</button>
    </div>
    <button class="shipping-btn" onclick="document.getElementById('modal-add-customs').style.display='flex'">+ إضافة تخليص</button>
</div>

<div id="customs-docs" class="shipping-internal-tab" style="display: <?php echo $sub == 'documentation' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>إدارة وثائق الشحن والمستندات</h4>
        <p>رفع ومعالجة بوالص الشحن وشهادات المنشأ.</p>
    </div>
</div>

<div id="customs-invoices" class="shipping-internal-tab" style="display: <?php echo $sub == 'invoices' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>الفواتير التجارية</h4>
        <p>مطابقة الفواتير التجارية مع الشحنات الفعلية.</p>
    </div>
</div>

<div id="customs-taxes" class="shipping-internal-tab" style="display: <?php echo $sub == 'duties-taxes' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>حساب الرسوم والضرائب الجمركية</h4>
        <p>أداة تقدير الضرائب الجمركية حسب نوع البضاعة والبلد.</p>
    </div>
</div>

<div id="customs-status" class="shipping-internal-tab" style="display: <?php echo $sub == 'status' ? 'block' : 'none'; ?>;">
    <?php
    global $wpdb;
    $customs = $wpdb->get_results("SELECT c.*, s.shipment_number FROM {$wpdb->prefix}shipping_customs c JOIN {$wpdb->prefix}shipping_shipments s ON c.shipment_id = s.id ORDER BY c.id DESC");
    ?>
    <div class="shipping-card">
        <h4>حالات التخليص الجمركي</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الشحنة</th><th>التوثيق</th><th>الرسوم</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php if(empty($customs)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">لا توجد بيانات تخليص مسجلة.</td></tr>
                    <?php else: foreach($customs as $c): ?>
                        <tr>
                            <td><strong><?php echo esc_html($c->shipment_number); ?></strong></td>
                            <td><?php echo esc_html($c->documentation_status); ?></td>
                            <td><?php echo number_format($c->duties_amount, 2); ?></td>
                            <td><span class="shipping-badge"><?php echo esc_html($c->clearance_status); ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal-add-customs" class="shipping-modal" style="display: none;">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h4>إضافة بيان جمركي</h4>
            <button onclick="document.getElementById('modal-add-customs').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-add-customs">
                <input type="hidden" name="action" value="shipping_add_customs">
                <?php wp_nonce_field('shipping_customs_action', 'nonce'); ?>

                <div class="shipping-form-group">
                    <label>الشحنة</label>
                    <select name="shipment_id" class="shipping-input" required>
                        <option value="">اختر الشحنة...</option>
                        <?php
                        global $wpdb;
                        $shipments = $wpdb->get_results("SELECT id, shipment_number FROM {$wpdb->prefix}shipping_shipments ORDER BY id DESC LIMIT 50");
                        foreach($shipments as $s) echo "<option value='{$s->id}'>".esc_html($s->shipment_number)."</option>";
                        ?>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>حالة التوثيق</label>
                    <input type="text" name="documentation_status" class="shipping-input" placeholder="مكتمل / قيد المراجعة" required>
                </div>
                <div class="shipping-form-group">
                    <label>مبلغ الرسوم</label>
                    <input type="number" step="0.01" name="duties_amount" class="shipping-input" placeholder="0.00" required>
                </div>
                <div class="shipping-form-group">
                    <label>حالة التخليص</label>
                    <input type="text" name="clearance_status" class="shipping-input" placeholder="تم الفحص / في الانتظار" required>
                </div>

                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ البيانات</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('form-add-customs')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الحفظ...';

    fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('تم حفظ البيانات الجمركية بنجاح');
            location.reload();
        } else {
            alert(res.data);
            btn.disabled = false; btn.innerText = 'حفظ البيانات';
        }
    });
});
</script>
