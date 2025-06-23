<?php



class Zuwad_Plugin_Roles
{
    public function __construct()
    {
        add_action('init', array($this, 'create_roles'));
    }

    public function create_roles()
    {
        // Add custom roles
        add_role('student', 'Student', array(
            'read' => true,
        ));
        add_role('teacher', 'Teacher', array(
            'read' => true,
        ));
        add_role('KPI', 'KPI', array(
            'read' => true,
        ));
        add_role('Accountant', 'Accountant', array(
            'read' => true,
        ));
        add_role('sales', 'sales', array(
            'read' => true,
        ));
        add_role('supervisor', 'Supervisor', array(
            'read' => true,
        ));
        add_role('admin', 'Admin', array(
            'read' => true,
            'manage_options' => true,
        ));
        add_role('superadmin', 'Super Admin', array(
            'read' => true,
            'manage_options' => true,
            'activate_plugins' => true,
        ));
    }

    public function reset_roles()
    {
        // Reset user roles to defaults
        $users = get_users();
        foreach ($users as $user) {
            wp_update_user(array(
                'ID' => $user->ID,
                'role' => 'subscriber', // Reset to default
            ));
        }
    }
}

new Zuwad_Plugin_Roles();





add_action('wp_ajax_create_user', 'zuwad_plugin_create_user');
add_action('wp_ajax_nopriv_create_user', 'zuwad_plugin_create_user');

function zuwad_plugin_create_user()
{
    // Validate and sanitize input data
    $role = sanitize_text_field($_POST['role'] ?? '');
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $dob = sanitize_text_field($_POST['dob'] ?? '');
    $password = sanitize_text_field($_POST['password'] ?? '');
    $teacher = sanitize_text_field($_POST['teacher'] ?? '');
    $supervisor = sanitize_text_field($_POST['supervisor'] ?? '');
    $lessons_name = sanitize_text_field($_POST['lessons_name'] ?? '');
    $teacher_classification = sanitize_text_field($_POST['teacher_classification'] ?? '');
    $teacher_status = sanitize_text_field($_POST['teacher_status'] ?? '');



    $lessons_number = sanitize_text_field($_POST['lessons_number'] ?? '');
    $lesson_duration = sanitize_text_field($_POST['lesson_duration'] ?? '');
    $currency = sanitize_text_field($_POST['currency'] ?? '');
    $previous_lesson = sanitize_text_field($_POST['previous_lesson'] ?? '');

    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $age = sanitize_text_field($_POST['age'] ?? '');
    $country = sanitize_text_field($_POST['country'] ?? '');
    $notes = sanitize_textarea_field($_POST['notes'] ?? '');
    $manual_id = sanitize_text_field($_POST['manual_id'] ?? '');
    $payment_status = sanitize_text_field($_POST['payment_status'] ?? ''); // Add payment status

    // Validate required fields
    if (empty($email)) {
        wp_send_json_error(array('message' => 'البريد الإلكتروني مطلوب.'));
    }


    // Validate phone number length (at least 8 digits)
    $phone = $_POST['phone']; // Assuming the phone number is sent via POST
    if (empty($phone)) {
        wp_send_json_error(array('message' => 'رقم الهاتف مطلوب.'));
    } elseif (!preg_match('/^\d{8,}$/', $phone)) {
        wp_send_json_error(array('message' => 'رقم الهاتف يجب أن يحتوي على 8 أرقام على الأقل.'));
    }



    // Role-specific validation
    if ($role === 'teacher' && empty($supervisor)) {
        wp_send_json_error(array('message' => 'الرجاء اختيار مشرف.'));
    }
    if ($role === 'student' && empty($teacher)) {
        wp_send_json_error(array('message' => 'الرجاء اختيار معلم.'));
    }

    // Check for duplicate manual_id for supervisors
    if ($role === 'supervisor' && !empty($manual_id)) {
        $existing_user = get_users(array(
            'meta_key' => 'manual_id',
            'meta_value' => $manual_id,
            'number' => 1,
        ));
        if (!empty($existing_user)) {
            wp_send_json_error(array('message' => 'معرف المستخدم موجود مسبقًا.'));
        }
    }

    // Extract username from email (remove @ and everything after it)
    $username = strtok($email, '@'); // Get the part before @
    if (empty($username)) {
        wp_send_json_error(array('message' => 'البريد الإلكتروني غير صالح.'));
    }

    // Create user
    $user_data = array(
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $password,
        'first_name' => $first_name,
        'nickname' => $first_name,
        'role' => $role,
    );
    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => 'حدث خطأ أثناء إنشاء المستخدم: ' . $user_id->get_error_message()));
    }

    // Generate m_id
    $m_id = generate_m_id($role, $user_id, $supervisor, $teacher, $manual_id);
    if (is_wp_error($m_id)) {
        wp_send_json_error(array('message' => $m_id->get_error_message()));
    }

    // Update user meta
    update_user_meta($user_id, 'phone', $phone);
    update_user_meta($user_id, 'dob', $dob);
    update_user_meta($user_id, 'teacher', $teacher);
    update_user_meta($user_id, 'supervisor', $supervisor);
    update_user_meta($user_id, 'lessons_name', $lessons_name);
    update_user_meta($user_id, 'teacher_classification', $teacher_classification);
    update_user_meta($user_id, 'teacher_status', $teacher_status);



    update_user_meta($user_id, 'lessons_number', $lessons_number);
    update_user_meta($user_id, 'lesson_duration', $lesson_duration);
    update_user_meta($user_id, 'currency', $currency);
    update_user_meta($user_id, 'previous_lesson', $previous_lesson);

    update_user_meta($user_id, 'amount', $amount);
    update_user_meta($user_id, 'age', $age);
    update_user_meta($user_id, 'country', $country);
    update_user_meta($user_id, 'notes', $notes);
    update_user_meta($user_id, 'm_id', $m_id);
    update_user_meta($user_id, 'payment_status', $payment_status); // Add payment status

    if ($role === 'supervisor' && !empty($manual_id)) {
        update_user_meta($user_id, 'manual_id', $manual_id);
    }

    // Return success response
    wp_send_json_success(array('m_id' => $m_id));
}

