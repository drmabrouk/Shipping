<?php if (!defined('ABSPATH')) exit;
$sub = $_GET['sub'] ?? 'calculator';
?>
<div class="shipping-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <div class="shipping-tabs-wrapper" style="display: flex; gap: 15px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px;">
        <button class="shipping-tab-btn <?php echo $sub == 'calculator' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-calc', this)">🧮 حاسبة الشحن</button>
        <button class="shipping-tab-btn <?php echo $sub == 'transport-costs' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-transport', this)">📋 قواعد التسعير</button>
        <button class="shipping-tab-btn <?php echo $sub == 'extra-charges' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-extra', this)">➕ رسوم إضافية</button>
        <button class="shipping-tab-btn <?php echo $sub == 'special-offers' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('pricing-offers', this)">🎁 العروض الخاصة</button>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="shipping-btn" onclick="openPricingModal('rule')">+ قاعدة جديدة</button>
        <button class="shipping-btn" style="background: #38a169;" onclick="openPricingModal('fee')">+ رسم إضافي</button>
        <button class="shipping-btn" style="background: #805ad5;" onclick="openPricingModal('offer')">+ عرض خاص</button>
    </div>
</div>

<!-- 1. Advanced Shipping Calculator -->
<div id="pricing-calc" class="shipping-internal-tab" style="display: <?php echo $sub == 'calculator' ? 'block' : 'none'; ?>;">
    <div class="shipping-grid" style="grid-template-columns: 1fr 1fr;">
        <div class="shipping-card">
            <h4 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">🧮 حاسبة التكلفة التقديرية</h4>
            <form id="shipping-calculator-form">
                <div class="shipping-form-group">
                    <label>الوزن (كجم)</label>
                    <input type="number" step="0.1" name="weight" class="shipping-input" placeholder="0.0" required>
                </div>
                <div class="shipping-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                    <div class="shipping-form-group">
                        <label>الطول (سم)</label>
                        <input type="number" name="length" class="shipping-input" placeholder="0">
                    </div>
                    <div class="shipping-form-group">
                        <label>العرض (سم)</label>
                        <input type="number" name="width" class="shipping-input" placeholder="0">
                    </div>
                    <div class="shipping-form-group">
                        <label>الارتفاع (سم)</label>
                        <input type="number" name="height" class="shipping-input" placeholder="0">
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>المسافة (كم)</label>
                    <input type="number" name="distance" class="shipping-input" placeholder="0" required>
                </div>
                <div class="shipping-form-group">
                    <label>خيار السرعة</label>
                    <select name="is_urgent" class="shipping-input">
                        <option value="0">شحن عادي</option>
                        <option value="1">شحن مستعجل (+)</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>تأمين الشحنة</label>
                    <select name="is_insured" class="shipping-input">
                        <option value="0">بدون تأمين</option>
                        <option value="1">إضافة تأمين (+)</option>
                    </select>
                </div>
                <button type="submit" class="shipping-btn" style="width: 100%; height: 50px; font-size: 1.1em;">حساب التكلفة التقديرية</button>
            </form>
        </div>

        <div id="calculator-results" class="shipping-card" style="display: none; background: #f0fdf4; border: 2px solid #bbf7d0;">
            <h4 style="margin-top:0; color: #166534;">📊 تحليل التكلفة المتوقعة</h4>
            <div id="cost-breakdown" style="margin-bottom: 20px;">
                <!-- Results injected here -->
            </div>
            <div style="text-align: center; padding: 20px; background: #fff; border-radius: 10px; border: 1px dashed #38a169;">
                <span style="font-size: 0.9em; color: #666; display: block; margin-bottom: 5px;">إجمالي التكلفة التقديرية</span>
                <span id="estimated-total" style="font-size: 2.5em; font-weight: 900; color: #2f855a;">0.00</span>
                <span style="font-size: 1.1em; font-weight: 700; color: #2f855a; margin-right: 5px;">SAR</span>
            </div>
        </div>
    </div>
</div>

<!-- 2. Pricing Rules -->
<div id="pricing-transport" class="shipping-internal-tab" style="display: <?php echo $sub == 'transport-costs' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>📋 قواعد تسعير النقل والشحن</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>اسم الخدمة</th>
                        <th>السعر الأساسي</th>
                        <th>سعر الكجم</th>
                        <th>سعر الكم</th>
                        <th>الحد الأدنى</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="pricing-rules-table">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 3. Additional Fees -->
