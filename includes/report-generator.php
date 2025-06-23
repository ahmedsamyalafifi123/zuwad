<?php
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

function zuwad_generate_report_image($report_data) {
    // Create a unique filename for the image
    $filename = 'report-' . time() . '.html';

    // Prepare HTML content for the report
    $html_content = '
    <!DOCTYPE html>
    <html lang="ar">
    <head>
        <meta charset="UTF-8">
        <title>تقرير الطالب</title>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap");
            body {
                font-family: "Cairo", sans-serif;
                margin: 0;
                padding: 0;
                direction: rtl;
                background: url("https://system.zuwad-academy.com/wp-content/uploads/2025/01/تقرير-الطالب-زواد-10-scaled.webp") no-repeat;
                background-size: cover;
                width: 1240px;
                height: 1754px;
                position: relative;
            }
            .field {
                position: absolute;
                color: black;
                font-size: 26px;
                text-align: center;
                width: 300px;
                word-wrap: break-word;
            }
            .student-name { top: 370px; left: 200px; color: #cd9900; font-weight: bold; }
            .evaluation { top: 420px; left: 235px; }
            .session-number { top: 477px; left: 470px; }
            .previous-review { top: 845px; left: 75px; }
            .previous-memorization { top: 845px; left: 460px; }
            .previous-recitation { top: 845px; right: 80px; }
            .upcoming-review { top: 1097px; left: 230px; }
            .upcoming-recitation { top: 1097px; right: 245px; }
            .notes { bottom: 163px; right: 164px; width: 350px; }
            .zoom-screenshot { bottom: 144px; left: 120px; width: 369px; height: 272px; }
        </style>
    </head>
    <body>
        <div class="field student-name">' . esc_html($report_data['student_name']) . '</div>
        <div class="field evaluation">' . esc_html($report_data['evaluation']) . '</div>
        <div class="field session-number">' . esc_html($report_data['session_number']) . '</div>
        <div class="field previous-review">' . esc_html($report_data['mourajah']) . '</div>
        <div class="field previous-memorization">' . esc_html($report_data['tahfiz']) . '</div>
        <div class="field previous-recitation">' . esc_html($report_data['tasmii']) . '</div>
        <div class="field upcoming-review">' . esc_html($report_data['next_mourajah']) . '</div>
        <div class="field upcoming-recitation">' . esc_html($report_data['next_tasmii']) . '</div>
        <div class="field notes">' . esc_html($report_data['notes']) . '</div>
        ' . (!empty($report_data['zoom_image_url']) ? 
            '<div class="zoom-screenshot"><img src="' . esc_url($report_data['zoom_image_url']) . '" style="width:100%; height:100%;"/></div>' : 
            '') . '
    </body>
    </html>';

    // Upload the HTML file to WordPress uploads
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . $filename;
    file_put_contents($file_path, $html_content);

    // Return the URL of the uploaded file
    return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
}

function zuwad_ajax_generate_report_image() {
    // Temporarily disable nonce check
    // check_ajax_referer('zuwad_report_nonce', 'nonce');

    // Get report data from POST
    $report_data = $_POST['report_data'];

    try {
        // Generate the HTML report
        $report_url = zuwad_generate_report_image($report_data);

        // Return success response with report URL
        wp_send_json_success([
            'image_url' => $report_url,
            'image_path' => str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $report_url)
        ]);
    } catch (Exception $e) {
        // Handle any errors
        wp_send_json_error([
            'message' => $e->getMessage()
        ]);
    }

    wp_die();
}
add_action('wp_ajax_generate_report_image', 'zuwad_ajax_generate_report_image');
add_action('wp_ajax_nopriv_generate_report_image', 'zuwad_ajax_generate_report_image');
