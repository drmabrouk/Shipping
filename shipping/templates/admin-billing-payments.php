<?php if (!defined('ABSPATH')) exit;
global $wpdb;
$sub = $_GET['sub'] ?? 'invoice-gen';
?>
<div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
    <button class="shipping-tab-btn <?php echo $sub == 'invoice-gen' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-invoice', this)">إصدار فواتير</button>
    <button class="shipping-tab-btn <?php echo $sub == 'records' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-records', this)">سجلات الدفع</button>
    <button class="shipping-tab-btn <?php echo $sub == 'balances' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-balances', this)">الأرصدة</button>
    <button class="shipping-tab-btn <?php echo $sub == 'reports' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('billing-reports', this)">التقارير المالية</button>
</div>

<!-- 2. Payment Records -->
<div id="billing-records" class="shipping-internal-tab" style="display: <?php echo $sub == 'records' ? 'block' : 'none'; ?>;">
    <?php
    $payments = $wpdb->get_results("SELECT p.*, i.invoice_number, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM {$wpdb->prefix}shipping_payments p JOIN {$wpdb->prefix}shipping_invoices i ON p.invoice_id = i.id JOIN {$wpdb->prefix}shipping_customers c ON i.customer_id = c.id ORDER BY p.payment_date DESC");
    ?>
    <div class="shipping-card">
        <h4>سجل المدفوعات والتحويلات</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>المعرف</th><th>رقم الفاتورة</th><th>العميل</th><th>المبلغ</th><th>الوسيلة</th><th>التاريخ</th></tr></thead>
                <tbody>
                    <?php if(empty($payments)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">لا توجد سجلات دفع حالياً.</td></tr>
                    <?php else: foreach($payments as $p): ?>
                        <tr>
                            <td>#<?php echo $p->transaction_id; ?></td>
                            <td><strong><?php echo $p->invoice_number; ?></strong></td>
                            <td><?php echo esc_html($p->customer_name); ?></td>
                            <td style="color:#2f855a; font-weight:700;">+ <?php echo number_format($p->amount_paid, 2); ?> SAR</td>
                            <td><?php echo $p->payment_method; ?></td>
                            <td><?php echo $p->payment_date; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 1. Automated Invoice Generation -->
<div id="billing-invoice" class="shipping-internal-tab" style="display: <?php echo $sub == 'invoice-gen' ? 'block' : 'none'; ?>;">
    <div class="shipping-grid" style="grid-template-columns: 2fr 1fr;">
        <div class="shipping-card">
            <h4>إصدار فاتورة شحن</h4>
            <div style="background: #fdf2f2; padding: 15px; border-radius: 10px; border: 1px solid #fed7d7; margin-bottom: 20px; font-size: 13px;">
                <strong>نصيحة:</strong> يمكنك استيراد بيانات الشحنة لحساب التكلفة والبنود تلقائياً بناءً على قواعد التسعير المسجلة.
            </div>

            <div class="shipping-form-group" style="margin-bottom: 25px; display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label>استيراد بيانات من رقم شحنة:</label>
                    <input type="text" id="import-shipment-number" class="shipping-input" placeholder="SHP-XXXXXX">
                </div>
                <button type="button" class="shipping-btn" style="width: auto; height: 45px;" onclick="importShipmentToInvoice()">استيراد البيانات</button>
            </div>

            <form id="shipping-invoice-form">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div class="shipping-form-group">
                        <label>العميل:</label>
                        <select name="customer_id" id="invoice-customer-id" class="shipping-select" required>
                            <option value="">اختر العميل...</option>
                            <?php
                            $customers = $wpdb->get_results("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers");
                            foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->name)."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="shipping-form-group"><label>تاريخ الاستحقاق:</label><input type="date" name="due_date" class="shipping-input" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" required></div>
                </div>

                <div id="invoice-items-container">
                    <h5 style="margin-bottom:15px; display: flex; justify-content: space-between;">
                        بنود الفاتورة:
                        <span id="invoice-shipment-ref" style="font-weight: normal; color: #718096;"></span>
                    </h5>
                    <!-- Items injected here -->
                </div>
                <button type="button" class="shipping-btn shipping-btn-outline" onclick="addInvoiceRow()" style="width:auto; margin-bottom:20px;">+ إضافة بند يدوي</button>

                <div style="background:#f8fafc; padding:20px; border-radius:12px; margin-top:20px; border: 1px solid #e2e8f0;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><span>المجموع الفرعي:</span><strong id="invoice-subtotal">0.00</strong></div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><span>الضريبة (15%):</span><strong id="invoice-tax">0.00</strong></div>
                    <div style="display:flex; justify-content:space-between; border-top:2px solid #fff; padding-top:10px; margin-top: 10px; font-size:1.4em; color: var(--shipping-primary-color);"><span>الإجمالي النهائي:</span><strong id="invoice-total">0.00</strong></div>
                </div>

                <div style="margin-top:20px; display: flex; gap: 20px; align-items: center;">
                    <label><input type="checkbox" name="is_recurring" value="1"> فاتورة متكررة</label>
                    <select name="billing_interval" class="shipping-select" style="width:auto;">
                        <option value="monthly">شهرياً</option>
                        <option value="yearly">سنوياً</option>
                    </select>
                </div>

                <button type="submit" class="shipping-btn" style="margin-top:25px; height:55px; font-weight:800; font-size: 1.1em;">إصدار وحفظ الفاتورة</button>
            </form>
        </div>

        <div class="shipping-card" style="background: #f0f4f8;">
            <h4>معاينة سريعة</h4>
            <div id="invoice-preview-area" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); min-height: 400px; position: relative;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="margin: 0; color: #2d3748;">فاتورة ضريبية</h2>
                    <div style="font-size: 12px; color: #718096; margin-top: 5px;">INVOICE DRAFT</div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 40px; font-size: 13px;">
                    <div>
                        <strong>مُصدر الفاتورة:</strong><br>
                        <?php echo esc_html($shipping['shipping_name']); ?><br>
                        <?php echo esc_html($shipping['address']); ?>
                    </div>
                    <div style="text-align: left;">
                        <strong>التاريخ:</strong> <?php echo date('Y-m-d'); ?><br>
                        <strong>رقم المسودة:</strong> #TEMP-<?php echo time(); ?>
                    </div>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #edf2f7; text-align: right;">
                            <th style="padding: 10px;">الوصف</th>
                            <th style="padding: 10px; text-align: center;">الكمية</th>
                            <th style="padding: 10px; text-align: left;">السعر</th>
                        </tr>
                    </thead>
                    <tbody id="preview-items-body">
                        <tr><td colspan="3" style="text-align: center; padding: 40px; color: #a0aec0;">أضف بنوداً لعرض المعاينة</td></tr>
                    </tbody>
                </table>
                <div style="position: absolute; bottom: 30px; left: 30px; right: 30px; border-top: 2px solid #edf2f7; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 16px;">
                        <span>الإجمالي المستحق:</span>
                        <span id="preview-total">0.00 SAR</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Receivables Tracking -->
