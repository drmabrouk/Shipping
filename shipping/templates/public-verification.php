<?php if (!defined('ABSPATH')) exit; ?>
<div class="shipping-verify-container" dir="rtl">
    <div class="shipping-verify-header">
        <h2 style="font-weight: 800; color: var(--shipping-dark-color); margin-bottom: 10px;">محرك التحقق الرسمي</h2>
        <p style="color: #64748b; font-size: 14px;">قم بالتحقق من صحة وصلاحية المستندات والعميليات الرسمية الصادرة عن Shipping.</p>
    </div>

    <div class="shipping-verify-search-box">
        <form id="shipping-verify-form">
            <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: flex-end;">
                <div class="shipping-form-group" style="margin-bottom: 0;">
                    <label class="shipping-label">نوع البحث:</label>
                    <select id="shipping-verify-type" class="shipping-select" style="background: #fff;">
                        <option value="all">اسم المستخدم</option>
                        <option value="customership">رقم التعريف</option>
                        <option value="license">رقم رخصة المنشأة</option>
                        <option value="practice">رقم تصريح المزاولة</option>
                    </select>
                </div>
                <div class="shipping-form-group" style="margin-bottom: 0;">
                    <label class="shipping-label">قيمة البحث:</label>
                    <input type="text" id="shipping-verify-value" class="shipping-input" placeholder="أدخل الرقم المراد التحقق منه..." style="background: #fff;">
                </div>
                <button type="submit" class="shipping-btn" style="height: 45px; padding: 0 30px; font-weight: 700;">تحقق الآن</button>
            </div>
        </form>
    </div>

    <div id="shipping-verify-loading" style="display: none; text-align: center; padding: 40px;">
        <span class="dashicons dashicons-update spin" style="font-size: 30px; color: var(--shipping-primary-color); width: 30px; height: 30px;"></span>
        <p style="margin-top: 10px; color: #64748b;">جاري استعلام البيانات من قاعدة البيانات...</p>
    </div>

    <div id="shipping-verify-results" style="margin-top: 30px;"></div>
</div>

<style>
/* Verification styles handled in shipping-public.css */
#shipping-verify-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 150px;
}
</style>

<script>
(function($) {
    $('#shipping-verify-form').on('submit', function(e) {
        e.preventDefault();
        const val = $('#shipping-verify-value').val();
        const type = $('#shipping-verify-type').val();
        const results = $('#shipping-verify-results').empty();
        const loading = $('#shipping-verify-loading').show();

        const fd = new FormData();
        fd.append('action', 'shipping_verify_document');
        fd.append('search_value', val);
        fd.append('search_type', type);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            loading.hide();
            if (res.success) {
                renderResults(res.data);
            } else {
                results.append(`<div style="background: #fff5f5; color: #c53030; padding: 20px; border-radius: 10px; border: 1px solid #feb2b2; text-align: center; font-weight: 600;">${res.data}</div>`);
            }
        });
    });

    function renderResults(data) {
        const results = $('#shipping-verify-results');
        const today = new Date();

        for (let k in data) {
            const doc = data[k];
            let statusClass = 'shipping-verify-status-valid';
            let statusLabel = 'صالح / ساري';

            if (doc.expiry) {
                const expiry = new Date(doc.expiry);
                if (expiry < today) {
                    statusClass = 'shipping-verify-status-invalid';
                    statusLabel = 'منتهي الصلاحية';
                }
            }

            let html = `
                <div class="shipping-verify-card">
                    <div class="shipping-verify-card-header">
                        <h3 style="margin: 0; font-weight: 800; color: var(--shipping-primary-color); font-size: 1.1em;">${doc.label}</h3>
                        <span class="shipping-badge ${statusClass === 'shipping-verify-status-valid' ? 'shipping-badge-high' : 'shipping-badge-urgent'}" style="font-size: 11px;">${statusLabel}</span>
                    </div>
                    <div class="shipping-verify-grid">
            `;

            if (k === 'customership') {
                html += `
                    <div class="shipping-verify-item"><label>الاسم</label><span>${doc.name}</span></div>
                    <div class="shipping-verify-item"><label>رقم القيد</label><span>${doc.number}</span></div>
                    <div class="shipping-verify-item"><label>تاريخ الانتهاء</label><span class="${statusClass}">${doc.expiry || 'غير محدد'}</span></div>
                `;
            } else if (k === 'license') {
                html += `
                    <div class="shipping-verify-item"><label>اسم المنشأة</label><span>${doc.facility_name}</span></div>
                    <div class="shipping-verify-item"><label>رقم الرخصة</label><span>${doc.number}</span></div>
                    <div class="shipping-verify-item"><label>الفئة</label><span>${doc.category}</span></div>
                    <div class="shipping-verify-item"><label>العنوان</label><span>${doc.address}</span></div>
                    <div class="shipping-verify-item"><label>تاريخ الانتهاء</label><span class="${statusClass}">${doc.expiry || 'غير محدد'}</span></div>
                `;
            } else if (k === 'practice') {
                html += `
                    <div class="shipping-verify-item"><label>اسم صاحب التصريح</label><span>${doc.name}</span></div>
                    <div class="shipping-verify-item"><label>رقم التصريح</label><span>${doc.number}</span></div>
                    <div class="shipping-verify-item"><label>تاريخ الإصدار</label><span>${doc.issue_date}</span></div>
                    <div class="shipping-verify-item"><label>تاريخ الانتهاء</label><span class="${statusClass}">${doc.expiry || 'غير محدد'}</span></div>
                `;
            }

            html += `</div></div>`;
            results.append(html);
        }
    }
})(jQuery);
</script>
