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
