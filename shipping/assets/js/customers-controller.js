/**
 * Customers Management Controller
 */

window.CustomersController = {
    init() {
        this.setupEventListeners();
        // Check if we need to load contracts on init
        const activeTab = document.querySelector('.shipping-tab-btn.shipping-active');
        if (activeTab && activeTab.textContent.includes('العقود')) {
            this.loadContracts();
        }
    },

    setupEventListeners() {
        const addCustomerForm = document.getElementById('shipping-add-customer-form');
        if (addCustomerForm) {
            addCustomerForm.addEventListener('submit', (e) => this.handleAddCustomer(e));
        }

        const addContractForm = document.getElementById('form-add-contract');
        if (addContractForm) {
            addContractForm.addEventListener('submit', (e) => this.handleAddContract(e));
        }
    },

    handleAddCustomer(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'جاري الحفظ...';

        const fd = new FormData(form);
        fd.append('action', 'shipping_add_customer_ajax');
        fd.append('shipping_nonce', shippingVars.customerNonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            btn.disabled = false;
            btn.innerText = 'حفظ بيانات العميل';
            if (res.success) {
                shippingShowNotification('تمت إضافة العميل بنجاح');
                ShippingModal.close('add-customer-modal');
                location.reload(); // Refresh to update list, or we could update DOM
            } else {
                alert(res.data);
            }
        });
    },

    loadContracts() {
        const tbody = document.getElementById('contracts-table-body');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;"><span class="dashicons dashicons-update spin"></span> جاري التحميل...</td></tr>';

        fetch(ajaxurl + '?action=shipping_get_contracts&nonce=' + shippingVars.nonce)
        .then(r => r.json()).then(res => {
            if (!res.data.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">لا توجد عقود مسجلة</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(c => `
                <tr>
                    <td><strong>${c.contract_number}</strong></td>
                    <td>${c.customer_name}</td>
                    <td>${c.title}</td>
                    <td style="color: ${new Date(c.end_date) < new Date() ? '#e53e3e' : 'inherit'}">${c.end_date}</td>
                    <td><span class="shipping-badge">${c.status}</span></td>
                    <td><a href="${c.file_url}" target="_blank" class="shipping-btn-outline" style="padding:4px 8px; font-size:10px;">عرض العقد</a></td>
                </tr>
            `).join('');
        });
    },

    handleAddContract(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerText = 'جاري الحفظ...';

        const fd = new FormData(form);
        fd.append('action', 'shipping_add_contract');
        fd.append('nonce', shippingVars.contractNonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            btn.disabled = false;
            btn.innerText = 'حفظ العقد';
            if (res.success) {
                shippingShowNotification('تم حفظ العقد بنجاح');
                ShippingModal.close('modal-add-contract');
                form.reset();
                this.loadContracts();
            } else {
                alert(res.data);
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('customer-profiles') || document.getElementById('contracts-table-body')) {
        CustomersController.init();
    }
});
