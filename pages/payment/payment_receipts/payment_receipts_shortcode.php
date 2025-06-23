<?php
/**
 * Payment Receipts Shortcode
 * 
 * Displays all submitted payment receipts in a beautiful way with search and filters
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create payment receipts table if it doesn't exist
 */
function zuwad_create_payment_receipts_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'zuwad_payment_receipts';
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            student_id varchar(50) NOT NULL,
            student_name varchar(100) NOT NULL,
            currency varchar(10) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50) NOT NULL,
            bank_name varchar(100),
            months varchar(255),
            notes text,
            file_url varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
// Run table creation on plugin activation
register_activation_hook(__FILE__, 'zuwad_create_payment_receipts_table');
// Also run it now in case the plugin is already activated
add_action('init', 'zuwad_create_payment_receipts_table');

/**
 * Register the payment receipts shortcode
 */
function zuwad_payment_receipts_shortcode() {
    // Enqueue required styles and scripts
    wp_enqueue_style('zuwad-payment-receipts-style');
    

    wp_enqueue_script('zuwad-payment-receipts-script');
    
    // Get current user role
    $user_role = zuwad_get_current_user_role();
    
    // Start output buffering
    ob_start();
    
    // Check if user has permission to view receipts
    if (!is_user_logged_in() || ($user_role != 'administrator' && $user_role != 'supervisor')) {
        echo '<div class="zuwad-error-message">عذراً، ليس لديك صلاحية لعرض هذه الصفحة.</div>';
        return ob_get_clean();
    }
    
    // Display the receipts interface
    ?>
    <div class="zuwad-payment-receipts-container">
        <h2 class="zuwad-payment-receipts-title">إيصالات الدفع</h2>
        

        
        <!-- Summary Statistics -->
        <div class="zuwad-receipts-summary">
            <!-- General Statistics Section -->
            <div class="zuwad-summary-section">
                <div class="zuwad-summary-section-title">إحصائيات عامة</div>
                <div class="zuwad-summary-row">
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">عدد الإيصالات</span>
                        <span id="zuwad-receipts-count" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">إيصالات الشهر الحالي</span>
                        <span id="zuwad-receipts-recent" class="zuwad-summary-value">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Currency Totals Section -->
            <div class="zuwad-summary-section">
                <div class="zuwad-summary-section-title">المجموع حسب العملة</div>
                <div class="zuwad-summary-row">
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">ريال سعودي</span>
                        <span id="zuwad-receipts-total-sar" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">دولار أمريكي</span>
                        <span id="zuwad-receipts-total-usd" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">درهم إماراتي</span>
                        <span id="zuwad-receipts-total-aed" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">جنيه مصري</span>
                        <span id="zuwad-receipts-total-egp" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">ريال قطري</span>
                        <span id="zuwad-receipts-total-qar" class="zuwad-summary-value">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods Section -->
            <div class="zuwad-summary-section">
                <div class="zuwad-summary-section-title">طرق الدفع</div>
                <div class="zuwad-summary-row">
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">PayPal</span>
                        <span id="zuwad-receipts-paypal" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">Vodafone Cash</span>
                        <span id="zuwad-receipts-vodafone-cash" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">InstaPay</span>
                        <span id="zuwad-receipts-insta-pay" class="zuwad-summary-value">0</span>
                    </div>
                    <div class="zuwad-summary-item">
                        <span class="zuwad-summary-label">تحويل بنكي</span>
                        <span id="zuwad-receipts-bank-transfer" class="zuwad-summary-value">0</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="zuwad-payment-receipts-filters">
            <div class="zuwad-filter-row">
                <div class="zuwad-filter-group">
                    <input type="text" id="zuwad-search-student" placeholder="بحث باسم الطالب أو الرقم" class="zuwad-filter-input">
                </div>
                <div class="zuwad-filter-group">
                    <select id="zuwad-filter-payment-method" class="zuwad-filter-select">
                        <option value="">طريقة الدفع (الكل)</option>
                        <option value="paypal">PayPal</option>
                        <option value="vodafone_cash">Vodafone Cash</option>
                        <option value="instapay">InstaPay</option>
                        <option value="bank">تحويل بنكي</option>
                    </select>
                </div>
                <div class="zuwad-filter-group">
                    <select id="zuwad-filter-currency" class="zuwad-filter-select">
                        <option value="SAR">ريال سعودي (SAR)</option>
                        <option value="AED">درهم إماراتي (AED)</option>
                        <option value="EGP">جنيه مصري (EGP)</option>
                        <option value="QAR">ريال قطري (QAR)</option>
                        <option value="USD">دولار أمريكي (USD)</option>
                        <option value="OMR">ريال عماني (OMR)</option>
                    </select>
                </div>
            </div>
            <div class="zuwad-filter-row">
                <div class="zuwad-filter-group">
                    <label for="zuwad-filter-date-from">من تاريخ:</label>
                    <input type="date" id="zuwad-filter-date-from" class="zuwad-filter-date">
                </div>
                <div class="zuwad-filter-group">
                    <label for="zuwad-filter-date-to">إلى تاريخ:</label>
                    <input type="date" id="zuwad-filter-date-to" class="zuwad-filter-date">
                </div>
                <div class="zuwad-filter-group">
                    <button id="zuwad-reset-filters" class="zuwad-button zuwad-reset-button">إعادة تعيين</button>
                </div>
            </div>
        </div>

        <!-- Receipts Table -->
        <div class="zuwad-receipts-table-container">
            <table id="zuwad-receipts-table" class="zuwad-receipts-table">
                <thead>
                    <tr>
                        <th>رقم الطالب</th>
                        <th>اسم الطالب</th>
                        <th>المبلغ</th>
                        <th>العملة</th>
                        <th>طريقة الدفع</th>
                        <th>البنك</th>
                        <th>الشهور</th>
                        <th>التاريخ</th>
                        <th>ملاحظات</th>
                        <th>الإيصال</th>
                    </tr>
                </thead>
                <tbody id="zuwad-receipts-body">
                    <!-- Receipts will be loaded here via AJAX -->
                    <tr class="zuwad-loading-row">
                        <td colspan="10">جاري تحميل البيانات...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="zuwad-receipts-pagination">
            <button id="zuwad-prev-page" class="zuwad-pagination-button" disabled>السابق</button>
            <span id="zuwad-page-info">صفحة <span id="zuwad-current-page">1</span> من <span id="zuwad-total-pages">1</span></span>
            <button id="zuwad-next-page" class="zuwad-pagination-button" disabled>التالي</button>
        </div>
        
        <!-- Receipt Modal -->
        <div id="zuwad-receipt-modal" class="zuwad-modal">
            <div class="zuwad-modal-content">
                <span class="zuwad-modal-close">&times;</span>
                <h3>تفاصيل الإيصال</h3>
                <div id="zuwad-receipt-details" class="zuwad-receipt-details">
                    <!-- Receipt details will be loaded here -->
                </div>
                <div id="zuwad-receipt-image" class="zuwad-receipt-image">
                    <!-- Receipt image will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('zuwad_payment_receipts', 'zuwad_payment_receipts_shortcode');

/**
 * AJAX handler for fetching payment receipts
 */
function zuwad_fetch_payment_receipts() {
    // Check if user is logged in and has permission
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يجب تسجيل الدخول لعرض الإيصالات']);
        return;
    }
    
    $user_role = zuwad_get_current_user_role();
    if ($user_role != 'administrator' && $user_role != 'supervisor') {
        wp_send_json_error(['message' => 'ليس لديك صلاحية لعرض الإيصالات']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'zuwad_payment_receipts';
    
    // Get parameters for filtering and pagination
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : '';
    $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
    $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
    
    // Build the query
    $query = "SELECT * FROM $table_name WHERE 1=1";
    $count_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
    $sum_query_sar = "SELECT SUM(amount) FROM $table_name WHERE currency = 'SAR'";
    $sum_query_usd = "SELECT SUM(amount) FROM $table_name WHERE currency = 'USD'";
    $sum_query_aed = "SELECT SUM(amount) FROM $table_name WHERE currency = 'AED'";
    $sum_query_egp = "SELECT SUM(amount) FROM $table_name WHERE currency = 'EGP'";
    $sum_query_qar = "SELECT SUM(amount) FROM $table_name WHERE currency = 'QAR'";
    
    $where_conditions = [];
    
    // Add search condition
    if (!empty($search)) {
        $where_conditions[] = "(student_id LIKE %s OR student_name LIKE %s)";
        $search_param = '%' . $wpdb->esc_like($search) . '%';
        $query_params[] = $search_param;
        $query_params[] = $search_param;
    }
    
    // Add payment method filter
    if (!empty($payment_method)) {
        $where_conditions[] = "payment_method = %s";
        $query_params[] = $payment_method;
    }
    
    // Add currency filter
    if (!empty($currency)) {
        $where_conditions[] = "currency = %s";
        $query_params[] = $currency;
    }
    
    // Add date range filter
    if (!empty($date_from)) {
        $where_conditions[] = "created_at >= %s";
        $query_params[] = $date_from . ' 00:00:00';
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "created_at <= %s";
        $query_params[] = $date_to . ' 23:59:59';
    }
    
    // Combine where conditions
    if (!empty($where_conditions)) {
        $query .= " AND " . implode(" AND ", $where_conditions);
        $count_query .= " AND " . implode(" AND ", $where_conditions);
        
        // Add conditions to sum queries if search is applied
        if (!empty($search) || !empty($payment_method) || !empty($date_from) || !empty($date_to)) {
            $sum_query_sar .= " AND " . implode(" AND ", $where_conditions);
            $sum_query_usd .= " AND " . implode(" AND ", $where_conditions);
            $sum_query_aed .= " AND " . implode(" AND ", $where_conditions);
            $sum_query_egp .= " AND " . implode(" AND ", $where_conditions);
            $sum_query_qar .= " AND " . implode(" AND ", $where_conditions);
        }
    }
    
    // Add sorting
    $query .= " ORDER BY created_at DESC";
    
    // Add pagination
    $offset = ($page - 1) * $per_page;
    $query .= " LIMIT %d OFFSET %d";
    $query_params[] = $per_page;
    $query_params[] = $offset;
    
    // Prepare and execute the queries
    if (!empty($query_params)) {
        $prepared_query = $wpdb->prepare($query, $query_params);
        $prepared_count_query = $wpdb->prepare($count_query, array_slice($query_params, 0, -2));
        
        // Prepare sum queries if filters are applied
        if (!empty($search) || !empty($payment_method) || !empty($date_from) || !empty($date_to)) {
            $prepared_sum_query_sar = $wpdb->prepare($sum_query_sar, array_slice($query_params, 0, -2));
            $prepared_sum_query_usd = $wpdb->prepare($sum_query_usd, array_slice($query_params, 0, -2));
            $prepared_sum_query_aed = $wpdb->prepare($sum_query_aed, array_slice($query_params, 0, -2));
            $prepared_sum_query_egp = $wpdb->prepare($sum_query_egp, array_slice($query_params, 0, -2));
            $prepared_sum_query_qar = $wpdb->prepare($sum_query_qar, array_slice($query_params, 0, -2));
        } else {
            $prepared_sum_query_sar = $sum_query_sar;
            $prepared_sum_query_usd = $sum_query_usd;
            $prepared_sum_query_aed = $sum_query_aed;
            $prepared_sum_query_egp = $sum_query_egp;
            $prepared_sum_query_qar = $sum_query_qar;
        }
    } else {
        $prepared_query = $query;
        $prepared_count_query = $count_query;
        $prepared_sum_query_sar = $sum_query_sar;
        $prepared_sum_query_usd = $sum_query_usd;
        $prepared_sum_query_aed = $sum_query_aed;
        $prepared_sum_query_egp = $sum_query_egp;
        $prepared_sum_query_qar = $sum_query_qar;
    }
    
    $receipts = $wpdb->get_results($prepared_query);
    
    // Process the months data to make it more readable
    foreach ($receipts as &$receipt) {
        if (!empty($receipt->months)) {
            // Try to decode JSON
            $months_data = json_decode($receipt->months, true);
            if (is_array($months_data)) {
                // If it's a valid JSON array, convert it to a comma-separated string
                $receipt->months = implode('، ', $months_data);
            } else {
                // If it's not valid JSON, clean up the string by removing brackets, quotes and backslashes
                $receipt->months = str_replace(['[', ']', '"', '\\', '\''], '', $receipt->months);
                // Also replace escaped commas with actual commas
                $receipt->months = str_replace('\\,', '، ', $receipt->months);
                $receipt->months = str_replace('\,', '، ', $receipt->months);
            }
        }
    }
    unset($receipt); // Break the reference
    
    $total_receipts = $wpdb->get_var($prepared_count_query);
    $total_amount_sar = $wpdb->get_var($prepared_sum_query_sar) ?: 0;
    $total_amount_aed = $wpdb->get_var($prepared_sum_query_aed) ?: 0;
    $total_amount_egp = $wpdb->get_var($prepared_sum_query_egp) ?: 0;
    $total_amount_qar = $wpdb->get_var($prepared_sum_query_qar) ?: 0;
    $total_amount_usd = $wpdb->get_var($prepared_sum_query_usd) ?: 0;


    $total_pages = ceil($total_receipts / $per_page);
    
    // Calculate additional statistics
    $current_month_start = date('Y-m-01 00:00:00');
    $current_month_end = date('Y-m-t 23:59:59');
    
    $recent_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE created_at BETWEEN %s AND %s",
        $current_month_start,
        $current_month_end
    ));
    
    $paypal_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE payment_method = %s",
        'paypal'
    ));
    
    $vodafone_cash_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE payment_method = %s",
        'vodafone_cash'
    ));
    
    $instapay_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE payment_method = %s",
        'instapay'
    ));
    
    $bank_transfer_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE payment_method = %s",
        'bank'
    ));
    
    // Format the response
    $response = [
        'receipts' => $receipts,
        'total_receipts' => $total_receipts,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_amount_sar' => number_format($total_amount_sar, 2),
        'total_amount_usd' => number_format($total_amount_usd, 2),
        'total_amount_aed' => number_format($total_amount_aed, 2),
        'total_amount_egp' => number_format($total_amount_egp, 2),
        'total_amount_qar' => number_format($total_amount_qar, 2),
        'stats' => [
            'recent_count' => $recent_count,
            'paypal_count' => $paypal_count,
            'vodafone_cash_count' => $vodafone_cash_count,
            'instapay_count' => $instapay_count,
            'bank_transfer_count' => $bank_transfer_count
        ]
    ];
    
    wp_send_json_success($response);
}
add_action('wp_ajax_zuwad_fetch_payment_receipts', 'zuwad_fetch_payment_receipts');
add_action('wp_ajax_nopriv_zuwad_fetch_payment_receipts', 'zuwad_fetch_payment_receipts');

