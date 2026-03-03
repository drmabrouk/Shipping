<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'create-shipment';
?>
<div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
    <button class="shipping-tab-btn <?php echo $sub == 'registry' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-registry', this)">سجل الشحنات</button>
    <button class="shipping-tab-btn <?php echo $sub == 'tracking' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-tracking', this)">تتبع الشحنات</button>
    <button class="shipping-tab-btn <?php echo $sub == 'monitoring' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-monitoring', this)">مراقبة الحالة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'schedule' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-schedule', this)">جدول الشحن</button>
    <button class="shipping-tab-btn <?php echo $sub == 'archiving' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-archiving', this)">الأرشفة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'bulk' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipment-bulk', this)">إدخال بالجملة</button>
</div>

<!-- Shipment Registry -->
<div id="shipment-registry" class="shipping-internal-tab" style="display: <?php echo ($sub == 'registry' || $sub == 'create-shipment') ? 'block' : 'none'; ?>;">
    <?php
    $all_shipments = $wpdb->get_results("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.is_archived = 0 ORDER BY s.created_at DESC");
    ?>
    <div class="shipping-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h4 style="margin:0;">سجل الشحنات الشامل</h4>
            <button onclick="ShipmentsController.openCreationModal()" class="shipping-btn" style="width:auto;">+ إضافة شحنة جديدة</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>العميل</th>
                        <th>النوع</th>
                        <th>المسار</th>
                        <th>الوزن</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($all_shipments)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد شحنات مسجلة حالياً.</td></tr>
                    <?php else: foreach($all_shipments as $s):
                        // Determine if domestic or international based on origin/destination (simple logic for demo)
                        $is_intl = (strpos(strtolower($s->origin), 'saudi') === false && strpos(strtolower($s->destination), 'saudi') === false && !empty($s->origin)) || (strpos(strtolower($s->origin), 'saudi') !== strpos(strtolower($s->destination), 'saudi'));
                        // Actually let's assume if it contains a comma and different countries.
                        // For simplicity, let's use a classification meta or just random pastel indicator.
                        $type_label = $is_intl ? 'دولية' : 'محلية';
                        $type_class = $is_intl ? 'badge-intl' : 'badge-dom';
                    ?>
                        <tr>
                            <td><strong><?php echo $s->shipment_number; ?></strong></td>
                            <td><?php echo esc_html($s->customer_name); ?></td>
                            <td><span class="pastel-badge <?php echo $type_class; ?>"><?php echo $type_label; ?></span></td>
                            <td><div style="font-size:11px;"><?php echo $s->origin; ?> <br> ➔ <?php echo $s->destination; ?></div></td>
                            <td><?php echo $s->weight; ?> كجم</td>
                            <td><span class="pastel-badge status-<?php echo $s->status; ?>"><?php echo $s->status; ?></span></td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <button class="shipping-btn" style="padding:5px 10px; font-size:11px;" onclick="ShipmentsController.quickTrack('<?php echo $s->shipment_number; ?>', <?php echo $s->id; ?>, this)">تتبع</button>
                                    <button class="shipping-btn" style="padding:5px 10px; font-size:11px; background:#319795;" onclick="ShipmentsController.viewFullDossier(<?php echo $s->id; ?>)">تفاصيل</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Creation Modal -->
