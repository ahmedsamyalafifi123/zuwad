<?php

/**
 * Get WhatsApp API configuration
 * 
 * This function provides a centralized source for all WhatsApp API configuration.
 * All functions that need WhatsApp API credentials should use this function.
 * 
 * @param string $key Optional. Specific config key to retrieve.
 * @param string $m_id Optional. Student m_id to determine which device_id to use.
 * @return mixed The entire config array or specific value if key is provided.
 */
function zuwad_get_whatsapp_config($key = null, $m_id = null) {
    $config = array(
        'api_url_base' => 'https://noti-fire.com/api/',
        'default_media_name' => 'zuwad.png',
        'device_id_01' => 'ed0f27f1-d937-41d0-8fe0-f2a7de41d35e', // For students with m_id starting with 01
        'device_id_02' => 'e6691ccf-5f76-4208-93b4-ab8ae9f26245', // For students with m_id starting with 02
    );
    
    // Determine which device_id to use based on m_id prefix
    if ($m_id) {
        $m_id_prefix = substr($m_id, 0, 2);
        if ($m_id_prefix === '01') {
            $config['device_id'] = $config['device_id_01'];
        } elseif ($m_id_prefix === '02') {
            $config['device_id'] = $config['device_id_02'];
        } else {
            // Default to device_id_01 if prefix is unknown
            $config['device_id'] = $config['device_id_01'];
        }
    } else {
        // Default to device_id_01 when no m_id is provided
        $config['device_id'] = $config['device_id_01'];
    }
    
    if ($key !== null) {
        return isset($config[$key]) ? $config[$key] : null;
    }
    
    return $config;
}

/**
 * Format date for Arabic WhatsApp messages
 */
function zuwad_format_arabic_date($date_string) {
    if (empty($date_string)) {
        return '';
    }

    try {
        $date_obj = new DateTime($date_string);

        // Arabic day names
        $arabic_days = array(
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت'
        );

        // Arabic month names
        $arabic_months = array(
            'January' => 'يناير',
            'February' => 'فبراير',
            'March' => 'مارس',
            'April' => 'أبريل',
            'May' => 'مايو',
            'June' => 'يونيو',
            'July' => 'يوليو',
            'August' => 'أغسطس',
            'September' => 'سبتمبر',
            'October' => 'أكتوبر',
            'November' => 'نوفمبر',
            'December' => 'ديسمبر'
        );

        $day_name = $date_obj->format('l');
        $month_name = $date_obj->format('F');
        $day_number = $date_obj->format('j');
        $year = $date_obj->format('Y');

        $arabic_day = isset($arabic_days[$day_name]) ? $arabic_days[$day_name] : $day_name;
        $arabic_month = isset($arabic_months[$month_name]) ? $arabic_months[$month_name] : $month_name;

        return $arabic_day . ' ' . $day_number . ' ' . $arabic_month . ' ' . $year;

    } catch (Exception $e) {
        return $date_string; // Return original if formatting fails
    }
}

/**
 * Get WhatsApp message template based on attendance type
 */
function zuwad_get_whatsapp_message_by_attendance($attendance, $student_name = '', $report_date = '') {
    // Format the date in Arabic if provided
    $formatted_date = '';
    if (!empty($report_date)) {
        $formatted_date = zuwad_format_arabic_date($report_date);
    }

    $messages = array(
        'غياب' => "سلام عليكم 👋🤩\n\nلاحظنا أنك لم تحضر الحصة اليوم دون إبلاغنا مسبقًا.\nوفقًا لنظام الحضور والغياب، لا يمكن تعويض الحصص التي يتم التغيب عنها دون إبلاغنا قبل موعدها بساعة واحدة على الأقل، وذلك حرصًا على وقت المعلم.\nإذا كنت قد واجهت ظرفًا طارئًا، يُرجى إبلاغنا حتى نتمكن من مساعدتك قدر الإمكان. 💙\n\nنتمنى رؤيتك في الحصة القادمة! 🎯📖\n\nإدارة أكاديمية زواد 📚🎖",

        'تأجيل ولي أمر' => "أهلًا وسهلًا بيك! 👋🤩\n\nتم تأجيل حصتك يوم " . $formatted_date . " للطالب " . $student_name . "، ونحتاج لتحديد موعد لتعويضها في أقرب وقت.\n\nهل يمكنك إبلاغي بيوم مناسب لك خلال الأيام القادمة لحضور حصة بديلة؟ ⏳📅\n\nشكرًا لتعاونك! ❤🌿\n\nإدارة أكاديمية زواد 📚🎖",

        'تأجيل المعلم' => "سلام عليكم 👋🤩\n\nنود إبلاغك بأن المعلم تعرض لظرف طارئ، لذلك سيتم تغيير موعد حصة يوم " . $formatted_date . " للطالب " . $student_name . ".\n\nسنقوم بتحديد موعد بديل يتناسب مع وقتك.\n\nهل يمكنك إبلاغنا باليوم المناسب لك لحضور الحصة البديلة؟ ⏳📅\n\nشكرًا لتفهمك وتعاونك! ❤🌿\n\nإدارة أكاديمية زواد 📚🎖"
    );

    return isset($messages[$attendance]) ? $messages[$attendance] : '';
}

