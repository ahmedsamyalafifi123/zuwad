<?php


function supervisor_teachers_shortcode()
{
    // Check if the current user is a supervisor or administrator
    if (!current_user_can('supervisor') && !current_user_can('administrator')) {
        return;
    }

    // If the user is an administrator, fetch all teachers
    if (current_user_can('administrator')) {
        $teachers = get_users(array(
            'role' => 'teacher',
        ));
    } else {
        // If the user is a supervisor, fetch only the teachers assigned to them
        $supervisor_id = get_current_user_id();
        $teachers = get_users(array(
            'role' => 'teacher',
            'meta_key' => 'supervisor',
            'meta_value' => $supervisor_id,
        ));
    }

    // If no teachers are found
    if (empty($teachers)) {
        return '<p>لا يوجد معلمون معينون.</p>';
    }

    // Start output buffering
    ob_start();
?>
    <h2 class="teacher-heading">🧑‍🏫 المعلمين</h2>

    <div class="supervisor-teachers-container">
        <?php
        // Sort teachers by m_id
        usort($teachers, function ($a, $b) {
            // $a_m_id = intval(get_user_meta($a->ID, 'm_id', true));
            // $b_m_id = intval(get_user_meta($b->ID, 'm_id', true));
            // return $a_m_id - $b_m_id;
            return strcasecmp($a->display_name, $b->display_name);
        });

        // Display sorted teachers
        foreach ($teachers as $teacher) :
            $teacher_m_id = get_user_meta($teacher->ID, 'm_id', true);
            $students = get_users(array(
                'role' => 'student',
                'meta_key' => 'teacher',
                'meta_value' => $teacher->ID,
            ));

            // Calculate total minutes and total hours for all students
            $total_minutes = 0;
            foreach ($students as $student) {
                $lessons_number = intval(get_user_meta($student->ID, 'lessons_number', true));
                $lesson_duration = intval(get_user_meta($student->ID, 'lesson_duration', true));
                $total_minutes += $lessons_number * $lesson_duration;
            }

            // Calculate عدد الطلاب (total hours / 4)
            $total_hours = $total_minutes / 60; // Convert total minutes to hours
            $number_of_students_calculated = round($total_hours / 4, 2); // Divide by 4 and round to 2 decimal places

            // Get the real student count (optional, if needed elsewhere)
            $real_student_count = count($students);

            // Get teacher classification and determine the highest number
            $teacher_classification = get_user_meta($teacher->ID, 'teacher_classification', true);
            $classification_number = '-';

            // Handle both array and comma-separated string formats
            if (!empty($teacher_classification)) {
                $classification_array = $teacher_classification;

                // If it's a string, convert to array
                if (!is_array($classification_array)) {
                    $classification_array = explode(',', $classification_array);
                }

                $classification_map = [
                    'فئة اولى (غير الناطقين)' => 1,
                    'فئة ثانية (خليج)' => 2,
                    'فئة ثالثة (مصر أطفال)' => 3,
                    'فئة رابعة (مصر كبار)' => 4,
                    'فئة خامسة (موبايل)' => 5
                ];

                $lowest_number = 999; // Start with a high number
                foreach ($classification_array as $classification) {
                    $classification = trim($classification); // Remove any whitespace
                    if (isset($classification_map[$classification]) && $classification_map[$classification] < $lowest_number) {
                        $lowest_number = $classification_map[$classification];
                    }
                }

                if ($lowest_number < 999) {
                    $classification_number = $lowest_number;
                }
            }

            // Get teacher status and determine the circle color
            $teacher_status = get_user_meta($teacher->ID, 'teacher_status', true);
            $status_color = 'blue'; // Default color

            // Handle both array and comma-separated string formats for status
            $status_array = $teacher_status;
            if (!is_array($status_array) && !empty($status_array)) {
                $status_array = explode(',', $status_array);
            } else if (!is_array($status_array)) {
                $status_array = array(); // Ensure it's an array even if empty
            }

            if (!empty($status_array)) {
                // Check for active status (green)
                if (in_array('نشط عدد كامل', $status_array)) {
                    $status_color = 'green';
                }
                if (in_array('نشط نصف عدد', $status_array)) {
                    $status_color = 'whitegreen';
                }
                // Check for yellow status
                elseif (in_array('وقف الطلاب الجدد', $status_array) || in_array('اجازة اسبوعين', $status_array)) {
                    $status_color = 'darkyellow';
                }
                // Check for red status
                elseif (in_array('اعتزار مؤقت', $status_array)) {
                    $status_color = 'darkred';
                }
                // Check for black status
                elseif (in_array('في انتظار النقل', $status_array) || in_array('متوقف عن العمل', $status_array)) {
                    $status_color = 'black';
                }
            }
        ?>
            <div class="teacher-card" data-teacher-id="<?php echo esc_attr($teacher->ID); ?>">
                <div class="teacher-card-header">
                    <div class="status-circles">
                        <span class="status-number"><?php echo esc_html($classification_number); ?></span>
                        <span class="status-circle <?php echo esc_attr($status_color); ?>"></span>
                    </div>
                    <h3><?php echo esc_html($teacher->display_name); ?></h3>
                    <div class="edit-section">
                        <span class="edit-teacher">✏️</span>
                    </div>
                </div>
                <p>م: <?php echo esc_html($teacher_m_id); ?></p>
                <p>عدد الطلاب: <?php echo esc_html($number_of_students_calculated); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Student Modal -->
    <div id="student-modal" style="display: none;">
        <!-- Modal content will be dynamically inserted here -->
    </div>

    <!-- Modal Structure -->
    <div id="teacher-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h3 id="modal-teacher-name"></h3>
            </div>
            <div class="widgets-container">
                <!-- First Row: Main Widgets -->
                <div class="widget-row">
                    <div class="widget" id="widget-students-container">
                        <h4>🧑‍🏫 عدد الطلاب الحالي</h4>
                        <p id="widget-students">جاري التحميل...</p>
                    </div>
                    <div class="widget" id="widget-all-time-lessons-container">
                        <h4>📅 الحصص الشهرية</h4>
                        <p id="widget-all-time-lessons">جاري التحميل...</p>
                    </div>
                    <div class="widget" id="widget-all-time-hours-container">
                        <h4>⏳ الساعات الشهرية</h4>
                        <p id="widget-all-time-hours">جاري التحميل...</p>
                    </div>
                    <div class="widget" id="widget-current-month-lessons-container">
                        <h4>✅ الحصص المكتملة</h4>
                        <p id="widget-current-month-lessons">جاري التحميل...</p>
                    </div>
                    <div class="widget" id="widget-current-month-hours-container">
                        <h4>⏱️ الساعات المكتملة</h4>
                        <p id="widget-current-month-hours">جاري التحميل...</p>
                    </div>
                </div>

                <!-- Second Row: Month Selector and Last 4 Widgets -->
                <div class="widget-row">
                    <!-- Month and Year Selector -->
                    <div class="month-year-selector">
                        <select id="month-select">
                            <?php
                            $months = [
                                1 => 'يناير',
                                2 => 'فبراير',
                                3 => 'مارس',
                                4 => 'أبريل',
                                5 => 'مايو',
                                6 => 'يونيو',
                                7 => 'يوليو',
                                8 => 'أغسطس',
                                9 => 'سبتمبر',
                                10 => 'أكتوبر',
                                11 => 'نوفمبر',
                                12 => 'ديسمبر',
                            ];
                            foreach ($months as $key => $month) : ?>
                                <option value="<?php echo $key; ?>" <?php echo ($key == date('n')) ? 'selected' : ''; ?>>
                                    <?php echo $month; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select id="year-select">
                            <?php
                            $current_year = date('Y');
                            for ($i = $current_year; $i >= $current_year - 3; $i--) : ?>
                                <option value="<?php echo $i; ?>" <?php echo ($i == $current_year) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Last 4 Widgets -->
                    <div class="widget" id="widget-student-delays-container">
                        <h4>🧑‍🎓 تأجيلات الطلاب</h4>
                        <p id="widget-student-delays">جاري التحميل...</p>
                    </div>
                    <div class="widget" id="widget-teacher-delays-container">
                        <h4>🧑‍🏫 تأجيلات المعلم</h4>
                        <p id="widget-teacher-delays">جاري التحميل...</p>
                    </div>
                    <div class="widget" id="widget-compensation-delays-container">
                        <h4>🕐 تعويض التأجيل</h4>
                        <p id="widget-compensation-delays">جاري التحميل...</p>
                    </div>
                    <div class="widget" id="widget-compensation-absences-container">
                        <h4>❌ تعويض الغياب</h4>
                        <p id="widget-compensation-absences">جاري التحميل...</p>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <table id="students-table">
                    <thead>
                        <tr>
                            <th>م</th>
                            <th>الاسم</th>
                            <th>الهاتف</th>
                            <th>المادة</th>
                            <th>العمر</th>
                            <th>البلد</th>
                            <th>الحصص</th>
                            <th>المدة</th>
                            <th>العملة</th>
                            <th>المبلغ</th>
                            <th>حالة الدفع</th>
                            <th>ملاحظات</th>
                            <th>تعديل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Students data will be populated here via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
    // Return the output
    return ob_get_clean();
}
add_shortcode('supervisor_teachers', 'supervisor_teachers_shortcode');








