<?php
if (!defined('ABSPATH')) exit;
global $wpdb;
$is_officer = current_user_can('manage_options');
?>

<?php if ($is_officer): ?>
<div class="shipping-card-grid" style="margin-bottom: 30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
    <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
        <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">إجمالي العملاء</div>
        <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-primary-color);"><?php echo esc_html($stats['total_customers'] ?? 0); ?></div>
    </div>
    <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
        <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">شحنات نشطة</div>
        <div style="font-size: 2.5em; font-weight: 900; color: var(--shipping-secondary-color);"><?php echo esc_html($stats['active_shipments'] ?? 0); ?></div>
    </div>
    <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
        <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">طلبات جديدة</div>
        <div style="font-size: 2.5em; font-weight: 900; color: #2ecc71;"><?php echo esc_html($stats['new_orders'] ?? 0); ?></div>
    </div>
    <div class="shipping-stat-card" style="background: white; padding: 25px; border-radius: 15px; border: 1px solid var(--shipping-border-color); box-shadow: var(--shipping-shadow); text-align: center;">
        <div style="font-size: 0.85em; color: #64748b; margin-bottom: 10px; font-weight: 700;">إجمالي الإيرادات</div>
        <div style="font-size: 1.8em; font-weight: 900; color: #27ae60; margin-top: 10px;"><?php echo number_format($stats['total_revenue'] ?? 0, 0); ?> <span style="font-size: 0.4em;">EGP</span></div>
    </div>
</div>

<?php endif; ?>

<?php if ($is_officer): ?>
<div id="activity-logs" style="margin-top: 30px;">
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:15px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <div>
                <h4 style="margin:0; font-size:16px;">سجل نشاطات النظام الشامل</h4>
                <div style="font-size:11px; color:#718096;">آخر 200 نشاط مسجل في النظام.</div>
            </div>
            <div style="display:flex; gap:10px;">
                <form method="get" style="display:flex; gap:5px;">
                    <input type="hidden" name="shipping_tab" value="summary">
                    <input type="text" name="log_search" value="<?php echo esc_attr($_GET['log_search'] ?? ''); ?>" placeholder="بحث في السجلات..." class="shipping-input" style="width:200px; padding:5px 10px; font-size:12px;">
                    <button type="submit" class="shipping-btn" style="width:auto; padding:5px 15px; font-size:12px;">بحث</button>
                </form>
                <button onclick="shippingDeleteAllLogs()" class="shipping-btn" style="background:#e53e3e; width:auto; font-size:12px; padding:5px 15px;">تفريغ السجل</button>
            </div>
        </div>
        <div class="shipping-table-container" style="margin:0; overflow-x:auto;">
            <table class="shipping-table" style="font-size:12px; width:100%;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:8px; width:140px;">الوقت</th>
                        <th style="padding:8px; width:120px;">المستخدم</th>
                        <th style="padding:8px; width:120px;">الإجراء</th>
                        <th style="padding:8px;">التفاصيل</th>
                        <th style="padding:8px; width:100px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $limit = 25;
                    $page_num = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
                    $offset = ($page_num - 1) * $limit;
                    $search = sanitize_text_field($_GET['log_search'] ?? '');
                    $all_logs = Shipping_Logger::get_logs($limit, $offset, $search);
                    $total_logs = Shipping_Logger::get_total_logs($search);
                    $total_pages = ceil($total_logs / $limit);

                    if (empty($all_logs)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">لا توجد سجلات تطابق البحث</td></tr>
                    <?php endif;

                    $appearance = Shipping_Settings::get_appearance();
                    foreach ($all_logs as $log):
                        $can_rollback = strpos($log->details, 'ROLLBACK_DATA:') === 0;
                        $details_display = $can_rollback ? 'عملية تتضمن بيانات للاستعادة' : esc_html($log->details);
                    ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding:6px 8px; color: #718096;"><?php echo esc_html($log->created_at); ?></td>
                            <td style="padding:6px 8px; font-weight: 600;"><?php echo esc_html($log->display_name ?: 'نظام'); ?></td>
                            <td style="padding:6px 8px;"><span style="background:<?php echo $appearance['primary_color']; ?>15; color:<?php echo $appearance['primary_color']; ?>; padding:2px 6px; border-radius:4px; font-weight:700;"><?php echo esc_html($log->action); ?></span></td>
                            <td style="padding:6px 8px; color:#4a5568; line-height:1.4;"><?php echo mb_strimwidth($details_display, 0, 100, "..."); ?></td>
                            <td style="padding:6px 8px;">
                                <div style="display:flex; gap:5px;">
                                    <button onclick='shippingViewLogDetails(<?php echo json_encode($log); ?>)' class="shipping-btn shipping-btn-outline" style="padding:2px 8px; font-size:10px;">التفاصيل</button>
                                    <?php if ($can_rollback): ?>
                                        <button onclick="shippingRollbackLog(<?php echo $log->id; ?>)" class="shipping-btn" style="padding:2px 8px; font-size:10px; background:#38a169;">استعادة</button>
                                    <?php endif; ?>
                                    <button onclick="shippingDeleteLog(<?php echo $log->id; ?>)" class="shipping-btn" style="padding:2px 8px; font-size:10px; background:#e53e3e;">حذف</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
            <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                <?php if ($page_num > 1): ?>
                    <a href="<?php echo add_query_arg('log_page', $page_num - 1); ?>" class="shipping-btn shipping-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">السابق</a>
                <?php endif; ?>
                <span style="align-self:center; font-size:13px;">صفحة <?php echo $page_num; ?> من <?php echo $total_pages; ?></span>
                <?php if ($page_num < $total_pages): ?>
                    <a href="<?php echo add_query_arg('log_page', $page_num + 1); ?>" class="shipping-btn shipping-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">التالي</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function shippingDownloadChart(chartId, fileName) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const link = document.createElement('a');
    link.download = fileName + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

(function() {
    <?php if (!$is_officer): ?>
    return;
    <?php endif; ?>
    window.shippingCharts = window.shippingCharts || {};

    const initSummaryCharts = function() {
        if (typeof Chart === 'undefined') {
            setTimeout(initSummaryCharts, 200);
            return;
        }

        const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };

        const createOrUpdateChart = (id, config) => {
            if (window.shippingCharts[id]) {
                window.shippingCharts[id].destroy();
            }
            const el = document.getElementById(id);
            if (el) {
                window.shippingCharts[id] = new Chart(el.getContext('2d'), config);
            }
        };


    };

    if (document.readyState === 'complete') initSummaryCharts();
    else window.addEventListener('load', initSummaryCharts);
})();
</script>
