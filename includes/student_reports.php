<?php

function add_report_shortcode()
{


    ob_start(); // Start output buffering
?>
    <!-- Button to trigger modal -->
    <?php
    if (current_user_can('supervisor') || current_user_can('administrator') || current_user_can('sales')) {
        // Display the button only for supervisors or administrators
        echo '<button id="open-report-modal" class="zuwad-button">✨ إضافة انجاز</button>';
    }

    // echo '<button id="open-report-modal" class="zuwad-button">✨ إضافة انجاز</button>';

    ?>

    <!-- Modal -->
    <div id="report-modal" class="zuwad-modal" style="display: none;">
        <div class="zuwad-modal-content">
            <span id="close-report-modal" class="zuwad-close-modal">&times;</span>
            <h2>✨ إضافة انجاز</h2>
            <div class="zuwad-form-row">
                <!-- Search bar for students -->
                <input type="text" id="student-search-report" placeholder="بحث بالاسم أو المعرف">
                <!-- Hidden input to store selected student ID -->
                <input type="hidden" id="selected-student-id-report">

                <!-- Report form -->
                <div class="zuwad-form-col">

                    <form id="report-form" class="zuwad-form">
                        <!-- <label for="attendance">الحضور</label> -->
                        <select id="attendance" name="attendance">
                        <?php 
                        // Check if current user is a teacher
                        $user = wp_get_current_user();
                        $is_teacher = in_array('teacher', (array) $user->roles);
                        
                        if ($is_teacher) {
                            // Show only حضور and غياب options for teachers
                            echo '<option value="حضور">حضور</option>';
                            echo '<option value="غياب">غياب</option>';
                        } else {
                            // Show all options for other roles
                            echo '<option value="حضور">حضور</option>';
                            echo '<option value="غياب">غياب</option>';
                            echo '<option value="تأجيل المعلم">تأجيل المعلم</option>';
                            echo '<option value="تأجيل ولي أمر">تأجيل ولي أمر</option>';
                            echo '<option value="تعويض التأجيل">تعويض التأجيل</option>';
                            echo '<option value="تعويض الغياب">تعويض الغياب</option>';
                            echo '<option value="تجريبي">تجريبي</option>';
                        }
                        ?>
                        </select>
                </div>



                <div class="zuwad-form-col">
                    <!-- <label for="session-number">رقم الحلقة</label> -->
                    <input type="text" id="session-number" name="session_number" readonly placeholder="رقم الحصة">
                </div>

            </div>

            <div id="search-results-report" class="zuwad-search-results"></div>

            <div id="last-report-date" style="display: none; margin-bottom: 10px;"></div>



            <div class="zuwad-form-row">
                <div class="zuwad-form-col">
                    <label for="date">التاريخ</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <div class="zuwad-form-col">
                    <label for="evaluation">التقييم</label>
                    <select id="evaluation" name="evaluation">
                        <option value="ماهر ⭐⭐⭐⭐⭐">ماهر ⭐⭐⭐⭐⭐</option>
                        <option value="محترف ⭐⭐⭐⭐">محترف ⭐⭐⭐⭐</option>
                        <option value="رائع ⭐⭐⭐">رائع ⭐⭐⭐</option>
                        <option value="متميز ⭐⭐">متميز ⭐⭐</option>
                        <option value="مجتهد ⭐">مجتهد ⭐</option>

                    </select>
                </div>
            </div>
            <!-- <div class="zuwad-form-row">
                    <div class="zuwad-form-col">
                        <label for="grade">الدرجة</label>
                        <input type="number" id="grade" name="grade" max="50">
                    </div>
                </div> -->


            <br>
            <div class="line-with-text">
                <span> الحصة السابقة </span>
            </div>




            <div class="zuwad-form-row">
                <div class="zuwad-form-col">
                    <label for="tasmii">تم تسميع</label>
                    <input type="text" id="tasmii" name="tasmii">
                </div>


                <div class="zuwad-form-col">
                    <label for="tahfiz">تم تحفيظ</label>
                    <input type="text" id="tahfiz" name="tahfiz">
                </div>

                <div class="zuwad-form-col">
                    <label for="mourajah">تم مراجعة</label>
                    <input type="text" id="mourajah" name="mourajah">
                </div>

            </div>


            <br>
            <div class="line-with-text">
                <span> الحصة القادمة </span>
            </div>



            <div class="zuwad-form-row">


                <div class="zuwad-form-col">
                    <label for="next_tasmii">سيتم تسميع</label>
                    <input type="text" id="next_tasmii" name="next_tasmii">
                </div>
                <div class="zuwad-form-col">
                    <label for="next_mourajah">سيتم مراحعة</label>
                    <input type="text" id="next_mourajah" name="next_mourajah">
                </div>

            </div>
            <div class="zuwad-form-row">

                <div class="zuwad-form-col">
                    <label for="notes">ملاحظات</label>
                    <input type="text" id="notes" name="notes">
                </div>


                <div class="zuwad-form-col">
                    <label for="zoom-image">صورة من حصة الزوم</label>
                    <input type="file" id="zoom-image" name="zoom_image[]" multiple accept="image/*">
                </div>

            </div>

            <div class="zuwad-form-row">

                <div class="zuwad-form-col">
                    <!-- Container to display uploaded images -->
                    <div id="zoom-image-preview" class="zuwad-form-row"></div>
                </div>
            </div>
            <input type="hidden" id="time" name="time" value="">

            <div class="button-row">
                <button type="button" id="share-report" class="zuwad-submit-button">📤 مشاركة</button>
                <button type="submit" class="zuwad-submit-button">💾 حفظ الانجاز</button>
                <!-- The "Remove Button" will be appended here dynamically -->
            </div>
            </form>
        </div>
    </div>
<?php
    return ob_get_clean(); // Return the output
}
add_shortcode('add_report', 'add_report_shortcode');







add_action('wp_ajax_delete_report', 'handle_delete_report');
add_action('wp_ajax_nopriv_delete_report', 'handle_delete_report');

