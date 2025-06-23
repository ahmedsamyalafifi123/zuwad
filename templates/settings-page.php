<?php if (current_user_can('administrator') || current_user_can('supervisor') || current_user_can('sales')): ?>
    <div class="wrap">
        <h2><?php _e('إنشاء المستخدمين', 'zuwad-plugin'); ?></h2>
        <form id="create_user_form" action="javascript:void(0);">
            <div class="button-container">
                <!-- These buttons are visible for administrators or supervisors -->
                <?php if (current_user_can('administrator') || current_user_can('supervisor') || current_user_can('sales')): ?>
                    <button class="button" data-role="student"><?php _e('اضافة طالب 🧑‍🎓', 'zuwad-plugin'); ?></button>
                    <button class="button" data-role="teacher"><?php _e('اضافة معلم 🧑‍🏫', 'zuwad-plugin'); ?></button>
                <?php endif; ?>
                <!-- These buttons are visible for administrators only -->
                <?php if (current_user_can('administrator')): ?>
                    <button class="button" data-role="supervisor"><?php _e('اضافة مشرف 🧑‍💼', 'zuwad-plugin'); ?></button>
                    <button class="button" data-role="admin"><?php _e('اضافة ادمن', 'zuwad-plugin'); ?></button>
                    <button class="button" data-role="superadmin"><?php _e('اضافة سوبر ادمن', 'zuwad-plugin'); ?></button>
                    <button class="button" data-role="KPI"><?php _e('اضافة مستخدم KPI 📊', 'zuwad-plugin'); ?></button>
                    <button class="button" data-role="sales"><?php _e('اضافة مستخدم sales 🪙', 'zuwad-plugin'); ?></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- Modal form for creating user -->
        <div id="user_modal" style="display:none;">
            <!-- Modal content will go here, with form fields -->
        </div>
        </form>
    </div>


    <style>
        /* General styles */
        .wrap {
            max-width: 1200px;
            margin: 10px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .button-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .button-container {
                flex-direction: column;
                align-items: center;
            }

            .button {
                width: 100%;
                text-align: center;
            }

        }
    </style>