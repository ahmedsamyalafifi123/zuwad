<?php

// Shortcode for the button and modal
function student_schedules_button_shortcode()
{

    if (!current_user_can('supervisor') && !current_user_can('administrator')) {
        return;
    }

    ob_start(); // Start output buffering
?>
    <!-- Button to trigger modal -->
    <button id="open-schedule-modal" class="zuwad-button">🕐 مواعيد الطلاب</button>

    <!-- Modal -->
    <div id="schedule-modal" class="zuwad-modal" style="display: none;">
        <div class="zuwad-modal-content">
            <span id="close-modal" class="zuwad-close-modal">&times;</span>
            <?php echo get_schedule_content(); // Reuse the same content 
            ?>
        </div>
    </div>
<?php
    return ob_get_clean(); // Return the output
}
add_shortcode('student_schedules_button', 'student_schedules_button_shortcode');






// Shortcode for the inline content
function student_schedules_inline_shortcode()
{
    if (!current_user_can('supervisor') && !current_user_can('administrator') && !current_user_can('sales')) {
        return;
    }
    ob_start(); // Start output buffering
?>
    <!-- Directly display the content in the body -->
    <div id="direct-schedule-content" class="zuwad-direct-content">
        <?php echo get_schedule_content(); // Reuse the same content 
        ?>
    </div>
<?php
    return ob_get_clean(); // Return the output
}
add_shortcode('student_schedules_inline', 'student_schedules_inline_shortcode');

// Helper function to generate the schedule content
function get_schedule_content()
{
    ob_start();
?>
    <h2>مواعيد الطلاب</h2>
    <!-- Search bar for students -->
    <input type="text" id="student-search" placeholder="ابحث باستخدام الاسم او الاي دي" class="zuwad-search-bar">
    <div id="search-results" class="zuwad-search-results"></div>
    <!-- Hidden input to store selected student ID -->
    <input type="hidden" id="selected-student-id">
    <!-- Class schedule form -->
    <form id="schedule-form" class="zuwad-form">
        <div id="class-times">
            <div class="class-time">
                <select class="zuwad-day-picker" required>
                    <option value="السبت">السبت</option>
                    <option value="الأحد">الأحد</option>
                    <option value="الاثنين">الاثنين</option>
                    <option value="الثلاثاء">الثلاثاء</option>
                    <option value="الأربعاء">الأربعاء</option>
                    <option value="الخميس">الخميس</option>
                    <option value="الجمعة">الجمعة</option>
                </select>
                <input type="text" class="zuwad-time-picker" placeholder="الموعد" readonly>
                <button type="button" class="zuwad-remove-class">&times;</button>
            </div>
        </div>
        <button type="button" id="add-class-time" class="zuwad-add-button">+ اضافة المزيد</button>
        <button type="submit" id="submit-schedule" class="zuwad-submit-button">حفظ المواعيد</button>
    </form>
<?php
    return ob_get_clean();
}






// Search students
// Search students
add_action('wp_ajax_search_students', 'search_students');
add_action('wp_ajax_nopriv_search_students', 'search_students');

function search_students()
{
    global $wpdb;
    $search = sanitize_text_field($_POST['search']);
    $current_user_id = get_current_user_id();
    $current_user = wp_get_current_user();
    $results = [];

    if (in_array('teacher', $current_user->roles)) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT u.ID, u.display_name, um3.meta_value as m_id, um4.meta_value as phone 
             FROM {$wpdb->users} u 
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
             LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'm_id'
             LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'phone'
             WHERE (u.display_name LIKE %s OR u.ID = %d OR um3.meta_value LIKE %s OR um4.meta_value LIKE %s) 
             AND um.meta_key = 'teacher' 
             AND um.meta_value = %d
             LIMIT 7",
            '%' . $wpdb->esc_like($search) . '%',
            intval($search),
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            $current_user_id
        ));
    } elseif (in_array('supervisor', $current_user->roles)) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT u.ID, u.display_name, um3.meta_value as m_id, um4.meta_value as phone 
             FROM {$wpdb->users} u 
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
             INNER JOIN {$wpdb->usermeta} um2 ON um.meta_value = um2.user_id 
             LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'm_id'
             LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'phone'
             WHERE (u.display_name LIKE %s OR u.ID = %d OR um3.meta_value LIKE %s OR um4.meta_value LIKE %s) 
             AND um.meta_key = 'teacher' 
             AND um2.meta_key = 'supervisor' 
             AND um2.meta_value = %d
             LIMIT 7",
            '%' . $wpdb->esc_like($search) . '%',
            intval($search),
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%',
            $current_user_id
        ));
    } elseif (in_array('administrator', $current_user->roles) || in_array('sales', $current_user->roles)) {
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT u.ID, u.display_name, um3.meta_value as m_id, um4.meta_value as phone 
             FROM {$wpdb->users} u 
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
             INNER JOIN {$wpdb->usermeta} um2 ON um.meta_value = um2.user_id 
             LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'm_id'
             LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'phone'
             WHERE (u.display_name LIKE %s OR u.ID = %d OR um3.meta_value LIKE %s OR um4.meta_value LIKE %s) 
             AND um.meta_key = 'teacher' 
             AND um2.meta_key = 'supervisor'
             LIMIT 7",
            '%' . $wpdb->esc_like($search) . '%',
            intval($search),
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        ));
    }

    if ($results) {
        foreach ($results as $result) {
            $phone_display = !empty($result->phone) ? " (هاتف: {$result->phone})" : '';
            echo '<div class="student-result" data-id="' . $result->ID . '">' .
                $result->display_name . ' (m_id: ' . $result->m_id . ')</div>';
        }
    } else {
        echo '<div>لم يتم العثور على طلاب.</div>';
    }
    wp_die();
}