function handle_delete_report()
{
    check_ajax_referer('zuwad_plugin_nonce', '_ajax_nonce');

    $report_id = intval($_POST['report_id']);
    //  error_log('Attempting to delete report with ID: ' . $report_id);

    if (!$report_id) {
        //  error_log('Invalid report ID provided.');
        wp_send_json_error('Invalid report ID.');
    }

    global $wpdb;
    $deleted = $wpdb->delete('wp_student_reports', ['id' => $report_id]);

    if ($deleted) {
        //  error_log('Report deleted successfully for report ID: ' . $report_id);
        wp_send_json_success('Report deleted successfully.');
    } else {
        //  error_log('Failed to delete report for report ID: ' . $report_id);
        wp_send_json_error('Failed to delete report.');
    }
}
















// Add action hooks for handle_get_report_data
add_action('wp_ajax_handle_get_report_data', 'handle_get_report_data');
add_action('wp_ajax_nopriv_handle_get_report_data', 'handle_get_report_data');

add_action('wp_ajax_get_report_data', 'handle_get_report_data');
add_action('wp_ajax_nopriv_get_report_data', 'handle_get_report_data');

function handle_get_report_data()
{
    check_ajax_referer('zuwad_plugin_nonce', '_ajax_nonce');

    $report_id = intval($_POST['report_id']);

    if (!$report_id) {
        error_log('Invalid report ID provided in handle_get_report_data');
        wp_send_json_error('Invalid report ID.');
        return;
    }

    global $wpdb;

    // Implement retry logic with exponential backoff for race condition handling
    $max_attempts = 5;
    $attempt = 0;
    $report_data = null;

    while ($attempt < $max_attempts && !$report_data) {
        $attempt++;

        // Progressive delay: 1s, 2s, 3s, 4s, 5s
        if ($attempt > 1) {
            sleep($attempt);
            error_log("Retry attempt $attempt for report ID: $report_id");
        }

        // Try to get the report data
        $report_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wp_student_reports WHERE id = %d",
            $report_id
        ));

        if ($report_data) {
            error_log("Successfully found report data on attempt $attempt for report ID: $report_id");
            break;
        }

        if ($attempt < $max_attempts) {
            error_log("Report not found on attempt $attempt for report ID: $report_id, retrying...");
        }
    }

    if (!$report_data) {
        error_log("No report data found for report ID: $report_id after $max_attempts attempts");
        wp_send_json_error("No report data found for report ID: $report_id. The report may still be processing. Please try again in a few seconds.");
        return;
    }

    // Fetch additional student details from usermeta
    $student_id = $report_data->student_id;
    $student_name = get_userdata($student_id)->display_name;
    $lesson_duration = get_user_meta($student_id, 'lesson_duration', true);
    $lessons_number = get_user_meta($student_id, 'lessons_number', true);
    $m_id = get_user_meta($student_id, 'm_id', true);

    // Add student details to the report data
    $report_data->student_name = $student_name;
    $report_data->lesson_duration = $lesson_duration;
    $report_data->lessons_number = $lessons_number;
    $report_data->m_id = $m_id;

    // Log successful data retrieval
    error_log('Successfully retrieved report data for ID: ' . $report_id);

    // Return the report data as JSON
    wp_send_json_success($report_data);
}



add_action('wp_ajax_upload_file', 'handle_upload_file');
add_action('wp_ajax_nopriv_upload_file', 'handle_upload_file');

function handle_upload_file()
{
    check_ajax_referer('zuwad_plugin_nonce', '_ajax_nonce');

    // Get the file data from the request
    $file_data = $_POST['file_data'];
    $file_type = $_POST['file_type']; // 'pdf' or 'image'
    $file_data = str_replace('data:application/pdf;base64,', '', $file_data);
    $file_data = str_replace('data:image/png;base64,', '', $file_data);
    $file_data = base64_decode($file_data);

    if (!$file_data) {
        wp_send_json_error('Invalid file data.');
    }

    // Save the file to a temporary directory
    $upload_dir = wp_upload_dir();
    $file_name = 'student_report_' . time() . '.' . $file_type;
    $file_path = $upload_dir['path'] . '/' . $file_name;
    $result = file_put_contents($file_path, $file_data);

    if ($result === false) {
        wp_send_json_error('Failed to save file.');
    }

    // Return the URL to the file
    wp_send_json_success(['url' => $upload_dir['url'] . '/' . $file_name]);
}



function get_existing_report()
{
    global $wpdb;

    // Set the default timezone to Cairo
    date_default_timezone_set('Africa/Cairo');

    // Get the student ID, date, and time from the request
    $student_id = intval($_POST['student_id']);
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : null;
    $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : null;

    if (!$student_id || !$date || !$time) {
        wp_send_json_error('Invalid data.');
    }

    // Combine date and time into a single string
    $datetime_string = $date . ' ' . $time;

    // Create a DateTime object in Cairo timezone
    $datetime = new DateTime($datetime_string, new DateTimeZone('Africa/Cairo'));

    // Format the date and time for the database
    $formatted_date = $datetime->format('Y-m-d');
    $formatted_time = $datetime->format('H:i:s');

    //  error_log('Formatted date: ' . $formatted_date);
    //  error_log('Formatted time: ' . $formatted_time);

    // Fetch the last report for the student
    $last_report = $wpdb->get_row($wpdb->prepare(
        "SELECT date, session_number FROM wp_student_reports WHERE student_id = %d ORDER BY date DESC LIMIT 1",
        $student_id
    ));

    // Fetch the student's lessons_number from usermeta
    $lessons_number = intval(get_user_meta($student_id, 'lessons_number', true));

    // Check if a report exists for the given date and time
    $existing_report = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM wp_student_reports WHERE student_id = %d AND date = %s AND time = %s",
        $student_id,
        $formatted_date,
        $formatted_time
    ));

    if ($existing_report) {
        // Decode the JSON-encoded image URLs
        $existing_report->zoom_image_url = json_decode($existing_report->zoom_image_url, true);
    }

    if ($last_report) {
        wp_send_json_success([
            'date' => $last_report->date,
            'last_session_number' => intval($last_report->session_number),
            'lessons_number' => $lessons_number,
            'existing_report' => $existing_report // Include all existing report data
        ]);
    } else {
        wp_send_json_success([
            'date' => 'لا يوجد تقارير سابقة.',
            'last_session_number' => 0,
            'lessons_number' => $lessons_number,
            'existing_report' => $existing_report // Include all existing report data
        ]);
    }
}