<div id="billing-balances" class="shipping-internal-tab" style="display: <?php echo $sub == 'balances' ? 'block' : 'none'; ?>;">
    <?php
    $receivables = Shipping_DB::get_receivables();
    ?>
    <div class="shipping-card">
        <h4>الأرصدة المستحقة (الحسابات المدينة)</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>المبلغ</th><th>تاريخ الاستحقاق</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                <tbody>
                    <?php if(empty($receivables)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px;">لا توجد مديونيات حالياً.</td></tr>
                    <?php else: foreach($receivables as $inv): ?>
                        <tr>
                            <td><strong><?php echo $inv->invoice_number; ?></strong></td>
                            <td><?php echo esc_html($inv->customer_name); ?></td>
                            <td><?php echo number_format($inv->total_amount, 2); ?></td>
                            <td style="color:<?php echo (strtotime($inv->due_date) < time()) ? '#e53e3e' : 'inherit'; ?>"><?php echo $inv->due_date; ?></td>
                            <td><span class="shipping-badge shipping-badge-low"><?php echo $inv->status; ?></span></td>
                            <td><button class="shipping-btn shipping-btn-outline" style="padding:5px 10px;" onclick="openPaymentModal(<?php echo htmlspecialchars(json_encode($inv)); ?>)">تسجيل دفع</button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 4. Financial Reporting -->
<div id="billing-reports" class="shipping-internal-tab" style="display: <?php echo $sub == 'reports' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>التقارير المالية وتحليل الإيرادات</h4>
        <div style="height:300px; margin-top:20px;">
            <canvas id="revenueChart"></canvas>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:30px;">
            <div style="background:#f0fff4; padding:20px; border-radius:12px; text-align:center; border:1px solid #c6f6d5;">
                <h5 style="margin-top:0;">إيرادات اليوم</h5>
                <div style="font-size:2em; font-weight:800; color:#2f855a;" id="today-revenue">0.00 SAR</div>
            </div>
            <div style="background:#ebf8ff; padding:20px; border-radius:12px; text-align:center; border:1px solid #bee3f8;">
                <h5 style="margin-top:0;">إيرادات الشهر الحالي</h5>
                <div style="font-size:2em; font-weight:800; color:#2b6cb0;" id="month-revenue">0.00 SAR</div>
            </div>
        </div>
    </div>
</div>

<div id="payment-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content">
        <div class="shipping-modal-header"><h3>تسجيل عملية دفع</h3><button class="shipping-modal-close" onclick="document.getElementById('payment-modal').style.display='none'">&times;</button></div>
        <form id="shipping-payment-form" style="padding:20px;">
            <input type="hidden" name="invoice_id" id="pay-inv-id">
            <div class="shipping-form-group"><label>المبلغ المدفوع:</label><input type="number" step="0.01" name="amount_paid" id="pay-amount" class="shipping-input" required></div>
            <div class="shipping-form-group">
                <label>وسيلة الدفع:</label>
                <select name="payment_method" class="shipping-select">
                    <option value="cash">نقدي</option>
                    <option value="bank">تحويل بنكي</option>
                    <option value="online">دفع إلكتروني (بوابة دفع)</option>
                </select>
            </div>
            <button type="submit" class="shipping-btn" style="width:100%;">تأكيد عملية الدفع</button>
        </form>
    </div>
</div>

<script>
function addInvoiceRow(desc = '', qty = 1, price = 0) {
    const container = document.getElementById('invoice-items-container');
    const div = document.createElement('div');
    div.className = 'invoice-item-row';
    div.style.cssText = 'display:grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px;';
    div.innerHTML = `
        <input type="text" placeholder="الوصف" class="shipping-input item-desc" value="${desc}">
        <input type="number" placeholder="الكمية" class="shipping-input item-qty" value="${qty}">
        <input type="number" placeholder="السعر" class="shipping-input item-price" value="${price}">
        <button type="button" class="shipping-btn" style="background:#e53e3e;" onclick="this.parentElement.remove(); calculateInvoice();">حذف</button>
    `;
    container.appendChild(div);
    attachInvoiceListeners();
    calculateInvoice();
}

function attachInvoiceListeners() {
    document.querySelectorAll('.item-qty, .item-price, .item-desc').forEach(input => {
        input.oninput = () => { calculateInvoice(); updatePreview(); };
    });
}

function calculateInvoice() {
    let subtotal = 0;
    document.querySelectorAll('.invoice-item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        subtotal += qty * price;
    });
    const tax = subtotal * 0.15; // 15% VAT
    const total = subtotal + tax;
    document.getElementById('invoice-subtotal').innerText = subtotal.toFixed(2);
    document.getElementById('invoice-tax').innerText = tax.toFixed(2);
    document.getElementById('invoice-total').innerText = total.toFixed(2);
    updatePreview();
}