function get_teacher_data()
{
    $teacher_id = intval($_POST['teacher_id']);
    if (!$teacher_id) {
        wp_send_json_error('Invalid teacher ID');
    }

    $teacher = get_userdata($teacher_id);
    if (!$teacher) {
        wp_send_json_error('Teacher not found');
    }

    $teacher_data = array(
        'ID' => $teacher->ID,
        'display_name' => $teacher->display_name,
        'email' => $teacher->user_email,
        'phone' => get_user_meta($teacher_id, 'phone', true),
        'dob' => get_user_meta($teacher_id, 'dob', true),
        'age' => get_user_meta($teacher_id, 'age', true),
        'country' => get_user_meta($teacher_id, 'country', true),
        'lessons_name' => get_user_meta($teacher_id, 'lessons_name', true),
        'supervisor_id' => get_user_meta($teacher_id, 'supervisor', true),
        'teacher_classification' => get_user_meta($teacher_id, 'teacher_classification', true),
        'teacher_status' => get_user_meta($teacher_id, 'teacher_status', true),

    );

    wp_send_json_success(array('teacher' => $teacher_data));
}
add_action('wp_ajax_get_teacher_data', 'get_teacher_data');








// Add this function to handle teacher updates
function update_teacher_ajax()
{
    // Verify nonce would be here

    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    if (!$teacher_id) {
        wp_send_json_error('Invalid teacher ID');
        return;
    }

    // Update user data
    $userdata = array(
        'ID' => $teacher_id,
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email' => sanitize_email($_POST['email'])
    );

    // Update password if provided
    if (!empty($_POST['password'])) {
        $userdata['user_pass'] = $_POST['password'];
    }

    $user_id = wp_update_user($userdata);

    if (is_wp_error($user_id)) {
        wp_send_json_error($user_id->get_error_message());
        return;
    }

    // Update user meta
    update_user_meta($teacher_id, 'phone', sanitize_text_field($_POST['phone']));
    update_user_meta($teacher_id, 'dob', sanitize_text_field($_POST['dob']));
    update_user_meta($teacher_id, 'age', sanitize_text_field($_POST['age']));
    update_user_meta($teacher_id, 'country', sanitize_text_field($_POST['country']));
    update_user_meta($teacher_id, 'lessons_name', sanitize_text_field($_POST['lessons_name']));
    update_user_meta($teacher_id, 'supervisor', sanitize_text_field($_POST['supervisor']));
    update_user_meta($teacher_id, 'teacher_classification', sanitize_text_field($_POST['teacher_classification']));
    update_user_meta($teacher_id, 'teacher_status', sanitize_text_field($_POST['teacher_status']));


    wp_send_json_success('Teacher updated successfully');
}
add_action('wp_ajax_update_teacher', 'update_teacher_ajax');