<div id="pricing-extra" class="shipping-internal-tab" style="display: <?php echo $sub == 'extra-charges' ? 'block' : 'none'; ?>;">
    <div class="shipping-card">
        <h4>➕ الرسوم والخدمات الإضافية</h4>
        <div class="shipping-table-container">
            <table class="shipping-table">
                <thead>
                    <tr>
                        <th>اسم الرسم</th>
                        <th>القيمة</th>
                        <th>النوع</th>
                        <th>التطبيق التلقائي</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="additional-fees-table">
                    <!-- Data via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 4. Special Offers -->
<div id="pricing-offers" class="shipping-internal-tab" style="display: <?php echo $sub == 'special-offers' ? 'block' : 'none'; ?>;">
    <div class="shipping-grid" style="grid-template-columns: 2fr 1fr; gap: 20px;">
        <div class="shipping-card">
            <h4>🎁 العروض الخاصة والحملات الترويجية</h4>
            <div class="shipping-table-container">
                <table class="shipping-table">
                    <thead>
                        <tr>
                            <th>العرض / الكوبون</th>
                            <th>الخصم</th>
                            <th>النوع</th>
                            <th>تاريخ الانتهاء</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="special-offers-table">
                        <!-- Data via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="shipping-card" style="background: #fdf2f2; border: 1px solid #fed7d7;">
            <h4 style="margin-top:0; color: #c53030;">📈 إحصائيات التكاليف والأرباح</h4>
            <div style="height: 250px; margin-bottom: 20px;">
                <canvas id="costProfitChart"></canvas>
            </div>
            <div style="display: grid; gap: 10px;">
                <div style="display: flex; justify-content: space-between; padding: 10px; background: #fff; border-radius: 8px;">
                    <span>إجمالي الخصومات الممنوحة:</span>
                    <strong id="total-discounts-val" style="color: #c53030;">0.00 SAR</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 10px; background: #fff; border-radius: 8px;">
                    <span>صافي الربح التقديري:</span>
                    <strong id="net-profit-val" style="color: #2f855a;">0.00 SAR</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for CRUD -->