function updatePreview() {
    const tbody = document.getElementById('preview-items-body');
    let html = '';
    let rowsFound = false;
    document.querySelectorAll('.invoice-item-row').forEach(row => {
        const desc = row.querySelector('.item-desc').value;
        const qty = row.querySelector('.item-qty').value;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        if (desc) {
            rowsFound = true;
            html += `<tr><td style="padding:10px; border-bottom:1px solid #f7fafc;">${desc}</td><td style="text-align:center;">${qty}</td><td style="text-align:left;">${(qty * price).toFixed(2)}</td></tr>`;
        }
    });
    tbody.innerHTML = rowsFound ? html : '<tr><td colspan="3" style="text-align: center; padding: 40px; color: #a0aec0;">أضف بنوداً لعرض المعاينة</td></tr>';
    document.getElementById('preview-total').innerText = document.getElementById('invoice-total').innerText + ' SAR';
}

function importShipmentToInvoice() {
    const num = document.getElementById('import-shipment-number').value;
    if(!num) return alert('يرجى إدخال رقم الشحنة');

    fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + num + '&nonce=<?php echo wp_create_nonce("shipping_shipment_action"); ?>')
    .then(r => r.json()).then(res => {
        if(res.success) {
            const s = res.data;
            document.getElementById('invoice-customer-id').value = s.customer_id;
            document.getElementById('invoice-items-container').innerHTML = '';
            document.getElementById('invoice-shipment-ref').innerText = '(شحنة: ' + s.shipment_number + ')';

            // Re-calculate cost to get breakdown
            const fd = new FormData();
            fd.append('action', 'shipping_estimate_cost');
            fd.append('customer_id', s.customer_id);
            fd.append('classification', s.classification);
            fd.append('weight', s.weight);
            fd.append('distance', 100); // Default if distance unknown
            fd.append('is_urgent', s.classification === 'express' ? 1 : 0);

            fetch(ajaxurl, { method:'POST', body: fd }).then(r=>r.json()).then(calcRes => {
                if(calcRes.success) {
                    const b = calcRes.data.breakdown;
                    addInvoiceRow('تكلفة الشحن الأساسية (' + s.shipment_number + ')', 1, b.base);
                    addInvoiceRow('تكلفة الوزن (' + s.weight + ' كجم)', 1, b.weight);
                    addInvoiceRow('تكلفة المسافة والوجهة', 1, b.distance);
                    if(b.fees > 0) addInvoiceRow('رسوم إضافية وخدمات خاصة', 1, b.fees);
                    if(b.discount > 0) addInvoiceRow('خصومات وعروض ترويجية', 1, -b.discount);
                }
            });
        } else alert('لم يتم العثور على الشحنة');
    });
}