// Add this function to get supervisors list
function get_supervisors_list_ajax()
{
    $supervisors = get_users(array(
        'role' => 'supervisor',
        'fields' => array('ID', 'display_name')
    ));

    wp_send_json_success($supervisors);
}
add_action('wp_ajax_get_supervisors_list', 'get_supervisors_list_ajax');







function display_teacher_data_shortcode()
{
    // Check if the current user is a teacher
    if (!current_user_can('teacher')) {
        return 'هذا المحتوى متاح فقط للمعلمين.';
    }

    global $wpdb;

    // Get the current user (teacher) ID
    $teacher_id = get_current_user_id();

    // Get selected month and year (default to current month and year if not provided)
    $selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $selected_month_year = sprintf('%04d-%02d', $selected_year, $selected_month);

    // Fetch students assigned to this teacher
    $students = get_users(array(
        'role' => 'student',
        'meta_key' => 'teacher',
        'meta_value' => $teacher_id,
    ));

    // Initialize variables for calculations
    $all_time_lesson_count = 0;
    $all_time_total_minutes = 0;
    $current_month_lesson_count = 0;
    $current_month_total_minutes = 0;

    // Query wp_student_reports for selected month data
    // Exclude reports with session_number = 0 (added from إضافة انجاز button)
    // These don't count towards regular lesson statistics
    $table_name = $wpdb->prefix . 'student_reports';
    $current_month_reports = $wpdb->get_results($wpdb->prepare(
        "SELECT lesson_duration
         FROM $table_name
         WHERE teacher_id = %d
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
         AND session_number > 0",
        $teacher_id,
        $selected_month_year
    ));

    // Calculate monthly lessons and hours
    $current_month_lesson_count = count($current_month_reports); // Count rows
    foreach ($current_month_reports as $report) {
        $current_month_total_minutes += intval($report->lesson_duration); // Sum lesson_duration
    }

    // Prepare student data and calculate all-time totals
    $student_data = array();
    foreach ($students as $student) {
        $lessons_number = intval(get_user_meta($student->ID, 'lessons_number', true));
        $lesson_duration = intval(get_user_meta($student->ID, 'lesson_duration', true));

        // All-time totals
        $all_time_lesson_count += $lessons_number;
        $all_time_total_minutes += $lessons_number * $lesson_duration;

        $student_data[] = array(
            'm_id' => get_user_meta($student->ID, 'm_id', true),
            'name' => $student->display_name,
            'phone' => get_user_meta($student->ID, 'phone', true),
            'age' => get_user_meta($student->ID, 'age', true),
            'country' => get_user_meta($student->ID, 'country', true),
            'lessons_number' => $lessons_number,
            'lesson_duration' => $lesson_duration,
        );
    }

    // Sort students by Arabic alphabet
    usort($student_data, function ($a, $b) {
        return strcoll($a['name'], $b['name']);
    });

    // Format total hours and minutes
    $all_time_total_hours = floor($all_time_total_minutes / 60) . 'h : ' . ($all_time_total_minutes % 60) . 'm';
    $current_month_total_hours = floor($current_month_total_minutes / 60) . 'h : ' . ($current_month_total_minutes % 60) . 'm';

    // Calculate عدد الطلاب (total hours / 4)
    $total_hours = $all_time_total_minutes / 60; // Convert total minutes to hours
    $number_of_students_calculated = round($total_hours / 4, 2); // Divide by 4 and round to 2 decimal places

    // Get the real student count
    $real_student_count = count($students);

    // Fetch data for the new widgets
    $student_delays = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تأجيل ولي أمر'",
        $teacher_id,
        $selected_month_year
    ));

    $teacher_delays = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تأجيل المعلم'",
        $teacher_id,
        $selected_month_year
    ));

    $compensation_delays = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تعويض التأجيل'",
        $teacher_id,
        $selected_month_year
    ));

    $compensation_absences = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تعويض الغياب'",
        $teacher_id,
        $selected_month_year
    ));

    // Start output buffering
    ob_start();
