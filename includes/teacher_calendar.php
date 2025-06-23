<?php
function zuwad_teacher_calendar_shortcode()
{
    // Check if the user is logged in
    if (!is_user_logged_in()) {
        return 'يجب تسجيل الدخول لعرض الجدول.';
    }

    // Get the current user
    $current_user = wp_get_current_user();

    // Check user roles
    $is_supervisor = in_array('supervisor', $current_user->roles);
    $is_teacher = in_array('teacher', $current_user->roles);
    $is_administrator = in_array('administrator', $current_user->roles);
    $is_kpi = in_array('KPI', $current_user->roles);
    $is_accountant = in_array('Accountant', $current_user->roles);
    $is_sales = in_array('sales', $current_user->roles);

    // Fetch teachers based on user role
    global $wpdb;
    $teachers = [];
    $students = []; // Initialize students array for all user roles

    if ($is_administrator || $is_kpi) {
        // Get all teachers for administrators and KPI users
        $teachers = $wpdb->get_results(
            "SELECT u.ID, u.display_name 
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             WHERE um.meta_key = 'wp_capabilities' 
             AND um.meta_value LIKE '%teacher%'"
        );
    } elseif ($is_supervisor) {
        // Get teachers assigned to supervisor
        $teachers = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name 
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             WHERE um.meta_key = 'supervisor' AND um.meta_value = %d",
            $current_user->ID
        ));
    } elseif ($is_sales) {
        // For sales users, get teachers who have students with payment_status = "معلق"
        // AND teacher status is "نشط عدد كامل" or "نشط نصف عدد"
        $teachers_with_pending_students = $wpdb->get_results(
            "SELECT DISTINCT um.meta_value as ID, u.display_name 
             FROM {$wpdb->usermeta} um
             INNER JOIN {$wpdb->users} u ON um.meta_value = u.ID
             INNER JOIN {$wpdb->usermeta} um2 ON um.user_id = um2.user_id
             INNER JOIN {$wpdb->usermeta} um3 ON um.meta_value = um3.user_id
             WHERE um.meta_key = 'teacher'
             AND um2.meta_key = 'payment_status' 
             AND um2.meta_value = 'معلق'
             AND um3.meta_key = 'teacher_status'
             AND (um3.meta_value = 'نشط عدد كامل' OR um3.meta_value = 'نشط نصف عدد')"
        );

        // Fix: Create a new array with properly structured objects
        $fixed_teachers_with_pending = [];
        foreach ($teachers_with_pending_students as $teacher) {
            $teacher_obj = new stdClass();
            $teacher_obj->ID = intval($teacher->ID);
            $teacher_obj->display_name = $teacher->display_name;
            $fixed_teachers_with_pending[] = $teacher_obj;
        }
        $teachers_with_pending_students = $fixed_teachers_with_pending;

        // Get teachers with number_of_students_calculated less than 18
        $teachers_with_low_students = [];
        $all_teachers = $wpdb->get_results(
            "SELECT u.ID, u.display_name 
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             INNER JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id
             WHERE um.meta_key = 'wp_capabilities' 
             AND um.meta_value LIKE '%teacher%'
             AND um2.meta_key = 'teacher_status'
             AND (um2.meta_value = 'نشط عدد كامل' OR um2.meta_value = 'نشط نصف عدد')"
        );

        foreach ($all_teachers as $teacher) {
            // Calculate number_of_students_calculated for each teacher
            $students_query = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id 
                 FROM {$wpdb->usermeta} 
                 WHERE meta_key = 'teacher' 
                 AND meta_value = %d",
                $teacher->ID
            ));

            $total_minutes = 0;
            foreach ($students_query as $student) {
                $lessons_number = intval(get_user_meta($student->user_id, 'lessons_number', true));
                $lesson_duration = intval(get_user_meta($student->user_id, 'lesson_duration', true));
                $total_minutes += $lessons_number * $lesson_duration;
            }

            $total_hours = $total_minutes / 60;
            $number_of_students_calculated = round($total_hours / 4, 2);

            // Get teacher status
            $teacher_status = get_user_meta($teacher->ID, 'teacher_status', true);

            // Apply different thresholds based on teacher status
            if ($teacher_status == 'نشط عدد كامل' && $number_of_students_calculated < 18) {
                $teachers_with_low_students[] = $teacher;
            } elseif ($teacher_status == 'نشط نصف عدد' && $number_of_students_calculated < 9) {
                $teachers_with_low_students[] = $teacher;
            }
        }

        // Merge both arrays and remove duplicates
        $teachers = array_merge($teachers_with_pending_students, $teachers_with_low_students);
        $unique_teachers = [];
        foreach ($teachers as $teacher) {
            $unique_teachers[$teacher->ID] = $teacher;
        }
        $teachers = array_values($unique_teachers);

        // Initialize students array for sales users
        $students = [];
    } elseif ($is_teacher) {
        // Fetch students assigned to the current teacher
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name 
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             WHERE um.meta_key = 'teacher' AND um.meta_value = %d",
            $current_user->ID
        ));
    }
    // Start output buffering
    ob_start();
