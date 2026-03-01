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
        <button class="shipping-tab-btn <?php echo $sub == 'analytics' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('logistic-analytics', this)">التحليلات والتقارير</button>
    </div>
</div>

<div id="logistic-live" class="shipping-internal-tab" style="display: <?php echo $sub == 'live-tracking' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">خريطة التتبع المباشر للشحنات</h4>
            <button class="shipping-btn" onclick="shippingRefreshTrackingMap()">تحديث الخريطة</button>
        </div>
        <div id="tracking-map" style="height: 500px; border-radius: 12px; border: 1px solid #eee;"></div>

        <div style="margin-top: 30px;">
            <h5>قائمة الشحنات النشطة</h5>
            <div class="shipping-table-container">
                <table class="shipping-table">
                    <thead>
                        <tr>
                            <th>رقم الشحنة</th>
                            <th>الحالة</th>
                            <th>الموقع الحالي</th>
                            <th>آخر تحديث</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="active-shipments-list">
                        <!-- Loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Location Modal -->
<div id="modal-update-location" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 400px;">
        <div class="shipping-modal-header">
            <h3>تحديث موقع الشحنة</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('modal-update-location').style.display='none'">&times;</button>
        </div>
        <form id="form-update-location" style="padding: 20px;">
            <input type="hidden" name="action" value="shipping_update_shipment_location">
            <input type="hidden" name="id" id="update-shipment-id">
            <?php wp_nonce_field('shipping_shipment_action', 'nonce'); ?>
            <div class="shipping-form-group">
                <label>اسم الموقع (نصي)</label>
                <input type="text" name="location" class="shipping-input" placeholder="مثال: مستودع الإسكندرية" required>
            </div>
            <div class="shipping-form-group">
                <label>خط العرض (Lat)</label>
                <input type="number" name="lat" class="shipping-input" step="0.00000001" required>
            </div>
            <div class="shipping-form-group">
                <label>خط الطول (Lng)</label>
                <input type="number" name="lng" class="shipping-input" step="0.00000001" required>
            </div>
            <div class="shipping-form-group">
                <label>تحديث الحالة</label>
                <select name="status" class="shipping-select">
                    <option value="in-transit">قيد النقل</option>
                    <option value="out-for-delivery">خارج للتوصيل</option>
                    <option value="arrived-at-hub">وصل للمركز</option>
                    <option value="delayed">متأخر</option>
                </select>
            </div>
            <button type="submit" class="shipping-btn" style="width: 100%;">تحديث الموقع</button>
        </form>
    </div>
</div>

<div id="logistic-routes" class="shipping-internal-tab" style="display: <?php echo $sub == 'routes' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">تخطيط مسارات الشحن</h4>
        <button class="shipping-btn" onclick="shippingOpenRouteModal()">+ إضافة مسار جديد</button>
    </div>

    <div class="shipping-table-container">
        <table class="shipping-table">
            <thead>
                <tr>
                    <th>اسم المسار</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>المسافة</th>
                    <th>المدة المتوقعة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="route-list-body">
                <!-- Routes will be loaded here -->
            </tbody>
        </table>
    </div>
</div>

<div id="logistic-stops" class="shipping-internal-tab" style="display: <?php echo $sub == 'stop-points' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">إدارة نقاط التوقف للمسار: <span id="selected-route-stops-name">الرجاء اختيار مسار</span></h4>
        <button class="shipping-btn" id="btn-add-stop" onclick="shippingOpenStopModal()" disabled>+ إضافة نقطة توقف</button>
    </div>

    <div class="shipping-table-container">
        <table class="shipping-table">
            <thead>
                <tr>
                    <th>الترتيب</th>
                    <th>اسم النقطة</th>
                    <th>الموقع</th>
                    <th>الإحداثيات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="stops-list-body">
                <tr><td colspan="5" style="text-align:center;">اختر مساراً من تبويب المسارات لعرض نقاط التوقف.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Route Modal -->
<div id="modal-route" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="route-modal-title">إضافة مسار</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('modal-route').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-route" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_route">
                <input type="hidden" name="id" id="route-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم المسار</label>
                    <input type="text" name="route_name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>نقطة البداية</label>
                    <input type="text" name="start_location" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>نقطة النهاية</label>
                    <input type="text" name="end_location" class="shipping-input" required>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="shipping-form-group">
                        <label>المسافة (كم)</label>
                        <input type="number" name="total_distance" class="shipping-input" step="0.1">
                    </div>
                    <div class="shipping-form-group">
                        <label>المدة المتوقعة</label>
                        <input type="text" name="estimated_duration" class="shipping-input" placeholder="مثال: 5 ساعات">
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>وصف المسار</label>
                    <textarea name="description" class="shipping-textarea" rows="3"></textarea>
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ المسار</button>
            </form>
        </div>
    </div>
