<?php
/**
 * homepage shortcode
 * 
 * This file contains the shortcode that displays different content based on user roles
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode to display content based on user role
 */
function zuwad_homepage_shortcode() {
    // Get current user
    $current_user = wp_get_current_user();
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please log in to view this content.</p>';
    }
    
    // Check if user is an accountant
    $is_accountant = in_array('Accountant', $current_user->roles);
    
    // Start output buffering
    ob_start();
    
    if ($is_accountant) {
        // Show only payment shortcode for accountants
        echo do_shortcode('[payment]');
    } else {
        // Show full dashboard for other users
        ?>
        <div class="container">
            <div class="two-colmuns">
                <div class="left-column">
                    <?php echo do_shortcode('[supervisor_teachers]'); ?>
                    <?php echo do_shortcode('[sales_dashboard]'); ?>
                </div>

                <div class="right-column">
                    <?php echo do_shortcode('[student_schedules_inline]'); ?>
                    <?php echo do_shortcode('[zuwad_settings]'); ?>
                    <div class="add-buttons">
                        <?php echo do_shortcode('[add_report]'); ?>
                    </div>
                </div>
            </div>

            <div class="calendar-body">
                <?php echo do_shortcode('[teacher_calendar]'); ?>
            </div>
        </div>

        <style>
            /* Base styling for large screens */
            .container {
                display: flex;
                flex-direction: column;
                /* Allow content to wrap on smaller screens */
            }

            .two-colmuns {
                display: flex;
                flex-wrap: wrap;
            }

            .add-buttons {
                display: flex;
                flex-direction: row;
                justify-content: center;
                gap: 20px;
                height: min-content;
            }

            .left-column {
                flex: 2.1;
                padding: 10px;
            }

            .right-column {
                flex: 0.8;
                padding: 10px;
                display: flex;
                flex-direction: column;
                margin-right: 10px;
            }

            /* Responsive Styling for smaller screens */
            @media (max-width: 768px) {
                .left-column,
                .right-column {
                    flex: 1;
                    /* Make columns equal width on smaller screens */
                }
            }

            @media (max-width: 480px) {
                .container {
                    flex-direction: column;
                    /* Stack columns vertically on very small screens */
                }

                .left-column,
                .right-column {
                    flex: 1 100%;
                    /* Take full width of the container */
                    padding: 15px;
                    /* More padding on mobile */
                }
            }
        </style>
        <?php
    }
    
    // Return the buffered content
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('zuwad_homepage', 'zuwad_homepage_shortcode');