<div id="modal-create-shipment" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 900px;">
        <div class="shipping-modal-header">
            <h3>إنشاء شحنة جديدة</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-create-shipment')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <div class="shipping-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
                <form id="shipping-create-shipment-form" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="shipping-form-group" style="grid-column: span 2;">
                        <label>العميل:</label>
                        <select name="customer_id" class="shipping-select" required>
                            <option value="">اختر العميل...</option>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers ORDER BY first_name ASC");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>دولة الانطلاق:</label>
                        <select name="origin_country" class="shipping-select origin-country-select" required>
                            <option value="">اختر الدولة...</option>
                            <option value="Saudi Arabia" selected>السعودية</option>
                            <option value="UAE">الإمارات</option>
                            <option value="Egypt">مصر</option>
                            <option value="Oman">عمان</option>
                            <option value="Qatar">قطر</option>
                            <option value="Jordan">الأردن</option>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>مدينة الانطلاق:</label>
                        <select name="origin_city" class="shipping-select origin-city-select" required>
                            <option value="">اختر المدينة...</option>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>دولة الوصول:</label>
                        <select name="destination_country" class="shipping-select destination-country-select" required>
                            <option value="">اختر الدولة...</option>
                            <option value="Saudi Arabia" selected>السعودية</option>
                            <option value="UAE">الإمارات</option>
                            <option value="Egypt">مصر</option>
                            <option value="Oman">عمان</option>
                            <option value="Qatar">قطر</option>
                            <option value="Jordan">الأردن</option>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>مدينة الوصول:</label>
                        <select name="destination_city" class="shipping-select destination-city-select" required>
                            <option value="">اختر المدينة...</option>
                        </select>
                    </div>

                    <div class="shipping-form-group">
                        <label>الوزن (كجم):</label>
                        <input type="number" name="weight" id="shipment-weight" step="0.01" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label>المسافة التقريبية (كم):</label>
                        <input type="number" name="distance" id="shipment-distance" class="shipping-input" placeholder="0">
                    </div>

                    <div class="shipping-form-group"><label>الأبعاد (L x W x H):</label><input type="text" name="dimensions" class="shipping-input" placeholder="30x30x30"></div>

                    <div class="shipping-form-group">
                        <label>التصنيف:</label>
                        <select name="classification" id="shipment-classification" class="shipping-select">
                            <option value="standard">قياسي (Standard)</option>
                            <option value="express">سريع (Express)</option>
                            <option value="priority">أولوية (Priority)</option>
                            <option value="fragile">قابل للكسر (Fragile)</option>
                        </select>
                    </div>

                    <div class="shipping-form-group" style="grid-column: span 2; border-top: 1px solid #eee; padding-top: 10px; margin-top: 5px;">
                        <label style="font-weight: 700;">خيارات إضافية:</label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <label><input type="checkbox" name="is_urgent" value="1"> شحن مستعجل</label>
                            <label><input type="checkbox" name="is_insured" value="1"> تأمين الشحنة</label>
                        </div>
                    </div>

                    <div class="shipping-form-group"><label>تاريخ الاستلام:</label><input type="datetime-local" name="pickup_date" class="shipping-input"></div>
                    <div class="shipping-form-group"><label>تاريخ التسليم المتوقع:</label><input type="datetime-local" name="delivery_date" class="shipping-input"></div>

                    <div class="shipping-form-group">
                        <label>المركبة (Fleet):</label>
                        <select name="carrier_id" class="shipping-select">
                            <option value="0">غير محدد</option>
                            <?php
                            $fleet = $wpdb->get_results("SELECT id, vehicle_number FROM {$wpdb->prefix}shipping_fleet WHERE status = 'available'");
                            foreach($fleet as $v) echo "<option value='{$v->id}'>".esc_html($v->vehicle_number)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>المسار (Route):</label>
                        <select name="route_id" class="shipping-select">
                            <option value="0">اختر المسار...</option>
                            <?php
                            $routes = $wpdb->get_results("SELECT id, route_name FROM {$wpdb->prefix}shipping_logistics");
                            foreach($routes as $r) echo "<option value='{$r->id}'>".esc_html($r->route_name)."</option>";
                            ?>
                        </select>
                    </div>

                    <input type="hidden" name="order_id" id="shipment-order-id-input" value="">
                    <input type="hidden" name="estimated_cost" id="shipment-estimated-cost-input" value="0">
                    <button type="submit" class="shipping-btn" style="grid-column: span 2; height: 50px; font-weight: 800; margin-top: 10px;">تأكيد وإنشاء الشحنة</button>
                </form>

                <div class="shipping-card" id="realtime-cost-card" style="background: #f8fafc; border: 2px solid #e2e8f0; margin: 0;">
                    <h4 style="margin-top:0; color: #4a5568;">ملخص التكلفة</h4>
                    <div id="cost-loader" style="display: none; text-align: center; padding: 20px;">
                        <span class="dashicons dashicons-update spin" style="font-size: 30px; width: 30px; height: 30px;"></span>
                    </div>
                    <div id="cost-details">
                        <div style="text-align: center; padding: 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e0; margin-bottom: 20px;">
                            <div style="font-size: 0.8em; color: #718096;">التكلفة المتوقعة</div>
                            <div style="font-size: 2em; font-weight: 900; color: var(--shipping-primary-color);" id="display-cost">0.00</div>
                            <div style="font-weight: 700; color: #4a5568; font-size: 12px;"><?php echo esc_html($currency); ?></div>
                        </div>
                        <div id="cost-breakdown-list" style="font-size: 12px; color: #4a5568;">
                            <p style="text-align: center; opacity: 0.7;">أدخل بيانات الشحنة للحساب.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. & 3. Tracking & Live Status Engine -->