</div>

<!-- Stop Modal -->
<div id="modal-stop" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="stop-modal-title">إضافة نقطة توقف</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('modal-stop').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-stop" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_route_stop">
                <input type="hidden" name="id" id="stop-id">
                <input type="hidden" name="route_id" id="stop-route-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم النقطة</label>
                    <input type="text" name="stop_name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>الموقع الوصفي</label>
                    <input type="text" name="location" class="shipping-input">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="shipping-form-group">
                        <label>خط العرض (Lat)</label>
                        <input type="number" name="lat" class="shipping-input" step="0.00000001">
                    </div>
                    <div class="shipping-form-group">
                        <label>خط الطول (Lng)</label>
                        <input type="number" name="lng" class="shipping-input" step="0.00000001">
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>الترتيب في المسار</label>
                    <input type="number" name="stop_order" class="shipping-input" value="1">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ النقطة</button>
            </form>
        </div>
    </div>
</div>

<div id="logistic-warehouse" class="shipping-internal-tab" style="display: <?php echo $sub == 'warehouse' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">إدارة المستودعات والتخزين</h4>
        <button class="shipping-btn" onclick="shippingOpenWarehouseModal()">+ إضافة مستودع جديد</button>
    </div>

    <div id="warehouse-list-container" class="shipping-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <!-- Warehouses will be loaded here via AJAX -->
    </div>

    <div id="inventory-section" style="display: none; margin-top: 40px; border-top: 2px solid #eee; padding-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">مخزون مستودع: <span id="selected-warehouse-name"></span></h4>
            <button class="shipping-btn" onclick="shippingOpenInventoryModal()">+ إضافة صنف للمخزون</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>اسم الصنف</th>
                        <th>SKU</th>
                        <th>الكمية</th>
                        <th>الوحدة</th>
                        <th>آخر تحديث</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="inventory-list-body">
                    <!-- Inventory items will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Warehouse Modal -->
<div id="modal-warehouse" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="warehouse-modal-title">إضافة مستودع</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('modal-warehouse').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-warehouse" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_warehouse">
                <input type="hidden" name="id" id="warehouse-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم المستودع</label>
                    <input type="text" name="name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>الموقع</label>
                    <input type="text" name="location" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>السعة الإجمالية (متر مكعب)</label>
                    <input type="number" name="total_capacity" class="shipping-input" step="0.1" required>
                </div>
                <div class="shipping-form-group">
                    <label>اسم المدير</label>
                    <input type="text" name="manager_name" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>رقم التواصل</label>
                    <input type="text" name="contact_number" class="shipping-input">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ البيانات</button>
            </form>
        </div>
    </div>
</div>

<!-- Inventory Modal -->
<div id="modal-inventory" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="inventory-modal-title">إضافة صنف</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('modal-inventory').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-inventory" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_inventory_item">
                <input type="hidden" name="id" id="inventory-id">
                <input type="hidden" name="warehouse_id" id="inventory-warehouse-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>اسم الصنف</label>
                    <input type="text" name="item_name" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>SKU (رمز الصنف)</label>
                    <input type="text" name="sku" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>الكمية</label>
                    <input type="number" name="quantity" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>الوحدة</label>
                    <input type="text" name="unit" class="shipping-input" placeholder="قطعة، كجم، إلخ">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ الصنف</button>
            </form>
        </div>
    </div>
</div>

