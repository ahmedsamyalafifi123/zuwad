<?php
// Ensure WordPress is loaded
if (!defined('ABSPATH')) exit;

/**
 * Creates a shortcode to display a dashboard with student statistics and a searchable, paginated table
 */
function zuwad_student_dashboard_shortcode()
{
    global $wpdb;

    // Get current user
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;

    // Check user roles
    $is_administrator = current_user_can('administrator');
    $is_supervisor = in_array('supervisor', $current_user->roles);
    $is_teacher = in_array('teacher', $current_user->roles);
    $is_kpi = in_array('KPI', $current_user->roles);
    $is_accountant = in_array('Accountant', $current_user->roles);
    $is_sales = in_array('sales', $current_user->roles);

    // Start output buffering
    ob_start();

    // Calculate statistics
    $total_students = count(get_users(['role' => 'student', 'fields' => 'ID']));
    $total_teachers = count(get_users(['role' => 'teacher', 'fields' => 'ID']));
    $total_active_students = count(get_users([
        'role' => 'student',
        'meta_key' => 'payment_status',
        'meta_value' => 'نشط',
        'fields' => 'ID'
    ]));
    $total_pending_payment = count(get_users([
        'role' => 'student',
        'meta_key' => 'payment_status',
        'meta_value' => 'في انتظار الدفع',
        'fields' => 'ID'
    ]));
    $total_inactive_students = count(get_users([
        'role' => 'student',
        'meta_key' => 'payment_status',
        'meta_value' => 'متوقف',
        'fields' => 'ID'
    ]));

    // Get active teachers count
    $active_teachers = count(get_users([
        'role' => 'teacher',
        'meta_key' => 'teacher_status',
        'meta_value' => 'نشط عدد كامل',
        'fields' => 'ID'
    ]));

    // Get part-time teachers count
    $part_time_teachers = count(get_users([
        'role' => 'teacher',
        'meta_key' => 'teacher_status',
        'meta_value' => 'نشط نصف عدد',
        'fields' => 'ID'
    ]));

    // Get total countries count
    $countries_count = $wpdb->get_var("
        SELECT COUNT(DISTINCT meta_value) 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'country' 
        AND meta_value != ''
    ");

    // Calculate total lessons completed
    $total_lessons_completed = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}student_reports
    ");

    // Get current month and year for enhanced analytics
    $current_month = date('Y-m');
    $current_year = date('Y');

    // Calculate payment statistics (track "نشط" status changes)
    $total_revenue_this_month = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(um_amount.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->usermeta} um_status
        JOIN {$wpdb->usermeta} um_amount ON um_status.user_id = um_amount.user_id
        JOIN {$wpdb->usermeta} um_change_date ON um_status.user_id = um_change_date.user_id
        WHERE um_status.meta_key = 'payment_status'
        AND um_status.meta_value = 'نشط'
        AND um_amount.meta_key = 'amount'
        AND um_change_date.meta_key = 'payment_status_change_date'
        AND DATE_FORMAT(um_change_date.meta_value, '%%Y-%%m') = %s
    ", $current_month));

    $total_revenue_today = $wpdb->get_var("
        SELECT SUM(CAST(um_amount.meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->usermeta} um_status
        JOIN {$wpdb->usermeta} um_amount ON um_status.user_id = um_amount.user_id
        JOIN {$wpdb->usermeta} um_change_date ON um_status.user_id = um_change_date.user_id
        WHERE um_status.meta_key = 'payment_status'
        AND um_status.meta_value = 'نشط'
        AND um_amount.meta_key = 'amount'
        AND um_change_date.meta_key = 'payment_status_change_date'
        AND DATE(um_change_date.meta_value) = CURDATE()
    ");

    // Calculate lessons statistics for current month
    $lessons_this_month = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}student_reports
        WHERE DATE_FORMAT(date, '%%Y-%%m') = %s
        AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
    ", $current_month));

    $lessons_today = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}student_reports
        WHERE DATE(date) = CURDATE()
        AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
    ");

    // Calculate first lessons (session number = 1) statistics
    $first_lessons_this_month = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}student_reports
        WHERE DATE_FORMAT(date, '%%Y-%%m') = %s
        AND session_number = 1
        AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
    ", $current_month));

    $first_lessons_today = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->prefix}student_reports
        WHERE DATE(date) = CURDATE()
        AND session_number = 1
        AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
    ");

    // HTML for the dashboard
