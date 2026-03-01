<?php

if ( ! class_exists( 'Shipping_DB' ) ) {
class Shipping_DB {

    public static function get_staff($args = array()) {
        $default_args = array(
            'role__in' => array('administrator', 'subscriber'),
            'number' => 20,
            'offset' => 0
        );

        $args = wp_parse_args($args, $default_args);
        return get_users($args);
    }

    public static function get_customers($args = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';
        $query = "SELECT *, CONCAT(first_name, ' ', last_name) as name FROM $table_name WHERE 1=1";
        $params = array();

        $limit = isset($args['limit']) ? intval($args['limit']) : 20;
        $offset = isset($args['offset']) ? intval($args['offset']) : 0;

        // Ensure we don't have negative limits unless specifically -1
        if ($limit < -1) $limit = 20;

        if (isset($args['account_status']) && !empty($args['account_status'])) {
            $query .= " AND account_status = %s";
            $params[] = $args['account_status'];
        }

        if (isset($args['search']) && !empty($args['search'])) {
            $query .= " AND (first_name LIKE %s OR last_name LIKE %s OR username LIKE %s OR id_number LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $query .= " ORDER BY sort_order ASC, first_name ASC, last_name ASC";

        if ($limit != -1) {
            $query .= " LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;
        }

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function get_customer_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE id = %d", $id));
    }

    public static function get_customer_by_username($username) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE username = %s", $username));
    }

    public static function get_customer_by_id_number($id_number) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE id_number = %s", $id_number));
    }

    public static function get_customer_by_wp_username($username) {
        $user = get_user_by('login', $username);
        if (!$user) return null;
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT *, CONCAT(first_name, ' ', last_name) as name FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));
    }

    public static function add_customer($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';

        $username = sanitize_text_field($data['username'] ?? '');
        if (empty($username)) {
            return new WP_Error('invalid_username', 'اسم المستخدم مطلوب.');
        }

        // Check if username already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE username = %s", $username));
        if ($exists) {
            return new WP_Error('duplicate_username', 'اسم المستخدم مسجل مسبقاً.');
        }

        $first_name = sanitize_text_field($data['first_name'] ?? '');
        $last_name = sanitize_text_field($data['last_name'] ?? '');
        $full_name = trim($first_name . ' ' . $last_name);
        $email = sanitize_email($data['email'] ?? '');

        // Auto-create WordPress User for the Customer
        $wp_user_id = null;
        $digits = '';
        for ($i = 0; $i < 10; $i++) {
            $digits .= mt_rand(0, 9);
        }
        $temp_pass = 'SHP' . $digits;

        if (!function_exists('wp_insert_user')) {
            require_once(ABSPATH . 'wp-includes/user.php');
        }

        $wp_user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email ?: $username . '@shipping.com',
            'display_name' => $full_name,
            'user_pass' => $temp_pass,
            'role' => 'subscriber'
        ));

        if (!is_wp_error($wp_user_id)) {
            $wp_user_id = $wp_user_id;
            update_user_meta($wp_user_id, 'shipping_temp_pass', $temp_pass);
            update_user_meta($wp_user_id, 'first_name', $first_name);
            update_user_meta($wp_user_id, 'last_name', $last_name);
        } else {
            return $wp_user_id; // Return WP_Error
        }

        $insert_data = array(
            'username' => $username,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'gender' => sanitize_text_field($data['gender'] ?? 'male'),
            'year_of_birth' => intval($data['year_of_birth'] ?? 0),
            'residence_street' => sanitize_textarea_field($data['residence_street'] ?? ''),
            'residence_city' => sanitize_text_field($data['residence_city'] ?? ''),
            'id_number' => sanitize_text_field($data['id_number'] ?? ''),
            'account_start_date' => sanitize_text_field($data['account_start_date'] ?? null),
            'account_expiration_date' => sanitize_text_field($data['account_expiration_date'] ?? null),
            'account_status' => sanitize_text_field($data['account_status'] ?? ''),
            'email' => $email ?: $username . '@shipping.com',
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'alt_phone' => sanitize_text_field($data['alt_phone'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'wp_user_id' => $wp_user_id,
            'registration_date' => current_time('Y-m-d'),
            'sort_order' => self::get_next_sort_order()
        );

        $wpdb->insert($table_name, $insert_data);
        $id = $wpdb->insert_id;

        if ($id) {
            Shipping_Logger::log('إضافة عميل جديد', "تمت إضافة العميل: $full_name بنجاح (اسم المستخدم: $username)");
        }

        return $id;
    }

    public static function add_customer_record($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';

        $insert_data = array(
            'username' => sanitize_text_field($data['username']),
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'gender' => sanitize_text_field($data['gender'] ?? 'male'),
            'year_of_birth' => intval($data['year_of_birth'] ?? 0),
            'email' => sanitize_email($data['email']),
            'wp_user_id' => intval($data['wp_user_id']),
            'account_status' => sanitize_text_field($data['account_status'] ?? 'active'),
            'registration_date' => current_time('Y-m-d'),
            'sort_order' => self::get_next_sort_order()
        );

        $wpdb->insert($table_name, $insert_data);
        return $wpdb->insert_id;
    }

    public static function update_customer($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shipping_customers';

        $update_data = array();
        $fields = [
            'username', 'first_name', 'last_name', 'gender', 'year_of_birth',
            'residence_street', 'residence_city', 'id_number',
            'account_start_date', 'account_expiration_date',
            'account_status', 'email', 'phone', 'alt_phone', 'notes'
        ];

        foreach ($fields as $f) {
            if (isset($data[$f])) {
                if (in_array($f, ['notes', 'residence_street'])) {
                    $update_data[$f] = sanitize_textarea_field($data[$f]);
                } elseif ($f === 'email') {
                    $update_data[$f] = sanitize_email($data[$f]);
                } else {
                    $update_data[$f] = sanitize_text_field($data[$f]);
                }
            }
        }

        if (isset($data['wp_user_id'])) $update_data['wp_user_id'] = intval($data['wp_user_id']);
        if (isset($data['registration_date'])) $update_data['registration_date'] = sanitize_text_field($data['registration_date']);
        if (isset($data['sort_order'])) $update_data['sort_order'] = intval($data['sort_order']);

        $res = $wpdb->update($table_name, $update_data, array('id' => $id));

        // Sync to WP User
        $customer = self::get_customer_by_id($id);
        if ($customer && $customer->wp_user_id) {
            $user_data = ['ID' => $customer->wp_user_id];
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $f = $data['first_name'] ?? $customer->first_name;
                $l = $data['last_name'] ?? $customer->last_name;
                $user_data['display_name'] = trim($f . ' ' . $l);
                update_user_meta($customer->wp_user_id, 'first_name', $f);
                update_user_meta($customer->wp_user_id, 'last_name', $l);
            }
            if (isset($data['email'])) $user_data['user_email'] = $data['email'];
            if (count($user_data) > 1) {
                wp_update_user($user_data);
            }
        }

        return $res;
    }

    public static function update_customer_photo($id, $photo_url) {
        global $wpdb;
        return $wpdb->update($wpdb->prefix . 'shipping_customers', array('photo_url' => $photo_url), array('id' => $id));
    }

    public static function delete_customer($id) {
        global $wpdb;

        $customer = self::get_customer_by_id($id);
        if ($customer) {
            Shipping_Logger::log('حذف عميل (مع إمكانية الاستعادة)', 'ROLLBACK_DATA:' . json_encode(['table' => 'customers', 'data' => (array)$customer]));
            if ($customer->wp_user_id) {
                if (!function_exists('wp_delete_user')) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                }
                wp_delete_user($customer->wp_user_id);
            }
        }

        return $wpdb->delete($wpdb->prefix . 'shipping_customers', array('id' => $id));
    }

    public static function customer_exists($username) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}shipping_customers WHERE username = %s",
            $username
        ));
    }

    public static function get_next_sort_order() {
        global $wpdb;
        $max = $wpdb->get_var("SELECT MAX(sort_order) FROM {$wpdb->prefix}shipping_customers");
        return ($max ? intval($max) : 0) + 1;
    }

    public static function send_message($sender_id, $receiver_id, $message, $customer_id = null, $file_url = null) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_messages', array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'customer_id' => $customer_id,
            'message' => $message,
            'file_url' => $file_url,
            'created_at' => current_time('mysql')
        ));
    }

    public static function add_order($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_orders', array(
            'order_number' => 'ORD-' . strtoupper(wp_generate_password(8, false)),
            'customer_id' => intval($data['customer_id']),
            'total_amount' => floatval($data['total_amount']),
            'status' => 'new',
            'created_at' => current_time('mysql')
        ));
    }


    public static function get_ticket_messages($customer_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}shipping_messages m
             LEFT JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE m.customer_id = %d
             ORDER BY m.created_at ASC",
            $customer_id
        ));
    }

    public static function get_conversation_messages($user1, $user2) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as sender_name
             FROM {$wpdb->prefix}shipping_messages m
             JOIN {$wpdb->prefix}users u ON m.sender_id = u.ID
             WHERE (sender_id = %d AND receiver_id = %d)
                OR (sender_id = %d AND receiver_id = %d)
             ORDER BY created_at ASC",
            $user1, $user2, $user2, $user1
        ));
    }

    public static function get_sent_messages($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name as receiver_name
             FROM {$wpdb->prefix}shipping_messages m
             JOIN {$wpdb->prefix}users u ON m.receiver_id = u.ID
             WHERE m.sender_id = %d
             ORDER BY m.created_at DESC",
            $user_id
        ));
    }

    public static function delete_expired_messages() {
        global $wpdb;
        return $wpdb->query("DELETE FROM {$wpdb->prefix}shipping_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    }

    public static function get_conversations($user_id) {
        global $wpdb;
        $other_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT CASE WHEN sender_id = %d THEN receiver_id ELSE sender_id END
             FROM {$wpdb->prefix}shipping_messages
             WHERE sender_id = %d OR receiver_id = %d",
            $user_id, $user_id, $user_id
        ));

        $conversations = [];
        foreach ($other_ids as $oid) {
            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipping_messages
                 WHERE (sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d)
                 ORDER BY created_at DESC LIMIT 1",
                $user_id, $oid, $oid, $user_id
            ));
            $conversations[] = [
                'user' => get_userdata($oid),
                'last_message' => $last_msg
            ];
        }
        return $conversations;
    }

    public static function get_officials() {
        return get_users(array('role__in' => array('administrator')));
    }

    public static function get_all_conversations() {
        global $wpdb;
        $ticket_customers = $wpdb->get_col("SELECT DISTINCT customer_id FROM {$wpdb->prefix}shipping_messages WHERE customer_id IS NOT NULL");
        $results = [];
        foreach ($ticket_customers as $mid) {
            $customer = self::get_customer_by_id($mid);
            if (!$customer) continue;
            $last_msg = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipping_messages WHERE customer_id = %d ORDER BY created_at DESC LIMIT 1",
                $mid
            ));
            $unread = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}shipping_messages WHERE customer_id = %d AND is_read = 0",
                $mid
            ));
            $results[] = [
                'customer' => $customer,
                'last_message' => $last_msg,
                'unread_count' => $unread
            ];
        }
        return $results;
    }

    public static function get_statistics($filters = array()) {
        global $wpdb;
        $stats = array();

        $stats['total_customers'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_customers");
        $stats['active_shipments'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE status != 'delivered' AND is_archived = 0");
        $stats['delivered_shipments'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE status = 'delivered'");
        $stats['delayed_shipments'] = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_shipments WHERE status != 'delivered' AND delivery_date < %s", current_time('mysql')));
        $stats['total_revenue'] = $wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}shipping_invoices WHERE status = 'paid'");
        $stats['new_orders'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}shipping_orders WHERE status = 'new'");

        return $stats;
    }

    public static function get_customer_stats($customer_id) {
        return array();
    }

    public static function delete_all_data() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}shipping_customers");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}shipping_messages");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}shipping_logs");
        Shipping_Logger::log('مسح شامل للبيانات', 'تم تنفيذ أمر مسح كافة بيانات النظام');
    }

    public static function get_backup_data() {
        global $wpdb;
        $data = array();
        $tables = array(
            'customers', 'messages', 'shipments', 'orders', 'customers',
            'logistics', 'customs', 'invoices', 'payments', 'pricing',
            'shipment_logs', 'shipment_tracking_events'
        );
        foreach ($tables as $t) {
            // Check if table exists before querying
            $table_name = $wpdb->prefix . 'shipping_' . $t;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
                $data[$t] = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
            }
        }
        return json_encode($data);
    }

    public static function restore_backup($json) {
        global $wpdb;
        $data = json_decode($json, true);
        if (!$data) return false;

        foreach ($data as $table => $rows) {
            $table_name = $wpdb->prefix . 'shipping_' . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
                $wpdb->query("TRUNCATE TABLE $table_name");
                foreach ($rows as $row) {
                    $wpdb->insert($table_name, $row);
                }
            }
        }
        return true;
    }

    public static function add_shipment($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_shipments';
        $res = $wpdb->insert($table, array(
            'shipment_number' => $data['shipment_number'],
            'customer_id' => intval($data['customer_id']),
            'origin' => sanitize_text_field($data['origin']),
            'destination' => sanitize_text_field($data['destination']),
            'weight' => floatval($data['weight']),
            'dimensions' => sanitize_text_field($data['dimensions']),
            'classification' => sanitize_text_field($data['classification']),
            'status' => sanitize_text_field($data['status'] ?? 'pending'),
            'pickup_date' => $data['pickup_date'] ?: null,
            'dispatch_date' => $data['dispatch_date'] ?: null,
            'delivery_date' => $data['delivery_date'] ?: null,
            'carrier_id' => intval($data['carrier_id'] ?? 0),
            'route_id' => intval($data['route_id'] ?? 0)
        ));
        if ($res) {
            $id = $wpdb->insert_id;
            self::log_shipment_event($id, $data['status'] ?? 'pending', 'Shipment created');
            return $id;
        }
        return false;
    }

    public static function update_shipment($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_shipments';
        $old_shipment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);

        $res = $wpdb->update($table, $data, array('id' => $id));
        if ($res !== false) {
            foreach ($data as $key => $val) {
                if (isset($old_shipment[$key]) && $old_shipment[$key] != $val) {
                    self::add_shipment_audit_log($id, "Updated $key", $old_shipment[$key], $val);
                }
            }
            if (isset($data['status'])) {
                self::log_shipment_event($id, $data['status'], 'Status updated');

                // Trigger Automated Alert
                $shipment = self::get_shipment_with_tracking($id);
                if ($shipment && $shipment->customer_id) {
                    $customer = $wpdb->get_row($wpdb->prepare("SELECT email, name FROM {$wpdb->prefix}shipping_customers WHERE id = %d", $shipment->customer_id));
                    if ($customer && $customer->email) {
                        // Note: Shipping_Notifications::send_template_notification typically expects a customer_id
                        // For shipping alerts, we use a custom mailer or ensure IDs map correctly.
                        // Here we simulate the notification process for the customer.
                        $shipping_info = Shipping_Settings::get_shipping_info();
                        $subject = "تحديث حالة الشحنة: " . $shipment->shipment_number;
                        $message = "عزيزي العميل " . $customer->name . ",\n\nتم تحديث حالة شحنتكم رقم " . $shipment->shipment_number . " إلى: " . $data['status'];
                        wp_mail($customer->email, $subject, $message);
                    }
                }
            }
            return true;
        }
        return false;
    }

    public static function log_shipment_event($shipment_id, $status, $description = '', $location = '') {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_shipment_tracking_events', array(
            'shipment_id' => intval($shipment_id),
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'created_at' => current_time('mysql')
        ));
    }

    public static function add_shipment_audit_log($shipment_id, $action, $old_val = '', $new_val = '') {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_shipment_logs', array(
            'shipment_id' => intval($shipment_id),
            'user_id' => get_current_user_id(),
            'action' => $action,
            'old_value' => is_array($old_val) ? json_encode($old_val) : $old_val,
            'new_value' => is_array($new_val) ? json_encode($new_val) : $new_val,
            'created_at' => current_time('mysql')
        ));
    }

    public static function get_shipment_with_tracking($id_or_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_shipments';
        if (is_numeric($id_or_number)) {
            $shipment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id_or_number));
        } else {
            $shipment = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE shipment_number = %s", $id_or_number));
        }

        if ($shipment) {
            $id = $shipment->id;
            $shipment->events = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}shipping_shipment_tracking_events WHERE shipment_id = %d ORDER BY created_at DESC",
                $id
            ));
        }
        return $shipment;
    }

    public static function bulk_add_shipments($rows) {
        $count = 0;
        foreach ($rows as $row) {
            if (self::add_shipment($row)) $count++;
        }
        return $count;
    }

    public static function archive_shipment($id) {
        return self::update_shipment($id, array('is_archived' => 1));
    }

    public static function create_invoice($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_invoices';
        $res = $wpdb->insert($table, array(
            'invoice_number' => 'INV-' . strtoupper(wp_generate_password(8, false)),
            'customer_id' => intval($data['customer_id']),
            'order_id' => intval($data['order_id'] ?? 0),
            'subtotal' => floatval($data['subtotal']),
            'tax_amount' => floatval($data['tax_amount'] ?? 0),
            'discount_amount' => floatval($data['discount_amount'] ?? 0),
            'total_amount' => floatval($data['total_amount']),
            'items_json' => $data['items_json'],
            'due_date' => $data['due_date'],
            'status' => 'unpaid',
            'is_recurring' => intval($data['is_recurring'] ?? 0),
            'billing_interval' => sanitize_text_field($data['billing_interval'] ?? '')
        ));
        if ($res) {
            $id = $wpdb->insert_id;
            self::log_billing_event($id, 'Invoice Created', floatval($data['total_amount']));
            return $id;
        }
        return false;
    }

    public static function record_payment($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_payments';
        $res = $wpdb->insert($table, array(
            'invoice_id' => intval($data['invoice_id']),
            'transaction_id' => sanitize_text_field($data['transaction_id']),
            'amount_paid' => floatval($data['amount_paid']),
            'payment_method' => sanitize_text_field($data['payment_method']),
            'payment_status' => 'completed',
            'payment_date' => current_time('mysql')
        ));
        if ($res) {
            $invoice_id = intval($data['invoice_id']);
            $wpdb->update($wpdb->prefix . 'shipping_invoices', array('status' => 'paid'), array('id' => $invoice_id));
            self::log_billing_event($invoice_id, 'Payment Received', floatval($data['amount_paid']));
            return true;
        }
        return false;
    }

    public static function get_receivables() {
        global $wpdb;
        return $wpdb->get_results("SELECT i.*, c.name as customer_name FROM {$wpdb->prefix}shipping_invoices i JOIN {$wpdb->prefix}shipping_customers c ON i.customer_id = c.id WHERE i.status != 'paid' ORDER BY i.due_date ASC");
    }

    public static function get_revenue_stats() {
        global $wpdb;
        $stats = array();
        $stats['daily'] = $wpdb->get_results("SELECT DATE(payment_date) as date, SUM(amount_paid) as total FROM {$wpdb->prefix}shipping_payments GROUP BY DATE(payment_date) LIMIT 30");
        $stats['monthly'] = $wpdb->get_results("SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount_paid) as total FROM {$wpdb->prefix}shipping_payments GROUP BY month LIMIT 12");
        return $stats;
    }

    public static function log_billing_event($invoice_id, $action, $amount = 0, $details = '') {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_billing_logs', array(
            'invoice_id' => intval($invoice_id),
            'user_id' => get_current_user_id(),
            'action' => $action,
            'amount' => $amount,
            'details' => $details,
            'created_at' => current_time('mysql')
        ));
    }




    // Ticketing System Methods
    public static function create_ticket($data) {
        global $wpdb;
        $res = $wpdb->insert("{$wpdb->prefix}shipping_tickets", array(
            'customer_id' => intval($data['customer_id']),
            'subject' => sanitize_text_field($data['subject']),
            'category' => sanitize_text_field($data['category']),
            'priority' => sanitize_text_field($data['priority'] ?? 'medium'),
            'status' => 'open',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ));
        if ($res) {
            $ticket_id = $wpdb->insert_id;
            // Add initial message to thread
            self::add_ticket_reply(array(
                'ticket_id' => $ticket_id,
                'sender_id' => get_current_user_id(),
                'message' => $data['message'],
                'file_url' => $data['file_url'] ?? null
            ));
            return $ticket_id;
        }
        return false;
    }

    public static function add_ticket_reply($data) {
        global $wpdb;
        $res = $wpdb->insert("{$wpdb->prefix}shipping_ticket_thread", array(
            'ticket_id' => intval($data['ticket_id']),
            'sender_id' => intval($data['sender_id']),
            'message' => sanitize_textarea_field($data['message']),
            'file_url' => $data['file_url'] ?? null,
            'created_at' => current_time('mysql')
        ));
        if ($res) {
            $wpdb->update("{$wpdb->prefix}shipping_tickets", array('updated_at' => current_time('mysql')), array('id' => intval($data['ticket_id'])));
            return $wpdb->insert_id;
        }
        return false;
    }

    public static function get_tickets($args = array()) {
        global $wpdb;
        $user = wp_get_current_user();
        $is_customer = in_array('subscriber', $user->roles);

        $where = "1=1";
        $params = array();

        if ($is_customer) {
            // Find customer_id from wp_user_id
            $customer_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));
            $where .= " AND t.customer_id = %d";
            $params[] = intval($customer_id);
        }

        if (!empty($args['status'])) {
            $where .= " AND t.status = %s";
            $params[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['category'])) {
            $where .= " AND t.category = %s";
            $params[] = sanitize_text_field($args['category']);
        }

        if (!empty($args['priority'])) {
            $where .= " AND t.priority = %s";
            $params[] = sanitize_text_field($args['priority']);
        }

        if (!empty($args['search'])) {
            $s = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= " AND (t.subject LIKE %s OR m.name LIKE %s)";
            $params[] = $s;
            $params[] = $s;
        }

        $query = "SELECT t.*, CONCAT(m.first_name, ' ', m.last_name) as customer_name, m.photo_url as customer_photo
                  FROM {$wpdb->prefix}shipping_tickets t
                  JOIN {$wpdb->prefix}shipping_customers m ON t.customer_id = m.id
                  WHERE $where
                  ORDER BY t.updated_at DESC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }
        return $wpdb->get_results($query);
    }

    public static function get_ticket($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, CONCAT(m.first_name, ' ', m.last_name) as customer_name, m.phone as customer_phone
             FROM {$wpdb->prefix}shipping_tickets t
             JOIN {$wpdb->prefix}shipping_customers m ON t.customer_id = m.id
             WHERE t.id = %d",
            $id
        ));
    }

    public static function get_ticket_thread($ticket_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT tr.*, u.display_name as sender_name
             FROM {$wpdb->prefix}shipping_ticket_thread tr
             LEFT JOIN {$wpdb->base_prefix}users u ON tr.sender_id = u.ID
             WHERE tr.ticket_id = %d
             ORDER BY tr.created_at ASC",
            $ticket_id
        ));
    }

    public static function update_ticket_status($id, $status) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}shipping_tickets", array('status' => $status), array('id' => $id));
    }

    // Page Customization Methods
    public static function get_pages() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_pages ORDER BY id ASC");
    }

    public static function get_page_by_shortcode($shortcode) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_pages WHERE shortcode = %s", $shortcode));
    }

    public static function update_page($id, $data) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}shipping_pages", [
            'title' => sanitize_text_field($data['title']),
            'instructions' => sanitize_textarea_field($data['instructions']),
            'settings' => $data['settings']
        ], ['id' => intval($id)]);
    }

    // Article Management Methods
    public static function add_article($data) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}shipping_articles", [
            'title' => sanitize_text_field($data['title']),
            'content' => wp_kses_post($data['content']),
            'image_url' => esc_url_raw($data['image_url'] ?? ''),
            'author_id' => get_current_user_id(),
            'status' => $data['status'] ?? 'publish',
            'created_at' => current_time('mysql')
        ]);
    }

    public static function get_articles($limit = 10) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_articles WHERE status = 'publish' ORDER BY created_at DESC LIMIT %d", $limit));
    }

    public static function delete_article($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}shipping_articles", ['id' => intval($id)]);
    }

    // Global Alert System Methods
    public static function save_alert($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'shipping_alerts';
        $insert_data = [
            'title' => sanitize_text_field($data['title']),
            'message' => wp_kses_post($data['message']),
            'severity' => sanitize_text_field($data['severity']),
            'must_acknowledge' => !empty($data['must_acknowledge']) ? 1 : 0,
            'status' => sanitize_text_field($data['status'] ?? 'active')
        ];

        if (!empty($data['id'])) {
            return $wpdb->update($table, $insert_data, ['id' => intval($data['id'])]);
        }
        return $wpdb->insert($table, $insert_data);
    }

    public static function get_alerts($args = []) {
        global $wpdb;
        $where = "1=1";
        if (!empty($args['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_alerts WHERE $where ORDER BY created_at DESC");
    }

    public static function get_alert($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_alerts WHERE id = %d", $id));
    }

    public static function delete_alert($id) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}shipping_alert_views", ['alert_id' => intval($id)]);
        return $wpdb->delete("{$wpdb->prefix}shipping_alerts", ['id' => intval($id)]);
    }

    public static function get_active_alerts_for_user($user_id) {
        global $wpdb;
        // Fetch active alerts that the user hasn't acknowledged yet (if acknowledgment is required)
        // or just all active alerts if they haven't seen them.
        // Actually, requirement says "immediately for logged-in users".
        // We should track which ones are seen.

        return $wpdb->get_results($wpdb->prepare("
            SELECT a.*
            FROM {$wpdb->prefix}shipping_alerts a
            LEFT JOIN {$wpdb->prefix}shipping_alert_views v ON a.id = v.alert_id AND v.user_id = %d
            WHERE a.status = 'active'
            AND v.id IS NULL
        ", $user_id));
    }

    public static function acknowledge_alert($alert_id, $user_id) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}shipping_alert_views", [
            'alert_id' => intval($alert_id),
            'user_id' => intval($user_id),
            'acknowledged' => 1,
            'created_at' => current_time('mysql')
        ]);
    }

    public static function add_route($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_logistics', array(
            'route_name' => sanitize_text_field($data['route_name']),
            'stop_points' => sanitize_textarea_field($data['stop_points'] ?? ''),
            'fleet_details' => sanitize_textarea_field($data['fleet_details'] ?? ''),
            'warehouse_info' => sanitize_textarea_field($data['warehouse_info'] ?? '')
        ));
    }

    public static function add_customs_entry($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_customs', array(
            'shipment_id' => intval($data['shipment_id']),
            'documentation_status' => sanitize_text_field($data['documentation_status']),
            'duties_amount' => floatval($data['duties_amount']),
            'clearance_status' => sanitize_text_field($data['clearance_status'])
        ));
    }

    public static function add_pricing_rule($data) {
        global $wpdb;
        return $wpdb->insert($wpdb->prefix . 'shipping_pricing', array(
            'service_name' => sanitize_text_field($data['service_name']),
            'base_cost' => floatval($data['base_cost']),
            'additional_fees' => floatval($data['additional_fees']),
            'special_offer_details' => sanitize_textarea_field($data['special_offer_details'] ?? '')
        ));
    }
}
}