/**
 * Handle WhatsApp report sending
 */
function handle_whatsapp_report_send() {
    try {
        // Parse parameters for the unified function
        $args = array(
            'phone_number' => '',
            'message' => '',
            'debug' => true
        );

        // Get message/caption
        if (isset($_POST['mediaCaption'])) {
            $args['message'] = $_POST['mediaCaption'];
        } elseif (isset($_POST['message'])) {
            $args['message'] = $_POST['message'];
        }

        // Get phone number
        if (isset($_POST['chatId'])) {
            $args['phone_number'] = str_replace('@c.us', '', $_POST['chatId']);
        } elseif (isset($_POST['phone_number'])) {
            $args['phone_number'] = $_POST['phone_number'];
        }

        // Get m_id for backward compatibility
        $m_id = isset($_POST['m_id']) ? $_POST['m_id'] : '';
        $args['m_id'] = $m_id;

        // Check for force_resend parameter
        $force_resend = isset($_POST['force_resend']) && $_POST['force_resend'];

        // Get report_id if available for tracking
        if (isset($_POST['report_id']) && !empty($_POST['report_id'])) {
            $args['report_id'] = $_POST['report_id'];

            // Check if this report was already sent, but only if not forcing a resend
            if (!$force_resend) {
                $report_id = $_POST['report_id'];
                global $wpdb;
                $table_name = $wpdb->prefix . 'student_reports';

                $whatsapp_shared = $wpdb->get_var($wpdb->prepare(
                    "SELECT whatsapp_shared FROM {$table_name} WHERE id = %d",
                    $report_id
                ));

                if ($whatsapp_shared == 1) {
                    // If already sent, return success with already_sent flag
                    wp_send_json_success(array(
                        'message' => 'Report already sent',
                        'already_sent' => true
                    ));
                    wp_die();
                }
            }
        }

        // Check if we need to send a special attendance message instead of a report
        if (isset($_POST['attendance_message']) && $_POST['attendance_message'] === 'true') {
            $attendance = isset($_POST['attendance']) ? sanitize_text_field($_POST['attendance']) : '';
            $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

            // Get student name and report date from the report
            $student_name = '';
            $report_date = '';

            if ($report_id > 0) {
                global $wpdb;
                $report_table = $wpdb->prefix . 'student_reports';
                $users_table = $wpdb->prefix . 'users';

                // Get report data with student name from WordPress users table
                $report_data = $wpdb->get_row($wpdb->prepare("
                    SELECT r.date as report_date, u.display_name as student_name
                    FROM {$report_table} r
                    LEFT JOIN {$users_table} u ON r.student_id = u.ID
                    WHERE r.id = %d
                ", $report_id));

                if ($report_data) {
                    $student_name = $report_data->student_name;
                    $report_date = $report_data->report_date;

                    // Format the date in a readable Arabic format
                    if (!empty($report_date)) {
                        // The report_date is already in Y-m-d format from database
                        // No need to reformat it here since zuwad_format_arabic_date handles it
                    }
                }
            }

            $special_message = zuwad_get_whatsapp_message_by_attendance($attendance, $student_name, $report_date);

            // Debug logging
            error_log('Attendance message request - Attendance: ' . $attendance);
            error_log('Student name: ' . $student_name);
            error_log('Report date: ' . $report_date);
            error_log('Special message: ' . $special_message);
            error_log('Phone number: ' . $args['phone_number']);
            error_log('M_ID: ' . $args['m_id']);

            if (!empty($special_message)) {
                $args['message'] = $special_message;

                // For attendance messages, we don't need media
                unset($args['media_url']);
                unset($args['media_base64']);

                // Call our unified WhatsApp function for text-only message
                $result = zuwad_send_whatsapp_message($args);

                // Debug logging
                error_log('WhatsApp send result: ' . print_r($result, true));

                if ($result['success']) {
                    // Mark the report as WhatsApp shared in the database
                    if ($report_id > 0) {
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'student_reports';

                        // Check if the whatsapp_shared column exists
                        $check_column = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'whatsapp_shared'");
                        if (empty($check_column)) {
                            // Add the column if it doesn't exist
                            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN whatsapp_shared TINYINT(1) DEFAULT 0");
                        }

                        // Update the whatsapp_shared status
                        $wpdb->update(
                            $table_name,
                            array('whatsapp_shared' => 1),
                            array('id' => $report_id),
                            array('%d'),
                            array('%d')
                        );

                        if ($debug) {
                            error_log('Marked report ID ' . $report_id . ' as WhatsApp shared for attendance message');
                        }
                    }

                    wp_send_json_success(array(
                        'message' => 'Attendance message sent successfully',
                        'response' => $result['data']
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => $result['message'],
                        'details' => isset($result['details']) ? $result['details'] : null
                    ));
                }
                wp_die();
            } else {
                // No message template found for this attendance type
                error_log('No message template found for attendance: ' . $attendance);
                wp_send_json_error(array(
                    'message' => 'No message template found for attendance type: ' . $attendance
                ));
                wp_die();
            }
        }
        
        // Check if we're using media_url
        if (isset($_POST['media_url']) && !empty($_POST['media_url'])) {
            // Check if it's a data URL or a regular URL
            if (strpos($_POST['media_url'], 'data:image/') === 0) {
                // It's a base64 data URL, extract the data part and use as media_base64
                list(, $base64_data) = explode(';base64,', $_POST['media_url']);
                $args['media_base64'] = $base64_data;
                $args['media_name'] = 'report_' . time() . '.png';
                
                // Log for debugging
                if ($args['debug']) {
                    error_log('Converted data URL to base64 data for processing');
                }
            } else {
                // It's a regular URL
                $args['media_url'] = $_POST['media_url'];
            }
        } elseif (isset($_POST['mediaUrl']) && !empty($_POST['mediaUrl'])) {
            // Same check for mediaUrl parameter
            if (strpos($_POST['mediaUrl'], 'data:image/') === 0) {
                list(, $base64_data) = explode(';base64,', $_POST['mediaUrl']);
                $args['media_base64'] = $base64_data;
                $args['media_name'] = 'report_' . time() . '.png';
        } else {
                $args['media_url'] = $_POST['mediaUrl'];
            }
        } 
        // Check if we're using mediaBase64
        elseif (isset($_POST['mediaBase64']) && !empty($_POST['mediaBase64'])) {
            $args['media_base64'] = $_POST['mediaBase64'];
            $args['media_name'] = isset($_POST['mediaName']) ? $_POST['mediaName'] : 'image.png';
        } 
        // Check if we're using media_base64 (new parameter name)
        elseif (isset($_POST['media_base64']) && !empty($_POST['media_base64'])) {
            $args['media_base64'] = $_POST['media_base64'];
            $args['media_name'] = isset($_POST['media_name']) ? $_POST['media_name'] : 'image.png';
        } else {
            // Only require media in certain contexts
            if (!isset($_POST['message']) || empty($_POST['message'])) {
                wp_send_json_error(array('message' => 'No media or message provided'));
            wp_die();
            }
        }
        
        // Add force_resend flag if present
        if ($force_resend) {
            $args['force_resend'] = true;
        }

        // Enable debug mode for troubleshooting
        $args['debug'] = true;

        // Call our unified WhatsApp function
        $result = zuwad_send_whatsapp_message($args);
        
        // Pass through all result data including inner errors
        if ($result['success']) {
            // If there's an inner error flag, include it in the response
            $response_data = array(
                'message' => 'Message sent successfully',
                'response' => $result['data']
            );
            
            if (isset($result['has_inner_error'])) {
                $response_data['has_inner_error'] = $result['has_inner_error'];
            }
            
            // Include already_sent flag if this was a duplicate
            if (isset($result['data']['already_sent']) && $result['data']['already_sent']) {
                $response_data['already_sent'] = true;
            }
            
            // Update report WhatsApp status in database if report_id is provided
            if (!empty($args['report_id'])) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'student_reports';
                
                // Check if the whatsapp_shared column exists
                $check_column = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'whatsapp_shared'");
                if (!empty($check_column)) {
                    $wpdb->update(
                        $table_name,
                        array('whatsapp_shared' => 1),
                        array('id' => $args['report_id']),
                        array('%d'),
                        array('%d')
                    );
                }
            }
            
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error(array(
                'message' => $result['message'],
                'details' => isset($result['details']) ? $result['details'] : null
            ));
        }

    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }

    wp_die();
}

/**
 * Unified WhatsApp messaging function for the entire plugin
 * 
 * This is the single source of truth for all WhatsApp communication in the plugin.
 * All other functions should use this function to send WhatsApp messages.
 * 
 * @param array $args {
 *     Parameters for WhatsApp message.
 *     
 *     @type string  $phone_number     Recipient phone number without @c.us suffix.
 *     @type string  $message          Message text or caption for media.
 *     @type string  $media_base64     Optional. Base64 encoded media data (without data:image prefix).
 *     @type string  $media_url        Optional. URL to media file.
 *     @type string  $media_name       Optional. Name for the media file. Default 'zuwad.png'.
 *     @type string  $m_id             Optional. Student m_id for instance selection.
 *     @type bool    $is_test          Optional. Whether this is a test message. Default false.
 *     @type bool    $add_to_log       Optional. Whether to add the number to sent log. Default true.
 *     @type bool    $debug            Optional. Whether to log extra debug info. Default false.
 *     @type string  $report_id        Optional. Report ID for tracking to prevent duplicate sends.
 *     @type bool    $force_resend     Optional. Force resend even if already sent. Default false.
 * }
 * 
 * @return array Response array with success status and data.
 */
function zuwad_send_whatsapp_message($args) {
    // Extract m_id for device selection
    $m_id = isset($args['m_id']) ? $args['m_id'] : '';
    
    // Get configuration from central function, passing m_id for device selection
    $config = zuwad_get_whatsapp_config(null, $m_id);
    
    // Set defaults
    $defaults = array(
        'phone_number'  => '',
        'message'       => '',
        'media_base64'  => '',
        'media_url'     => '',
        'media_name'    => $config['default_media_name'],
        'm_id'          => $m_id,
        'is_test'       => false,
        'add_to_log'    => true,
        'debug'         => false,
        'report_id'     => '', // Report ID for tracking
        'force_resend'  => false // Force resend even if already sent
    );
    
    // Merge with defaults
    $args = wp_parse_args($args, $defaults);
    
    // Extract variables
    $phone_number = $args['phone_number'];
    $message = $args['message'];
    $media_base64 = $args['media_base64'];
    $media_url = $args['media_url'];
    $media_name = $args['media_name'];
    $m_id = $args['m_id']; // Now has the value from defaults if not provided
    $is_test = $args['is_test'];
    $add_to_log = $args['add_to_log'];
    $debug = $args['debug'];
    $report_id = $args['report_id'];
    $force_resend = $args['force_resend'];
    
    // Removed all transient functionality to ensure messages are always sent
    // without any duplicate prevention
    if ($debug) {
        // error_log('Transient functionality removed - all messages will be sent regardless of previous sends');
    }
    
    // If force_resend is true and we have a timestamp parameter to prevent caching
    if ($force_resend && !empty($media_url) && strpos($media_url, '?') === false) {
        // Add a timestamp parameter to prevent caching
        $media_url .= '?t=' . time();
        if ($debug) {
            error_log('Adding timestamp to media URL to prevent caching: ' . $media_url);
        }
    }
    
    // Validate required fields
    if (empty($phone_number)) {
        if ($debug) {
            error_log('WhatsApp send failed: Phone number is required');
        }
        return array(
            'success' => false,
            'message' => 'Missing required phone_number'
        );
    }

    if (empty($message) && empty($media_base64) && empty($media_url)) {
        if ($debug) {
            error_log('WhatsApp send failed: Message content is required');
        }
        return array(
            'success' => false,
            'message' => 'Missing message content (text or media)'
        );
    }

    // Format phone number - ensure it starts with +
    if (substr($phone_number, 0, 1) !== '+') {
        $phone_number = '+' . $phone_number;
    }

    // Trim any @c.us suffix
    $phone_number = str_replace('@c.us', '', $phone_number);

    if ($debug) {
        error_log('WhatsApp send parameters: Phone=' . $phone_number . ', M_ID=' . $m_id . ', Device_ID=' . $config['device_id']);
        error_log('Message length: ' . strlen($message));
    }
    
    // Determine if this is a media message
    $is_media = !empty($media_base64) || !empty($media_url);
    
    // Determine API endpoint based on message type
    $api_url_base = $config['api_url_base'];
    
    if ($is_media) {
        // For media messages, use the media endpoint
        $url = $api_url_base . 'send/media';
        
        $payload = array(
            'device_id' => $config['device_id'], // Use the device_id selected based on m_id
            'to' => $phone_number,
            'type' => 'image',
            'mediaUrl' => '',
            'caption' => !empty($message) ? $message : ''
        );
        
        // Add mediaUrl to the payload
        if (!empty($media_url)) {
            // Validate media URL
            if (filter_var($media_url, FILTER_VALIDATE_URL) === false) {
                if ($debug) {
                    error_log('Invalid media URL format: ' . $media_url);
                }
                return array(
                    'success' => false,
                    'message' => 'The media url field must be a valid URL.'
                );
            }
            
            $payload['mediaUrl'] = $media_url;
            
            // For forced resends, ensure we have a timestamp parameter
            if ($force_resend && (strpos($media_url, '?t=') === false && strpos($media_url, '&t=') === false)) {
                $timestamp = time();
                $payload['mediaUrl'] = $media_url . (strpos($media_url, '?') !== false ? '&t=' : '?t=') . $timestamp;
                
            if ($debug) {
                    error_log('Added timestamp to media URL for forced resend: ' . $payload['mediaUrl']);
                }
            }
        } elseif (!empty($media_base64)) {
            // We need to upload the base64 to a temporary URL first
            $temp_media_url = upload_base64_to_temp_url($media_base64, $media_name);
            if ($temp_media_url) {
                $payload['mediaUrl'] = $temp_media_url;
                
                if ($debug) {
                    error_log('Uploaded base64 data to temporary URL: ' . $temp_media_url);
                }
            } else {
                if ($debug) {
                    error_log('Failed to upload base64 data to temporary URL');
                }
                return array(
                    'success' => false,
                    'message' => 'Failed to prepare media for sending'
                );
            }
        }
    } else {
        // For text messages, use the message endpoint
        $url = $api_url_base . 'send/message';
        
        $payload = array(
            'device_id' => $config['device_id'], // Use the device_id selected based on m_id
            'to' => $phone_number,
            'message' => $message
        );
    }
    
    // Log the payload for debugging
    if ($debug) {
        error_log('WhatsApp API Payload: ' . print_r($payload, true));
    }
    
    // Prepare the headers for the API request
    $headers = array(
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    );
    
    // Prepare the args for wp_remote_post
    $args = array(
        'headers' => $headers,
        'body' => json_encode($payload),
        'timeout' => 60,
        'sslverify' => false
    );
    
    // Send the request
    $response = wp_remote_post($url, $args);

    // Process the response
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        return array(
            'success' => false,
            'message' => $error_message
        );
    }

    // Get the response details
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    // Log the API response for debugging
    if ($debug) {
        error_log('WhatsApp API Response Code: ' . $response_code);
        error_log('WhatsApp API Response Body: ' . $response_body);
        error_log('WhatsApp API Response Data: ' . print_r($response_data, true));
    }

    // Check response status - noti-fire API returns success in different formats
    // Try multiple success indicators to be more flexible
    $is_success = false;

    if ($response_code === 200) {
        // Check various success indicators
        if (isset($response_data['status']) && $response_data['status'] === true) {
            $is_success = true;
        } elseif (isset($response_data['success']) && $response_data['success'] === true) {
            $is_success = true;
        } elseif (isset($response_data['sent']) && $response_data['sent'] === true) {
            $is_success = true;
        } elseif (isset($response_data['result']) && $response_data['result'] === 'success') {
            $is_success = true;
        } elseif (!isset($response_data['error']) && !isset($response_data['message'])) {
            // If no explicit error and 200 response, consider it success
            $is_success = true;
        }
    }

    if ($is_success) {
        // NOTE: We do NOT automatically add to sent log here anymore
        // The calling function will handle adding to log only when appropriate
        // This prevents numbers from being added to log when they should be skipped

        // Completely removed transient functionality
        if ($debug) {
            error_log('WhatsApp message sent successfully');
        }

        // Success!
        return array(
            'success' => true,
            'message' => 'تم الإرسال بنجاح',
            'data' => $response_data,
            'device_id' => $config['device_id'], // Include the device_id used in the response
            'add_to_log' => $add_to_log, // Pass this back so caller knows if they should add to log
            'is_test' => $is_test // Pass this back so caller knows if this was a test
        );
    } else {
        // Extract error message from various possible locations
        $error_message = 'Unknown error occurred';

        if (isset($response_data['message'])) {
            $error_message = $response_data['message'];
        } elseif (isset($response_data['error'])) {
            if (is_string($response_data['error'])) {
                $error_message = $response_data['error'];
            } elseif (is_array($response_data['error']) && isset($response_data['error']['message'])) {
                $error_message = $response_data['error']['message'];
            }
        } elseif (isset($response_data['description'])) {
            $error_message = $response_data['description'];
        } elseif ($response_code !== 200) {
            $error_message = 'HTTP Error: ' . $response_code;
        }

        if ($debug) {
            error_log('WhatsApp message failed: ' . $error_message);
        }

        return array(
            'success' => false,
            'message' => $error_message,
            'details' => $response_data,
            'response_code' => $response_code,
            'response_body' => $response_body,
            'device_id' => $config['device_id'] // Include the device_id used in the response
        );
    }
}

