<?php if (!defined('ABSPATH')) exit; ?>
<script>
/**
 * SHIPPING - CORE UI ENGINE (ULTRA HARDENED V5)
 * Standard linking and routing fix.
 */
(function(window) {
    const SHIPPING_UI = {
        showNotification: function(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'shipping-toast';
            toast.style.cssText = "position:fixed; top:20px; left:50%; transform:translateX(-50%); background:white; padding:15px 30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:10001; display:flex; align-items:center; gap:10px; border-right:5px solid " + (isError ? '#e53e3e' : '#38a169');
            toast.innerHTML = `<strong>${isError ? '✖' : '✓'}</strong> <span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = '0.5s'; setTimeout(() => toast.remove(), 500); }, 3000);
        },

        openInternalTab: function(tabId, element) {
            console.log('Opening tab:', tabId);
            const target = document.getElementById(tabId);
            if (!target || !element) {
                console.error('Target or element not found:', tabId, element);
                return;
            }
            const container = target.parentElement;
            container.querySelectorAll('.shipping-internal-tab').forEach(p => p.style.setProperty('display', 'none', 'important'));
            target.style.setProperty('display', 'block', 'important');
            element.parentElement.querySelectorAll('.shipping-tab-btn').forEach(b => b.classList.remove('shipping-active'));
            element.classList.add('shipping-active');
        }
    };

    window.shippingShowNotification = SHIPPING_UI.showNotification;
    window.shippingOpenInternalTab = SHIPPING_UI.openInternalTab;

    window.shippingViewLogDetails = function(log) {
        const detailsBody = document.getElementById('log-details-body');
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
        document.getElementById('log-details-modal').style.display = 'flex';
    };

    window.shippingRollbackLog = function(logId) {
        if (!confirm('هل أنت متأكد من رغبتك في استعادة هذه البيانات؟ سيتم محاولة عكس العملية.')) return;

        const fd = new FormData();
        fd.append('action', 'shipping_rollback_log_ajax');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                shippingShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };

    // MEDIA UPLOADER FOR LOGO
    window.shippingResetSystem = function() {
        const password = prompt('تحذير نهائي: سيتم مسح كافة بيانات النظام بالكامل. يرجى إدخال كلمة مرور مدير النظام للتأكيد:');
        if (!password) return;

        if (!confirm('هل أنت متأكد تماماً؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const fd = new FormData();
        fd.append('action', 'shipping_reset_system_ajax');
        fd.append('admin_password', password);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تمت إعادة تهيئة النظام بنجاح.');
                location.reload();
            } else {
                alert('خطأ: ' + res.data);
            }
        });
    };


    window.shippingDeleteLog = function(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_log');
        fd.append('log_id', logId);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.shippingDeleteAllLogs = function() {
        if (!confirm('هل أنت متأكد من مسح كافة السجلات؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_clear_all_logs');
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?_=' + Date.now(), { method: 'POST', body: fd })
        .then(r => r.json()).then(res => { if (res.success) location.reload(); });
    };

    window.shippingOpenMediaUploader = function(inputId) {
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
    };

    window.shippingToggleUserDropdown = function() {
        const menu = document.getElementById('shipping-user-dropdown-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            document.getElementById('shipping-profile-view').style.display = 'block';
            document.getElementById('shipping-profile-edit').style.display = 'none';
            const notif = document.getElementById('shipping-notifications-menu');
            if (notif) notif.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.shippingToggleNotifications = function() {
        const menu = document.getElementById('shipping-notifications-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            const userMenu = document.getElementById('shipping-user-dropdown-menu');
            if (userMenu) userMenu.style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.shippingEditProfile = function() {
        document.getElementById('shipping-profile-view').style.display = 'none';
        document.getElementById('shipping-profile-edit').style.display = 'block';
    };

    window.shippingSaveProfile = function() {
        const firstName = document.getElementById('shipping_edit_first_name').value;
        const lastName = document.getElementById('shipping_edit_last_name').value;
        const email = document.getElementById('shipping_edit_user_email').value;
        const pass = document.getElementById('shipping_edit_user_pass').value;
        const nonce = '<?php echo wp_create_nonce("shipping_profile_action"); ?>';

        const formData = new FormData();
        formData.append('action', 'shipping_update_profile_ajax');
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('user_email', email);
        formData.append('user_pass', pass);
        formData.append('nonce', nonce);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                shippingShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                shippingShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.shipping-user-dropdown');
        const menu = document.getElementById('shipping-user-dropdown-menu');
        if (dropdown && !dropdown.contains(e.target)) {
            if (menu) menu.style.display = 'none';
        }
    });

    window.addEventListener('load', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('settings_saved')) {
            shippingShowNotification('تم حفظ الإعدادات بنجاح');
        }
    });

    window.shippingEditPageSettings = function(page) {
        document.getElementById('edit-page-id').value = page.id;
        document.getElementById('page-edit-name').innerText = page.title;
        document.getElementById('edit-page-title').value = page.title;
        document.getElementById('edit-page-instructions').value = page.instructions;
        document.getElementById('shipping-edit-page-modal').style.display = 'flex';
    };

    document.getElementById('shipping-edit-page-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'shipping_save_page_settings');
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { shippingShowNotification('تم تحديث الصفحة'); location.reload(); }
            else alert(res.data);
        });
    });

    window.shippingOpenAddArticleModal = function() {
        document.getElementById('shipping-add-article-modal').style.display = 'flex';
    };

    document.getElementById('shipping-add-article-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'shipping_add_article');
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { shippingShowNotification('تم نشر المقال'); location.reload(); }
            else alert(res.data);
        });
    });

    window.shippingDeleteArticle = function(id) {
        if(!confirm('هل أنت متأكد من حذف هذا المقال؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_article');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    }

    window.shippingOpenAddAlertModal = function() {
        document.getElementById('shipping-alert-form').reset();
        document.getElementById('edit-alert-id').value = '';
        document.getElementById('shipping-alert-modal-title').innerText = 'إنشاء تنبيه نظام جديد';
        document.getElementById('shipping-alert-modal').style.display = 'flex';
    };

    window.shippingEditAlert = function(al) {
        const f = document.getElementById('shipping-alert-form');
        document.getElementById('edit-alert-id').value = al.id;
        f.title.value = al.title;
        f.message.value = al.message;
        f.severity.value = al.severity;
        f.status.value = al.status;
        f.must_acknowledge.checked = al.must_acknowledge == 1;
        document.getElementById('shipping-alert-modal-title').innerText = 'تعديل التنبيه';
        document.getElementById('shipping-alert-modal').style.display = 'flex';
    };

    window.shippingDeleteAlert = function(id) {
        if(!confirm('هل أنت متأكد من حذف هذا التنبيه؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_delete_alert');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) location.reload();
        });
    };

    const alertTemplates = {
        payment: { title: 'تذكير بسداد الرسوم', message: 'نود تذكيركم بضرورة سداد رسوم الحساب المتأخرة لتجنب غرامات التأخير ولضمان استمرار الخدمات.', severity: 'warning', must_acknowledge: 1 },
        expiry: { title: 'تنبيه: انتهاء صلاحية الحساب', message: 'عميليتكم ستنتهي قريباً، يرجى التوجه لقسم المالية أو السداد إلكترونياً لتجديد الحساب.', severity: 'critical', must_acknowledge: 1 },
        maintenance: { title: 'إعلان صيانة النظام', message: 'سيتم إيقاف النظام مؤقتاً لأعمال الصيانة الدورية يوم الجمعة القادم من الساعة 2 صباحاً وحتى 6 صباحاً.', severity: 'info', must_acknowledge: 0 },
        docs: { title: 'تذكير باستكمال الوثائق', message: 'يرجى مراجعة ملفكم الشخصي ورفع الوثائق المطلوبة لاستكمال ملف الحساب الرقمي.', severity: 'info', must_acknowledge: 0 },
        urgent: { title: 'قرار إداري عاجل', message: 'بناءً على اجتماع مجلس الإدارة الأخير، تقرر البدء في تنفيذ الآلية الجديدة لتوزيع الحوافز المهنية.', severity: 'critical', must_acknowledge: 1 }
    };

    window.shippingApplyAlertTemplate = function(type) {
        const t = alertTemplates[type];
        if(!t) return;
        const f = document.getElementById('shipping-alert-form');
        f.title.value = t.title;
        f.message.value = t.message;
        f.severity.value = t.severity;
        f.must_acknowledge.checked = t.must_acknowledge == 1;
        document.getElementById('shipping-alert-modal-title').innerText = 'إنشاء تنبيه من قالب';
        document.getElementById('shipping-alert-modal').style.display = 'flex';
    };

    document.getElementById('shipping-alert-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'shipping_save_alert');
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch(ajaxurl, {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
            if(res.success) { shippingShowNotification('تم حفظ التنبيه'); location.reload(); }
            else alert(res.data);
        });
    });

    window.shippingLoadNotifTemplate = function(type) {
        const fd = new FormData();
        fd.append('action', 'shipping_get_template_ajax');
        fd.append('type', type);
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
    };

    document.getElementById('shipping-notif-template-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('action', 'shipping_save_template_ajax');
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_admin_action"); ?>');
        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) shippingShowNotification('تم حفظ القالب بنجاح');
            else alert(res.data);
        });
    });

})(window);
</script>

<?php
global $wpdb;
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('administrator', $roles);
$is_administrator = in_array('administrator', $roles);
$is_subscriber = in_array('subscriber', $roles);
$is_customer = in_array('subscriber', $roles);
$is_officer = $is_administrator;

$active_tab = isset($_GET['shipping_tab']) ? sanitize_text_field($_GET['shipping_tab']) : 'summary';
$is_restricted = $is_subscriber;
if ($is_restricted && !in_array($active_tab, ['my-profile', 'customer-profile', 'messaging'])) {
    $active_tab = 'my-profile';
}

$shipping = Shipping_Settings::get_shipping_info();
$labels = Shipping_Settings::get_labels();
$appearance = Shipping_Settings::get_appearance();
$stats = array();

if ($active_tab === 'summary') {
    $stats = Shipping_DB::get_statistics();
}

// Dynamic Greeting logic
$hour = (int)current_time('G');
$greeting = ($hour >= 5 && $hour < 12) ? 'صباح الخير' : 'مساء الخير';
?>

<div class="shipping-admin-dashboard" dir="rtl" style="font-family: 'Rubik', sans-serif; background: <?php echo $appearance['bg_color']; ?>; border: 1px solid var(--shipping-border-color); border-radius: 12px; overflow: hidden; color: <?php echo $appearance['font_color']; ?>; font-size: <?php echo $appearance['font_size']; ?>; font-weight: <?php echo $appearance['font_weight']; ?>; line-height: <?php echo $appearance['line_spacing']; ?>;">
    <!-- OFFICIAL SYSTEM HEADER -->
    <div class="shipping-main-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if (!empty($shipping['shipping_logo'])): ?>
                <div style="background: white; padding: 5px; border: 1px solid var(--shipping-border-color); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <img src="<?php echo esc_url($shipping['shipping_logo']); ?>" style="height: 45px; width: auto; object-fit: contain; display: block;">
                </div>
            <?php else: ?>
                <div style="background: #f1f5f9; padding: 5px; border: 1px solid var(--shipping-border-color); border-radius: 10px; height: 45px; width: 45px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    <span class="dashicons dashicons-building" style="font-size: 24px; width: 24px; height: 24px;"></span>
                </div>
            <?php endif; ?>
            <div>
                <h1 style="margin:0; border: none; padding: 0; color: var(--shipping-dark-color); font-weight: 800; font-size: 1.3em; text-decoration: none; line-height: 1;">
                    <?php echo esc_html($shipping['shipping_name']); ?>
                </h1>
                <div style="display: inline-flex; flex-direction: column; align-items: center; padding: 5px 15px; background: #f0f4f8; color: #111F35; border-radius: 12px; font-size: 11px; font-weight: 700; margin-top: 6px; border: 1px solid #cbd5e0; line-height: 1.4;">
                    <div>
                        <?php
                        if ($is_admin || $is_sys_admin) echo 'مدير نظام الشحن';
                        elseif ($is_administrator) echo 'مسؤول الشحن';
                        elseif ($is_subscriber) echo 'عميل نظام الشحن';
                        elseif ($is_customer) echo 'عميل';
                        else echo 'مستخدم النظام';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="shipping-header-info-box" style="text-align: right; border-left: 1px solid var(--shipping-border-color); padding-left: 15px;">
                <div style="font-size: 0.85em; font-weight: 700; color: var(--shipping-dark-color);"><?php echo date_i18n('l j F Y'); ?></div>
            </div>

            <div style="display: flex; gap: 15px; align-items: center; border-left: 1px solid var(--shipping-border-color); padding-left: 20px;">
                <!-- Messages Icon -->
                <a href="<?php echo add_query_arg('shipping_tab', 'messaging'); ?>" class="shipping-header-circle-icon" title="المراسلات والشكاوى">
                    <span class="dashicons dashicons-email"></span>
                    <?php
                    $unread_msgs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_messages WHERE receiver_id = %d AND is_read = 0", $user->ID));
                    if ($unread_msgs > 0): ?>
                        <span class="shipping-icon-badge" style="background: #e53e3e;"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Notifications Icon -->
                <div class="shipping-notifications-dropdown" style="position: relative;">
                    <a href="javascript:void(0)" onclick="shippingToggleNotifications()" class="shipping-header-circle-icon" title="التنبيهات">
                        <span class="dashicons dashicons-bell"></span>
                        <?php
                        $notif_alerts = [];
                        if ($is_restricted) {
                            $customer_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id, last_paid_customership_year FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));
                            if ($customer_by_wp) {
                                if ($customer_by_wp->last_paid_customership_year < date('Y')) {
                                    $notif_alerts[] = ['text' => 'يوجد متأخرات في تجديد الحساب السنوية', 'type' => 'warning'];
                                }
                            }
                        }

                        // Integrated System Alerts
                        $sys_alerts = Shipping_DB::get_active_alerts_for_user($user->ID);
                        foreach($sys_alerts as $sa) {
                            $notif_alerts[] = ['text' => $sa->title, 'type' => 'system', 'id' => $sa->id];
                        }

                        if (count($notif_alerts) > 0): ?>
                            <span class="shipping-icon-dot" style="background: #f6ad55;"></span>
                        <?php endif; ?>
                    </a>
                    <div id="shipping-notifications-menu" style="display: none; position: absolute; top: 150%; left: 0; background: white; border: 1px solid var(--shipping-border-color); border-radius: 8px; width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; padding: 15px;">
                        <h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px;">التنبيهات والإشعارات</h4>
                        <?php if (empty($notif_alerts)): ?>
                            <div style="font-size: 12px; color: #94a3b8; text-align: center; padding: 10px;">لا توجد تنبيهات جديدة حالياً</div>
                        <?php else: ?>
                            <?php foreach ($notif_alerts as $a): ?>
                                <div style="font-size: 12px; padding: 8px; border-bottom: 1px solid #f9fafb; color: #4a5568; display: flex; gap: 8px; align-items: flex-start;">
                                    <span class="dashicons <?php echo $a['type'] == 'system' ? 'dashicons-megaphone' : 'dashicons-warning'; ?>" style="font-size: 16px; color: <?php echo $a['type'] == 'system' ? 'var(--shipping-primary-color)' : '#d69e2e'; ?>;"></span>
                                    <span>
                                        <?php echo $a['text']; ?>
                                        <?php if($a['type'] == 'system'): ?>
                                            <br><a href="javascript:location.reload()" style="font-size:10px; color:var(--shipping-primary-color); font-weight:700;">عرض التفاصيل</a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="shipping-user-dropdown" style="position: relative;">
                <div class="shipping-user-profile-nav" onclick="shippingToggleUserDropdown()" style="display: flex; align-items: center; gap: 12px; background: white; padding: 6px 12px; border-radius: 50px; border: 1px solid var(--shipping-border-color); cursor: pointer;">
                    <div style="text-align: right;">
                        <div style="font-size: 0.85em; font-weight: 700; color: var(--shipping-dark-color);"><?php echo $greeting . '، ' . $user->display_name; ?></div>
                        <div style="font-size: 0.7em; color: #38a169;">متصل الآن <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px;"></span></div>
                    </div>
                    <?php echo get_avatar($user->ID, 32, '', '', array('style' => 'border-radius: 50%; border: 2px solid var(--shipping-primary-color);')); ?>
                </div>
                <div id="shipping-user-dropdown-menu" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid var(--shipping-border-color); border-radius: 8px; width: 260px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; animation: shippingFadeIn 0.2s ease-out; padding: 10px 0;">
                    <div id="shipping-profile-view">
                        <div style="padding: 10px 20px; border-bottom: 1px solid #f0f0f0; margin-bottom: 5px;">
                            <div style="font-weight: 800; color: var(--shipping-dark-color);"><?php echo $user->display_name; ?></div>
                            <div style="font-size: 11px; color: var(--shipping-text-gray);"><?php echo $user->user_email; ?></div>
                        </div>
                        <?php if (!$is_customer): ?>
                            <a href="javascript:shippingEditProfile()" class="shipping-dropdown-item"><span class="dashicons dashicons-edit"></span> تعديل البيانات الشخصية</a>
                        <?php endif; ?>
                        <?php if ($is_customer): ?>
                            <a href="javascript:shippingEditProfile()" class="shipping-dropdown-item"><span class="dashicons dashicons-lock"></span> تغيير كلمة المرور</a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo add_query_arg('shipping_tab', 'advanced-settings'); ?>" class="shipping-dropdown-item"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <?php endif; ?>
                        <a href="javascript:location.reload()" class="shipping-dropdown-item"><span class="dashicons dashicons-update"></span> تحديث الصفحة</a>
                    </div>

                    <div id="shipping-profile-edit" style="display: none; padding: 15px;">
                        <div style="font-weight: 800; margin-bottom: 15px; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 10px;">تعديل الملف الشخصي</div>
                        <div class="shipping-form-group" style="margin-bottom: 10px;">
                            <label class="shipping-label" style="font-size: 11px;">الاسم الأول:</label>
                            <input type="text" id="shipping_edit_first_name" class="shipping-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr(get_user_meta($user->ID, 'first_name', true)); ?>" <?php if ($is_customer) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="shipping-form-group" style="margin-bottom: 10px;">
                            <label class="shipping-label" style="font-size: 11px;">اسم العائلة:</label>
                            <input type="text" id="shipping_edit_last_name" class="shipping-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr(get_user_meta($user->ID, 'last_name', true)); ?>" <?php if ($is_customer) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="shipping-form-group" style="margin-bottom: 10px;">
                            <label class="shipping-label" style="font-size: 11px;">البريد الإلكتروني:</label>
                            <input type="email" id="shipping_edit_user_email" class="shipping-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->user_email); ?>" <?php if ($is_customer) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="shipping-form-group" style="margin-bottom: 15px;">
                            <label class="shipping-label" style="font-size: 11px;">كلمة مرور جديدة (اختياري):</label>
                            <input type="password" id="shipping_edit_user_pass" class="shipping-input" style="padding: 8px; font-size: 12px;" placeholder="********">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="shippingSaveProfile()" class="shipping-btn" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">حفظ</button>
                            <button onclick="document.getElementById('shipping-profile-edit').style.display='none'; document.getElementById('shipping-profile-view').style.display='block';" class="shipping-btn shipping-btn-outline" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">إلغاء</button>
                        </div>
                    </div>

                    <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                    <a href="<?php echo wp_logout_url(home_url('/shipping-login')); ?>" class="shipping-dropdown-item" style="color: #e53e3e;"><span class="dashicons dashicons-logout"></span> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>

    <div class="shipping-admin-layout" style="display: flex; min-height: 800px;">
        <!-- SIDEBAR -->
        <?php $is_restricted = $is_subscriber; ?>
        <div class="shipping-sidebar" style="width: 280px; flex-shrink: 0; background: <?php echo $appearance['sidebar_bg_color']; ?>; border-left: 1px solid var(--shipping-border-color); padding: 20px 0;">
            <ul style="list-style: none; padding: 0; margin: 0;">

                <?php if (!$is_restricted): ?>
                <li class="shipping-sidebar-item <?php echo $active_tab == 'summary' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg('shipping_tab', 'summary'); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-dashboard"></span> <?php echo $labels['tab_summary']; ?></a>
                </li>

                <!-- Shipping Dashboard Sections -->
                <li class="shipping-sidebar-item <?php echo $active_tab == 'general-stats' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'general-stats', 'sub' => 'active-shipments']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-chart-bar"></span> <?php echo $labels['tab_general_stats']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'general-stats' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'general-stats', 'sub' => 'active-shipments']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'active-shipments' ? 'shipping-sub-active' : ''; ?>">الشحنات النشطة</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'general-stats', 'sub' => 'delivered-shipments']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'delivered-shipments' ? 'shipping-sub-active' : ''; ?>">الشحنات المسلمة</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'general-stats', 'sub' => 'delayed-shipments']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'delayed-shipments' ? 'shipping-sub-active' : ''; ?>">الشحنات المتأخرة</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'general-stats', 'sub' => 'total-revenue']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'total-revenue' ? 'shipping-sub-active' : ''; ?>">إجمالي الإيرادات</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'general-stats', 'sub' => 'real-time-status']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'real-time-status' ? 'shipping-sub-active' : ''; ?>">حالة العمليات المباشرة</a></li>
                    </ul>
                </li>

                <li class="shipping-sidebar-item <?php echo $active_tab == 'shipment-mgmt' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'shipment-mgmt', 'sub' => 'create-shipment']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-products"></span> <?php echo $labels['tab_shipment_mgmt']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'shipment-mgmt' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'shipment-mgmt', 'sub' => 'create-shipment']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'create-shipment' ? 'shipping-sub-active' : ''; ?>">إنشاء شحنة جديدة</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'shipment-mgmt', 'sub' => 'tracking']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'tracking' ? 'shipping-sub-active' : ''; ?>">تتبع الشحنات</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'shipment-mgmt', 'sub' => 'monitoring']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'monitoring' ? 'shipping-sub-active' : ''; ?>">مراقبة حالة الشحن</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'shipment-mgmt', 'sub' => 'schedule']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'schedule' ? 'shipping-sub-active' : ''; ?>">إدارة جدول الشحن</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'shipment-mgmt', 'sub' => 'archiving']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'archiving' ? 'shipping-sub-active' : ''; ?>">أرشفة الشحنات</a></li>
                    </ul>
                </li>

                <li class="shipping-sidebar-item <?php echo $active_tab == 'customer-mgmt' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'customer-mgmt', 'sub' => 'profiles']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-groups"></span> <?php echo $labels['tab_customer_mgmt']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'customer-mgmt' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customer-mgmt', 'sub' => 'profiles']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'profiles' ? 'shipping-sub-active' : ''; ?>">ملفات العملاء</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customer-mgmt', 'sub' => 'history']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'history' ? 'shipping-sub-active' : ''; ?>">سجل الشحنات</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customer-mgmt', 'sub' => 'address-book']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'address-book' ? 'shipping-sub-active' : ''; ?>">دفتر العناوين</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customer-mgmt', 'sub' => 'contracts']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'contracts' ? 'shipping-sub-active' : ''; ?>">العقود والاتفاقيات</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customer-mgmt', 'sub' => 'classification']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'classification' ? 'shipping-sub-active' : ''; ?>">تصنيف العملاء</a></li>
                    </ul>
                </li>

                <li class="shipping-sidebar-item <?php echo $active_tab == 'order-mgmt' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'order-mgmt', 'sub' => 'new-orders']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-clipboard"></span> <?php echo $labels['tab_order_mgmt']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'order-mgmt' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'order-mgmt', 'sub' => 'new-orders']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'new-orders' ? 'shipping-sub-active' : ''; ?>">طلبات شحن جديدة</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'order-mgmt', 'sub' => 'in-progress']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'in-progress' ? 'shipping-sub-active' : ''; ?>">طلبات قيد التنفيذ</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'order-mgmt', 'sub' => 'completed']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'completed' ? 'shipping-sub-active' : ''; ?>">طلبات مكتملة</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'order-mgmt', 'sub' => 'cancelled']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'cancelled' ? 'shipping-sub-active' : ''; ?>">طلبات ملغاة</a></li>
                    </ul>
                </li>

                <li class="shipping-sidebar-item <?php echo $active_tab == 'tracking-logistics' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'tracking-logistics', 'sub' => 'live-tracking']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-location-alt"></span> <?php echo $labels['tab_tracking_logistics']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'tracking-logistics' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'tracking-logistics', 'sub' => 'live-tracking']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'live-tracking' ? 'shipping-sub-active' : ''; ?>">تتبع مباشر</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'tracking-logistics', 'sub' => 'routes']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'routes' ? 'shipping-sub-active' : ''; ?>">مسارات الشحن</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'tracking-logistics', 'sub' => 'stop-points']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'stop-points' ? 'shipping-sub-active' : ''; ?>">نقاط التوقف</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'tracking-logistics', 'sub' => 'warehouse']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'warehouse' ? 'shipping-sub-active' : ''; ?>">إدارة المستودعات</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'tracking-logistics', 'sub' => 'fleet']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'fleet' ? 'shipping-sub-active' : ''; ?>">إدارة الأسطول</a></li>
                    </ul>
                </li>

                <li class="shipping-sidebar-item <?php echo $active_tab == 'customs-clearance' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'customs-clearance', 'sub' => 'documentation']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-media-document"></span> <?php echo $labels['tab_customs_clearance']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'customs-clearance' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customs-clearance', 'sub' => 'documentation']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'documentation' ? 'shipping-sub-active' : ''; ?>">الوثائق والمستندات</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customs-clearance', 'sub' => 'invoices']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'invoices' ? 'shipping-sub-active' : ''; ?>">الفواتير التجارية</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customs-clearance', 'sub' => 'duties-taxes']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'duties-taxes' ? 'shipping-sub-active' : ''; ?>">الرسوم والضرائب</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'customs-clearance', 'sub' => 'status']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'status' ? 'shipping-sub-active' : ''; ?>">حالة التخليص</a></li>
                    </ul>
                </li>

                <li class="shipping-sidebar-item <?php echo $active_tab == 'billing-payments' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'billing-payments', 'sub' => 'invoice-gen']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-money-alt"></span> <?php echo $labels['tab_billing_payments']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'billing-payments' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'billing-payments', 'sub' => 'invoice-gen']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'invoice-gen' ? 'shipping-sub-active' : ''; ?>">إصدار الفواتير</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'billing-payments', 'sub' => 'records']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'records' ? 'shipping-sub-active' : ''; ?>">سجلات المدفوعات</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'billing-payments', 'sub' => 'balances']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'balances' ? 'shipping-sub-active' : ''; ?>">الأرصدة المستحقة</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'billing-payments', 'sub' => 'financial-reports']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'financial-reports' ? 'shipping-sub-active' : ''; ?>">التقارير المالية</a></li>
                    </ul>
                </li>

                <li class="shipping-sidebar-item <?php echo $active_tab == 'pricing-costs' ? 'shipping-active' : ''; ?>">
                    <a href="<?php echo add_query_arg(['shipping_tab' => 'pricing-costs', 'sub' => 'calculator']); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-calculator"></span> <?php echo $labels['tab_pricing_costs']; ?></a>
                    <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'pricing-costs' ? 'block' : 'none'; ?>;">
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'pricing-costs', 'sub' => 'calculator']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'calculator' ? 'shipping-sub-active' : ''; ?>">حاسبة الشحن</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'pricing-costs', 'sub' => 'transport-costs']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'transport-costs' ? 'shipping-sub-active' : ''; ?>">تكاليف النقل</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'pricing-costs', 'sub' => 'extra-charges']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'extra-charges' ? 'shipping-sub-active' : ''; ?>">رسوم إضافية</a></li>
                        <li><a href="<?php echo add_query_arg(['shipping_tab' => 'pricing-costs', 'sub' => 'special-offers']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'special-offers' ? 'shipping-sub-active' : ''; ?>">عروض خاصة</a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if ($is_restricted): ?>
                    <li class="shipping-sidebar-item <?php echo in_array($active_tab, ['my-profile', 'customer-profile']) ? 'shipping-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('shipping_tab', 'my-profile'); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-admin-users"></span> <?php echo $labels['tab_my_profile']; ?></a>
                    </li>
                <?php endif; ?>

                <?php if (!$is_restricted && ($is_admin || $is_sys_admin || $is_administrator)): ?>
                    <li class="shipping-sidebar-item <?php echo $active_tab == 'users-management' ? 'shipping-active' : ''; ?>">
                        <a href="<?php echo add_query_arg('shipping_tab', 'users-management'); ?>" class="shipping-sidebar-link"><span class="dashicons dashicons-admin-users"></span> <?php echo $labels['tab_users_management']; ?></a>
                    </li>
                <?php endif; ?>


                <?php if ($is_admin || $is_sys_admin || $is_administrator): ?>
                    <li class="shipping-sidebar-item <?php echo $active_tab == 'advanced-settings' ? 'shipping-active' : ''; ?>">
                        <a href="<?php echo add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'init']); ?>" class="shipping-sidebar-link" style="color: #c53030 !important;"><span class="dashicons dashicons-shield-alt"></span> الإعدادات المتقدمة</a>
                        <ul class="shipping-sidebar-dropdown" style="display: <?php echo $active_tab == 'advanced-settings' ? 'block' : 'none'; ?>;">
                            <li><a href="<?php echo add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'init']); ?>" class="<?php echo (!isset($_GET['sub']) || $_GET['sub'] == 'init') ? 'shipping-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-tools"></span> تهيئة النظام</a></li>
                            <li><a href="<?php echo add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'notifications']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'notifications' ? 'shipping-sub-active' : ''; ?>"><span class="dashicons dashicons-email"></span> التنبيهات والبريد</a></li>
                            <li><a href="<?php echo add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'design']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'design' ? 'shipping-sub-active' : ''; ?>"><span class="dashicons dashicons-art"></span> التصميم والمظهر</a></li>
                            <li><a href="<?php echo add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'pages']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'pages' ? 'shipping-sub-active' : ''; ?>"><span class="dashicons dashicons-admin-page"></span> تخصيص الصفحات</a></li>
                            <li><a href="<?php echo add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'alerts']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'alerts' ? 'shipping-sub-active' : ''; ?>"><span class="dashicons dashicons-megaphone"></span> تنبيهات النظام (System Alerts)</a></li>
                            <li><a href="<?php echo add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'backup']); ?>" class="<?php echo ($_GET['sub'] ?? '') == 'backup' ? 'shipping-sub-active' : ''; ?>"><span class="dashicons dashicons-database-export"></span> مركز النسخ الاحتياطي</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- CONTENT AREA -->
        <div class="shipping-main-panel" style="flex: 1; min-width: 0; padding: 40px; background: #fff;">

            <?php
            switch ($active_tab) {
                case 'general-stats':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-general-stats.php';
                    break;

                case 'shipment-mgmt':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-shipment-mgmt.php';
                    break;

                case 'customer-mgmt':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-customer-mgmt.php';
                    break;

                case 'order-mgmt':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-order-mgmt.php';
                    break;

                case 'tracking-logistics':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-tracking-logistics.php';
                    break;

                case 'customs-clearance':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-customs-clearance.php';
                    break;

                case 'billing-payments':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-billing-payments.php';
                    break;

                case 'pricing-costs':
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-pricing-costs.php';
                    break;

                case 'summary':
                    include SHIPPING_PLUGIN_DIR . 'templates/public-dashboard-summary.php';
                    break;

                case 'users-management':
                    if ($is_admin || current_user_can('manage_options')) {
                        include SHIPPING_PLUGIN_DIR . 'templates/admin-users-management.php';
                    }
                    break;

                case 'messaging':
                    include SHIPPING_PLUGIN_DIR . 'templates/messaging-center.php';
                    break;


                case 'customer-profile':
                case 'customer-profile':
                case 'my-profile':
                    if ($active_tab === 'my-profile') {
                        $customer_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", get_current_user_id()));
                        if ($customer_by_wp) $_GET['customer_id'] = $customer_by_wp->id;
                    }
                    include SHIPPING_PLUGIN_DIR . 'templates/admin-customer-profile.php';
                    break;



                case 'advanced-settings':
                    if ($is_admin || $is_sys_admin || $is_administrator) {
                        $sub = $_GET['sub'] ?? 'init';
                        ?>
                        <div class="shipping-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="shipping-tab-btn <?php echo $sub == 'init' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('shipping-settings', this)">تهيئة النظام</button>
                            <button class="shipping-tab-btn <?php echo $sub == 'notifications' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('notification-settings', this)">التنبيهات والبريد</button>
                            <button class="shipping-tab-btn <?php echo $sub == 'design' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('design-settings', this)">التصميم والمظهر</button>
                            <button class="shipping-tab-btn <?php echo $sub == 'pages' ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('page-customization', this)">تخصيص الصفحات</button>
                            <button class="shipping-tab-btn <?php echo ($sub == 'alerts') ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('system-alerts-settings', this)">تنبيهات النظام</button>
                            <button class="shipping-tab-btn <?php echo ($sub == 'backup') ? 'shipping-active' : ''; ?>" onclick="shippingOpenInternalTab('backup-settings', this)">مركز النسخ الاحتياطي</button>
                        </div>

                        <div id="shipping-settings" class="shipping-internal-tab" style="display: <?php echo ($sub == 'init') ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('shipping_admin_action', 'shipping_admin_nonce'); ?>

                                <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: var(--shipping-shadow);">
                                    <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--shipping-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-groups"></span> بيانات Shipping (Union Data)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                                        <div class="shipping-form-group"><label class="shipping-label">اسم Shipping كاملاً:</label><input type="text" name="shipping_name" value="<?php echo esc_attr($shipping['shipping_name']); ?>" class="shipping-input"></div>
                                        <div class="shipping-form-group"><label class="shipping-label">اسم رئيس Shipping / المسؤول:</label><input type="text" name="shipping_officer_name" value="<?php echo esc_attr($shipping['shipping_officer_name'] ?? ''); ?>" class="shipping-input"></div>
                                        <div class="shipping-form-group"><label class="shipping-label">رقم التواصل الموحد:</label><input type="text" name="shipping_phone" value="<?php echo esc_attr($shipping['phone']); ?>" class="shipping-input"></div>
                                        <div class="shipping-form-group"><label class="shipping-label">البريد الإلكتروني الرسمي:</label><input type="email" name="shipping_email" value="<?php echo esc_attr($shipping['email']); ?>" class="shipping-input"></div>
                                        <div class="shipping-form-group"><label class="shipping-label">العنوان الجغرافي للمقر الرئيسي:</label><input type="text" name="shipping_address" value="<?php echo esc_attr($shipping['address']); ?>" class="shipping-input"></div>
                                        <div class="shipping-form-group"><label class="shipping-label">رابط خرائط جوجل (Map Link):</label><input type="url" name="shipping_map_link" value="<?php echo esc_attr($shipping['map_link'] ?? ''); ?>" class="shipping-input" placeholder="https://goo.gl/maps/..."></div>
                                        <div class="shipping-form-group" style="grid-column: span 2;"><label class="shipping-label">تفاصيل إضافية / نبذة عن Shipping:</label><textarea name="shipping_extra_details" class="shipping-textarea" rows="3"><?php echo esc_textarea($shipping['extra_details'] ?? ''); ?></textarea></div>
                                        <div class="shipping-form-group" style="grid-column: span 2;">
                                            <label class="shipping-label">شعار Shipping الرسمي (Official Logo):</label>
                                            <div style="display:flex; gap:10px;">
                                                <input type="text" name="shipping_logo" id="shipping_logo_url" value="<?php echo esc_attr($shipping['shipping_logo']); ?>" class="shipping-input">
                                                <button type="button" onclick="shippingOpenMediaUploader('shipping_logo_url')" class="shipping-btn" style="width:auto; font-size:12px; background:#4a5568;">اختيار الشعار</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                                    <h4 style="margin-top:0; border-bottom:2px solid #cbd5e0; padding-bottom:12px; color: var(--shipping-dark-color); display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-admin-settings"></span> مسميات أقسام النظام (Section Labels)
                                    </h4>
                                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; margin-top:15px;">
                                        <?php foreach($labels as $key => $val): ?>
                                            <div class="shipping-form-group">
                                                <label class="shipping-label" style="font-size:11px;"><?php echo str_replace('tab_', '', $key); ?>:</label>
                                                <input type="text" name="<?php echo $key; ?>" value="<?php echo esc_attr($val); ?>" class="shipping-input" style="padding:10px; font-size:13px; border-color: #cbd5e0;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div style="position: sticky; bottom: 0; background: rgba(255,255,255,0.9); padding: 15px 0; border-top: 1px solid #eee; z-index: 10;">
                                    <button type="submit" name="shipping_save_settings_unified" class="shipping-btn" style="width:auto; height:50px; padding: 0 50px; font-size: 1.1em; font-weight: 800; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">حفظ كافة الإعدادات والتهيئة</button>
                                </div>
                            </form>
                        </div>

                        <div id="notification-settings" class="shipping-internal-tab" style="display: <?php echo ($sub == 'notifications') ? 'block' : 'none'; ?>;">
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;">
                                <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--shipping-dark-color);">إدارة قوالب التنبيهات والبريد الإلكتروني</h4>
                                <?php
                                $notif_templates = [
                                    'customership_renewal' => 'تذكير تجديد الحساب',
                                    'welcome_activation' => 'رسالة الترحيب بالتفعيل',
                                    'admin_alert' => 'تنبيه إداري عام'
                                ];
                                ?>
                                <div style="display:grid; grid-template-columns: 250px 1fr; gap:30px; margin-top:20px;">
                                    <div style="border-left:1px solid #eee; padding-left:20px;">
                                        <?php foreach($notif_templates as $type => $label): ?>
                                            <div onclick="shippingLoadNotifTemplate('<?php echo $type; ?>')" style="padding:12px; border-radius:8px; cursor:pointer; margin-bottom:10px; background:#f8fafc; border:1px solid #e2e8f0; font-size:13px; font-weight:600;">
                                                <?php echo $label; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="notif-template-editor" style="display:none;">
                                        <form id="shipping-notif-template-form">
                                            <input type="hidden" name="template_type" id="tmpl_type">
                                            <div class="shipping-form-group">
                                                <label class="shipping-label">عنوان الرسالة (Subject):</label>
                                                <input type="text" name="subject" id="tmpl_subject" class="shipping-input">
                                            </div>
                                            <div class="shipping-form-group">
                                                <label class="shipping-label">محتوى الرسالة (Body):</label>
                                                <textarea name="body" id="tmpl_body" class="shipping-textarea" rows="8"></textarea>
                                                <small style="color:#718096;">الوسوم المتاحة: {customer_name}, {id_number}, {username}, {year}</small>
                                            </div>
                                            <div style="display:flex; align-items:center; gap:15px;">
                                                <div class="shipping-form-group" style="flex:1;">
                                                    <label class="shipping-label">تنبيه قبل (يوم):</label>
                                                    <input type="number" name="days_before" id="tmpl_days" class="shipping-input">
                                                </div>
                                                <div style="flex:1;">
                                                    <label><input type="checkbox" name="is_enabled" id="tmpl_enabled"> تفعيل هذا القالب</label>
                                                </div>
                                            </div>
                                            <button type="submit" class="shipping-btn" style="margin-top:20px;">حفظ القالب</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="page-customization" class="shipping-internal-tab" style="display: <?php echo $sub == 'pages' ? 'block' : 'none'; ?>;">
                            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px;">
                                <h4 style="margin-top:0; border-bottom:2px solid #f1f5f9; padding-bottom:12px; color: var(--shipping-dark-color);">إدارة صفحات النظام والوسوم (Shortcodes)</h4>

                                <div class="shipping-table-container">
                                    <table class="shipping-table">
                                        <thead>
                                            <tr>
                                                <th>اسم الصفحة</th>
                                                <th>الوسم (Shortcode)</th>
                                                <th>الرابط</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach(Shipping_DB::get_pages() as $p): ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html($p->title); ?></strong></td>
                                                    <td><code>[<?php echo $p->shortcode; ?>]</code></td>
                                                    <td><a href="<?php echo home_url('/' . $p->slug); ?>" target="_blank">معاينة</a></td>
                                                    <td>
                                                        <button onclick='shippingEditPageSettings(<?php echo json_encode($p); ?>)' class="shipping-btn shipping-btn-outline" style="padding: 5px 10px; font-size: 11px;">تعديل التصميم</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h4 style="margin: 0;">إدارة الأخبار والمقالات (Blog)</h4>
                                    <button onclick="shippingOpenAddArticleModal()" class="shipping-btn" style="width: auto;">+ إضافة مقال جديد</button>
                                </div>

                                <div class="shipping-table-container">
                                    <table class="shipping-table">
                                        <thead>
                                            <tr>
                                                <th>عنوان المقال</th>
                                                <th>التاريخ</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $articles = Shipping_DB::get_articles(50);
                                            if (empty($articles)): ?>
                                                <tr><td colspan="3" style="text-align:center; padding:20px;">لا توجد مقالات حالياً.</td></tr>
                                            <?php else: foreach($articles as $art): ?>
                                                <tr>
                                                    <td><?php echo esc_html($art->title); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($art->created_at)); ?></td>
                                                    <td>
                                                        <button onclick="shippingDeleteArticle(<?php echo $art->id; ?>)" class="shipping-btn" style="background: #e53e3e; padding: 5px 10px; font-size: 11px;">حذف</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="design-settings" class="shipping-internal-tab" style="display: <?php echo $sub == 'design' ? 'block' : 'none'; ?>;">
                            <form method="post">
                                <?php wp_nonce_field('shipping_admin_action', 'shipping_admin_nonce'); ?>
                                <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">إعدادات الألوان والمظهر الشاملة</h4>
                                <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:20px;">
                                    <div class="shipping-form-group"><label class="shipping-label">الأساسي:</label><input type="color" name="primary_color" value="<?php echo esc_attr($appearance['primary_color']); ?>" class="shipping-input" style="height:40px;"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">الثانوي:</label><input type="color" name="secondary_color" value="<?php echo esc_attr($appearance['secondary_color']); ?>" class="shipping-input" style="height:40px;"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">التمييز:</label><input type="color" name="accent_color" value="<?php echo esc_attr($appearance['accent_color']); ?>" class="shipping-input" style="height:40px;"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">الهيدر:</label><input type="color" name="dark_color" value="<?php echo esc_attr($appearance['dark_color']); ?>" class="shipping-input" style="height:40px;"></div>

                                    <div class="shipping-form-group"><label class="shipping-label">خلفية النظام:</label><input type="color" name="bg_color" value="<?php echo esc_attr($appearance['bg_color']); ?>" class="shipping-input" style="height:40px;"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">خلفية السايدبار:</label><input type="color" name="sidebar_bg_color" value="<?php echo esc_attr($appearance['sidebar_bg_color']); ?>" class="shipping-input" style="height:40px;"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">لون الخط:</label><input type="color" name="font_color" value="<?php echo esc_attr($appearance['font_color']); ?>" class="shipping-input" style="height:40px;"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">لون الحدود:</label><input type="color" name="border_color" value="<?php echo esc_attr($appearance['border_color']); ?>" class="shipping-input" style="height:40px;"></div>
                                </div>

                                <h4 style="margin-top:30px; border-bottom:1px solid #eee; padding-bottom:10px;">الخطوط والخطوط المطبعية (Typography)</h4>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-top:20px;">
                                    <div class="shipping-form-group"><label class="shipping-label">حجم الخط (مثال: 15px):</label><input type="text" name="font_size" value="<?php echo esc_attr($appearance['font_size']); ?>" class="shipping-input"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">وزن الخط (400, 700...):</label><input type="text" name="font_weight" value="<?php echo esc_attr($appearance['font_weight']); ?>" class="shipping-input"></div>
                                    <div class="shipping-form-group"><label class="shipping-label">تباعد الأسطر (1.5...):</label><input type="text" name="line_spacing" value="<?php echo esc_attr($appearance['line_spacing']); ?>" class="shipping-input"></div>
                                </div>

                                <button type="submit" name="shipping_save_appearance" class="shipping-btn" style="width:auto; margin-top:20px;">حفظ كافة تعديلات التصميم</button>
                            </form>
                        </div>

                        <div id="system-alerts-settings" class="shipping-internal-tab" style="display: <?php echo ($sub == 'alerts') ? 'block' : 'none'; ?>;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin-bottom:20px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                    <h4 style="margin:0;">إدارة تنبيهات النظام الشاملة</h4>
                                    <button onclick="shippingOpenAddAlertModal()" class="shipping-btn" style="width:auto; padding:8px 20px;">+ إنشاء تنبيه جديد</button>
                                </div>

                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:20px;">
                                    <button onclick="shippingApplyAlertTemplate('payment')" class="shipping-btn shipping-btn-outline" style="font-size:12px;">قالب: تذكير بالسداد</button>
                                    <button onclick="shippingApplyAlertTemplate('expiry')" class="shipping-btn shipping-btn-outline" style="font-size:12px;">قالب: تنبيه انتهاء الحساب</button>
                                    <button onclick="shippingApplyAlertTemplate('maintenance')" class="shipping-btn shipping-btn-outline" style="font-size:12px;">قالب: صيانة النظام</button>
                                    <button onclick="shippingApplyAlertTemplate('docs')" class="shipping-btn shipping-btn-outline" style="font-size:12px;">قالب: تذكير الوثائق</button>
                                    <button onclick="shippingApplyAlertTemplate('urgent')" class="shipping-btn shipping-btn-outline" style="font-size:12px;">قالب: قرار إداري عاجل</button>
                                </div>

                                <div class="shipping-table-container" style="margin:0;">
                                    <table class="shipping-table">
                                        <thead>
                                            <tr>
                                                <th>العنوان</th>
                                                <th>المستوى</th>
                                                <th>الإقرار</th>
                                                <th>الحالة</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $alerts = Shipping_DB::get_alerts();
                                            if (empty($alerts)): ?>
                                                <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">لا توجد تنبيهات نشطة حالياً.</td></tr>
                                            <?php else: foreach($alerts as $al):
                                                $severity_map = ['info' => 'عادي (White)', 'warning' => 'تحذير (Orange)', 'critical' => 'هام جداً (Red)'];
                                                $severity_color = ['info' => '#64748b', 'warning' => '#f59e0b', 'critical' => '#e53e3e'];
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo esc_html($al->title); ?></strong></td>
                                                    <td><span style="color:<?php echo $severity_color[$al->severity]; ?>; font-weight:700;"><?php echo $severity_map[$al->severity]; ?></span></td>
                                                    <td><?php echo $al->must_acknowledge ? '✅ نعم' : '❌ لا'; ?></td>
                                                    <td>
                                                        <span class="shipping-badge <?php echo $al->status == 'active' ? 'shipping-badge-high' : 'shipping-badge-low'; ?>">
                                                            <?php echo $al->status == 'active' ? 'نشط' : 'معطل'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div style="display:flex; gap:5px;">
                                                            <button onclick='shippingEditAlert(<?php echo json_encode($al); ?>)' class="shipping-btn shipping-btn-outline" style="padding:4px 10px; font-size:11px;">تعديل</button>
                                                            <button onclick="shippingDeleteAlert(<?php echo $al->id; ?>)" class="shipping-btn" style="background:#e53e3e; padding:4px 10px; font-size:11px;">حذف</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                        <div id="backup-settings" class="shipping-internal-tab" style="display: <?php echo ($sub == 'backup') ? 'block' : 'none'; ?>;">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <h4 style="margin-top:0;">مركز النسخ الاحتياطي وإدارة البيانات</h4>
                                <?php $backup_info = Shipping_Settings::get_last_backup_info(); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر تصدير ناجح:</div>
                                        <div style="font-weight:700; color:var(--shipping-primary-color);"><?php echo $backup_info['export']; ?></div>
                                    </div>
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر استيراد ناجح:</div>
                                        <div style="font-weight:700; color:var(--shipping-secondary-color);"><?php echo $backup_info['import']; ?></div>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير البيانات الشاملة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">قم بتحميل نسخة كاملة من بيانات العملاء بصيغة JSON.</p>
                                        <div style="display:flex; gap:10px;">
                                            <form method="post">
                                                <?php wp_nonce_field('shipping_admin_action', 'shipping_admin_nonce'); ?>
                                                <button type="submit" name="shipping_download_backup" class="shipping-btn" style="background:#27ae60; width:auto;">تصدير الآن (JSON)</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">استيراد البيانات</h5>
                                        <p style="font-size:12px; color:#e53e3e; margin-bottom:15px;">تحذير: سيقوم الاستيراد بمسح البيانات الحالية واستبدالها بالنسخة المرفوعة.</p>
                                        <form method="post" enctype="multipart/form-data">
                                            <?php wp_nonce_field('shipping_admin_action', 'shipping_admin_nonce'); ?>
                                            <input type="file" name="backup_file" required style="margin-bottom:10px; font-size:11px;">
                                            <button type="submit" name="shipping_restore_backup" class="shipping-btn" style="background:#2980b9; width:auto;">بدء الاستيراد</button>
                                        </form>
                                    </div>


                                    <div style="background:#fff5f5; padding:20px; border-radius:8px; border:1px solid #feb2b2; grid-column: 1 / -1;">
                                        <h5 style="margin-top:0; color:#c53030;">منطقة الخطر: إعادة تهيئة النظام</h5>
                                        <p style="font-size:12px; color:#c53030; margin-bottom:15px;">سيقوم هذا الإجراء بمسح كافة بيانات العملاء، الحسابات، والنشاطات بشكل نهائي ولا يمكن التراجع عنه.</p>
                                        <button onclick="shippingResetSystem()" class="shipping-btn" style="background:#e53e3e; width:auto; font-weight:800;">إعادة تهيئة النظام بالكامل (Reset)</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    break;


            }
            ?>

        </div>
    </div>
</div>

<!-- Alert Management Modal -->
<div id="shipping-alert-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 600px;">
        <div class="shipping-modal-header"><h3><span id="shipping-alert-modal-title">إنشاء تنبيه جديد</span></h3><button class="shipping-modal-close" onclick="document.getElementById('shipping-alert-modal').style.display='none'">&times;</button></div>
        <form id="shipping-alert-form" style="padding: 20px;">
            <input type="hidden" name="id" id="edit-alert-id">
            <div class="shipping-form-group"><label class="shipping-label">عنوان التنبيه:</label><input type="text" name="title" class="shipping-input" required></div>
            <div class="shipping-form-group"><label class="shipping-label">نص الرسالة:</label><textarea name="message" class="shipping-textarea" rows="4" required></textarea></div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="shipping-form-group">
                    <label class="shipping-label">مستوى الخطورة:</label>
                    <select name="severity" class="shipping-select">
                        <option value="info">عادي (White)</option>
                        <option value="warning">تحذير (Orange)</option>
                        <option value="critical">هام (Red)</option>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label class="shipping-label">الحالة:</label>
                    <select name="status" class="shipping-select">
                        <option value="active">نشط</option>
                        <option value="inactive">معطل</option>
                    </select>
                </div>
            </div>
            <div class="shipping-form-group">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="must_acknowledge" value="1"> يتطلب إقرار بالاستلام من العميل قبل الإغلاق
                </label>
            </div>
            <button type="submit" class="shipping-btn" style="width: 100%; margin-top:10px;">حفظ ونشر التنبيه</button>
        </form>
    </div>
</div>

<!-- Page Edit Modal -->
<div id="shipping-edit-page-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content">
        <div class="shipping-modal-header"><h3>تعديل الصفحة: <span id="page-edit-name"></span></h3><button class="shipping-modal-close" onclick="document.getElementById('shipping-edit-page-modal').style.display='none'">&times;</button></div>
        <form id="shipping-edit-page-form" style="padding: 20px;">
            <input type="hidden" name="id" id="edit-page-id">
            <div class="shipping-form-group"><label class="shipping-label">عنوان الصفحة (يظهر في الهيدر):</label><input type="text" name="title" id="edit-page-title" class="shipping-input" required></div>
            <div class="shipping-form-group"><label class="shipping-label">معلومات/تعليمات الصفحة:</label><textarea name="instructions" id="edit-page-instructions" class="shipping-textarea" rows="4"></textarea></div>
            <button type="submit" class="shipping-btn" style="width: 100%;">حفظ التعديلات</button>
        </form>
    </div>
</div>

<!-- Add Article Modal -->
<div id="shipping-add-article-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content">
        <div class="shipping-modal-header"><h3>إضافة مقال/خبر جديد</h3><button class="shipping-modal-close" onclick="document.getElementById('shipping-add-article-modal').style.display='none'">&times;</button></div>
        <form id="shipping-add-article-form" style="padding: 20px;">
            <div class="shipping-form-group"><label class="shipping-label">عنوان المقال:</label><input type="text" name="title" class="shipping-input" required></div>
            <div class="shipping-form-group"><label class="shipping-label">رابط صورة المقال:</label><input type="text" name="image_url" class="shipping-input"></div>
            <div class="shipping-form-group"><label class="shipping-label">المحتوى:</label><textarea name="content" class="shipping-textarea" rows="6" required></textarea></div>
            <button type="submit" class="shipping-btn" style="width: 100%;">نشر المقال</button>
        </form>
    </div>
</div>

<!-- Global Detailed Finance Modal -->
<div id="log-details-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 700px;">
        <div class="shipping-modal-header">
            <h3>تفاصيل العملية المسجلة</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('log-details-modal').style.display='none'">&times;</button>
        </div>
        <div id="log-details-body" style="padding: 20px;"></div>
    </div>
</div>


<style>
.shipping-sidebar-item { border-bottom: 1px solid rgba(0,0,0,0.05); transition: 0.2s; position: relative; }
.shipping-sidebar-link {
    padding: 15px 25px;
    cursor: pointer; font-weight: 600; color: #4a5568 !important;
    display: flex; align-items: center; gap: 12px;
    text-decoration: none !important;
    width: 100%;
}
.shipping-sidebar-item:hover { background: rgba(0,0,0,0.02); }
.shipping-sidebar-item.shipping-active {
    background: rgba(0,0,0,0.02) !important;
}
.shipping-sidebar-item.shipping-active > .shipping-sidebar-link {
    color: var(--shipping-primary-color) !important;
    font-weight: 700;
}

.shipping-sidebar-badge {
    position: absolute; left: 15px; top: 15px;
    background: #e53e3e; color: white; border-radius: 20px; padding: 2px 8px; font-size: 10px; font-weight: 800;
}

.shipping-sidebar-dropdown {
    list-style: none; padding: 0; margin: 0; background: rgba(0,0,0,0.04); display: none;
}
.shipping-sidebar-dropdown li a {
    display: flex; align-items: center; gap: 12px; padding: 10px 25px;
    font-size: 13px; color: #4a5568 !important; text-decoration: none !important;
    transition: 0.2s;
}
.shipping-sidebar-dropdown li a:hover {
    background: rgba(255,255,255,0.3);
}
.shipping-sidebar-dropdown li a.shipping-sub-active {
    background: var(--shipping-dark-color) !important; color: #fff !important; font-weight: 600;
}
.shipping-sidebar-dropdown li a .dashicons { font-size: 16px; width: 16px; height: 16px; }

.shipping-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--shipping-dark-color) !important;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}
.shipping-dropdown-item:hover { background: var(--shipping-bg-light); color: var(--shipping-primary-color) !important; }

@keyframes shippingFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FORCE VISIBILITY FOR PANELS */
.shipping-admin-dashboard .shipping-main-tab-panel {
    width: 100% !important;
}
.shipping-tab-btn { padding: 10px 20px; border: 1px solid #e2e8f0; background: #f8f9fa; cursor: pointer; border-radius: 5px 5px 0 0; }
.shipping-tab-btn.shipping-active { background: var(--shipping-primary-color) !important; color: #fff !important; border-bottom: none; }
.shipping-quick-btn { background: #48bb78 !important; color: white !important; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-block; }
.shipping-refresh-btn { background: #718096; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.shipping-logout-btn { background: #e53e3e; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; font-weight: 700; display: inline-block; }

.shipping-header-circle-icon {
    width: 40px; height: 40px; background: #ffffff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--shipping-dark-color); text-decoration: none !important; position: relative;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
    transition: 0.3s;
}
.shipping-header-circle-icon:hover { background: #edf2f7; color: var(--shipping-primary-color); }
.shipping-header-circle-icon .dashicons { font-size: 20px; width: 20px; height: 20px; }

.shipping-admin-dashboard .shipping-btn { background-color: <?php echo $appearance['btn_color']; ?>; }
.shipping-admin-dashboard .shipping-table th { border-color: <?php echo $appearance['border_color']; ?>; }
.shipping-admin-dashboard .shipping-input, .shipping-admin-dashboard .shipping-select, .shipping-admin-dashboard .shipping-textarea { border-color: <?php echo $appearance['border_color']; ?>; }

.shipping-icon-badge {
    position: absolute; top: -5px; right: -5px; color: white; border-radius: 50%;
    width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center;
    justify-content: center; font-weight: 800; border: 2px solid white;
}
.shipping-icon-dot {
    position: absolute; top: 0; right: 0; width: 10px; height: 10px;
    border-radius: 50%; border: 2px solid white;
}

@media (max-width: 992px) {
    .shipping-hide-mobile { display: none; }
}
</style>
