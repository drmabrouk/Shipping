<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'new-orders';
?>
<div class="shipping-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 15px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px;">
        <button class="shipping-tab-btn <?php echo $sub == 'new-orders' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-new', this); loadOrders('new')">طلبات جديدة</button>
        <button class="shipping-tab-btn <?php echo $sub == 'in-progress' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-progress', this); loadOrders('in-progress')">قيد التنفيذ</button>
        <button class="shipping-tab-btn <?php echo $sub == 'completed' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-completed', this); loadOrders('completed')">مكتملة</button>
        <button class="shipping-tab-btn <?php echo $sub == 'cancelled' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('order-cancelled', this); loadOrders('cancelled')">ملغاة</button>
    </div>
    <div style="display: flex; gap: 10px;">
        <div class="shipping-search-box" style="position: relative;">
            <input type="text" id="order-search" class="shipping-input" placeholder="بحث برقم الطلب أو العميل..." oninput="debounceOrderSearch()" style="width: 250px; padding-right: 35px;">
            <span class="dashicons dashicons-search" style="position: absolute; right: 10px; top: 10px; color: #94a3b8;"></span>
        </div>
        <button class="shipping-btn" onclick="document.getElementById('modal-add-order').style.display='flex'">+ طلب جديد</button>
    </div>
</div>

<div class="shipping-bulk-actions" id="order-bulk-bar" style="display: none; background: #f8fafc; padding: 15px 20px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 20px; align-items: center; gap: 15px; animation: slideIn 0.3s ease;">
    <span style="font-weight: 700; color: #4a5568;">الإجراءات الجماعية (<span id="bulk-count">0</span>):</span>
    <select id="bulk-status" class="shipping-select" style="width: 180px;">
        <option value="">تغيير الحالة إلى...</option>
        <option value="new">جديد</option>
        <option value="in-progress">قيد التنفيذ</option>
        <option value="completed">مكتمل</option>
        <option value="cancelled">ملغى</option>
    </select>
    <button class="shipping-btn" onclick="applyBulkStatus()" style="width: auto;">تطبيق</button>
    <button class="shipping-btn shipping-btn-outline" onclick="clearBulkSelection()" style="width: auto;">إلغاء التحديد</button>
</div>

<!-- Tabs Content -->
<?php
$statuses = ['new' => 'order-new', 'in-progress' => 'order-progress', 'completed' => 'order-completed', 'cancelled' => 'order-cancelled'];
foreach($statuses as $status => $id): ?>
<div id="<?php echo $id; ?>" class="shipping-internal-tab" style="display: <?php echo $sub == $status ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" onclick="toggleAllOrders(this)"></th>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>المبلغ</th>
                        <th>المسار/العناوين</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="table-body-<?php echo $status; ?>">
                    <!-- Data loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modals -->