// Fetch existing class times for a student
add_action('wp_ajax_fetch_student_schedule', 'fetch_student_schedule');
add_action('wp_ajax_nopriv_fetch_student_schedule', 'fetch_student_schedule');

function fetch_student_schedule()
{
    global $wpdb;
    $student_id = intval($_POST['student_id']);

    // Get student's lessons_number
    $lessons_number = intval(get_user_meta($student_id, 'lessons_number', true));

    // Calculate max allowed class times (lessons_number divided by 4)
    $max_class_times = ceil($lessons_number / 4);

    // If lessons_number is not set or is 0, default to 1
    if ($max_class_times < 1) {
        $max_class_times = 1;
    }

    $schedule = $wpdb->get_var($wpdb->prepare(
        "SELECT schedule FROM wp_student_schedules WHERE student_id = %d",
        $student_id
    ));

    if ($schedule) {
        $decoded_schedule = json_decode($schedule, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            wp_send_json_success([
                'schedule' => $decoded_schedule,
                'max_class_times' => $max_class_times
            ]);
        } else {
            wp_send_json_error('Invalid JSON data in schedule.');
        }
    } else {
        wp_send_json_success([
            'schedule' => [],
            'max_class_times' => $max_class_times
        ]);
    }
}




















// Save schedule
add_action('wp_ajax_save_schedule', 'save_schedule');
add_action('wp_ajax_nopriv_save_schedule', 'save_schedule');

function save_schedule()
{
    global $wpdb;
    $student_id = intval($_POST['student_id']);
    $schedule = json_decode(stripslashes($_POST['schedule']), true); // Decode JSON data

    if ($student_id && is_array($schedule)) {
        // Get the student's teacher ID
        $teacher_id = get_user_meta($student_id, 'teacher', true);

        if (!$teacher_id) {
            wp_send_json_error('Teacher not found for this student.');
        }

        // Get the student's lesson duration
        $lesson_duration = intval(get_user_meta($student_id, 'lesson_duration', true));
        if (!$lesson_duration) {
            wp_send_json_error('Lesson duration not found for this student.');
        }

        $schedule_json = json_encode($schedule, JSON_UNESCAPED_UNICODE); // Encode as JSON

        // Check if the student already has a schedule
        $existing_schedule = $wpdb->get_var($wpdb->prepare(
            "SELECT schedule FROM wp_student_schedules WHERE student_id = %d",
            $student_id
        ));

        if ($existing_schedule) {
            // Update existing schedule
            $wpdb->update(
                'wp_student_schedules',
                array(
                    'schedule' => $schedule_json,
                    'teacher_id' => $teacher_id,
                    'lesson_duration' => $lesson_duration
                ),
                array('student_id' => $student_id)
            );
        } else {
            // Insert new schedule
            $wpdb->insert(
                'wp_student_schedules',
                array(
                    'student_id' => $student_id,
                    'teacher_id' => $teacher_id,
                    'lesson_duration' => $lesson_duration,
                    'schedule' => $schedule_json
                )
            );
        }

        wp_send_json_success('Schedule saved successfully!');
    } else {
        wp_send_json_error('Invalid data.');
    }
}

// Update student schedule handler - forwards to save_schedule 
add_action('wp_ajax_update_student_schedule', 'update_student_schedule');
add_action('wp_ajax_nopriv_update_student_schedule', 'update_student_schedule');

function update_student_schedule() {
    // Convert class_times to schedule format
    if (isset($_POST['class_times'])) {
        $_POST['schedule'] = $_POST['class_times'];
    }
    
    // Call the existing save_schedule function
    save_schedule();
}

















// Check for schedule conflicts
add_action('wp_ajax_check_schedule_conflicts', 'check_schedule_conflicts');
add_action('wp_ajax_nopriv_check_schedule_conflicts', 'check_schedule_conflicts');


