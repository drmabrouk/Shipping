/**
 * Shipments Management Controller
 */

window.ShipmentsController = {
    arabCities: {
        "Saudi Arabia": ["الرياض", "جدة", "الدمام", "مكة المكرمة", "المدينة المنورة", "الخبر"],
        "UAE": ["دبي", "أبو ظبي", "الشارقة", "عجمان", "العين"],
        "Egypt": ["القاهرة", "الإسكندرية", "الجيزة", "بورسعيد", "المنصورة"],
        "Oman": ["مسقط", "صلالة", "صحار", "نزوى"],
        "Qatar": ["الدوحة", "الريان", "الوكرة", "الخور"],
        "Jordan": ["عمان", "إربد", "الزرقاء", "العقبة"]
    },

    init() {
        this.setupEventListeners();
        this.checkUrlParams();
    },

    quickTrack(number, id, btn) {
        const input = document.getElementById('track-number');
        if (input) input.value = number;
        ShippingState.setShipment(id);
        const tabsBtn = btn.closest('.shipping-admin-layout').querySelector('.shipping-tab-btn:nth-child(2)');
        shippingOpenInternalTab('shipment-tracking', tabsBtn);
        this.trackShipment();
    },

    setupEventListeners() {
        const form = document.getElementById('shipping-create-shipment-form');
        if (form) {
            form.addEventListener('submit', (e) => this.handleCreateShipment(e));

            // Real-time cost calculation triggers
            form.querySelectorAll('input, select').forEach(input => {
                if (['weight', 'distance', 'classification', 'is_urgent', 'is_insured'].includes(input.name)) {
                    input.addEventListener('change', () => this.debounceCalculateCost());
                }
            });

            // Country/City dropdown sync
            form.querySelectorAll('.origin-country-select, .destination-country-select').forEach(select => {
                select.addEventListener('change', (e) => {
                    const type = e.target.name.includes('origin') ? 'origin-city-select' : 'destination-city-select';
                    this.updateCities(e.target, type);
                });
            });
        }
    },

    checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        if (orderId) {
            this.openCreationModal();
            this.loadOrderDataForShipment(orderId);
        }

        if (urlParams.has('trigger_add')) {
            this.openCreationModal();
        }

        const dossierId = urlParams.get('view_dossier');
        if (dossierId) {
            this.viewFullDossier(dossierId);
        }
    },

    openCreationModal() {
        const f = document.getElementById('shipping-create-shipment-form');
        if (f && ShippingState.selectedCustomer) {
            f.customer_id.value = ShippingState.selectedCustomer;
        }
        ShippingModal.open('modal-create-shipment');
        // Initial city load
        document.querySelectorAll('.origin-country-select, .destination-country-select').forEach(s => {
            this.updateCities(s, s.name.includes('origin') ? 'origin-city-select' : 'destination-city-select');
        });
    },

    updateCities(countrySelect, citySelectClass) {
        const country = countrySelect.value;
        const citySelects = document.querySelectorAll('.' + citySelectClass);
        const cities = this.arabCities[country] || [];

        citySelects.forEach(select => {
            select.innerHTML = '<option value="">اختر المدينة...</option>' +
                cities.map(c => `<option value="${c}">${c}</option>`).join('');
        });
    },

    loadOrderDataForShipment(orderId) {
        fetch(ajaxurl + '?action=shipping_get_orders&id=' + orderId)
        .then(r => r.json()).then(res => {
            if (res.success && res.data.length) {
                const o = res.data[0];
                const f = document.getElementById('shipping-create-shipment-form');
                if (f) {
                    document.getElementById('shipment-order-id-input').value = orderId;
                    f.customer_id.value = o.customer_id;
                }
            }
        });
    },

    debounceCalculateCost() {
        if (this.costTimeout) clearTimeout(this.costTimeout);
        this.costTimeout = setTimeout(() => this.calculateCost(), 500);
    },

    calculateCost() {
        const form = document.getElementById('shipping-create-shipment-form');
        const weight = form.weight.value;
        const distance = form.distance.value;
        if (!weight || !distance) return;

        const loader = document.getElementById('cost-loader');
        const details = document.getElementById('cost-details');
        if (loader) loader.style.display = 'block';
        if (details) details.style.display = 'none';

        const fd = new FormData(form);
        fd.append('action', 'shipping_estimate_cost');

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (loader) loader.style.display = 'none';
            if (details) details.style.display = 'block';

            if (res.success) {
                const data = res.data;
                document.getElementById('display-cost').innerText = data.total_cost.toFixed(2);
                document.getElementById('shipment-estimated-cost-input').value = data.total_cost;

                let html = '<div style="display:grid; gap:8px;">';
                html += `<div style="display:flex; justify-content:space-between;"><span>التكلفة الأساسية:</span> <strong>${data.breakdown.base.toFixed(2)}</strong></div>`;
                html += `<div style="display:flex; justify-content:space-between;"><span>وزن (${weight} كجم):</span> <strong>${data.breakdown.weight.toFixed(2)}</strong></div>`;
                html += `<div style="display:flex; justify-content:space-between;"><span>مسافة (${distance} كم):</span> <strong>${data.breakdown.distance.toFixed(2)}</strong></div>`;
                if (data.breakdown.fees > 0) html += `<div style="display:flex; justify-content:space-between; color:#c53030;"><span>إضافات:</span> <strong>+ ${data.breakdown.fees.toFixed(2)}</strong></div>`;
                if (data.breakdown.discount > 0) html += `<div style="display:flex; justify-content:space-between; color:#2f855a;"><span>خصومات:</span> <strong>- ${data.breakdown.discount.toFixed(2)}</strong></div>`;
                html += '</div>';
                document.getElementById('cost-breakdown-list').innerHTML = html;
            }
        });
    },

    handleCreateShipment(e) {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        fd.append('action', 'shipping_create_shipment');

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم إنشاء الشحنة بنجاح');
                ShippingModal.close('modal-create-shipment');
                form.reset();
                location.reload(); // To update registry
            } else alert(res.data);
        });
    },

    trackShipment() {
        const num = document.getElementById('track-number').value;
        if (!num) return alert('يرجى إدخال رقم الشحنة');

        fetch(ajaxurl + '?action=shipping_get_shipment_tracking&number=' + encodeURIComponent(num))
        .then(r => r.json()).then(res => {
            if (res.success) {
                const s = res.data;
                document.getElementById('res-number').innerText = s.shipment_number;
                document.getElementById('res-status').innerText = s.status;
                document.getElementById('res-route').innerText = s.origin + ' ← ' + s.destination;

                let timelineHtml = '';
                if (s.events && s.events.length > 0) {
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
    },

    viewFullDossier(id) {
        const modal = document.getElementById('modal-full-dossier');
        const container = document.getElementById('dossier-content');
        container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:50px;"><span class="dashicons dashicons-update spin" style="font-size:40px; width:40px; height:40px;"></span><br>جاري تجميع ملف البيانات...</div>';
        ShippingModal.open('modal-full-dossier');

        fetch(ajaxurl + '?action=shipping_get_shipment_full_details&id=' + id)
        .then(r => r.json()).then(res => {
            if (!res.success) { alert(res.data); return; }
            const d = res.data;
            document.getElementById('dossier-num').innerText = d.shipment.shipment_number;

            let html = `
                <div class="shipping-card" style="margin:0;">
                    <h5 style="color:var(--shipping-primary-color); border-bottom:1px solid #eee; padding-bottom:10px;">تفاصيل الشحنة واللوجستيات</h5>
                    <div style="font-size:13px; display:grid; gap:8px; margin-top:10px;">
                        <div><strong>العميل:</strong> ${d.shipment.customer_name}</div>
                        <div><strong>المسار:</strong> ${d.shipment.route_name || 'غير محدد'}</div>
                        <div><strong>المركبة:</strong> ${d.shipment.vehicle_number || 'غير محدد'}</div>
                        <div><strong>الوزن:</strong> ${d.shipment.weight} كجم</div>
                        <div><strong>الحالة الحالية:</strong> <span class="shipping-badge">${d.shipment.status}</span></div>
                        <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #eee;">
                            <strong>من:</strong> ${d.shipment.origin}<br>
                            <strong>إلى:</strong> ${d.shipment.destination}
                        </div>
                    </div>
                </div>
                <div class="shipping-card" style="margin:0;">
                    <h5 style="color:#3182ce; border-bottom:1px solid #eee; padding-bottom:10px;">الطلب المرتبط والفواتير</h5>
                    <div style="font-size:13px; display:grid; gap:8px; margin-top:10px;">
                        ${d.order ? `<div><strong>رقم الطلب:</strong> ${d.order.order_number}</div>` : '<div style="color:#e53e3e;">لا يوجد طلب مرتبط</div>'}
                        ${d.invoice ? `<div style="margin-top:10px; padding:10px; background:#f0fff4; border-radius:8px;"><strong>الفاتورة:</strong> ${d.invoice.invoice_number}<br><strong>المبلغ:</strong> ${parseFloat(d.invoice.total_amount).toFixed(2)} ${window.shippingCurrency || ''}</div>` : '<div>لا توجد فاتورة</div>'}
                    </div>
                </div>
                <div class="shipping-card" style="margin:0; grid-column: 1 / -1;">
                    <h5 style="color:#805ad5; border-bottom:1px solid #eee; padding-bottom:10px;">سجل التتبع التاريخي</h5>
                    <div style="max-height:200px; overflow-y:auto; font-size:12px; margin-top:10px;">
                        ${d.events.map(ev => `
                            <div style="display:flex; gap:10px; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #f8f9fa;">
                                <span style="color:#718096; white-space:nowrap;">${ev.created_at}</span>
                                <strong>${ev.status}:</strong> <span>${ev.location || ''} - ${ev.description || ''}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            container.innerHTML = html;
        });
    },

    viewLogs(id, num) {
        document.getElementById('log-shipment-num').innerText = num;
        const container = document.getElementById('shipment-logs-timeline');
        container.innerHTML = '<p style="text-align:center;">جاري تحميل السجل...</p>';
        ShippingModal.open('modal-shipment-logs');

        fetch(ajaxurl + '?action=shipping_get_shipment_logs&id=' + id)
        .then(r => r.json()).then(res => {
            if (!res.data.length) { container.innerHTML = '<p>لا توجد سجلات لهذه الشحنة</p>'; return; }
            container.innerHTML = res.data.map(l => `
                <div class="timeline-item" style="border-right: 2px solid #edf2f7; padding-right: 20px; position: relative; padding-bottom: 15px; margin-right: 10px; text-align: right;">
                    <div style="position: absolute; right: -7px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--shipping-primary-color); border: 2px solid #fff;"></div>
                    <div style="font-weight: 700; font-size: 13px;">${l.action}</div>
                    <div style="font-size: 11px; color: #718096;">بواسطة: ${l.display_name} | ${l.created_at}</div>
                    <div style="font-size: 12px; margin-top: 5px; background: #f8fafc; padding: 5px; border-radius: 5px;">
                        <span style="color:#718096">من:</span> ${l.old_value || '---'} <br>
                        <span style="color:#718096">إلى:</span> ${l.new_value}
                    </div>
                </div>
            `).join('');
        });
    },

    processBulk() {
        const rowsRaw = document.getElementById('bulk-rows').value;
        if (!rowsRaw) return alert('يرجى إدخال البيانات');
        try { JSON.parse(rowsRaw); } catch(e) { return alert('تنسيق JSON غير صحيح'); }

        const fd = new FormData();
        fd.append('action', 'shipping_bulk_shipments');
        fd.append('rows', rowsRaw);

        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تمت المعالجة بنجاح');
                location.reload();
            } else alert(res.data);
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('shipment-registry') || document.getElementById('track-number')) {
        ShipmentsController.init();
    }
});