/**
 * Helper function to upload base64 data to a temporary URL
 */
function upload_base64_to_temp_url($base64_data, $filename = 'image.png') {
    // Create a temporary file
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/whatsapp_temp';
    
    // Create directory if it doesn't exist
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }
    
    // Generate unique filename
    $unique_filename = 'whatsapp_' . uniqid() . '_' . sanitize_file_name($filename);
    $temp_file_path = $temp_dir . '/' . $unique_filename;
    
    // Decode and save the base64 data
    $decoded_data = base64_decode($base64_data);
    if ($decoded_data === false) {
        return false;
    }
    
    $result = file_put_contents($temp_file_path, $decoded_data);
    if ($result === false) {
        return false;
    }
    
    // Return the URL to the file
    $file_url = $upload_dir['baseurl'] . '/whatsapp_temp/' . $unique_filename;
    
    // Ensure URL uses https
    $file_url = str_replace('http://', 'https://', $file_url);
    
    return $file_url;
}

// Update waapi_send_message_to_number to use our unified function
function waapi_send_message_to_number($phone_number, $message, $instance_id = null, $media_base64 = null, $is_media = false, $is_test = false, $m_id = '') {
    // Prepare arguments for the unified function
    $args = array(
        'phone_number' => $phone_number,
        'message' => $message,
        'm_id' => $m_id,
        'instance_id' => $instance_id,
        'is_test' => $is_test
    );
    
    // Add media parameters if this is a media message
    if ($is_media && !empty($media_base64)) {
        $args['media_base64'] = $media_base64;
        $args['media_name'] = 'zuwad.png'; // Default name
    }
    
    // Call our unified function
    return zuwad_send_whatsapp_message($args);
}