<div id="logistic-analytics" class="shipping-internal-tab" style="display: <?php echo $sub == 'analytics' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">تحليلات الأداء والخدمات اللوجستية</h4>
        <button class="shipping-btn" onclick="shippingLoadLogisticsAnalytics()">تحديث البيانات</button>
    </div>

    <div class="shipping-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <div class="shipping-card">
            <h5>حالة الشحنات النشطة</h5>
            <canvas id="chart-shipment-status" height="200"></canvas>
        </div>
        <div class="shipping-card">
            <h5>حالة الأسطول والمركبات</h5>
            <canvas id="chart-fleet-status" height="200"></canvas>
        </div>
        <div class="shipping-card">
            <h5>نسبة إشغال المستودعات</h5>
            <canvas id="chart-warehouse-utilization" height="200"></canvas>
        </div>
        <div class="shipping-card">
            <h5>تكاليف صيانة الأسطول</h5>
            <div style="text-align: center; padding: 40px 0;">
                <div style="font-size: 14px; color: #666;">إجمالي التكاليف المسجلة</div>
                <div style="font-size: 32px; font-weight: 800; color: var(--shipping-primary-color);" id="total-maintenance-cost">0.00 SAR</div>
            </div>
        </div>
    </div>

    <div class="shipping-card" style="margin-top: 30px;">
        <h5>سجل التتبع التاريخي (Historical Tracking)</h5>
        <div class="shipping-form-group" style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="history-shipment-number" class="shipping-input" placeholder="أدخل رقم الشحنة (مثلاً: SHP-XXXX)">
            <button class="shipping-btn" onclick="shippingSearchHistory()">بحث</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>الوقت</th>
                        <th>الحالة</th>
                        <th>الموقع</th>
                        <th>الوصف</th>
                    </tr>
                </thead>
                <tbody id="history-list-body">
                    <tr><td colspan="4" style="text-align:center;">أدخل رقم الشحنة لعرض السجل التاريخي.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="logistic-fleet" class="shipping-internal-tab" style="display: <?php echo $sub == 'fleet' ? 'block' : 'none'; ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="margin: 0;">إدارة الأسطول والمركبات</h4>
        <button class="shipping-btn" onclick="shippingOpenVehicleModal()">+ إضافة مركبة جديدة</button>
    </div>

    <div id="fleet-list-container" class="shipping-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        <!-- Vehicles will be loaded here via AJAX -->
    </div>

    <div id="maintenance-section" style="display: none; margin-top: 40px; border-top: 2px solid #eee; padding-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="margin: 0;">سجل الصيانة للمركبة: <span id="selected-vehicle-number"></span></h4>
            <button class="shipping-btn" onclick="shippingOpenMaintenanceModal()">+ إضافة سجل صيانة</button>
        </div>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>نوع الصيانة</th>
                        <th>التاريخ</th>
                        <th>التكلفة</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="maintenance-list-body">
                    <!-- Maintenance logs will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Vehicle Modal -->
<div id="modal-vehicle" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="vehicle-modal-title">إضافة مركبة</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('modal-vehicle').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-vehicle" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_vehicle">
                <input type="hidden" name="id" id="vehicle-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>رقم المركبة</label>
                    <input type="text" name="vehicle_number" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>نوع المركبة</label>
                    <input type="text" name="vehicle_type" class="shipping-input" placeholder="مثال: شاحنة نقل ثقيل" required>
                </div>
                <div class="shipping-form-group">
                    <label>الحمولة القصوى (طن)</label>
                    <input type="number" name="capacity" class="shipping-input" step="0.1" required>
                </div>
                <div class="shipping-form-group">
                    <label>اسم السائق</label>
                    <input type="text" name="driver_name" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>رقم هاتف السائق</label>
                    <input type="text" name="driver_phone" class="shipping-input">
                </div>
                <div class="shipping-form-group">
                    <label>تاريخ الصيانة القادمة</label>
                    <input type="date" name="next_maintenance_date" class="shipping-input">
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ البيانات</button>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance Modal -->
<div id="modal-maintenance" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h3 id="maintenance-modal-title">إضافة سجل صيانة</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('modal-maintenance').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <form id="form-maintenance" style="padding: 20px;">
                <input type="hidden" name="action" value="shipping_add_maintenance_log">
                <input type="hidden" name="id" id="maintenance-id">
                <input type="hidden" name="vehicle_id" id="maintenance-vehicle-id">
                <?php wp_nonce_field('shipping_logistic_action', 'nonce'); ?>
                <div class="shipping-form-group">
                    <label>نوع الصيانة</label>
                    <input type="text" name="maintenance_type" class="shipping-input" placeholder="مثال: تغيير زيت، فحص دوري" required>
                </div>
                <div class="shipping-form-group">
                    <label>الوصف</label>
                    <textarea name="description" class="shipping-textarea" rows="3"></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>التكلفة</label>
                    <input type="number" name="cost" class="shipping-input" step="0.01" required>
                </div>
                <div class="shipping-form-group">
                    <label>التاريخ</label>
                    <input type="date" name="maintenance_date" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label><input type="checkbox" name="completed" value="1"> تمت العملية</label>
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%;">حفظ السجل</button>
            </form>
        </div>
    </div>