/**
 * AJAX handler for viewing receipt details
 */
function zuwad_view_receipt_details() {
    // Check if user is logged in and has permission
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يجب تسجيل الدخول لعرض تفاصيل الإيصال']);
        return;
    }
    
    $user_role = zuwad_get_current_user_role();
    if ($user_role != 'administrator' && $user_role != 'supervisor') {
        wp_send_json_error(['message' => 'ليس لديك صلاحية لعرض تفاصيل الإيصال']);
        return;
    }
    
    // Get receipt ID
    $receipt_id = isset($_POST['receipt_id']) ? intval($_POST['receipt_id']) : 0;
    if (!$receipt_id) {
        wp_send_json_error(['message' => 'معرف الإيصال غير صالح']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'zuwad_payment_receipts';
    
    // Get receipt details
    $receipt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $receipt_id));
    
    if (!$receipt) {
        wp_send_json_error(['message' => 'الإيصال غير موجود']);
        return;
    }
    
    wp_send_json_success(['receipt' => $receipt]);
}
add_action('wp_ajax_zuwad_view_receipt_details', 'zuwad_view_receipt_details');
add_action('wp_ajax_nopriv_zuwad_view_receipt_details', 'zuwad_view_receipt_details');

/**
 * AJAX handler for uploading payment receipt
 */
