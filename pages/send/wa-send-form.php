<?php

add_shortcode('waapi_media_sender', 'waapi_media_sender_shortcode');

function waapi_media_sender_shortcode()
{
    // // Enqueue necessary scripts and styles for this shortcode
    // wp_enqueue_script('waapi-scripts', plugin_dir_url(__FILE__) . 'wa-send-form.js', array('jquery'), '1.0.3', true);
    // wp_enqueue_style('waapi-styles', plugin_dir_url(__FILE__) . 'wa-send-form.css', array(), '1.0.3');
    
    // Localize the script with new data
    wp_localize_script('waapi-scripts', 'waapiData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('waapi-nonce'),
    ));

    ob_start();
?>
    <div class="waapi-media-sender-container" dir="rtl">
        <div class="waapi-header">
            <h2>إرسال رسائل واتساب للطلاب</h2>
            <p>قم برفع صورة وكتابة نص لإرسالها للطلاب</p>
        </div>

        <form id="waapi-media-form" enctype="multipart/form-data">
            <div class="waapi-form-group">
                <label for="teacher-filter">تحديد المعلمين:</label>
                <div class="custom-dropdown" id="teacher-filter-wrapper">
                    <div class="dropdown-header">
                        <span>تحديد المعلمين</span>
                        <span class="arrow">▼</span>
                    </div>
                    <div class="dropdown-content" style="display: none;">
                        <div class="dropdown-item filter-option" data-value="show_all">عرض الكل</div>
                        <div class="dropdown-item filter-option" data-value="hide_all">إخفاء الكل</div>
                        <div class="dropdown-divider"></div>
                        <?php
                        $teachers = get_users(['role__in' => ['teacher', 'supervisor', 'supervisor-quran', 'supervisor-islamic', 'supervisor-arabic', 'administrator']]);
                        foreach ($teachers as $teacher) {
                            echo "<div class='dropdown-item teacher-item filter-option' data-value='" . esc_attr($teacher->ID) . "' data-visible='1'>✔️ " . esc_html($teacher->display_name) . "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="waapi-form-group">
                <label for="waapi-image">رفع صورة (اختياري):</label>
                <div class="waapi-file-upload">
                    <input type="file" id="waapi-image" name="waapi-image" accept="image/*">
                    <span class="waapi-file-label">اختر صورة (اختياري)</span>
                </div>
                <div id="waapi-image-preview" class="waapi-image-preview"></div>
            </div>

            <div class="waapi-form-group">
                <label for="waapi-message">الرسالة:</label>
                <textarea id="waapi-message" name="waapi-message" rows="4" placeholder="اكتب رسالتك هنا..." required></textarea>
            </div>

            <div class="waapi-form-actions">
                <button type="submit" id="waapi-send-btn" class="waapi-send-btn">إرسال إلى رقم الاختبار</button>
                <button type="button" id="waapi-send-all-btn" class="waapi-send-btn" style="display: none;">إرسال إلى جميع الطلاب</button>
                <button type="button" id="waapi-stop-btn" class="waapi-stop-btn" style="display: none;">إيقاف الإرسال</button>
            </div>
        </form>

        <div id="waapi-progress-container" class="waapi-progress-container" style="display: none;">
            <div class="waapi-progress-info">
                <span id="waapi-progress-text">جاري الإرسال...</span>
                <span id="waapi-progress-percentage">0%</span>
            </div>
            <div id="waapi-progress" class="waapi-progress">
                <div class="waapi-progress-bar"></div>
            </div>
        </div>

        <div class="waapi-log-container" style="display: none;">
            <h3>سجل الإرسال</h3>
            <div id="waapi-log" class="waapi-log"></div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

add_action('wp_ajax_waapi_send_media', 'waapi_send_media_ajax_handler');
add_action('wp_ajax_nopriv_waapi_send_media', 'waapi_send_media_ajax_handler');

function waapi_send_media_ajax_handler()
{
    // More flexible nonce verification
    if (isset($_POST['nonce'])) {
        check_ajax_referer('waapi-nonce', 'nonce');
    } elseif (isset($_REQUEST['_ajax_nonce'])) {
        check_ajax_referer('waapi-nonce', '_ajax_nonce');
    }
    
    $message = sanitize_textarea_field($_POST['message']);
    $has_image = isset($_POST['image_data']) && !empty($_POST['image_data']);
    // Check if is_media parameter was explicitly set
    $is_media = isset($_POST['is_media']) ? filter_var($_POST['is_media'], FILTER_VALIDATE_BOOLEAN) : $has_image;
    // Get the m_id if provided, empty string otherwise
    $m_id = isset($_POST['m_id']) ? sanitize_text_field($_POST['m_id']) : '';

    if ($has_image) {
        // Process image data for media message
        $image_data = $_POST['image_data'];
        $encoded_image = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
        $encoded_image = str_replace(' ', '+', $encoded_image);

        // Pass m_id to the function, null for instance_id to use m_id-based logic
        // For media messages: is_media = true with the encoded image
        $result = waapi_send_message_to_number(
            '201146048290',  // test phone number
            $message,        // message (will be used as caption for media)
            null,            // instance_id (null to use m_id-based logic)
            $encoded_image,  // media_base64
            $is_media,       // is_media flag
            true,            // is_test = true
            $m_id            // m_id for instance selection
        );
    } else {
        // For text-only messages: is_media = false with no image data
        $result = waapi_send_message_to_number(
            '201146048290',  // test phone number
            $message,        // message (will be used as plain text)
            null,            // instance_id (null to use m_id-based logic)
            null,            // media_base64 = null for text-only
            false,           // is_media = false
            true,            // is_test = true
            $m_id            // m_id for instance selection
        );
    }

    wp_send_json($result);
    wp_die();
}

function waapi_get_sent_numbers_log()
{
    $upload_dir = wp_upload_dir();
    $log_dir = $upload_dir['basedir'] . '/waapi_logs';

    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    $log_file = $log_dir . '/sent_numbers_' . date('Y-m-d') . '.log';

    if (!file_exists($log_file)) {
        file_put_contents($log_file, '');
        chmod($log_file, 0644);
    }

    $content = file_get_contents($log_file);
    $sent_numbers = !empty($content) ? explode("\n", trim($content)) : array();

    return array(
        'file' => $log_file,
        'numbers' => $sent_numbers
    );
}

function waapi_add_to_sent_log($phone_number)
{
    $log = waapi_get_sent_numbers_log();

    // فقط إضافة إذا لم يكن موجودًا بالفعل في السجل
    if (!in_array($phone_number, $log['numbers'])) {
        $log['numbers'][] = $phone_number;
        file_put_contents($log['file'], implode("\n", $log['numbers']));
    }
}

function waapi_has_been_sent($phone_number)
{
    $log = waapi_get_sent_numbers_log();
    return in_array($phone_number, $log['numbers']);
}

function waapi_reset_sent_log()
{
    $log = waapi_get_sent_numbers_log();
    file_put_contents($log['file'], '');
    return true;
}

add_action('wp_ajax_waapi_reset_log', 'waapi_reset_log_ajax_handler');
add_action('wp_ajax_nopriv_waapi_reset_log', 'waapi_reset_log_ajax_handler');

function waapi_reset_log_ajax_handler()
{
    // More flexible nonce verification
    if (isset($_POST['nonce'])) {
        check_ajax_referer('waapi-nonce', 'nonce');
    } elseif (isset($_REQUEST['_ajax_nonce'])) {
        check_ajax_referer('waapi-nonce', '_ajax_nonce');
    }
    
    $result = waapi_reset_sent_log();

    wp_send_json(array(
        'success' => $result,
        'message' => $result ? 'تم إعادة تعيين السجل بنجاح' : 'حدث خطأ أثناء إعادة تعيين السجل'
    ));

    wp_die();
}

// The waapi_send_message_to_number function is now defined in includes/whatsapp.php
// This version is removed to prevent duplicate function declarations
// Using the unified WhatsApp messaging system

// נקודת נהאית AJAX לארסל לכל הסטודנטים
add_action('wp_ajax_waapi_send_to_all_students', 'waapi_send_to_all_students_ajax_handler');
add_action('wp_ajax_nopriv_waapi_send_to_all_students', 'waapi_send_to_all_students_ajax_handler');

function waapi_send_to_all_students_ajax_handler()
{
    // More flexible nonce verification
    if (isset($_POST['nonce'])) {
        check_ajax_referer('waapi-nonce', 'nonce');
    } elseif (isset($_REQUEST['_ajax_nonce'])) {
        check_ajax_referer('waapi-nonce', '_ajax_nonce');
    }
    
    // الحصول على بيانات النموذج
    $message = sanitize_textarea_field($_POST['message']);
    $page = intval($_POST['page']);
    $visible_teachers = isset($_POST['visible_teachers']) ? array_map('intval', $_POST['visible_teachers']) : array();

    // التحقق مما إذا كانت بيانات الصورة قد تم توفيرها
    $has_image = isset($_POST['image_data']) && !empty($_POST['image_data']);
    $encoded_image = null;

    if ($has_image) {
        $image_data = $_POST['image_data'];
        $encoded_image = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
        $encoded_image = str_replace(' ', '+', $encoded_image);
    }

    // تحديد حالة الإرسال
    $sending_status = get_option('waapi_sending_status', 'running');

    if ($sending_status === 'terminated') {
        wp_send_json(array(
            'success' => false,
            'message' => 'تم إنهاء الإرسال بواسطة المستخدم',
            'status' => 'terminated'
        ));
        wp_die();
    } elseif ($sending_status === 'paused') {
        wp_send_json(array(
            'success' => false,
            'message' => 'تم إيقاف الإرسال مؤقتًا',
            'status' => 'paused'
        ));
        wp_die();
    }

    // الحصول على إجمالي عدد الطلاب أولاً
    $args = array(
        'role' => 'student',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'phone',
                'compare' => 'EXISTS',
            ),
        ),
        'number' => -1,
        'orderby' => 'display_name',
        'order' => 'ASC',
    );

    $user_query = new WP_User_Query($args);
    $all_students = $user_query->get_results();

    // تصفية الطلاب بناءً على المعلمين المحددين
    $filtered_students = array();
    if (!empty($visible_teachers)) {
        foreach ($all_students as $student) {
            $student_teacher_id = get_user_meta($student->ID, 'teacher', true);
            // Convert to string for comparison
            $student_teacher_id = (string)$student_teacher_id;
            
            if (in_array($student_teacher_id, array_map('strval', $visible_teachers))) {
                $filtered_students[] = $student;
            }
        }
    } else {
        $filtered_students = $all_students;
    }

    $total_students = count($filtered_students);

    // إذا لم يتم العثور على طلاب، ارجع مبكرًا
    if ($total_students === 0) {
        wp_send_json(array(
            'success' => true,
            'results' => array(),
            'total_users' => 0,
            'processed' => 0,
            'sent' => 0,
            'skipped' => 0,
            'progress' => 0,
            'current_page' => $page,
            'has_more' => false,
            'status' => 'completed'
        ));
        wp_die();
    }

    // الحصول على الدفعة الحالية من الطلاب
    $batch_size = 5;
    $start = ($page - 1) * $batch_size;
    $current_batch = array_slice($filtered_students, $start, $batch_size);

    $results = array();
    $processed_count = 0;
    $skipped_count = 0;
    $sent_count = 0;
    $all_skipped = true;

    foreach ($current_batch as $user) {
        $processed_count++;

        // الحصول على الهاتف لهذا المستخدم
        $phone = get_user_meta($user->ID, 'phone', true);

        // تجاهل إذا لم يكن هناك هاتف
        if (empty($phone)) {
            continue;
        }

        // الحصول على اسم الطالب
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        $display_name = $user->display_name;

        // استخدام الاسم الأنسب المتاح
        $student_name = '';
        if (!empty($first_name) && !empty($last_name)) {
            $student_name = $first_name . ' ' . $last_name;
        } elseif (!empty($display_name)) {
            $student_name = $display_name;
        } else {
            $student_name = $user->user_login;
        }

        // التحقق مما إذا كان هذا الرقم قد تم إرساله بالفعل
        if (waapi_has_been_sent($phone)) {
            $skipped_count++;
            $results[] = array(
                'user_id' => $user->ID,
                'phone' => $phone,
                'name' => $student_name,
                'result' => array(
                    'success' => 'skipped',
                    'message' => 'تم تخطي هذا الرقم لأنه استلم الرسالة بالفعل'
                )
            );
            continue;
        }

        $all_skipped = false;

        // Get student's m_id for proper instance selection
        $m_id = get_user_meta($user->ID, 'm_id', true);
        
        // Send the message appropriately based on whether we have media or not
        if ($has_image && !empty($encoded_image)) {
            // For media messages: is_media = true
            $result = waapi_send_message_to_number(
                $phone,          // student phone number
                $message,        // message (will be used as caption for media)
                null,            // instance_id (null to use m_id-based logic)
                $encoded_image,  // media_base64
                true,            // is_media = true for media messages
                false,           // is_test = false for real messages
                $m_id            // m_id for instance selection
            );
        } else {
            // For text-only messages: is_media = false
            $result = waapi_send_message_to_number(
                $phone,          // student phone number
                $message,        // message (will be used as plain text)
                null,            // instance_id (null to use m_id-based logic)
                null,            // media_base64 = null for text-only
                false,           // is_media = false for text-only messages
                false,           // is_test = false for real messages
                $m_id            // m_id for instance selection
            );
        }

        if ($result['success']) {
            $sent_count++;
            // Only add to sent log if the message was actually sent successfully
            // and it's not a test message
            if (isset($result['add_to_log']) && $result['add_to_log'] &&
                isset($result['is_test']) && !$result['is_test'] &&
                function_exists('waapi_add_to_sent_log')) {
                waapi_add_to_sent_log($phone);
            }
        }

        $results[] = array(
            'user_id' => $user->ID,
            'phone' => $phone,
            'name' => $student_name,
            'result' => $result
        );

        // الانتظار 20 ثانية بين الرسائل
        if ($processed_count < count($current_batch)) {
            sleep(20);
        }

        // التحقق من حالة الإرسال
        $sending_status = get_option('waapi_sending_status', 'running');
        if ($sending_status !== 'running') {
            break;
        }
    }

    // حساب التقدم
    $processed = $start + $processed_count;
    $progress = $total_students > 0 ? min(100, round(($processed / $total_students) * 100)) : 0;

    // Get cumulative skipped count from all previous batches
    $total_skipped = get_option('waapi_total_skipped_count', 0);
    $total_skipped += $skipped_count;
    update_option('waapi_total_skipped_count', $total_skipped);

    // Get cumulative sent count from all previous batches
    $total_sent = get_option('waapi_total_sent_count', 0);
    $total_sent += $sent_count;
    update_option('waapi_total_sent_count', $total_sent);

    // تحديد ما إذا كان يجب الاستمرار
    $has_more = false;
    $status = $sending_status;

    if ($processed >= $total_students) {
        // تمت معالجة جميع الطلاب
        $status = 'completed';
        $has_more = false;
    } elseif ($sending_status === 'running') {
        $has_more = true;
    }

    wp_send_json(array(
        'success' => true,
        'results' => $results,
        'total_users' => $total_students,
        'processed' => $processed,
        'sent' => $sent_count, // Current batch sent count
        'skipped' => $skipped_count, // Current batch skipped count
        'total_sent' => $total_sent, // Cumulative sent count
        'total_skipped' => $total_skipped, // Cumulative skipped count
        'progress' => $progress,
        'current_page' => $page,
        'has_more' => $has_more,
        'status' => $status
    ));

    wp_die();
}

