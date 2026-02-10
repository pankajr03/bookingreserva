<?php
/**
 * Plugin Name: Booknetic - Collaborative Services
 * Plugin URI:  https://github.com/pankajr03/booknetic-add-on
 * Description: Add Collaborative Services menu to Booknetic Settings
 * Version:     1.0.0
 * Author:      Pankaj Kumar
 * Text Domain: bkntc-collab
 */

defined('ABSPATH') || exit;

// Constants
define('BKNTCCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKNTCCS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load plugin logger if present
if (file_exists(BKNTCCS_PLUGIN_DIR . 'app/Helpers/logger.php')) {
    require_once BKNTCCS_PLUGIN_DIR . 'app/Helpers/logger.php';
}

final class BookneticCollaborativeServices {

    private static $instance = null;
    private bool $tenantAllowed = true;
    private $collaborative_enabled_for_tenant = 0;
    private $guest_info_required_for_tenant = 0;
    private $backend_slug = 'booknetic';
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function backend_init() {
        if (class_exists('\BookneticSaaS\Providers\Helpers\Helper')) {
            $this->backend_slug = \BookneticSaaS\Providers\Helpers\Helper::getOption('backend_slug', 'booknetic');
        } else {
            $this->backend_slug = \BookneticApp\Providers\Helpers\Helper::getOption('backend_slug', 'booknetic');
        }
            
        add_action('bkntc_init_backend', [$this, 'register_routes']);
        // Register settings submenu only after Booknetic backend is initialized,
        // so SaaS capability filters are active and tenant context is available.
        // Settings -> General Settings -> Collaborative Services
        add_action('admin_init', [$this, 'add_menu_item']);

        // Also log request params early to capture module/view when admin page runs
        add_action('admin_init', [$this, 'log_request_params'], 5);
        
        // Intercept Booknetic AJAX settings requests
        add_action('admin_init', [$this, 'maybe_handle_booknetic_ajax'], 1);
        
        // Initialize Service Category Collaborative features
        add_action('admin_init', [$this, 'init_service_category_collaborative'], 5);
        add_action('admin_init', [$this, 'init_service_collaborative'], 6);
        
        // Service Collaborative features [Register AJAX handlers directly]
        add_action('wp_ajax_bkntc_collab_save_category_settings', [$this, 'ajax_save_category_settings']);
        add_action('wp_ajax_bkntc_collab_get_category_settings', [$this, 'ajax_get_category_settings']);
        
        // Service Collaborative features
        // add_action('admin_enqueue_scripts', [$this, 'enqueue_service_assets']);
        add_action('wp_ajax_bkntc_collab_get_service_settings', [$this, 'ajax_get_service_settings']);
        add_action('wp_ajax_bkntc_collab_save_service_settings', [$this, 'ajax_save_service_settings']);
        
    }

    public function init() {
        
        // Ensure core Booknetic is available before proceeding
        if (!class_exists('BookneticApp\Providers\UI\SettingsMenuUI')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>Booknetic must be installed and active.</p></div>';
            });
            return;
        }

        // 1) Register a tenant capability so it appears in SaaS Plans -> Permissions
        //    This makes "Collaborative Services" selectable per plan without editing SaaS files.
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities')) {
            \BookneticApp\Providers\Core\Capabilities::registerTenantCapability(
                'collaborative_services',
                function_exists('bkntc__') ? bkntc__('Collaborative Services') : __('Collaborative Services', $this->backend_slug), 
            );

            // 2) Gate all features by plan permission (tenants without permission won't load plugin features)
            $this->tenantAllowed = \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services');
        }

        // If tenant doesn't have permission, skip registering routes/menus/assets.
        // Capability will still be visible in the Plans UI for admins to assign.
        if (!$this->tenantAllowed) {
            return;
        }

        $this->backend_init();
        
        // Register AJAX handlers directly
        add_action('wp_ajax_bkntc_collab_get_staff_list', [$this, 'ajax_get_staff_list']);
        
        // Hook into Booknetic service save to persist collab fields
        add_filter('bkntc_service_insert_data', [$this, 'filter_service_save_data'], 10, 1);
        
        // Register Appointment AJAX handlers
        add_action('wp_ajax_bkntc_collab_get_appointment_staff', [$this, 'ajax_get_appointment_staff']);
        add_action('wp_ajax_bkntc_collab_save_appointment_staff', [$this, 'ajax_save_appointment_staff']);
        add_action('wp_ajax_bkntc_collab_get_category_rules', [$this, 'ajax_get_category_rules']);
        
        // Enqueue appointment assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_appointment_assets']);
        
        // Frontend booking panel hooks
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_booking_assets']);
        add_action('wp_ajax_bkntc_collab_get_frontend_category_rules', [$this, 'ajax_get_frontend_category_rules']);
        add_action('wp_ajax_nopriv_bkntc_collab_get_frontend_category_rules', [$this, 'ajax_get_frontend_category_rules']);
        add_action('wp_ajax_bkntc_collab_get_category_settings_frontend', [$this, 'ajax_get_category_settings_frontend']);
        add_action('wp_ajax_nopriv_bkntc_collab_get_category_settings_frontend', [$this, 'ajax_get_category_settings_frontend']);
        add_action('wp_ajax_bkntc_collab_get_service_category', [$this, 'ajax_get_service_category']);
        add_action('wp_ajax_nopriv_bkntc_collab_get_service_category', [$this, 'ajax_get_service_category']);
        
        // AJAX handler for getting available staff for datetime
        add_action('wp_ajax_bkntc_collab_get_available_staff', [$this, 'ajax_get_available_staff']);
        add_action('wp_ajax_nopriv_bkntc_collab_get_available_staff', [$this, 'ajax_get_available_staff']);
        
        // AJAX handler for loading combined datetime-staff step
        add_action('wp_ajax_bkntc_collab_load_combined_step', [$this, 'ajax_load_combined_step']);
        add_action('wp_ajax_nopriv_bkntc_collab_load_combined_step', [$this, 'ajax_load_combined_step']);
        
        // Modify staff step rendering
        add_filter('bkntc_booking_panel_render_staff_info', [$this, 'modify_staff_step_output'], 10, 1);
        
        // Register custom combined DateTime-Staff step
        // Enqueue admin script for step injection
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_booking_steps_script']);
        
        // Hook into appointment creation to handle collaborative bookings
        add_action('bkntc_booking_step_confirmation_validation', [$this, 'process_collaborative_booking'], 10);

        // Ensure collaborative_group_id is injected before DB insert
        add_filter('bkntc_appointment_insert_data', [$this, 'add_collaborative_group_on_insert'], 10, 2);
        
        // Admin appointments list - Show collaborative booking groups
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_appointments_list_script']);
        add_action('admin_footer', [$this, 'enqueue_admin_appointments_list_fallback']);
        add_filter('bkntc_datatable_after_render', [$this, 'modify_appointments_datatable_html'], 10, 2);
        add_action('wp_ajax_bkntc_collab_get_appointment_groups', [$this, 'ajax_get_appointment_groups']);
    }
    
    /**
     * Enqueue script for admin appointments list to show collaborative groups
     */
    public function enqueue_admin_appointments_list_script($hook) {
        // Debug logging
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('enqueue_admin_appointments_list_script called - hook: ' . $hook);
            bkntc_cs_log('GET params - page: ' . (isset($_GET['page']) ? $_GET['page'] : 'not set') . ', module: ' . (isset($_GET['module']) ? $_GET['module'] : 'not set'));
        }
        
        // Only on Booknetic appointments page
        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Not booknetic page, skipping');
            }
            return;
        }
        
        if (!isset($_GET['module']) || $_GET['module'] !== 'appointments') {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Not appointments module, skipping');
            }
            return;
        }
        
        // Enqueue CSS for collaborative group badges
        $css_path = plugin_dir_path(__FILE__) . 'assets/css/admin-appointments-badges.css';
        $css_url = plugin_dir_url(__FILE__) . 'assets/css/admin-appointments-badges.css';
        
        wp_enqueue_style(
            'booknetic-collab-admin-appointments-badges',
            $css_url,
            [],
            filemtime($css_path)
        );
        
        $script_path = plugin_dir_path(__FILE__) . 'assets/js/admin-appointments-list.js';
        $script_url = plugin_dir_url(__FILE__) . 'assets/js/admin-appointments-list.js';
        
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('Enqueuing appointments list script: ' . $script_url);
            bkntc_cs_log('Script exists: ' . (file_exists($script_path) ? 'yes' : 'no'));
        }
        
        wp_enqueue_script(
            'booknetic-collab-admin-appointments-list',
            $script_url,
            ['jquery'],
            filemtime($script_path),
            true
        );
        
        // Add inline script to confirm loading
        wp_add_inline_script('booknetic-collab-admin-appointments-list', 
            'console.log("✓ Collaborative Appointments List Script Loaded");', 
            'before'
        );
    }
    
    /**
     * Fallback to inject script directly in footer if enqueue didn't work
     */
    public function enqueue_admin_appointments_list_fallback() {
        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) {
            return;
        }
        
        if (!isset($_GET['module']) || $_GET['module'] !== 'appointments') {
            return;
        }
        
        // Check if script was already enqueued
        if (wp_script_is('booknetic-collab-admin-appointments-list', 'enqueued')) {
            return;
        }
        
        $script_url = plugin_dir_url(__FILE__) . 'assets/js/admin-appointments-list.js';
        $version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/admin-appointments-list.js');
        
        ?>
        <script>
        console.log('🔧 Loading Collaborative Appointments List Script (Footer Fallback)');
        </script>
        <script src="<?php echo esc_url($script_url); ?>?ver=<?php echo $version; ?>"></script>
        <?php
    }
    
    /**
     * AJAX handler to get collaborative group data for appointments
     */
    public function ajax_get_appointment_groups() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        
        $appointment_ids = isset($_POST['appointment_ids']) ? array_map('intval', $_POST['appointment_ids']) : [];
        
        if (empty($appointment_ids)) {
            wp_send_json_success([]);
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bkntc_appointments';
        
        // Get collaborative group IDs for these appointments
        $placeholders = implode(',', array_fill(0, count($appointment_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT id, collaborative_group_id FROM {$table} WHERE id IN ($placeholders) AND collaborative_group_id IS NOT NULL AND collaborative_group_id != ''",
            $appointment_ids
        );
        
        $appointments = $wpdb->get_results($query, ARRAY_A);
        
        $result = [];
        
        foreach ($appointments as $appt) {
            $group_id = $appt['collaborative_group_id'];
            
            // Get all appointments in this group
            $group_appointments = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM {$table} WHERE collaborative_group_id = %s ORDER BY id ASC",
                $group_id
            ), ARRAY_A);
            
            // Find the index of this appointment in the group
            $index = 1;
            foreach ($group_appointments as $idx => $group_appt) {
                if ($group_appt['id'] == $appt['id']) {
                    $index = $idx + 1;
                    break;
                }
            }
            
            $result[$appt['id']] = [
                'group_id' => $group_id,
                'index' => $index,
                'total' => count($group_appointments)
            ];
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Modify appointments DataTable to add collaborative group indicators to the ID column
     */
    public function modify_appointments_datatable_html($data, $dataTable) {
        // Debug: Log that filter is called
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('modify_appointments_datatable_html called - Module: ' . ($dataTable->getModule() ?: 'null'));
            bkntc_cs_log('Data is array: ' . (is_array($data) ? 'yes' : 'no'));
        } else {
            // Fallback to PHP error_log so we see messages in wp-content/debug.log
            if (function_exists('error_log')) {
                error_log('[bkntc-collab] modify_appointments_datatable_html called - Module: ' . ($dataTable->getModule() ?: 'null'));
                error_log('[bkntc-collab] Data is array: ' . (is_array($data) ? 'yes' : 'no'));
            }
        }
        
        if (!is_array($data)) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Data is not array, skipping');
            } else {
                error_log('[bkntc-collab] Data is not array, skipping');
            }
            return $data;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'bkntc_appointments';
        
        try {
            // Ensure tbody exists
            // Get all appointments with collaborative_group_id
            if (!isset($data['tbody']) || !is_array($data['tbody'])) {
                if (function_exists('bkntc_cs_log')) {
                    bkntc_cs_log('No tbody in data');
                } else {
                    error_log('[bkntc-collab] No tbody in data');
                }
                return $data;
            }

            // Collect appointment IDs displayed on current page
            $page_ids = [];
            foreach ($data['tbody'] as $row) {
                if (isset($row['id'])) {
                    $page_ids[] = (int)$row['id'];
                }
            }

            if (empty($page_ids)) {
                if (function_exists('bkntc_cs_log')) {
                    bkntc_cs_log('No page IDs found');
                } else {
                    error_log('[bkntc-collab] No page IDs found');
                }
                return $data;
            }

            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Page IDs: ' . implode(',', $page_ids));
            } else {
                error_log('[bkntc-collab] Page IDs: ' . implode(',', $page_ids));
            }

            // Fetch collaborative group ids only for displayed appointments
            $placeholders = implode(',', array_fill(0, count($page_ids), '%d'));
            $sql = $wpdb->prepare(
                "SELECT id, collaborative_group_id FROM {$table} WHERE id IN ($placeholders) AND collaborative_group_id IS NOT NULL AND collaborative_group_id != ''",
                $page_ids
            );
            $appointments = $wpdb->get_results($sql, ARRAY_A);
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Found ' . count($appointments) . ' appointments with collaborative_group_id');
            }
            
            if (empty($appointments)) {
                return $data;
            }
            
            // Build a map of appointment IDs with their collaborative group info
            $collab_info = [];
            
            foreach ($appointments as $appt) {
                $group_id = $appt['collaborative_group_id'];
                
                // Get all appointments in this group if not cached
                if (!isset($collab_info[$group_id])) {
                    $group_appointments = $wpdb->get_results($wpdb->prepare(
                        "SELECT id FROM {$table} WHERE collaborative_group_id = %s ORDER BY id ASC",
                        $group_id
                    ), ARRAY_A);
                    
                    if (!empty($group_appointments)) {
                        $collab_info[$group_id] = [];
                        foreach ($group_appointments as $idx => $g_appt) {
                            $appt_id_key = (int) $g_appt['id'];
                            $collab_info[$group_id][$appt_id_key] = [
                                'index' => $idx + 1,
                                'total' => count($group_appointments)
                            ];
                        }
                    }
                }
            }
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Collab info built for groups: ' . implode(',', array_keys($collab_info)));
            } else {
                error_log('[bkntc-collab] Collab info groups: ' . implode(',', array_keys($collab_info)));
            }
            
            // Check if data has tbody
            if (!isset($data['tbody']) || !is_array($data['tbody'])) {
                if (function_exists('bkntc_cs_log')) {
                    bkntc_cs_log('No tbody in data');
                }
                return $data;
            }
            
            // Modify tbody rows - add badges to ID column (first column in 'data')
            $modified_count = 0;
            foreach ($data['tbody'] as $row_index => $row) {
                if (!is_array($row) || empty($row['data']) || !is_array($row['data'])) {
                    continue;
                }

                // Appointment ID is provided separately in each row
                $appt_id = isset($row['id']) ? (int)$row['id'] : 0;
                if ($appt_id <= 0) {
                    continue;
                }

                // First column content
                $firstCellContent = isset($row['data'][0]['content']) ? (string)$row['data'][0]['content'] : '';

                // Append badge if collaborative info exists for this appointment
                foreach ($collab_info as $group_id => $appointments_in_group) {
                    if (isset($appointments_in_group[$appt_id])) {
                        $info = $appointments_in_group[$appt_id];
                        // Inline visible marker to confirm execution
                        $badge_html = ' <span class="collab-group-badge" title="Part of collaborative booking group: ' . (int)$info['index'] . '/' . (int)$info['total'] . '">👥 ' . (int)$info['index'] . '/' . (int)$info['total'] . '</span>';
                        $marker_html = ' <span class="text-muted" style="font-size:10px">/*collab*/</span>';

                        $data['tbody'][$row_index]['data'][0]['content'] = $firstCellContent . $badge_html . $marker_html;
                        $modified_count++;

                        if (function_exists('bkntc_cs_log')) {
                            bkntc_cs_log('Added badge for appointment ' . $appt_id);
                        } else {
                            error_log('[bkntc-collab] Added badge for appointment ' . $appt_id);
                        }
                        break;
                    }
                }
            }
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Modified ' . $modified_count . ' appointments with badges');
            } else {
                error_log('[bkntc-collab] Modified ' . $modified_count . ' appointments with badges');
            }
            
        } catch (Exception $e) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Error in modify_appointments_datatable_html: ' . $e->getMessage());
            }
        }
        
        return $data;
    }
    
    public function ajax_get_staff_list() {
        bkntc_cs_log('ajax_get_staff_list called - Direct registration');
        
        check_ajax_referer('bkntc_collab_category_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            bkntc_cs_log('ajax_get_staff_list - Permission denied');
            wp_send_json_error(['message' => 'Permission denied']);
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'bkntc_staff';
        
        bkntc_cs_log('Querying table: ' . $table_name);

        $staff = $wpdb->get_results(
            "SELECT id, name FROM {$table_name} WHERE is_active = 1 ORDER BY name ASC",
            ARRAY_A
        );
        
        if ($wpdb->last_error) {
            bkntc_cs_log('Database error: ' . $wpdb->last_error);
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
            return;
        }
        
        bkntc_cs_log('ajax_get_staff_list - Found ' . count($staff) . ' staff members');

        wp_send_json_success($staff);
    }
    
    public function ajax_save_category_settings() {
        // Verify request nonce but don't die; allow admins without nonce (modal may be loaded via AJAX)
        $nonce_ok = check_ajax_referer('bkntc_collab_category_nonce', 'nonce', false);
        // if (!$nonce_ok && !current_user_can('manage_options')) {
        //     wp_send_json_error(['message' => 'Invalid nonce']);
        //     return;
        // }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $allow_multi_select = isset($_POST['allow_multi_select']) ? intval($_POST['allow_multi_select']) : null;
        $service_selection_limit = isset($_POST['service_selection_limit']) ? intval($_POST['service_selection_limit']) : null;
        
        if ($category_id <= 0) {
            wp_send_json_error(['message' => 'Invalid category ID']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bkntc_service_categories';
        
        // Build update array dynamically based on what was sent
        $update_data = [];
        $update_format = [];
        
        if ($allow_multi_select !== null) {
            $update_data['allow_multi_select'] = $allow_multi_select;
            $update_format[] = '%d';
        }
        
        if ($service_selection_limit !== null) {
            $update_data['service_selection_limit'] = $service_selection_limit;
            $update_format[] = '%d';
        }
        
        if (empty($update_data)) {
            wp_send_json_error(['message' => 'No data to save']);
        }
        
        // Save to database table
        $result = $wpdb->update(
            $table,
            $update_data,
            ['id' => $category_id],
            $update_format,
            ['%d']
        );

        // Rows affected (may be 0 if values unchanged)
        $rows_affected = $wpdb->rows_affected;
        $db_error = $wpdb->last_error;

        if ($result !== false) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Saved collaborative category settings for category ' . $category_id . ': ' . json_encode($update_data) . ' - rows_affected: ' . intval($rows_affected));
            }

            // Return a structured success response the JS expects
            wp_send_json_success([
                'message' => 'Category service settings saved successfully',
                'settings' => $update_data,
                'updated_rows' => intval($rows_affected),
                'db_error' => $db_error,
                'id' => $category_id
            ]);
        } else {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Failed to save collaborative settings for category ' . $category_id . ': ' . $db_error);
            }
            wp_send_json_error(['message' => 'Database error Coming', 'db_error' => $db_error]);
        }
        
                
    }
    
    public function ajax_get_category_settings() {
        // check_ajax_referer('bkntc_collab_category_nonce', 'nonce');
        
        // if (!current_user_can('manage_options')) {
        //     wp_send_json_error(['message' => 'Permission denied']);
        // }

        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

        if ($category_id <= 0) {
            wp_send_json_success([
                'allow_multi_select' => 0,
                'service_selection_limit' => 1
            ]);
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bkntc_service_categories';
        
        $category = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT allow_multi_select, service_selection_limit, name FROM {$table} WHERE id = %d",
                $category_id
            ),
            ARRAY_A
        );
        if ($category) {
            $settings = [
                'service_selection_limit' => intval($category['service_selection_limit']) ?? 0,
                'category_name' => $category['name'],
                'allow_multi_select' => intval($category['allow_multi_select'])
            ];
        } else {
            $settings = [
                'allow_multi_select' => 0,
                'category_name' => '',
                'service_selection_limit' => 0
              
            ];
        }

        bkntc_cs_log('Retrieved collaborative settings for category ' . $category_id . ': ' . json_encode($settings));

        wp_send_json_success($settings);
    }
    
    // Appointment Multi-Staff AJAX Handlers
    public function ajax_get_appointment_staff() {
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('ajax_get_appointment_staff called');
        }
        
        check_ajax_referer('bkntc_collab_appointment_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;

        if ($appointment_id <= 0) {
            wp_send_json_success([
                'primary_staff_id' => 0,
                'staff_ids' => []
            ]);
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bkntc_appointments';

        $appointment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT staff_id, collab_staff_ids FROM {$table} WHERE id = %d",
                $appointment_id
            ),
            ARRAY_A
        );

        if ($appointment) {
            $staff_ids = $appointment['collab_staff_ids'] ? json_decode($appointment['collab_staff_ids'], true) : [];
            
            // If no collaborative staff, use the single staff_id
            if (empty($staff_ids) && !empty($appointment['staff_id'])) {
                $staff_ids = [intval($appointment['staff_id'])];
            }

            $result = [
                'primary_staff_id' => intval($appointment['staff_id']),
                'staff_ids' => $staff_ids
            ];
        } else {
            $result = [
                'primary_staff_id' => 0,
                'staff_ids' => []
            ];
        }

        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('Retrieved appointment staff for ID ' . $appointment_id . ': ' . json_encode($result));
        }

        wp_send_json_success($result);
    }

    public function ajax_save_appointment_staff() {
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('ajax_save_appointment_staff called');
        }

        check_ajax_referer('bkntc_collab_appointment_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
        $staff_ids = isset($_POST['staff_ids']) ? array_map('intval', (array)$_POST['staff_ids']) : [];

        if ($appointment_id <= 0) {
            wp_send_json_error(['message' => 'Invalid appointment ID']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bkntc_appointments';

        // Primary staff is the first selected staff
        $primary_staff_id = !empty($staff_ids) ? $staff_ids[0] : 0;

        // Update appointment with primary staff and all staff IDs
        $result = $wpdb->update(
            $table,
            [
                'staff_id' => $primary_staff_id,
                'collab_staff_ids' => json_encode($staff_ids)
            ],
            ['id' => $appointment_id],
            ['%d', '%s'],
            ['%d']
        );

        if ($result !== false) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Saved appointment staff for ID ' . $appointment_id . ': primary=' . $primary_staff_id . ', all=' . json_encode($staff_ids));
            }
            wp_send_json_success(['message' => 'Staff saved successfully']);
        } else {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Failed to save appointment staff for ID ' . $appointment_id . ': ' . $wpdb->last_error);
            }
            wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
        }
    }

    public function init_service_collaborative() {
        $user_id = null;
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID) {
            // Try user meta first (common in SaaS)
            $user_id = $current_user->ID;
        }


        // Fetch settings from tenants table if tenant_id is available
        $collaborative_enabled = 0; // Default
        $guest_info_required = 0; // Default

        if ($user_id) {
            global $wpdb;
            
            if (class_exists('BookneticSaaS\Providers\Helpers\Helper')) {
                $tenants_table = $wpdb->prefix . 'bkntc_tenants';
            
                $settings = $wpdb->get_row($wpdb->prepare(
                    "SELECT collaborative_enabled, guest_info_required FROM {$tenants_table} WHERE user_id = %d",
                    $user_id
                ), ARRAY_A);
                
                if ($settings) {
                    $collaborative_enabled = $settings['collaborative_enabled'] ? $settings['collaborative_enabled'] : 0; // Convert to string for select
                    $guest_info_required = $settings['guest_info_required'] ? $settings['guest_info_required'] : 0;
                }
            } else {
                $collaborative_enabled = get_option('bkntc_collaborative_services_enabled', 0) ? 1 : 0;
                $guest_info_required = get_option('bkntc_collaborative_guest_info_required', 0) ? 1 : 0; 
            }
            
        }

        $this->tenantAllowed = \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services');
        $this->collaborative_enabled_for_tenant = $collaborative_enabled;
        
        if (!$this->tenantAllowed || !$this->collaborative_enabled_for_tenant ) {
            return;
        }
        // Don't output scripts during AJAX/XHR requests or on table-refresh calls
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }
        // fs-data-table is used by Booknetic to request table data — avoid output in that case
        if ((isset($_REQUEST['fs-data-table']) && $_REQUEST['fs-data-table']) || (isset($_POST['fs-data-table']) && $_POST['fs-data-table'])) {
            return;
        }

        // Direct output for services module - bypass WordPress hooks
        // Only output on actual page loads, never during AJAX or XHR
        if (isset($_GET['module']) && $_GET['module'] === 'services' && !isset($_GET['ajax'])) {
            $js_file = BKNTCCS_PLUGIN_URL . 'assets/js/service-collaborative.js';
            $css_file = BKNTCCS_PLUGIN_URL . 'assets/css/service-collaborative.css';

            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Direct output collaborative scripts for services');
            }

            echo '<!-- Collaborative Service Scripts via Direct Output -->';
            echo '<script type="text/javascript">';
            echo 'console.log("=== Collaborative Service Script Loading (Direct) ===");';
            echo 'var bkntcCollabCategory = {';
            echo '  nonce: "' . wp_create_nonce('bkntc_collab_category_nonce') . '",';
            echo '  ajaxurl: "' . admin_url('admin-ajax.php') . '",';
            echo '  slug: "' . $this->backend_slug . '"';
            echo '};';
            echo 'console.log("bkntcCollabCategory config:", bkntcCollabCategory);';
            echo '</script>';
            echo '<script type="text/javascript" src="' . esc_url($js_file) . '?v=' . time() . '"></script>';
            echo '<!-- End Collaborative Service Scripts -->';
        }

    }
    
    public function init_service_category_collaborative() {
        $user_id = null;
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID) {
            // Try user meta first (common in SaaS)
            $user_id = $current_user->ID;
        }


        // Fetch settings from tenants table if tenant_id is available
        $collaborative_enabled = 0; // Default
        $guest_info_required = 0; // Default

        if ($user_id) {
            global $wpdb;

            if (class_exists('BookneticSaaS\Providers\Helpers\Helper')) {   
                $tenants_table = $wpdb->prefix . 'bkntc_tenants';
            
                $settings = $wpdb->get_row($wpdb->prepare(
                    "SELECT collaborative_enabled, guest_info_required FROM {$tenants_table} WHERE user_id = %d",
                    $user_id
                ), ARRAY_A);
                
                if ($settings) {
                    $collaborative_enabled = $settings['collaborative_enabled'] ? $settings['collaborative_enabled'] : 0; // Convert to string for select
                    $guest_info_required = $settings['guest_info_required'] ? $settings['guest_info_required'] : 0;
                }
            } else {
                $collaborative_enabled = get_option('bkntc_collaborative_services_enabled', 0) ? 1 : 0;
                $guest_info_required = get_option('bkntc_collaborative_guest_info_required', 0) ? 1 : 0;
            }
            
        }
        $this->tenantAllowed = \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services');
        $this->collaborative_enabled_for_tenant = $collaborative_enabled;
        
        if (!$this->tenantAllowed || !$this->collaborative_enabled_for_tenant ) {
            return;
        }

        // Don't output scripts during AJAX/XHR requests or on table-refresh calls
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }
        // fs-data-table is used by Booknetic to request table data — avoid output in that case
        if ((isset($_REQUEST['fs-data-table']) && $_REQUEST['fs-data-table']) || (isset($_POST['fs-data-table']) && $_POST['fs-data-table'])) {
            return;
        }

        // Direct output for service_categories module - bypass WordPress hooks
        // Only output on actual page loads, never during AJAX
        if (isset($_GET['module']) && ( $_GET['module'] === 'service_categories' || $_GET['module'] === 'services' ) && !isset($_GET['ajax'])) {
            $js_file = BKNTCCS_PLUGIN_URL . 'assets/js/service-category-collaborative.js';
            $css_file = BKNTCCS_PLUGIN_URL . 'assets/css/service-category-collaborative.css';
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Direct output collaborative scripts for service_categories');
            }
            
            // Output directly to the page
            echo '<!-- Collaborative Category Scripts via Direct Output -->';
            echo '<link rel="stylesheet" href="' . esc_url($css_file) . '?v=' . time() . '">';
            echo '<script type="text/javascript">';
            echo 'console.log("=== Collaborative Category Script Loading (Direct) ===");';
            echo 'var bkntcCollabCategory = {';
            echo '  nonce: "' . wp_create_nonce('bkntc_collab_service_category_nonce') . '",';
            echo '  ajaxurl: "' . admin_url('admin-ajax.php') . '",';
            echo '  slug: "' . $this->backend_slug . '"';
            echo '};';
            echo 'console.log("bkntcCollabCategory config:", bkntcCollabCategory);';
            echo 'console.log("'.$this->tenantAllowed.'");';
            echo '</script>';
            echo '<script type="text/javascript" src="' . esc_url($js_file) . '?v=' . time() . '"></script>';
            echo '<!-- End Collaborative Category Scripts -->';
        }

    }
    
    
    public function inject_service_category_scripts() {
        
    }
    
    public function enqueue_appointment_assets() {
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('enqueue_appointment_assets: Called - page=' . (isset($_GET['page']) ? $_GET['page'] : 'none') . ' module=' . (isset($_GET['module']) ? $_GET['module'] : 'none') . ' action=' . (isset($_GET['action']) ? $_GET['action'] : 'none'));
        }
        
        // Only on Booknetic appointments page
        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) {
            return;
        }
        if (!isset($_GET['module']) || $_GET['module'] !== 'appointments') {
            return;
        }
        
        // IMPORTANT: Only load on add/edit/info modals, NOT on the appointments list
        // The list page has no 'action' parameter, so we skip it
        if (!isset($_GET['action']) || !in_array($_GET['action'], ['add_new', 'edit', 'info'])) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('enqueue_appointment_assets: Skipping - not on add/edit/info modal');
            }
            return;
        }

        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('enqueue_appointment_assets: Conditions met, enqueuing scripts');
        }

        wp_enqueue_script(
            'bkntc-collab-appointment-js',
            BKNTCCS_PLUGIN_URL . 'assets/js/appointment-collaborative.js',
            ['jquery'],
            filemtime(BKNTCCS_PLUGIN_DIR . 'assets/js/appointment-collaborative.js'),
            true
        );

        wp_localize_script('bkntc-collab-appointment-js', 'bkntcCollabAppointment', [
            'nonce' => wp_create_nonce('bkntc_collab_appointment_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);

        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('Enqueued appointment collaborative assets');
        }
    }

    public function maybe_override_booknetic_view()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) {
            return;
        }

        if (!isset($_GET['module']) || !isset($_GET['view'])) {
            return;
        }

        if ($_GET['module'] !== 'settings' || $_GET['view'] !== 'settings.collaborative_services') {
            return;
        }

        if (function_exists('bkntc_cs_log')) bkntc_cs_log('maybe_override_booknetic_view: intercepting Booknetic view request');

        // Load controller and directly render the view to bypass Booknetic internals
        $controller_file = BKNTCCS_PLUGIN_DIR . 'app/Backend/CollaborativeServices/Controller.php';
        if (!file_exists($controller_file)) {
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('maybe_override_booknetic_view: controller missing: ' . $controller_file);
            return;
        }

        require_once $controller_file;

        // Enforce capabilities if available
        if (class_exists('BookneticApp\Providers\Core\Capabilities')) {
            try {
                \BookneticApp\Providers\Core\Capabilities::must('settings');
            } catch (\Exception $e) {
                if (function_exists('bkntc_cs_log')) bkntc_cs_log('maybe_override_booknetic_view: capability denied: ' . $e->getMessage());
                return;
            }
        }

        $view_file = BKNTCCS_PLUGIN_DIR . 'app/Backend/CollaborativeServices/view/collaborative_services.php';
        if (file_exists($view_file)) {
            include $view_file;
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('maybe_override_booknetic_view: included view file and exiting to prevent core handler');
            // Stop further processing to avoid Booknetic core showing its error
            exit;
        }

        if (function_exists('bkntc_cs_log')) bkntc_cs_log('maybe_override_booknetic_view: view file not found: ' . $view_file);
    }

    // NOTE: temporary override removed. The method kept for reference but no longer hooked.

    public function maybe_handle_booknetic_ajax()
    {
        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) return;
        if (!isset($_GET['ajax']) || (string) $_GET['ajax'] !== '1') return;

        $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
        $module = isset($_POST['module']) ? $_POST['module'] : (isset($_GET['module']) ? $_GET['module'] : '');
        
        // Only intercept our specific collaborative_services actions - be very strict
        if ($action !== 'collaborative_services' &&
            $module !== 'collaborative_services' && 
            $action !== 'settings.collaborative_services' && 
            $action !== 'collaborative_services.save' && 
            $action !== 'settings.collaborative_services.save') {
            return; // Not our action, let Booknetic handle it
        }

        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('maybe_handle_booknetic_ajax: intercepted action=' . $action . ' | POST: ' . json_encode($_POST));
        }

        
        // Handle save action
        if ($action === 'collaborative_services.save' || $action === 'save') {
           
            // Get and sanitize settings
            $collaborative_enabled = isset($_POST['collaborative_enabled']) ? intval($_POST['collaborative_enabled']) : 0;
            $guest_info_required = isset($_POST['guest_info_required']) ? intval($_POST['guest_info_required']) : 0;
            
            // Save settings
            // Get current tenant ID (assuming Booknetic provides this method; adjust if needed)
            $user_id = 0;
            $current_user = wp_get_current_user();
            if ($current_user && $current_user->ID) {
                $user_id = $current_user->ID;
            }
            if ($user_id) {
                global $wpdb;
                
                if (class_exists('BookneticSaaS\Providers\Helpers\Helper')) {   
                    $tenants_table = $wpdb->prefix . 'bkntc_tenants';
                
                    $result = $wpdb->update(
                        $tenants_table,
                        [
                            'collaborative_enabled' => $collaborative_enabled,
                            'guest_info_required' => $guest_info_required
                        ],
                        ['user_id' => $user_id],
                        ['%d', '%d'],
                        ['%d']
                    );

                } else {
                    update_option('bkntc_collaborative_services_enabled', $collaborative_enabled ? 1 : 0);
                    update_option('bkntc_collaborative_guest_info_required', $guest_info_required ? 1 : 0);
                }

                
                 
            } 


            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('maybe_handle_booknetic_ajax: saved - enabled=' . $collaborative_enabled . ' guest_info=' . $guest_info_required. ' tenantid=' . $user_id);
            }

            echo json_encode(['status' => 'ok', 'message' => bkntc__('Settings saved successfully')]);
            exit;
        }

        // Handle view action
        $view_file = BKNTCCS_PLUGIN_DIR . 'app/Backend/CollaborativeServices/view/collaborative_services.php';
        if (!file_exists($view_file)) {
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('maybe_handle_booknetic_ajax: view missing: ' . $view_file);
            echo json_encode(['status' => 'error', 'message' => 'View file missing']);
            exit;
        }

        ob_start();
        include $view_file;
        $html = ob_get_clean();

        if (function_exists('bkntc_cs_log')) bkntc_cs_log('maybe_handle_booknetic_ajax: returning HTML length=' . strlen($html));

        // Return in Booknetic's expected format
        echo json_encode(['status' => 'ok', 'html' => $html]);
        exit;
    }

    public function log_request_params()
    {
        // CRITICAL: Never output anything during AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }
        
        if (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'bkntc_collab_') === 0) {
            return;
        }
        
        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) {
            return;
        }

        $req = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        $ajax = isset($_REQUEST['ajax']) ? $_REQUEST['ajax'] : '';

        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('log_request_params: REQUEST_URI=' . $req . ' METHOD=' . $method . ' action=' . $action . ' ajax=' . $ajax . ' GET=' . print_r($_GET, true));
        }
        $this->tenantAllowed = \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services');
        if (!$this->tenantAllowed){
            return;
        }
        
        
        // Direct output for appointments module - bypass WordPress hooks
        // AJAX is already blocked at the top of this function, so just check module
        if (isset($_GET['module']) && $_GET['module'] === 'appointments' && !isset($_GET['ajax'])) {
            $js_file = BKNTCCS_PLUGIN_URL . 'assets/js/appointment-collaborative.js';

            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Direct output collaborative scripts for appointments');
            }

            // Output directly to the page (inline)
            echo '<!-- Collaborative Appointment Scripts via Direct Output -->';
            echo '<script type="text/javascript">';
            echo 'console.log("=== Collaborative Appointment Script Loading (Direct) ===");';
            echo 'var bkntcCollabAppointment = {';
            echo '  nonce: "' . wp_create_nonce('bkntc_collab_appointment_nonce') . '",';
            echo '  ajaxurl: "' . admin_url('admin-ajax.php') . '"';
            echo '};';
            echo 'window.bkntcCollabSvcRules = window.bkntcCollabSvcRules || null;';
            echo 'console.log("bkntcCollabAppointment config:", bkntcCollabAppointment);';
            echo '</script>';
            echo '<script type="text/javascript" src="' . esc_url($js_file) . '?v=' . time() . '"></script>';
            echo '<!-- End Collaborative Appointment Scripts -->';
        }
    }

    private function get_frontend_tenant_id() {
        // 1) explicit request
        if (isset($_REQUEST['tenant_id'])) {
            return intval($_REQUEST['tenant_id']);
        }

        // 2) cookie
        if (!empty($_COOKIE['bkntc_tenant_id'])) {
            return intval($_COOKIE['bkntc_tenant_id']);
        }

        global $wpdb;

        // 3) current user's tenant (common in SaaS setups)
        $current_user = wp_get_current_user();
        if ($current_user && $current_user->ID) {
            $tenants_table = $wpdb->prefix . 'bkntc_tenants';
            $tenant_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$tenants_table} WHERE user_id = %d LIMIT 1", $current_user->ID));
            if ($tenant_id) {
                return intval($tenant_id);
            }
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
        if ($uri) {
            $parts = explode('/', trim($uri, '/'));
            if (!empty($parts[1])) {
                $first = $parts[1];
                $tenants_table = $wpdb->prefix . 'bkntc_tenants';
                $like = '%' . $wpdb->esc_like($first) . '%';
                $tenant_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$tenants_table} WHERE domain LIKE %s LIMIT 1", $like));
                if ($tenant_id) return intval($tenant_id);
            }
        }
       

        return 0;

    }

    public function enqueue_frontend_booking_assets() {
        // Load on all frontend pages (Booknetic can be loaded via shortcode, popup, iframe, etc.)
        $tenant_id = $this->get_frontend_tenant_id();
        global $wpdb;
        if ( $tenant_id > 0 ) {
            $tenants_table = $wpdb->prefix . 'bkntc_tenants';
            $collaborative_enabled = $wpdb->get_var($wpdb->prepare("SELECT collaborative_enabled FROM {$tenants_table} WHERE id = %d LIMIT 1", $tenant_id));
            if ( ! $collaborative_enabled ) {
                if (function_exists('bkntc_cs_log')) {
                    bkntc_cs_log('enqueue_frontend_booking_assets: collaborative disabled for tenant ' . $tenant_id );
                }
                return;
            }
        } 
        
        if (!is_admin()) {
            // Gate by tenant plan: do not load collaborative assets if plan disallows
            if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
                ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
                if (function_exists('bkntc_cs_log')) bkntc_cs_log('enqueue_frontend_booking_assets: gated by tenant capability');
                return;
            }
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Enqueuing frontend booking assets on page');
            }
            
            // Enqueue main frontend booking handler FIRST
            // Load in footer with high priority to ensure it loads after Booknetic's scripts
            wp_enqueue_script(
                'bkntc-collab-frontend-booking',
                BKNTCCS_PLUGIN_URL . 'assets/js/frontend-booking-collaborative.js',
                ['jquery'],
                time(), // Use timestamp for development
                true
            );
            
            // Enqueue step-based service handler for multi-service selection
            wp_enqueue_script(
                'bkntc-collab-service-step',
                BKNTCCS_PLUGIN_URL . 'assets/js/steps/step_service_collaborative.js',
                ['jquery', 'bkntc-collab-frontend-booking'],
                time(), // Use timestamp for development
                true
            );
            
            // NEW: Enqueue COMBINED datetime-staff step handler (single unified step)
            // This intercepts date_time step and converts it to combined view in multi-service mode
            wp_enqueue_script(
                'bkntc-collab-combined-step',
                BKNTCCS_PLUGIN_URL . 'assets/js/steps/step_date_time_staff_combined.js',
                ['jquery', 'bkntc-collab-service-step'],
                time(), // Use timestamp for development
                true
            );
            
            // LEGACY: Old datetime-staff handler (kept for compatibility, but combined step takes priority)
            wp_enqueue_script(
                'bkntc-collab-datetime-staff-step',
                BKNTCCS_PLUGIN_URL . 'assets/js/steps/step_datetime_staff_collaborative.js',
                ['jquery', 'bkntc-collab-combined-step'],
                time(), // Use timestamp for development
                true
            );
            
            // Enqueue step-based staff handler (follows Booknetic pattern)
            wp_enqueue_script(
                'bkntc-collab-staff-step',
                BKNTCCS_PLUGIN_URL . 'assets/js/steps/step_staff_collaborative.js',
                ['jquery', 'bkntc-collab-datetime-staff-step'],
                time(), // Use timestamp for development
                true
            );
            
            // Enqueue step-based information handler for multi-guest fields
            wp_enqueue_script(
                'bkntc-collab-information-step',
                BKNTCCS_PLUGIN_URL . 'assets/js/steps/step_information_collaborative.js',
                ['jquery', 'bkntc-collab-staff-step'],
                time(), // Use timestamp for development
                true
            );

            // Enqueue step-based confirm handler to display all selected staff
            wp_enqueue_script(
                'bkntc-collab-confirm-step',
                BKNTCCS_PLUGIN_URL . 'assets/js/steps/step_confirm_collaborative.js',
                ['jquery'],
                time(), // Use timestamp for development
                true
            );

            // Enqueue step-based cart handler to display all selected staff in cart
            wp_enqueue_script(
                'bkntc-collab-cart-step',
                BKNTCCS_PLUGIN_URL . 'assets/js/steps/step_cart_collaborative.js',
                ['jquery'],
                time(), // Use timestamp for development
                true
            );
            
            wp_localize_script('bkntc-collab-frontend-booking', 'BookneticCollabFrontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bkntc_collab_frontend_nonce')
            ));
            
            // Add inline script to verify loading
            wp_add_inline_script('bkntc-collab-frontend-booking', '
                console.log("=== Booknetic Collaborative Services - Frontend ===");
                console.log("Script loaded at:", new Date().toISOString());
                console.log("jQuery available:", typeof jQuery !== "undefined");
                console.log("bookneticHooks available:", typeof bookneticHooks !== "undefined");
                console.log("BookneticCollabFrontend:", typeof BookneticCollabFrontend !== "undefined" ? BookneticCollabFrontend : "NOT DEFINED");
            ', 'before');
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Frontend booking assets enqueued');
            }
        }
    }
    
    public function ajax_get_frontend_category_rules() {
        // Allow both logged-in and non-logged-in users
        // Gate by tenant plan
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
            ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        if (!check_ajax_referer('bkntc_collab_frontend_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(['message' => 'Service ID required']);
            return;
        }
        
        global $wpdb;
        
        // Get service category
        $service_table = $wpdb->prefix . 'bkntc_services';
        $category_id = $wpdb->get_var($wpdb->prepare(
            "SELECT category_id FROM {$service_table} WHERE id = %d",
            $service_id
        ));
        
        if (!$category_id) {
            wp_send_json_success([
                'min' => null,
                'max' => null,
                'message' => 'No category found for service'
            ]);
            return;
        }
        
        // Get category rules from database
        $categories_table = $wpdb->prefix . 'bkntc_service_categories';
        $category_data = $wpdb->get_row($wpdb->prepare(
            "SELECT collab_min_staff, collab_max_staff, collab_eligible_staff 
             FROM {$categories_table} WHERE id = %d",
            $category_id
        ), ARRAY_A);
        
        if ($category_data) {
            wp_send_json_success([
                'min' => !empty($category_data['collab_min_staff']) ? intval($category_data['collab_min_staff']) : null,
                'max' => !empty($category_data['collab_max_staff']) ? intval($category_data['collab_max_staff']) : null,
                'eligible_staff' => !empty($category_data['collab_eligible_staff']) 
                    ? json_decode($category_data['collab_eligible_staff'], true) 
                    : [],
                'category_id' => $category_id
            ]);
        } else {
            wp_send_json_success([
                'min' => null,
                'max' => null,
                'eligible_staff' => [],
                'category_id' => $category_id
            ]);
        }
    }
    
    public function ajax_get_category_settings_frontend() {
        // Allow both logged-in and non-logged-in users
        // Gate by tenant plan
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
            ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        if (!check_ajax_referer('bkntc_collab_frontend_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        //$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $category_name = isset($_POST['category_name']) ? sanitize_text_field(wp_unslash($_POST['category_name'])) : '';
        
        if (!$category_name) {
            wp_send_json_error(['message' => 'Category name required']);
            return;
        }
        
        global $wpdb;
        
        // Get category settings including allow_multi_select
        $categories_table = $wpdb->prefix . 'bkntc_service_categories';
        $category_data = $wpdb->get_row($wpdb->prepare(
            "SELECT id, allow_multi_select, service_selection_limit 
             FROM {$categories_table} WHERE name = %s",
            $category_name  
        ), ARRAY_A);
        
        if ($category_data) {
            wp_send_json_success([
                'allow_multi_select' => !empty($category_data['allow_multi_select']) ? intval($category_data['allow_multi_select']) : 0,
                'service_selection_limit' => !empty($category_data['service_selection_limit']) ? intval($category_data['service_selection_limit']) : 0,
                'category_id' => $category_data['id']
            ]);
        } else {
            wp_send_json_success([
                'allow_multi_select' => 0,
                'service_selection_limit' => 0,
                'category_id' => 0
            ]);
        }
    }
    
    public function ajax_get_available_staff() {
        // Allow both logged-in and non-logged-in users
        // Gate by tenant plan
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
            ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        if (!check_ajax_referer('bkntc_collab_frontend_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
        
        if (!$service_id || !$date || !$time) {
            wp_send_json_error(['message' => 'Service ID, date, and time required']);
            return;
        }
        
        global $wpdb;
        
        $staff_table = $wpdb->prefix . 'bkntc_staff';
        $staff_services_table = $wpdb->prefix . 'bkntc_staff_services';
        
        // Check if staff_services table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$staff_services_table}'") === $staff_services_table;
        
        if ($table_exists) {
            // Use the relationship table if it exists
            $staff = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT s.id, s.name, s.email, s.profile_image
                 FROM {$staff_table} s
                 INNER JOIN {$staff_services_table} ss ON s.id = ss.staff_id
                 WHERE s.is_active = 1 AND ss.service_id = %d
                 ORDER BY s.name ASC",
                $service_id
            ), ARRAY_A);
        } else {
            // Fallback: get all active staff (table doesn't exist)
            $staff = $wpdb->get_results(
                "SELECT id, name, email, profile_image
                 FROM {$staff_table}
                 WHERE is_active = 1
                 ORDER BY name ASC",
                ARRAY_A
            );
        }
        
        // TODO: In a real implementation, we would check availability against the timesheet and appointments
        
        wp_send_json_success([
            'staff' => $staff,
            'service_id' => $service_id,
            'date' => $date,
            'time' => $time
        ]);
    }
    
    public function ajax_load_combined_step() {
        // Allow both logged-in and non-logged-in users
        // Gate by tenant plan
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
            ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        if (!check_ajax_referer('bkntc_collab_frontend_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : 'date_time';
        $selected_services_json = isset($_POST['selected_services']) ? $_POST['selected_services'] : '[]';
        $selected_services = json_decode(stripslashes($selected_services_json), true);
        
        if (!is_array($selected_services) || count($selected_services) < 2) {
            wp_send_json_error(['message' => 'Multiple services required for combined step']);
            return;
        }
        
        // Build HTML for combined step
        ob_start();
        ?>
        <div class="booknetic_appointment_step_body">
            <div class="booknetic_appointment_step_element" data-step-id="date_time">
                <div class="booknetic_calendar_div">
                    <!-- Calendar will be rendered here by Booknetic's standard calendar code -->
                    <div class="booknetic_calendar" id="booknetic_calendar"></div>
                </div>
                <div class="booknetic_time_div">
                    <div class="booknetic_times_title"></div>
                    <div class="booknetic_times_list"></div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        // Get calendar data (simplified - in real implementation, this would come from Booknetic's calendar logic)
        $calendar_data = [
            'dates' => [],
            'hide_available_slots' => 'off'
        ];
        
        wp_send_json_success([
            'html' => $html,
            'calendar_data' => $calendar_data,
            'services' => $selected_services
        ]);
    }
    
    public function ajax_get_service_category() {
        // Allow both logged-in and non-logged-in users
        // Gate by tenant plan
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
            ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }
        if (!check_ajax_referer('bkntc_collab_frontend_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(['message' => 'Service ID required']);
            return;
        }
        
        global $wpdb;
        
        $category_id = $wpdb->get_var($wpdb->prepare(
            "SELECT category_id FROM {$wpdb->prefix}bkntc_services WHERE id = %d",
            $service_id
        ));
        
        if ($category_id) {
            wp_send_json_success(['category_id' => intval($category_id)]);
        } else {
            wp_send_json_error(['message' => 'Service not found']);
        }
    }
    
    public function modify_staff_step_output($staff_list) {
        // This filter allows us to modify staff list before rendering
        // The JavaScript will handle the actual UI conversion to multi-select
        // Here we just ensure the data passes through unchanged
        return $staff_list;
    }

    public function add_wp_submenu()
    {
        // parent slug 'booknetic' should exist; capability chosen to 'manage_options' as fallback
        add_submenu_page(
            'booknetic',
            bkntc__('Collaborative Services'),
            bkntc__('Collaborative Services'),
            'manage_options',
            'booknetic_collaborative_services',
            [$this, 'render_fallback_page']
        );
        if (function_exists('bkntc_cs_log')) bkntc_cs_log('add_wp_submenu: fallback submenu added with slug=booknetic_collaborative_services');
    }

    public function render_fallback_page()
    {
        if (function_exists('bkntc_cs_log')) bkntc_cs_log('render_fallback_page: invoked');

        // If Booknetic capability helper exists, enforce permissions
        if (class_exists('BookneticApp\Providers\Core\Capabilities')) {
            \BookneticApp\Providers\Core\Capabilities::must('settings');
        }

        $view_file = BKNTCCS_PLUGIN_DIR . 'app/Backend/CollaborativeServices/view/collaborative_services.php';
        if (file_exists($view_file)) {
            require $view_file;
        } else {
            echo '<div class="notice notice-error"><p>Collaborative Services view not found.</p></div>';
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('render_fallback_page: view missing: ' . $view_file);
        }
    }

    public function register_routes() {

        if (!class_exists('BookneticApp\Providers\Core\Route')) {
                if (function_exists('bkntc_cs_log')) bkntc_cs_log('register_routes: Booknetic Route class not found');
                return;
        }

        // Gate route registration by plan permission at runtime as well,
        // in case init-time gating occurred before SaaS filters were attached.
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
            ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('register_routes: gated by tenant capability');
            return;
        }

        // Load the plugin controller so the class is available
        $controller_file = BKNTCCS_PLUGIN_DIR . 'app/Backend/CollaborativeServices/Controller.php';
            if (file_exists($controller_file)) {
                require_once $controller_file;
                if (function_exists('bkntc_cs_log')) bkntc_cs_log('register_routes: Controller loaded: ' . $controller_file);
            } else {
                if (function_exists('bkntc_cs_log')) bkntc_cs_log('register_routes: Controller missing: ' . $controller_file);
            }

        // Register the 'settings' module with specific allowed actions
        // JavaScript sends booknetic.ajax('settings.collaborative_services', ...) which becomes action=settings.collaborative_services
        // or booknetic.ajax('settings.collaborative_services.save') for save action
        \BookneticApp\Providers\Core\Route::get(
            'settings',
            'BookneticApp\\Backend\\CollaborativeServices\\Controller',
            ['settings', 'collaborative_services', 'settings.collaborative_services']
        );

        \BookneticApp\Providers\Core\Route::post(
            'settings',
            'BookneticApp\\Backend\\CollaborativeServices\\Controller',
            ['collaborative_services', 'collaborative_services.save', 'settings.collaborative_services', 'save', 'settings.collaborative_services.save']
        );
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('register_routes: routes registered for module=settings');
    }

    /**
     * Add menu item under Booknetic settings -> General Settings -> Collaborative Services
     */
    public function add_menu_item() {
        if (!$this->tenantAllowed) {
            return;
        }
        // Do not add menu if tenant plan does not allow collaborative services
        if (class_exists('BookneticApp\\Providers\\Core\\Capabilities') &&
            ! \BookneticApp\Providers\Core\Capabilities::tenantCan('collaborative_services')) {
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('add_menu_item: gated by tenant capability');
            return;
        }

        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) {
            if (function_exists('bkntc_cs_log')) bkntc_cs_log('add_menu_item: not on booknetic page; page=' . (isset($_GET['page']) ? $_GET['page'] : '')); 
            return;
        }

        // Log module/view query parameters to help trace the admin page routing
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('add_menu_item: GET module=' . (isset($_GET['module']) ? $_GET['module'] : '') . ' view=' . (isset($_GET['view']) ? $_GET['view'] : ''));
        }

        // Add as a submenu under General Settings - no capability registration needed,
        // the controller handles capability checks via Capabilities::must('settings')
        \BookneticApp\Providers\UI\SettingsMenuUI::get('general_settings')
            ->subItem('collaborative_services')
            ->setTitle(bkntc__(' Collaborative Services'))
            ->setPriority(7);
        if (function_exists('bkntc_cs_log')) bkntc_cs_log('add_menu_item: submenu collaborative_services added');
    }



    // Service Collaborative handlers
    public function enqueue_service_assets() {
        $screen = get_current_screen();
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('Service collaborative assets enqueued');
        }
        // Only load on Services page
        if ($screen && strpos($screen->id, $this->backend_slug) !== false) {
            wp_enqueue_script(
                'bkntc-collab-service',
                BKNTCCS_PLUGIN_URL . 'assets/js/service-collaborative.js',
                ['jquery'],
                time(),
                true
            );
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Service collaborative assets enqueued');
            }
        }
    }
    
    public function ajax_get_service_settings() {
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(['message' => 'Invalid service ID']);
        }
        
        global $wpdb;
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT collab_min_staff, collab_max_staff FROM {$wpdb->prefix}bkntc_services WHERE id = %d",
            $service_id
        ), ARRAY_A);
        
        if (!$service) {
            wp_send_json_error(['message' => 'Service not found']);
        }
        
        wp_send_json_success($service);
    }
    
    public function ajax_save_service_settings() {
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $collab_min_staff = isset($_POST['collab_min_staff']) ? intval($_POST['collab_min_staff']) : null;
        $collab_max_staff = isset($_POST['collab_max_staff']) ? intval($_POST['collab_max_staff']) : null;
        
        if (!$service_id) {
            wp_send_json_error(['message' => 'Invalid service ID']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'bkntc_services';
        
        // Build update array dynamically based on what was sent
        $update_data = [];
        $update_format = [];
        
        if ($collab_min_staff !== null) {
            $update_data['collab_min_staff'] = max(1, $collab_min_staff);
            $update_format[] = '%d';
        }
        
        if ($collab_max_staff !== null) {
            $update_data['collab_max_staff'] = max(1, $collab_max_staff);
            $update_format[] = '%d';
        }
        
        if (empty($update_data)) {
            wp_send_json_error(['message' => 'No data to save']);
        }
        // Diagnostic: check table and columns exist
        $collab_min_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s", DB_NAME, $wpdb->prefix . 'bkntc_services', 'collab_min_staff'));
        $collab_max_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s", DB_NAME, $wpdb->prefix . 'bkntc_services', 'collab_max_staff'));

        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('ajax_save_service_settings: table=' . $table . ' service_id=' . $service_id . ' payload=' . json_encode($update_data) . ' collab_min_exists=' . intval($collab_min_exists) . ' collab_max_exists=' . intval($collab_max_exists));
        }

        // Save to database table
        $result = $wpdb->update(
            $table,
            $update_data,
            ['id' => $service_id],
            $update_format,
            ['%d']
        );

        // Rows affected (may be 0 if values unchanged)
        $rows_affected = $wpdb->rows_affected;
        $db_error = $wpdb->last_error;
        
        if ($result !== false) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Saved collaborative settings for service ' . $service_id . ': ' . json_encode($update_data) . ' - rows_affected: ' . intval($rows_affected));
            }

            // If rows_affected is 0, the UPDATE ran but didn't change values.
            wp_send_json_success([
                'message' => 'Settings saved successfully',
                'settings' => $update_data,
                'updated_rows' => intval($rows_affected),
                'db_error' => $db_error
            ]);
        } else {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Failed to save collaborative settings for service ' . $service_id . ': ' . $db_error);
            }
            wp_send_json_error(['message' => 'Database error', 'db_error' => $db_error]);
        }
    }
    
    public function filter_service_save_data($data) {
        // Add collab_min_staff and collab_max_staff to insert data if present in POST
        if (isset($_POST['collab_min_staff'])) {
            $data['collab_min_staff'] = max(1, intval($_POST['collab_min_staff']));
        }
        
        if (isset($_POST['collab_max_staff'])) {
            $data['collab_max_staff'] = max(1, intval($_POST['collab_max_staff']));
        }
        
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('filter_service_save_data: ' . json_encode($data));
        }
        
        return $data;
    }

    public static function activate() {
        // Create required folders
        wp_mkdir_p(BKNTCCS_PLUGIN_DIR . 'app/Backend/CollaborativeServices/view/');
        
        global $wpdb;
        
        // Add columns to service categories table
        $categories_table = $wpdb->prefix . 'bkntc_service_categories';
        
        // Add allow_multi_select if missing
        $multi_select_column = $wpdb->get_results("SHOW COLUMNS FROM {$categories_table} LIKE 'allow_multi_select'");
        if (empty($multi_select_column)) {
            $wpdb->query("ALTER TABLE {$categories_table} ADD COLUMN allow_multi_select TINYINT(1) DEFAULT 0");
        }
        $service_selection_limit_column = $wpdb->get_results("SHOW COLUMNS FROM {$categories_table} LIKE 'service_selection_limit'");
        if (empty($service_selection_limit_column)) {
            $wpdb->query("ALTER TABLE {$categories_table} ADD COLUMN service_selection_limit INT(11) DEFAULT 1");
        }
        
        
        // Add columns to services table
        $services_table = $wpdb->prefix . 'bkntc_services';
        $service_columns = $wpdb->get_results("SHOW COLUMNS FROM {$services_table} LIKE 'collab_%'");
        
        if (empty($service_columns)) {
            $wpdb->query("ALTER TABLE {$services_table} 
                ADD COLUMN collab_min_staff INT(11) DEFAULT 1 AFTER category_id,
                ADD COLUMN collab_max_staff INT(11) DEFAULT 1 AFTER collab_min_staff");
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Added collaborative columns to services table');
            }
        }


        // Add columns to tenants table
        $tenants_table = $wpdb->prefix . 'bkntc_tenants';
        $tenants_columns = $wpdb->get_results("SHOW COLUMNS FROM {$tenants_table} LIKE 'collaborative_%'");
        
        if (class_exists('\BookneticSaaS\Providers\Helpers\Helper')) {
            // Add columns to bkntc_tenants (existing logic)
            if (empty($tenants_columns)) {
                $wpdb->query("ALTER TABLE {$tenants_table} 
                    ADD COLUMN collaborative_enabled TINYINT(1) DEFAULT 0 AFTER money_balance,
                    ADD COLUMN guest_info_required TINYINT(1) DEFAULT 0 AFTER collaborative_enabled");
            
                if (function_exists('bkntc_cs_log')) {
                    bkntc_cs_log('Added collaborative columns to tenants table');
                }
            }
        } else {
            // Initialize options
            add_option('bkntc_collaborative_services_enabled', 0);
            add_option('bkntc_collaborative_guest_info_required', 0);
        }
        



        
        // Add column to appointments table for multi-staff support
        $appointments_table = $wpdb->prefix . 'bkntc_appointments';
        $appt_columns = $wpdb->get_results("SHOW COLUMNS FROM {$appointments_table} LIKE 'collab_staff_ids'");
        
        if (empty($appt_columns)) {
            $wpdb->query("ALTER TABLE {$appointments_table} 
                ADD COLUMN collab_staff_ids TEXT AFTER staff_id,
                ADD COLUMN collaborative_group_id VARCHAR(255) DEFAULT NULL AFTER collab_staff_ids,
                ADD INDEX idx_collaborative_group_id (collaborative_group_id)");
            
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('Added collab_staff_ids and collaborative_group_id columns to appointments table');
            }
        }
        
        // Check if collaborative_group_id exists, if not add it
        $group_id_column = $wpdb->get_results("SHOW COLUMNS FROM {$appointments_table} LIKE 'collaborative_group_id'");
        if (empty($group_id_column)) {
            $wpdb->query("ALTER TABLE {$appointments_table} 
                ADD COLUMN collaborative_group_id VARCHAR(255) DEFAULT NULL AFTER collab_staff_ids,
                ADD INDEX idx_collaborative_group_id (collaborative_group_id)");
        }
        
        // Create guests table
        $guests_table = $wpdb->prefix . 'bkntc_appointment_guests';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$guests_table} (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT(11) NOT NULL,
            guest_name VARCHAR(255) DEFAULT NULL,
            guest_email VARCHAR(255) DEFAULT NULL,
            guest_phone VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_appointment_id (appointment_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('Database tables created/updated');
        }
    }

    /**
     * Plugin uninstall cleanup: drop tenant-level collaborative columns
     */
    public static function uninstall()
    {
        global $wpdb;
        if (!class_exists('\BookneticSaaS\Providers\Helpers\Helper')) {
            delete_option('bkntc_collaborative_services_enabled');
            delete_option('bkntc_collaborative_guest_info_required');
        } else {
            $tenants_table = $wpdb->prefix . 'bkntc_tenants';

            $col = $wpdb->get_results("SHOW COLUMNS FROM {$tenants_table} LIKE 'collaborative_enabled'");
            if (!empty($col)) {
                $wpdb->query("ALTER TABLE {$tenants_table} DROP COLUMN collaborative_enabled");
                if (function_exists('bkntc_cs_log')) bkntc_cs_log('Uninstall: dropped bkntc_tenants.collaborative_enabled');
            }

            $col = $wpdb->get_results("SHOW COLUMNS FROM {$tenants_table} LIKE 'guest_info_required'");
            if (!empty($col)) {
                $wpdb->query("ALTER TABLE {$tenants_table} DROP COLUMN guest_info_required");
                if (function_exists('bkntc_cs_log')) bkntc_cs_log('Uninstall: dropped bkntc_tenants.guest_info_required');
            }
        }
        // Drop columns from tenants table if they exist
        
    }
    
    /**
     * Enqueue admin script for booking steps injection
     */
    public function enqueue_admin_booking_steps_script($hook) {
        // Only on Booknetic admin pages
        if (!isset($_GET['page']) || $_GET['page'] !== $this->backend_slug) {
            return;
        }
        
        wp_enqueue_script(
            'booknetic-collab-admin-steps',
            plugin_dir_url(__FILE__) . 'assets/js/admin-booking-steps.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }
    
    /**
     * Process collaborative bookings - create multiple appointments with shared group ID
     */
    public function process_collaborative_booking() {
        bkntc_cs_log('process_collaborative_booking: Hook triggered');
        
        // Check if this is a collaborative booking
        if (empty($_POST['cart']) || !is_array($_POST['cart'])) {
            bkntc_cs_log('process_collaborative_booking: No cart data found');
            return;
        }
        
        $cart_items = $_POST['cart'];
        bkntc_cs_log('process_collaborative_booking: Cart items: ' . json_encode($cart_items));
        
        // Check if any cart item has collaborative data
        $has_collaborative = false;
        $collaborative_group_id = null;
        
        foreach ($cart_items as $item) {
            if (isset($item['is_collaborative_booking']) && $item['is_collaborative_booking'] === true) {
                $has_collaborative = true;
                if (isset($item['collaborative_group_id'])) {
                    $collaborative_group_id = $item['collaborative_group_id'];
                }
                break;
            }
        }
        
        if (!$has_collaborative || !$collaborative_group_id) {
            bkntc_cs_log('process_collaborative_booking: Not a collaborative booking');
            return;
        }
        
        bkntc_cs_log('process_collaborative_booking: Processing collaborative booking with group ID: ' . $collaborative_group_id);
        
        // Store group ID globally for the bkntc_appointment_created action hook to access
        // The actual database update happens AFTER each appointment is created
        global $bkntc_collab_current_group_id;
        $bkntc_collab_current_group_id = $collaborative_group_id;
    }

    /**
     * Inject collaborative_group_id into appointment insert data using AppointmentRequestData accessors
     * Hook: bkntc_appointment_insert_data (see AppointmentService::createSingle)
     * @param array $appointment_data
     * @param object $appointmentRequestData (AppointmentRequestData)
     * @return array
     */
    public function add_collaborative_group_on_insert($appointment_data, $appointmentRequestData)
    {
        // Guard: Expect an AppointmentRequestData object with getData() method
        if (!is_object($appointmentRequestData) || !method_exists($appointmentRequestData, 'getData')) {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('add_collaborative_group_on_insert: Invalid AppointmentRequestData');
            }
            return $appointment_data;
        }

        // Check collaborative booking flag from raw cart item
        $is_collab = $appointmentRequestData->getData('is_collaborative_booking', false);
        if (empty($is_collab)) {
            return $appointment_data;
        }

        // Extract group id from raw cart item
        $group_id = $appointmentRequestData->getData('collaborative_group_id', '', 'string');

        if (empty($group_id)) {
            // Fallback to global set during confirmation validation (same request)
            global $bkntc_collab_current_group_id;
            $group_id = !empty($bkntc_collab_current_group_id) ? $bkntc_collab_current_group_id : '';
        }

        if (!empty($group_id)) {
            $appointment_data['collaborative_group_id'] = sanitize_text_field($group_id);
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('add_collaborative_group_on_insert: Set collaborative_group_id=' . $appointment_data['collaborative_group_id']);
            }
        } else {
            if (function_exists('bkntc_cs_log')) {
                bkntc_cs_log('add_collaborative_group_on_insert: No group id found');
            }
        }

        return $appointment_data;
    }
    
    
}

// Boot plugin
BookneticCollaborativeServices::get_instance();

// Hooks
register_activation_hook(__FILE__, ['BookneticCollaborativeServices', 'activate']);
register_uninstall_hook(__FILE__, ['BookneticCollaborativeServices', 'uninstall']);

// CRITICAL: Hook into appointment creation after it's saved to update collaborative_group_id
add_action('bkntc_appointment_created', function($appointmentRequestData) {
    global $bkntc_collab_current_group_id;
    
    // The parameter is an AppointmentRequestData object
    if (!is_object($appointmentRequestData) || empty($appointmentRequestData->appointmentId)) {
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('bkntc_appointment_created: Invalid appointment data object');
        }
        return;
    }
    
    $appointment_id = $appointmentRequestData->appointmentId;
    
    // Try to get collaborative_group_id from the appointment request data
    $collaborative_group_id = null;
    
    // First, check if the AppointmentRequestData has the data we need
    // We'll try to access any public properties that might have it
    if (!empty($bkntc_collab_current_group_id)) {
        $collaborative_group_id = $bkntc_collab_current_group_id;
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('bkntc_appointment_created: Found global collaborative_group_id for appointment ' . $appointment_id . ': ' . $collaborative_group_id);
        }
    }
    
    if (empty($collaborative_group_id)) {
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('bkntc_appointment_created: No collaborative_group_id found for appointment ' . $appointment_id);
        }
        return;
    }
    
    // Update the appointment with the collaborative group ID
    global $wpdb;
    $table = $wpdb->prefix . 'bkntc_appointments';
    
    $result = $wpdb->update(
        $table,
        ['collaborative_group_id' => $collaborative_group_id],
        ['id' => $appointment_id],
        ['%s'],
        ['%d']
    );
    
    if ($result !== false) {
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('bkntc_appointment_created: Successfully updated appointment ' . $appointment_id . ' with collaborative_group_id: ' . $collaborative_group_id);
        }
    } else {
        if (function_exists('bkntc_cs_log')) {
            bkntc_cs_log('bkntc_appointment_created: ERROR - Failed to update appointment ' . $appointment_id . ' - ' . $wpdb->last_error);
        }
    }
}, 10, 1);