?>
    <div class="teacher-dashboard" data-teacher-id="<?php echo $teacher_id; ?>">
        <h2>لوحة تحكم المعلم</h2>

        <!-- Widgets Container -->
        <div class="widgets-container">
            <!-- First Row: Main Widgets -->
            <div class="widget-row">
                <div class="widget">
                    <h4>🧑‍🏫 عدد الطلاب الحالي</h4>
                    <p id="widget-students"><?php echo $real_student_count; ?></p>
                </div>
                <div class="widget">
                    <h4>📅 الحصص الشهرية</h4>
                    <p id="widget-all-time-lessons"><?php echo $all_time_lesson_count; ?></p>
                </div>
                <div class="widget">
                    <h4>⏳ الساعات الشهرية</h4>
                    <p id="widget-all-time-hours"><?php echo $all_time_total_hours; ?></p>
                </div>
                <div class="widget">
                    <h4>✅ الحصص المكتملة</h4>
                    <p id="widget-current-month-lessons"><?php echo $current_month_lesson_count; ?></p>
                </div>
                <div class="widget">
                    <h4>⏱️ الساعات المكتملة</h4>
                    <p id="widget-current-month-hours"><?php echo $current_month_total_hours; ?></p>
                </div>
            </div>

            <!-- Second Row: Month Selector and Last 4 Widgets -->
            <div class="widget-row">
                <!-- Month and Year Selector -->
                <div class="month-year-selector">
                    <select id="shortcode-month-select">
                        <?php
                        $months = [
                            1 => 'يناير',
                            2 => 'فبراير',
                            3 => 'مارس',
                            4 => 'أبريل',
                            5 => 'مايو',
                            6 => 'يونيو',
                            7 => 'يوليو',
                            8 => 'أغسطس',
                            9 => 'سبتمبر',
                            10 => 'أكتوبر',
                            11 => 'نوفمبر',
                            12 => 'ديسمبر',
                        ];
                        foreach ($months as $key => $month) : ?>
                            <option value="<?php echo $key; ?>" <?php echo ($key == $selected_month) ? 'selected' : ''; ?>>
                                <?php echo $month; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="shortcode-year-select">
                        <?php
                        $current_year = date('Y');
                        for ($i = $current_year; $i >= $current_year - 3; $i--) : ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i == $selected_year) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Last 4 Widgets -->
                <div class="widget">
                    <h4>🧑‍🎓 تأجيلات الطلاب</h4>
                    <p id="widget-student-delays"><?php echo $student_delays; ?></p>
                </div>
                <div class="widget">
                    <h4>🧑‍🏫 تأجيلات المعلم</h4>
                    <p id="widget-teacher-delays"><?php echo $teacher_delays; ?></p>
                </div>
                <div class="widget">
                    <h4>🕐 تعويض التأجيل</h4>
                    <p id="widget-compensation-delays"><?php echo $compensation_delays; ?></p>
                </div>
                <div class="widget">
                    <h4>❌ تعويض الغياب</h4>
                    <p id="widget-compensation-absences"><?php echo $compensation_absences; ?></p>
                </div>
            </div>
        </div>

        <!-- Table -->
        <?php
        // Sort the array by m_id
        usort($student_data, function ($a, $b) {
            return $a['m_id'] - $b['m_id']; // Ascending order
        });
        ?>

        <table id="students-table">
            <thead>
                <tr>
                    <th>م</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>العمر</th>
                    <th>البلد</th>
                    <th>عدد الدروس</th>
                    <th>المدة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($student_data as $student): ?>
                    <tr>
                        <td><?php echo $student['m_id']; ?></td>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['phone']; ?></td>
                        <td><?php echo $student['age']; ?></td>
                        <td><?php echo $student['country']; ?></td>
                        <td><?php echo $student['lessons_number']; ?></td>
                        <td><?php echo $student['lesson_duration']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
    // Return the output
    return ob_get_clean();
}
add_shortcode('display_teacher_data', 'display_teacher_data_shortcode');