// نقطة نهاية Ajax لإدارة عملية الإرسال
add_action('wp_ajax_waapi_manage_sending', 'waapi_manage_sending_ajax_handler');
add_action('wp_ajax_nopriv_waapi_manage_sending', 'waapi_manage_sending_ajax_handler');

function waapi_manage_sending_ajax_handler()
{
    // More flexible nonce verification
    if (isset($_POST['nonce'])) {
        check_ajax_referer('waapi-nonce', 'nonce');
    } elseif (isset($_REQUEST['_ajax_nonce'])) {
        check_ajax_referer('waapi-nonce', '_ajax_nonce');
    } else {
        wp_send_json_error('فشل التحقق الأمني');
        wp_die();
    }
    
    $action = isset($_POST['action_type']) ? $_POST['action_type'] : '';
    $message = '';

    switch ($action) {
        case 'start':
            update_option('waapi_sending_status', 'running');
            // Reset cumulative counters when starting a new sending session
            update_option('waapi_total_skipped_count', 0);
            update_option('waapi_total_sent_count', 0);
            $message = 'تم بدء إرسال الرسائل';
            break;
        case 'pause':
            update_option('waapi_sending_status', 'paused');
            $message = 'تم إيقاف إرسال الرسائل مؤقتًا';
            break;
        case 'resume':
            update_option('waapi_sending_status', 'running');
            $message = 'تم استئناف إرسال الرسائل';
            break;
        case 'terminate':
            update_option('waapi_sending_status', 'terminated');
            $message = 'تم إنهاء إرسال الرسائل';
            break;
        default:
            wp_send_json_error('إجراء غير معروف');
            wp_die();
    }

    wp_send_json_success(array(
        'message' => $message,
        'status' => get_option('waapi_sending_status', 'running')
    ));
    wp_die();
}