<div id="shipment-tracking" class="shipping-internal-tab" style="display: <?php echo $sub == 'tracking' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>تتبع الشحنات المباشر</h4>
        <div style="display:flex; gap:10px; margin-bottom:20px;">
            <input type="text" id="track-number" class="shipping-input" placeholder="ادخل رقم الشحنة (مثال: SHP-XXXXXX)">
            <button class="shipping-btn" style="width:auto;" onclick="ShipmentsController.trackShipment()">بحث وتتبع</button>
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

<!-- Shipment Logs Modal -->
<div id="modal-shipment-logs" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 600px;">
        <div class="shipping-modal-header">
            <h3>سجل تتبع الشحنة: <span id="log-shipment-num"></span></h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-shipment-logs')">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <div id="shipment-logs-timeline" class="shipping-timeline" style="padding: 20px;">
                <!-- Logs loaded via AJAX -->
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
                                <div style="display: flex; gap: 5px;">
                                    <button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#319795;" onclick="ShipmentsController.viewFullDossier(<?php echo $s->id; ?>)">الملف الكامل</button>
                                    <button class="shipping-btn shipping-btn-outline" style="padding:4px 8px; font-size:11px;" onclick="ShipmentsController.quickTrack('<?php echo $s->shipment_number; ?>', <?php echo $s->id; ?>, this)">تتبع</button>
                                    <button class="shipping-btn shipping-btn-outline" style="padding:4px 8px; font-size:11px;" onclick="ShipmentsController.viewLogs(<?php echo $s->id; ?>, '<?php echo $s->shipment_number; ?>')">السجل</button>
                                </div>
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
    $pickups = $wpdb->get_results($wpdb->prepare("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as name FROM {$wpdb->prefix}shipping_shipments s JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE DATE(pickup_date) = %s", $today));
    $dispatches = $wpdb->get_results($wpdb->prepare("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as name FROM {$wpdb->prefix}shipping_shipments s JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE DATE(dispatch_date) = %s", $today));
    $deliveries = $wpdb->get_results($wpdb->prepare("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as name FROM {$wpdb->prefix}shipping_shipments s JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE DATE(delivery_date) = %s", $today));
    ?>
    <div class="shipping-card">
        <h4>جدول الشحن والمهام لليوم (<?php echo $today; ?>)</h4>

        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:20px;">
            <!-- Pickups -->
            <div style="background:#fffaf0; border:1px solid #feebc8; border-radius:12px; padding:20px;">
                <h5 style="margin:0 0 15px 0; color:#dd6b20;">مهام الاستلام (${<?php echo count($pickups); ?>})</h5>
                <div style="display:grid; gap:10px;">
                    <?php if(empty($pickups)) echo '<p style="font-size:12px; opacity:0.6;">لا توجد مهام</p>';
                    foreach($pickups as $p): ?>
                        <div style="background:#fff; padding:10px; border-radius:8px; font-size:12px; border:1px solid #fbd38d;">
                            <strong><?php echo $p->shipment_number; ?></strong><br>
                            <span style="color:#718096;"><?php echo esc_html($p->name); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dispatches -->
            <div style="background:#ebf8ff; border:1px solid #bee3f8; border-radius:12px; padding:20px;">
                <h5 style="margin:0 0 15px 0; color:#3182ce;">مهام الانطلاق (${<?php echo count($dispatches); ?>})</h5>
                <div style="display:grid; gap:10px;">
                    <?php if(empty($dispatches)) echo '<p style="font-size:12px; opacity:0.6;">لا توجد مهام</p>';
                    foreach($dispatches as $d): ?>
                        <div style="background:#fff; padding:10px; border-radius:8px; font-size:12px; border:1px solid #90cdf4;">
                            <strong><?php echo $d->shipment_number; ?></strong><br>
                            <span style="color:#718096;"><?php echo esc_html($d->name); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Deliveries -->
            <div style="background:#f0fff4; border:1px solid #c6f6d5; border-radius:12px; padding:20px;">
                <h5 style="margin:0 0 15px 0; color:#38a169;">مهام التسليم (${<?php echo count($deliveries); ?>})</h5>
                <div style="display:grid; gap:10px;">
                    <?php if(empty($deliveries)) echo '<p style="font-size:12px; opacity:0.6;">لا توجد مهام</p>';
                    foreach($deliveries as $del): ?>
                        <div style="background:#fff; padding:10px; border-radius:8px; font-size:12px; border:1px solid #9ae6b4;">
                            <strong><?php echo $del->shipment_number; ?></strong><br>
                            <span style="color:#718096;"><?php echo esc_html($del->name); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 10. Advanced Archiving System -->
