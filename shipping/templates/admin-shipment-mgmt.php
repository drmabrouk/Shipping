<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'create-shipment';
?>
<div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
    <button class="shipping-tab-btn <?php echo $sub == 'create-shipment' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-create', this)">إنشاء شحنة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'tracking' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-tracking', this)">تتبع الشحنات</button>
    <button class="shipping-tab-btn <?php echo $sub == 'monitoring' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-monitoring', this)">مراقبة الحالة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'schedule' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-schedule', this)">جدول الشحن</button>
    <button class="shipping-tab-btn <?php echo $sub == 'archiving' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-archiving', this)">الأرشفة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'bulk' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-bulk', this)">إدخال بالجملة</button>
</div>

<!-- 1. Centralized Shipment Creation -->
<div id="shipment-create" class="shipping-internal-tab" style="display: <?php echo $sub == 'create-shipment' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>إنشاء شحنة جديدة</h4>
        <form id="shipping-create-shipment-form" style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin-top:15px;">
            <div class="shipping-form-group">
                <label>العميل:</label>
                <select name="customer_id" class="shipping-select" required>
                    <option value="">اختر العميل...</option>
                    <?php
                    $customers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}shipping_customers");
                    foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                    ?>
                </select>
            </div>
            <div class="shipping-form-group"><label>نقطة الانطلاق:</label><input type="text" name="origin" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>نقطة الوصول:</label><input type="text" name="destination" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>الوزن (كجم):</label><input type="number" name="weight" step="0.01" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>الأبعاد (L x W x H):</label><input type="text" name="dimensions" class="shipping-input" placeholder="مثال: 10x20x30" required></div>
            <div class="shipping-form-group">
                <label>التصنيف:</label>
                <select name="classification" class="shipping-select">
                    <option value="standard">قياسي (Standard)</option>
                    <option value="express">سريع (Express)</option>
                    <option value="priority">أولوية (Priority)</option>
                    <option value="fragile">قابل للكسر (Fragile)</option>
                </select>
            </div>
            <div class="shipping-form-group"><label>تاريخ الاستلام:</label><input type="datetime-local" name="pickup_date" class="shipping-input"></div>
            <div class="shipping-form-group"><label>تاريخ الشحن المتوقع:</label><input type="datetime-local" name="dispatch_date" class="shipping-input"></div>
            <div class="shipping-form-group"><label>تاريخ التسليم المتوقع:</label><input type="datetime-local" name="delivery_date" class="shipping-input"></div>
            <div class="shipping-form-group"><label>الناقل (Carrier):</label><select name="carrier_id" class="shipping-select"><option value="0">داخلي</option></select></div>
            <div class="shipping-form-group"><label>المسار (Route):</label><select name="route_id" class="shipping-select"><option value="0">اختر المسار...</option></select></div>
            <button type="submit" class="shipping-btn" style="grid-column: span 3; height: 50px; font-weight: 800;">تأكيد وإنشاء الشحنة</button>
        </form>
    </div>
</div>

<!-- 2. & 3. Tracking & Live Status Engine -->
<div id="shipment-tracking" class="shipping-internal-tab" style="display: <?php echo $sub == 'tracking' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>تتبع الشحنات المباشر</h4>
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <input type="text" id="track-number" class="shipping-input" placeholder="ادخل رقم الشحنة (مثال: SHP-XXXXXX)">
            <button class="shipping-btn" style="width:auto;" onclick="trackShipment()">بحث وتتبع</button>
        </div>
        <div id="tracking-result" style="display:none; padding:20px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                <div>
                    <h3 id="res-number" style="margin:0; color:var(--shipping-primary-color);"></h3>
                    <div id="res-route" style="font-size:13px; color:#64748b; margin-top:5px;"></div>
                </div>
                <span id="res-status" class="shipping-badge" style="font-size:14px; padding:8px 15px;"></span>
            </div>
            <div id="res-timeline" class="tracking-timeline" style="position:relative; padding-right:40px; margin-top:20px;">
                <!-- Timeline events will be injected here -->
            </div>
        </div>
    </div>
</div>