</div>

<script>
(function($) {
    // Warehouse Management JS
    window.shippingOpenWarehouseModal = function(warehouse = null) {
        const form = document.getElementById('form-warehouse');
        const modal = document.getElementById('modal-warehouse');
        form.reset();
        document.getElementById('warehouse-id').value = '';
        document.getElementById('warehouse-modal-title').innerText = warehouse ? 'تعديل مستودع' : 'إضافة مستودع جديد';
        // Handle hidden action field differently if needed, but here it works
        const actionField = form.querySelector('input[name="action"]');
        actionField.value = warehouse ? 'shipping_update_warehouse' : 'shipping_add_warehouse';

        if (warehouse) {
            document.getElementById('warehouse-id').value = warehouse.id;
            form.name.value = warehouse.name;
            form.location.value = warehouse.location;
            form.total_capacity.value = warehouse.total_capacity;
            form.manager_name.value = warehouse.manager_name;
            form.contact_number.value = warehouse.contact_number;
        }
        modal.style.display = 'flex';
    };

    window.shippingLoadWarehouses = function() {
        fetch(ajaxurl + '?action=shipping_get_warehouses')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const container = document.getElementById('warehouse-list-container');
                container.innerHTML = res.data.map(w => `
                    <div class="shipping-card warehouse-card" onclick="shippingSelectWarehouse(${w.id}, '${w.name}')">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <h5 style="margin:0;">${w.name}</h5>
                            <span class="shipping-badge">${w.location}</span>
                        </div>
                        <div style="margin-top:15px; font-size:13px; color:#666;">
                            <div><strong>السعة:</strong> ${w.available_capacity} / ${w.total_capacity} m³</div>
                            <div><strong>المدير:</strong> ${w.manager_name || 'N/A'}</div>
                        </div>
                        <div style="margin-top:15px; display:flex; gap:10px;">
                            <button class="shipping-btn-outline" onclick="event.stopPropagation(); shippingOpenWarehouseModal(${JSON.stringify(w).replace(/"/g, '&quot;')})" style="padding:5px 10px; font-size:11px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:5px 10px; font-size:11px;" onclick="event.stopPropagation(); shippingDeleteWarehouse(${w.id})">حذف</button>
                        </div>
                    </div>
                `).join('') || '<p style="text-align:center; grid-column:1/-1;">لا توجد مستودعات مسجلة.</p>';
            }
        });
    };

    window.shippingDeleteWarehouse = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا المستودع وكافة محتوياته؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_warehouse');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_logistic_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) shippingLoadWarehouses();
            else alert(res.data);
        });
    };

    window.shippingSelectWarehouse = function(id, name) {
        document.getElementById('inventory-warehouse-id').value = id;
        document.getElementById('selected-warehouse-name').innerText = name;
        document.getElementById('inventory-section').style.display = 'block';
        shippingLoadInventory(id);
        // Highlight active card
        document.querySelectorAll('.warehouse-card').forEach(c => c.style.borderColor = '#eee');
        event.currentTarget.style.borderColor = 'var(--shipping-primary-color)';
    };

    window.shippingLoadInventory = function(warehouseId) {
        fetch(ajaxurl + '?action=shipping_get_inventory&warehouse_id=' + warehouseId)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const body = document.getElementById('inventory-list-body');
                body.innerHTML = res.data.map(i => `
                    <tr>
                        <td>${i.item_name}</td>
                        <td>${i.sku || '-'}</td>
                        <td><strong>${i.quantity}</strong></td>
                        <td>${i.unit}</td>
                        <td>${i.last_updated}</td>
                        <td>
                            <button class="shipping-btn-outline" onclick='shippingOpenInventoryModal(${JSON.stringify(i).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:10px;" onclick="shippingDeleteInventoryItem(${i.id})">حذف</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="6" style="text-align:center;">لا توجد أصناف في هذا المستودع.</td></tr>';
            }
        });
    };

    window.shippingOpenInventoryModal = function(item = null) {
        const form = document.getElementById('form-inventory');
        const modal = document.getElementById('modal-inventory');
        form.reset();
        document.getElementById('inventory-id').value = '';
        document.getElementById('inventory-modal-title').innerText = item ? 'تعديل صنف' : 'إضافة صنف جديد';
        form.action.value = item ? 'shipping_update_inventory_item' : 'shipping_add_inventory_item';

        if (item) {
            document.getElementById('inventory-id').value = item.id;
            form.item_name.value = item.item_name;
            form.sku.value = item.sku;
            form.quantity.value = item.quantity;
            form.unit.value = item.unit;
        }
        modal.style.display = 'flex';
    };

    window.shippingDeleteInventoryItem = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا الصنف من المخزون؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_inventory_item');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_logistic_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) shippingLoadInventory(document.getElementById('inventory-warehouse-id').value);
        });
    };

    // Initialize forms
    document.getElementById('form-warehouse')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            if (res.success) {
                document.getElementById('modal-warehouse').style.display = 'none';
                shippingLoadWarehouses();
            } else alert(res.data);
        });
    });

    document.getElementById('form-inventory')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            if (res.success) {
                document.getElementById('modal-inventory').style.display = 'none';
                shippingLoadInventory(document.getElementById('inventory-warehouse-id').value);
            } else alert(res.data);
        });
    });

    // Initial load
    if ("<?php echo $sub; ?>" === 'warehouse') {
        shippingLoadWarehouses();
    } else if ("<?php echo $sub; ?>" === 'fleet') {
        shippingLoadFleet();
    } else if ("<?php echo $sub; ?>" === 'routes') {
        shippingLoadRoutes();
    } else if ("<?php echo $sub; ?>" === 'live-tracking') {
        shippingInitTrackingMap();
    } else if ("<?php echo $sub; ?>" === 'analytics') {
        shippingLoadLogisticsAnalytics();
    }

    // Route & Stop Management JS
    window.shippingOpenRouteModal = function(route = null) {
        const form = document.getElementById('form-route');
        const modal = document.getElementById('modal-route');
        form.reset();
        document.getElementById('route-id').value = '';
        document.getElementById('route-modal-title').innerText = route ? 'تعديل مسار' : 'إضافة مسار جديد';
        const actionField = form.querySelector('input[name="action"]');
        actionField.value = route ? 'shipping_update_route' : 'shipping_add_route';

        if (route) {
            document.getElementById('route-id').value = route.id;
            form.route_name.value = route.route_name;
            form.start_location.value = route.start_location;
            form.end_location.value = route.end_location;
            form.total_distance.value = route.total_distance;
            form.estimated_duration.value = route.estimated_duration;
            form.description.value = route.description;
        }
        modal.style.display = 'flex';
    };

    window.shippingLoadRoutes = function() {
        fetch(ajaxurl + '?action=shipping_get_routes')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const body = document.getElementById('route-list-body');
                body.innerHTML = res.data.map(r => `
                    <tr>
                        <td><strong>${r.route_name}</strong></td>
                        <td>${r.start_location}</td>
                        <td>${r.end_location}</td>
                        <td>${r.total_distance} كم</td>
                        <td>${r.estimated_duration}</td>
                        <td>
                            <button class="shipping-btn-outline" onclick="shippingSelectRoute(${r.id}, '${r.route_name}')" style="padding:4px 8px; font-size:10px;">النقاط</button>
                            <button class="shipping-btn-outline" onclick='shippingOpenRouteModal(${JSON.stringify(r).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:10px;" onclick="shippingDeleteRoute(${r.id})">حذف</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="6" style="text-align:center;">لا توجد مسارات مسجلة.</td></tr>';
            }
        });
    };

    window.shippingDeleteRoute = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا المسار وكافة نقاط التوقف التابعة له؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_route');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_logistic_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) shippingLoadRoutes();
        });
    };

    window.shippingSelectRoute = function(id, name) {
        document.getElementById('stop-route-id').value = id;
        document.getElementById('selected-route-stops-name').innerText = name;
        document.getElementById('btn-add-stop').disabled = false;

        // Switch to stops tab UI-wise (internal)
        const stopsBtn = document.querySelector('button[onclick*="logistic-stops"]');
        shippingOpenInternalTab('logistic-stops', stopsBtn);

        shippingLoadStops(id);
    };

    window.shippingLoadStops = function(routeId) {
        fetch(ajaxurl + '?action=shipping_get_route_stops&route_id=' + routeId)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const body = document.getElementById('stops-list-body');
                body.innerHTML = res.data.map(s => `
                    <tr>
                        <td>${s.stop_order}</td>
                        <td>${s.stop_name}</td>
                        <td>${s.location || '-'}</td>
                        <td style="font-size:11px;">${s.lat}, ${s.lng}</td>
                        <td>
                            <button class="shipping-btn-outline" onclick='shippingOpenStopModal(${JSON.stringify(s).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:10px;" onclick="shippingDeleteStop(${s.id})">حذف</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="5" style="text-align:center;">لا توجد نقاط توقف مسجلة لهذا المسار.</td></tr>';
            }
        });
    };

    window.shippingOpenStopModal = function(stop = null) {
        const form = document.getElementById('form-stop');
        const modal = document.getElementById('modal-stop');
        form.reset();
        document.getElementById('stop-id').value = '';
        document.getElementById('stop-modal-title').innerText = stop ? 'تعديل نقطة توقف' : 'إضافة نقطة توقف جديدة';
        const actionField = form.querySelector('input[name="action"]');
        actionField.value = stop ? 'shipping_update_route_stop' : 'shipping_add_route_stop';

        if (stop) {
            document.getElementById('stop-id').value = stop.id;
            form.stop_name.value = stop.stop_name;
            form.location.value = stop.location;
            form.lat.value = stop.lat;
            form.lng.value = stop.lng;
            form.stop_order.value = stop.stop_order;
        }
        modal.style.display = 'flex';
    };

    window.shippingDeleteStop = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذه النقطة؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_route_stop');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_logistic_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) shippingLoadStops(document.getElementById('stop-route-id').value);
        });
    };

    document.getElementById('form-route')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            if (res.success) { document.getElementById('modal-route').style.display = 'none'; shippingLoadRoutes(); }
            else alert(res.data);
        });
    });

    document.getElementById('form-stop')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            if (res.success) { document.getElementById('modal-stop').style.display = 'none'; shippingLoadStops(document.getElementById('stop-route-id').value); }
            else alert(res.data);
        });
    });

    // Live Tracking JS
    let trackingMap;
    let trackingMarkers = [];

    window.shippingInitTrackingMap = function() {
        if (!document.getElementById('tracking-map')) return;

        if (!trackingMap) {
            trackingMap = L.map('tracking-map').setView([23.8859, 45.0792], 6); // Center on Saudi Arabia
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(trackingMap);
        }

        shippingRefreshTrackingMap();
    };

    window.shippingRefreshTrackingMap = function() {
        fetch(ajaxurl + '?action=shipping_refresh_dashboard') // Reusing stats for now or get dedicated shipments
        .then(r => r.json())
        .then(res => {
            // Actually we need the shipments with coordinates
            shippingLoadActiveShipments();
        });
    };

    window.shippingLoadActiveShipments = function() {
        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&id=all&nonce=' + '<?php echo wp_create_nonce("shipping_shipment_action"); ?>')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const tableBody = document.getElementById('active-shipments-list');
                const shipments = res.data;

                tableBody.innerHTML = shipments.map(s => `
                    <tr>
                        <td><strong>${s.shipment_number}</strong></td>
                        <td><span class="shipping-badge">${s.status}</span></td>
                        <td>${s.location || '-'}</td>
                        <td style="font-size:11px;">${s.updated_at}</td>
                        <td>
                            <button class="shipping-btn-outline" onclick="shippingOpenUpdateLocationModal(${s.id}, ${s.current_lat}, ${s.current_lng}, '${s.location || ''}')" style="padding:4px 8px; font-size:10px;">تحديث الموقع</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="5" style="text-align:center;">لا توجد شحنات نشطة حالياً.</td></tr>';

                // Clear old markers
                trackingMarkers.forEach(m => trackingMap.removeLayer(m));
                trackingMarkers = [];

                // Add markers
                shipments.forEach(s => {
                    if (s.current_lat && s.current_lng) {
                        const marker = L.marker([s.current_lat, s.current_lng]).addTo(trackingMap);
                        marker.bindPopup(`<strong>${s.shipment_number}</strong><br>الحالة: ${s.status}<br>الموقع: ${s.location || 'N/A'}`);
                        trackingMarkers.push(marker);
                    }
                });

                if (trackingMarkers.length > 0) {
                    const group = new L.featureGroup(trackingMarkers);
                    trackingMap.fitBounds(group.getBounds().pad(0.1));
                }
            }
        });
    };

    window.shippingOpenUpdateLocationModal = function(shipmentId, currentLat, currentLng, currentLocation) {
        const form = document.getElementById('form-update-location');
        const modal = document.getElementById('modal-update-location');
        document.getElementById('update-shipment-id').value = shipmentId;
        form.lat.value = currentLat || '';
        form.lng.value = currentLng || '';
        form.location.value = currentLocation || '';
        modal.style.display = 'flex';
    };

    document.getElementById('form-update-location')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            if (res.success) {
                document.getElementById('modal-update-location').style.display = 'none';
                shippingInitTrackingMap();
            } else alert(res.data);
        });
    });

    // Analytics & History JS
    let charts = {};

    window.shippingLoadLogisticsAnalytics = function() {
        fetch(ajaxurl + '?action=shipping_get_logistics_analytics')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const data = res.data;
                document.getElementById('total-maintenance-cost').innerText = (data.total_maintenance_cost || 0) + ' SAR';

                // Shipment Status Chart
                renderChart('shipment-status', 'pie', {
                    labels: data.shipment_count_by_status.map(i => i.status),
                    datasets: [{
                        data: data.shipment_count_by_status.map(i => i.count),
                        backgroundColor: ['#4299E1', '#48BB78', '#ECC94B', '#F56565']
                    }]
                });

                // Fleet Status Chart
                renderChart('fleet-status', 'bar', {
                    labels: data.fleet_status.map(i => i.status),
                    datasets: [{
                        label: 'عدد المركبات',
                        data: data.fleet_status.map(i => i.count),
                        backgroundColor: '#805AD5'
                    }]
                });

                // Warehouse Utilization Chart
                renderChart('warehouse-utilization', 'bar', {
                    labels: data.warehouse_utilization.map(i => i.name),
                    datasets: [{
                        label: '% نسبة الإشغال',
                        data: data.warehouse_utilization.map(i => i.utilization),
                        backgroundColor: '#319795'
                    }]
                });
            }
        });
    };

    function renderChart(id, type, data) {
        const ctx = document.getElementById('chart-' + id).getContext('2d');
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, {
            type: type,
            data: data,
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    window.shippingSearchHistory = function() {
        const number = document.getElementById('history-shipment-number').value;
        if (!number) return;

        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + number + '&nonce=' + '<?php echo wp_create_nonce("shipping_shipment_action"); ?>')
        .then(r => r.json())
        .then(res => {
            const body = document.getElementById('history-list-body');
            if (res.success && res.data.events) {
                body.innerHTML = res.data.events.map(e => `
                    <tr>
                        <td>${e.created_at}</td>
                        <td><span class="shipping-badge">${e.status}</span></td>
                        <td>${e.location || '-'}</td>
                        <td>${e.description}</td>
                    </tr>
                `).join('');
            } else {
                body.innerHTML = '<tr><td colspan="4" style="text-align:center;">لم يتم العثور على شحنة بهذا الرقم.</td></tr>';
            }
        });
    };

    // Fleet Management JS
    window.shippingOpenVehicleModal = function(vehicle = null) {
        const form = document.getElementById('form-vehicle');
        const modal = document.getElementById('modal-vehicle');
        form.reset();
        document.getElementById('vehicle-id').value = '';
        document.getElementById('vehicle-modal-title').innerText = vehicle ? 'تعديل مركبة' : 'إضافة مركبة جديدة';
        const actionField = form.querySelector('input[name="action"]');
        actionField.value = vehicle ? 'shipping_update_vehicle' : 'shipping_add_vehicle';

        if (vehicle) {
            document.getElementById('vehicle-id').value = vehicle.id;
            form.vehicle_number.value = vehicle.vehicle_number;
            form.vehicle_type.value = vehicle.vehicle_type;
            form.capacity.value = vehicle.capacity;
            form.driver_name.value = vehicle.driver_name;
            form.driver_phone.value = vehicle.driver_phone;
            form.next_maintenance_date.value = vehicle.next_maintenance_date;
        }
        modal.style.display = 'flex';
    };

    window.shippingLoadFleet = function() {
        fetch(ajaxurl + '?action=shipping_get_fleet')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const container = document.getElementById('fleet-list-container');
                container.innerHTML = res.data.map(v => `
                    <div class="shipping-card vehicle-card" onclick="shippingSelectVehicle(${v.id}, '${v.vehicle_number}')">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <h5 style="margin:0;">${v.vehicle_number}</h5>
                            <span class="shipping-badge ${v.status === 'available' ? 'shipping-badge-high' : 'shipping-badge-urgent'}">${v.status}</span>
                        </div>
                        <div style="margin-top:15px; font-size:13px; color:#666;">
                            <div><strong>النوع:</strong> ${v.vehicle_type}</div>
                            <div><strong>السائق:</strong> ${v.driver_name || 'N/A'}</div>
                            <div><strong>صيانة قادمة:</strong> ${v.next_maintenance_date || '-'}</div>
                        </div>
                        <div style="margin-top:15px; display:flex; gap:10px;">
                            <button class="shipping-btn-outline" onclick="event.stopPropagation(); shippingOpenVehicleModal(${JSON.stringify(v).replace(/"/g, '&quot;')})" style="padding:5px 10px; font-size:11px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:5px 10px; font-size:11px;" onclick="event.stopPropagation(); shippingDeleteVehicle(${v.id})">حذف</button>
                        </div>
                    </div>
                `).join('') || '<p style="text-align:center; grid-column:1/-1;">لا توجد مركبات مسجلة.</p>';
            }
        });
    };

    window.shippingDeleteVehicle = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذه المركبة؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_vehicle');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_logistic_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) shippingLoadFleet();
        });
    };

    window.shippingSelectVehicle = function(id, number) {
        document.getElementById('maintenance-vehicle-id').value = id;
        document.getElementById('selected-vehicle-number').innerText = number;
        document.getElementById('maintenance-section').style.display = 'block';
        shippingLoadMaintenance(id);
        document.querySelectorAll('.vehicle-card').forEach(c => c.style.borderColor = '#eee');
        event.currentTarget.style.borderColor = 'var(--shipping-primary-color)';
    };

    window.shippingLoadMaintenance = function(vehicleId) {
        fetch(ajaxurl + '?action=shipping_get_maintenance_logs&vehicle_id=' + vehicleId)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const body = document.getElementById('maintenance-list-body');
                body.innerHTML = res.data.map(m => `
                    <tr>
                        <td>${m.maintenance_type}</td>
                        <td>${m.maintenance_date}</td>
                        <td>${m.cost} SAR</td>
                        <td><span class="shipping-badge ${m.completed == 1 ? 'shipping-badge-high' : 'shipping-badge-medium'}">${m.completed == 1 ? 'مكتملة' : 'قيد الانتظار'}</span></td>
                        <td>
                            <button class="shipping-btn-outline" onclick='shippingOpenMaintenanceModal(${JSON.stringify(m).replace(/"/g, '&quot;')})' style="padding:4px 8px; font-size:10px;">تعديل</button>
                            <button class="shipping-btn" style="background:#e53e3e; padding:4px 8px; font-size:10px;" onclick="shippingDeleteMaintenance(${m.id})">حذف</button>
                        </td>
                    </tr>
                `).join('') || '<tr><td colspan="5" style="text-align:center;">لا توجد سجلات صيانة لهذه المركبة.</td></tr>';
            }
        });
    };

    window.shippingOpenMaintenanceModal = function(log = null) {
        const form = document.getElementById('form-maintenance');
        const modal = document.getElementById('modal-maintenance');
        form.reset();
        document.getElementById('maintenance-id').value = '';
        document.getElementById('maintenance-modal-title').innerText = log ? 'تعديل سجل صيانة' : 'إضافة سجل صيانة';
        const actionField = form.querySelector('input[name="action"]');
        actionField.value = log ? 'shipping_update_maintenance_log' : 'shipping_add_maintenance_log';

        if (log) {
            document.getElementById('maintenance-id').value = log.id;
            form.maintenance_type.value = log.maintenance_type;
            form.description.value = log.description;
            form.cost.value = log.cost;
            form.maintenance_date.value = log.maintenance_date;
            form.completed.checked = log.completed == 1;
        }
        modal.style.display = 'flex';
    };

    window.shippingDeleteMaintenance = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_maintenance_log');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_logistic_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) shippingLoadMaintenance(document.getElementById('maintenance-vehicle-id').value);
        });
    };

    document.getElementById('form-vehicle')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            if (res.success) { document.getElementById('modal-vehicle').style.display = 'none'; shippingLoadFleet(); }
            else alert(res.data);
        });
    });

    document.getElementById('form-maintenance')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            if (res.success) { document.getElementById('modal-maintenance').style.display = 'none'; shippingLoadMaintenance(document.getElementById('maintenance-vehicle-id').value); }
            else alert(res.data);
        });
    });

})(jQuery);
</script>
