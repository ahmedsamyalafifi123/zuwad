<?php
// Ensure WordPress is loaded
if (!defined('ABSPATH')) exit;

function zuwad_statistics_shortcode() {
    global $wpdb;

    // Get counts
    $students_count = count(get_users(['role' => 'student']));
    $teachers_count = count(get_users(['role' => 'teacher']));
    $active_students_count = count(get_users([
        'role' => 'student',
        'meta_key' => 'payment_status',
        'meta_value' => 'نشط'
    ]));
    $pending_payment_count = count(get_users([
        'role' => 'student',
        'meta_key' => 'payment_status',
        'meta_value' => 'في انتظار الدفع'
    ]));

    // Start output buffering
    ob_start();
?>
    <div class="zuwad-statistics-container">
        <div class="zuwad-statistics-cards">
            <div class="zuwad-stat-card">
                <h3>إجمالي الطلاب</h3>
                <div class="stat-number"><?php echo $students_count; ?></div>
            </div>
            <div class="zuwad-stat-card">
                <h3>إجمالي المعلمين</h3>
                <div class="stat-number"><?php echo $teachers_count; ?></div>
            </div>
            <div class="zuwad-stat-card">
                <h3>الطلاب النشطين</h3>
                <div class="stat-number"><?php echo $active_students_count; ?></div>
            </div>
            <div class="zuwad-stat-card">
                <h3>في انتظار الدفع</h3>
                <div class="stat-number"><?php echo $pending_payment_count; ?></div>
            </div>
        </div>

        <div class="zuwad-search-container">
            <input type="text" id="zuwad-search-input" placeholder="ابحث عن طالب أو معلم...">
            <select id="zuwad-filter-type">
                <option value="all">الكل</option>
                <option value="student">الطلاب</option>
                <option value="teacher">المعلمين</option>
            </select>
            <select id="zuwad-filter-status">
                <option value="all">جميع الحالات</option>
                <option value="نشط">نشط</option>
                <option value="في انتظار الدفع">في انتظار الدفع</option>
                <option value="متوقف">متوقف</option>
            </select>
            <button id="zuwad-search-button">بحث</button>
        </div>

        <div id="zuwad-data-table-container">
            <table id="zuwad-data-table">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>الاسم</th>
                        <th>النوع</th>
                        <th>المعلم</th>
                        <th>الهاتف</th>
                        <th>البلد</th>
                        <th>الحصص</th>
                        <th>المدة</th>
                        <th>العملة</th>
                        <th>المبلغ</th>
                        <th>حالة الدفع</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
            <div id="zuwad-pagination">
                <button id="zuwad-prev-page" disabled>السابق</button>
                <span id="zuwad-page-info">صفحة 1 من 1</span>
                <button id="zuwad-next-page" disabled>التالي</button>
            </div>
        </div>
    </div>

    <style>
        .zuwad-statistics-container {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            margin: 20px 0;
        }
        
        .zuwad-statistics-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .zuwad-stat-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 200px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .zuwad-stat-card:hover {
            transform: translateY(-5px);
        }
        
        .zuwad-stat-card h3 {
            margin-top: 0;
            color: #333;
            font-size: 18px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #007bff;
        }
        
        .zuwad-search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        #zuwad-search-input {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        #zuwad-filter-type, #zuwad-filter-status {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        #zuwad-search-button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        #zuwad-search-button:hover {
            background-color: #0056b3;
        }
        
        #zuwad-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        #zuwad-data-table th, #zuwad-data-table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        
        #zuwad-data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        #zuwad-data-table tbody tr:hover {
            background-color: #f1f1f1;
        }
        
        #zuwad-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        #zuwad-prev-page, #zuwad-next-page {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        #zuwad-prev-page:disabled, #zuwad-next-page:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        #zuwad-page-info {
            font-size: 14px;
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('zuwad_statistics', 'zuwad_statistics_shortcode');

// AJAX handler to fetch users data
function zuwad_fetch_users_data() {
    // Check nonce for security
    check_ajax_referer('zuwad_statistics_nonce', 'nonce');
    
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $user_type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'all';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
    
    // Build query args
    $args = [
        'number' => $per_page,
        'paged' => $page,
        'search' => '*' . $search_term . '*',
        'search_columns' => ['display_name', 'user_email'],
        'fields' => 'all_with_meta'
    ];
    
    // Add role filter
    if ($user_type !== 'all') {
        $args['role'] = $user_type;
    } else {
        $args['role__in'] = ['student', 'teacher'];
    }
    
    // Add meta query for status if needed
    if ($status !== 'all') {
        $args['meta_query'] = [
            [
                'key' => 'payment_status',
                'value' => $status,
                'compare' => '='
            ]
        ];
    }
    
    // Get total users count for pagination
    $count_args = $args;
    $count_args['fields'] = 'ids';
    $count_args['number'] = -1; // Get all for counting
    $count_args['paged'] = 1;
    $total_users = count(get_users($count_args));
    
    // Get users
    $users = get_users($args);
    
    $data = [];
    
    foreach ($users as $user) {
        $user_id = $user->ID;
        $user_roles = $user->roles;
        $is_student = in_array('student', $user_roles);
        $is_teacher = in_array('teacher', $user_roles);
        
        $user_data = [
            'm_id' => get_user_meta($user_id, 'm_id', true),
            'name' => $user->display_name,
            'type' => $is_student ? 'طالب' : ($is_teacher ? 'معلم' : 'أخرى'),
            'email' => $user->user_email,
            'phone' => get_user_meta($user_id, 'phone', true) ?: get_user_meta($user_id, 'payment_phone', true),
            'country' => get_user_meta($user_id, 'country', true),
            'payment_status' => get_user_meta($user_id, 'payment_status', true),
            'notes' => get_user_meta($user_id, 'notes', true)
        ];
        
        // Add student-specific data
        if ($is_student) {
            $teacher_id = get_user_meta($user_id, 'teacher', true);
            $user_data['teacher'] = $teacher_id ? get_userdata($teacher_id)->display_name : 'غير معين';
            $user_data['lessons_number'] = get_user_meta($user_id, 'lessons_number', true);
            $user_data['lesson_duration'] = get_user_meta($user_id, 'lesson_duration', true);
            $user_data['currency'] = get_user_meta($user_id, 'currency', true);
            $user_data['amount'] = get_user_meta($user_id, 'amount', true);
        } else {
            $user_data['teacher'] = '-';
            $user_data['lessons_number'] = '-';
            $user_data['lesson_duration'] = '-';
            $user_data['currency'] = '-';
            $user_data['amount'] = '-';
        }
        
        $data[] = $user_data;
    }
    
    $response = [
        'success' => true,
        'data' => $data,
        'total' => $total_users,
        'pages' => ceil($total_users / $per_page),
        'current_page' => $page
    ];
    
    wp_send_json($response);
}
add_action('wp_ajax_zuwad_fetch_users_data', 'zuwad_fetch_users_data');
add_action('wp_ajax_nopriv_zuwad_fetch_users_data', 'zuwad_fetch_users_data');