// Get the last report date and session number for a student
add_action('wp_ajax_get_last_report_date', 'get_last_report_date');
add_action('wp_ajax_nopriv_get_last_report_date', 'get_last_report_date');

function get_last_report_date()
{
    global $wpdb;
    $student_id = intval($_POST['student_id']);

    if (!$student_id) {
        wp_send_json_error('Invalid student ID.');
    }

    // Fetch ALL reports for the student, ordered by date and time
    $all_reports = $wpdb->get_results($wpdb->prepare(
        "SELECT date, session_number, attendance 
         FROM wp_student_reports 
         WHERE student_id = %d 
         ORDER BY date DESC, time DESC",
        $student_id
    ));

    // Fetch the student's lessons_number from usermeta
    $lessons_number = intval(get_user_meta($student_id, 'lessons_number', true));

    // Fetch the student's previous_lesson and previous_lesson_used from usermeta
    $previous_lesson = intval(get_user_meta($student_id, 'previous_lesson', true));
    $previous_lesson_used = get_user_meta($student_id, 'previous_lesson_used', true);
    
    // Find the last INCREMENTING report (not تعويض التأجيل, تعويض الغياب, تجريبي, اجازة معلم)
    // اجازة معلم should be completely ignored for next session calculation
    $last_incrementing_report = null;
    $incrementing_attendances = ['حضور', 'غياب', 'تأجيل المعلم', 'تأجيل ولي أمر'];

    foreach ($all_reports as $report) {
        if (in_array($report->attendance, $incrementing_attendances)) {
            $last_incrementing_report = $report;
            break; // Found the most recent incrementing report
        }
    }

    // Calculate the next session number based on the rules
    $next_session_number = 1; // Default for new students

    if ($last_incrementing_report) {
        $last_session = intval($last_incrementing_report->session_number);

        // Always increment by 1 for incrementing attendances
        $next_session_number = $last_session + 1;

        // Handle reset logic when reaching lessons_number
        if ($lessons_number > 0 && $next_session_number > $lessons_number) {
            $next_session_number = 1; // Reset to 1 after completing all lessons
        }

        // Ensure we never have a session number less than 1
        if ($next_session_number < 1) {
            $next_session_number = 1;
        }
        
        wp_send_json_success([
            'date' => $last_incrementing_report->date,
            'last_session_number' => $last_session,
            'next_session_number' => $next_session_number,
            'lessons_number' => $lessons_number,
            'previous_lesson' => $previous_lesson,
            'previous_lesson_used' => $previous_lesson_used
        ]);
    } else {
        // No valid reports found
        wp_send_json_success([
            'date' => 'لا يوجد تقارير سابقة.',
            'last_session_number' => 0,
            'next_session_number' => 1, // Always start with 1 for new students
            'lessons_number' => $lessons_number,
            'previous_lesson' => $previous_lesson,
            'previous_lesson_used' => $previous_lesson_used
        ]);
    }
}

// Get session number for specific attendance type
add_action('wp_ajax_get_session_number_for_attendance', 'get_session_number_for_attendance');
add_action('wp_ajax_nopriv_get_session_number_for_attendance', 'get_session_number_for_attendance');

function get_session_number_for_attendance()
{
    global $wpdb;
    $student_id = intval($_POST['student_id']);
    $attendance = sanitize_text_field($_POST['attendance']);

    if (!$student_id || !$attendance) {
        wp_send_json_error('Invalid parameters.');
    }

    // Define non-valid attendances that should always use session number 0
    $non_valid_attendances = ['تعويض التأجيل', 'تعويض الغياب', 'تجريبي'];

    // If this is a non-valid attendance type, always return 0
    if (in_array($attendance, $non_valid_attendances)) {
        wp_send_json_success([
            'session_number' => 0,
            'lessons_number' => intval(get_user_meta($student_id, 'lessons_number', true))
        ]);
        return;
    }

    // Fetch ALL reports for the student, ordered by date and time
    $all_reports = $wpdb->get_results($wpdb->prepare(
        "SELECT date, session_number, attendance
         FROM wp_student_reports
         WHERE student_id = %d
         ORDER BY date DESC, time DESC",
        $student_id
    ));

    // Fetch the student's lessons_number from usermeta
    $lessons_number = intval(get_user_meta($student_id, 'lessons_number', true));

    if ($attendance === 'اجازة معلم') {
        // For اجازة معلم, find the last incrementing report and use its session number
        $incrementing_attendances = ['حضور', 'غياب', 'تأجيل المعلم', 'تأجيل ولي أمر'];

        foreach ($all_reports as $report) {
            if (in_array($report->attendance, $incrementing_attendances)) {
                wp_send_json_success([
                    'session_number' => intval($report->session_number),
                    'lessons_number' => $lessons_number
                ]);
                return;
            }
        }

        // No incrementing reports found, default to 1
        wp_send_json_success([
            'session_number' => 1,
            'lessons_number' => $lessons_number
        ]);
    } else {
        // For other attendances, use the regular logic from get_last_report_date
        // Find the last incrementing report
        $incrementing_attendances = ['حضور', 'غياب', 'تأجيل المعلم', 'تأجيل ولي أمر'];

        foreach ($all_reports as $report) {
            if (in_array($report->attendance, $incrementing_attendances)) {
                $last_session = intval($report->session_number);
                $next_session_number = $last_session + 1;

                // Handle reset logic when reaching lessons_number
                if ($lessons_number > 0 && $next_session_number > $lessons_number) {
                    $next_session_number = 1;
                }

                wp_send_json_success([
                    'session_number' => $next_session_number,
                    'lessons_number' => $lessons_number
                ]);
                return;
            }
        }

        // No incrementing reports found, default to 1
        wp_send_json_success([
            'session_number' => 1,
            'lessons_number' => $lessons_number
        ]);
    }
}

// Get the student's lessons_number
add_action('wp_ajax_get_student_lessons_number', 'get_student_lessons_number');
add_action('wp_ajax_nopriv_get_student_lessons_number', 'get_student_lessons_number');

