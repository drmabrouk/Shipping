<?php if (!defined('ABSPATH')) exit; ?>
<?php
$my_id = get_current_user_id();
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles);
$is_officer = in_array('administrator', $roles);
$is_customer = in_array('subscriber', $roles);
$is_official = $is_admin || $is_officer;

// Get customer data if applicable
$customer_id = 0;
global $wpdb;
$customer = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $my_id));
if ($customer) {
    $customer_id = $customer->id;
}

$categories = array(
    'inquiry' => array('label' => 'استفسار عام', 'color' => '#EBF8FF', 'text' => '#3182CE'),
    'finance' => array('label' => 'مشكلة مالية', 'color' => '#FEF3C7', 'text' => '#B45309'),
    'technical' => array('label' => 'دعم فني', 'color' => '#F0FDF4', 'text' => '#15803D'),
    'customership' => array('label' => 'تجديد حساب', 'color' => '#F5F3FF', 'text' => '#6D28D9'),
    'other' => array('label' => 'أخرى', 'color' => '#F1F5F9', 'text' => '#475569')
);

$statuses = array(
    'open' => array('label' => 'مفتوح', 'class' => 'shipping-badge-high'),
    'in-progress' => array('label' => 'قيد التنفيذ', 'class' => 'shipping-badge-mid'),
    'closed' => array('label' => 'مغلق', 'class' => 'shipping-badge-low')
);

$priorities = array(
    'low' => 'منخفض',
    'medium' => 'متوسط',
    'high' => 'عاجل'
);
?>