<!-- Monitoring & Audit Trail -->
<div id="shipment-monitoring" class="shipping-internal-tab" style="display: <?php echo $sub == 'monitoring' ? 'block' : 'none'; ?>;">
    <?php
    $monitoring_shipments = $wpdb->get_results("SELECT s.*, (SELECT location FROM {$wpdb->prefix}shipping_shipment_tracking_events WHERE shipment_id = s.id ORDER BY created_at DESC LIMIT 1) as current_location FROM {$wpdb->prefix}shipping_shipments s WHERE s.status NOT IN ('delivered', 'cancelled') AND s.is_archived = 0 ORDER BY s.updated_at DESC");
    ?>
    <div class="shipping-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h4>مراقبة حالة الشحن والعمليات</h4>
            <div style="display:flex; gap:10px;">
                <button class="shipping-btn shipping-btn-outline" style="width:auto;" onclick="location.reload()">تحديث البيانات</button>
            </div>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الشحنة</th><th>الموقع الحالي</th><th>آخر تحديث</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php if(empty($monitoring_shipments)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px;">لا توجد عمليات مراقبة نشطة حالياً.</td></tr>
                    <?php else: foreach($monitoring_shipments as $s): ?>
                        <tr>
                            <td><strong><?php echo $s->shipment_number; ?></strong></td>
                            <td><?php echo esc_html($s->current_location ?: 'غير محدد'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($s->updated_at)); ?></td>
                            <td><span class="shipping-badge"><?php echo $s->status; ?></span></td>
                            <td>
                                <button class="shipping-btn shipping-btn-outline" style="padding:4px 8px; font-size:11px;" onclick="document.getElementById('track-number').value='<?php echo $s->shipment_number; ?>'; shippingOpenInternalTab('shipment-tracking', this.closest('.shipping-internal-tab').parentElement.querySelector('.shipping-tab-btn:nth-child(2)')); trackShipment();">تتبع</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 4. Intelligent Scheduling Module -->
<div id="shipment-schedule" class="shipping-internal-tab" style="display: <?php echo $sub == 'schedule' ? 'block' : 'none'; ?>;">
    <?php
    $today = date('Y-m-d');
    $pickup_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE DATE(pickup_date) = %s", $today));
    $dispatch_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE DATE(dispatch_date) = %s", $today));
    $delivery_today = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE DATE(delivery_date) = %s", $today));
    ?>
    <div class="shipping-card">
        <h4>جدول الشحن والمواعيد اليومي</h4>
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px;">
            <div style="background:#fffaf0; padding:15px; border-radius:10px; border:1px solid #feebc8;">
                <h5 style="margin-top:0; color:#dd6b20;">مواعيد الاستلام اليوم</h5>
                <div style="font-size:24px; font-weight:800;"><?php echo $pickup_today; ?></div>
            </div>
            <div style="background:#ebf8ff; padding:15px; border-radius:10px; border:1px solid #bee3f8;">
                <h5 style="margin-top:0; color:#3182ce;">شحنات قيد الانطلاق</h5>
                <div style="font-size:24px; font-weight:800;"><?php echo $dispatch_today; ?></div>
            </div>
            <div style="background:#f0fff4; padding:15px; border-radius:10px; border:1px solid #c6f6d5;">
                <h5 style="margin-top:0; color:#38a169;">مواعيد التسليم المتوقعة</h5>
                <div style="font-size:24px; font-weight:800;"><?php echo $delivery_today; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- 10. Advanced Archiving System -->
<div id="shipment-archiving" class="shipping-internal-tab" style="display: <?php echo $sub == 'archiving' ? 'block' : 'none'; ?>;">
    <?php
    $archived_shipments = $wpdb->get_results("SELECT s.*, c.name as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.is_archived = 1 ORDER BY s.updated_at DESC");
    ?>
    <div class="shipping-card">
        <h4>أرشفة الشحنات والاسترجاع</h4>
        <div style="display:flex; gap:10px; margin-bottom:20px; background:#f1f5f9; padding:15px; border-radius:10px;">
            <input type="date" class="shipping-input" placeholder="من تاريخ">
            <input type="date" class="shipping-input" placeholder="إلى تاريخ">
            <select class="shipping-select"><option value="">كل العملاء</option></select>
            <button class="shipping-btn" style="width:auto;">بحث في الأرشيف</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الشحنة</th><th>تاريخ التحديث</th><th>العميل</th><th>الحالة النهائية</th></tr></thead>
                <tbody>
                    <?php if(empty($archived_shipments)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px;">الأرشيف فارغ.</td></tr>
                    <?php else: foreach($archived_shipments as $s): ?>
                        <tr>
                            <td><strong><?php echo $s->shipment_number; ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($s->updated_at)); ?></td>
                            <td><?php echo esc_html($s->customer_name); ?></td>
                            <td><span class="shipping-badge shipping-badge-low"><?php echo $s->status; ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 8. Bulk Shipment Entry -->
<div id="shipment-bulk" class="shipping-internal-tab" style="display: <?php echo $sub == 'bulk' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>إدخال الشحنات بالجملة</h4>
        <p style="color:#64748b; font-size:13px;">يرجى لصق بيانات الشحنات بتنسيق JSON أو استخدام واجهة الإدخال المتعدد.</p>
        <textarea id="bulk-rows" class="shipping-textarea" rows="10" placeholder='[{"shipment_number":"SHP-001", "customer_id":1, "origin":"Cairo", "destination":"Dubai", "weight":10.5, "dimensions":"30x30x30", "classification":"express"}]'></textarea>
        <button class="shipping-btn" style="margin-top:15px;" onclick="processBulkShipments()">معالجة وإدراج الشحنات</button>
    </div>
</div>

<script>
document.getElementById('shipping-create-shipment-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'shipping_create_shipment');
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_shipment_action"); ?>');

    fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
        if(res.success) {
            shippingShowNotification('تم إنشاء الشحنة بنجاح برقم: ' + res.data);
            this.reset();
        } else alert(res.data);
    });
});