?>
    <div class="zuwad-dashboard">
        <h2 style="text-align: center;">لوحة معلومات الطلاب</h2>

        <!-- Statistics Cards -->
        <div class="zuwad-stats-container">
            <div class="zuwad-stat-card">
                <div class="zuwad-stat-icon">👨‍🎓</div>
                <div class="zuwad-stat-value"><?php echo $total_students; ?></div>
                <div class="zuwad-stat-label">إجمالي الطلاب</div>
            </div>

            <div class="zuwad-stat-card active">
                <div class="zuwad-stat-icon">✅</div>
                <div class="zuwad-stat-value"><?php echo $total_active_students; ?></div>
                <div class="zuwad-stat-label">الطلاب النشطين</div>
            </div>

            <div class="zuwad-stat-card pending">
                <div class="zuwad-stat-icon">⏳</div>
                <div class="zuwad-stat-value"><?php echo $total_pending_payment; ?></div>
                <div class="zuwad-stat-label">في انتظار الدفع</div>
            </div>

            <div class="zuwad-stat-card inactive">
                <div class="zuwad-stat-icon">🛑</div>
                <div class="zuwad-stat-value"><?php echo $total_inactive_students; ?></div>
                <div class="zuwad-stat-label">الطلاب المتوقفين</div>
            </div>

            <div class="zuwad-stat-card teachers">
                <div class="zuwad-stat-icon">👨‍🏫</div>
                <div class="zuwad-stat-value"><?php echo $total_teachers; ?></div>
                <div class="zuwad-stat-label">إجمالي المعلمين</div>
            </div>

            <div class="zuwad-stat-card teachers">
                <div class="zuwad-stat-icon">👨‍💼</div>
                <div class="zuwad-stat-value"><?php echo $active_teachers; ?></div>
                <div class="zuwad-stat-label">معلمين دوام كامل</div>
            </div>

            <div class="zuwad-stat-card teachers">
                <div class="zuwad-stat-icon">👨‍💻</div>
                <div class="zuwad-stat-value"><?php echo $part_time_teachers; ?></div>
                <div class="zuwad-stat-label">معلمين دوام جزئي</div>
            </div>

            <div class="zuwad-stat-card countries">
                <div class="zuwad-stat-icon">🌍</div>
                <div class="zuwad-stat-value"><?php echo $countries_count; ?></div>
                <div class="zuwad-stat-label">عدد الدول</div>
            </div>

            <div class="zuwad-stat-card active">
                <div class="zuwad-stat-icon">📚</div>
                <div class="zuwad-stat-value"><?php echo $total_lessons_completed; ?></div>
                <div class="zuwad-stat-label">الحصص المكتملة</div>
            </div>
        </div>

        <!-- Enhanced Analytics Section -->
        <div class="zuwad-analytics-section">
            <div class="analytics-header">
                <h3 style="color: white; margin: 0;">📊 تحليلات مفصلة</h3>
                <div class="month-selector">
                    <select id="analytics-month-selector">
                        <option value="current">الشهر الحالي</option>
                        <option value="all">جميع الأشهر</option>
                        <?php
                        // Generate last 12 months options
                        for ($i = 1; $i <= 12; $i++) {
                            $month_date = date('Y-m', strtotime("-$i months"));
                            $month_name = date('F Y', strtotime("-$i months"));

                            // Convert month names to Arabic
                            $arabic_months = array(
                                'January' => 'يناير', 'February' => 'فبراير', 'March' => 'مارس',
                                'April' => 'أبريل', 'May' => 'مايو', 'June' => 'يونيو',
                                'July' => 'يوليو', 'August' => 'أغسطس', 'September' => 'سبتمبر',
                                'October' => 'أكتوبر', 'November' => 'نوفمبر', 'December' => 'ديسمبر'
                            );

                            $english_month = date('F', strtotime("-$i months"));
                            $year = date('Y', strtotime("-$i months"));
                            $arabic_month_name = $arabic_months[$english_month] . ' ' . $year;

                            echo '<option value="' . $month_date . '">' . $arabic_month_name . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Payment Analytics Cards -->
            <div class="zuwad-payment-analytics">
                <div class="analytics-card revenue-today clickable-card" data-type="revenue-today">
                    <div class="analytics-icon">💰</div>
                    <div class="analytics-value"><?php echo number_format($total_revenue_today ?: 0, 0); ?> ج.م</div>
                    <div class="analytics-label">إيرادات اليوم (نشط)</div>
                    <div class="click-hint">انقر للتفاصيل</div>
                </div>

                <div class="analytics-card revenue-month clickable-card" data-type="revenue-month">
                    <div class="analytics-icon">📈</div>
                    <div class="analytics-value"><?php echo number_format($total_revenue_this_month ?: 0, 0); ?> ج.م</div>
                    <div class="analytics-label">إيرادات الشهر (نشط)</div>
                    <div class="click-hint">انقر للتفاصيل</div>
                </div>

                <div class="analytics-card lessons-today clickable-card" data-type="lessons-today">
                    <div class="analytics-icon">📚</div>
                    <div class="analytics-value"><?php echo $lessons_today; ?></div>
                    <div class="analytics-label">حصص اليوم</div>
                    <div class="click-hint">انقر للتفاصيل</div>
                </div>

                <div class="analytics-card lessons-month clickable-card" data-type="lessons-month">
                    <div class="analytics-icon">📊</div>
                    <div class="analytics-value"><?php echo $lessons_this_month; ?></div>
                    <div class="analytics-label">حصص الشهر</div>
                    <div class="click-hint">انقر للتفاصيل</div>
                </div>

                <div class="analytics-card first-lessons-today clickable-card" data-type="first-lessons-today">
                    <div class="analytics-icon">🎯</div>
                    <div class="analytics-value"><?php echo $first_lessons_today; ?></div>
                    <div class="analytics-label">طلاب جدد اليوم</div>
                    <div class="click-hint">انقر للتفاصيل</div>
                </div>

                <div class="analytics-card first-lessons-month clickable-card" data-type="first-lessons-month">
                    <div class="analytics-icon">🌟</div>
                    <div class="analytics-value"><?php echo $first_lessons_this_month; ?></div>
                    <div class="analytics-label">طلاب جدد الشهر</div>
                    <div class="click-hint">انقر للتفاصيل</div>
                </div>
            </div>

            <!-- Charts Container -->
            <div class="zuwad-charts-container">
                <div class="chart-section">
                    <div class="chart-card">
                        <h4>📈 إيرادات الشهر الحالي (يومياً)</h4>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h4>📚 الحصص المكتملة هذا الشهر</h4>
                        <div class="chart-container">
                            <canvas id="lessonsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="chart-section">
                    <div class="chart-card">
                        <h4>🎯 الطلاب الجدد (الحصة الأولى)</h4>
                        <div class="chart-container">
                            <canvas id="firstLessonsChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h4>💳 توزيع حالات الدفع</h4>
                        <div class="chart-container">
                            <canvas id="paymentStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="chart-section">
                    <div class="chart-card">
                        <h4>🌍 توزيع الطلاب حسب الدول</h4>
                        <div class="chart-container">
                            <canvas id="countriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="zuwad-search-container">
            <input type="text" id="zuwad-search-input" placeholder="البحث عن طالب أو معلم...">

            <div class="zuwad-filters">
                <select id="zuwad-role-filter">
                    <option value="">جميع الأدوار</option>
                    <option value="student">الطلاب</option>
                    <option value="teacher">المعلمين</option>
                    <option value="supervisor">المشرفين</option>
                    <option value="KPI">مراقبي الأداء</option>
                    <option value="Accountant">محاسب</option>
                    <option value="sales">المبيعات</option>
                </select>

                <select id="zuwad-status-filter">
                    <option value="">جميع الحالات</option>
                    <option value="نشط">نشط</option>
                    <option value="في انتظار الدفع">في انتظار الدفع</option>
                    <option value="متوقف">متوقف</option>
                </select>

                <select id="zuwad-country-filter">
                    <option value="">جميع الدول</option>
                    <?php
                    // Get all countries
                    $countries = $wpdb->get_col("
                        SELECT DISTINCT meta_value 
                        FROM {$wpdb->usermeta} 
                        WHERE meta_key = 'country' 
                        AND meta_value != ''
                        ORDER BY meta_value ASC
                    ");

                    foreach ($countries as $country) {
                        echo '<option value="' . esc_attr($country) . '">' . esc_html($country) . '</option>';
                    }
                    ?>
                </select>

                <button id="zuwad-export-btn" class="zuwad-export-btn">تصدير البيانات</button>
            </div>
        </div>

        <!-- Table Container -->
        <div class="zuwad-table-container">
            <table id="zuwad-users-table">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>الاسم</th>
                        <th>الدور</th>
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
                    <!-- Table data will be loaded via AJAX -->
                    <tr>
                        <td colspan="12" class="zuwad-loading">جاري تحميل البيانات...</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="zuwad-pagination">
                <button id="zuwad-prev-page" disabled>&lt; السابق</button>
                <span id="zuwad-page-info">صفحة <span id="zuwad-current-page">1</span> من <span id="zuwad-total-pages">1</span></span>
                <button id="zuwad-next-page">التالي &gt;</button>
            </div>
        </div>
    </div>

    <style>
        .zuwad-dashboard {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            max-width: 100%;
            margin: 0 auto;
        }

        .zuwad-stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 30px;
        }

        .zuwad-stat-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            min-width: 200px;
            transition: transform 0.3s ease;
        }

        .zuwad-stat-card:hover {
            transform: translateY(-5px);
        }

        .zuwad-stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .zuwad-stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #3498db;
        }

        .zuwad-stat-label {
            font-size: 1rem;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .zuwad-stat-card.active {
            background-color: #c6efce;
        }

        .zuwad-stat-card.pending {
            background-color: #fbe4ec;
        }

        .zuwad-stat-card.inactive {
            background-color: #f9d2c4;
        }

        .zuwad-stat-card.teachers {
            background-color: #f7f7f7;
        }

        .zuwad-stat-card.countries {
            background-color: #e5e5e5;
        }

        /* Enhanced Analytics Section */
        .zuwad-analytics-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            color: white;
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .month-selector select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 10px 15px;
            color: white;
            font-size: 14px;
            min-width: 180px;
            backdrop-filter: blur(10px);
        }

        .month-selector select option {
            background: #2c3e50;
            color: white;
        }

        .month-selector select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
        }

        .zuwad-payment-analytics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .analytics-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .analytics-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .analytics-value {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .analytics-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .clickable-card {
            cursor: pointer;
            position: relative;
        }

        .clickable-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .click-hint {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
            font-style: italic;
        }

        .zuwad-charts-container {
            display: grid;
            gap: 30px;
        }

        .chart-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            color: #2c3e50;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 350px; /* Fixed height to prevent overflow */
        }

        .chart-card h4 {
            margin: 0 0 15px 0;
            text-align: center;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .chart-container {
            position: relative;
            height: 280px; /* Fixed container height */
            width: 100%;
        }

        .chart-card canvas {
            max-height: 280px !important; /* Limit canvas height */
            width: 100% !important;
        }

        .zuwad-search-container {
            margin-bottom: 20px;
        }

        #zuwad-search-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .zuwad-filters {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .zuwad-filters select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            min-width: 150px;
        }

        .zuwad-export-btn {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #3498db;
            color: #fff;
            cursor: pointer;
        }

        .zuwad-table-container {
            overflow-x: auto;
        }

        #zuwad-users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        #zuwad-users-table th,
        #zuwad-users-table td {
            padding: 12px 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        #zuwad-users-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        #zuwad-users-table tbody tr:hover {
            background-color: #f1f9ff;
        }

        .zuwad-loading {
            text-align: center;
            padding: 30px !important;
            color: #7f8c8d;
        }

        .zuwad-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 15px;
        }

        .zuwad-pagination button {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .zuwad-pagination button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        #zuwad-page-info {
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .zuwad-stat-card {
                min-width: 120px;
                padding: 15px;
            }

            .zuwad-stat-icon {
                font-size: 2rem;
            }

            .zuwad-stat-value {
                font-size: 1.5rem;
            }

            #zuwad-users-table {
                font-size: 14px;
            }

            #zuwad-users-table th,
            #zuwad-users-table td {
                padding: 8px 10px;
            }

            /* Analytics responsive */
            .zuwad-analytics-section {
                padding: 20px;
                margin: 20px 0;
            }

            .zuwad-payment-analytics {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .analytics-card {
                padding: 15px;
            }

            .analytics-icon {
                font-size: 2rem;
            }

            .analytics-value {
                font-size: 1.4rem;
            }

            .chart-section {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .chart-card {
                padding: 15px;
                height: 300px; /* Smaller height on mobile */
            }

            .chart-container {
                height: 220px; /* Smaller container on mobile */
            }

            .chart-card h4 {
                font-size: 1rem;
            }

            .analytics-header {
                flex-direction: column;
                text-align: center;
            }

            .month-selector select {
                min-width: 150px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .zuwad-payment-analytics {
                grid-template-columns: 1fr;
            }

            .analytics-value {
                font-size: 1.2rem;
            }

            .analytics-label {
                font-size: 0.8rem;
            }
        }

        .status-active {
            color: #2ecc71;
        }

        .status-pending {
            color: #e67e73;
        }

        .status-inactive {
            color: #e74c3c;
        }
    </style>

<?php
    return ob_get_clean();
}
add_shortcode('student_dashboard', 'zuwad_student_dashboard_shortcode');

// AJAX handler to fetch users data
function zuwad_fetch_users_data()
{
    global $wpdb;

    // Debug log
    error_log('zuwad_fetch_users_data called');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    // Get parameters
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
    $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;

    // Debug log
    error_log('Search parameters: ' . json_encode([
        'search' => $search,
        'role' => $role,
        'status' => $status,
        'country' => $country,
        'page' => $page,
        'per_page' => $per_page
    ]));

    // Calculate offset
    $offset = ($page - 1) * $per_page;

    // Build query
    $query_args = array(
        'number' => $per_page,
        'offset' => $offset,
        'orderby' => 'display_name',
        'order' => 'ASC',
        'fields' => 'all'
    );

    // Add role filter
    if (!empty($role)) {
        $query_args['role'] = $role;
    }

    // Add meta query for filters
    $meta_query = array();

    // Add payment status filter
    if (!empty($status)) {
        $meta_query[] = array(
            'key' => 'payment_status',
            'value' => $status,
            'compare' => '='
        );
    }

    // Add country filter
    if (!empty($country)) {
        $meta_query[] = array(
            'key' => 'country',
            'value' => $country,
            'compare' => '='
        );
    }

    // Add search filter
    if (!empty($search)) {
        $query_args['search'] = '*' . $search . '*';
    }

    // Apply meta query if any filters are set
    if (!empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    // Debug log
    error_log('Query args: ' . json_encode($query_args));

    // Get users
    $users = get_users($query_args);

    // Debug log
    error_log('Found ' . count($users) . ' users');

    // Get total users count for pagination
    $count_args = $query_args;
    unset($count_args['number']);
    unset($count_args['offset']);
    $count_args['fields'] = 'ids';
    $total_users = count(get_users($count_args));
    $total_pages = ceil($total_users / $per_page);

    // Prepare the data for the table
    $users_data = array();
    foreach ($users as $user) {
        $user_id = $user->ID;
        $teacher_id = get_user_meta($user_id, 'teacher', true);

        $payment_status = get_user_meta($user_id, 'payment_status', true);
        $status_class = '';
        if ($payment_status === 'نشط') {
            $status_class = 'status-active';
        } elseif ($payment_status === 'في انتظار الدفع') {
            $status_class = 'status-pending';
        } elseif ($payment_status === 'متوقف') {
            $status_class = 'status-inactive';
        }

        $user_data = array(
            'm_id' => get_user_meta($user_id, 'm_id', true),
            'name' => $user->display_name,
            'role' => implode(', ', $user->roles),
            'teacher' => $teacher_id ? get_userdata($teacher_id)->display_name : '',
            'phone' => get_user_meta($user_id, 'payment_phone', true),
            'country' => get_user_meta($user_id, 'country', true),
            'lessons_number' => get_user_meta($user_id, 'lessons_number', true),
            'lesson_duration' => get_user_meta($user_id, 'lesson_duration', true),
            'currency' => get_user_meta($user_id, 'currency', true),
            'amount' => get_user_meta($user_id, 'amount', true),
            'payment_status' => $payment_status,
            'status_class' => $status_class,
            'notes' => get_user_meta($user_id, 'notes', true)
        );

        $users_data[] = $user_data;
    }

    // Return the data
    $response = array(
        'users' => $users_data,
        'total' => $total_users,
        'total_pages' => $total_pages,
        'page' => $page
    );

    // Debug log
    error_log('Response: ' . json_encode($response));

    wp_send_json_success($response);
}
add_action('wp_ajax_zuwad_fetch_users_data', 'zuwad_fetch_users_data');
add_action('wp_ajax_nopriv_zuwad_fetch_users_data', 'zuwad_fetch_users_data');

// AJAX handler for revenue chart data
function zuwad_analytics_fetch_revenue_chart_data() {
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    $selected_month = isset($_POST['selected_month']) ? sanitize_text_field($_POST['selected_month']) : 'current';

    if ($selected_month === 'all') {
        // Show all months comparison
        $revenue_data = array();
        $labels = array();

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month_date = date('Y-m', strtotime("-$i months"));
            $month_name = date('M Y', strtotime("-$i months"));
            $labels[] = $month_name;

            $monthly_revenue = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(CAST(um_amount.meta_value AS DECIMAL(10,2)))
                FROM {$wpdb->usermeta} um_status
                JOIN {$wpdb->usermeta} um_amount ON um_status.user_id = um_amount.user_id
                JOIN {$wpdb->usermeta} um_change_date ON um_status.user_id = um_change_date.user_id
                WHERE um_status.meta_key = 'payment_status'
                AND um_status.meta_value = 'نشط'
                AND um_amount.meta_key = 'amount'
                AND um_change_date.meta_key = 'payment_status_change_date'
                AND DATE_FORMAT(um_change_date.meta_value, '%%Y-%%m') = %s
            ", $month_date));

            $revenue_data[] = floatval($monthly_revenue ?: 0);
        }
    } else {
        // Show daily data for specific month
        $current_month = ($selected_month === 'current') ? date('Y-m') : $selected_month;
        $days_in_month = date('t', strtotime($current_month . '-01'));

        $revenue_data = array();
        $labels = array();

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%s-%02d', $current_month, $day);
            $labels[] = $day;

            $daily_revenue = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(CAST(um_amount.meta_value AS DECIMAL(10,2)))
                FROM {$wpdb->usermeta} um_status
                JOIN {$wpdb->usermeta} um_amount ON um_status.user_id = um_amount.user_id
                JOIN {$wpdb->usermeta} um_change_date ON um_status.user_id = um_change_date.user_id
                WHERE um_status.meta_key = 'payment_status'
                AND um_status.meta_value = 'نشط'
                AND um_amount.meta_key = 'amount'
                AND um_change_date.meta_key = 'payment_status_change_date'
                AND DATE(um_change_date.meta_value) = %s
            ", $date));

            $revenue_data[] = floatval($daily_revenue ?: 0);
        }
    }

    wp_send_json_success(array(
        'labels' => $labels,
        'data' => $revenue_data,
        'chart_type' => $selected_month === 'all' ? 'monthly' : 'daily'
    ));
}
add_action('wp_ajax_zuwad_analytics_fetch_revenue_chart_data', 'zuwad_analytics_fetch_revenue_chart_data');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_revenue_chart_data', 'zuwad_analytics_fetch_revenue_chart_data');