// إضافة نقطة نهاية AJAX جديدة للحصول على عدد الطلاب
add_action('wp_ajax_waapi_get_student_count', 'waapi_get_student_count_ajax_handler');
add_action('wp_ajax_nopriv_waapi_get_student_count', 'waapi_get_student_count_ajax_handler');

function waapi_get_student_count_ajax_handler() {
    // More flexible nonce verification
    if (isset($_POST['nonce'])) {
        check_ajax_referer('waapi-nonce', 'nonce');
    } elseif (isset($_REQUEST['_ajax_nonce'])) {
        check_ajax_referer('waapi-nonce', '_ajax_nonce');
    }
    
    $visible_teachers = isset($_POST['visible_teachers']) ? array_map('intval', $_POST['visible_teachers']) : array();
    
    // Debug information
    error_log('Selected teachers: ' . json_encode($visible_teachers));
    
    // الحصول على جميع الطلاب الذين لديهم أرقام هواتف
    $args = array(
        'role' => 'student',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'phone',
                'compare' => 'EXISTS',
            ),
        ),
        'number' => -1, // الحصول على جميع الطلاب
        'orderby' => 'display_name',
        'order' => 'ASC',
    );

    $user_query = new WP_User_Query($args);
    $all_students = $user_query->get_results();
    
    error_log('Total students with phone: ' . count($all_students));

    // تصفية الطلاب بناءً على المعلمين المحددين
    $filtered_students = array();
    $selected_teachers_names = array();
    
    if (!empty($visible_teachers)) {
        foreach ($all_students as $student) {
            // Try different possible meta keys for teacher assignment
            $student_teacher_id = get_user_meta($student->ID, 'teacher', true);
            
            // Debug info for the first few students
            if (count($filtered_students) < 5) {
                error_log("Student ID: {$student->ID}, Name: {$student->display_name}, Teacher ID: {$student_teacher_id}, Phone: " . get_user_meta($student->ID, 'phone', true));
            }
            
            // Convert to string for comparison
            $student_teacher_id = (string)$student_teacher_id;
            
            // Check if this student's teacher is in the selected teachers
            if (in_array($student_teacher_id, array_map('strval', $visible_teachers))) {
                $filtered_students[] = $student;
            }
        }

        // الحصول على أسماء المعلمين
        foreach ($visible_teachers as $teacher_id) {
            $teacher = get_user_by('ID', $teacher_id);
            if ($teacher) {
                $selected_teachers_names[] = $teacher->display_name;
            }
        }
    } else {
        // إذا لم يتم تحديد أي معلمين، أرجع نتيجة فارغة
        wp_send_json(array(
            'success' => true,
            'total_students' => 0,
            'selected_teachers' => 'لم يتم تحديد أي معلمين'
        ));
        wp_die();
    }
    
    error_log('Filtered students count: ' . count($filtered_students));

    wp_send_json(array(
        'success' => true,
        'total_students' => count($filtered_students),
        'selected_teachers' => implode('، ', $selected_teachers_names)
    ));
    wp_die();
}