// Update handle_whatsapp_report_send_helper to use our unified function
function handle_whatsapp_report_send_helper($image_url, $chat_id, $media_caption, $authorization, $mediaBase64 = null, $mediaName = null, $instance_id = null, $m_id = '') {
    // Get m_id from function parameter or from POST data
    if (empty($m_id) && isset($_POST['m_id'])) {
        $m_id = $_POST['m_id'];
    }
    
    // Prepare arguments for the unified function
    $args = array(
        'phone_number' => str_replace('@c.us', '', $chat_id), // Remove @c.us if present
        'message' => $media_caption,
        'm_id' => $m_id,
        'instance_id' => $instance_id,
        'auth_token' => trim(str_replace('Bearer ', '', $authorization)),
        'debug' => true // Enable debug logging to troubleshoot sending issues
    );
    
    // Add media parameters 
    if (!empty($image_url)) {
        $args['media_url'] = $image_url;
    } elseif (!empty($mediaBase64)) {
        $args['media_base64'] = $mediaBase64;
        $args['media_name'] = $mediaName;
    }
    
    // Call our unified function
    return zuwad_send_whatsapp_message($args);
}

add_action('wp_ajax_send_whatsapp_report', 'handle_whatsapp_report_send');
add_action('wp_ajax_nopriv_send_whatsapp_report', 'handle_whatsapp_report_send');