// AJAX handler for lessons chart data
function zuwad_analytics_fetch_lessons_chart_data() {
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    $selected_month = isset($_POST['selected_month']) ? sanitize_text_field($_POST['selected_month']) : 'current';

    if ($selected_month === 'all') {
        // Show all months comparison
        $lessons_data = array();
        $labels = array();

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month_date = date('Y-m', strtotime("-$i months"));
            $month_name = date('M Y', strtotime("-$i months"));
            $labels[] = $month_name;

            $monthly_lessons = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}student_reports
                WHERE DATE_FORMAT(date, '%%Y-%%m') = %s
                AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
            ", $month_date));

            $lessons_data[] = intval($monthly_lessons ?: 0);
        }
    } else {
        // Show daily data for specific month
        $current_month = ($selected_month === 'current') ? date('Y-m') : $selected_month;
        $days_in_month = date('t', strtotime($current_month . '-01'));

        $lessons_data = array();
        $labels = array();

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%s-%02d', $current_month, $day);
            $labels[] = $day;

            $daily_lessons = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}student_reports
                WHERE DATE(date) = %s
                AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
            ", $date));

            $lessons_data[] = intval($daily_lessons ?: 0);
        }
    }

    wp_send_json_success(array(
        'labels' => $labels,
        'data' => $lessons_data,
        'chart_type' => $selected_month === 'all' ? 'monthly' : 'daily'
    ));
}
add_action('wp_ajax_zuwad_analytics_fetch_lessons_chart_data', 'zuwad_analytics_fetch_lessons_chart_data');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_lessons_chart_data', 'zuwad_analytics_fetch_lessons_chart_data');