function check_schedule_conflicts()
{
    global $wpdb;
    $student_id = intval($_POST['student_id']);
    $new_schedule = json_decode(stripslashes($_POST['schedule']), true); // Decode JSON data

    if (!$student_id || !is_array($new_schedule)) {
        wp_send_json_error('Invalid data.');
    }

    // Debug: Log received data
    // error_log('check_schedule_conflicts - student_id: ' . $student_id);
    // error_log('check_schedule_conflicts - new_schedule: ' . print_r($new_schedule, true));

    // Get the student's teacher ID
    $teacher_id = get_user_meta($student_id, 'teacher', true);

    if (!$teacher_id) {
        wp_send_json_error('Teacher not found for this student.');
    }

    // Get the student's lesson duration
    $lesson_duration = intval(get_user_meta($student_id, 'lesson_duration', true));
    if (!$lesson_duration) {
        $lesson_duration = 30; // Default to 30 minutes if not set
        // error_log('check_schedule_conflicts - using default lesson duration: 30 minutes');
    }

    // Get the student's existing schedule (if any)
    $existing_schedule = $wpdb->get_var($wpdb->prepare(
        "SELECT schedule FROM wp_student_schedules WHERE student_id = %d",
        $student_id
    ));
    $existing_schedule = $existing_schedule ? json_decode($existing_schedule, true) : [];

    // Check each class in the new schedule, regardless of whether it's modified or not
    $conflicts = [];

    // First, check for conflicts within the same student's schedule
    for ($i = 0; $i < count($new_schedule); $i++) {
        $class1 = $new_schedule[$i];
        
        // Compare with all other classes in the new schedule
        for ($j = $i + 1; $j < count($new_schedule); $j++) {
            $class2 = $new_schedule[$j];
            
            if ($class1['day'] === $class2['day']) {
                // Calculate start and end times
                $start1 = strtotime($class1['hour']);
                $end1 = $start1 + ($lesson_duration * 60);
                
                $start2 = strtotime($class2['hour']);
                $end2 = $start2 + ($lesson_duration * 60);
                
                // Check for overlap
                if (($start1 < $end2) && ($end1 > $start2)) {
                    $conflicts[] = "يوجد تعارض بين حصتين في نفس اليوم {$class1['day']} ساعة {$class1['hour']} مع ساعة {$class2['hour']}";
                }
            }
        }
    }

    // Get all schedules for the teacher's students (excluding the current student)
    $other_students_schedules = $wpdb->get_results($wpdb->prepare(
        "SELECT student_id, schedule, lesson_duration FROM wp_student_schedules WHERE teacher_id = %d AND student_id != %d",
        $teacher_id,
        $student_id
    ));

    // error_log('check_schedule_conflicts - Found ' . count($other_students_schedules) . ' other students with schedules');

    // Check for conflicts with other students' schedules
    foreach ($other_students_schedules as $other_schedule) {
        $other_classes = json_decode($other_schedule->schedule, true);
        
        if (!is_array($other_classes)) {
            // error_log('check_schedule_conflicts - Invalid schedule for student ID: ' . $other_schedule->student_id);
            continue;
        }
        
        $other_lesson_duration = intval($other_schedule->lesson_duration);
        if (!$other_lesson_duration) {
            $other_lesson_duration = 30; // Default to 30 minutes
        }
        
        // Get student name for conflict message
        $conflicting_student = get_userdata($other_schedule->student_id);
        $conflicting_student_name = $conflicting_student ? $conflicting_student->display_name : "طالب آخر";
        
        // Get teacher name for conflict message
        $teacher = get_userdata($teacher_id);
        $teacher_name = $teacher ? $teacher->display_name : "المعلم";

        foreach ($new_schedule as $new_class) {
            foreach ($other_classes as $other_class) {
                if ($new_class['day'] === $other_class['day']) {
                    // Calculate start and end times for the new class
                    $start_new = strtotime($new_class['hour']);
                    $end_new = $start_new + ($lesson_duration * 60);

                    // Calculate start and end times for the other student's class
                    $start_other = strtotime($other_class['hour']);
                    $end_other = $start_other + ($other_lesson_duration * 60);

                    // Check for overlap (end times are exclusive)
                    if (($start_new < $end_other) && ($end_new > $start_other)) {
                        // Format the conflict message in Arabic
                        $conflicts[] = "يوجد تعارض مع الطالب {$conflicting_student_name} للمعلم {$teacher_name} يوم {$new_class['day']} ساعة {$new_class['hour']} مع ساعة {$other_class['hour']}";
                    }
                }
            }
        }
    }

    // error_log('check_schedule_conflicts - Found ' . count($conflicts) . ' conflicts');

    if (!empty($conflicts)) {
        wp_send_json_success(['conflicts' => $conflicts]);
    } else {
        wp_send_json_success(['conflicts' => []]);
    }
}