function zuwad_upload_payment_receipt() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يجب تسجيل الدخول لرفع الإيصال']);
        return;
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'zuwad_payment_receipts';

    // Sanitize and validate fields
    $student_id = isset($_POST['student_id']) ? sanitize_text_field($_POST['student_id']) : '';
    $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
    $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $bank_name = isset($_POST['bank_name']) ? sanitize_text_field($_POST['bank_name']) : '';
    $months = isset($_POST['months']) ? sanitize_text_field($_POST['months']) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

    // Validate required fields
    if (!$student_id || !$student_name || !$currency || !$amount || !$payment_method) {
        wp_send_json_error(['message' => 'يرجى ملء جميع الحقول المطلوبة']);
        return;
    }

    // Handle file upload
    if (!isset($_FILES['receipt_file'])) {
        wp_send_json_error(['message' => 'يرجى رفع ملف الإيصال']);
        return;
    }
    $file = $_FILES['receipt_file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        wp_send_json_error(['message' => 'نوع الملف غير مدعوم. الرجاء رفع صورة أو ملف PDF فقط.']);
        return;
    }
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($file, $upload_overrides);
    if (!$movefile || isset($movefile['error'])) {
        wp_send_json_error(['message' => 'فشل رفع الملف: ' . ($movefile['error'] ?? '')]);
        return;
    }
    $file_url = $movefile['url'];

    // Insert into DB
    $result = $wpdb->insert($table_name, [
        'student_id' => $student_id,
        'student_name' => $student_name,
        'currency' => $currency,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'bank_name' => $bank_name,
        'months' => $months,
        'notes' => $notes,
        'file_url' => $file_url,
        'created_at' => current_time('mysql'),
    ]);
    if ($result === false) {
        wp_send_json_error(['message' => 'حدث خطأ أثناء حفظ البيانات في قاعدة البيانات']);
        return;
    }
    
    // Update payment status to 'نشط' when receipt is uploaded successfully
    // Find the user by student_id (m_id)
    $users = get_users([
        'meta_key' => 'm_id',
        'meta_value' => $student_id,
        'number' => 1
    ]);
    
    if (!empty($users)) {
        $user_id = $users[0]->ID;
        
        // Update payment status to 'نشط'
        update_user_meta($user_id, 'payment_status', 'نشط');
        update_user_meta($user_id, 'payment_status_change_date', current_time('mysql'));
        
        // Add note about the status change
        $payment_notes = get_user_meta($user_id, 'payment_notes', true);
        $new_note = "تم تغيير حالة الدفع إلى 'نشط' بعد رفع إيصال الدفع - " . current_time('mysql');
        
        if (!empty($payment_notes)) {
            $payment_notes = $new_note . "\n" . $payment_notes;
        } else {
            $payment_notes = $new_note;
        }
        
        update_user_meta($user_id, 'payment_notes', $payment_notes);
    }
    
    wp_send_json_success(['message' => 'تم رفع الإيصال بنجاح وتم تحديث حالة الدفع إلى نشط']);
}
add_action('wp_ajax_upload_payment_receipt', 'zuwad_upload_payment_receipt');
add_action('wp_ajax_nopriv_upload_payment_receipt', 'zuwad_upload_payment_receipt');