// AJAX handler for first lessons chart data
function zuwad_analytics_fetch_first_lessons_chart_data() {
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    $selected_month = isset($_POST['selected_month']) ? sanitize_text_field($_POST['selected_month']) : 'current';

    if ($selected_month === 'all') {
        // Show all months comparison
        $first_lessons_data = array();
        $labels = array();

        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month_date = date('Y-m', strtotime("-$i months"));
            $month_name = date('M Y', strtotime("-$i months"));
            $labels[] = $month_name;

            $monthly_first_lessons = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}student_reports
                WHERE DATE_FORMAT(date, '%%Y-%%m') = %s
                AND session_number = 1
                AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
            ", $month_date));

            $first_lessons_data[] = intval($monthly_first_lessons ?: 0);
        }
    } else {
        // Show daily data for specific month
        $current_month = ($selected_month === 'current') ? date('Y-m') : $selected_month;
        $days_in_month = date('t', strtotime($current_month . '-01'));

        $first_lessons_data = array();
        $labels = array();

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%s-%02d', $current_month, $day);
            $labels[] = $day;

            $daily_first_lessons = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}student_reports
                WHERE DATE(date) = %s
                AND session_number = 1
                AND attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
            ", $date));

            $first_lessons_data[] = intval($daily_first_lessons ?: 0);
        }
    }

    wp_send_json_success(array(
        'labels' => $labels,
        'data' => $first_lessons_data,
        'chart_type' => $selected_month === 'all' ? 'monthly' : 'daily'
    ));
}
add_action('wp_ajax_zuwad_analytics_fetch_first_lessons_chart_data', 'zuwad_analytics_fetch_first_lessons_chart_data');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_first_lessons_chart_data', 'zuwad_analytics_fetch_first_lessons_chart_data');

