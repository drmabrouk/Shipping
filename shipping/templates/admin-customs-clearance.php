<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'documentation';
?>
<div class="shipping-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 15px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px;">
        <button class="shipping-tab-btn <?php echo $sub == 'documentation' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-docs', this); loadCustomsDocs()">الوثائق والمستندات</button>
        <button class="shipping-tab-btn <?php echo $sub == 'invoices' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-invoices', this); loadCustomsInvoices()">الفواتير التجارية</button>
        <button class="shipping-tab-btn <?php echo $sub == 'duties-taxes' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-taxes', this)">الرسوم والضرائب</button>
        <button class="shipping-tab-btn <?php echo $sub == 'status' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('customs-status', this); loadCustomsStatus()">حالة التخليص</button>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="shipping-btn" onclick="document.getElementById('modal-add-customs').style.display='flex'">+ بيان جمركي</button>
        <button class="shipping-btn" style="background: #4a5568;" onclick="document.getElementById('modal-add-customs-doc').style.display='flex'">+ رفع مستند</button>
    </div>
</div>

<div id="customs-invoices" class="shipping-internal-tab" style="display: <?php echo $sub == 'invoices' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>الفواتير التجارية المصاحبة للشحنات</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>رقم الفاتورة</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody id="customs-invoices-table">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customs-docs" class="shipping-internal-tab" style="display: <?php echo $sub == 'documentation' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>وثائق التخليص الجمركي للشحنات</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>نوع المستند</th>
                        <th>الحالة</th>
                        <th>تاريخ الرفع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="customs-docs-table">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customs-taxes" class="shipping-internal-tab" style="display: <?php echo $sub == 'duties-taxes' ? 'block' : 'none'; ?>;">
    <div class="shipping-grid" style="grid-template-columns: 2fr 1fr;">
        <div class="shipping-card">
            <h4>تقدير الرسوم الجمركية والضرائب</h4>
            <form id="form-tax-calculator" style="margin-top: 20px;">
                <div class="shipping-form-group">
                    <label>القيمة المصرح بها للبضاعة (<?php echo esc_html($currency); ?>)</label>
                    <input type="number" id="goods-value" class="shipping-input" placeholder="0.00">
                </div>
                <div class="shipping-form-group">
                    <label>فئة البضاعة / رمز HS</label>
                    <select id="hs-category" class="shipping-select">
                        <option value="0.05">إلكترونيات (5%)</option>
                        <option value="0.10">ملابس ومنسوجات (10%)</option>
                        <option value="0.15">قطع غيار (15%)</option>
                        <option value="0">أدوية ومواد طبية (0%)</option>
                        <option value="0.05">أخرى (5%)</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>بلد المنشأ</label>
                    <input type="text" class="shipping-input" placeholder="مثال: الصين">
                </div>
                <button type="button" class="shipping-btn" onclick="calculateCustomsTax()">احسب التقدير</button>
            </form>
        </div>
        <div class="shipping-card" id="tax-result-card" style="display: none; background: #fffaf0; border: 1px solid #feebc8;">
            <h4>ملخص التقدير</h4>
            <div style="display: grid; gap: 15px; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between;"><span>الرسوم الجمركية:</span><strong id="res-duties">0.00 <?php echo esc_html($currency); ?></strong></div>
                <div style="display: flex; justify-content: space-between;"><span>ضريبة القيمة المضافة (15%):</span><strong id="res-vat">0.00 <?php echo esc_html($currency); ?></strong></div>
                <div style="display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 10px; font-size: 1.2em; color: #c05621;">
                    <span>إجمالي التقدير:</span><strong id="res-total-tax">0.00 <?php echo esc_html($currency); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="customs-status" class="shipping-internal-tab" style="display: <?php echo $sub == 'status' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>متابعة طلبات التخليص الجاري</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>رقم الشحنة</th>
                        <th>الحالة الورقية</th>
                        <th>الرسوم المقدرة</th>
                        <th>حالة التخليص</th>
                    </tr>
                </thead>
                <tbody id="customs-status-table">
                    <!-- Loaded via AJAX in loadCustomsStatus() -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Customs Record Modal -->