/**
 * AJAX handler for deleting payment receipt
 */
function zuwad_delete_payment_receipt() {
    // Check if user is logged in and has permission
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يجب تسجيل الدخول لحذف الإيصال']);
        return;
    }
    

    $user_role = zuwad_get_current_user_role();
    if ($user_role != 'administrator' && $user_role != 'supervisor') {
        wp_send_json_error(['message' => 'ليس لديك صلاحية لحذف الإيصال']);
        return;
    }
    
    // Get receipt ID
    $receipt_id = isset($_POST['receipt_id']) ? intval($_POST['receipt_id']) : 0;
    if (!$receipt_id) {
        wp_send_json_error(['message' => 'معرف الإيصال غير صالح']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'zuwad_payment_receipts';
    
    // Get receipt details to delete file if needed
    $receipt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $receipt_id));
    
    if (!$receipt) {
        wp_send_json_error(['message' => 'الإيصال غير موجود']);
        return;
    }
    
    // Delete the file from uploads directory if it exists
    if (!empty($receipt->file_url)) {
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $receipt->file_url);
        
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }
    
    // Delete the receipt from database
    $result = $wpdb->delete($table_name, ['id' => $receipt_id], ['%d']);
    
    if ($result === false) {
        wp_send_json_error(['message' => 'حدث خطأ أثناء حذف الإيصال']);
        return;
    }
    
    wp_send_json_success(['message' => 'تم حذف الإيصال بنجاح']);
}
add_action('wp_ajax_zuwad_delete_payment_receipt', 'zuwad_delete_payment_receipt');

