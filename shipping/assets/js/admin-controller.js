/**
 * General Admin Controller
 * Handles system settings, logs, pages, and alerts
 */

window.AdminController = {
    init() {
        this.setupEventListeners();
    },

    openSubTab(tab, btn) {
        const container = btn.closest('.shipping-notifications-settings') || btn.closest('.shipping-main-panel');
        if (!container) return;

        container.querySelectorAll('.shipping-sub-tab').forEach(t => t.style.display = 'none');
        const target = document.getElementById(tab);
        if (target) target.style.display = 'block';

        btn.parentElement.querySelectorAll('.shipping-tab-btn').forEach(b => b.classList.remove('shipping-active'));
        btn.classList.add('shipping-active');
    },

    setupEventListeners() {
        this.bindForm('shipping-edit-page-form', 'shipping_save_page_settings', () => location.reload(), shippingVars.nonce);
        this.bindForm('shipping-add-article-form', 'shipping_add_article', () => location.reload(), shippingVars.nonce);
        this.bindForm('shipping-alert-form', 'shipping_save_alert', () => location.reload(), shippingVars.nonce);
        this.bindForm('shipping-notif-template-form', 'shipping_save_template_ajax', () => shippingShowNotification('تم حفظ القالب بنجاح'), shippingVars.nonce);
    },

    bindForm(formId, action, callback, nonce) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(form);
            if (!fd.has('action')) fd.append('action', action);
            if (nonce) fd.append('nonce', nonce);

            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                if (res.success) {
                    const modalId = form.closest('.shipping-modal-overlay')?.id;
                    if (modalId) ShippingModal.close(modalId);
                    if (callback) callback(res);
                } else alert(res.data);
            });
        });
    },

    // --- Logs ---
    viewLogDetails(log) {
        const detailsBody = document.getElementById('log-details-body');
        if (!detailsBody) return;
        let detailsText = log.details;

        if (log.details.startsWith('ROLLBACK_DATA:')) {
            try {
                const data = JSON.parse(log.details.replace('ROLLBACK_DATA:', ''));
                detailsText = `<pre style="background:#f4f4f4; padding:10px; border-radius:5px; font-size:11px; overflow-x:auto;">${JSON.stringify(data, null, 2)}</pre>`;
            } catch(e) {
                detailsText = log.details;
            }
        }

        detailsBody.innerHTML = `
            <div style="display:grid; gap:15px;">
                <div><strong>المشغل:</strong> ${log.display_name || 'نظام'}</div>
                <div><strong>الوقت:</strong> ${log.created_at}</div>
                <div><strong>الإجراء:</strong> <span class="shipping-badge shipping-badge-low">${log.action}</span></div>
                <div><strong>بيانات العملية:</strong><br>${detailsText}</div>
            </div>
        `;
        ShippingModal.open('log-details-modal');
    },

    rollbackLog(logId) {
        if (!confirm('هل أنت متأكد من رغبتك في استعادة هذه البيانات؟ سيتم محاولة عكس العملية.')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_rollback_log_ajax');
        fd.append('log_id', logId);
        fd.append('nonce', shippingVars.nonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 500);
            } else alert('خطأ: ' + res.data);
        });
    },

    deleteLog(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_log');
        fd.append('log_id', logId);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) location.reload();
        });
    },

    deleteAllLogs() {
        if (!confirm('هل أنت متأكد من مسح كافة السجلات؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_clear_all_logs');
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if (res.success) location.reload();
        });
    },

    // --- System ---
    resetSystem() {
        const password = prompt('تحذير نهائي: سيتم مسح كافة بيانات النظام بالكامل. يرجى إدخال كلمة مرور مدير النظام للتأكيد:');
        if (!password) return;
        if (!confirm('هل أنت متأكد تماماً؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'shipping_reset_system_ajax');
        fd.append('admin_password', password);
        fd.append('nonce', shippingVars.nonce);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                alert('تمت إعادة تهيئة النظام بنجاح.');
                location.reload();
            } else alert('خطأ: ' + res.data);
        });
    },

    openMediaUploader(inputId) {
        const frame = wp.media({
            title: 'اختر شعار Shipping',
            button: { text: 'استخدام هذا الشعار' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });
        frame.open();
    },

    toggleUserDropdown() {
        const menu = document.getElementById('shipping-user-dropdown-menu');
        if (!menu) return;
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
            document.getElementById('shipping-profile-view').style.display = 'block';
            document.getElementById('shipping-profile-edit').style.display = 'none';
            const notif = document.getElementById('shipping-notifications-menu');
            if (notif) notif.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    },

    toggleNotifications() {
        const menu = document.getElementById('shipping-notifications-menu');
        if (!menu) return;
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
            const userMenu = document.getElementById('shipping-user-dropdown-menu');
            if (userMenu) userMenu.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    },

    saveProfile() {
        const fd = new FormData();
        fd.append('action', 'shipping_update_profile_ajax');
        fd.append('nonce', shippingVars.profileNonce);
        fd.append('first_name', document.getElementById('shipping_edit_first_name').value);
        fd.append('last_name', document.getElementById('shipping_edit_last_name').value);
        fd.append('user_email', document.getElementById('shipping_edit_user_email').value);
        fd.append('user_pass', document.getElementById('shipping_edit_user_pass').value);

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.success) {
                shippingShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else shippingShowNotification('خطأ: ' + res.data, 'error');
        });
    },

    // --- Pages & Articles ---
    editPageSettings(page) {
        document.getElementById('edit-page-id').value = page.id;
        document.getElementById('page-edit-name').innerText = page.title;
        document.getElementById('edit-page-title').value = page.title;
        document.getElementById('edit-page-instructions').value = page.instructions;
        ShippingModal.open('shipping-edit-page-modal');
    },

    deleteArticle(id) {
        if(!confirm('هل أنت متأكد من حذف هذا المقال؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_article');
        fd.append('id', id);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    },

    // --- Alerts ---
    editAlert(al) {
        const f = document.getElementById('shipping-alert-form');
        document.getElementById('edit-alert-id').value = al.id;
        f.title.value = al.title;
        f.message.value = al.message;
        f.severity.value = al.severity;
        f.status.value = al.status;
        f.must_acknowledge.checked = al.must_acknowledge == 1;
        document.getElementById('shipping-alert-modal-title').innerText = 'تعديل التنبيه';
        ShippingModal.open('shipping-alert-modal');
    },

    deleteAlert(id) {
        if(!confirm('هل أنت متأكد من حذف هذا التنبيه؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_alert');
        fd.append('id', id);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd }).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    },

    applyAlertTemplate(type) {
        const templates = {
            payment: { title: 'تذكير بسداد الرسوم', message: 'نود تذكيركم بضرورة سداد رسوم الحساب المتأخرة لتجنب غرامات التأخير ولضمان استمرار الخدمات.', severity: 'warning', must_acknowledge: 1 },
            expiry: { title: 'تنبيه: انتهاء صلاحية الحساب', message: 'عميليتكم ستنتهي قريباً، يرجى التوجه لقسم المالية أو السداد إلكترونياً لتجديد الحساب.', severity: 'critical', must_acknowledge: 1 },
            maintenance: { title: 'إعلان صيانة النظام', message: 'سيتم إيقاف النظام مؤقتاً لأعمال الصيانة الدورية يوم الجمعة القادم من الساعة 2 صباحاً وحتى 6 صباحاً.', severity: 'info', must_acknowledge: 0 },
            docs: { title: 'تذكير باستكمال الوثائق', message: 'يرجى مراجعة ملفكم الشخصي ورفع الوثائق المطلوبة لاستكمال ملف الحساب الرقمي.', severity: 'info', must_acknowledge: 0 },
            urgent: { title: 'قرار إداري عاجل', message: 'بناءً على اجتماع مجلس الإدارة الأخير، تقرر البدء في تنفيذ الآلية الجديدة لتوزيع الحوافز المهنية.', severity: 'critical', must_acknowledge: 1 }
        };
        const t = templates[type];
        if(!t) return;
        const f = document.getElementById('shipping-alert-form');
        f.title.value = t.title;
        f.message.value = t.message;
        f.severity.value = t.severity;
        f.must_acknowledge.checked = t.must_acknowledge == 1;
        document.getElementById('shipping-alert-modal-title').innerText = 'إنشاء تنبيه من قالب';
        ShippingModal.open('shipping-alert-modal');
    },

    loadNotifTemplate(type) {
        const fd = new FormData();
        fd.append('action', 'shipping_get_template_ajax');
        fd.append('type', type);
        fd.append('nonce', shippingVars.nonce);
        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const t = res.data;
                document.getElementById('tmpl_type').value = t.template_type;
                document.getElementById('tmpl_subject').value = t.subject;
                document.getElementById('tmpl_body').value = t.body;
                document.getElementById('tmpl_days').value = t.days_before;
                document.getElementById('tmpl_enabled').checked = t.is_enabled == 1;
                document.getElementById('notif-template-editor').style.display = 'block';
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    AdminController.init();
});