<div id="modal-add-customs" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h4>إضافة بيان جمركي جديد</h4>
            <button onclick="document.getElementById('modal-add-customs').style.display='none'">&times;</button>
        </div>
        <form id="form-add-customs-full">
            <input type="hidden" name="action" value="shipping_add_customs">
            <?php wp_nonce_field('shipping_customs_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>رقم الشحنة</label>
                    <select name="shipment_id" id="select-customs-shipment" class="shipping-input" required>
                        <option value="">اختر الشحنة...</option>
                        <!-- Loaded via AJAX -->
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>حالة التوثيق</label>
                    <select name="documentation_status" class="shipping-select">
                        <option value="complete">مكتملة</option>
                        <option value="pending">قيد المراجعة</option>
                        <option value="missing-info">نقص بيانات</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>الرسوم الجمركية المقدرة (<?php echo esc_html($currency); ?>)</label>
                    <input type="number" step="0.01" name="duties_amount" class="shipping-input" required>
                </div>
                <div class="shipping-form-group">
                    <label>حالة التخليص الميداني</label>
                    <select name="clearance_status" class="shipping-select">
                        <option value="waiting">في الانتظار</option>
                        <option value="under-inspection">تحت التفتيش</option>
                        <option value="released">تم الفسح</option>
                        <option value="held">محجوزة</option>
                    </select>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">حفظ البيان الجمركي</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Doc Modal -->
<div id="modal-add-customs-doc" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 500px;">
        <div class="shipping-modal-header">
            <h4>رفع مستند جمركي</h4>
            <button onclick="document.getElementById('modal-add-customs-doc').style.display='none'">&times;</button>
        </div>
        <form id="form-add-customs-doc">
            <input type="hidden" name="action" value="shipping_add_customs_doc">
            <?php wp_nonce_field('shipping_customs_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>رقم الشحنة</label>
                    <select name="shipment_id" id="select-doc-shipment" class="shipping-input" required>
                        <option value="">اختر الشحنة...</option>
                        <!-- Loaded via AJAX -->
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>نوع المستند</label>
                    <select name="doc_type" class="shipping-select">
                        <option value="Bill of Lading">بوليصة الشحن (BOL)</option>
                        <option value="Commercial Invoice">فاتورة تجارية</option>
                        <option value="Packing List">قائمة التعبئة</option>
                        <option value="Certificate of Origin">شهادة المنشأ</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>رابط المستند / الملف</label>
                    <input type="text" name="file_url" class="shipping-input" placeholder="https://..." required>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">رفع المستند</button>
            </div>
        </form>
    </div>
</div>

<script>
function calculateCustomsTax() {
    const val = parseFloat(document.getElementById('goods-value').value) || 0;
    const rate = parseFloat(document.getElementById('hs-category').value);

    const duties = val * rate;
    const vat = (val + duties) * 0.15;
    const total = duties + vat;

    document.getElementById('res-duties').innerText = duties.toFixed(2) + ' ' + '<?php echo esc_js($currency); ?>';
    document.getElementById('res-vat').innerText = vat.toFixed(2) + ' ' + '<?php echo esc_js($currency); ?>';
    document.getElementById('res-total-tax').innerText = total.toFixed(2) + ' ' + '<?php echo esc_js($currency); ?>';
    document.getElementById('tax-result-card').style.display = 'block';
}

function loadCustomsDocs() {
    fetch(ajaxurl + '?action=shipping_get_customs_docs')
    .then(r => r.json()).then(res => {
        const tbody = document.getElementById('customs-docs-table');
        if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد مستندات مرفوعة</td></tr>'; return; }
        tbody.innerHTML = res.data.filter(d => d.doc_type !== 'Commercial Invoice').map(d => `
            <tr>
                <td><strong>#${d.shipment_id}</strong></td>
                <td>${d.doc_type}</td>
                <td><span class="shipping-badge">${d.status}</span></td>
                <td>${d.uploaded_at}</td>
                <td><a href="${d.file_url}" target="_blank" class="shipping-btn-outline" style="padding:4px 8px; font-size:11px;">معاينة</a></td>
            </tr>
        `).join('');
    });
}

function loadCustomsInvoices() {
    fetch(ajaxurl + '?action=shipping_get_customs_docs')
    .then(r => r.json()).then(res => {
        const tbody = document.getElementById('customs-invoices-table');
        if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد فواتير تجارية</td></tr>'; return; }
        tbody.innerHTML = res.data.filter(d => d.doc_type === 'Commercial Invoice').map(d => `
            <tr>
                <td><strong>#${d.shipment_id}</strong></td>
                <td>CIN-${d.id}</td>
                <td>---</td>
                <td><span class="shipping-badge">${d.status}</span></td>
                <td><a href="${d.file_url}" target="_blank" class="shipping-btn-outline" style="padding:4px 8px; font-size:11px;">عرض الفاتورة</a></td>
            </tr>
        `).join('');
    });
}

function loadShipmentsForSelect() {
    fetch(ajaxurl + '?action=shipping_get_all_shipments')
    .then(r => r.json()).then(res => {
        if (res.success) {
            const options = res.data.map(s => `<option value="${s.id}">${s.shipment_number}</option>`).join('');
            document.getElementById('select-customs-shipment').innerHTML = '<option value="">اختر الشحنة...</option>' + options;
            document.getElementById('select-doc-shipment').innerHTML = '<option value="">اختر الشحنة...</option>' + options;
        }
    });
}

function loadCustomsStatus() {
    fetch(ajaxurl + '?action=shipping_get_customs_status')
    .then(r => r.json()).then(res => {
        const tbody = document.getElementById('customs-status-table');
        if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">لا توجد بيانات تخليص</td></tr>'; return; }
        tbody.innerHTML = res.data.map(c => `
            <tr>
                <td><strong>${c.shipment_number}</strong></td>
                <td>${c.documentation_status}</td>
                <td>${parseFloat(c.duties_amount).toFixed(2)} <?php echo esc_js($currency); ?></td>
                <td><span class="shipping-badge">${c.clearance_status}</span></td>
            </tr>
        `).join('');
    });
}

document.getElementById('form-add-customs-full')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
    .then(r => r.json()).then(res => {
        if (res.success) {
            shippingShowNotification('تم حفظ البيانات الجمركية');
            location.reload();
        } else alert(res.data);
    });
});

document.getElementById('form-add-customs-doc')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
    .then(r => r.json()).then(res => {
        if (res.success) {
            shippingShowNotification('تم رفع المستند بنجاح');
            document.getElementById('modal-add-customs-doc').style.display = 'none';
            loadCustomsDocs();
        } else alert(res.data);
    });
});

window.addEventListener('DOMContentLoaded', () => {
    loadShipmentsForSelect();
    if ("<?php echo $sub; ?>" === 'documentation') loadCustomsDocs();
    if ("<?php echo $sub; ?>" === 'status') loadCustomsStatus();
});
</script>