?>
    <div id="teacher-calendar-container"
        data-is-teacher="<?php echo $is_teacher ? 'true' : 'false'; ?>"
        data-teacher-id="<?php echo $is_teacher ? $current_user->ID : ''; ?>">
        <div id="calender-filter">
            <!-- Teacher Filter (show for supervisor, administrator, and KPI) -->
            <?php if ($is_supervisor || $is_administrator || $is_kpi || $is_sales) : ?>
                <select id="teacher-filter">
                    <option value="">اختر المعلم</option>
                    <?php
                    // Sort teachers by Arabic alphabet
                    if (!empty($teachers)) {
                        usort($teachers, function ($a, $b) {
                            return strcoll($a->display_name, $b->display_name);
                        });
                        foreach ($teachers as $teacher) : ?>
                            <option value="<?php echo esc_attr($teacher->ID); ?>" <?php selected(isset($_POST['teacher_id']) && $_POST['teacher_id'] == $teacher->ID); ?>>
                                <?php echo esc_html($teacher->display_name); ?>
                            </option>
                    <?php endforeach;
                    } ?>
                </select>
            <?php endif; ?>

            <!-- Student Filter -->
            <select id="student-filter">
                <?php if ($is_teacher || $is_administrator || $is_kpi || (isset($_POST['teacher_id']) && $_POST['teacher_id'])) : ?>
                    <option value="">جميع الطلبة</option>
                <?php endif; ?>
                <?php
                // Sort students by Arabic alphabet
                if (!empty($students)) {
                    // usort($students, function ($a, $b) {
                    //     return strcoll($a->display_name, $b->display_name);
                    // });
                    foreach ($students as $key => $student) {
                        if (!isset($student->display_name) && isset($student->user_id)) {
                            // If we only have user_id, fetch the full user data
                            $user_data = get_userdata($student->user_id);
                            if ($user_data) {
                                $students[$key]->ID = $student->user_id;
                                $students[$key]->display_name = $user_data->display_name;
                            } else {
                                // Remove invalid student entries
                                unset($students[$key]);
                            }
                        }
                    }

                    // Reset array keys after possible removals
                    $students = array_values($students);

                    if (!empty($students)) {
                        usort($students, function ($a, $b) {
                            return strcoll($a->display_name, $b->display_name);
                        });
                        foreach ($students as $student) : ?>
                            <option value="<?php echo esc_attr(isset($student->ID) ? $student->ID : $student->user_id); ?>">
                                <?php echo esc_html($student->display_name); ?>
                            </option>
                <?php endforeach;
                    }
                } ?>
            </select>
        </div>

        <!-- Calendar -->
        <div id="teacher-calendar"></div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('teacher_calendar', 'zuwad_teacher_calendar_shortcode');