<!-- Rule Modal -->
<div id="modal-pricing-rule" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 550px;">
        <div class="shipping-modal-header">
            <h4>إضافة/تعديل قاعدة تسعير</h4>
            <button onclick="closePricingModal('rule')">&times;</button>
        </div>
        <form id="form-pricing-rule">
            <input type="hidden" name="action" value="shipping_add_pricing">
            <?php wp_nonce_field('shipping_pricing_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>اسم قاعدة التسعير (مثال: شحن سريع - المنطقة الوسطى)</label>
                    <input type="text" name="name" class="shipping-input" required>
                </div>
                <div class="shipping-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="shipping-form-group">
                        <label>التكلفة الأساسية (SAR)</label>
                        <input type="number" step="0.01" name="base_cost" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label>تكلفة الكيلو جرام (SAR)</label>
                        <input type="number" step="0.01" name="cost_per_kg" class="shipping-input" required>
                    </div>
                </div>
                <div class="shipping-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="shipping-form-group">
                        <label>تكلفة الكيلومتر (SAR)</label>
                        <input type="number" step="0.01" name="cost_per_km" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label>الحد الأدنى للتكلفة (SAR)</label>
                        <input type="number" step="0.01" name="min_cost" class="shipping-input" value="0">
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>معدل الحجم (الوزن الحجمي - اختياريا)</label>
                    <input type="number" step="0.1" name="volumetric_factor" class="shipping-input" placeholder="مثال: 5000">
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">حفظ القاعدة</button>
            </div>
        </form>
    </div>
</div>

<!-- Fee Modal -->
<div id="modal-pricing-fee" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 450px;">
        <div class="shipping-modal-header">
            <h4>إضافة رسم إضافي</h4>
            <button onclick="closePricingModal('fee')">&times;</button>
        </div>
        <form id="form-pricing-fee">
            <input type="hidden" name="action" value="shipping_add_additional_fee">
            <?php wp_nonce_field('shipping_pricing_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>اسم الرسم (مثال: رسوم الوقود، تغليف خاص)</label>
                    <input type="text" name="fee_name" class="shipping-input" required>
                </div>
                <div class="shipping-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="shipping-form-group">
                        <label>قيمة الرسم</label>
                        <input type="number" step="0.01" name="fee_value" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label>النوع</label>
                        <select name="fee_type" class="shipping-input">
                            <option value="fixed">مبلغ ثابت</option>
                            <option value="percentage">نسبة مئوية %</option>
                        </select>
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label><input type="checkbox" name="is_automatic" value="1"> تطبيق تلقائي على كافة الشحنات</label>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">حفظ الرسم</button>
            </div>
        </form>
    </div>
</div>

<!-- Offer Modal -->
<div id="modal-pricing-offer" class="shipping-modal">
    <div class="shipping-modal-content" style="max-width: 450px;">
        <div class="shipping-modal-header">
            <h4>إضافة عرض خاص / كوبون</h4>
            <button onclick="closePricingModal('offer')">&times;</button>
        </div>
        <form id="form-pricing-offer">
            <input type="hidden" name="action" value="shipping_add_special_offer">
            <?php wp_nonce_field('shipping_pricing_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>كود العرض أو الاسم</label>
                    <input type="text" name="offer_code" class="shipping-input" required>
                </div>
                <div class="shipping-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="shipping-form-group">
                        <label>قيمة الخصم</label>
                        <input type="number" step="0.01" name="discount_value" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label>نوع الخصم</label>
                        <select name="discount_type" class="shipping-input">
                            <option value="percentage">نسبة مئوية %</option>
                            <option value="fixed">خصم ثابت</option>
                        </select>
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>تاريخ الانتهاء</label>
                    <input type="date" name="expiry_date" class="shipping-input">
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">تنشيط العرض</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPricingModal(type) {
    document.getElementById('modal-pricing-' + type).style.display = 'flex';
}
function closePricingModal(type) {
    document.getElementById('modal-pricing-' + type).style.display = 'none';
}

function loadPricingData() {
    // Load Rules
    fetch(ajaxurl + '?action=shipping_get_pricing_rules')
    .then(r => r.json()).then(res => {
        const tbody = document.getElementById('pricing-rules-table');
        if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">لا توجد قواعد مسجلة</td></tr>'; return; }
        tbody.innerHTML = res.data.map(r => `
            <tr>
                <td><strong>${r.name}</strong></td>
                <td>${parseFloat(r.base_cost).toFixed(2)} SAR</td>
                <td>${parseFloat(r.cost_per_kg).toFixed(2)} / كجم</td>
                <td>${parseFloat(r.cost_per_km).toFixed(2)} / كم</td>
                <td>${parseFloat(r.min_cost).toFixed(2)} SAR</td>
                <td>
                    <button class="shipping-btn-icon" onclick="deletePricingItem('rule', ${r.id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    });

    // Load Fees
    fetch(ajaxurl + '?action=shipping_get_additional_fees')
    .then(r => r.json()).then(res => {
        const tbody = document.getElementById('additional-fees-table');
        if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">لا توجد رسوم مسجلة</td></tr>'; return; }
        tbody.innerHTML = res.data.map(f => `
            <tr>
                <td>${f.fee_name}</td>
                <td>${parseFloat(f.fee_value).toFixed(2)}${f.fee_type === 'percentage' ? '%' : ' SAR'}</td>
                <td>${f.fee_type === 'percentage' ? 'نسبة' : 'مبلغ ثابت'}</td>
                <td>${f.is_automatic == 1 ? '<span class="status-badge status-active">تلقائي</span>' : '<span class="status-badge status-inactive">يدوي</span>'}</td>
                <td>
                    <button class="shipping-btn-icon" onclick="deletePricingItem('fee', ${f.id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    });

    // Load Offers
    fetch(ajaxurl + '?action=shipping_get_special_offers')
    .then(r => r.json()).then(res => {
        const tbody = document.getElementById('special-offers-table');
        if (!res.data.length) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">لا توجد عروض منشورة</td></tr>'; return; }
        tbody.innerHTML = res.data.map(o => `
            <tr>
                <td><code style="background:#edf2f7; padding:4px 8px; border-radius:4px;">${o.offer_code}</code></td>
                <td>${parseFloat(o.discount_value).toFixed(2)}${o.discount_type === 'percentage' ? '%' : ' SAR'}</td>
                <td>${o.discount_type === 'percentage' ? 'نسبة' : 'ثابت'}</td>
                <td>${o.expiry_date || 'غير محدد'}</td>
                <td>${new Date(o.expiry_date) < new Date() ? '<span class="status-badge status-inactive">منتهي</span>' : '<span class="status-badge status-active">نشط</span>'}</td>
                <td>
                    <button class="shipping-btn-icon" onclick="deletePricingItem('offer', ${o.id})">🗑️</button>
                </td>
            </tr>
        `).join('');
    });
}

function deletePricingItem(type, id) {
    if (!confirm('هل أنت متأكد من حذف هذا البند؟')) return;
    const fd = new FormData();
    let action = 'shipping_delete_pricing_rule';
    if (type === 'fee') action = 'shipping_delete_additional_fee';
    if (type === 'offer') action = 'shipping_delete_special_offer';

    fd.append('action', action);
    fd.append('id', id);
    fd.append('nonce', '<?php echo wp_create_nonce("shipping_pricing_action"); ?>');
    fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if (res.success) loadPricingData(); else alert(res.data);
    });
}

// Calculator Logic
document.getElementById('shipping-calculator-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'shipping_estimate_cost');

    const btn = this.querySelector('button');
    btn.disabled = true; btn.innerText = 'جاري الحساب...';

    fetch(ajaxurl, { method: 'POST', body: fd })
    .then(r => r.json()).then(res => {
        btn.disabled = false; btn.innerText = 'حساب التكلفة التقديرية';
        if (res.success) {
            const data = res.data;
            document.getElementById('calculator-results').style.display = 'block';
            document.getElementById('estimated-total').innerText = data.total_cost.toFixed(2);

            let breakdownHtml = '<ul style="list-style:none; padding:0; margin:0;">';
            breakdownHtml += `<li style="display:flex; justify-content:space-between; margin-bottom:8px;"><span>التكلفة الأساسية:</span> <strong>${data.breakdown.base.toFixed(2)} SAR</strong></li>`;
            breakdownHtml += `<li style="display:flex; justify-content:space-between; margin-bottom:8px;"><span>تكلفة الوزن:</span> <strong>${data.breakdown.weight.toFixed(2)} SAR</strong></li>`;
            breakdownHtml += `<li style="display:flex; justify-content:space-between; margin-bottom:8px;"><span>تكلفة المسافة:</span> <strong>${data.breakdown.distance.toFixed(2)} SAR</strong></li>`;

            if (data.breakdown.fees > 0) {
                breakdownHtml += `<li style="display:flex; justify-content:space-between; margin-bottom:8px; color:#c53030;"><span>الرسوم الإضافية:</span> <strong>+ ${data.breakdown.fees.toFixed(2)} SAR</strong></li>`;
            }
            if (data.breakdown.discount > 0) {
                breakdownHtml += `<li style="display:flex; justify-content:space-between; margin-bottom:8px; color:#2f855a;"><span>الخصومات والعروض:</span> <strong>- ${data.breakdown.discount.toFixed(2)} SAR</strong></li>`;
            }
            breakdownHtml += '</ul>';
            document.getElementById('cost-breakdown').innerHTML = breakdownHtml;
        } else {
            alert(res.data);
        }
    });
});

// Generic Form Handlers
['rule', 'fee', 'offer'].forEach(type => {
    document.getElementById('form-pricing-' + type)?.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        btn.disabled = true;
        fetch(ajaxurl, { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(res => {
            btn.disabled = false;
            if (res.success) {
                closePricingModal(type);
                this.reset();
                loadPricingData();
            } else {
                alert(res.data);
            }
        });
    });
});

function initPricingCharts() {
    const ctx = document.getElementById('costProfitChart')?.getContext('2d');
    if (!ctx) return;

    fetch(ajaxurl + '?action=shipping_get_billing_report')
    .then(r => r.json()).then(res => {
        if (res.success) {
            const stats = res.data.summary;
            document.getElementById('total-discounts-val').innerText = stats.total_discounts.toFixed(2) + ' SAR';
            document.getElementById('net-profit-val').innerText = (stats.total_revenue - stats.total_discounts).toFixed(2) + ' SAR';

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['صافي الربح', 'خصومات ممنوحة'],
                    datasets: [{
                        data: [stats.total_revenue - stats.total_discounts, stats.total_discounts],
                        backgroundColor: ['#2f855a', '#e53e3e'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
    });
}

window.addEventListener('DOMContentLoaded', () => {
    loadPricingData();
    initPricingCharts();
});
</script>

<style>
.status-badge {
    padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.status-active { background: #c6f6d5; color: #22543d; }
.status-inactive { background: #fed7d7; color: #822727; }
.shipping-btn-icon {
    background: none; border: none; cursor: pointer; font-size: 16px; padding: 5px; transition: 0.2s;
}
.shipping-btn-icon:hover { transform: scale(1.2); }
</style>