// AJAX handler for payment status chart data
function zuwad_analytics_fetch_payment_status_chart_data() {
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    $payment_statuses = array('نشط', 'في انتظار الدفع', 'متوقف', 'مدفوع', 'ملغي');
    $status_data = array();
    $labels = array();

    foreach ($payment_statuses as $status) {
        $count = count(get_users([
            'role' => 'student',
            'meta_key' => 'payment_status',
            'meta_value' => $status,
            'fields' => 'ID'
        ]));

        if ($count > 0) {
            $labels[] = $status;
            $status_data[] = $count;
        }
    }

    wp_send_json_success(array(
        'labels' => $labels,
        'data' => $status_data
    ));
}
add_action('wp_ajax_zuwad_analytics_fetch_payment_status_chart_data', 'zuwad_analytics_fetch_payment_status_chart_data');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_payment_status_chart_data', 'zuwad_analytics_fetch_payment_status_chart_data');

// AJAX handler for countries chart data
function zuwad_analytics_fetch_countries_chart_data() {
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    $countries_data = $wpdb->get_results("
        SELECT um.meta_value as country, COUNT(*) as count
        FROM {$wpdb->usermeta} um
        JOIN {$wpdb->usermeta} um2 ON um.user_id = um2.user_id
        WHERE um.meta_key = 'country'
        AND um.meta_value != ''
        AND um2.meta_key = 'wp_capabilities'
        AND um2.meta_value LIKE '%student%'
        GROUP BY um.meta_value
        ORDER BY count DESC
        LIMIT 10
    ");

    $labels = array();
    $data = array();

    foreach ($countries_data as $country) {
        $labels[] = $country->country;
        $data[] = intval($country->count);
    }

    wp_send_json_success(array(
        'labels' => $labels,
        'data' => $data
    ));
}
add_action('wp_ajax_zuwad_analytics_fetch_countries_chart_data', 'zuwad_analytics_fetch_countries_chart_data');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_countries_chart_data', 'zuwad_analytics_fetch_countries_chart_data');

// AJAX handler for revenue breakdown by currency
function zuwad_analytics_fetch_revenue_breakdown() {
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    $type = sanitize_text_field($_POST['breakdown_type']);
    $date_condition = '';

    if ($type === 'today') {
        $date_condition = "AND DATE(um_change_date.meta_value) = CURDATE()";
    } elseif ($type === 'month') {
        $current_month = date('Y-m');
        $date_condition = $wpdb->prepare("AND DATE_FORMAT(um_change_date.meta_value, '%%Y-%%m') = %s", $current_month);
    }

    $revenue_by_currency = $wpdb->get_results("
        SELECT
            um_currency.meta_value as currency,
            SUM(CAST(um_amount.meta_value AS DECIMAL(10,2))) as total_amount,
            COUNT(*) as student_count
        FROM {$wpdb->usermeta} um_status
        JOIN {$wpdb->usermeta} um_amount ON um_status.user_id = um_amount.user_id
        JOIN {$wpdb->usermeta} um_currency ON um_status.user_id = um_currency.user_id
        JOIN {$wpdb->usermeta} um_change_date ON um_status.user_id = um_change_date.user_id
        WHERE um_status.meta_key = 'payment_status'
        AND um_status.meta_value = 'نشط'
        AND um_amount.meta_key = 'amount'
        AND um_currency.meta_key = 'currency'
        AND um_change_date.meta_key = 'payment_status_change_date'
        {$date_condition}
        GROUP BY um_currency.meta_value
        ORDER BY total_amount DESC
    ");

    $breakdown = array();
    $total_revenue = 0;

    foreach ($revenue_by_currency as $item) {
        $breakdown[] = array(
            'currency' => $item->currency ?: 'غير محدد',
            'amount' => floatval($item->total_amount),
            'student_count' => intval($item->student_count),
            'formatted_amount' => number_format($item->total_amount, 0) . ' ' . ($item->currency ?: 'غير محدد')
        );
        $total_revenue += floatval($item->total_amount);
    }

    wp_send_json_success(array(
        'breakdown' => $breakdown,
        'total_revenue' => $total_revenue,
        'type' => $type
    ));
}
add_action('wp_ajax_zuwad_analytics_fetch_revenue_breakdown', 'zuwad_analytics_fetch_revenue_breakdown');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_revenue_breakdown', 'zuwad_analytics_fetch_revenue_breakdown');

// AJAX handler for lessons breakdown by attendance
function zuwad_analytics_fetch_lessons_breakdown() {
    global $wpdb;

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }

    $type = sanitize_text_field($_POST['breakdown_type']);
    $date_condition = '';

    if ($type === 'today') {
        $date_condition = "AND DATE(date) = CURDATE()";
    } elseif ($type === 'month') {
        $current_month = date('Y-m');
        $date_condition = $wpdb->prepare("AND DATE_FORMAT(date, '%%Y-%%m') = %s", $current_month);
    }

    $lessons_by_attendance = $wpdb->get_results("
        SELECT
            attendance,
            COUNT(*) as lesson_count
        FROM {$wpdb->prefix}student_reports
        WHERE 1=1 {$date_condition}
        GROUP BY attendance
        ORDER BY lesson_count DESC
    ");

    $breakdown = array();
    $total_lessons = 0;

    // Define attendance type translations and icons
    $attendance_info = array(
        'حضور' => array('icon' => '✅', 'color' => '#2ecc71'),
        'غياب' => array('icon' => '❌', 'color' => '#e74c3c'),
        'تأجيل المعلم' => array('icon' => '⏰', 'color' => '#f39c12'),
        'تأجيل ولي أمر' => array('icon' => '⏳', 'color' => '#9b59b6'),
        'تعويض التأجيل' => array('icon' => '🔄', 'color' => '#3498db'),
        'تعويض الغياب' => array('icon' => '🔁', 'color' => '#1abc9c'),
        'تجريبي' => array('icon' => '🧪', 'color' => '#95a5a6'),
        'اجازة معلم' => array('icon' => '🏖️', 'color' => '#34495e')
    );

    foreach ($lessons_by_attendance as $item) {
        $attendance = $item->attendance ?: 'غير محدد';
        $info = $attendance_info[$attendance] ?? array('icon' => '📝', 'color' => '#7f8c8d');

        $breakdown[] = array(
            'attendance' => $attendance,
            'count' => intval($item->lesson_count),
            'icon' => $info['icon'],
            'color' => $info['color']
        );
        $total_lessons += intval($item->lesson_count);
    }

    wp_send_json_success(array(
        'breakdown' => $breakdown,
        'total_lessons' => $total_lessons,
        'type' => $type
    ));
}
add_action('wp_ajax_zuwad_analytics_fetch_lessons_breakdown', 'zuwad_analytics_fetch_lessons_breakdown');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_lessons_breakdown', 'zuwad_analytics_fetch_lessons_breakdown');

// AJAX handler for first lessons breakdown - simplified version
function zuwad_analytics_fetch_first_lessons_breakdown() {
    try {
        global $wpdb;

        // Basic validation
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }

        $type = isset($_POST['breakdown_type']) ? sanitize_text_field($_POST['breakdown_type']) : 'today';

        // Step 1: Test basic query first
        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}student_reports WHERE session_number = 1";
        $total_count = $wpdb->get_var($count_query);

        if ($wpdb->last_error) {
            wp_send_json_error('Database error in count query: ' . $wpdb->last_error);
            return;
        }

        // Step 2: Simple query without joins first
        if ($type === 'today') {
            $date_condition = "AND DATE(date) = CURDATE()";
        } else {
            $current_month = date('Y-m');
            $date_condition = "AND DATE_FORMAT(date, '%Y-%m') = '" . $current_month . "'";
        }

        $query_with_country = "
            SELECT
                sr.student_id,
                sr.attendance,
                u.display_name as student_name,
                um_country.meta_value as country
            FROM {$wpdb->prefix}student_reports sr
            LEFT JOIN {$wpdb->users} u ON sr.student_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um_country ON u.ID = um_country.user_id AND um_country.meta_key = 'country'
            WHERE sr.session_number = 1
            AND sr.attendance NOT IN ('تعويض التأجيل', 'تعويض الغياب', 'تجريبي')
            {$date_condition}
        ";

        $results = $wpdb->get_results($query_with_country);

        if ($wpdb->last_error) {
            wp_send_json_error('Database error in main query: ' . $wpdb->last_error);
            return;
        }

        // Step 3: Process results
        $attendance_breakdown = array();
        $total_first_lessons = 0;

        $attendance_info = array(
            'حضور' => array('icon' => '✅', 'color' => '#2ecc71'),
            'غياب' => array('icon' => '❌', 'color' => '#e74c3c'),
            'تأجيل المعلم' => array('icon' => '⏰', 'color' => '#f39c12'),
            'تأجيل ولي أمر' => array('icon' => '⏳', 'color' => '#9b59b6')
        );

        foreach ($results as $lesson) {
            $attendance = $lesson->attendance ? $lesson->attendance : 'غير محدد';
            $country = $lesson->country ? $lesson->country : 'غير محدد';

            if (!isset($attendance_breakdown[$attendance])) {
                $info = isset($attendance_info[$attendance]) ? $attendance_info[$attendance] : array('icon' => '📝', 'color' => '#7f8c8d');
                $attendance_breakdown[$attendance] = array(
                    'count' => 0,
                    'icon' => $info['icon'],
                    'color' => $info['color'],
                    'countries' => array()
                );
            }

            // Count by country within each attendance type
            if (!isset($attendance_breakdown[$attendance]['countries'][$country])) {
                $attendance_breakdown[$attendance]['countries'][$country] = 0;
            }

            $attendance_breakdown[$attendance]['count']++;
            $attendance_breakdown[$attendance]['countries'][$country]++;
            $total_first_lessons++;
        }

        // Convert to array format with country sorting
        $breakdown = array();
        foreach ($attendance_breakdown as $attendance => $data) {
            // Sort countries by count (highest first)
            arsort($data['countries']);

            $breakdown[] = array(
                'attendance' => $attendance,
                'count' => $data['count'],
                'icon' => $data['icon'],
                'color' => $data['color'],
                'countries' => $data['countries']
            );
        }

        // Sort by count descending
        usort($breakdown, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        wp_send_json_success(array(
            'breakdown' => $breakdown,
            'total_first_lessons' => $total_first_lessons,
            'type' => $type,
            'debug' => array(
                'total_in_db' => $total_count,
                'query_executed' => true,
                'date_condition' => $date_condition,
                'raw_results_count' => count($results)
            )
        ));

    } catch (Exception $e) {
        wp_send_json_error('Exception: ' . $e->getMessage());
    } catch (Error $e) {
        wp_send_json_error('PHP Error: ' . $e->getMessage());
    }
}
add_action('wp_ajax_zuwad_analytics_fetch_first_lessons_breakdown', 'zuwad_analytics_fetch_first_lessons_breakdown');
add_action('wp_ajax_nopriv_zuwad_analytics_fetch_first_lessons_breakdown', 'zuwad_analytics_fetch_first_lessons_breakdown');

// Simple test AJAX handler
function zuwad_test_first_lessons() {
    wp_send_json_success(array(
        'message' => 'Test successful',
        'breakdown' => array(
            array(
                'attendance' => 'حضور',
                'count' => 8,
                'icon' => '✅',
                'color' => '#2ecc71',
                'countries' => array(
                    'مصر' => 5,
                    'السعودية' => 2,
                    'الإمارات' => 1
                )
            ),
            array(
                'attendance' => 'غياب',
                'count' => 2,
                'icon' => '❌',
                'color' => '#e74c3c',
                'countries' => array(
                    'الكويت' => 1,
                    'مصر' => 1
                )
            )
        ),
        'total_first_lessons' => 10,
        'type' => 'test'
    ));
}
add_action('wp_ajax_zuwad_test_first_lessons', 'zuwad_test_first_lessons');
add_action('wp_ajax_nopriv_zuwad_test_first_lessons', 'zuwad_test_first_lessons');

// Very simple test
function zuwad_simple_test() {
    wp_send_json_success('Hello from server!');
}
add_action('wp_ajax_zuwad_simple_test', 'zuwad_simple_test');
add_action('wp_ajax_nopriv_zuwad_simple_test', 'zuwad_simple_test');
