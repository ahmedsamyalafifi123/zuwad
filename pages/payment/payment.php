<?php
// Ensure WordPress is loaded
if (! defined('ABSPATH')) exit;

function payment_shortcode()
{
    global $wpdb;

    // Get current user
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;

    // Base query parameters
    $query_args = array(
        'role' => 'student',
        'meta_key' => 'payment_status',
        'meta_value' => 'في انتظار الدفع',
        'fields' => 'all'
    );

    // If not administrator, add m_id filter
    if (!current_user_can('administrator')) {
        if ($current_user_id == 27) {
            $query_args['meta_query'] = array(
                array(
                    'key' => 'm_id',
                    'value' => '^01',
                    'compare' => 'REGEXP'
                )
            );
        } elseif ($current_user_id == 65) {
            $query_args['meta_query'] = array(
                array(
                    'key' => 'm_id',
                    'value' => '^02',
                    'compare' => 'REGEXP'
                )
            );
        }
    }

    // Fetch students
    $students = get_users($query_args);

    // Prepare the data for the table
    $student_data = array();
    foreach ($students as $student) {
        $teacher_id = get_user_meta($student->ID, 'teacher', true);
        $student_data[] = array(
            'm_id' => get_user_meta($student->ID, 'm_id', true),
            'name' => $student->display_name,
            'teacher' => $teacher_id ? get_userdata($teacher_id)->display_name : '',
            'payment_phone' => get_user_meta($student->ID, 'payment_phone', true),
            'country' => get_user_meta($student->ID, 'country', true),
            'lessons_number' => get_user_meta($student->ID, 'lessons_number', true),
            'lesson_duration' => get_user_meta($student->ID, 'lesson_duration', true),
            'currency' => get_user_meta($student->ID, 'currency', true),
            'amount' => get_user_meta($student->ID, 'amount', true),
            'payment_status' => get_user_meta($student->ID, 'payment_status', true),
            'payment_status_change_date' => get_user_meta($student->ID, 'payment_status_change_date', true),
            'reminder_change_date' => get_user_meta($student->ID, 'reminder_change_date', true),

            'reminder' => get_user_meta($student->ID, 'reminder', true) ?: 'لا يوجد',
            'notes' => get_user_meta($student->ID, 'notes', true),
            'payment_notes' => get_user_meta($student->ID, 'payment_notes', true)
        );
    }

    // Start output buffering
    ob_start();
?>
    <h2 style="text-align: center;">المدفوعات في انتظار الدفع 💸</h2>

    <!-- Statistics Cards -->
    <div class="payment-stats-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;padding: 20px;">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="total-students" style="font-size: 2em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.9;">إجمالي الطلاب</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="no-reminder-count" style="font-size: 2em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.9;">لا يوجد تنبيه</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="first-reminder-count" style="font-size: 2em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.9;">التنبيه الأول</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="second-reminder-count" style="font-size: 2em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.9;">التنبيه الثاني</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="third-reminder-count" style="font-size: 2em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.9;">التنبيه الثالث</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="no-response-count" style="font-size: 2em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.8;">لم يتم الرد</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="total-amount" style="font-size: 1.5em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.8;">إجمالي المبلغ المستحق</div>
        </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%); color: #333; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div class="stat-number" id="today-changes" style="font-size: 2em; font-weight: bold; margin-bottom: 5px;">0</div>
            <div class="stat-label" style="font-size: 0.9em; opacity: 0.8;">تغييرات اليوم</div>
        </div>
    </div>

    <div class="payment-filters" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; align-items: last baseline;">
        <div style="display: flex;flex-direction: column;">
            <label for="search-input">بحث:</label>
            <input type="text" id="search-input" placeholder="بحث بالاسم أو الرقم..." style="padding: 5px; width: 100%;">
        </div>
        <div>
            <label for="reminder-filter">فلتر التنبيه:</label>
            <select id="reminder-filter" style="padding: 5px;">
                <option value="">الكل</option>
                <option value="لا يوجد">لا يوجد</option>
                <option value="التنبيه الاول">التنبيه الاول</option>
                <option value="التنبيه الثاني">التنبيه الثاني</option>
                <option value="التنبيه التالت">التنبيه التالت</option>
                <option value="لم يتم الرد">لم يتم الرد</option>
            </select>
        </div>
        <div>
            <label for="history-filter">فلتر تاريخ التنبيه:</label>
            <select id="history-filter" style="padding: 5px;">
                <option value="">الكل</option>
                <option value="0">اليوم</option>
                <option value="1">قبل يوم واحد</option>
                <option value="2">قبل يومين</option>
                <option value="3">قبل 3 ايام</option>
                <option value="4">قبل 4 ايام</option>
                <option value="5">قبل 5 ايام</option>
                <option value="7">قبل أسبوع</option>
                <option value="14">قبل أسبوعين</option>
                <option value="30">قبل شهر</option>
            </select>
        </div>
    </div>

    <?php
    // Sort the student data by name
    usort($student_data, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    ?>


    <table id="students-table">
        <thead>
            <tr>
                <th>م</th>
                <th>الاسم</th>
                <th>المعلم</th>
                <th>الهاتف</th>
                <th>البلد</th>
                <th>الحصص</th>
                <th>المدة</th>
                <th>العملة</th>
                <th>المبلغ</th>
                <th>التنبيه</th>
                <th>ملاحظات</th>
                <th>ملاحظات الدفع</th>
                <th>إيصال الدفع</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($student_data as $student) : ?>
                <tr>
                    <td><?php echo esc_html($student['m_id']); ?></td>
                    <td class="student-name" style="cursor: pointer;"
                        data-status-date="<?php echo esc_attr($student['payment_status_change_date']); ?>"
                        data-reminder-date="<?php echo esc_attr($student['reminder_change_date']); ?>"
                        data-student-id="<?php echo esc_attr($student['m_id']); ?>">
                        <?php echo esc_html($student['name']); ?>
                    </td>
                    <td><?php echo esc_html($student['teacher']); ?></td>
                    <td class="payment-phone" data-student-id="<?php echo esc_attr($student['m_id']); ?>"><?php echo esc_html($student['payment_phone']); ?></td>
                    <td><?php echo esc_html($student['country']); ?></td>
                    <td><?php echo esc_html($student['lessons_number']); ?></td>
                    <td><?php echo esc_html($student['lesson_duration']); ?></td>
                    <td><?php echo esc_html($student['currency']); ?></td>
                    <td><?php echo esc_html($student['amount']); ?></td>
                    <td>
                        <select class="reminder-dropdown" style="padding: 5px; margin:0px;" data-student-id="<?php echo esc_attr($student['m_id']); ?>">
                            <option value="لا يوجد" <?php echo ($student['reminder'] == 'لا يوجد') ? 'selected' : ''; ?>>لا يوجد</option>
                            <option value="التنبيه الاول" <?php echo ($student['reminder'] == 'التنبيه الاول') ? 'selected' : ''; ?>>التنبيه الاول</option>
                            <option value="التنبيه الثاني" <?php echo ($student['reminder'] == 'التنبيه الثاني') ? 'selected' : ''; ?>>التنبيه الثاني</option>
                            <option value="التنبيه التالت" <?php echo ($student['reminder'] == 'التنبيه التالت') ? 'selected' : ''; ?>>التنبيه التالت</option>
                            <option value="لم يتم الرد" <?php echo ($student['reminder'] == 'لم يتم الرد') ? 'selected' : ''; ?>>لم يتم الرد</option>
                        </select>
                    </td>

                    <td class="notes" data-student-id="<?php echo esc_attr($student['m_id']); ?>"><?php echo esc_html($student['notes']); ?></td>
                    <td class="payment_notes" data-student-id="<?php echo esc_attr($student['m_id']); ?>"><?php echo esc_html($student['payment_notes']); ?></td>
                    <td>
                        <button class="upload-receipt-btn"
                            data-student-id="<?php echo esc_attr($student['m_id']); ?>"
                            data-student-name="<?php echo esc_attr($student['name']); ?>"
                            data-currency="<?php echo esc_attr($student['currency']); ?>"
                            data-amount="<?php echo esc_attr($student['amount']); ?>"
                            title="📜 رفع إيصال الدفع"
                            style="background: none; border: none; cursor: pointer;">
                            <img src="<?php echo plugin_dir_url(__FILE__); ?>../../assets/css/upload.svg" alt="Upload" style="width: 24px; height: 24px;" />
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Receipt Upload Modal -->
    <div id="receipt-upload-modal" style="display:none;">
        <div class="receipt-modal-overlay"></div>
        <div class="receipt-modal-box receipt-modal-2col compact-modal">
            <form id="receipt-upload-form" enctype="multipart/form-data" dir="rtl">
                <h3 class="receipt-modal-title">رفع إيصال الدفع</h3>

                <div class="receipt-modal-flex">
                    <div class="receipt-modal-left">
                        <div class="receipt-modal-row">
                            <label>اسم الطالب:</label>
                            <span id="modal-student-name"></span>
                        </div>
                        <div class="receipt-modal-row">
                            <label>العملة:</label>
                            <span id="modal-currency"></span>
                        </div>
                        <div class="receipt-modal-row">
                            <label for="modal-amount">المبلغ:</label>
                            <input type="number" id="modal-amount" name="amount" required />
                        </div>
                        <div class="receipt-modal-row">
                            <label for="modal-payment-method">طريقة الدفع:</label>
                            <select id="modal-payment-method" name="payment_method" required>
                                <option value="">اختر طريقة الدفع</option>
                                <option value="paypal">PayPal</option>
                                <option value="vodafone_cash">Vodafone Cash</option>
                                <option value="instapay">InstaPay</option>
                                <option value="bank">تحويل بنكي</option>
                            </select>
                        </div>
                        <div class="receipt-modal-row" id="modal-bank-row" style="display:none;">
                            <label for="modal-bank-name">البنك:</label>
                            <select id="modal-bank-name" name="bank_name">
                                <option value="">اختر البنك</option>
                                <option value="بنك السعودية">بنك السعودية</option>
                                <option value="البنك الامارات">البنك الامارات</option>
                                <option value="بنك مسقط">بنك مسقط</option>
                                <option value="صرافة">صرافة</option>
                                <option value="اخرى">اخرى</option>
                            </select>
                        </div>

                    </div>
                    <div class="receipt-modal-right">
                        <div class="receipt-modal-row">
                            <div class="receipt-modal-row">
                                <label>الشهر:</label>
                                <div style="position:relative;">
                                    <div id="custom-month-select" class="custom-month-select" tabindex="0">اختر الشهر</div>
                                    <div class="custom-month-dropdown" id="custom-month-dropdown" style="display:none;">
                                        <div class="custom-month-option" data-value="يناير">يناير</div>
                                        <div class="custom-month-option" data-value="فبراير">فبراير</div>
                                        <div class="custom-month-option" data-value="مارس">مارس</div>
                                        <div class="custom-month-option" data-value="ابريل">ابريل</div>
                                        <div class="custom-month-option" data-value="مايو">مايو</div>
                                        <div class="custom-month-option" data-value="يونيو">يونيو</div>
                                        <div class="custom-month-option" data-value="يوليو">يوليو</div>
                                        <div class="custom-month-option" data-value="اغسطس">اغسطس</div>
                                        <div class="custom-month-option" data-value="سبتمبر">سبتمبر</div>
                                        <div class="custom-month-option" data-value="اكتوبر">اكتوبر</div>
                                        <div class="custom-month-option" data-value="نوفمبر">نوفمبر</div>
                                        <div class="custom-month-option" data-value="ديسمبر">ديسمبر</div>
                                    </div>
                                    <input type="hidden" id="modal-months-hidden" name="months" value="[]" />
                                </div>
                            </div>
                            <label for="modal-file">رفع الملف (صورة أو PDF):</label>
                            <input type="file" id="modal-file" name="receipt_file" accept="image/*,application/pdf" required />
                            <div class="file-preview-area" id="file-preview-area"></div>
                        </div>
                        <div class="receipt-modal-row">
                            <label for="modal-notes">ملاحظات:</label>
                            <textarea id="modal-notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="receipt-modal-actions">
                    <button type="submit" class="receipt-modal-submit">رفع</button>
                    <button type="button" class="receipt-modal-cancel">إلغاء</button>
                </div>
                <input type="hidden" id="modal-student-id" name="student_id" />
                <input type="hidden" id="modal-student-name-hidden" name="student_name" />
                <input type="hidden" id="modal-currency-hidden" name="currency" />
            </form>
        </div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('payment', 'payment_shortcode');

// Correctly register AJAX handlers
add_action('wp_ajax_update_payment_status', 'update_payment_status');
add_action('wp_ajax_nopriv_update_payment_status', 'update_payment_status');

function update_payment_status()
{
    if (!isset($_POST['student_id']) || !isset($_POST['payment_status'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    // Get the user ID based on m_id
    $m_id = sanitize_text_field($_POST['student_id']);
    $user = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $m_id,
        'number' => 1,
    ));

    if (empty($user)) {
        wp_send_json_error('User not found');
        return;
    }

    $student_id = $user[0]->ID; // Get the actual user ID
    $payment_status = sanitize_text_field($_POST['payment_status']);

    // Update payment status and change date
    $updated = update_user_meta($student_id, 'payment_status', $payment_status);
    if ($updated) {
        // Update the payment status change date to current time
        update_user_meta($student_id, 'payment_status_change_date', current_time('Y-m-d H:i:s'));
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to update payment status');
    }
}

// Correctly register AJAX handlers for payment phone
add_action('wp_ajax_update_payment_phone', 'update_payment_phone');
add_action('wp_ajax_nopriv_update_payment_phone', 'update_payment_phone');

function update_payment_phone()
{
    if (!isset($_POST['student_id']) || !isset($_POST['payment_phone'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    // Get the user ID based on m_id
    $m_id = sanitize_text_field($_POST['student_id']);
    $user = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $m_id,
        'number' => 1,
    ));

    if (empty($user)) {
        wp_send_json_error('User not found');
        return;
    }

    $student_id = $user[0]->ID; // Get the actual user ID
    $payment_phone = sanitize_text_field($_POST['payment_phone']);

    if (update_user_meta($student_id, 'payment_phone', $payment_phone)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to update payment phone');
    }
}

// Add AJAX handler for updating payment notes
add_action('wp_ajax_update_notes', 'update_notes');
add_action('wp_ajax_nopriv_update_notes', 'update_notes');

function update_notes()
{
    if (!isset($_POST['student_id']) || !isset($_POST['notes'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    // Get the user ID based on m_id
    $m_id = sanitize_text_field($_POST['student_id']);
    $user = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $m_id,
        'number' => 1,
    ));

    if (empty($user)) {
        wp_send_json_error('User not found');
        return;
    }

    $student_id = $user[0]->ID; // Get the actual user ID
    $notes = sanitize_text_field($_POST['notes']);

    if (update_user_meta($student_id, 'notes', $notes)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to update payment notes');
    }
}



// Add AJAX handler for updating payment notes
add_action('wp_ajax_update_payment_notes', 'update_payment_notes');
add_action('wp_ajax_nopriv_update_payment_notes', 'update_payment_notes');

function update_payment_notes()
{
    if (!isset($_POST['student_id']) || !isset($_POST['payment_notes'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    // Get the user ID based on m_id
    $m_id = sanitize_text_field($_POST['student_id']);
    $user = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $m_id,
        'number' => 1,
    ));

    if (empty($user)) {
        wp_send_json_error('User not found');
        return;
    }

    $student_id = $user[0]->ID; // Get the actual user ID
    $payment_notes = sanitize_text_field($_POST['payment_notes']);

    if (update_user_meta($student_id, 'payment_notes', $payment_notes)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to update payment notes');
    }
}





// Add AJAX handler for updating reminder
add_action('wp_ajax_update_reminder', 'update_reminder');
add_action('wp_ajax_nopriv_update_reminder', 'update_reminder');

function update_reminder()
{
    if (!isset($_POST['student_id']) || !isset($_POST['reminder'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    // Get the user ID based on m_id
    $m_id = sanitize_text_field($_POST['student_id']);
    $user = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $m_id,
        'number' => 1,
    ));

    if (empty($user)) {
        wp_send_json_error('User not found');
        return;
    }

    $student_id = $user[0]->ID;
    $reminder = sanitize_text_field($_POST['reminder']);

    if (update_user_meta($student_id, 'reminder', $reminder)) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to update reminder');
    }
}




add_action('wp_ajax_update_reminder_date', 'update_reminder_date');
add_action('wp_ajax_nopriv_update_reminder_date', 'update_reminder_date');

function update_reminder_date()
{
    $student_m_id = $_POST['student_id'];
    $reminder = $_POST['reminder'];

    // Get user_id from m_id
    global $wpdb;
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = 'm_id' AND meta_value = %s",
        $student_m_id
    ));

    if ($user_id) {
        // Get existing reminder history or initialize new array
        $reminder_history = get_user_meta($user_id, 'reminder_change_date', true);
        $reminder_history = $reminder_history ? json_decode($reminder_history, true) : [];

        // Set timezone to Cairo
        date_default_timezone_set('Africa/Cairo');

        // Format current time in Cairo timezone
        $current_time = date('Y-m-d H:i:s');
        $formatted_date = date('j/n/Y');
        $formatted_time = date('g:i a');

        // Add new reminder change to history
        $reminder_history[] = [
            'status' => $reminder,
            'date' => $formatted_date,
            'time' => $formatted_time,
            'timestamp' => $current_time // Keep original timestamp for sorting
        ];

        // Sort by timestamp in descending order (newest first)
        usort($reminder_history, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Store the updated history
        update_user_meta($user_id, 'reminder_change_date', json_encode($reminder_history));

        // Format the history for display
        $formatted_history = array_map(function ($entry) {
            return sprintf(
                '%s : %s الساعة %s',
                $entry['status'],
                $entry['date'],
                str_replace(['am', 'pm'], ['صباحا', 'مساءا'], $entry['time'])
            );
        }, $reminder_history);

        wp_send_json_success([
            'date' => $formatted_history,
            'raw_history' => $reminder_history
        ]);
    } else {
        wp_send_json_error('Student not found');
    }
}


add_action('wp_ajax_clear_reminder_history', 'clear_reminder_history');
add_action('wp_ajax_nopriv_clear_reminder_history', 'clear_reminder_history');

function clear_reminder_history()
{
    if (!isset($_POST['student_id'])) {
        wp_send_json_error('Missing student ID');
        return;
    }

    $student_m_id = sanitize_text_field($_POST['student_id']);

    // Get user_id from m_id
    global $wpdb;
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = 'm_id' AND meta_value = %s",
        $student_m_id
    ));

    if ($user_id) {
        // Clear reminder history and current reminder
        delete_user_meta($user_id, 'reminder_change_date');
        update_user_meta($user_id, 'reminder', 'لا يوجد');
        wp_send_json_success('Reminder history cleared');
    } else {
        wp_send_json_error('Student not found');
    }
}







// Add AJAX handler for sending WhatsApp message when reminder changes
add_action('wp_ajax_send_whatsapp_reminder', 'send_whatsapp_reminder');
add_action('wp_ajax_nopriv_send_whatsapp_reminder', 'send_whatsapp_reminder');

function send_whatsapp_reminder()
{
    if (!isset($_POST['student_id']) || !isset($_POST['reminder'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    // Get the user data based on m_id
    $m_id = sanitize_text_field($_POST['student_id']);
    $reminder = sanitize_text_field($_POST['reminder']);
    $previous_reminder = isset($_POST['previous_reminder']) ? sanitize_text_field($_POST['previous_reminder']) : '';

    // Get user based on m_id
    $user = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $m_id,
        'number' => 1,
    ));

    if (empty($user)) {
        wp_send_json_error('User not found');
        return;
    }

    $student_id = $user[0]->ID;

    // Get payment phone number
    $payment_phone = get_user_meta($student_id, 'payment_phone', true);

    // Log the payment phone number for debugging
    error_log('Payment Phone: ' . $payment_phone);

    // Format phone number for WhatsApp (remove any spaces)
    $whatsapp_number = preg_replace('/\s+/', '', $payment_phone);

    // Ensure it has a country code (+ prefix)
    if (substr($whatsapp_number, 0, 1) !== '+') {
        $whatsapp_number = '+' . $whatsapp_number;
    }

    // For logging/display purposes only
    $chat_id = $whatsapp_number;

    // Only send message if there's a valid transition
    $valid_transition = false;
    $message = '';

    if ($previous_reminder === 'لا يوجد' && $reminder === 'التنبيه الاول') {
        $valid_transition = true;
        $message = 'أهلًا وسهلًا بيك! 👋🤩
نود أن نعلمك بأن اشتراكك الشهري قد انتهى، ونسعد باستمرارك معنا في رحلتك التعليمية. ✨
نحن نؤمن بأن الاستمرار في التعلم هو الأهم، لذلك نرجو منك الالتزام بحضور حصصك بشكل منتظم حتى يظل موعدك محجوزًا في جدول المعلم، ويمكنك إتمام الدفع في أي وقت يناسبك.
📅 إذا كنت بحاجة لأي مساعدة أو لديك أي استفسارات، لا تتردد في التواصل معنا، نحن هنا دائمًا لدعمك. 💙
تنويه: (يتم إرسال هذه الرسالة تلقائيًا من خلال الذكاء الاصطناعي) 🖥🤖.';
    } elseif ($previous_reminder === 'التنبيه الاول' && $reminder === 'التنبيه الثاني') {
        $valid_transition = true;
        $message = 'أهلًا وسهلًا بيك! 👋🤩
نود تذكيرك بأن اشتراكك لم يتم تجديده بعد، ونحب أن تستمر معنا في رحلتنا التعليمية.
إذا كنت بحاجة إلى بعض الوقت لإتمام الدفع، يرجى الرد على هذه الرسالة وإبلاغي بالوقت الذي يناسبك، وسأقوم بتذكيرك في الموعد الذي تحدده، حتى لا نزعجك برسائل إضافية.
📅 إذا كنت بحاجة إلى أي مساعدة، لا تتردد في التواصل معنا، يسعدنا دعمك دائمًا. 💙
تنويه: (يتم إرسال هذه الرسالة تلقائيًا من خلال الذكاء الاصطناعي) 🖥🤖.';
    } elseif ($previous_reminder === 'التنبيه الثاني' && $reminder === 'التنبيه التالت') {
        $valid_transition = true;
        $message = 'أهلًا وسهلًا بيك! 👋🤩
لاحظنا أن اشتراكك لم يتم تجديده بعد، ونتمنى أن تستمر معنا في حضور الحصص والاستفادة من دروسك.
إذا كنت بحاجة إلى مزيد من الوقت، يمكنك إبلاغي بالموعد الذي يناسبك لإتمام الدفع، وسأقوم بتذكيرك في الوقت الذي تحدده.
📅 إذا كنت بحاجة لأي مساعدة أو لديك أي استفسارات، نحن هنا دائمًا لدعمك. 💙
تنويه: (يتم إرسال هذه الرسالة تلقائيًا من خلال الذكاء الاصطناعي) 🖥🤖.';
    } elseif ($previous_reminder === 'التنبيه التالت' && $reminder === 'لم يتم الرد') {
        $valid_transition = true;
        $message = 'أهلًا وسهلًا بيك! 👋🤩
نود إبلاغك بأن اشتراكك لم يتم تجديده حتى الآن، وقد يؤثر ذلك على إمكانية حضور الحصص القادمة.
إذا كنت بحاجة إلى بعض الوقت، يمكنك إبلاغي بالوقت الذي يناسبك لإتمام الدفع، وسأقوم بتذكيرك حينها.
📅 لضمان استمرار حضورك، يُرجى تجديد الاشتراك في أقرب وقت ممكن. وإذا كنت تواجه أي ظروف خاصة، لا تتردد في التواصل معنا، فنحن هنا دائمًا لدعمك. 💙
تنويه: (يتم إرسال هذه الرسالة تلقائيًا من خلال الذكاء الاصطناعي) 🖥🤖.';
    }

    if (!$valid_transition || empty($message) || empty($chat_id)) {
        wp_send_json_error('Invalid transition or missing data');
        return;
    }

    // Use the unified WhatsApp sending function
    require_once dirname(dirname(dirname(__FILE__))) . '/includes/whatsapp.php';

    $args = array(
        'phone_number' => $whatsapp_number,
        'message' => $message,
        'm_id' => $m_id,
        'debug' => true,
        'force_resend' => true, // Force resend even if already sent today
        'add_to_log' => false   // Don't add to sent log to prevent duplicate checking
    );

    // Send WhatsApp message using the unified function
    $result = zuwad_send_whatsapp_message($args);

    // Log the attempt for debugging
    error_log('Payment reminder attempt details: ' . json_encode([
        'phone' => $whatsapp_number,
        'm_id' => $m_id,
        'reminder' => $reminder,
        'previous' => $previous_reminder,
        'result' => $result
    ]));

    if (!$result['success']) {
        error_log('WhatsApp API Error: ' . ($result['message'] ?? 'Unknown error'));
        wp_send_json_error(array('message' => 'WhatsApp sending failed: ' . $result['message']));
        return;
    }

    // Return success response with details
    wp_send_json_success([
        'message' => 'WhatsApp message sent successfully',
        'chat_id' => $chat_id,
        'reminder' => $reminder,
        'previous_reminder' => $previous_reminder
    ]);
}

// Add AJAX handler for fetching payment data
add_action('wp_ajax_fetch_payment_data', 'fetch_payment_data');
add_action('wp_ajax_nopriv_fetch_payment_data', 'fetch_payment_data');

function fetch_payment_data()
{
    global $wpdb;

    // Get current user
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;

    // Base query parameters
    $query_args = array(
        'role' => 'student',
        'meta_key' => 'payment_status',
        'meta_value' => 'في انتظار الدفع',
        'fields' => 'all'
    );

    // If not administrator, add m_id filter
    if (!current_user_can('administrator')) {
        if ($current_user_id == 27) {
            $query_args['meta_query'] = array(
                array(
                    'key' => 'm_id',
                    'value' => '^01',
                    'compare' => 'REGEXP'
                )
            );
        } elseif ($current_user_id == 65) {
            $query_args['meta_query'] = array(
                array(
                    'key' => 'm_id',
                    'value' => '^02',
                    'compare' => 'REGEXP'
                )
            );
        }
    }

    // Fetch students
    $students = get_users($query_args);

    // Prepare the data for the table
    $student_data = array();
    foreach ($students as $student) {
        $teacher_id = get_user_meta($student->ID, 'teacher', true);
        $student_data[] = array(
            'm_id' => get_user_meta($student->ID, 'm_id', true),
            'name' => $student->display_name,
            'teacher' => $teacher_id ? get_userdata($teacher_id)->display_name : '',
            'payment_phone' => get_user_meta($student->ID, 'payment_phone', true),
            'country' => get_user_meta($student->ID, 'country', true),
            'lessons_number' => get_user_meta($student->ID, 'lessons_number', true),
            'lesson_duration' => get_user_meta($student->ID, 'lesson_duration', true),
            'currency' => get_user_meta($student->ID, 'currency', true),
            'amount' => get_user_meta($student->ID, 'amount', true),
            'payment_status' => get_user_meta($student->ID, 'payment_status', true),
            'notes' => get_user_meta($student->ID, 'notes', true),
        );
    }

    wp_send_json_success($student_data);
}

// Payment receipt functionality has been moved to payment_receipts_shortcode.php

?>