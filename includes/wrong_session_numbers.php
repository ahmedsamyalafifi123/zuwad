<?php
/**
 * Wrong Session Numbers Detector
 * 
 * This file contains the shortcode to detect and display lessons with incorrect session numbers
 * with month filtering capability and AJAX loading
 */

/**
 * Shortcode to display lessons with incorrect session numbers
 * 
 * @return string HTML output showing lessons with incorrect numbering
 */
function zuwad_wrong_session_numbers_shortcode() {
    ob_start();
    
    // Get current month and year
    $current_month = date('m');
    $current_year = date('Y');
    
    // Get selected months and year from URL parameters or use current
    $selected_months = isset($_GET['months']) && is_array($_GET['months']) ? 
        array_map('intval', $_GET['months']) : array(intval($current_month));
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval($current_year);
    
    // Validate months and year
    $selected_months = array_filter($selected_months, function($month) {
        return $month >= 1 && $month <= 12;
    });
    if (empty($selected_months)) {
        $selected_months = array(intval($current_month));
    }
    if ($selected_year < 2020 || $selected_year > 2030) {
        $selected_year = intval($current_year);
    }
    
    // Create month selector
    echo '<div class="month-filter">';
    echo '<form method="get" id="month-filter-form">';
    echo '<label for="month-select">اختر الشهور: </label>';
    echo '<div class="month-checkboxes">';
    
    $month_names = array(
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
        12 => 'ديسمبر'
    );
    
    foreach ($month_names as $num => $name) {
        $checked = in_array($num, $selected_months) ? 'checked' : '';
        echo "<label class='month-checkbox'>";
        echo "<input type='checkbox' name='months[]' value='$num' $checked> $name";
        echo "</label>";
    }
    
    echo '</div>';
    
    echo '<label for="year-select"> السنة: </label>';
    echo '<select name="year" id="year-select">';
    
    for ($year = 2020; $year <= 2030; $year++) {
        $selected = ($year == $selected_year) ? 'selected' : '';
        echo "<option value=\"$year\" $selected>$year</option>";
    }
    
    echo '</select>';
    
    // Preserve any other GET parameters
    foreach ($_GET as $key => $value) {
        if ($key != 'months' && $key != 'year') {
            if (is_array($value)) {
                foreach ($value as $v) {
                    echo "<input type='hidden' name='{$key}[]' value='" . esc_attr($v) . "'>";
                }
            } else {
                echo "<input type='hidden' name='" . esc_attr($key) . "' value='" . esc_attr($value) . "'>";
            }
        }
    }
    
    echo '<button type="button" id="load-report-btn">عرض التقرير</button>';
    echo '</form>';
    echo '</div>';
    
    // Loading indicator and results container
    echo '<div id="loading-indicator" style="display: none;">';
    echo '<div class="loading-spinner"></div>';
    echo '<p>جاري تحميل التقرير...</p>';
    echo '</div>';
    
    echo '<div id="zuwad-wrong-sessions-container" class="zuwad-wrong-sessions-container">';
    echo '<h2>تقرير الحلقات ذات الترقيم الخاطئ</h2>';
    echo '<p>اختر الشهور والسنة ثم اضغط "عرض التقرير" لتحميل البيانات.</p>';
    echo '</div>';
    
    // Add CSS
    echo '<style>
        .month-filter {
            direction: rtl;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f8f8;
            border-radius: 5px;
            text-align: center;
        }
        .month-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin: 15px 0;
            text-align: right;
        }
        .month-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        .month-checkbox:hover {
            background-color: #e8e8e8;
        }
        .month-checkbox input[type="checkbox"] {
            margin: 0;
        }
        .month-filter select, .month-filter button {
            margin: 0 5px;
            padding: 8px 15px;
        }
        .month-filter button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .month-filter button:hover {
            background-color: #45a049;
        }
        .month-filter button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        #loading-indicator {
            text-align: center;
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 5px;
            margin: 20px 0;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .zuwad-wrong-sessions-container {
            direction: rtl;
            font-family: Arial, sans-serif;
            margin: 20px 0;
        }
        .wrong-sequence-student {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .wrong-sequence-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .wrong-sequence-table th, .wrong-sequence-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .wrong-sequence-table th {
            background-color: #f2f2f2;
        }
        tr.wrong-session {
            background-color: #ffdddd;
        }
        .editable-session {
            cursor: pointer;
            position: relative;
            text-decoration: underline;
            color: #0073aa;
        }
        .editable-session:hover {
            color: #00a0d2;
            background-color: #f0f0f0;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #d6e9c6;
            border-radius: 4px;
            text-align: center;
            font-size: 18px;
        }
        .error-message {
            background-color: #f2dede;
            color: #a94442;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ebccd1;
            border-radius: 4px;
            text-align: center;
        }
    </style>';
    
    // Add JavaScript for AJAX loading
    echo '<script>
    // Define ajaxurl if it is not already defined (for frontend)
    if (typeof ajaxurl === "undefined") {
        var ajaxurl = "' . admin_url('admin-ajax.php') . '";
    }
    
    jQuery(document).ready(function($) {
        // Load report button click handler
        $("#load-report-btn").on("click", function() {
            loadWrongSessionsReport();
        });
        
        // Function to load the report via AJAX
        function loadWrongSessionsReport() {
            const loadBtn = $("#load-report-btn");
            const loadingIndicator = $("#loading-indicator");
            const resultsContainer = $("#zuwad-wrong-sessions-container");
            
            // Get selected months and year
            const selectedMonths = [];
            $("input[name=\'months[]\']:checked").each(function() {
                selectedMonths.push($(this).val());
            });
            const selectedYear = $("#year-select").val();
            
            if (selectedMonths.length === 0) {
                alert("يرجى اختيار شهر واحد على الأقل");
                return;
            }
            
            // Show loading, hide results
            loadBtn.prop("disabled", true).text("جاري التحميل...");
            loadingIndicator.show();
            resultsContainer.html("<h2>تقرير الحلقات ذات الترقيم الخاطئ</h2>");
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "load_wrong_sessions_report",
                    months: selectedMonths,
                    year: selectedYear,
                    _ajax_nonce: "' . wp_create_nonce('load_wrong_sessions_nonce') . '"
                },
                success: function(response) {
                    if (response.success) {
                        resultsContainer.html(response.data.html);
                        initializeEditableSessions();
                    } else {
                        resultsContainer.html("<div class=\'error-message\'><p>خطأ: " + (response.data || "حدث خطأ غير متوقع") + "</p></div>");
                    }
                },
                error: function() {
                    resultsContainer.html("<div class=\'error-message\'><p>خطأ في الاتصال بالخادم</p></div>");
                },
                complete: function() {
                    loadBtn.prop("disabled", false).text("عرض التقرير");
                    loadingIndicator.hide();
                }
            });
        }
        
        // Function to initialize editable sessions after AJAX load
        function initializeEditableSessions() {
            // Make session numbers editable on click
            $(".editable-session").off("click").on("click", function() {
                const originalValue = $(this).data("original-value");
                const reportId = $(this).data("report-id");
                const cell = $(this);
                const date = $(this).closest("tr").find("td:first").text();
                const attendance = $(this).closest("tr").find("td:nth-child(2)").text();
                
                // Extract lessons_number from the student header
                let lessonsNumber = 0;
                const lessonsMatch = $(this).closest(".wrong-sequence-student").find("h3").text().match(/عدد الحلقات: (\d+)/);
                if (lessonsMatch && lessonsMatch.length > 1) {
                    lessonsNumber = lessonsMatch[1];
                }
                
                // Use SweetAlert2 for editing
                Swal.fire({
                    title: "تعديل رقم الحلقة",
                    html: `
                        <div style="text-align: right; direction: rtl;">
                            <p><strong>التاريخ:</strong> ${date}</p>
                            <p><strong>الحضور:</strong> ${attendance}</p>
                            <p><strong>رقم الحلقة الحالي:</strong> ${originalValue}</p>
                            <p><strong>عدد الحلقات للطالب:</strong> ${lessonsNumber}</p>
                            <input id="session-number-input" type="number" class="swal2-input" value="${originalValue}" min="0">
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: "حفظ",
                    cancelButtonText: "إلغاء",
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    focusConfirm: false,
                    preConfirm: () => {
                        return document.getElementById("session-number-input").value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const newValue = result.value;
                        saveSessionNumber(reportId, newValue, cell, originalValue);
                    }
                });
            });
        }
        
        // Function to save the updated session number
        function saveSessionNumber(reportId, newValue, cell, originalValue) {
            Swal.fire({
                title: "جاري الحفظ...",
                text: "يرجى الانتظار",
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "update_session_number",
                    report_id: reportId,
                    session_number: newValue,
                    _ajax_nonce: "' . wp_create_nonce('update_session_number_nonce') . '"
                },
                success: function(response) {
                    if (response.success) {
                        // Update the cell with the new value
                        cell.text(newValue);
                        cell.data("original-value", newValue);
                        
                        Swal.fire({
                            icon: "success",
                            title: "تم التحديث بنجاح",
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        // Show error and revert to original value
                        Swal.fire({
                            icon: "error",
                            title: "خطأ!",
                            text: response.data ? response.data : "حدث خطأ أثناء التحديث"
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: "error",
                        title: "خطأ!",
                        text: "حدث خطأ أثناء الاتصال بالخادم"
                    });
                }
            });
        }
    });
    </script>';

    return ob_get_clean();
}

// Register the shortcode
add_shortcode('wrong_session_numbers', 'zuwad_wrong_session_numbers_shortcode');

/**
 * AJAX handler to load wrong sessions report
 */
function zuwad_load_wrong_sessions_report() {
    // Check nonce for security
    check_ajax_referer('load_wrong_sessions_nonce', '_ajax_nonce');
    
    global $wpdb;
    
    // Get and validate parameters
    $selected_months = isset($_POST['months']) && is_array($_POST['months']) ? 
        array_map('intval', $_POST['months']) : array();
    $selected_year = isset($_POST['year']) ? intval($_POST['year']) : intval(date('Y'));
    
    // Validate months and year
    $selected_months = array_filter($selected_months, function($month) {
        return $month >= 1 && $month <= 12;
    });
    
    if (empty($selected_months)) {
        wp_send_json_error('يرجى اختيار شهر واحد على الأقل');
        return;
    }
    
    if ($selected_year < 2020 || $selected_year > 2030) {
        wp_send_json_error('السنة غير صحيحة');
        return;
    }
    
    ob_start();
    
    // Get current user info for filtering
    $current_user = wp_get_current_user();
    $current_username = $current_user->user_login;
    $is_admin = current_user_can('administrator');
    
    // Create date ranges for selected months
    $date_conditions = array();
    foreach ($selected_months as $month) {
        $start_date = sprintf('%04d-%02d-01', $selected_year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $date_conditions[] = $wpdb->prepare("(date BETWEEN %s AND %s)", $start_date, $end_date);
    }
    $date_condition_sql = '(' . implode(' OR ', $date_conditions) . ')';
    
    // Build a more targeted query to get only the relevant students
    $meta_query = array();
    
    // Apply m_id filter based on username
    if (!$is_admin) {
        if ($current_username === '1') {
            $meta_query[] = array(
                'key' => 'm_id',
                'value' => '01',
                'compare' => 'LIKE',
                'type' => 'CHAR'
            );
        } else if ($current_username === '2') {
            $meta_query[] = array(
                'key' => 'm_id',
                'value' => '02',
                'compare' => 'LIKE',
                'type' => 'CHAR'
            );
        }
    }
    
    // Get filtered students with meta query
    $args = array(
        'role' => 'student',
        'orderby' => 'display_name',
        'order' => 'ASC',
        'fields' => array('ID', 'display_name') // Only get the fields we need
    );
    
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    
    $students = get_users($args);
    
    // Preload common student meta to reduce DB queries
    $student_ids = wp_list_pluck($students, 'ID');
    
    $teacher_ids = array();
    $m_ids = array();
    $lessons_numbers = array();

    if (!empty($student_ids)) {
        // Preload teacher IDs, m_ids, and lessons_number for all relevant students
        $teacher_ids = get_user_meta_batch($student_ids, 'teacher');
        $m_ids = get_user_meta_batch($student_ids, 'm_id');
        $lessons_numbers = get_user_meta_batch($student_ids, 'lessons_number');
    }
    
    echo '<h2>تقرير الحلقات ذات الترقيم الخاطئ</h2>';
    
    // Show selected months info
    $month_names = array(
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
    );
    
    $selected_month_names = array();
    foreach ($selected_months as $month) {
        $selected_month_names[] = $month_names[$month];
    }
    
    echo '<div class="report-info">';
    echo '<p><strong>الشهور المحددة:</strong> ' . implode(', ', $selected_month_names) . ' ' . $selected_year . '</p>';
    echo '</div>';
    
    if (empty($students)) {
        echo '<p>لا يوجد طلاب مسجلين.</p>';
    } else {
        // Counter to track if any wrong sequences were found
        $wrong_sequences_found = false;
        
        foreach ($students as $student) {
            // Get student's teacher from preloaded data
            $teacher_id = isset($teacher_ids[$student->ID][0]) ? $teacher_ids[$student->ID][0] : 0;
            $teacher_name = 'غير محدد'; // Default value
            
            // Only fetch teacher data if we have a valid ID and haven't loaded it already
            static $teachers_cache = array();
            if ($teacher_id > 0) {
                if (!isset($teachers_cache[$teacher_id])) {
                    $teacher = get_userdata($teacher_id);
                    $teachers_cache[$teacher_id] = $teacher ? $teacher->display_name : 'غير محدد';
                }
                $teacher_name = $teachers_cache[$teacher_id];
            }
            
            // Get student's m_id from preloaded data
            $m_id = isset($m_ids[$student->ID][0]) ? $m_ids[$student->ID][0] : 'غير محدد';
            
            // Get student's lessons_number from preloaded data
            $lessons_number = isset($lessons_numbers[$student->ID][0]) ? intval($lessons_numbers[$student->ID][0]) : 0;
            
            // Get reports for this student for the selected months
            $student_id_sql = intval($student->ID);
            $reports = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, date, time, attendance, session_number FROM {$wpdb->prefix}student_reports 
                    WHERE student_id = %d 
                    AND {$date_condition_sql} 
                    ORDER BY date ASC, time ASC",
                    $student_id_sql
                )
            );
            
            if (!empty($reports)) {
                // Define attendance types
                // Zero attendances - always have session number 0
                $zero_attendances = array('تعويض التأجيل', 'تعويض الغياب', 'تجريبي');
                
                // These attendances should keep the last session number (no increment)
                $non_incrementing_attendances = array('اجازة معلم');
                
                // Valid attendances that should increment
                $valid_attendances = array('حضور', 'غياب', 'تأجيل المعلم', 'تأجيل ولي أمر');
                
                // Check for wrong sequences across multiple months
                $wrong_sequence = false;
                $last_valid_session_number = null;
                $first_valid_found = false;
                
                foreach ($reports as $index => $report) {
                    $session_number = intval($report->session_number);
                    $attendance = $report->attendance;
                    
                    if (in_array($attendance, $zero_attendances)) {
                        // Zero attendances should have session number 0
                        if ($session_number !== 0) {
                            $wrong_sequence = true;
                            break;
                        }
                    }
                    else if (in_array($attendance, $non_incrementing_attendances)) {
                        // Should match the last valid session number
                        if ($last_valid_session_number !== null && $session_number !== $last_valid_session_number) {
                            $wrong_sequence = true;
                            break;
                        }
                    }
                    else if (in_array($attendance, $valid_attendances)) {
                        // Valid attendances should not be 0 (except possibly the first one if no prior history)
                        // For this report, we assume if it's a valid attendance, it should not be 0
                        // unless it's truly the very first session for the student ever,
                        // but the logic relies on sequencing within the selected period.
                        if ($session_number === 0) {
                             $wrong_sequence = true;
                             break;
                        }
                        
                        if (!$first_valid_found) {
                            // This is the first valid attendance in the selected period - accept it as is
                            $first_valid_found = true;
                            $last_valid_session_number = $session_number;
                        } else {
                            // For subsequent valid attendances, check if they follow the expected sequence
                            $expected_next_session = $last_valid_session_number + 1;
                            
                            // Handle cycling back to 1 if we have lessons_number set
                            if ($lessons_number > 0 && $last_valid_session_number >= $lessons_number) {
                                $expected_next_session = 1;
                            }
                            
                            // If the session number doesn't match what we expect, it's wrong
                            if ($session_number !== $expected_next_session) {
                                $wrong_sequence = true;
                                break;
                            }
                            
                            // Update the last valid session number
                            $last_valid_session_number = $session_number;
                        }
                    }
                }
                
                // If there are no wrong sequences, don't show this student
                if (!$wrong_sequence) {
                    continue;
                }
                
                // If wrong sequence detected, display the student information
                $wrong_sequences_found = true;
                echo '<div class="wrong-sequence-student">';
                echo '<h3>' . esc_html($student->display_name) . ' - ' . esc_html($teacher_name) . ' - ' . esc_html($m_id) . ' - ID: ' . esc_html($student->ID) . ' - عدد الحلقات: ' . esc_html($lessons_number) . '</h3>';
                echo '<table class="wrong-sequence-table">';
                echo '<thead><tr><th>التاريخ</th><th>الحضور</th><th>رقم الحلقة</th></tr></thead>';
                echo '<tbody>';
                
                // Reset tracking variables for display logic
                $display_last_valid_session = null;
                $display_first_valid_found = false;
                
                foreach ($reports as $index => $report) {
                    $session_number = intval($report->session_number);
                    $attendance = $report->attendance;
                    $is_wrong = false;
                    
                    if (in_array($attendance, $zero_attendances)) {
                        $is_wrong = ($session_number !== 0);
                    }
                    else if (in_array($attendance, $non_incrementing_attendances)) {
                        // Should match the last valid session number
                        if ($display_last_valid_session !== null && $session_number !== $display_last_valid_session) {
                            $is_wrong = true;
                        }
                    }
                    else if (in_array($attendance, $valid_attendances)) {
                        // Check if session number is 0
                        if ($session_number === 0) {
                            $is_wrong = true;
                        } else if (!$display_first_valid_found) {
                            // First valid attendance - always accept it
                            $display_first_valid_found = true;
                            $display_last_valid_session = $session_number;
                            $is_wrong = false; // Never mark first valid as wrong
                        } else {
                            // For subsequent valid attendances, check sequence
                            $expected_next_session = $display_last_valid_session + 1;
                            
                            // Handle cycling back to 1 if we have lessons_number set
                            if ($lessons_number > 0 && $display_last_valid_session >= $lessons_number) {
                                $expected_next_session = 1;
                            }
                            
                            if ($session_number !== $expected_next_session) {
                                $is_wrong = true;
                            }
                            
                            // Update tracking variable
                            $display_last_valid_session = $session_number;
                        }
                    }
                    
                    // Output the row with appropriate styling
                    if ($is_wrong) {
                        echo '<tr class="wrong-session">';
                    } else {
                        echo '<tr>';
                    }
                    
                    echo '<td>' . date('Y-m-d', strtotime($report->date)) . '</td>';
                    echo '<td>' . esc_html($attendance) . '</td>';
                    echo '<td class="editable-session" data-report-id="' . esc_attr($report->id) . '" data-original-value="' . esc_attr($session_number) . '">' . esc_html($session_number) . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
                echo '</div>';
            }
        }
        
        // If no wrong sequences were found, display a success message
        if (!$wrong_sequences_found) {
            echo '<div class="success-message"><p>ممتاااز 🤓 مفيش اي غلطات 🥳</p></div>';
        }
    }
    
    $html = ob_get_clean();
    wp_send_json_success(array('html' => $html));
}

/**
 * AJAX handler to update session numbers
 */
function zuwad_update_session_number() {
    // Check nonce for security
    check_ajax_referer('update_session_number_nonce', '_ajax_nonce');
    
    // Get and validate parameters
    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $session_number = isset($_POST['session_number']) ? intval($_POST['session_number']) : -1; // Use -1 to distinguish from valid 0
    
    if ($report_id <= 0) {
        wp_send_json_error('Invalid report ID.');
        return;
    }
    
    // Allow 0 as a valid session number
    if ($session_number < 0) { 
        wp_send_json_error('Invalid session number.');
        return;
    }
    
    global $wpdb;
    
    // Update the session number in the database
    $result = $wpdb->update(
        $wpdb->prefix . 'student_reports',
        array('session_number' => $session_number),
        array('id' => $report_id),
        array('%d'), // format for value
        array('%d')  // format for WHERE clause
    );
    
    if ($result === false) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
        return;
    } else if ($result === 0) {
        // No rows were updated, but the query was successful (maybe the value was the same)
        // Check if the value was actually the same
        $current_value = $wpdb->get_var($wpdb->prepare("SELECT session_number FROM {$wpdb->prefix}student_reports WHERE id = %d", $report_id));
        if ($current_value == $session_number) { // Use loose comparison as DB might return string "0"
             wp_send_json_success(array('message' => 'No changes were made as the value was the same.'));
        } else {
            // This case should ideally not happen if $wpdb->update was successful but affected 0 rows with a different value
            // unless the report_id doesn't exist, but that should be caught by report_id > 0 or result === false.
            wp_send_json_error('No rows updated. Please check report ID or data.');
        }
        return;
    }
    
    wp_send_json_success(array('message' => 'تم تحديث رقم الحلقة بنجاح.'));
}

// Register AJAX handlers for logged-in users
add_action('wp_ajax_load_wrong_sessions_report', 'zuwad_load_wrong_sessions_report');
add_action('wp_ajax_update_session_number', 'zuwad_update_session_number');

if (!function_exists('get_user_meta_batch')) {
    /**
     * Helper function to get user meta for multiple users.
     *
     * @param array $user_ids Array of user IDs.
     * @param string $meta_key The meta key to retrieve.
     * @return array An associative array where keys are user IDs and values are arrays of meta values for that key.
     */
    function get_user_meta_batch($user_ids, $meta_key) {
        global $wpdb;
        $results = array();

        if (empty($user_ids) || !is_array($user_ids) || empty($meta_key)) {
            return $results;
        }

        // Sanitize user IDs to ensure they are positive integers
        $valid_user_ids = array_unique(array_filter(array_map('intval', $user_ids), function($id) { return $id > 0; }));

        if (empty($valid_user_ids)) {
            return $results;
        }
        
        // Initialize results array for all valid user IDs to ensure all keys exist
        foreach ($valid_user_ids as $user_id) {
             $results[$user_id] = array();
        }

        // Create placeholders for the IN clause
        $user_ids_placeholders = implode(',', array_fill(0, count($valid_user_ids), '%d'));
        
        // Prepare arguments for the query. First argument for prepare is $meta_key, rest are user_ids.
        $query_args = $valid_user_ids; // This is an array of integers
        array_unshift($query_args, $meta_key); // $query_args becomes [$meta_key, $id1, $id2, ...]

        $query_sql = $wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN ({$user_ids_placeholders})",
            $query_args // Pass the array of arguments
        );
        
        $meta_data = $wpdb->get_results($query_sql);

        if ($meta_data) {
            foreach ($meta_data as $meta_row) {
                // Ensure user_id from DB is int for array key consistency
                $results[intval($meta_row->user_id)][] = $meta_row->meta_value;
            }
        }
        
        return $results;
    }
}