function get_student_lessons_number()
{
    $student_id = intval($_POST['student_id']);

    if (!$student_id) {
        wp_send_json_error('Invalid student ID.');
    }

    // Fetch the student's lessons_number from usermeta
    $lessons_number = get_user_meta($student_id, 'lessons_number', true);

    if ($lessons_number) {
        wp_send_json_success(['lessons_number' => $lessons_number]);
    } else {
        wp_send_json_success(['lessons_number' => 0]);
    }
}




add_action('wp_ajax_update_previous_lesson_used', 'update_previous_lesson_used');
add_action('wp_ajax_nopriv_update_previous_lesson_used', 'update_previous_lesson_used');

function update_previous_lesson_used()
{
    $student_id = intval($_POST['student_id']);

    if (!$student_id) {
        wp_send_json_error('Invalid student ID.');
    }

    // Update previous_lesson_used to true
    update_user_meta($student_id, 'previous_lesson_used', true);

    wp_send_json_success('Previous lesson marked as used.');
}




add_action('wp_ajax_update_previous_lesson_and_session_numbers', 'update_previous_lesson_and_session_numbers');
add_action('wp_ajax_nopriv_update_previous_lesson_and_session_numbers', 'update_previous_lesson_and_session_numbers');

function update_previous_lesson_and_session_numbers()
{
    global $wpdb;
    //  error_log('update_previous_lesson_and_session_numbers function called.');

    if (!isset($_POST['student_id']) || !isset($_POST['previous_lesson'])) {
        //  error_log('Invalid input: student_id or previous_lesson missing.');
        wp_send_json_error('Invalid input.');
    }

    $student_m_id = sanitize_text_field($_POST['student_id']);
    $new_previous_lesson = intval($_POST['previous_lesson']);

    //  error_log('Student m_id: ' . $student_m_id);
    //  error_log('New Previous Lesson: ' . $new_previous_lesson);

    // جلب user_id بناءً على m_id
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM wp_usermeta WHERE meta_key = 'm_id' AND meta_value = %s",
        $student_m_id
    ));

    if (!$user_id) {
        //  error_log('User ID not found for m_id: ' . $student_m_id);
        wp_send_json_error('Student not found.');
    }

    //  error_log('User ID found: ' . $user_id);

    // جلب عدد الدروس الخاصة بالطالب
    $lessons_number = intval(get_user_meta($user_id, 'lessons_number', true));
    //  error_log('Lessons Number: ' . $lessons_number);

    if (!$lessons_number) {
        //  error_log('Lessons number not found for the student.');
        wp_send_json_error('Lessons number not found for the student.');
    }

    // تحديث previous_lesson في usermeta
    update_user_meta($user_id, 'previous_lesson', $new_previous_lesson);
    //  error_log('Updated previous_lesson in usermeta.');

    // جلب التقارير مع **استبعاد** الحالات غير المرغوبة في attendance
    $reports = $wpdb->get_results($wpdb->prepare(
        "SELECT id, session_number FROM wp_student_reports 
         WHERE student_id = %d 
         AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
         ORDER BY date ASC, time ASC",
        $user_id
    ));

    if (!$reports) {
        //  error_log('No eligible reports found for the student.');
        wp_send_json_success('No eligible reports found for the student.');
    }

    // تحديث أرقام الجلسات للصفوف المؤهلة فقط
    foreach ($reports as $index => $report) {
        $new_session_number = ($new_previous_lesson + $index) % $lessons_number + 1;
        //  error_log('Recalculated Session Number for Report ID ' . $report->id . ': ' . $new_session_number);

        $wpdb->update(
            'wp_student_reports',
            array('session_number' => $new_session_number),
            array('id' => $report->id)
        );
    }

    //  error_log('Session numbers updated successfully.');
    wp_send_json_success('Session numbers updated successfully.');
}











add_action('wp_ajax_save_report', 'save_report');
add_action('wp_ajax_nopriv_save_report', 'save_report');