function get_teacher_students()
{
    global $wpdb;

    // Validate teacher ID
    $teacher_id = intval($_POST['teacher_id']);
    if (empty($teacher_id)) {
        wp_send_json_error(array('message' => 'معرف المعلم غير صالح.'));
    }

    // Get selected month and year (default to current month and year if not provided)
    $selected_month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
    $selected_year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
    $selected_month_year = sprintf('%04d-%02d', $selected_year, $selected_month);

    // Fetch students assigned to this teacher
    $students = get_users(array(
        'role' => 'student',
        'meta_key' => 'teacher',
        'meta_value' => $teacher_id,
    ));

    // Initialize variables for calculations
    $all_time_lesson_count = 0;
    $all_time_total_minutes = 0;
    $current_month_lesson_count = 0;
    $current_month_total_minutes = 0;

    // Query wp_student_reports for selected month data
    // Exclude reports with session_number = 0 (added from إضافة انجاز button)
    // These don't count towards regular lesson statistics
    $table_name = $wpdb->prefix . 'student_reports';
    $current_month_reports = $wpdb->get_results($wpdb->prepare(
        "SELECT lesson_duration
         FROM $table_name
         WHERE teacher_id = %d
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
         AND session_number > 0",
        $teacher_id,
        $selected_month_year
    ));

    // Calculate monthly lessons and hours
    $current_month_lesson_count = count($current_month_reports); // Count rows
    foreach ($current_month_reports as $report) {
        $current_month_total_minutes += intval($report->lesson_duration); // Sum lesson_duration
    }

    // Prepare student data and calculate all-time totals
    $student_data = array();
    foreach ($students as $student) {
        $lessons_number = intval(get_user_meta($student->ID, 'lessons_number', true));
        $lesson_duration = intval(get_user_meta($student->ID, 'lesson_duration', true));

        // All-time totals
        $all_time_lesson_count += $lessons_number;
        $all_time_total_minutes += $lessons_number * $lesson_duration;

        $student_data[] = array(
            'm_id' => get_user_meta($student->ID, 'm_id', true),
            'id' => $student->ID,
            'name' => $student->display_name,
            'email' => $student->user_email,
            'phone' => get_user_meta($student->ID, 'phone', true),
            'dob' => get_user_meta($student->ID, 'dob', true),
            'age' => get_user_meta($student->ID, 'age', true),
            'country' => get_user_meta($student->ID, 'country', true),
            'lessons_name' => get_user_meta($student->ID, 'lessons_name', true),
            'lessons_number' => $lessons_number,
            'lesson_duration' => $lesson_duration,
            'currency' => get_user_meta($student->ID, 'currency', true),
            'previous_lesson' => get_user_meta($student->ID, 'previous_lesson', true),
            'amount' => get_user_meta($student->ID, 'amount', true),
            'notes' => get_user_meta($student->ID, 'notes', true),
            'payment_status' => get_user_meta($student->ID, 'payment_status', true),
        );
    }

    // Sort students by Arabic alphabet
    usort($student_data, function ($a, $b) {
        return strcoll($a['name'], $b['name']);
    });

    // Format total hours and minutes
    $all_time_total_hours = floor($all_time_total_minutes / 60) . 'h : ' . ($all_time_total_minutes % 60) . 'm';
    $current_month_total_hours = floor($current_month_total_minutes / 60) . 'h : ' . ($current_month_total_minutes % 60) . 'm';

    // Calculate عدد الطلاب (total hours / 4)
    $total_hours = $all_time_total_minutes / 60; // Convert total minutes to hours
    $number_of_students_calculated = round($total_hours / 4, 2); // Divide by 4 and round to 2 decimal places

    // Get the real student count
    $real_student_count = count($students);

    // Fetch data for the new widgets
    $student_delays = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تأجيل ولي أمر'",
        $teacher_id,
        $selected_month_year
    ));

    $teacher_delays = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تأجيل المعلم'",
        $teacher_id,
        $selected_month_year
    ));

    $compensation_delays = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تعويض الغياب'",
        $teacher_id,
        $selected_month_year
    ));

    $compensation_absences = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) 
         FROM $table_name 
         WHERE teacher_id = %d 
         AND DATE_FORMAT(date, '%%Y-%%m') = %s
         AND attendance = 'تعويض التأجيل'",
        $teacher_id,
        $selected_month_year
    ));

    // Return student data and calculated metrics
    wp_send_json_success(array(
        'students' => $student_data,
        'all_time_lesson_count' => $all_time_lesson_count,
        'all_time_total_hours' => $all_time_total_hours,
        'current_month_lesson_count' => $current_month_lesson_count,
        'current_month_total_hours' => $current_month_total_hours,
        'number_of_students_calculated' => $number_of_students_calculated,
        'real_student_count' => $real_student_count,
        'student_delays' => $student_delays,
        'teacher_delays' => $teacher_delays,
        'compensation_delays' => $compensation_delays,
        'compensation_absences' => $compensation_absences,
    ));
}
add_action('wp_ajax_get_teacher_students', 'get_teacher_students');
add_action('wp_ajax_nopriv_get_teacher_students', 'get_teacher_students'); // If needed for non-logged-in users