function zuwad_get_teacher_schedule()
{
    global $wpdb;

    // Check if the user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User is not logged in.');
    }

    // Get the current user
    $current_user = wp_get_current_user();

    // Check user roles
    $is_supervisor = in_array('supervisor', $current_user->roles);
    $is_administrator = in_array('administrator', $current_user->roles);
    $is_kpi = in_array('KPI', $current_user->roles);
    $is_accountant = in_array('Accountant', $current_user->roles);
    $is_sales = in_array('sales', $current_user->roles);
    $is_teacher = in_array('teacher', $current_user->roles);

    // Initialize variables
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;

    // Get the start and end dates from the request
    $start_date = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : null;
    $end_date = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : null;

    if (!$start_date || !$end_date) {
        wp_send_json_error('Invalid date range.');
    }

    // Convert the start and end dates to DateTime objects
    $start_date = new DateTime($start_date);
    $end_date = new DateTime($end_date);

    // If the user is a supervisor and not an administrator, ensure the teacher is under their supervision
    if ($is_supervisor && !$is_administrator && $teacher_id) {
        $is_teacher_under_supervisor = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
             FROM {$wpdb->usermeta} 
             WHERE meta_key = 'supervisor' AND meta_value = %d AND user_id = %d",
            $current_user->ID,
            $teacher_id
        ));

        if (!$is_teacher_under_supervisor) {
            wp_send_json_error('Teacher is not under your supervision.');
        }
    }

    // Fetch schedules based on the selected teacher or student
    $query = "SELECT * FROM wp_student_schedules WHERE 1=1";
    if ($teacher_id) {
        $query .= $wpdb->prepare(" AND teacher_id = %d", $teacher_id);
    }
    if ($student_id) {
        $query .= $wpdb->prepare(" AND student_id = %d", $student_id);
    }

    $schedules = $wpdb->get_results($query);

    $events = array();

    // Add scheduled events
    foreach ($schedules as $schedule) {
        $schedule_data = json_decode($schedule->schedule, true);

        // Fetch the student's display_name and m_id
        $student = get_userdata($schedule->student_id);
        $student_name = $student ? $student->display_name : 'Unknown Student';
        $m_id = get_user_meta($schedule->student_id, 'm_id', true);

        if (empty($m_id)) {
            $m_id = 'N/A';
        }

        $title = $student_name;

        foreach ($schedule_data as $event) {
            $day = $event['day'];
            $start_time = $event['hour'];

            // Fetch the latest lesson_duration from the student's metadata for gray lessons
            $latest_lesson_duration = get_user_meta($schedule->student_id, 'lesson_duration', true);

            $day_map = [
                'الأحد' => 0,    // Sunday
                'الاثنين' => 1,  // Monday
                'الثلاثاء' => 2, // Tuesday
                'الأربعاء' => 3, // Wednesday
                'الخميس' => 4,   // Thursday
                'الجمعة' => 5,   // Friday
                'السبت' => 6     // Saturday
            ];

            $day_number = $day_map[$day];

            // Parse the start time using DateTime
            $start_datetime = DateTime::createFromFormat('g:i A', $start_time);
            if (!$start_datetime) {
                // Log an error if the time format is invalid
                // error_log("Invalid time format: $start_time");
                continue;
            }

            // Convert the start time to 24-hour format for FullCalendar
            $start_time_24h = $start_datetime->format('H:i:s');

            // Generate events for each week within the visible date range
            $current_date = clone $start_date;
            while ($current_date <= $end_date) {
                // Calculate the event date for the current week
                $current_day_of_week = $current_date->format('w'); // 0 (Sunday) to 6 (Saturday)
                $days_difference = $day_number - $current_day_of_week;
                $event_date = (clone $current_date)->modify("{$days_difference} days")->setTime($start_datetime->format('H'), $start_datetime->format('i'));

                // Skip if the event date is outside the visible range
                if ($event_date < $start_date || $event_date >= $end_date) {
                    $current_date->modify('+1 week');
                    continue;
                }

                // Convert 24-hour time to 12-hour format for the query
                $start_time_12h = date("h:i A", strtotime($start_time_24h));

                // Check if a report exists for this event
                $report_exists = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM wp_student_reports 
                     WHERE student_id = %d AND date = %s AND time = %s",
                    $schedule->student_id,
                    $event_date->format('Y-m-d'),
                    $start_time_12h
                ));


                // Determine if the event is in the past, present, or future
                $now = new DateTime();
                $twoDaysAgo = clone $now;
                $twoDaysAgo->modify('-2 days');
                $twoDaysAgo->setTime(0, 0, 0); // Set to beginning of 2 days ago

                if ($event_date->format('Y-m-d') === $now->format('Y-m-d')) {
                    $eventStatus = 'today'; // Today's event
                } elseif ($event_date >= $twoDaysAgo && $event_date < $now) {
                    $eventStatus = 'recent'; // Within the last 2 days
                } elseif ($event_date < $twoDaysAgo) {
                    $eventStatus = 'past'; // Past event (more than 2 days ago)

                    // Skip adding this event if it's more than 2 days old AND has no report (gray lesson)
                    if (!$report_exists) {
                        $current_date->modify('+1 week');
                        continue; // Skip to the next iteration
                    }
                } else {
                    $eventStatus = 'future'; // Future event
                }

                // Determine the lesson_duration to use
                $event_lesson_duration = $report_exists ? $report_exists->lesson_duration : $latest_lesson_duration;

                // Calculate the end time
                $end_datetime = clone $start_datetime;
                $end_datetime->modify("+{$event_lesson_duration} minutes");
                $end_time_24h = $end_datetime->format('H:i:s');

                // Include attendance in the event data
                $attendance = $report_exists ? $report_exists->attendance : null;

                // Check if the report has been shared on WhatsApp
                $whatsapp_shared = $report_exists ? $report_exists->whatsapp_shared : 0;
                $event_title = $title;
                if ($whatsapp_shared == 1) {
                    $event_title .= ' ✅✨';
                }

                $events[] = [
                    'id' => $schedule->student_id,
                    'title' => $event_title,
                    'start' => $event_date->format('Y-m-d') . 'T' . $start_time_24h,
                    'end' => $event_date->format('Y-m-d') . 'T' . $end_time_24h,
                    'extendedProps' => [
                        'student_id' => $schedule->student_id,
                        'lesson_duration' => $event_lesson_duration,
                        'eventStatus' => $eventStatus,
                        'isSubmitted' => $report_exists ? true : false,
                        'isAdHoc' => false,
                        'reportData' => $report_exists ? $report_exists : null,
                        'attendance' => $attendance
                    ],
                    'color' => $report_exists ? 'green' : 'gray'
                ];

                // Move to the next week
                $current_date->modify('+1 week');
            }
        }
    }

    // Add ad-hoc reports (reports not part of the schedule)
    $ad_hoc_query = "SELECT * FROM wp_student_reports WHERE 1=1";
    if ($teacher_id) {
        $ad_hoc_query .= $wpdb->prepare(" AND teacher_id = %d", $teacher_id);
    }
    if ($student_id) {
        $ad_hoc_query .= $wpdb->prepare(" AND student_id = %d", $student_id);
    }
    $ad_hoc_query .= $wpdb->prepare(
        " AND date >= %s AND date <= %s",
        $start_date->format('Y-m-d'),
        $end_date->format('Y-m-d')
    );

    $ad_hoc_reports = $wpdb->get_results($ad_hoc_query);

    foreach ($ad_hoc_reports as $report) {
        // Check if this report is already in the calendar (as a scheduled event)
        $is_scheduled = false;
        foreach ($events as $event) {
            if (
                $event['extendedProps']['student_id'] == $report->student_id &&
                $event['start'] == $report->date . 'T' . date("H:i:s", strtotime($report->time))
            ) {
                $is_scheduled = true;
                break;
            }
        }

        // If the report is not part of the schedule, add it as an ad-hoc event
        if (!$is_scheduled) {
            $student = get_userdata($report->student_id);
            $student_name = $student ? $student->display_name : 'Unknown Student';
            $m_id = get_user_meta($report->student_id, 'm_id', true);

            if (empty($m_id)) {
                $m_id = 'N/A';
            }

            $title = $student_name;

            // Use the lesson_duration from the report
            $lesson_duration = $report->lesson_duration;

            // Parse the report time
            $start_datetime = DateTime::createFromFormat('H:i:s', date('H:i:s', strtotime($report->time)));
            if (!$start_datetime) {
                // Log an error if the time format is invalid
                // error_log("Invalid time format for ad-hoc report: " . $report->time);
                continue;
            }

            // Calculate the end time based on the lesson_duration
            $end_datetime = clone $start_datetime;
            $end_datetime->modify("+{$lesson_duration} minutes");

            // Include attendance in the event data
            $attendance = $report->attendance;

            // Check if the report has been shared on WhatsApp
            $whatsapp_shared = $report->whatsapp_shared;
            $event_title = $title;
            if ($whatsapp_shared == 1) {
                $event_title .= ' 💫✅';
            }

            $events[] = [
                'id' => $report->student_id,
                'title' => $event_title,
                'start' => $report->date . 'T' . $start_datetime->format('H:i:s'), // Use report date and time
                'end' => $report->date . 'T' . $end_datetime->format('H:i:s'), // Calculate end time
                'extendedProps' => [
                    'student_id' => $report->student_id,
                    'isAdHoc' => true, // Mark as an ad-hoc event
                    'reportData' => $report, // Include full report data
                    'attendance' => $attendance // Include attendance
                ],
                // 'color' => 'purple' // Set color to purple for ad-hoc events
            ];




            // // Only add the event if it's not a past event without attendance
            // if (!($event_date < $now && !$attendance)) {
            //     $events[] = [
            //         'id' => $schedule->student_id,
            //         'title' => $event_title,
            //         'start' => $event_date->format('Y-m-d') . 'T' . $start_time_24h,
            //         'end' => $event_date->format('Y-m-d') . 'T' . $end_time_24h,
            //         'extendedProps' => [
            //             'student_id' => $schedule->student_id,
            //             'lesson_duration' => $event_lesson_duration,
            //             'eventStatus' => $eventStatus,
            //             'isSubmitted' => $report_exists ? true : false,
            //             'isAdHoc' => false,
            //             'reportData' => $report_exists ? $report_exists : null,
            //             'attendance' => $attendance
            //         ],
            //         'color' => $report_exists ? 'green' : 'gray'
            //     ];
            // }




        }
    }

    wp_send_json($events);
}
add_action('wp_ajax_zuwad_get_teacher_schedule', 'zuwad_get_teacher_schedule');
add_action('wp_ajax_nopriv_zuwad_get_teacher_schedule', 'zuwad_get_teacher_schedule');