add_action('wp_ajax_update_report_whatsapp_status', 'handle_update_report_whatsapp_status');
add_action('wp_ajax_nopriv_update_report_whatsapp_status', 'handle_update_report_whatsapp_status');

function handle_update_report_whatsapp_status() {
    
    $report_id = intval($_POST['report_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'student_reports';
    
    // Add a whatsapp_shared column if it doesn't exist
    $check_column = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'whatsapp_shared'");
    if (empty($check_column)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN whatsapp_shared TINYINT(1) DEFAULT 0");
    }
    
    // Update the whatsapp_shared status
    $result = $wpdb->update(
        $table_name,
        array('whatsapp_shared' => 1),
        array('id' => $report_id),
        array('%d'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success(array('message' => 'Report status updated successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update report status'));
    }
}

/**
 * Handle file upload for WhatsApp sharing
 * This is used as a fallback when base64 fails
 */
function handle_whatsapp_upload_file() {
    // Verify nonce for security
    // check_ajax_referer('zuwad_nonce', '_ajax_nonce');
    
    // Validate required parameters
    if (!isset($_POST['file_type']) || !isset($_POST['file_data'])) {
        wp_send_json_error(array('message' => 'Missing required parameters'));
        wp_die();
    }
    
    $file_type = sanitize_text_field($_POST['file_type']);
    $file_data = $_POST['file_data']; // Don't sanitize the base64 data
    
    // Extract the base64 data from the dataURL format
    if (strpos($file_data, ';base64,') !== false) {
        list(, $file_data) = explode(';base64,', $file_data);
    }
    
    // Decode the base64 data
    $decoded_data = base64_decode($file_data);
    if ($decoded_data === false) {
        wp_send_json_error(array('message' => 'Invalid base64 data'));
        wp_die();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $whatsapp_dir = $upload_dir['basedir'] . '/whatsapp-reports';
    
    if (!file_exists($whatsapp_dir)) {
        $mkdir_result = wp_mkdir_p($whatsapp_dir);
        
        // Create an empty index.php file for security
        $index_file = fopen($whatsapp_dir . '/index.php', 'w');
        fwrite($index_file, '<?php // Silence is golden');
        fclose($index_file);
    }
    
    // Generate a unique filename with the correct extension based on file type
    $extension = '.jpg'; // Default to jpg for better WhatsApp compatibility
    if (isset($_POST['file_type']) && !empty($_POST['file_type'])) {
        if (strpos($_POST['file_type'], 'png') !== false) {
            $extension = '.png';
        } elseif (strpos($_POST['file_type'], 'jpeg') !== false || strpos($_POST['file_type'], 'jpg') !== false) {
            $extension = '.jpg';
        }
    }
    
    $filename = 'report-' . time() . '-' . wp_generate_password(8, false) . $extension;
    $file_path = $whatsapp_dir . '/' . $filename;
    
    // Save the file
    $result = file_put_contents($file_path, $decoded_data);
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to save file'));
        wp_die();
    }
    
    // Get the URL for the saved file - use site_url() for more reliable URL generation
    $file_url = site_url('/wp-content/uploads/whatsapp-reports/' . $filename);
    
    // Make sure the URL uses https for security and compatibility with WhatsApp API
    $file_url = str_replace('http://', 'https://', $file_url);
    
    // Try to fetch the image via HTTP to verify it's accessible
    $test_request = wp_remote_get($file_url);
    
    wp_send_json_success(array(
        'message' => 'File uploaded successfully',
        'url' => $file_url,
        'path' => $file_path
    ));
    
    wp_die();
}

// Register the AJAX action
add_action('wp_ajax_handle_whatsapp_upload_file', 'handle_whatsapp_upload_file');
add_action('wp_ajax_nopriv_handle_whatsapp_upload_file', 'handle_whatsapp_upload_file');

// New AJAX handler to check if a report has already been sent via WhatsApp
add_action('wp_ajax_check_report_whatsapp_status', 'handle_check_report_whatsapp_status');
add_action('wp_ajax_nopriv_check_report_whatsapp_status', 'handle_check_report_whatsapp_status');

function handle_check_report_whatsapp_status() {
    $report_id = intval($_POST['report_id']);
    
    if (empty($report_id)) {
        wp_send_json_error(array('message' => 'No report ID provided'));
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'student_reports';
    
    // Check if the whatsapp_shared column exists
    $check_column = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'whatsapp_shared'");
    if (empty($check_column)) {
        wp_send_json_success(array('sent' => false, 'message' => 'WhatsApp status tracking not available'));
        return;
    }
    
    // Check if the report has been sent via WhatsApp
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT whatsapp_shared FROM {$table_name} WHERE id = %d",
        $report_id
    ));
    
    // Also check if there's a transient indicating the report was sent
    $transient_sent = get_transient('whatsapp_report_sent_' . $report_id);
    
    $was_sent = ($result == 1 || $transient_sent);
    
    wp_send_json_success(array(
        'sent' => $was_sent,
        'db_status' => ($result == 1),
        'transient_status' => ($transient_sent ? true : false)
    ));
}

// Function to handle temporary image uploads for WhatsApp
add_action('wp_ajax_upload_temp_whatsapp_image', 'handle_upload_temp_whatsapp_image');
add_action('wp_ajax_nopriv_upload_temp_whatsapp_image', 'handle_upload_temp_whatsapp_image');
function handle_upload_temp_whatsapp_image() {
    // Verify nonce
    // check_ajax_referer('zuwad_ajax_nonce', '_ajax_nonce');
    
    // Check if image was uploaded
    if (!isset($_FILES['image'])) {
        wp_send_json_error(array('message' => 'No image provided'));
        return;
    }
    
    // Get WordPress upload directory
    $upload_dir = wp_upload_dir();
    
    // Create a temporary directory for WhatsApp images if it doesn't exist
    $whatsapp_temp_dir = $upload_dir['basedir'] . '/whatsapp_temp';
    if (!file_exists($whatsapp_temp_dir)) {
        wp_mkdir_p($whatsapp_temp_dir);
    }
    
    // Generate a unique filename
    $filename = 'whatsapp_report_' . uniqid() . '.png';
    $file_path = $whatsapp_temp_dir . '/' . $filename;
    
    // Move the uploaded file to our temporary directory
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        // Get the URL for the uploaded file
        $file_url = $upload_dir['baseurl'] . '/whatsapp_temp/' . $filename;
        
        wp_send_json_success(array(
            'url' => $file_url,
            'path' => $file_path
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to upload image'));
    }
    
    wp_die();
}

// Function to delete temporary image after successful WhatsApp sending
add_action('wp_ajax_delete_temp_whatsapp_image', 'handle_delete_temp_whatsapp_image');
add_action('wp_ajax_nopriv_delete_temp_whatsapp_image', 'handle_delete_temp_whatsapp_image');
function handle_delete_temp_whatsapp_image() {
    // Verify nonce
    // check_ajax_referer('zuwad_ajax_nonce', '_ajax_nonce');
    
    // Check if image URL was provided
    if (!isset($_POST['image_url'])) {
        wp_send_json_error(array('message' => 'No image URL provided'));
        return;
    }
    
    $image_url = $_POST['image_url'];
    
    // Convert URL to file path
    $upload_dir = wp_upload_dir();
    $base_url = $upload_dir['baseurl'] . '/whatsapp_temp/';
    $base_dir = $upload_dir['basedir'] . '/whatsapp_temp/';
    
    // Extract filename from URL
    $filename = basename($image_url);
    $file_path = $base_dir . $filename;
    
    // Delete the file
    if (file_exists($file_path) && unlink($file_path)) {
        wp_send_json_success(array('message' => 'Image deleted successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete image'));
    }
    
    wp_die();
}

// Add AJAX handler to expose whatsapp config to JavaScript
add_action('wp_ajax_get_whatsapp_config', 'zuwad_get_whatsapp_config_ajax');
add_action('wp_ajax_nopriv_get_whatsapp_config', 'zuwad_get_whatsapp_config_ajax');

/**
 * AJAX handler for getting WhatsApp configuration
 * 
 * This function sends only the necessary configuration to the client
 * without exposing sensitive data like auth tokens.
 */
function zuwad_get_whatsapp_config_ajax() {
    // Verify nonce
    check_ajax_referer('zuwad_plugin_nonce', '_ajax_nonce');
    
    // Get full config
    $config = zuwad_get_whatsapp_config();
    
    // Only send safe parts to client
    $client_config = array(
        'device_id_01' => $config['device_id_01'],
        'device_id_02' => $config['device_id_02'],
    );
    
    wp_send_json_success($client_config);
}
