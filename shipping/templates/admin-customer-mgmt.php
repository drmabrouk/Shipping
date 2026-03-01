<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'profiles';
?>
<div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
    <button class="shipping-tab-btn <?php echo $sub == 'profiles' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customer-profiles', this)">ملفات العملاء</button>
    <button class="shipping-tab-btn <?php echo $sub == 'history' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customer-history', this)">سجل الشحنات</button>
    <button class="shipping-tab-btn <?php echo $sub == 'address-book' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customer-address', this)">دفتر العناوين</button>
    <button class="shipping-tab-btn <?php echo $sub == 'contracts' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customer-contracts', this)">العقود</button>
    <button class="shipping-tab-btn <?php echo $sub == 'classification' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customer-class', this)">التصنيف</button>
</div>

<div id="customer-profiles" class="shipping-internal-tab" style="display: <?php echo $sub == 'profiles' ? 'block' : 'none'; ?>;">
    <?php
    global $wpdb;
    $customers = $wpdb->get_results("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers ORDER BY id DESC");
    ?>
    <div class="shipping-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <h4>قاعدة بيانات العملاء</h4>
            <button class="shipping-btn" style="width:auto;" onclick="document.getElementById('add-customer-modal').style.display='flex'">+ إضافة عميل جديد</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>الاسم</th><th>البريد</th><th>الهاتف</th><th>التصنيف</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php if(empty($customers)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px;">لا يوجد عملاء مسجلين.</td></tr>
                    <?php else: foreach($customers as $c): ?>
                        <tr>
                            <td><strong><?php echo esc_html($c->name); ?></strong></td>
                            <td><?php echo esc_html($c->email); ?></td>
                            <td><?php echo esc_html($c->phone); ?></td>
                            <td><span class="shipping-badge"><?php echo esc_html($c->classification); ?></span></td>
                            <td>
                                <a href="<?php echo add_query_arg(['shipping_tab' => 'customer-profile', 'customer_id' => $c->id]); ?>" class="shipping-btn shipping-btn-outline" style="padding: 5px 10px; font-size: 11px; text-decoration:none;">عرض الملف</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customer-history" class="shipping-internal-tab" style="display: <?php echo $sub == 'history' ? 'block' : 'none'; ?>;">
    <?php
    $history = $wpdb->get_results("SELECT s.*, c.name as customer_name FROM {$wpdb->prefix}shipping_shipments s JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id ORDER BY s.created_at DESC LIMIT 100");
    ?>
    <div class="shipping-card">
        <h4>سجل الشحنات العام للعملاء</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>العميل</th><th>رقم الشحنة</th><th>التاريخ</th><th>الحالة</th></tr></thead>
                <tbody>
                    <?php if(empty($history)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">لا توجد شحنات سابقة.</td></tr>
                    <?php else: foreach($history as $h): ?>
                        <tr>
                            <td><?php echo esc_html($h->customer_name); ?></td>
                            <td><strong><?php echo $h->shipment_number; ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($h->created_at)); ?></td>
                            <td><span class="shipping-badge"><?php echo $h->status; ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customer-address" class="shipping-internal-tab" style="display: <?php echo $sub == 'address-book' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>دفتر العناوين الموحد</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>العميل</th><th>العنوان المسجل</th><th>الهاتف</th></tr></thead>
                <tbody>
                    <?php foreach($customers as $c): ?>
                        <tr>
                            <td><strong><?php echo esc_html($c->name); ?></strong></td>
                            <td><?php echo esc_html($c->residence_street . ', ' . $c->residence_city); ?></td>
                            <td><?php echo esc_html($c->phone); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customer-contracts" class="shipping-internal-tab" style="display: <?php echo $sub == 'contracts' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>العقود والاتفاقيات الجارية</h4>
        <p style="color:#64748b;">يتم هنا عرض ملخص العقود السنوية واتفاقيات مستوى الخدمة (SLA) مع كبار العملاء.</p>
        <div style="background:#f8fafc; padding:40px; text-align:center; border-radius:12px; border:2px dashed #cbd5e0; color:#94a3b8;">
            <span class="dashicons dashicons-pdf" style="font-size:40px; width:40px; height:40px; margin-bottom:10px;"></span>
            <div>لا توجد عقود مؤرشفة حالياً</div>
        </div>
    </div>
</div>

<div id="customer-class" class="shipping-internal-tab" style="display: <?php echo $sub == 'classification' ? 'block' : 'none'; ?>;">
    <?php
    $class_stats = $wpdb->get_results("SELECT classification, COUNT(*) as count FROM {$wpdb->prefix}shipping_customers GROUP BY classification");
    ?>
    <div class="shipping-card">
        <h4>تحليل تصنيفات العملاء</h4>
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:20px;">
            <?php foreach($class_stats as $cs): ?>
                <div style="background:#fff; border:1px solid #e2e8f0; padding:20px; border-radius:12px; text-align:center;">
                    <div style="font-size:14px; color:#64748b; margin-bottom:10px;"><?php echo strtoupper($cs->classification); ?></div>
                    <div style="font-size:2em; font-weight:800; color:var(--shipping-primary-color);"><?php echo $cs->count; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="add-customer-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content">
        <div class="shipping-modal-header"><h3>إضافة عميل جديد</h3><button class="shipping-modal-close" onclick="document.getElementById('add-customer-modal').style.display='none'">&times;</button></div>
        <form id="shipping-add-customer-form" style="padding:20px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="shipping-form-group"><label>الاسم الأول:</label><input type="text" name="first_name" class="shipping-input" required></div>
                <div class="shipping-form-group"><label>اسم العائلة:</label><input type="text" name="last_name" class="shipping-input" required></div>
            </div>
            <div class="shipping-form-group"><label>اسم المستخدم:</label><input type="text" name="username" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>البريد الإلكتروني:</label><input type="email" name="email" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>الهاتف:</label><input type="text" name="phone" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>المدينة:</label><input type="text" name="residence_city" class="shipping-input"></div>
            <div class="shipping-form-group">
                <label>تصنيف العميل:</label>
                <select name="classification" class="shipping-select">
                    <option value="regular">عادي (Regular)</option>
                    <option value="vip">VIP</option>
                    <option value="corporate">شركات (Corporate)</option>
                </select>
            </div>
            <button type="submit" class="shipping-btn" style="width:100%;">حفظ بيانات العميل</button>
        </form>
    </div>
</div>

<script>
document.getElementById('shipping-add-customer-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'shipping_add_customer_ajax');
    fd.append('shipping_nonce', '<?php echo wp_create_nonce("shipping_add_customer"); ?>');

    fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
        if(res.success) {
            shippingShowNotification('تمت إضافة العميل بنجاح');
            location.reload();
        } else alert(res.data);
    });
});
</script>