function zuwad_get_student_data()
{
    // Debug: Log the incoming request
    //  error_log('Received AJAX request to fetch student data.');

    // Validate student_id (m_id)
    $student_m_id = sanitize_text_field($_POST['student_id']); // Treat m_id as a string
    if (empty($student_m_id)) {
        //  error_log('Invalid student m_id received: ' . $student_m_id);
        wp_send_json_error(array('message' => 'معرف الطالب غير صالح.'));
    }

    // Debug: Log the student m_id being searched
    //  error_log('Searching for student with m_id: ' . $student_m_id);

    // Fetch user by m_id (custom meta field)
    $students = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $student_m_id, // Match the exact string, including leading zeros
        'number' => 1, // Limit to 1 result
    ));

    if (empty($students)) {
        //  error_log('No student found with m_id: ' . $student_m_id);
        wp_send_json_error(array('message' => 'لم يتم العثور على الطالب.'));
    }

    $student = $students[0]; // Get the first (and only) student
    $student_id = $student->ID; // Get the WordPress user ID

    // Debug: Log the found student
    //  error_log('Found student: ' . print_r($student, true));

    // Fetch teacher data
    $teacher_id = get_user_meta($student_id, 'teacher', true);
    $teacher = get_userdata($teacher_id);
    $teacher_name = $teacher ? $teacher->display_name : '';

    // Debug: Log teacher data
    //  error_log('Teacher ID: ' . $teacher_id);
    //  error_log('Teacher Name: ' . $teacher_name);

    // Prepare student data
    $student_data = array(
        'm_id' => $student_m_id, // Include m_id in the response
        'display_name' => $student->display_name,
        'email' => $student->user_email,
        'phone' => get_user_meta($student_id, 'phone', true),
        'age' => get_user_meta($student_id, 'age', true),
        'dob' => get_user_meta($student_id, 'dob', true),
        'country' => get_user_meta($student_id, 'country', true),
        'lessons_name' => get_user_meta($student_id, 'lessons_name', true),
        'lessons_number' => get_user_meta($student_id, 'lessons_number', true),
        'lesson_duration' => get_user_meta($student_id, 'lesson_duration', true),
        'currency' => get_user_meta($student_id, 'currency', true),
        'previous_lesson' => get_user_meta($student_id, 'previous_lesson', true),

        'amount' => get_user_meta($student_id, 'amount', true),
        'notes' => get_user_meta($student_id, 'notes', true),
        'payment_status' => get_user_meta($student_id, 'payment_status', true),
        'teacher_name' => $teacher_name, // Add teacher's name
        'teacher_id' => $teacher_id, // Add teacher's ID
    );

    // Debug: Log the student data being returned
    // error_log('Student Data: ' . print_r($student_data, true));

    // Return student data
    wp_send_json_success(array('student' => $student_data));
}
add_action('wp_ajax_get_student_data', 'zuwad_get_student_data');
add_action('wp_ajax_nopriv_get_student_data', 'zuwad_get_student_data'); // If needed for non-logged-in users





function zuwad_update_student()
{
    // Debug: Log the incoming request
    //  error_log('Received AJAX request to update student data.');

    // Validate student_id (m_id)
    $student_m_id = sanitize_text_field($_POST['student_id']); // Treat m_id as a string
    if (empty($student_m_id)) {
        //  error_log('Invalid student m_id received: ' . $student_m_id);
        wp_send_json_error(array('message' => 'معرف الطالب غير صالح.'));
    }

    // Debug: Log the student m_id being updated
    //  error_log('Updating student with m_id: ' . $student_m_id);

    // Fetch user by m_id (custom meta field)
    $students = get_users(array(
        'meta_key' => 'm_id',
        'meta_value' => $student_m_id,
        'number' => 1,
    ));

    if (empty($students)) {
        //  error_log('No student found with m_id: ' . $student_m_id);
        wp_send_json_error(array('message' => 'لم يتم العثور على الطالب.'));
    }

    $student = $students[0]; // Get the first (and only) student
    $student_id = $student->ID;

    // Debug: Log the found student
    //  error_log('Found student: ' . print_r($student, true));

    // Check if the teacher has been changed
    $new_teacher_id = intval($_POST['teacher']);
    $current_teacher_id = get_user_meta($student_id, 'teacher', true);

    if ($new_teacher_id != $current_teacher_id) {
        global $wpdb;

        // Teacher has been changed
        // Step 1: Generate a new m_id for the student
        $new_teacher_m_id = get_user_meta($new_teacher_id, 'm_id', true);
        if (empty($new_teacher_m_id)) {
            //  error_log('Invalid teacher m_id for new teacher: ' . $new_teacher_id);
            wp_send_json_error(array('message' => 'لم يتم العثور على م (ID) للمعلم الجديد.'));
        }

        $student_counter = (int) get_user_meta($new_teacher_id, 'student_counter', true) ?: 1;
        $new_m_id = $new_teacher_m_id . str_pad($student_counter, 2, '0', STR_PAD_LEFT);

        // Update the student_counter for the new teacher
        update_user_meta($new_teacher_id, 'student_counter', $student_counter + 1);

        // Update the student's m_id
        update_user_meta($student_id, 'm_id', $new_m_id);

        // Debug: Log the new m_id
        //  error_log('New m_id generated: ' . $new_m_id);

        // Step 2: Remove all rows in wp_student_schedules for this student
        $table_name = $wpdb->prefix . 'student_schedules';
        $delete_result = $wpdb->delete($table_name, array('student_id' => $student_id));

        if ($delete_result === false) {
            //  error_log('Error deleting rows from wp_student_schedules for student_id: ' . $student_id);
            wp_send_json_error(array('message' => 'حدث خطأ أثناء تحديث جداول الطالب.'));
        } else {
            //  error_log('Deleted rows from wp_student_schedules for student_id: ' . $student_id);
        }
    }

    // Update user data
    $user_data = array(
        'ID' => $student_id,
        'display_name' => sanitize_text_field($_POST['display_name']),
        'user_email' => sanitize_email($_POST['email']),
    );

    // Update password if provided
    if (!empty($_POST['password'])) {
        $user_data['user_pass'] = $_POST['password'];
    }

    $update_result = wp_update_user($user_data);

    if (is_wp_error($update_result)) {
        //  error_log('Error updating user data: ' . $update_result->get_error_message());
        wp_send_json_error(array('message' => 'حدث خطأ أثناء تحديث بيانات المستخدم: ' . $update_result->get_error_message()));
    }

    // Update user meta
    update_user_meta($student_id, 'phone', sanitize_text_field($_POST['phone']));
    update_user_meta($student_id, 'dob', sanitize_text_field($_POST['dob']));
    update_user_meta($student_id, 'age', intval($_POST['age']));
    update_user_meta($student_id, 'country', sanitize_text_field($_POST['country']));
    update_user_meta($student_id, 'lessons_name', sanitize_text_field($_POST['lessons_name']));
    update_user_meta($student_id, 'lessons_number', intval($_POST['lessons_number']));
    update_user_meta($student_id, 'lesson_duration', sanitize_text_field($_POST['lesson_duration']));
    update_user_meta($student_id, 'currency', sanitize_text_field($_POST['currency']));
    update_user_meta($student_id, 'previous_lesson', floatval($_POST['previous_lesson']));
    update_user_meta($student_id, 'amount', floatval($_POST['amount']));
    update_user_meta($student_id, 'notes', sanitize_textarea_field($_POST['notes']));
    update_user_meta($student_id, 'payment_status', sanitize_text_field($_POST['payment_status']));

    if ($new_teacher_id) {
        update_user_meta($student_id, 'teacher', $new_teacher_id);
    }

    // Debug: Log the updated data
    //  error_log('Updated User Data: ' . print_r($user_data, true));
    error_log('Updated User Meta: ' . print_r(array(
        'phone' => $_POST['phone'],
        'dob' => $_POST['dob'],
        'age' => $_POST['age'],
        'country' => $_POST['country'],
        'lessons_name' => $_POST['lessons_name'],
        'lessons_number' => $_POST['lessons_number'],
        'lesson_duration' => $_POST['lesson_duration'],
        'currency' => $_POST['currency'],
        'previous_lesson' => $_POST['previous_lesson'],
        'amount' => $_POST['amount'],
        'notes' => $_POST['notes'],
        'payment_status' => $_POST['payment_status'],
        'teacher' => $new_teacher_id,
    ), true));

    // Return success response
    wp_send_json_success(array('message' => 'تم تحديث بيانات الطالب بنجاح.', 'new_m_id' => $new_m_id ?? $student_m_id));
}
add_action('wp_ajax_update_student', 'zuwad_update_student');
add_action('wp_ajax_nopriv_update_student', 'zuwad_update_student');