function generate_m_id($role, $user_id, $supervisor, $teacher, $manual_id)
{
    switch ($role) {
        case 'teacher':
            $supervisor_m_id = get_user_meta($supervisor, 'm_id', true);
            if (empty($supervisor_m_id)) {
                return new WP_Error('invalid_supervisor', 'لم يتم العثور على م (ID) للمشرف.');
            }
            $teacher_counter = (int) get_user_meta($supervisor, 'teacher_counter', true) ?: 1;
            update_user_meta($supervisor, 'teacher_counter', $teacher_counter + 1);
            return $supervisor_m_id . str_pad($teacher_counter, 2, '0', STR_PAD_LEFT); // Ensure two digits

        case 'student':
            $teacher_m_id = get_user_meta($teacher, 'm_id', true);
            if (empty($teacher_m_id)) {
                return new WP_Error('invalid_teacher', 'لم يتم العثور على م (ID) للمعلم.');
            }
            $student_counter = (int) get_user_meta($teacher, 'student_counter', true) ?: 1;
            update_user_meta($teacher, 'student_counter', $student_counter + 1);
            return $teacher_m_id . str_pad($student_counter, 2, '0', STR_PAD_LEFT); // Ensure two digits

        case 'supervisor':
            return !empty($manual_id) ? str_pad($manual_id, 2, '0', STR_PAD_LEFT) : str_pad($user_id, 2, '0', STR_PAD_LEFT); // Ensure two digits

        default:
            return str_pad($user_id, 2, '0', STR_PAD_LEFT); // Ensure two digits
    }
}