function save_report()
{
    global $wpdb;

    // Log the start of the function
    //  error_log('save_report function called.');

    // Log the received POST and FILES data
    //  error_log('POST data: ' . print_r($_POST, true));
    //  error_log('FILES data: ' . print_r($_FILES, true));

    // Check if student_id is received
    if (!isset($_POST['student_id'])) {
        //  error_log('Student ID is missing in the request.');
        wp_send_json_error('Student ID is missing.');
    }

    $student_id = intval($_POST['student_id']);
    //  error_log('Student ID: ' . $student_id);

    // Check if report_id is received (for updating existing reports)
    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : null;
    //  error_log('Report ID: ' . ($report_id ? $report_id : 'Not provided'));

    // Validate required fields
    $required_fields = ['attendance', 'session_number', 'date', 'time', 'evaluation'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            //  error_log('Required field is missing: ' . $field);
            wp_send_json_error('Required field is missing: ' . $field);
        }
        // Allow 0 as a valid value for session_number
        if ($field === 'session_number' && $_POST[$field] === '0') {
            continue; // Skip validation for session_number if it's 0
        }
        // if (empty($_POST[$field])) {
        //    //  error_log('Required field is empty: ' . $field);
        //     wp_send_json_error('Required field is empty: ' . $field);
        // }
    }

    // Define non-valid attendances that should always use session number 0
    $non_valid_attendances = ['تعويض التأجيل', 'تعويض الغياب', 'تجريبي'];
    $attendance = sanitize_text_field($_POST['attendance']);

    // Force session number to 0 for non-valid attendance types
    if (in_array($attendance, $non_valid_attendances)) {
        $_POST['session_number'] = 0;
        //  error_log('Forced session number to 0 for attendance: ' . $attendance);
    }



    // =======   this add tate and time manulauu ============
    // Parse the date and time directly as provided by the user
    $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');
    $time = sanitize_text_field($_POST['time']);

    // If the date is empty, set it to today's date
    if (empty($date)) {
        global $wpdb;
        $date = date('Y-m-d'); // Set to today's date

        // Get teacher ID from student meta
        $teacher_id = get_user_meta($student_id, 'teacher', true);

        // Get all reports for this teacher today
        $existing_times = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT time 
            FROM wp_student_reports 
            WHERE teacher_id = %d 
            AND date = %s 
            ORDER BY time ASC",
            $teacher_id,
            $date
        ));

        // Define available time slots
        $time_slots = ['10:00 PM', '10:30 PM', '11:00 PM', '11:30 PM'];

        // Find the first available time slot
        $time = '10:00 PM'; // Default time
        foreach ($time_slots as $slot) {
            if (!in_array($slot, $existing_times)) {
                $time = $slot;
                break;
            }
        }
    }


    // Combine date and time into a single string
    $datetime_string = $date . ' ' . $time;

    // Format the date and time for the database
    $formatted_date = date('Y-m-d', strtotime($date));
    $formatted_time = date('h:i A', strtotime($time)); // 12-hour format with AM/PM

    // error_log('Received date: ' . print_r($date, true));
    // error_log('Received time: ' . print_r($time, true));

    //  error_log('Formatted date: ' . $formatted_date);
    //  error_log('Formatted time: ' . $formatted_time);


    // // Check if the report is ad-hoc
    // $is_adhoc = isset($_POST['isAdHoc']) && $_POST['isAdHoc'] === '1';

    // if ($is_adhoc) {
    //     $formatted_date = date('Y-m-d'); // Get today's date in Y-m-d format

    // } else {
    //     $formatted_date = sanitize_text_field($_POST['date']); // Ensure to sanitize input

    // }
    //     $formatted_time = date('h:i A', strtotime($_POST['time'])); // Keep the existing time processing


    // Get the teacher ID associated with the student
    $teacher_id = get_user_meta($student_id, 'teacher', true);
    $lesson_duration = get_user_meta($student_id, 'lesson_duration', true);

    if (!$teacher_id) {
        //  error_log('No teacher associated with this student. Student ID: ' . $student_id);
        wp_send_json_error('No teacher associated with this student.');
    }

    // Check if a report already exists for the given report_id (for updating)
    $existing_report = null;
    if ($report_id) {
        $existing_report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wp_student_reports WHERE id = %d",
            $report_id
        ));
        //  error_log('Existing report found by report ID: ' . print_r($existing_report, true));
    }

    // If no existing report is found by report_id, check by student_id, date, and time
    if (!$existing_report) {
        $existing_report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wp_student_reports WHERE student_id = %d AND date = %s AND time = %s",
            $student_id,
            $formatted_date,
            $formatted_time
        ));
        //  error_log('Existing report found by student_id, date, and time: ' . print_r($existing_report, true));
    }

    // Handle multiple image uploads
    $zoom_image_urls = [];
    if (!empty($_FILES['zoom_image']['name'][0])) {
        //  error_log('Attempting to upload images.');

        // Loop through each file
        foreach ($_FILES['zoom_image']['name'] as $key => $value) {
            if ($_FILES['zoom_image']['error'][$key] === UPLOAD_ERR_OK) {
                $file = array(
                    'name'     => $_FILES['zoom_image']['name'][$key],
                    'type'     => $_FILES['zoom_image']['type'][$key],
                    'tmp_name' => $_FILES['zoom_image']['tmp_name'][$key],
                    'error'    => $_FILES['zoom_image']['error'][$key],
                    'size'     => $_FILES['zoom_image']['size'][$key]
                );

                $uploaded = wp_handle_upload($file, array('test_form' => false));
                if (isset($uploaded['url'])) {
                    $zoom_image_urls[] = $uploaded['url'];
                    //  error_log('Image uploaded successfully. URL: ' . $uploaded['url']);
                } else {
                    //  error_log('Image upload failed. Error: ' . print_r($uploaded, true));
                }
            } else {
                //  error_log('File upload error: ' . $_FILES['zoom_image']['error'][$key]);
            }
        }
    } else {
        //  error_log('No new images were uploaded.');
    }

    // If updating an existing report, preserve existing images if no new images are uploaded
    if ($existing_report) {
        $existing_images = json_decode($existing_report->zoom_image_url, true);
        if (empty($zoom_image_urls) && !empty($existing_images)) {
            $zoom_image_urls = $existing_images; // Preserve existing images
            //  error_log('Preserving existing images: ' . print_r($existing_images, true));
        }
    }

    // Log the final image URLs
    //  error_log('Final image URLs: ' . print_r($zoom_image_urls, true));

    // Store zoom_image_urls as a JSON-encoded array
    $zoom_image_urls_json = json_encode($zoom_image_urls);

    if ($existing_report) {
        //  error_log('Updating existing report. Report ID: ' . $existing_report->id);
        $result = $wpdb->update(
            'wp_student_reports',
            array(
                'attendance' => sanitize_text_field($_POST['attendance']),
                'session_number' => intval($_POST['session_number']), // Ensure session_number is an integer
                'evaluation' => sanitize_text_field($_POST['evaluation']),
                'tasmii' => sanitize_text_field($_POST['tasmii']),
                'tahfiz' => sanitize_text_field($_POST['tahfiz']),
                'mourajah' => sanitize_text_field($_POST['mourajah']),
                'next_tasmii' => sanitize_text_field($_POST['next_tasmii']),
                'next_mourajah' => sanitize_text_field($_POST['next_mourajah']),
                'notes' => sanitize_text_field($_POST['notes']),
                'zoom_image_url' => $zoom_image_urls_json, // Store as JSON
            ),
            array(
                'id' => $existing_report->id // Use the unique ID to update the report
            )
        );

        if ($result === false) {
            //  error_log('Failed to update the report. Database error: ' . $wpdb->last_error);
            wp_send_json_error('Failed to update the report.');
        } else {
            //  error_log('Report updated successfully.');
            wp_send_json_success('Report updated successfully!');
        }
    } else {
        // error_log('Inserting new report.');
        $result = $wpdb->insert(
            'wp_student_reports',
            array(
                'student_id' => $student_id,
                'teacher_id' => $teacher_id,
                'lesson_duration' => $lesson_duration, // Store the lesson_duration with the report
                'attendance' => sanitize_text_field($_POST['attendance']),
                'session_number' => intval($_POST['session_number']), // Ensure session_number is an integer
                'date' => $formatted_date, // Use the formatted date
                'time' => $formatted_time, // Use the formatted time (12-hour format with AM/PM)
                'evaluation' => sanitize_text_field($_POST['evaluation']),
                'tasmii' => sanitize_text_field($_POST['tasmii']),
                'tahfiz' => sanitize_text_field($_POST['tahfiz']),
                'mourajah' => sanitize_text_field($_POST['mourajah']),
                'next_tasmii' => sanitize_text_field($_POST['next_tasmii']),
                'next_mourajah' => sanitize_text_field($_POST['next_mourajah']),
                'notes' => sanitize_text_field($_POST['notes']),
                'zoom_image_url' => $zoom_image_urls_json, // Store as JSON
            )
        );

        if ($result === false) {
            //  error_log('Failed to insert the report. Database error: ' . $wpdb->last_error);
            wp_send_json_error('Failed to save the report.');
        } else {
            //  error_log('Report inserted successfully. New report ID: ' . $wpdb->insert_id);

            // Update previous_lesson_used if this is the first report
            $previous_lesson_used = get_user_meta($student_id, 'previous_lesson_used', true);
            if (!$previous_lesson_used) {
                update_user_meta($student_id, 'previous_lesson_used', true);
            }

            $session_number = intval($_POST['session_number']);
            $lessons_number = intval(get_user_meta($student_id, 'lessons_number', true));
            
            // Check if this is the last lesson
            if ($session_number == $lessons_number) {
                // Update payment status
                update_user_meta($student_id, 'payment_status', 'في انتظار الدفع');
                update_user_meta($student_id, 'payment_status_change_date', current_time('Y-m-d H:i:s'));
                update_user_meta($student_id, 'reminder', 'لا يوجد');

                // Initialize reminder history for the student
                date_default_timezone_set('Africa/Cairo');
                $current_time = date('Y-m-d H:i:s');
                $formatted_date = date('j/n/Y');
                $formatted_time = date('g:i a');

                $initial_reminder_history = array(
                    array(
                        'status' => 'لا يوجد',
                        'date' => $formatted_date,
                        'time' => $formatted_time,
                        'timestamp' => $current_time
                    )
                );

                update_user_meta($student_id, 'reminder_change_date', json_encode($initial_reminder_history));

                // Get student data for WhatsApp message
                $student_data = get_userdata($student_id);
                $student_name = $student_data->display_name;
                $payment_phone = get_user_meta($student_id, 'payment_phone', true);
                $m_id = get_user_meta($student_id, 'm_id', true);
                
                if ($payment_phone) {
                    // Get WhatsApp configuration
                    $whatsapp_config = zuwad_get_whatsapp_config();
                    
                    // Prepare WhatsApp message
                    $message = "سلام عليكم 👋\n";
                    $message .= "حرصًا على تطوير تجربتك معنا، نود أن نعرف رأيك في أداء المعلمة والحصص، من حيث:\n";
                    $message .= "	1.	أسلوب الشرح\n";
                    $message .= "	2.	التفاعل مع الطالب\n";
                    $message .= "	3.	الالتزام بالمواعيد\n";
                    $message .= "	4.	الرضا العام\n\n";
                    $message .= "يمكنك الرد باختيار تقييم من 1 إلى 5 لكل عنصر:\n";
                    $message .= "1 = ضعيف | 5 = ممتاز\n\n";
                    $message .= "شكراً لتعاونك وثقتك في أكاديمية زواد💙";
                    
                    // Use the unified WhatsApp sending function with force_resend set to true
                    // This ensures the message is always sent, even if it was sent before
                    $args = array(
                        'phone_number' => $payment_phone,
                        'message' => $message,
                        'm_id' => $m_id,
                        'debug' => true,
                        'force_resend' => true // Force resend even if already sent
                    );
                    
                    // Send WhatsApp message using the unified function
                    $result = zuwad_send_whatsapp_message($args);
                    
                    // Log the WhatsApp message attempt
                    error_log('WhatsApp notification sent to ' . $payment_phone . ' for student ' . $student_name . ' (ID: ' . $student_id . ') - Completed all ' . $lessons_number . ' lessons');
                    if (!$result['success']) {
                        error_log('WhatsApp API Error: ' . ($result['message'] ?? 'Unknown error'));
                    }
                    
                    // Add a delay before sending the report to ensure the feedback message is delivered first
                    sleep(2);
                    
                    // Mark the report as shared via WhatsApp
                    // This will prevent the need for manual sharing later
                    $wpdb->update(
                        'wp_student_reports',
                        array('whatsapp_shared' => 1),
                        array('id' => $new_report_id)
                    );
                    
                    // Schedule an AJAX request to send the report via JavaScript with progressive retries
                    // This uses the existing client-side report generation and sending functionality
                    $schedule_script = "<script>
                        jQuery(document).ready(function() {
                            // Create a function to attempt sending with increasing delays
                            function attemptSendReport(reportId, attempt) {
                                console.log('Attempt ' + attempt + ' to send report ID ' + reportId);
                                
                                // Check if the report exists first
                                jQuery.ajax({
                                    url: zuwadPlugin.ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'handle_get_report_data',
                                        report_id: reportId,
                                        _ajax_nonce: zuwadPlugin.nonce
                                    },
                                    success: function(response) {
                                        if (response.success && response.data) {
                                            console.log('Report data found, proceeding with sending');
                                            // Report exists, proceed with sending
                                            if (typeof sendReportViaWhatsApp === 'function') {
                                                sendReportViaWhatsApp(reportId);
                                                console.log('Automatically sending report ID ' + reportId + ' after feedback message');
                                            } else {
                                                console.error('sendReportViaWhatsApp function not found');
                                            }
                                        } else {
                                            console.log('Report data not found yet, will retry');
                                            // Report not found yet, retry with exponential backoff
                                            if (attempt < 5) { // Maximum 5 attempts
                                                var nextDelay = Math.pow(2, attempt) * 1000; // Exponential backoff
                                                console.log('Next attempt in ' + nextDelay + 'ms');
                                                setTimeout(function() {
                                                    attemptSendReport(reportId, attempt + 1);
                                                }, nextDelay);
                                            } else {
                                                console.error('Failed to find report data after maximum attempts');
                                            }
                                        }
                                    },
                                    error: function() {
                                        console.error('AJAX error when checking report existence');
                                        // Retry on error as well
                                        if (attempt < 5) {
                                            setTimeout(function() {
                                                attemptSendReport(reportId, attempt + 1);
                                            }, 2000);
                                        }
                                    }
                                });
                            }
                            
                            // Start the first attempt after initial delay
                            setTimeout(function() {
                                attemptSendReport({$new_report_id}, 1);
                            }, 5000); // Initial 5 second delay
                        });
                    </script>";
                    
                    // Add the script to the footer to execute after page load
                    add_action('wp_footer', function() use ($schedule_script) {
                        echo $schedule_script;
                    });
                    
                    error_log('Scheduled automatic report sending for report ID ' . $new_report_id . ' after feedback message');
                }
            }
            
            // Get the newly created report ID
            $new_report_id = $wpdb->insert_id;
            
            // Return the report ID in the response
            wp_send_json_success(array(
                'message' => 'Report saved successfully!',
                'report_id' => $new_report_id
            ));
        }
    }
}