function zuwad_get_edit_teachers()
{
    // Get the current user
    $current_user = wp_get_current_user();

    // Check if the current user is a supervisor or admin
    if (in_array('supervisor', $current_user->roles)) {
        // If supervisor, get the supervisor ID
        $supervisor_id = $current_user->ID;

        // Fetch teachers assigned to this supervisor
        $teachers = get_users(array(
            'role' => 'teacher',
            'meta_key' => 'supervisor',
            'meta_value' => $supervisor_id,
        ));
    } elseif (in_array('administrator', $current_user->roles)) {
        // If admin, fetch all teachers
        $teachers = get_users(array(
            'role' => 'teacher',
        ));
    } else {
        // If the user is neither supervisor nor admin, return an empty array
        $teachers = array();
    }

    // Prepare teacher data
    $teacher_data = array();
    foreach ($teachers as $teacher) {
        $teacher_data[] = array(
            'ID' => $teacher->ID,
            'display_name' => $teacher->display_name,
        );
    }

    // Return teacher data
    wp_send_json_success(array('teachers' => $teacher_data));
}
add_action('wp_ajax_get_edit_teachers', 'zuwad_get_edit_teachers');
add_action('wp_ajax_nopriv_get_edit_teachers', 'zuwad_get_edit_teachers'); // If needed for non-logged-in users








add_action('wp_ajax_delete_student', 'delete_student_callback');

function delete_student_callback()
{
    // Verify nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'zuwad_plugin_nonce')) {
        wp_send_json_error('Invalid nonce.');
    }

    // Verify password
    if (!isset($_POST['password']) || $_POST['password'] !== '55555') {
        wp_send_json_error('Invalid password.');
    }

    // Verify student ID
    if (!isset($_POST['student_id'])) {
        wp_send_json_error('Invalid request.');
    }

    $student_id = sanitize_text_field($_POST['student_id']);

    // Perform the deletion
    $deleted = delete_student_from_database($student_id);

    if ($deleted) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Failed to delete student.');
    }
}






function delete_student_from_database($student_id)
{
    global $wpdb;

    // Step 1: Find the user_id from wp_usermeta where m_id = $student_id
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'm_id' AND meta_value = %s",
        $student_id
    ));

    if (!$user_id) {
        return false; // User not found
    }

    // Step 2: Use the user_id to find the student_id in wp_student_reports and wp_student_schedules
    // (Assuming the student_id in these tables is the same as the user_id or linked to it)

    // Delete from wp_student_reports table where student_id matches
    $reports_table = $wpdb->prefix . 'student_reports';
    $reports_deleted = $wpdb->delete($reports_table, array('student_id' => $user_id));

    // Delete from wp_student_schedules table where student_id matches
    $schedules_table = $wpdb->prefix . 'student_schedules';
    $schedules_deleted = $wpdb->delete($schedules_table, array('student_id' => $user_id));

    // Step 3: Delete the user from wp_users and wp_usermeta tables
    $user_deleted = wp_delete_user($user_id);

    // Check if all deletions were successful
    if ($reports_deleted !== false && $schedules_deleted !== false && $user_deleted) {
        return true; // Success
    } else {
        return false; // Failure
    }
}






























add_action('admin_menu', 'zuwad_bulk_add_schedules_menu');

