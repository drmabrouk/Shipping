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

<!-- 1. Automated Invoice Generation -->
<div id="billing-invoice" class="shipping-internal-tab" style="display: <?php echo $sub == 'invoice-gen' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>إنشاء فاتورة جديدة</h4>
        <form id="shipping-invoice-form" style="margin-top:20px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
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
                <div class="shipping-form-group"><label>تاريخ الاستحقاق:</label><input type="date" name="due_date" class="shipping-input" required></div>
            </div>

            <div id="invoice-items-container">
                <h5 style="margin-bottom:10px;">بنود الفاتورة:</h5>
                <div class="invoice-item-row" style="display:grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px;">
                    <input type="text" placeholder="الوصف" class="shipping-input item-desc">
                    <input type="number" placeholder="الكمية" class="shipping-input item-qty" value="1">
                    <input type="number" placeholder="السعر" class="shipping-input item-price">
                    <button type="button" class="shipping-btn" style="background:#e53e3e;" onclick="this.parentElement.remove()">حذف</button>
                </div>
            </div>
            <button type="button" class="shipping-btn shipping-btn-outline" onclick="addInvoiceRow()" style="width:auto; margin-bottom:20px;">+ إضافة بند آخر</button>

            <div style="background:#f8fafc; padding:20px; border-radius:12px; margin-top:20px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><span>المجموع الفرعي:</span><strong id="invoice-subtotal">0.00</strong></div>
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><span>الضريبة (14%):</span><strong id="invoice-tax">0.00</strong></div>
                <div style="display:flex; justify-content:space-between; border-top:1px solid #eee; padding-top:10px; font-size:1.2em;"><span>الإجمالي النهائي:</span><strong id="invoice-total">0.00</strong></div>
            </div>

            <div style="margin-top:20px;">
                <label><input type="checkbox" name="is_recurring" value="1"> فاتورة متكررة (اشتراك)</label>
                <select name="billing_interval" class="shipping-select" style="width:auto; margin-right:10px;">
                    <option value="monthly">شهرياً</option>
                    <option value="yearly">سنوياً</option>
                </select>
            </div>

            <button type="submit" class="shipping-btn" style="margin-top:20px; height:50px; font-weight:800;">إصدار الفاتورة وحفظها</button>
        </form>
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
                <div style="font-size:2em; font-weight:800; color:#2f855a;">0.00 EGP</div>
            </div>
            <div style="background:#ebf8ff; padding:20px; border-radius:12px; text-align:center; border:1px solid #bee3f8;">
                <h5 style="margin-top:0;">إيرادات الشهر الحالي</h5>
                <div style="font-size:2em; font-weight:800; color:#2b6cb0;">0.00 EGP</div>
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
function addInvoiceRow() {
    const container = document.getElementById('invoice-items-container');
    const div = document.createElement('div');
    div.className = 'invoice-item-row';
    div.style.cssText = 'display:grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px;';
    div.innerHTML = `
        <input type="text" placeholder="الوصف" class="shipping-input item-desc">
        <input type="number" placeholder="الكمية" class="shipping-input item-qty" value="1">
        <input type="number" placeholder="السعر" class="shipping-input item-price">
        <button type="button" class="shipping-btn" style="background:#e53e3e;" onclick="this.parentElement.remove()">حذف</button>
    `;
    container.appendChild(div);
    attachInvoiceListeners();
}

function attachInvoiceListeners() {
    document.querySelectorAll('.item-qty, .item-price').forEach(input => {
        input.oninput = calculateInvoice;
    });
}

function calculateInvoice() {
    let subtotal = 0;
    document.querySelectorAll('.invoice-item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        subtotal += qty * price;
    });
    const tax = subtotal * 0.14;
    const total = subtotal + tax;
    document.getElementById('invoice-subtotal').innerText = subtotal.toFixed(2);
    document.getElementById('invoice-tax').innerText = tax.toFixed(2);
    document.getElementById('invoice-total').innerText = total.toFixed(2);
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

    fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
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
    attachInvoiceListeners();
    const ctx = document.getElementById('revenueChart')?.getContext('2d');
    if(ctx) {
        fetch(ajaxurl + '?action=shipping_get_billing_report')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const stats = res.data;
                const labels = stats.monthly.map(s => s.month);
                const data = stats.monthly.map(s => s.total);

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