// Add a new column to the wp_student_reports table
function zuwad_add_image_column_to_reports()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'student_reports';
    $column_name = 'zoom_image_url';

    // Check if the column already exists
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE '$column_name'");

    if (!$column_exists) {
        $wpdb->query("ALTER TABLE $table_name ADD $column_name VARCHAR(255) DEFAULT NULL");
    }
}
add_action('init', 'zuwad_add_image_column_to_reports');











add_action('wp_ajax_save_free_slot', 'save_free_slot');
add_action('wp_ajax_nopriv_save_free_slot', 'save_free_slot');

function save_free_slot()
{
    global $wpdb;

    // Get the current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('User not logged in.');
    }

    // Get the teacher ID from the request
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    if (!$teacher_id) {
        wp_send_json_error('Invalid teacher ID.');
    }

    // Get the start and end times from the request
    $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : null;
    $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : null;

    if (!$start || !$end) {
        wp_send_json_error('Invalid start or end time.');
    }

    // Convert UTC times to Cairo timezone
    $cairo_tz = new DateTimeZone('Africa/Cairo');
    $utc_tz = new DateTimeZone('UTC');

    $start_datetime = new DateTime($start, $utc_tz);
    $start_datetime->setTimezone($cairo_tz);

    $end_datetime = new DateTime($end, $utc_tz);
    $end_datetime->setTimezone($cairo_tz);

    // Calculate the duration of the slot in minutes
    $interval = $start_datetime->diff($end_datetime);
    $duration_minutes = ($interval->h * 60) + $interval->i;

    // Silently reject the slot if the duration is 15 minutes or less
    if ($duration_minutes <= 15) {
        // Do nothing, just exit
        wp_die();
    }

    // Extract the day of the week (0 = Sunday, 6 = Saturday)
    $day_of_week = intval($start_datetime->format('w'));

    // Extract the time (HH:MM:SS)
    $start_time = $start_datetime->format('H:i:s');
    $end_time = $end_datetime->format('H:i:s');

    // Check for overlapping slots
    $table_name = $wpdb->prefix . 'free_slots';
    $overlapping_slots = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE user_id = %d 
         AND day_of_week = %d 
         AND (
             (start_time <= %s AND end_time >= %s) OR
             (start_time <= %s AND end_time >= %s) OR
             (start_time >= %s AND end_time <= %s)
         )",
        $teacher_id,
        $day_of_week,
        $start_time,
        $start_time,
        $end_time,
        $end_time,
        $start_time,
        $end_time
    ));

    if (!empty($overlapping_slots)) {
        // Find the earliest start time and latest end time
        $earliest_start = $start_time;
        $latest_end = $end_time;

        foreach ($overlapping_slots as $slot) {
            if ($slot->start_time < $earliest_start) {
                $earliest_start = $slot->start_time;
            }
            if ($slot->end_time > $latest_end) {
                $latest_end = $slot->end_time;
            }
            // Delete the overlapping slot
            $wpdb->delete(
                $table_name,
                [
                    'user_id' => $teacher_id,
                    'day_of_week' => $day_of_week,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time
                ],
                ['%d', '%d', '%s', '%s']
            );
        }

        // Insert the merged slot
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $teacher_id,
                'day_of_week' => $day_of_week,
                'start_time' => $earliest_start,
                'end_time' => $latest_end
            ],
            ['%d', '%d', '%s', '%s']
        );
    } else {
        // No overlapping slots, just insert the new one
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $teacher_id,
                'day_of_week' => $day_of_week,
                'start_time' => $start_time,
                'end_time' => $end_time
            ],
            ['%d', '%d', '%s', '%s']
        );
    }

    if ($result) {
        wp_send_json_success('Free slot saved successfully.');
    } else {
        wp_send_json_error('Failed to save free slot.');
    }
}