/**
 * AJAX handler for updating payment receipt
 */
function zuwad_update_payment_receipt() {
    // Check if user is logged in and has permission
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يجب تسجيل الدخول لتعديل الإيصال']);
        return;
    }
    
    $user_role = zuwad_get_current_user_role();
    if ($user_role != 'administrator' && $user_role != 'supervisor') {
        wp_send_json_error(['message' => 'ليس لديك صلاحية لتعديل الإيصال']);
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'zuwad_nonce')) {
        wp_send_json_error(['message' => 'فشل التحقق من الأمان']);
        return;
    }
    
    // Get receipt ID
    $receipt_id = isset($_POST['receipt_id']) ? intval($_POST['receipt_id']) : 0;
    if (!$receipt_id) {
        wp_send_json_error(['message' => 'معرف الإيصال غير صالح']);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'zuwad_payment_receipts';
    
    // Check if receipt exists
    $receipt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $receipt_id));
    if (!$receipt) {
        wp_send_json_error(['message' => 'الإيصال غير موجود']);
        return;
    }
    
    // Get form data
    $student_id = isset($_POST['student_id']) ? sanitize_text_field($_POST['student_id']) : '';
    $student_name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';
    $currency = isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $bank_name = isset($_POST['bank_name']) ? sanitize_text_field($_POST['bank_name']) : '';
    $months = isset($_POST['months']) ? sanitize_text_field($_POST['months']) : '[]';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
    
    // Validate required fields
    if (empty($student_id) || empty($student_name) || empty($currency) || $amount <= 0 || empty($payment_method) || empty($months)) {
        wp_send_json_error(['message' => 'يرجى ملء جميع الحقول المطلوبة']);
        return;
    }
    
    // Prepare update data
    $update_data = [
        'student_id' => $student_id,
        'student_name' => $student_name,
        'currency' => $currency,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'bank_name' => $bank_name,
        'months' => $months,
        'notes' => $notes,
        'updated_at' => current_time('mysql')
    ];
    
    // Handle file upload if provided
    if (!empty($_FILES['receipt_file']['name'])) {
        // Check file type
        $file_type = wp_check_filetype($_FILES['receipt_file']['name']);
        $allowed_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(['message' => 'نوع الملف غير مسموح به. يرجى استخدام JPG أو PNG أو PDF']);
            return;
        }
        
        // Upload file
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/payment_receipts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        // Generate unique filename
        $filename = 'receipt_' . time() . '_' . sanitize_file_name($_FILES['receipt_file']['name']);
        $target_file = $target_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['receipt_file']['tmp_name'], $target_file)) {
            $file_url = $upload_dir['baseurl'] . '/payment_receipts/' . $filename;
            $update_data['file_url'] = $file_url;
            
            // Delete old file if exists and different from new one
            if (!empty($receipt->file_url) && file_exists(str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $receipt->file_url))) {
                @unlink(str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $receipt->file_url));
            }
        } else {
            wp_send_json_error(['message' => 'فشل في رفع الملف']);
            return;
        }
    }
    
    // Update receipt in database
    $result = $wpdb->update(
        $table_name,
        $update_data,
        ['id' => $receipt_id],
        array_fill(0, count($update_data), '%s'),
        ['%d']
    );
    
    if ($result === false) {
        wp_send_json_error(['message' => 'حدث خطأ أثناء تحديث الإيصال']);
        return;
    }
    
    wp_send_json_success(['message' => 'تم تحديث الإيصال بنجاح']);
}
add_action('wp_ajax_update_payment_receipt', 'zuwad_update_payment_receipt');