function zuwad_bulk_add_schedules_menu()
{
    add_menu_page(
        'Bulk Add Schedules', // Page title
        'Bulk Add Schedules', // Menu title
        'manage_options', // Capability
        'zuwad-bulk-add-schedules', // Menu slug
        'zuwad_bulk_add_schedules_page', // Callback function
        'dashicons-calendar-alt', // Icon URL
        7 // Position
    );
}

function zuwad_bulk_add_schedules_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['zuwad_bulk_add_schedules_nonce'])) {
        $logs = zuwad_process_bulk_schedule_creation();
        echo '<div class="wrap">';
        echo '<h2>Processing Logs</h2>';
        echo '<pre>';
        foreach ($logs as $log) {
            echo esc_html($log) . "\n";
        }
        echo '</pre>';
        echo '</div>';
    }

?>
    <div class="wrap">
        <h1>Bulk Add Schedules</h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('zuwad_bulk_add_schedules_action', 'zuwad_bulk_add_schedules_nonce'); ?>
            <p>
                <label for="zuwad_bulk_schedules">Upload CSV file:</label><br>
                <input type="file" id="zuwad_bulk_schedules" name="zuwad_bulk_schedules" accept=".csv" required>
            </p>
            <p>
                <input type="submit" class="button button-primary" value="Add Schedules">
            </p>
        </form>
    </div>
<?php
}

function zuwad_process_bulk_schedule_creation()
{
    if (!wp_verify_nonce($_POST['zuwad_bulk_add_schedules_nonce'], 'zuwad_bulk_add_schedules_action')) {
        wp_die(__('Security check failed.'));
    }

    if (!isset($_FILES['zuwad_bulk_schedules']) || $_FILES['zuwad_bulk_schedules']['error'] !== UPLOAD_ERR_OK) {
        wp_die(__('Error uploading file.'));
    }

    $file = $_FILES['zuwad_bulk_schedules']['tmp_name'];
    $rows = array_map('str_getcsv', file($file));
    $logs = [];

    foreach ($rows as $row) {
        if (count($row) < 2) {
            $logs[] = 'Skipping invalid row: ' . implode(', ', $row);
            continue;
        }

        $student_name = trim($row[0]);
        $schedule_data = array_slice($row, 1); // Extract days and times

        // Normalize student name for comparison
        $normalized_student_name = zuwad_normalize_name($student_name);

        // Get all students and find a match
        $students = get_users(['role' => 'student']);
        $student_id = null;

        foreach ($students as $student) {
            if (zuwad_normalize_name($student->display_name) === $normalized_student_name) {
                $student_id = $student->ID;
                break;
            }
        }

        if (!$student_id) {
            $logs[] = 'Student not found: ' . $student_name;
            continue;
        }

        // Get the student's teacher ID
        $teacher_id = get_user_meta($student_id, 'teacher', true);
        if (!$teacher_id) {
            $logs[] = 'Teacher not found for student: ' . $student_name;
            continue;
        }

        // Get the student's lesson duration
        $lesson_duration = intval(get_user_meta($student_id, 'lesson_duration', true));
        if (!$lesson_duration) {
            $logs[] = 'Lesson duration not found for student: ' . $student_name;
            continue;
        }

        // Format the schedule as JSON
        $schedule = [];
        for ($i = 0; $i < count($schedule_data); $i += 2) {
            $day = trim($schedule_data[$i]);
            $time = trim($schedule_data[$i + 1]);

            if (!empty($day) && !empty($time)) {
                // Convert Arabic time format to 12-hour format with AM/PM
                $time_12h = zuwad_convert_arabic_time_to_12h($time);
                if (!$time_12h) {
                    $logs[] = 'Invalid time format for student: ' . $student_name . ' - Time: ' . $time;
                    continue;
                }

                $schedule[] = array(
                    'day' => $day,
                    'hour' => $time_12h,
                    'original' => null
                );
            }
        }

        if (empty($schedule)) {
            $logs[] = 'No valid schedule data for student: ' . $student_name;
            continue;
        }

        $schedule_json = json_encode($schedule, JSON_UNESCAPED_UNICODE);

        // Check if the student already has a schedule
        global $wpdb;
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
            $logs[] = 'Updated schedule for student: ' . $student_name;
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
            $logs[] = 'Added schedule for student: ' . $student_name;
        }
    }

    return $logs;
}

function zuwad_convert_arabic_time_to_12h($time)
{
    // Check if the time contains ص (AM) or م (PM)
    if (strpos($time, 'ص') !== false) {
        $period = 'AM';
    } elseif (strpos($time, 'م') !== false) {
        $period = 'PM';
    } else {
        return false; // Invalid time format
    }

    // Remove ص or م from the time string
    $time = str_replace(['ص', 'م'], '', $time);
    $time = trim($time);

    // Convert to 12-hour format with AM/PM
    $time_12h = date('g:i A', strtotime($time . ' ' . $period));

    return $time_12h;
}

function zuwad_normalize_name($name)
{
    // Normalize Arabic characters
    $name = str_replace(['أ', 'إ', 'آ', 'ء', 'ئ', 'ؤ'], 'ا', $name);
    $name = str_replace(['ة'], 'ه', $name);
    $name = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name); // Remove special characters
    $name = preg_replace('/\s+/', ' ', $name); // Remove extra spaces
    $name = trim($name); // Trim spaces
    $name = mb_strtolower($name, 'UTF-8'); // Convert to lowercase
    return $name;
}