function get_free_slots()
{
    global $wpdb;

    // Get the teacher ID from the request
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    if (!$teacher_id) {
        wp_send_json_error('Invalid teacher ID.');
    }

    // Get the start and end dates from the request
    $start_date = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : null;
    $end_date = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : null;

    if (!$start_date || !$end_date) {
        wp_send_json_error('Invalid date range.');
    }

    // Convert UTC times to Cairo timezone
    $cairo_tz = new DateTimeZone('Africa/Cairo');
    $utc_tz = new DateTimeZone('UTC');

    $start_date = new DateTime($start_date, $utc_tz);
    $start_date->setTimezone($cairo_tz);

    $end_date = new DateTime($end_date, $utc_tz);
    $end_date->setTimezone($cairo_tz);

    // Fetch free slots for the selected teacher
    $table_name = $wpdb->prefix . 'free_slots';
    $free_slots = $wpdb->get_results($wpdb->prepare(
        "SELECT day_of_week, start_time, end_time 
         FROM $table_name 
         WHERE user_id = %d",
        $teacher_id
    ));

    if (!$free_slots) {
        wp_send_json_success([]); // Return an empty array if no free slots exist
    }

    // Generate recurring events for each free slot
    $events = [];
    foreach ($free_slots as $slot) {
        // Start from the beginning of the start week
        $current_date = clone $start_date;
        $current_date->modify('last sunday'); // Go to the start of the week

        // Loop through weeks until we pass the end date
        while ($current_date <= $end_date) {
            // Calculate the event date for this week
            $event_date = clone $current_date;
            $days_to_add = ($slot->day_of_week - $event_date->format('w') + 7) % 7;
            $event_date->modify("+{$days_to_add} days");

            // If this week's occurrence is within our range
            if ($event_date >= $start_date && $event_date <= $end_date) {
                // Create the full event datetime
                $start_datetime = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $event_date->format('Y-m-d') . ' ' . $slot->start_time,
                    $cairo_tz
                );

                $end_datetime = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $event_date->format('Y-m-d') . ' ' . $slot->end_time,
                    $cairo_tz
                );

                // Convert back to UTC for FullCalendar
                $start_datetime->setTimezone($utc_tz);
                $end_datetime->setTimezone($utc_tz);

                $events[] = [
                    'title' => 'وقت متاح',
                    'start' => $start_datetime->format('Y-m-d\TH:i:s\Z'),
                    'end' => $end_datetime->format('Y-m-d\TH:i:s\Z'),
                    'color' => 'green',
                    'extendedProps' => [
                        'isFreeSlot' => true,
                        'teacher_id' => $teacher_id
                    ]
                ];
            }

            // Move to next week
            $current_date->modify('+1 week');
        }
    }

    wp_send_json_success($events);
}



