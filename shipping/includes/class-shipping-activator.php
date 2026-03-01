<?php

class Shipping_Activator {

    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $installed_ver = get_option('shipping_db_version');

        // Migration: Rename old tables if they exist
        if (empty($installed_ver) || version_compare($installed_ver, SHIPPING_VERSION, '<')) {
            self::migrate_tables();
            self::migrate_settings();
        }

        $sql = "";

        // Customers Table
        $table_name = $wpdb->prefix . 'shipping_customers';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            username varchar(100) NOT NULL,
            customer_code tinytext,
            first_name tinytext NOT NULL,
            last_name tinytext NOT NULL,
            gender enum('male', 'female') DEFAULT 'male',
            year_of_birth int,
            residence_street text,
            residence_city tinytext,
            id_number tinytext,
            account_start_date date,
            account_expiration_date date,
            account_status tinytext,
            email tinytext,
            phone tinytext,
            alt_phone tinytext,
            notes text,
            photo_url text,
            wp_user_id bigint(20),
            officer_id bigint(20),
            registration_date date,
            classification varchar(50) DEFAULT 'regular',
            sort_order int DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY username (username),
            KEY wp_user_id (wp_user_id),
            KEY officer_id (officer_id)
        ) $charset_collate;\n";


        // Messages Table
        $table_name = $wpdb->prefix . 'shipping_messages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            customer_id mediumint(9),
            message text NOT NULL,
            file_url text,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY sender_id (sender_id),
            KEY receiver_id (receiver_id),
            KEY customer_id (customer_id)
        ) $charset_collate;\n";

        // Logs Table
        $table_name = $wpdb->prefix . 'shipping_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            action tinytext NOT NULL,
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;\n";


        // Notification Templates Table
        $table_name = $wpdb->prefix . 'shipping_notification_templates';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            body text NOT NULL,
            days_before int DEFAULT 0,
            is_enabled tinyint(1) DEFAULT 1,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY template_type (template_type)
        ) $charset_collate;\n";

        // Notification Logs Table
        $table_name = $wpdb->prefix . 'shipping_notification_logs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id mediumint(9),
            notification_type varchar(50),
            recipient_email varchar(100),
            subject varchar(255),
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20),
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY sent_at (sent_at)
        ) $charset_collate;\n";

        // Tickets Table
        $table_name = $wpdb->prefix . 'shipping_tickets';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            customer_id mediumint(9) NOT NULL,
            subject varchar(255) NOT NULL,
            category varchar(50),
            priority enum('low', 'medium', 'high') DEFAULT 'medium',
            status enum('open', 'in-progress', 'closed') DEFAULT 'open',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Ticket Thread Table
        $table_name = $wpdb->prefix . 'shipping_ticket_thread';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ticket_id mediumint(9) NOT NULL,
            sender_id bigint(20) NOT NULL,
            message text NOT NULL,
            file_url text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY sender_id (sender_id)
        ) $charset_collate;\n";

        // Pages Table
        $table_name = $wpdb->prefix . 'shipping_pages';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            shortcode varchar(50) NOT NULL,
            instructions text,
            settings text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;\n";

        // Articles Table
        $table_name = $wpdb->prefix . 'shipping_articles';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            image_url text,
            author_id bigint(20),
            status enum('publish', 'draft') DEFAULT 'publish',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alerts Table
        $table_name = $wpdb->prefix . 'shipping_alerts';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            severity enum('info', 'warning', 'critical') DEFAULT 'info',
            must_acknowledge tinyint(1) DEFAULT 0,
            status enum('active', 'inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Alert Views Table
        $table_name = $wpdb->prefix . 'shipping_alert_views';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            alert_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            acknowledged tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY alert_id (alert_id),
            KEY user_id (user_id)
        ) $charset_collate;\n";

        // Shipments Table
        $table_name = $wpdb->prefix . 'shipping_shipments';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            shipment_number varchar(100) NOT NULL,
            customer_id mediumint(9),
            origin varchar(255),
            destination varchar(255),
            weight decimal(10,2),
            dimensions varchar(100),
            classification varchar(50),
            status varchar(50) DEFAULT 'pending',
            pickup_date datetime,
            dispatch_date datetime,
            delivery_date datetime,
            carrier_id mediumint(9),
            route_id mediumint(9),
            is_archived tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY shipment_number (shipment_number),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY is_archived (is_archived)
        ) $charset_collate;\n";

        // Shipment Logs Table (Audit Trail)
        $table_name = $wpdb->prefix . 'shipping_shipment_logs';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            shipment_id mediumint(9) NOT NULL,
            user_id bigint(20),
            action varchar(100) NOT NULL,
            old_value text,
            new_value text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY shipment_id (shipment_id)
        ) $charset_collate;\n";

        // Shipment Tracking Events Table
        $table_name = $wpdb->prefix . 'shipping_shipment_tracking_events';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            shipment_id mediumint(9) NOT NULL,
            status varchar(50) NOT NULL,
            location varchar(255),
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY shipment_id (shipment_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Orders Table
        $table_name = $wpdb->prefix . 'shipping_orders';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_number varchar(100) NOT NULL,
            customer_id mediumint(9),
            total_amount decimal(10,2),
            status varchar(50) DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number)
        ) $charset_collate;\n";


        // Logistics Table
        $table_name = $wpdb->prefix . 'shipping_logistics';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            route_name varchar(255),
            stop_points text,
            fleet_details text,
            warehouse_info text,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Customs Table
        $table_name = $wpdb->prefix . 'shipping_customs';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            shipment_id mediumint(9),
            documentation_status varchar(50),
            duties_amount decimal(10,2),
            clearance_status varchar(50),
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        // Invoices Table
        $table_name = $wpdb->prefix . 'shipping_invoices';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            invoice_number varchar(100) NOT NULL,
            order_id mediumint(9),
            customer_id mediumint(9),
            subtotal decimal(10,2),
            tax_amount decimal(10,2),
            discount_amount decimal(10,2),
            total_amount decimal(10,2),
            items_json text,
            currency varchar(10) DEFAULT 'EGP',
            due_date date,
            status varchar(50) DEFAULT 'unpaid',
            invoice_type varchar(50) DEFAULT 'one-time',
            is_recurring tinyint(1) DEFAULT 0,
            billing_interval varchar(20),
            next_billing_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset_collate;\n";

        // Payments Table
        $table_name = $wpdb->prefix . 'shipping_payments';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            invoice_id mediumint(9),
            transaction_id varchar(100),
            amount_paid decimal(10,2),
            payment_date datetime DEFAULT CURRENT_TIMESTAMP,
            payment_method varchar(50),
            payment_status varchar(50),
            currency varchar(10) DEFAULT 'EGP',
            gateway_response text,
            notes text,
            PRIMARY KEY  (id),
            KEY invoice_id (invoice_id),
            KEY transaction_id (transaction_id)
        ) $charset_collate;\n";

        // Billing Logs Table (Audit Trail)
        $table_name = $wpdb->prefix . 'shipping_billing_logs';
        $sql .= "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id mediumint(9),
            user_id bigint(20),
            action varchar(100) NOT NULL,
            amount decimal(10,2),
            details text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY invoice_id (invoice_id)
        ) $charset_collate;\n";

        // Pricing Table
        $table_name = $wpdb->prefix . 'shipping_pricing';
        $sql .= "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            service_name varchar(255),
            base_cost decimal(10,2),
            additional_fees decimal(10,2),
            special_offer_details text,
            PRIMARY KEY  (id)
        ) $charset_collate;\n";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('shipping_db_version', SHIPPING_VERSION);

        self::setup_roles();
        self::seed_notification_templates();
    }

    private static function seed_notification_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_notification_templates';
        $templates = [
            'customership_renewal' => [
                'subject' => 'تذكير: تجديد حساب Shipping',
                'body' => "عزيزي العميل {customer_name}،\n\nنود تذكيركم بقرب موعد تجديد حسابكم السنوي لعام {year}.\nيرجى السداد لضمان استمرار الخدمات.\n\nشكراً لكم.",
                'days_before' => 30
            ],
            'welcome_activation' => [
                'subject' => 'مرحباً بك في المنصة الرقمية للشحن',
                'body' => "أهلاً بك يا {customer_name}،\n\nتم تفعيل حسابك بنجاح في منصة الشحن.\nيمكنك الآن تتبع شحناتك والاستفادة من كافة الخدمات الإلكترونية.\n\nكود الحساب الخاص بك: {id_number}",
                'days_before' => 0
            ],
            'admin_alert' => [
                'subject' => 'تنبيه إداري من Shipping',
                'body' => "عزيزي العميل {customer_name}،\n\n{alert_message}\n\nشكراً لكم.",
                'days_before' => 0
            ],
            'shipment_status_update' => [
                'subject' => 'تحديث حالة الشحنة: {shipment_number}',
                'body' => "عزيزي العميل،\n\nتم تحديث حالة شحنتكم رقم {shipment_number} إلى: {status}.\n\nشكراً لاستخدامكم خدماتنا.",
                'days_before' => 0
            ],
            'shipment_delay_alert' => [
                'subject' => 'تنبيه: تأخر في وصول الشحنة {shipment_number}',
                'body' => "عزيزي العميل،\n\nنعتذر عن إبلاغكم بوجود تأخير بسيط في وصول شحنتكم رقم {shipment_number}.\nالحالة الحالية: {status}.\n\nشكراً لتفهمكم.",
                'days_before' => 0
            ]
        ];

        foreach ($templates as $type => $data) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE template_type = %s", $type));
            if (!$exists) {
                $wpdb->insert($table, [
                    'template_type' => $type,
                    'subject' => $data['subject'],
                    'body' => $data['body'],
                    'days_before' => $data['days_before'],
                    'is_enabled' => 1
                ]);
            }
        }
    }

    private static function migrate_settings() {
        // Core info migration
        $old_info = get_option('sm_company_info') ?: get_option('workedia_info');
        if ($old_info && !get_option('shipping_info')) {
            $mapped_info = [];
            foreach ((array)$old_info as $key => $value) {
                $new_key = str_replace(['company_', 'workedia_', 'sm_'], 'shipping_', $key);
                $mapped_info[$new_key] = $value;
            }
            // Ensure essential keys are present
            if (isset($old_info['company_name'])) $mapped_info['shipping_name'] = $old_info['company_name'];
            if (isset($old_info['workedia_name'])) $mapped_info['shipping_name'] = $old_info['workedia_name'];

            if (isset($old_info['company_officer_name'])) $mapped_info['shipping_officer_name'] = $old_info['company_officer_name'];
            if (isset($old_info['workedia_officer_name'])) $mapped_info['shipping_officer_name'] = $old_info['workedia_officer_name'];

            if (isset($old_info['company_logo'])) $mapped_info['shipping_logo'] = $old_info['company_logo'];
            if (isset($old_info['workedia_logo'])) $mapped_info['shipping_logo'] = $old_info['workedia_logo'];

            update_option('shipping_info', $mapped_info);
        }

        // Settings migration
        $settings_to_migrate = [
            'sm_appearance'            => 'shipping_appearance',
            'sm_labels'                => 'shipping_labels',
            'sm_notification_settings' => 'shipping_notification_settings',
            'sm_last_backup_download'  => 'shipping_last_backup_download',
            'sm_last_backup_import'    => 'shipping_last_backup_import',
            'sm_plugin_version'        => 'shipping_plugin_version'
        ];

        foreach ($settings_to_migrate as $old => $new) {
            $val = get_option($old);
            if ($val !== false && get_option($new) === false) {
                update_option($new, $val);
            }
        }
    }

    private static function migrate_tables() {
        global $wpdb;
        // Rebranding Migration (workedia_ -> shipping_)
        $mappings = array(
            'workedia_customers'                  => 'shipping_customers',
            'workedia_messages'                 => 'shipping_messages',
            'workedia_logs'                     => 'shipping_logs',
            'workedia_notification_templates'   => 'shipping_notification_templates',
            'workedia_notification_logs'        => 'shipping_notification_logs',
            'workedia_tickets'                  => 'shipping_tickets',
            'workedia_ticket_thread'            => 'shipping_ticket_thread',
            'workedia_pages'                    => 'shipping_pages',
            'workedia_articles'                 => 'shipping_articles',
            'workedia_alerts'                   => 'shipping_alerts',
            'workedia_alert_views'              => 'shipping_alert_views',
            'workedia_shipments'                => 'shipping_shipments',
            'workedia_orders'                   => 'shipping_orders',
            'workedia_customers'                => 'shipping_customers',
            'workedia_logistics'                => 'shipping_logistics',
            'workedia_customs'                  => 'shipping_customs',
            'workedia_invoices'                 => 'shipping_invoices',
            'workedia_payments'                 => 'shipping_payments',
            'workedia_pricing'                  => 'shipping_pricing',
            'workedia_shipment_logs'            => 'shipping_shipment_logs',
            'workedia_shipment_tracking_events' => 'shipping_shipment_tracking_events',
            // Table Renaming
            'shipping_customers'          => 'shipping_customers',
            // Legacy Migration (sm_ -> shipping_)
            'sm_customers'                => 'shipping_customers',
            'sm_messages'               => 'shipping_messages',
            'sm_logs'                   => 'shipping_logs',
            'sm_payments'               => 'shipping_payments',
            'sm_notification_templates' => 'shipping_notification_templates',
            'sm_notification_logs'      => 'shipping_notification_logs',
            'sm_documents'              => 'shipping_documents',
            'sm_document_logs'          => 'shipping_document_logs',
            'sm_pub_templates'          => 'shipping_pub_templates',
            'sm_pub_documents'          => 'shipping_pub_documents',
            'sm_tickets'                => 'shipping_tickets',
            'sm_ticket_thread'          => 'shipping_ticket_thread',
            'sm_pages'                  => 'shipping_pages',
            'sm_articles'               => 'shipping_articles',
            'sm_alerts'                 => 'shipping_alerts',
            'sm_alert_views'            => 'shipping_alert_views'
        );

        foreach ($mappings as $old => $new) {
            $old_table = $wpdb->prefix . $old;
            $new_table = $wpdb->prefix . $new;
            if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") && !$wpdb->get_var("SHOW TABLES LIKE '$new_table'")) {
                $wpdb->query("RENAME TABLE $old_table TO $new_table");
            }
        }

        $customers_table = $wpdb->prefix . 'shipping_customers';
        if ($wpdb->get_var("SHOW TABLES LIKE '$customers_table'")) {
            // Rename national_id to username if it exists
            $col_national = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE 'national_id'");
            if (!empty($col_national)) {
                $wpdb->query("ALTER TABLE $customers_table CHANGE national_id username varchar(100) NOT NULL");
            }

            // Split name into first_name and last_name if name exists
            $col_name = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE 'name'");
            if (!empty($col_name)) {
                // Ensure first_name and last_name columns exist
                $col_first = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE 'first_name'");
                if (empty($col_first)) {
                    $wpdb->query("ALTER TABLE $customers_table ADD first_name tinytext NOT NULL AFTER username");
                    $wpdb->query("ALTER TABLE $customers_table ADD last_name tinytext NOT NULL AFTER first_name");

                    // Migrate data
                    $existing_customers = $wpdb->get_results("SELECT id, name FROM $customers_table");
                    foreach ($existing_customers as $m) {
                        $parts = explode(' ', $m->name);
                        $first = $parts[0];
                        $last = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '.';
                        $wpdb->update($customers_table, ['first_name' => $first, 'last_name' => $last], ['id' => $m->id]);
                    }
                }
                // Drop old name column
                $wpdb->query("ALTER TABLE $customers_table DROP COLUMN name");
            }

            // Drop geographic columns if they exist
            $cols_to_drop = ['governorate', 'province'];
            foreach ($cols_to_drop as $col) {
                $exists = $wpdb->get_results("SHOW COLUMNS FROM $customers_table LIKE '$col'");
                if (!empty($exists)) {
                    $wpdb->query("ALTER TABLE $customers_table DROP COLUMN $col");
                }
            }
        }
    }

    private static function setup_roles() {
        // Remove custom roles if they exist
        remove_role('shipping_system_admin');
        remove_role('shipping_admin');
        remove_role('shipping_customer');
        remove_role('shipping_officer');
        remove_role('shipping_company_admin');
        remove_role('shipping_company_customer');
        remove_role('sm_system_admin');
        remove_role('sm_company_admin');
        remove_role('sm_company_customer');
        remove_role('sm_officer');
        remove_role('sm_customer');
        remove_role('sm_parent');
        remove_role('sm_student');

        // Remove custom capabilities from administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $custom_caps = [
                'shipping_manage_system',
                'shipping_manage_users',
                'shipping_manage_customers',
                'shipping_manage_finance',
                'shipping_manage_licenses',
                'shipping_print_reports',
                'shipping_full_access',
                'shipping_manage_archive'
            ];
            foreach ($custom_caps as $cap) {
                $admin_role->remove_cap($cap);
            }
        }

        self::migrate_user_meta();
        self::migrate_user_roles();
        self::sync_missing_customer_accounts();
        self::create_pages();
    }

    private static function migrate_user_meta() {
        global $wpdb;
        $meta_mappings = [
            'sm_phone' => 'shipping_phone',
            'sm_account_status' => 'shipping_account_status',
            'sm_temp_pass' => 'shipping_temp_pass',
            'sm_recovery_otp' => 'shipping_recovery_otp',
            'sm_recovery_otp_time' => 'shipping_recovery_otp_time',
            'sm_recovery_otp_used' => 'shipping_recovery_otp_used'
        ];

        foreach ($meta_mappings as $old => $new) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}usermeta SET meta_key = %s WHERE meta_key = %s",
                $new, $old
            ));
        }

        // Split name for existing users in usermeta
        $users = get_users(['fields' => ['ID', 'display_name']]);
        foreach ($users as $u) {
            if (!get_user_meta($u->ID, 'first_name', true)) {
                $parts = explode(' ', $u->display_name);
                update_user_meta($u->ID, 'first_name', $parts[0]);
                update_user_meta($u->ID, 'last_name', isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '.');
            }
        }
    }

    private static function create_pages() {
        global $wpdb;
        $pages = array(
            'shipping-login' => array(
                'title' => 'تسجيل الدخول للنظام',
                'content' => '[shipping_login]'
            ),
            'shipping-admin' => array(
                'title' => 'لوحة الإدارة الشحن المحلي والدولي',
                'content' => '[shipping_admin]'
            ),
            'home' => array(
                'title' => 'الرئيسية',
                'content' => '[shipping_home]',
                'shortcode' => 'shipping_home'
            ),
            'about-us' => array(
                'title' => 'عن Shipping',
                'content' => '[shipping_about]',
                'shortcode' => 'shipping_about'
            ),
            'contact-us' => array(
                'title' => 'اتصل بنا',
                'content' => '[shipping_contact]',
                'shortcode' => 'shipping_contact'
            ),
            'articles' => array(
                'title' => 'أخبار ومقالات',
                'content' => '[shipping_blog]',
                'shortcode' => 'shipping_blog'
            ),
            'shipping-register' => array(
                'title' => 'إنشاء حساب جديد',
                'content' => '[shipping_register]',
                'shortcode' => 'shipping_register'
            )
        );

        foreach ($pages as $slug => $data) {
            $existing = get_page_by_path($slug);
            if (!$existing) {
                wp_insert_post(array(
                    'post_title'    => $data['title'],
                    'post_content'  => $data['content'],
                    'post_status'   => 'publish',
                    'post_type'     => 'page',
                    'post_name'     => $slug
                ));
            }

            // Sync with shipping_pages table
            if (isset($data['shortcode'])) {
                $table = $wpdb->prefix . 'shipping_pages';
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug));
                if (!$exists) {
                    $wpdb->insert($table, array(
                        'title' => $data['title'],
                        'slug' => $slug,
                        'shortcode' => $data['shortcode'],
                        'instructions' => 'تحرير بيانات هذه الصفحة من إعدادات النظام.',
                        'settings' => json_encode(['layout' => 'standard'])
                    ));
                }
            }
        }
    }

    private static function sync_missing_customer_accounts() {
        global $wpdb;
        $customers = $wpdb->get_results("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id IS NULL OR wp_user_id = 0");
        foreach ($customers as $m) {
            $digits = '';
            for ($i = 0; $i < 10; $i++) {
                $digits .= mt_rand(0, 9);
            }
            $temp_pass = 'SHP' . $digits;
            $user_id = wp_insert_user([
                'user_login' => $m->username,
                'user_email' => $m->email ?: $m->username . '@shipping.com',
                'display_name' => $m->name,
                'user_pass' => $temp_pass,
                'role' => 'subscriber'
            ]);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'shipping_temp_pass', $temp_pass);
                $wpdb->update("{$wpdb->prefix}shipping_customers", ['wp_user_id' => $user_id], ['id' => $m->id]);
            }
        }
    }

    private static function migrate_user_roles() {
        $role_migration = array(
            'sm_system_admin'           => 'administrator',
            'sm_company_admin'        => 'administrator',
            'sm_company_customer'       => 'subscriber',
            'sm_officer'                => 'administrator',
            'sm_customer'                 => 'subscriber',
            'sm_parent'                 => 'subscriber',
            'sm_student'                => 'subscriber',
            'shipping_system_admin'     => 'administrator',
            'shipping_admin'            => 'administrator',
            'shipping_customer'           => 'subscriber',
            'shipping_company_admin'  => 'administrator',
            'shipping_company_customer' => 'subscriber'
        );

        foreach ($role_migration as $old => $new) {
            $users = get_users(array('role' => $old));
            if (!empty($users)) {
                foreach ($users as $user) {
                    $user->add_role($new);
                    $user->remove_role($old);
                }
            }
        }
    }
}
