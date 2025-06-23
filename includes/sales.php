<?php
// Shortcode for sales dashboard
function zuwad_sales_dashboard_shortcode()
{
    // Check if user is logged in and has sales role
    if (!is_user_logged_in() || !current_user_can('sales')) {
        return;
    }

    global $wpdb;

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

    // Get teachers who have students with payment_status = "معلق"
    $teachers_with_pending_students = $wpdb->get_results(
        "SELECT um.meta_value as teacher_id, COUNT(um.user_id) as pending_count 
     FROM {$wpdb->usermeta} um
     INNER JOIN {$wpdb->usermeta} um2 ON um.user_id = um2.user_id
     WHERE um.meta_key = 'teacher'
     AND um2.meta_key = 'payment_status' 
     AND um2.meta_value = 'معلق'
     GROUP BY um.meta_value"
    );

    // Create a lookup array for teachers with pending students
    $teachers_with_pending_lookup = [];
    foreach ($teachers_with_pending_students as $teacher) {
        $teachers_with_pending_lookup[$teacher->teacher_id] = $teacher->pending_count;
    }

    foreach ($all_teachers as $teacher) {
        // Get teacher metadata
        $teacher_m_id = get_user_meta($teacher->ID, 'm_id', true);
        $teacher_status = get_user_meta($teacher->ID, 'teacher_status', true);
        $teacher_classification = get_user_meta($teacher->ID, 'teacher_classification', true);
        $teacher_phone = get_user_meta($teacher->ID, 'phone', true);
        $teacher_notes = get_user_meta($teacher->ID, 'teacher_notes', true);

        // Calculate number_of_students_calculated for each teacher
        $students_query = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id 
         FROM {$wpdb->usermeta} 
         WHERE meta_key = 'teacher' 
         AND meta_value = %d",
            $teacher->ID
        ));

        $total_minutes = 0;
        $has_pending_students = isset($teachers_with_pending_lookup[$teacher->ID]);
        $pending_count = $has_pending_students ? $teachers_with_pending_lookup[$teacher->ID] : 0;

        foreach ($students_query as $student) {
            $lessons_number = intval(get_user_meta($student->user_id, 'lessons_number', true));
            $lesson_duration = intval(get_user_meta($student->user_id, 'lesson_duration', true));
            $total_minutes += $lessons_number * $lesson_duration;
        }

        $total_hours = $total_minutes / 60;
        $number_of_students_calculated = round($total_hours / 4, 2);

        // Calculate required students based on teacher status
        $required_students = 0;
        if ($teacher_status == 'نشط عدد كامل') {
            $required_students = 18 - $number_of_students_calculated;
        } elseif ($teacher_status == 'نشط نصف عدد') {
            $required_students = 9 - $number_of_students_calculated;
        }

        // Include teachers with required students > 0 OR teachers with pending students
        if ($required_students > 0 || $has_pending_students) {
            $teachers_with_low_students[] = [
                'id' => $teacher->ID,
                'name' => $teacher->display_name,
                'm_id' => $teacher_m_id ?: 'غير متوفر',
                'status' => $teacher_status ?: 'غير متوفر',
                'classification' => $teacher_classification ?: 'غير متوفر',
                'phone' => $teacher_phone ?: 'غير متوفر',
                'calculated_students' => $number_of_students_calculated,
                'required_students' => max(0, round($required_students, 2)),
                'has_pending' => $has_pending_students ? 'نعم' : 'لا',
                'pending_count' => $pending_count,
                'notes' => $teacher_notes ?: ''
            ];
        }
    }
    // Sort teachers by name (Arabic-aware sorting)
    usort($teachers_with_low_students, function ($a, $b) {
        return strcoll($a['name'], $b['name']);
    });
    // Start output buffering
    ob_start();