add_action('wp_ajax_get_free_slots', 'get_free_slots');
add_action('wp_ajax_nopriv_get_free_slots', 'get_free_slots');













add_action('wp_ajax_delete_free_slot', 'delete_free_slot');
add_action('wp_ajax_nopriv_delete_free_slot', 'delete_free_slot');

function delete_free_slot()
{
    global $wpdb;

    // Get the teacher ID from the request
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    if (!$teacher_id) {
        wp_send_json_error('Invalid teacher ID.');
    }

    // Get the start and end times from the request
    $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : null;
    $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : null;

    if (!$start || !$end) {
        wp_send_json_error('Invalid start or end time.');
    }

    // Convert UTC times to Cairo timezone
    $cairo_tz = new DateTimeZone('Africa/Cairo');
    $utc_tz = new DateTimeZone('UTC');

    $start_datetime = new DateTime($start, $utc_tz);
    $start_datetime->setTimezone($cairo_tz);

    $end_datetime = new DateTime($end, $utc_tz);
    $end_datetime->setTimezone($cairo_tz);

    // Get the day of the week (0 = Sunday, 6 = Saturday)
    $day_of_week = intval($start_datetime->format('w'));

    // Format for MySQL
    $start_time = $start_datetime->format('H:i:s');
    $end_time = $end_datetime->format('H:i:s');

    // Delete the free slot from the database
    $table_name = $wpdb->prefix . 'free_slots';
    $result = $wpdb->delete(
        $table_name,
        [
            'user_id' => $teacher_id,
            'day_of_week' => $day_of_week,
            'start_time' => $start_time,
            'end_time' => $end_time
        ],
        [
            '%d',
            '%d',
            '%s',
            '%s'
        ]
    );

    if ($result !== false) {
        wp_send_json_success('Free slot deleted successfully.');
    } else {
        wp_send_json_error('Failed to delete free slot.');
    }
}


// Remove temporary image file
add_action('wp_ajax_remove_temp_image', 'zuwad_remove_temp_image');
add_action('wp_ajax_nopriv_remove_temp_image', 'zuwad_remove_temp_image');

function zuwad_remove_temp_image()
{
    // Verify nonce for security
    check_ajax_referer('zuwad_plugin_nonce', '_ajax_nonce');

    // Ensure image URL is provided
    if (!isset($_POST['image_url'])) {
        wp_send_json_error('No image URL provided');
        return;
    }

    $image_url = sanitize_url($_POST['image_url']);
    $remove_from_media = isset($_POST['remove_from_media']) && $_POST['remove_from_media'] === 'true';

    // Convert URL to server path
    $upload_dir = wp_upload_dir();
    $base_url = $upload_dir['baseurl'];
    $base_path = $upload_dir['basedir'];

    // Remove base URL to get relative path
    $relative_path = str_replace($base_url, '', $image_url);
    $full_path = $base_path . $relative_path;

    // Log the paths for debugging
    //  error_log('Attempting to remove temporary image:');
    //  error_log('Base URL: ' . $base_url);
    //  error_log('Base Path: ' . $base_path);
    //  error_log('Relative Path: ' . $relative_path);
    //  error_log('Full Path: ' . $full_path);
    //  error_log('Remove from Media Library: ' . ($remove_from_media ? 'Yes' : 'No'));

    // Check if file exists before attempting to delete
    if (file_exists($full_path)) {
        // Attempt to delete the file from filesystem
        $deleted_file = unlink($full_path);

        // If remove from media library is requested
        if ($remove_from_media) {
            // Get attachment ID by URL
            $attachment_id = zuwad_get_attachment_id_by_url($image_url);

            if ($attachment_id) {
                // Delete attachment from WordPress media library
                $deleted_media = wp_delete_attachment($attachment_id, true);

                //  error_log('Media Library Deletion Result: ' . ($deleted_media ? 'Success' : 'Failed'));
            }
        }

        if ($deleted_file) {
            //  error_log('Temporary image successfully deleted: ' . $full_path);
            wp_send_json_success('Image deleted');
        } else {
            //  error_log('Failed to delete temporary image: ' . $full_path);
            wp_send_json_error('Failed to delete image');
        }
    } else {
        //  error_log('Temporary image file not found: ' . $full_path);
        wp_send_json_error('Image file not found');
    }
}

// Helper function to get attachment ID by URL
function zuwad_get_attachment_id_by_url($url)
{
    // Remove any query parameters from the URL
    $url = strtok($url, '?');

    // Get the upload directory
    $upload_dir = wp_upload_dir();

    // If the URL is not within the uploads directory, return false
    if (strpos($url, $upload_dir['baseurl']) === false) {
        return false;
    }

    // Remove the base upload URL to get the relative path
    $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);

    // Query for the attachment
    $attachment = get_posts(array(
        'post_type' => 'attachment',
        'meta_query' => array(
            array(
                'key' => '_wp_attached_file',
                'value' => _wp_relative_upload_path($file_path),
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    // Return the attachment ID if found
    return $attachment ? $attachment[0]->ID : false;
}
