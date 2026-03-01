<?php

class Shipping {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'shipping';
        $this->version = SHIPPING_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-loader.php';
        require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-db.php';
        require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-settings.php';
        require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-logger.php';
        require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-notifications.php';
        require_once SHIPPING_PLUGIN_DIR . 'admin/class-shipping-admin.php';
        require_once SHIPPING_PLUGIN_DIR . 'public/class-shipping-public.php';
        $this->loader = new Shipping_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Shipping_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    }

    private function define_public_hooks() {
        $plugin_public = new Shipping_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('show_admin_bar', $plugin_public, 'hide_admin_bar_for_non_admins');
        $this->loader->add_action('admin_init', $plugin_public, 'restrict_admin_access');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_footer', $plugin_public, 'inject_global_alerts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_form_submission');
        $this->loader->add_action('wp_login_failed', $plugin_public, 'login_failed');
        $this->loader->add_action('wp_login', $plugin_public, 'log_successful_login', 10, 2);
        $this->loader->add_action('wp_ajax_shipping_get_customer', $plugin_public, 'ajax_get_customer');
        $this->loader->add_action('wp_ajax_shipping_search_customers', $plugin_public, 'ajax_search_customers');
        $this->loader->add_action('wp_ajax_shipping_refresh_dashboard', $plugin_public, 'ajax_refresh_dashboard');
        $this->loader->add_action('wp_ajax_shipping_update_customer_photo', $plugin_public, 'ajax_update_customer_photo');
        $this->loader->add_action('wp_ajax_shipping_send_message_ajax', $plugin_public, 'ajax_send_message');
        $this->loader->add_action('wp_ajax_shipping_get_conversation_ajax', $plugin_public, 'ajax_get_conversation');
        $this->loader->add_action('wp_ajax_shipping_get_conversations_ajax', $plugin_public, 'ajax_get_conversations');
        $this->loader->add_action('wp_ajax_shipping_mark_read', $plugin_public, 'ajax_mark_read');
        $this->loader->add_action('wp_ajax_shipping_get_tickets', $plugin_public, 'ajax_get_tickets');
        $this->loader->add_action('wp_ajax_shipping_create_ticket', $plugin_public, 'ajax_create_ticket');
        $this->loader->add_action('wp_ajax_shipping_get_ticket_details', $plugin_public, 'ajax_get_ticket_details');
        $this->loader->add_action('wp_ajax_shipping_add_ticket_reply', $plugin_public, 'ajax_add_ticket_reply');
        $this->loader->add_action('wp_ajax_shipping_close_ticket', $plugin_public, 'ajax_close_ticket');
        $this->loader->add_action('wp_ajax_shipping_create_shipment', $plugin_public, 'ajax_create_shipment');
        $this->loader->add_action('wp_ajax_shipping_update_shipment', $plugin_public, 'ajax_update_shipment');
        $this->loader->add_action('wp_ajax_shipping_get_shipment_tracking', $plugin_public, 'ajax_get_shipment_tracking');
        $this->loader->add_action('wp_ajax_shipping_bulk_shipments', $plugin_public, 'ajax_bulk_shipments');
        $this->loader->add_action('wp_ajax_shipping_save_invoice', $plugin_public, 'ajax_save_invoice');
        $this->loader->add_action('wp_ajax_shipping_process_payment', $plugin_public, 'ajax_process_payment');
        $this->loader->add_action('wp_ajax_shipping_get_billing_report', $plugin_public, 'ajax_get_billing_report');
        $this->loader->add_action('wp_ajax_shipping_add_customer', $plugin_public, 'ajax_add_customer');
        $this->loader->add_action('wp_ajax_shipping_add_order', $plugin_public, 'ajax_add_order');
        $this->loader->add_action('wp_ajax_shipping_add_route', $plugin_public, 'ajax_add_route');
        $this->loader->add_action('wp_ajax_shipping_add_customs', $plugin_public, 'ajax_add_customs');
        $this->loader->add_action('wp_ajax_shipping_add_pricing', $plugin_public, 'ajax_add_pricing');
        $this->loader->add_action('wp_ajax_shipping_update_profile_ajax', $plugin_public, 'ajax_update_profile');
        $this->loader->add_action('wp_ajax_shipping_print', $plugin_public, 'handle_print');
        $this->loader->add_action('wp_ajax_shipping_add_customer_ajax', $plugin_public, 'ajax_add_customer');
        $this->loader->add_action('wp_ajax_shipping_update_customer_ajax', $plugin_public, 'ajax_update_customer');
        $this->loader->add_action('wp_ajax_shipping_delete_customer_ajax', $plugin_public, 'ajax_delete_customer');
        $this->loader->add_action('wp_ajax_shipping_get_counts_ajax', $plugin_public, 'ajax_get_counts');
        $this->loader->add_action('wp_ajax_shipping_add_staff_ajax', $plugin_public, 'ajax_add_staff');
        $this->loader->add_action('wp_ajax_shipping_update_staff_ajax', $plugin_public, 'ajax_update_staff');
        $this->loader->add_action('wp_ajax_shipping_delete_staff_ajax', $plugin_public, 'ajax_delete_staff');
        $this->loader->add_action('wp_ajax_shipping_bulk_delete_users_ajax', $plugin_public, 'ajax_bulk_delete_users');
        $this->loader->add_action('wp_ajax_shipping_reset_system_ajax', $plugin_public, 'ajax_reset_system');
        $this->loader->add_action('wp_ajax_shipping_rollback_log_ajax', $plugin_public, 'ajax_rollback_log');
        $this->loader->add_action('wp_ajax_shipping_delete_log', $plugin_public, 'ajax_delete_log');
        $this->loader->add_action('wp_ajax_shipping_clear_all_logs', $plugin_public, 'ajax_clear_all_logs');
        $this->loader->add_action('wp_ajax_shipping_get_user_role', $plugin_public, 'ajax_get_user_role');
        $this->loader->add_action('wp_ajax_shipping_update_customer_account_ajax', $plugin_public, 'ajax_update_customer_account');
        $this->loader->add_action('wp_ajax_shipping_verify_document', $plugin_public, 'ajax_verify_document');
        $this->loader->add_action('wp_ajax_nopriv_shipping_verify_document', $plugin_public, 'ajax_verify_document');
        $this->loader->add_action('wp_ajax_nopriv_shipping_forgot_password_otp', $plugin_public, 'ajax_forgot_password_otp');
        $this->loader->add_action('wp_ajax_nopriv_shipping_reset_password_otp', $plugin_public, 'ajax_reset_password_otp');
        $this->loader->add_action('wp_ajax_shipping_get_template_ajax', $plugin_public, 'ajax_get_template_ajax');
        $this->loader->add_action('wp_ajax_shipping_save_template_ajax', $plugin_public, 'ajax_save_template_ajax');
        $this->loader->add_action('wp_ajax_shipping_save_page_settings', $plugin_public, 'ajax_save_page_settings');
        $this->loader->add_action('wp_ajax_shipping_add_article', $plugin_public, 'ajax_add_article');
        $this->loader->add_action('wp_ajax_shipping_delete_article', $plugin_public, 'ajax_delete_article');
        $this->loader->add_action('wp_ajax_shipping_save_alert', $plugin_public, 'ajax_save_alert');
        $this->loader->add_action('wp_ajax_shipping_delete_alert', $plugin_public, 'ajax_delete_alert');
        $this->loader->add_action('wp_ajax_shipping_acknowledge_alert', $plugin_public, 'ajax_acknowledge_alert');
        $this->loader->add_action('wp_ajax_nopriv_shipping_check_username_email', $plugin_public, 'ajax_check_username_email');
        $this->loader->add_action('wp_ajax_nopriv_shipping_register_send_otp', $plugin_public, 'ajax_register_send_otp');
        $this->loader->add_action('wp_ajax_nopriv_shipping_register_verify_otp', $plugin_public, 'ajax_register_verify_otp');
        $this->loader->add_action('wp_ajax_nopriv_shipping_register_complete', $plugin_public, 'ajax_register_complete');
        $this->loader->add_action('shipping_daily_maintenance', 'Shipping_DB', 'delete_expired_messages');
        $this->loader->add_action('shipping_daily_maintenance', 'Shipping_Notifications', 'run_daily_checks');
    }

    public function run() {
        add_action('plugins_loaded', array($this, 'check_version_updates'));
        $this->loader->add_action('init', $this, 'schedule_maintenance_cron');
        $this->loader->run();
    }

    public function schedule_maintenance_cron() {
        if (function_exists('wp_next_scheduled') && !wp_next_scheduled('shipping_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'shipping_daily_maintenance');
        }
    }

    public function check_version_updates() {
        $db_version = get_option('shipping_plugin_version', '1.0.0');
        if (version_compare($db_version, SHIPPING_VERSION, '<')) {
            require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-activator.php';
            Shipping_Activator::activate();
            update_option('shipping_plugin_version', SHIPPING_VERSION);
        }
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