<div id="modal-add-order" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 650px;">
        <div class="shipping-modal-header">
            <h4>إنشاء طلب شحن جديد</h4>
            <button onclick="document.getElementById('modal-add-order').style.display='none'">&times;</button>
        </div>
        <form id="form-add-order">
            <input type="hidden" name="action" value="shipping_add_order">
            <?php wp_nonce_field('shipping_order_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="shipping-form-group">
                        <label>العميل</label>
                        <select name="customer_id" class="shipping-input" required>
                            <option value="">اختر العميل...</option>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers ORDER BY first_name ASC");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>المبلغ الإجمالي (SAR)</label>
                        <input type="number" step="0.01" name="total_amount" class="shipping-input" placeholder="0.00" required>
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان الاستلام</label>
                    <textarea name="pickup_address" class="shipping-textarea" rows="2" placeholder="أدخل تفاصيل موقع الاستلام..." required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان التسليم</label>
                    <textarea name="delivery_address" class="shipping-textarea" rows="2" placeholder="أدخل تفاصيل موقع التسليم..." required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>تفاصيل الشحنة / ملاحظات</label>
                    <textarea name="order_details" class="shipping-textarea" rows="3" placeholder="محتويات الشحنة، متطلبات خاصة..."></textarea>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">تأكيد وإنشاء الطلب</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="modal-edit-order" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 650px;">
        <div class="shipping-modal-header">
            <h4>تعديل طلب الشحن</h4>
            <button onclick="document.getElementById('modal-edit-order').style.display='none'">&times;</button>
        </div>
        <form id="form-edit-order">
            <input type="hidden" name="id">
            <div class="shipping-modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="shipping-form-group">
                        <label>العميل</label>
                        <select name="customer_id" class="shipping-input" required>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers ORDER BY first_name ASC");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label>المبلغ الإجمالي (SAR)</label>
                        <input type="number" step="0.01" name="total_amount" class="shipping-input" required>
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان الاستلام</label>
                    <textarea name="pickup_address" class="shipping-textarea" rows="2" required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان التسليم</label>
                    <textarea name="delivery_address" class="shipping-textarea" rows="2" required></textarea>
                </div>
                <div class="shipping-form-group">
                    <label>تفاصيل الشحنة / ملاحظات</label>
                    <textarea name="order_details" class="shipping-textarea" rows="3"></textarea>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>

<!-- Order Logs Modal -->
<div id="modal-order-logs" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 600px;">
        <div class="shipping-modal-header">
            <h4>سجل تتبع الطلب: <span id="log-order-num"></span></h4>
            <button onclick="document.getElementById('modal-order-logs').style.display='none'">&times;</button>
        </div>
        <div class="shipping-modal-body">
            <div id="order-logs-timeline" class="shipping-timeline">
                <!-- Logs loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
let currentStatus = '<?php echo $sub; ?>';
if (currentStatus === 'new-orders') currentStatus = 'new';

function loadOrders(status = currentStatus) {
    currentStatus = status;
    const search = document.getElementById('order-search').value;
    const tbody = document.getElementById('table-body-' + status);
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px;"><span class="dashicons dashicons-update spin"></span> جاري التحميل...</td></tr>';

    fetch(ajaxurl + `?action=shipping_get_orders&status=${status}&search=${encodeURIComponent(search)}`)
    .then(r => r.json()).then(res => {
        if (!res.data.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد طلبات متوفرة</td></tr>';
            return;
        }
        tbody.innerHTML = res.data.map(o => `
            <tr>
                <td><input type="checkbox" class="order-checkbox" value="${o.id}" onchange="updateBulkBar()"></td>
                <td><strong>${o.order_number}</strong></td>
                <td>
                    <div style="font-weight:700;">${o.customer_name}</div>
                    <div style="font-size:11px; color:#718096;">${o.customer_phone}</div>
                </td>
                <td>${parseFloat(o.total_amount).toFixed(2)} SAR</td>
                <td style="font-size:12px; max-width:200px;">
                    <div class="truncate" title="${o.pickup_address}">من: ${o.pickup_address}</div>
                    <div class="truncate" title="${o.delivery_address}">إلى: ${o.delivery_address}</div>
                </td>
                <td>${o.created_at.split(' ')[0]}</td>
                <td>
                    <div style="display:flex; gap:5px;">
                        <button class="shipping-btn" style="padding:4px 8px; font-size:11px;" onclick="viewOrderLogs(${o.id}, '${o.order_number}')">سجل</button>
                        <button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#4a5568;" onclick='openEditOrderModal(${JSON.stringify(o).replace(/"/g, '&quot;')})'>تعديل</button>
                        ${o.shipment_id ? `<button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#319795;" onclick="viewShipmentDossier(${o.shipment_id})">ملف</button>` : ''}
                        ${o.status === 'new' ? `<button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#3182ce;" onclick="prepareShipment(${o.id})">شحن</button>` : ''}
                        ${o.status !== 'completed' && o.status !== 'cancelled' ? `
                            <button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#38a169;" onclick="updateOrderStatus(${o.id}, '${getNextStatus(o.status)}')">تحديث</button>
                        ` : ''}
                        <button class="shipping-btn" style="padding:4px 8px; font-size:11px; background:#e53e3e;" onclick="deleteOrder(${o.id})">حذف</button>
                    </div>
                </td>
            </tr>
        `).join('');
    });
}

function getNextStatus(current) {
    if (current === 'new') return 'in-progress';
    if (current === 'in-progress') return 'completed';
    return current;
}

function updateOrderStatus(id, status) {
    if (!confirm(`هل أنت متأكد من تغيير حالة الطلب إلى ${status}؟`)) return;
    const fd = new FormData();
    fd.append('action', 'shipping_update_order');
    fd.append('id', id);
    fd.append('status', status);
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_order_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if (res.success) {
            shippingShowNotification('تم تحديث حالة الطلب');
            loadOrders(currentStatus);
        } else alert(res.data);
    });
}

function deleteOrder(id) {
    if (!confirm('هل أنت متأكد من حذف هذا الطلب نهائياً؟')) return;
    const fd = new FormData();
    fd.append('action', 'shipping_delete_order');
    fd.append('id', id);
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_order_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if (res.success) {
            shippingShowNotification('تم حذف الطلب');
            loadOrders(currentStatus);
        } else alert(res.data);
    });
}

function viewOrderLogs(id, num) {
    document.getElementById('log-order-num').innerText = num;
    const container = document.getElementById('order-logs-timeline');
    container.innerHTML = '<p style="text-align:center;">جاري تحميل السجل...</p>';
    document.getElementById('modal-order-logs').style.display = 'flex';

    fetch(ajaxurl + '?action=shipping_get_order_logs&id=' + id)
    .then(r => r.json()).then(res => {
        if (!res.data.length) { container.innerHTML = '<p>لا توجد سجلات لهذا الطلب</p>'; return; }
        container.innerHTML = res.data.map(l => `
            <div class="timeline-item" style="border-right: 2px solid #edf2f7; padding-right: 20px; position: relative; padding-bottom: 15px; margin-right: 10px;">
                <div style="position: absolute; right: -7px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--shipping-primary-color); border: 2px solid #fff;"></div>
                <div style="font-weight: 700; font-size: 13px;">${l.action}</div>
                <div style="font-size: 11px; color: #718096;">بواسطة: ${l.display_name} | ${l.created_at}</div>
                ${l.new_value ? `<div style="font-size: 12px; margin-top: 5px; background: #f8fafc; padding: 5px; border-radius: 5px;">${l.new_value}</div>` : ''}
            </div>
        `).join('');
    });
}

function prepareShipment(orderId) {
    // Logic to redirect to shipment creation with order data
    window.location.href = `<?php echo admin_url('admin.php?page=shipping-admin&shipping_tab=shipment-mgmt&sub=create-shipment&order_id='); ?>` + orderId;
}

function viewShipmentDossier(shipmentId) {
    window.location.href = `<?php echo admin_url('admin.php?page=shipping-admin&shipping_tab=shipment-mgmt&sub=monitoring&view_dossier='); ?>` + shipmentId;
}

// Bulk Actions Logic
function toggleAllOrders(master) {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = master.checked);
    updateBulkBar();
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const bar = document.getElementById('order-bulk-bar');
    if (checked.length > 0) {
        bar.style.display = 'flex';
        document.getElementById('bulk-count').innerText = checked.length;
    } else {
        bar.style.display = 'none';
    }
}

function clearBulkSelection() {
    document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
    updateBulkBar();
}

function applyBulkStatus() {
    const status = document.getElementById('bulk-status').value;
    if (!status) return alert('يرجى اختيار الحالة الجديدة');
    const ids = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);

    if (!confirm(`هل أنت متأكد من تغيير حالة ${ids.length} طلبات إلى ${status}؟`)) return;

    const fd = new FormData();
    fd.append('action', 'shipping_bulk_update_orders');
    fd.append('ids', ids.join(','));
    fd.append('status', status);
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_order_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if (res.success) {
            shippingShowNotification(`تم تحديث ${res.data} طلبات بنجاح`);
            clearBulkSelection();
            loadOrders(currentStatus);
        } else alert(res.data);
    });
}

// Search Debouncing
let searchTimeout;
function debounceOrderSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadOrders(currentStatus), 500);
}

function openEditOrderModal(o) {
    const f = document.getElementById('form-edit-order');
    f.id.value = o.id;
    f.customer_id.value = o.customer_id;
    f.total_amount.value = o.total_amount;
    f.pickup_address.value = o.pickup_address;
    f.delivery_address.value = o.delivery_address;
    f.order_details.value = o.order_details;
    document.getElementById('modal-edit-order').style.display = 'flex';
}

document.getElementById('form-edit-order')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الحفظ...';
    const fd = new FormData(this);
    fd.append('action', 'shipping_update_order');
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_order_action"); ?>');

    fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        btn.disabled = false; btn.innerText = 'حفظ التعديلات';
        if (res.success) {
            shippingShowNotification('تم تحديث الطلب بنجاح');
            document.getElementById('modal-edit-order').style.display = 'none';
            loadOrders(currentStatus);
        } else alert(res.data);
    });
});

// Form Submission
document.getElementById('form-add-order')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الحفظ...';

    fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
    .then(r => r.json()).then(res => {
        if (res.success) {
            shippingShowNotification('تم إنشاء الطلب بنجاح');
            document.getElementById('modal-add-order').style.display = 'none';
            this.reset();
            loadOrders('new');
        } else {
            alert(res.data);
            btn.disabled = false; btn.innerText = 'إنشاء الطلب';
        }
    });
});

window.addEventListener('DOMContentLoaded', () => loadOrders(currentStatus));
</script>

<style>
.spin { animation: spin 2s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
.truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.shipping-btn-icon {
    background: none; border: none; cursor: pointer; font-size: 16px; padding: 6px; border-radius: 6px; transition: 0.2s;
    background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center;
}
.shipping-btn-icon:hover { transform: translateY(-2px); filter: brightness(0.9); }
@keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