function trackShipment() {
    const num = document.getElementById('track-number').value;
    if(!num) return alert('يرجى إدخال رقم الشحنة');
    const nonce = '<?php echo wp_create_nonce("shipping_shipment_action"); ?>';

    fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + encodeURIComponent(num) + '&nonce=' + nonce).then(r=>r.json()).then(res=>{
        if(res.success) {
            const s = res.data;
            document.getElementById('res-number').innerText = s.shipment_number;
            document.getElementById('res-status').innerText = s.status;
            document.getElementById('res-route').innerText = s.origin + ' ← ' + s.destination;

            let timelineHtml = '';
            if(s.events && s.events.length > 0) {
                s.events.forEach((ev, idx) => {
                    timelineHtml += `
                        <div class="tracking-event ${idx === 0 ? 'active' : ''}">
                            <div style="font-weight:700; color:var(--shipping-dark-color);">${ev.status}</div>
                            <div style="font-size:12px; color:#64748b;">${ev.created_at} - ${ev.location || ''}</div>
                            <div style="font-size:13px; margin-top:5px;">${ev.description || ''}</div>
                        </div>
                    `;
                });
            } else {
                timelineHtml = '<p>لا توجد أحداث تتبع مسجلة.</p>';
            }
            document.getElementById('res-timeline').innerHTML = timelineHtml;
            document.getElementById('tracking-result').style.display = 'block';
        } else alert('لم يتم العثور على الشحنة');
    });
}

function processBulkShipments() {
    const rowsRaw = document.getElementById('bulk-rows').value;
    if(!rowsRaw) return alert('يرجى إدخال البيانات');

    try {
        JSON.parse(rowsRaw);
    } catch(e) {
        return alert('تنسيق البيانات غير صحيح، يرجى التأكد من كتابة JSON بشكل سليم.');
    }

    const fd = new FormData();
    fd.append('action', 'shipping_bulk_shipments');
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_shipment_action"); ?>');
    fd.append('rows', rowsRaw);

    fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
        if(res.success) shippingShowNotification('تمت معالجة ' + res.data + ' شحنة بنجاح');
        else alert(res.data);
    });
}
</script>

<style>
.tracking-timeline::before {
    content: ""; position: absolute; right: 8px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;
}
.tracking-event { position: relative; padding-bottom: 20px; }
.tracking-event::after {
    content: ""; position: absolute; right: -25px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #cbd5e0; border: 2px solid #fff;
}
.tracking-event.active::after { background: var(--shipping-primary-color); box-shadow: 0 0 0 4px rgba(246, 48, 73, 0.2); }
</style>