<div class="shipping-tickets-wrapper" dir="rtl" style="min-height: 700px; font-family: 'Rubik', sans-serif;">

    <!-- Top Filter Bar -->
    <div class="shipping-tickets-top-bar" style="background: #fff; border-radius: 15px; border: 1px solid var(--shipping-border-color); padding: 20px 25px; box-shadow: var(--shipping-shadow); margin-bottom: 25px;">
        <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
            <h2 style="margin: 0; font-weight: 800; color: var(--shipping-dark-color); font-size: 1.2em; flex: 1; min-width: 200px;">نظام التذاكر والدعم</h2>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <select id="filter-status" class="shipping-select" onchange="shippingLoadTickets()" style="width: 120px; height: 40px; padding: 0 10px;">
                    <option value="">كل الحالات</option>
                    <?php foreach($statuses as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                </select>

                <select id="filter-category" class="shipping-select" onchange="shippingLoadTickets()" style="width: 130px; height: 40px; padding: 0 10px;">
                    <option value="">كل الأقسام</option>
                    <?php foreach($categories as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                </select>

                <select id="filter-priority" class="shipping-select" onchange="shippingLoadTickets()" style="width: 110px; height: 40px; padding: 0 10px;">
                    <option value="">كل الأولويات</option>
                    <?php foreach($priorities as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                </select>


                <div style="position: relative;">
                    <input type="text" id="filter-search" class="shipping-input" placeholder="بحث..." oninput="shippingLoadTickets()" style="width: 180px; height: 40px; padding-left: 30px;">
                    <span class="dashicons dashicons-search" style="position: absolute; left: 8px; top: 10px; color: #94a3b8; font-size: 18px;"></span>
                </div>

                <?php if ($is_customer): ?>
                    <button onclick="shippingOpenCreateTicketModal()" class="shipping-btn" style="height: 40px; padding: 0 15px; font-weight: 700;">+ تذكرة</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="shipping-tickets-main">
        <div id="tickets-list-container">
            <div id="shipping-tickets-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <!-- Loaded via JS -->
            </div>
        </div>

        <div id="ticket-details-container" style="display: none; animation: shippingFadeIn 0.3s ease-out;">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<!-- Modal: Create Ticket -->
<div id="create-ticket-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 600px;">
        <div class="shipping-modal-header">
            <h3>فتح تذكرة دعم جديدة</h3>
            <button class="shipping-modal-close" onclick="document.getElementById('create-ticket-modal').style.display='none'">&times;</button>
        </div>
        <form id="create-ticket-form" style="padding: 20px;">
            <div class="shipping-form-group">
                <label class="shipping-label">موضوع التذكرة:</label>
                <input type="text" name="subject" class="shipping-input" required placeholder="مثال: مشكلة في تحديث البيانات">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="shipping-form-group">
                    <label class="shipping-label">القسم:</label>
                    <select name="category" class="shipping-select" required>
                        <?php foreach($categories as $k => $v) echo "<option value='$k'>{$v['label']}</option>"; ?>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label class="shipping-label">الأولوية:</label>
                    <select name="priority" class="shipping-select">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية / عاجل</option>
                    </select>
                </div>
            </div>
            <div class="shipping-form-group">
                <label class="shipping-label">تفاصيل المشكلة / الطلب:</label>
                <textarea name="message" class="shipping-textarea" rows="5" required placeholder="يرجى شرح طلبك بالتفصيل..."></textarea>
            </div>
            <div class="shipping-form-group">
                <label class="shipping-label">مرفقات (اختياري):</label>
                <input type="file" name="attachment" class="shipping-input">
                <p style="font-size: 11px; color: #64748b; margin-top: 5px;">يسمح بملفات الصور و PDF (بحد أقصى 5 ميجابايت)</p>
            </div>
            <button type="submit" class="shipping-btn" style="width: 100%; height: 45px; font-weight: 700; margin-top: 10px;">إرسال التذكرة</button>
        </form>
    </div>
</div>

<script>
(function($) {
    let currentActiveTicketId = null;
    let autoRefreshInterval = null;

    const categories = <?php echo json_encode($categories); ?>;
    const statuses = <?php echo json_encode($statuses); ?>;
    const priorities = <?php echo json_encode($priorities); ?>;
    const isOfficial = <?php echo $is_official ? 'true' : 'false'; ?>;
    const currentUserId = <?php echo $my_id; ?>;

    window.shippingOpenCreateTicketModal = function() {
        $('#create-ticket-form')[0].reset();
        $('#create-ticket-modal').fadeIn().css('display', 'flex');
    };

    window.shippingLoadTickets = function(showLoader = true) {
        const grid = $('#shipping-tickets-grid');
        if (showLoader) grid.css('opacity', '0.5');

        const status = $('#filter-status').val();
        const category = $('#filter-category').val();
        const priority = $('#filter-priority').val();
        const search = $('#filter-search').val();
        const nonce = '<?php echo wp_create_nonce("shipping_ticket_action"); ?>';

        fetch(ajaxurl + `?action=shipping_get_tickets&status=${status}&category=${category}&priority=${priority}&search=${search}&nonce=${nonce}&t=${Date.now()}`)
        .then(r => r.json())
        .then(res => {
            grid.css('opacity', '1').empty();
            if (res.success && res.data.length > 0) {
                res.data.forEach(t => {
                    const cat = categories[t.category] || categories['other'];
                    const stat = statuses[t.status];
                    const priorityLabel = priorities[t.priority];

                    const card = $(`
                        <div class="shipping-ticket-card" onclick="shippingViewTicket(${t.id})" style="background: #fff; border: 1px solid var(--shipping-border-color); border-radius: 12px; padding: 20px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 20px;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; border: 1px solid #e2e8f0;">
                                ${t.customer_photo ? `<img src="${t.customer_photo}" style="width: 100%; height: 100%; object-fit: cover;">` : `<span class="dashicons dashicons-admin-users" style="color: #94a3b8;"></span>`}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                    <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">#${t.id}</span>
                                    <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--shipping-dark-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${t.subject}</h4>
                                    <span style="background: ${cat.color}; color: ${cat.text}; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;">${cat.label}</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 15px; font-size: 12px; color: #64748b;">
                                    <span style="font-weight: 600;">${t.customer_name}</span>
                                    <span>•</span>
                                    <span>${t.updated_at}</span>
                                </div>
                            </div>
                            <div style="text-align: left; flex-shrink: 0;">
                                <div class="shipping-badge ${stat.class}" style="margin-bottom: 5px;">${stat.label}</div>
                                <div style="font-size: 10px; color: ${t.priority === 'high' ? '#e53e3e' : '#94a3b8'}; font-weight: 700;">الأولوية: ${priorityLabel}</div>
                            </div>
                        </div>
                    `);
                    grid.append(card);
                });
            } else {
                grid.html('<div style="text-align: center; padding: 50px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e0; color: #94a3b8;">لا توجد تذاكر حالياً تتطابق مع البحث.</div>');
            }
        });
    };

    window.shippingViewTicket = function(id, silent = false) {
        currentActiveTicketId = id;
        if (!silent) {
            $('#tickets-list-container').hide();
            $('#ticket-details-container').show().html('<div style="text-align: center; padding: 100px;"><div class="shipping-loader-mini"></div></div>');
        }
        const nonce = '<?php echo wp_create_nonce("shipping_ticket_action"); ?>';

        fetch(ajaxurl + `?action=shipping_get_ticket_details&id=${id}&nonce=${nonce}&t=${Date.now()}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const t = res.data.ticket;
                const thread = res.data.thread;

                if (silent) {
                    const threadHtml = renderThreadHtml(thread);
                    const oldHtml = $('#ticket-thread-body').html();
                    if (threadHtml.trim() !== oldHtml.trim()) {
                        $('#ticket-thread-body').html(threadHtml);
                        const threadBody = $('#ticket-thread-body');
                        threadBody.scrollTop(threadBody[0].scrollHeight);
                    }
                    return;
                }
                const cat = categories[t.category] || categories['other'];
                const stat = statuses[t.status];

                const threadHtml = renderThreadHtml(thread);

                const container = $('#ticket-details-container');
                container.html(`
                    <div style="background: #fff; border-radius: 15px; border: 1px solid var(--shipping-border-color); overflow: hidden; box-shadow: var(--shipping-shadow);">
                        <div style="padding: 20px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <button onclick="shippingBackToList()" class="shipping-btn shipping-btn-outline" style="width: auto; padding: 5px 10px;"><span class="dashicons dashicons-arrow-right-alt2"></span> العودة</button>
                                <div>
                                    <h3 style="margin: 0; font-weight: 800; color: var(--shipping-dark-color);">${t.subject}</h3>
                                    <div style="display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; margin-top: 5px;">
                                        <span>تذكرة رقم: #${t.id}</span>
                                        <span>•</span>
                                        <span style="background: ${cat.color}; color: ${cat.text}; padding: 1px 8px; border-radius: 10px; font-weight: 700;">${cat.label}</span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <span class="shipping-badge ${stat.class}">${stat.label}</span>
                                ${isOfficial && t.status !== 'closed' ? `<button onclick="shippingCloseTicket(${t.id})" class="shipping-btn" style="background: #e53e3e; width: auto; padding: 5px 15px; font-size: 12px;">إغلاق التذكرة</button>` : ''}
                            </div>
                        </div>

                        <div style="padding: 30px; background: #f8fafc; max-height: 500px; overflow-y: auto;" id="ticket-thread-body">
                            ${threadHtml}
                        </div>

                        ${t.status !== 'closed' ? `
                            <div style="padding: 25px 30px; border-top: 1px solid #f1f5f9;">
                                <form id="ticket-reply-form" style="display: flex; flex-direction: column; gap: 15px;">
                                    <input type="hidden" name="ticket_id" value="${t.id}">
                                    <textarea name="message" class="shipping-textarea" rows="3" required placeholder="اكتب ردك هنا..."></textarea>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <input type="file" name="attachment" style="font-size: 12px;">
                                        <button type="submit" class="shipping-btn" style="width: auto; padding: 0 30px; height: 40px;">إرسال الرد</button>
                                    </div>
                                </form>
                            </div>
                        ` : `
                            <div style="padding: 20px; text-align: center; background: #fff5f5; color: #c53030; font-weight: 700; font-size: 14px;">هذه التذكرة مغلقة. لا يمكنك إضافة ردود جديدة.</div>
                        `}
                    </div>

                    <div style="margin-top: 20px; background: #fff; border-radius: 15px; border: 1px solid var(--shipping-border-color); padding: 20px; box-shadow: var(--shipping-shadow);">
                        <h4 style="margin: 0 0 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">بيانات مقدم الطلب</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; font-size: 13px;">
                            <div><label style="color: #94a3b8; display: block;">الاسم:</label><strong>${t.customer_name}</strong></div>
                            <div><label style="color: #94a3b8; display: block;">رقم الهاتف:</label><strong>${t.customer_phone}</strong></div>
                            <div><label style="color: #94a3b8; display: block;">تاريخ الفتح:</label><strong>${t.created_at}</strong></div>
                        </div>
                    </div>
                `);

                const threadBody = $('#ticket-thread-body');
                threadBody.scrollTop(threadBody[0].scrollHeight);

                $('#ticket-reply-form').on('submit', function(e) {
                    e.preventDefault();
                    const btn = $(this).find('button[type="submit"]');
                    btn.prop('disabled', true).text('جاري الإرسال...');

                    const fd = new FormData(this);
                    fd.append('action', 'shipping_add_ticket_reply');
                    fd.append('nonce', '<?php echo wp_create_nonce("shipping_ticket_action"); ?>');

                    fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            shippingViewTicket(t.id);
                        } else alert('خطأ: ' + res.data);
                    });
                });
            }
        });
    };

    window.shippingBackToList = function() {
        currentActiveTicketId = null;
        $('#ticket-details-container').hide();
        $('#tickets-list-container').show();
        shippingLoadTickets();
    };

    function renderThreadHtml(thread) {
        let html = '';
        thread.forEach(m => {
            const isMe = m.sender_id == currentUserId;
            let fileHtml = '';
            if (m.file_url) {
                const fileName = m.file_url.split('/').pop();
                fileHtml = `<a href="${m.file_url}" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; margin-top: 10px; padding: 8px 12px; background: rgba(0,0,0,0.05); border-radius: 8px; text-decoration: none; color: inherit; font-size: 12px;">
                    <span class="dashicons dashicons-paperclip"></span> ${fileName}
                </a>`;
            }

            html += `
                <div style="display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'}; margin-bottom: 20px;">
                    <div style="background: ${isMe ? 'var(--shipping-primary-color)' : '#fff'}; color: ${isMe ? '#fff' : 'inherit'}; padding: 15px 20px; border-radius: 15px; border-bottom-${isMe ? 'left' : 'right'}-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: ${isMe ? 'none' : '1px solid #e2e8f0'}; max-width: 80%;">
                        <div style="font-weight: 800; font-size: 11px; margin-bottom: 5px; opacity: 0.8;">${m.sender_name} • ${m.created_at}</div>
                        <div style="font-size: 14px; line-height: 1.6; white-space: pre-wrap;">${m.message}</div>
                        ${fileHtml}
                    </div>
                </div>
            `;
        });
        return html;
    }

    window.shippingCloseTicket = function(id) {
        if (!confirm('هل أنت متأكد من إغلاق هذه التذكرة بشكل نهائي؟')) return;
        const fd = new FormData();
        fd.append('action', 'shipping_close_ticket');
        fd.append('id', id);
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_ticket_action"); ?>');

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                shippingViewTicket(id);
            } else alert('خطأ: ' + res.data);
        });
    };

    $('#create-ticket-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('جاري الإرسال...');

        const fd = new FormData(this);
        fd.append('action', 'shipping_create_ticket');
        fd.append('nonce', '<?php echo wp_create_nonce("shipping_ticket_action"); ?>');

        fetch(ajaxurl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                $('#create-ticket-modal').fadeOut();
                shippingLoadTickets();
                shippingViewTicket(res.data);
            } else {
                alert('خطأ: ' + res.data);
                btn.prop('disabled', false).text('إرسال التذكرة');
            }
        });
    });

    shippingLoadTickets();

    // Auto-refresh logic
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(() => {
        if (currentActiveTicketId) {
            shippingViewTicket(currentActiveTicketId, true);
        } else if ($('#tickets-list-container').is(':visible')) {
            shippingLoadTickets(false);
        }
    }, 5000);

})(jQuery);
</script>

<style>
.shipping-ticket-card:hover {
    border-color: var(--shipping-primary-color) !important;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    transform: translateY(-2px);
}
.shipping-loader-mini { border: 3px solid #f3f3f3; border-top: 3px solid var(--shipping-primary-color); border-radius: 50%; width: 24px; height: 24px; animation: shipping-spin 1s linear infinite; display: inline-block; }
@keyframes shipping-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