?>
    <div class="zuwad-container">
        <!-- <h2>المعلمين الذين يحتاجون طلاب إضافيين</h2> -->

        <table id="sales-teachers-table" class="zuwad-table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>اسم المعلم</th>
                    <th>حالة المعلم</th>
                    <th>تصنيف المعلم</th>
                    <th>رقم الهاتف</th>
                    <th>الطلاب</th>
                    <th>المطلوبين</th>
                    <th>معلق</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers_with_low_students)) : ?>
                    <tr>
                        <td colspan="9">لا يوجد معلمين يحتاجون طلاب إضافيين حالياً</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($teachers_with_low_students as $teacher) : ?>
                        <tr>
                            <td><?php echo esc_html($teacher['m_id']); ?></td>
                            <td><?php echo $teacher['has_pending'] === 'نعم' ? '🔎 ' : ''; ?><?php echo esc_html($teacher['name']); ?></td>
                            <td><?php echo esc_html($teacher['status']); ?></td>
                            <td><?php echo esc_html($teacher['classification']); ?></td>
                            <td><?php echo esc_html($teacher['phone']); ?></td>
                            <td><?php echo esc_html($teacher['calculated_students']); ?></td>
                            <td><?php echo esc_html($teacher['required_students']); ?></td>
                            <td><?php echo $teacher['pending_count'] > 0 ? esc_html($teacher['pending_count']) : '0'; ?></td>

                            <td>
                                <div class="teacher-notes"
                                    data-teacher-id="<?php echo esc_attr($teacher['id']); ?>"
                                    data-teacher-name="<?php echo esc_attr($teacher['name']); ?>"
                                    data-notes="<?php echo esc_attr($teacher['notes']); ?>">
                                    <?php echo !empty($teacher['notes']) ? esc_html($teacher['notes']) : ' '; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <style>
        .zuwad-container {
            /* margin: 20px 0; */
            direction: rtl;
            overflow-x: auto;
            /* Enable horizontal scrolling for small screens */
        }

        .zuwad-table {
            width: 100%;
            border-collapse: collapse;
            /* margin-top: 20px; */
            text-align: center;
            background-color: white;
            font-size: 14px;
            table-layout: fixed;
            /* Fixed table layout */
        }

        .zuwad-table th,
        .zuwad-table td {
            border: 1px solid #ddd;
            padding: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Fixed column widths */
        .zuwad-table th:nth-child(1),
        .zuwad-table td:nth-child(1) {
            width: 70px;
            border-radius: 0 10px 0 0;
        }

        /* رقم المعلم */
        .zuwad-table th:nth-child(2),
        .zuwad-table td:nth-child(2) {
            width: 140px;
        }

        /* اسم المعلم */
        .zuwad-table th:nth-child(3),
        .zuwad-table td:nth-child(3) {
            width: 90px;
        }

        /* حالة المعلم */
        .zuwad-table th:nth-child(4),
        .zuwad-table td:nth-child(4) {
            width: 100px;
        }

        /* تصنيف المعلم */
        .zuwad-table th:nth-child(5),
        .zuwad-table td:nth-child(5) {
            width: 100px;
        }

        /* رقم الهاتف */
        .zuwad-table th:nth-child(6),
        .zuwad-table td:nth-child(6) {
            width: 70px;
        }

        /* عدد الطلاب */
        .zuwad-table th:nth-child(7),
        .zuwad-table td:nth-child(7) {
            width: 70px;
        }

        /* الطلاب المطلوبين */
        .zuwad-table th:nth-child(8),
        .zuwad-table td:nth-child(8) {
            width: 80px;
        }

        /* دفعات معلقة */
        .zuwad-table th:nth-child(9),
        .zuwad-table td:nth-child(9) {
            width: 180px;
        }

        /* ملاحظات */

        /* Allow notes column to wrap text */
        .zuwad-table th:nth-child(9),
        .zuwad-table td:nth-child(9) {
            white-space: normal;
            word-wrap: break-word;
            border-radius: 10px 0 0 0;
        }

        .zuwad-table th {
            background-color: #8b0628;
            font-weight: bold;
            color: wheat;
        }

        .zuwad-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .zuwad-table tr:hover {
            background-color: #f1f1f1;
        }

        .teacher-notes {
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            min-height: 20px;
            text-align: right;
        }

        .teacher-notes:hover {
            background-color: #f0f0f0;
        }

        .teacher-notes:empty::after {
            content: 'إضافة ملاحظات...';
            color: #999;
            font-style: italic;
        }

        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .zuwad-table {
                width: 100%;
                table-layout: fixed;
            }

            .zuwad-container {
                overflow-x: auto;
                padding-bottom: 15px;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('.teacher-notes').on('click', function() {
                const teacherId = $(this).data('teacher-id');
                const teacherName = $(this).data('teacher-name');
                const currentNotes = $(this).data('notes');

                Swal.fire({
                    title: 'ملاحظات للمعلم: ' + teacherName,
                    input: 'textarea',
                    inputValue: currentNotes,
                    inputPlaceholder: 'أدخل ملاحظاتك هنا...',
                    showCancelButton: true,
                    confirmButtonText: 'حفظ',
                    cancelButtonText: 'إلغاء',
                    inputAttributes: {
                        'aria-label': 'ملاحظات المعلم',
                        'dir': 'rtl'
                    },
                    preConfirm: (notes) => {
                        return new Promise((resolve) => {
                            // Send AJAX request to save notes
                            $.ajax({
                                url: zuwadPlugin.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'save_teacher_notes',
                                    teacher_id: teacherId,
                                    notes: notes,
                                    nonce: '<?php echo wp_create_nonce('save_teacher_notes_nonce'); ?>'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        resolve(notes);
                                    } else {
                                        Swal.showValidationMessage('فشل في حفظ الملاحظات: ' + response.data);
                                    }
                                },
                                error: function() {
                                    Swal.showValidationMessage('حدث خطأ في الاتصال بالخادم');
                                }
                            });
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Update the notes text in the table without page refresh
                        const $notesElement = $(this);
                        $notesElement.text(result.value || ' ');
                        $notesElement.data('notes', result.value);

                        Swal.fire({
                            title: 'تم الحفظ!',
                            text: 'تم حفظ ملاحظات المعلم بنجاح',
                            icon: 'success',
                            confirmButtonText: 'حسناً'
                        });
                    }
                });
            });
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('sales_dashboard', 'zuwad_sales_dashboard_shortcode');

// AJAX handler for saving teacher notes
function save_teacher_notes()
{
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'save_teacher_notes_nonce')) {
        wp_send_json_error('Security check failed');
    }

    // Check if user has permission
    if (!current_user_can('sales')) {
        wp_send_json_error('Permission denied');
    }

    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

    if (!$teacher_id) {
        wp_send_json_error('Invalid teacher ID');
    }

    // Update teacher notes in user meta
    $result = update_user_meta($teacher_id, 'teacher_notes', $notes);

    if ($result !== false) {
        wp_send_json_success('Notes saved successfully');
    } else {
        wp_send_json_error('Failed to save notes');
    }
}
add_action('wp_ajax_save_teacher_notes', 'save_teacher_notes');
