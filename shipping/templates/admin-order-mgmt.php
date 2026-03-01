<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'new-orders';
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px; margin-bottom: 0;">
        <button class="shipping-tab-btn <?php echo $sub == 'new-orders' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-new', this)">طلبات جديدة</button>
        <button class="shipping-tab-btn <?php echo $sub == 'in-progress' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-progress', this)">قيد التنفيذ</button>
        <button class="shipping-tab-btn <?php echo $sub == 'completed' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-completed', this)">مكتملة</button>
        <button class="shipping-tab-btn <?php echo $sub == 'cancelled' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-cancelled', this)">ملغاة</button>
    </div>
    <button class="shipping-btn" onclick="document.getElementById('modal-add-order').style.display='flex'">+ طلب جديد</button>
</div>

<div id="order-new" class="shipping-internal-tab" style="display: <?php echo $sub == 'new-orders' ? 'block' : 'none'; ?>;">
    <?php
    global $wpdb;
    $new_orders = $wpdb->get_results("SELECT o.*, c.name as customer_name FROM {$wpdb->prefix}shipping_orders o LEFT JOIN {$wpdb->prefix}shipping_customers c ON o.customer_id = c.id WHERE o.status = 'new' ORDER BY o.created_at DESC");
    ?>
    <div class="shipping-card">
        <h4>طلبات شحن جديدة</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الطلب</th><th>العميل</th><th>المبلغ</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php if(empty($new_orders)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">لا توجد طلبات جديدة.</td></tr>
                    <?php else: foreach($new_orders as $o): ?>
                        <tr>
                            <td><strong><?php echo $o->order_number; ?></strong></td>
                            <td><?php echo esc_html($o->customer_name); ?></td>
                            <td><?php echo number_format($o->total_amount, 2); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($o->created_at)); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="order-progress" class="shipping-internal-tab" style="display: <?php echo $sub == 'in-progress' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>طلبات قيد التنفيذ</h4>
        <p>متابعة الطلبات التي يتم تجهيزها حالياً.</p>
    </div>
</div>

<div id="order-completed" class="shipping-internal-tab" style="display: <?php echo $sub == 'completed' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>طلبات مكتملة</h4>
        <p>أرشيف الطلبات التي تم تسويتها بنجاح.</p>
    </div>
</div>

<div id="order-cancelled" class="shipping-internal-tab" style="display: <?php echo $sub == 'cancelled' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>طلبات ملغاة</h4>
        <p>سجل الطلبات التي تم إلغاؤها مع ذكر الأسباب.</p>
    </div>
</div>

<div id="modal-add-order" class="shipping-modal" style="display: none;">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h4>إضافة طلب شحن جديد</h4>
            <button onclick="document.getElementById('modal-add-order').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-add-order">
                <input type="hidden" name="action" value="shipping_add_order">
                <?php wp_nonce_field('shipping_order_action', 'nonce'); ?>

                <div class="shipping-form-group">
                    <label>العميل</label>
                    <select name="customer_id" class="shipping-input" required>
                        <option value="">اختر العميل...</option>
                        <?php
                        $customers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}shipping_customers ORDER BY name ASC");
                        foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                        ?>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>المبلغ الإجمالي</label>
                    <input type="number" step="0.01" name="total_amount" class="shipping-input" placeholder="0.00" required>
                </div>

                <button type="submit" class="shipping-btn" style="width: 100%;">إنشاء الطلب</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('form-add-order')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الحفظ...';

    fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('تم إنشاء الطلب بنجاح');
            location.reload();
        } else {
            alert(res.data);
            btn.disabled = false; btn.innerText = 'إنشاء الطلب';
        }
    });
});
</script>