<div id="shipment-archiving" class="shipping-internal-tab" style="display: <?php echo $sub == 'archiving' ? 'block' : 'none'; ?>;">
    <?php
    $archived_shipments = $wpdb->get_results("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id WHERE s.is_archived = 1 ORDER BY s.updated_at DESC");
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

<!-- Full Dossier Modal -->
<div id="modal-full-dossier" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 900px;">
        <div class="shipping-modal-header" style="background: var(--shipping-dark-color); color: #fff;">
            <h3>ملف البيانات الموحد للشحنة: <span id="dossier-num"></span></h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-full-dossier')" style="color:#fff;">&times;</button>
        </div>
        <div class="shipping-modal-body" id="dossier-content" style="padding: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Content injected via JS -->
        </div>
    </div>
</div>

<!-- 8. Bulk Shipment Entry -->
<div id="shipment-bulk" class="shipping-internal-tab" style="display: <?php echo $sub == 'bulk' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>إدخال الشحنات بالجملة</h4>
        <p style="color:#64748b; font-size:13px;">يرجى لصق بيانات الشحنات بتنسيق JSON أو استخدام واجهة الإدخال المتعدد.</p>
        <textarea id="bulk-rows" class="shipping-textarea" rows="10" placeholder='[{"shipment_number":"SHP-001", "customer_id":1, "origin":"Cairo", "destination":"Dubai", "weight":10.5, "dimensions":"30x30x30", "classification":"express"}]'></textarea>
        <button class="shipping-btn" style="margin-top:15px;" onclick="ShipmentsController.processBulk()">معالجة وإدراج الشحنات</button>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    ShipmentsController.init();
});
</script>

<style>
.pastel-badge {
    padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-block;
}
.badge-intl { background: #e9d8fd; color: #553c9a; }
.badge-dom { background: #bee3f8; color: #2b6cb0; }

.status-pending { background: #fed7d7; color: #9b2c2c; }
.status-in-transit { background: #feebc8; color: #9c4221; }
.status-delivered { background: #c6f6d5; color: #22543d; }
.status-cancelled { background: #edf2f7; color: #4a5568; }

.spin { animation: spin 2s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

.tracking-timeline::before {
    content: ""; position: absolute; right: 8px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;
}
.tracking-event { position: relative; padding-bottom: 20px; }
.tracking-event::after {
    content: ""; position: absolute; right: -25px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #cbd5e0; border: 2px solid #fff;
}
.tracking-event.active::after { background: var(--shipping-primary-color); box-shadow: 0 0 0 4px rgba(246, 48, 73, 0.2); }
</style>