document.getElementById('shipping-invoice-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const items = [];
    document.querySelectorAll('.invoice-item-row').forEach(row => {
        items.push({
            desc: row.querySelector('.item-desc').value,
            qty: row.querySelector('.item-qty').value,
            price: row.querySelector('.item-price').value
        });
    });

    const fd = new FormData(this);
    fd.append('action', 'shipping_save_invoice');
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_billing_action"); ?>');
    fd.append('subtotal', document.getElementById('invoice-subtotal').innerText);
    fd.append('total_amount', document.getElementById('invoice-total').innerText);
    fd.append('tax_amount', document.getElementById('invoice-tax').innerText);
    fd.append('items_json', JSON.stringify(items));

    fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res => {
        if(res.success) {
            shippingShowNotification('تم إصدار الفاتورة بنجاح');
            location.reload();
        } else alert(res.data);
    });
});

function openPaymentModal(inv) {
    document.getElementById('pay-inv-id').value = inv.id;
    document.getElementById('pay-amount').value = inv.total_amount;
    document.getElementById('payment-modal').style.display = 'flex';
}

document.getElementById('shipping-payment-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'shipping_process_payment');
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_billing_action"); ?>');
    fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
        if(res.success) {
            shippingShowNotification('تم تسجيل الدفع بنجاح');
            location.reload();
        } else alert(res.data);
    });
});

window.onload = function() {
    addInvoiceRow();
    const ctx = document.getElementById('revenueChart')?.getContext('2d');
    if(ctx) {
        fetch(ajaxurl + '?action=shipping_get_billing_report')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const stats = res.data;
                const labels = stats.monthly.map(s => s.month);
                const data = stats.monthly.map(s => s.total);

                document.getElementById('today-revenue').innerText = stats.summary.today.toFixed(2) + ' SAR';
                document.getElementById('month-revenue').innerText = stats.summary.month.toFixed(2) + ' SAR';

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels.length ? labels : ['No Data'],
                        datasets: [{
                            label: 'الإيرادات الشهرية',
                            data: data.length ? data : [0],
                            borderColor: '#F63049',
                            backgroundColor: 'rgba(246, 48, 73, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        });
    }
};
</script>
