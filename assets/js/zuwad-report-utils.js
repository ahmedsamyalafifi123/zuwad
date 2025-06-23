/**
 * Zuwad Academy Report Utilities
 * 
 * This module provides shared functionality for report generation and WhatsApp sending
 * to be used across different pages in the Zuwad Academy system.
 * 
 * @author Zuwad Academy Team
 * @version 1.0.0
 */
(function(window, document, $, undefined) {
  'use strict';

  // Shared configuration constants
  const CONFIG = {
    REPORT_TEMPLATE_URL: 'https://system.zuwad-academy.com/wp-content/uploads/2025/01/تقرير-الطالب-زواد-10-scaled.webp',
    // Auth token will be fetched from server
    DOMAIN: 'https://system.zuwad-academy.com/',
    CANVAS: {
      WIDTH: 1240,
      HEIGHT: 1754
    },
    DEVICE_ID_01: 'device_id_01',
    DEVICE_ID_02: 'device_id_02'
  };

  // Fetch WhatsApp configuration from server
  function fetchWhatsAppConfig() {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: zuwadPlugin.ajaxurl,
        type: 'POST',
        data: {
          action: 'get_whatsapp_config',
          _ajax_nonce: zuwadPlugin.nonce
        },
        success: function(response) {
          if (response.success && response.data) {
            // Store the message caption if provided
            if (response.data.message_caption) {
              CONFIG.WHATSAPP_MESSAGE_CAPTION = response.data.message_caption;
            }
            // Store the device IDs if provided
            if (response.data.device_id_01) {
              CONFIG.DEVICE_ID_01 = response.data.device_id_01;
            }
            if (response.data.device_id_02) {
              CONFIG.DEVICE_ID_02 = response.data.device_id_02;
            }
            resolve(response.data);
          } else {
            console.error('Failed to fetch WhatsApp config:', response);
            reject(new Error('Failed to fetch WhatsApp configuration'));
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX error when fetching WhatsApp config:', error);
          reject(new Error('AJAX error: ' + error));
        }
      });
    });
  }

  /**
   * Report Generator - Handles report creation and rendering
   */
  const ReportGenerator = {
    /**
     * Extracts zoom image URL from report data
     * @param {Object} reportData - The report data object
     * @returns {string|null} The formatted zoom image URL or null
     */
    extractZoomImageUrl: function(reportData) {
      if (!reportData.zoom_image_url) return null;
      
      try {
        const wpContentIndex = reportData.zoom_image_url.indexOf("wp-content");
        if (wpContentIndex === -1) return null;
        
        const urlSubstring = reportData.zoom_image_url.substring(wpContentIndex);
        const endIndex = urlSubstring.indexOf('"');
        if (endIndex === -1) return null;
        
        const urlPath = urlSubstring.substring(0, endIndex);
        const zoomImageUrl = `${CONFIG.DOMAIN}${urlPath}`;
        
        // Decode the URL to handle Arabic characters
        return JSON.parse(`"${zoomImageUrl}"`);
      } catch (error) {
        console.error("Error extracting zoom_image_url:", error);
        return null;
      }
    },
    
    /**
     * Creates HTML content for the report
     * @param {Object} reportData - The report data object
     * @param {string|null} zoomImageUrl - The formatted zoom image URL
     * @returns {string} The HTML content
     */
    createReportHTML: function(reportData, zoomImageUrl) {
      return `
        <!DOCTYPE html>
        <html lang="ar">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>إنجاز اليوم</title>
            <style>
                @font-face {
                    font-family: 'Cairo';
                    src: url('../fonts/Cairo.ttf') format('truetype');
                    font-style: normal;
                }

                body {
                    margin: 0;
                    padding: 0;
                    height: 100vh;
                    direction: rtl; /* Set right-to-left layout */
                    overflow: hidden; /* Prevent scrollbars */
                    font-family: 'Cairo', sans-serif; /* Apply Cairo font */
                }
                .container {
                    background: url('${CONFIG.REPORT_TEMPLATE_URL}');
                    background-size: cover;
                    background-repeat: no-repeat;
                    position: relative;
                    width: ${CONFIG.CANVAS.WIDTH}px;
                    height: ${CONFIG.CANVAS.HEIGHT}px;
                    margin: 0 auto;
                }
                .field {
                    position: absolute;
                    font-size: 26px;
                    color: black;
                    font-family: 'Cairo', sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    text-align: center;
                    flex-wrap: wrap;
                    word-wrap: break-word;
                    word-spacing: 0.5em !important;
                    white-space: normal;
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }
                .student-name { top: 370px; left: 200px; color:#cd9900; font-weight: bold; width: 300px; height: 50px; max-height: 75px; overflow: hidden; }
                .evaluation { top: 420px; left: 235px; width: 300px; height: 50px; max-height: 50px; overflow: hidden; }
                .session-number { top: 477px; left: 470px; width: 50px; height: 40px; max-height: 40px; overflow: hidden; }

                .previous-review { top: 845px; left: 75px; width: 320px; height: 100px; max-height: 100px; overflow: hidden; }
                .previous-memorization { top: 845px; left: 460px; width: 320px; height: 100px; max-height: 100px; overflow: hidden; }
                .previous-recitation { top: 845px; right: 80px; width: 320px; height: 100px; max-height: 100px; overflow: hidden; }
                .upcoming-review { top: 1097px; left: 230px; width: 320px; height: 100px; max-height: 100px; overflow: hidden; }
                .upcoming-recitation { top: 1097px; right: 245px; width: 320px; height: 100px; max-height: 100px; overflow: hidden; }
                .notes { bottom: 163px; right: 164px; width: 350px; height: 235px; padding: 10px; box-sizing: border-box; max-height: 235px; overflow: hidden; }
                .zoom-screenshot { bottom: 144px; left: 120px; width: 369px; height: 272px; display: block; }
                .zoom-screenshot img { width: 100%; height: 100%; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="field student-name">${reportData.student_name}</div>
                <div class="field evaluation">${reportData.evaluation}</div>
                <div class="field session-number">${
                  !reportData.session_number ||
                  reportData.session_number === "0"
                    ? "-"
                    : reportData.session_number
                }</div>
                <div class="field previous-review">${reportData.mourajah}</div>
                <div class="field previous-memorization">${reportData.tahfiz}</div>
                <div class="field previous-recitation">${reportData.tasmii}</div>
                <div class="field upcoming-review">${reportData.next_mourajah}</div>
                <div class="field upcoming-recitation">${reportData.next_tasmii}</div>
                <div class="field notes">${reportData.notes}</div>
                <div class="field zoom-screenshot">
                    ${
                      zoomImageUrl
                        ? `<img src="${zoomImageUrl}" alt="Zoom Screenshot" style="width: 100%; height: 100%;"/>`
                        : "صورة من حصة الزووم غير متوفرة"
                    }
                </div>
            </div>
        </body>
        </html>
      `;
    },
    
    /**
     * Renders the report in an iframe and captures it as an image
     * @param {Object} reportData - The report data object
     * @param {Object} options - Optional configuration for rendering
     * @returns {Promise<Object>} Promise resolving to canvas, PDF, and image data
     */
    renderReport: function(reportData, options = {}) {
      return new Promise((resolve, reject) => {
        try {
          const zoomImageUrl = this.extractZoomImageUrl(reportData);
          
          // Create a hidden iframe to render the report
          const iframe = document.createElement("iframe");
          iframe.style.position = "absolute";
          iframe.style.top = "-9999px";
          iframe.style.left = "-9999px";
          iframe.style.width = `${CONFIG.CANVAS.WIDTH}px`;
          iframe.style.height = `${CONFIG.CANVAS.HEIGHT}px`;
          iframe.style.border = "none";
          document.body.appendChild(iframe);
          
          // Write the HTML content to the iframe
          const htmlContent = this.createReportHTML(reportData, zoomImageUrl);
          iframe.contentDocument.open();
          iframe.contentDocument.write(htmlContent);
          iframe.contentDocument.close();
          
          // Use html2canvas to capture the content as an image
          html2canvas(iframe.contentDocument.body, {
            scale: 1,
            width: CONFIG.CANVAS.WIDTH,
            height: CONFIG.CANVAS.HEIGHT,
            logging: options.debug || false,
            useCORS: true,
            allowTaint: true,
            backgroundColor: null,
          }).then(function(canvas) {
            document.body.removeChild(iframe);
            
            // Create PDF from canvas
            const pdf = new jspdf.jsPDF("p", "mm", "a4");
            const imgWidth = 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            const imgData = canvas.toDataURL("image/png", 1.0);
            
            pdf.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight, undefined, "FAST");
            
            // Create optimized versions for WhatsApp
            const smallCanvas = document.createElement('canvas');
            const ctx = smallCanvas.getContext('2d');
            // Use a good size that maintains quality but works with WhatsApp API
            smallCanvas.width = 800;
            smallCanvas.height = 1130;
            // Draw the original canvas into the smaller canvas
            ctx.drawImage(canvas, 0, 0, smallCanvas.width, smallCanvas.height);
            
            resolve({
              canvas: canvas,
              smallCanvas: smallCanvas,
              pdf: pdf,
              imageDataUrl: canvas.toDataURL("image/png", 1.0),
              optimizedImageDataUrl: smallCanvas.toDataURL("image/jpeg", 0.85),
              pdfDataUrl: pdf.output("datauristring")
            });
          }).catch(function(error) {
            document.body.removeChild(iframe);
            reject(error);
          });
        } catch (error) {
          reject(error);
        }
      });
    },
    
    /**
     * Fetch report data from the server with retry logic
     * @param {string} reportId - The report ID
     * @param {number} maxRetries - Maximum number of retry attempts (default: 5)
     * @returns {Promise<Object>} Promise resolving to the report data
     */
    fetchReportData: function(reportId, maxRetries = 5) {
      const self = this;
      let lastErrorMessage = "";

      function attemptFetch(attempt = 1) {
        return new Promise((resolve, reject) => {
          $.ajax({
            url: zuwadPlugin.ajaxurl,
            type: "POST",
            data: {
              action: "get_report_data",
              report_id: reportId,
              _ajax_nonce: zuwadPlugin.nonce,
            },
            timeout: 15000, // 15 second timeout
            success: function(response) {
              if (response.success) {
                console.log(`Report data fetched successfully on attempt ${attempt}`);
                resolve(response.data);
              } else {
                const errorMessage = response.data || "Failed to fetch report data";
                lastErrorMessage = errorMessage;

                // Check if this is a "report not found" error and we have retries left
                if (errorMessage.includes("No report data found") && attempt < maxRetries) {
                  console.log(`Report not found on attempt ${attempt}, retrying in ${attempt * 2} seconds...`);

                  // Wait progressively longer between retries: 2s, 4s, 6s, 8s, 10s
                  setTimeout(() => {
                    attemptFetch(attempt + 1).then(resolve).catch(reject);
                  }, attempt * 2000);
                } else {
                  reject({
                    error: new Error(errorMessage),
                    isProcessing: errorMessage.includes("No report data found") || errorMessage.includes("still be processing"),
                    attempt: attempt
                  });
                }
              }
            },
            error: function(xhr, status, error) {
              console.error(`AJAX Error on attempt ${attempt}:`, xhr.responseText);
              lastErrorMessage = xhr.responseText || error || "Network error";

              // Handle timeout specifically
              if (status === "timeout") {
                lastErrorMessage = "تجاوز وقت الاتصال بالخادم";
              }

              if (attempt < maxRetries) {
                console.log(`Network error on attempt ${attempt}, retrying in ${attempt * 2} seconds...`);
                setTimeout(() => {
                  attemptFetch(attempt + 1).then(resolve).catch(reject);
                }, attempt * 2000);
              } else {
                reject({
                  error: new Error(lastErrorMessage || "Error fetching report data"),
                  isNetworkError: true,
                  attempt: attempt
                });
              }
            },
          });
        });
      }

      return attemptFetch();
    }
  };

  /**
   * WhatsApp Sender - Handles sending reports via WhatsApp
   */
  const WhatsAppSender = {
    /**
     * Determine device ID to use based on m_id
     * @param {string} m_id - The student m_id
     * @returns {string} The device ID to use
     */
    determineDeviceId: function(m_id) {
      if (!m_id || !m_id.toString()) {
        console.error("No m_id provided, using default device ID");
        return CONFIG.DEVICE_ID_01;
      }
      
      const m_id_prefix = m_id.toString().substring(0, 2);
      
      // Updated logic to select the correct device ID:
      // If prefix is '01', use device_id_01
      // If prefix is '02', use device_id_02
      // Otherwise, show error and use device_id_01 as fallback
      if (m_id_prefix === '01') {
        console.log("Using device_id_01 for m_id (01 prefix):", m_id);
        return CONFIG.DEVICE_ID_01;
      } else if (m_id_prefix === '02') {
        console.log("Using device_id_02 for m_id (02 prefix):", m_id);
        return CONFIG.DEVICE_ID_02;
      } else {
        console.error("Invalid m_id prefix:", m_id_prefix, "Must start with 01 or 02");
        return CONFIG.DEVICE_ID_01; // Use device_id_01 as fallback
      }
    },
    
    /**
     * Check if a report has already been sent via WhatsApp
     * @param {string} reportId - The report ID to check
     * @returns {Promise<boolean>} Promise resolving to true if already sent
     */
    checkIfReportAlreadySent: function(reportId) {
      return new Promise((resolve, reject) => {
        if (!reportId) {
          resolve(false);
          return;
        }
        
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          type: "POST",
          data: {
            action: "check_report_whatsapp_status",
            report_id: reportId,
            _ajax_nonce: zuwadPlugin.nonce,
          },
          success: function(response) {
            if (response.success && response.data && response.data.sent) {
              console.log("Report was already sent via WhatsApp:", reportId);
              resolve(true);
            } else {
              resolve(false);
            }
          },
          error: function() {
            console.error("Error checking if report was already sent");
            resolve(false); // Default to not sent on error
          }
        });
      });
    },
    
    /**
     * Get student phone number from server
     * @param {string} studentId - The student ID
     * @returns {Promise<string>} Promise resolving to the student phone number
     */
    getStudentPhone: function(studentId) {
      return new Promise((resolve, reject) => {
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          type: "POST",
          data: {
            action: "get_student_phone",
            student_id: studentId,
            _ajax_nonce: zuwadPlugin.nonce,
          },
          success: function(response) {
            if (response.success && response.data && response.data.phone) {
              resolve(response.data.phone);
            } else {
              reject(new Error("Failed to get student phone number"));
            }
          },
          error: function(xhr, status, error) {
            console.error("Failed to get student phone:", error);
            reject(new Error(error || "Error fetching student phone"));
          },
        });
      });
    },
    
    /**
     * Send a report via WhatsApp using base64 image data
     * @param {string} imageDataUrl - Base64 image data URL
     * @param {string} studentPhone - Student phone number
     * @param {string} m_id - Student m_id (kept for backward compatibility)
     * @param {string} reportId - Report ID for tracking to prevent duplicate sends
     * @returns {Promise<Object>} Promise resolving to the WhatsApp send response
     */
    sendWithBase64: function(imageDataUrl, studentPhone, m_id, reportId) {
      const self = this;
      const base64Data = imageDataUrl.split(',')[1];
      
      // First check if report was already sent
      return this.checkIfReportAlreadySent(reportId)
        .then(alreadySent => {
          if (alreadySent) {
            console.log("Preventing duplicate send - report already sent:", reportId);
            return {
              success: true,
              already_sent: true,
              message: "Report already sent via WhatsApp"
            };
          }
          
          // Format phone number - ensure it starts with +
          if (studentPhone && studentPhone.charAt(0) !== '+') {
            studentPhone = '+' + studentPhone;
          }
          
          // Determine the proper device ID based on m_id
          const deviceId = this.determineDeviceId(m_id);
          
          // Log the data being sent
      console.log("WhatsApp Send Data:", {
            phone: studentPhone,
        m_id: m_id,
            device_id: deviceId,
            report_id: reportId
      });
      
      // First fetch WhatsApp config
      return fetchWhatsAppConfig()
        .then(config => {
              // We need to first upload the base64 data to get a URL
              // Use FileUtils to upload the image
              return FileUtils.uploadTemporaryImage(imageDataUrl)
                .then(mediaUrl => {
          return new Promise((resolve, reject) => {
                    // Add a timestamp to help prevent caching 
                    const timestampParam = "t=" + new Date().getTime();
                    const mediaUrlWithTimestamp = mediaUrl + (mediaUrl.includes('?') ? '&' : '?') + timestampParam;
            
            $.ajax({
              url: zuwadPlugin.ajaxurl,
              type: "POST",
              data: {
                action: "send_whatsapp_report",
                phone_number: studentPhone,
                        media_url: mediaUrlWithTimestamp,
                        message: CONFIG.WHATSAPP_MESSAGE_CAPTION || '',
                        m_id: m_id, // Include m_id to determine the device ID on the server side
                        report_id: reportId, // Include report_id to prevent duplicate sends
                _ajax_nonce: zuwadPlugin.nonce,
              },
              success: function(response) {
                console.log("WhatsApp API Response:", response);
                
                        // Check if the API request was successful or if message was already sent
                        if (response.success && (!response.has_inner_error || response.already_sent)) {
                  resolve(response);
                } else {
                  const errorMessage = extractErrorMessage(response);
                  reject(new Error(errorMessage));
                }
              },
              error: function(xhr, status, error) {
                console.error("WhatsApp API Error:", {
                  status: status,
                  error: error,
                  response: xhr.responseText,
                });
                reject(new Error(error || "Error sending WhatsApp message"));
              },
                    });
            });
          });
        })
        .catch(error => {
              console.error("Error in WhatsApp sending workflow:", error);
              
              // Fallback to direct AJAX with base64 data as a last resort
          return new Promise((resolve, reject) => {
                // We need to upload the image to get a URL first
                return FileUtils.uploadTemporaryImage(imageDataUrl)
                  .then(mediaUrl => {
                    // Add a timestamp to help prevent caching
                    const timestampParam = "t=" + new Date().getTime();
                    const mediaUrlWithTimestamp = mediaUrl + (mediaUrl.includes('?') ? '&' : '?') + timestampParam;
                    
            $.ajax({
              url: zuwadPlugin.ajaxurl,
              type: "POST",
              data: {
                action: "send_whatsapp_report",
                phone_number: studentPhone,
                        media_url: mediaUrlWithTimestamp,
                        message: CONFIG.WHATSAPP_MESSAGE_CAPTION || '',
                        m_id: m_id, // Include m_id to determine the device ID on the server side
                        report_id: reportId, // Include report_id to prevent duplicate sends
                _ajax_nonce: zuwadPlugin.nonce,
              },
              success: function(response) {
                console.log("WhatsApp API Response (fallback):", response);
                
                        if (response.success && (!response.has_inner_error || response.already_sent)) {
                  resolve(response);
                } else {
                  const errorMessage = extractErrorMessage(response);
                  reject(new Error(errorMessage));
                }
              },
              error: function(xhr, status, error) {
                console.error("WhatsApp API Error (fallback):", error);
                reject(new Error(error || "Error sending WhatsApp message"));
              },
                    });
                  })
                  .catch(uploadError => {
                    console.error("Failed to upload image:", uploadError);
                    Swal.fire({
                      icon: "error",
                      title: "فشل في إعادة الإرسال",
                      text: "فشل في تحميل الصورة: " + uploadError.message,
                      confirmButtonText: "حسناً",
                    });
                  });
            });
          });
        });
    },
    
    /**
     * Update report WhatsApp status
     * @param {string} reportId - The report ID
     * @returns {Promise<Object>} Promise resolving to the update status response
     */
    updateReportStatus: function(reportId) {
      return new Promise((resolve, reject) => {
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          type: "POST",
          data: {
            action: "update_report_whatsapp_status",
            report_id: reportId,
            _ajax_nonce: zuwadPlugin.nonce,
          },
          success: function(response) {
            if (response.success) {
              resolve(response);
            } else {
              reject(new Error("Failed to update report status"));
            }
          },
          error: function(xhr, status, error) {
            console.error("Failed to update report status:", error);
            reject(new Error(error || "Error updating report status"));
          },
        });
      });
    },
    
    /**
     * Complete workflow to send a report via WhatsApp
     * @param {string} reportId - The report ID
     * @param {string} imageDataUrl - Image data URL to send
     * @param {Object} options - Options for sending
     * @returns {Promise<Object>} Promise resolving when the report is sent
     */
    sendReport: function(reportId, imageDataUrl, options = {}) {
      const self = this;
      const showLoading = options.showLoading !== false;
      const useSmallImage = options.useSmallImage !== false;
      
      if (showLoading) {
        Swal.fire({
          title: "جارٍ الإرسال...",
          text: "يرجى الانتظار بينما يتم إرسال التقرير",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });
      }
      
      return new Promise((resolve, reject) => {
        // 1. Fetch report data
        ReportGenerator.fetchReportData(reportId)
          .then(function(reportData) {
            const studentId = reportData.student_id;
            const m_id = reportData.m_id || '';
            
            if (!studentId) {
              throw new Error("No student ID found in report data");
            }
            
            // 2. Get student phone number
            return self.getStudentPhone(studentId)
              .then(function(studentPhone) {
                // 3. Send WhatsApp message
                return self.sendWithBase64(imageDataUrl, studentPhone, m_id, reportId)
                  .then(function(sendResponse) {
                    // Check if this was a duplicate send
                    if (sendResponse.already_sent) {
                      console.log("Report was already sent, skipping status update");
                      
                      // 5. Refresh calendar if available
                      if (typeof calendar !== 'undefined') {
                        calendar.refetchEvents();
                      }
                      
                      // 6. Return success with already_sent flag
                      resolve({
                        success: true,
                        already_sent: true,
                        reportData: reportData,
                        sendResponse: sendResponse
                      });
                      
                      if (showLoading) {
                        Swal.fire({
                          icon: "success",
                          title: "تم الإرسال بنجاح",
                          text: "تم إرسال التقرير بالفعل مسبقاً",
                          confirmButtonText: "حسناً",
                        });
                      }
                      
                      return;
                    }
                    
                    // 4. Update report status
                    return self.updateReportStatus(reportId)
                      .then(function() {
                        // 5. Refresh calendar if available
                        if (typeof calendar !== 'undefined') {
                          calendar.refetchEvents();
                        }
                        
                        // 6. Return success
                        resolve({
                          success: true,
                          reportData: reportData,
                          sendResponse: sendResponse
                        });
                        
                        if (showLoading) {
                          Swal.fire({
                            icon: "success",
                            title: "تم الإرسال بنجاح",
                            text: "تم إرسال التقرير عبر الواتساب بنجاح",
                            confirmButtonText: "حسناً",
                          });
                        }
                      });
                  });
              });
          })
          .catch(function(error) {
            console.error("Error in send report workflow:", error);
            
            if (showLoading) {
              Swal.fire({
                icon: "error",
                title: "خطأ في الإرسال",
                text: error.message || "حدث خطأ أثناء إرسال التقرير",
                confirmButtonText: "حسناً",
              });
            }
            
            reject(error);
          });
      });
    },
    
    /**
     * Send with retries if the initial attempt fails
     * @param {string} reportId - The report ID
     * @param {Object} renderData - The rendered report data
     * @param {Object} options - Options for sending
     * @returns {Promise<Object>} Promise resolving when the report is sent
     */
    sendWithRetry: function(reportId, renderData, options = {}) {
      const self = this;
      
      if (options.showLoading !== false) {
        Swal.fire({
          title: "جارٍ الإرسال...",
          text: "يرجى الانتظار بينما يتم إرسال التقرير",
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });
      }
      
      // Simple approach - just try to send once
      return ReportGenerator.fetchReportData(reportId)
        .then(function(reportData) {
          // Get the student ID and phone number
          const studentId = reportData.student_id;
          const m_id = reportData.m_id || '';
          
          if (!studentId) {
            throw new Error("No student ID found in report data");
          }
          
          // Get student phone number
          return self.getStudentPhone(studentId)
            .then(function(studentPhone) {
              // Send the message
              return $.ajax({
                url: zuwadPlugin.ajaxurl,
                type: "POST",
                data: {
                  action: "send_whatsapp_report",
                  phone_number: studentPhone.charAt(0) !== '+' ? '+' + studentPhone : studentPhone,
                  media_url: renderData.optimizedImageDataUrl || renderData.imageDataUrl,
                  message: CONFIG.WHATSAPP_MESSAGE_CAPTION || '',
                  m_id: m_id,
                  report_id: reportId,
                  _ajax_nonce: zuwadPlugin.nonce,
                },
                success: function(response) {
                  console.log("WhatsApp send response:", response);
                  
                  // Always close any existing SweetAlert
                  Swal.close();
                  
                  if (response.success) {
                    // Check if the report was already sent
                    const isAlreadySent = response.already_sent || (response.data && response.data.already_sent);
                    
                    if (isAlreadySent) {
                      // For already sent reports, show option to send again
                      Swal.fire({
                        icon: "info",
                        title: "تم إرسال التقرير مسبقاً",
                        text: "هذا التقرير تم إرساله بالفعل. هل تريد إعادة إرساله؟",
                        showCancelButton: true,
                        confirmButtonText: "نعم، إرسال مرة أخرى",
                        cancelButtonText: "لا، شكراً",
                        reverseButtons: true
                      }).then((result) => {
                        if (result.isConfirmed) {
                          // User wants to send again - first re-upload the image to get a fresh URL
                          Swal.fire({
                            title: "جارٍ إعادة الإرسال...",
                            text: "يرجى الانتظار بينما يتم إرسال التقرير مرة أخرى",
                            allowOutsideClick: false,
                            didOpen: () => {
                              Swal.showLoading();
                            },
                          });
                          
                          // We need to upload the image again to get a fresh URL
                          FileUtils.uploadTemporaryImage(renderData.optimizedImageDataUrl || renderData.imageDataUrl)
                            .then(mediaUrl => {
                              // Add a timestamp to prevent caching
                              const timestampParam = "t=" + new Date().getTime();
                              const mediaUrlWithTimestamp = mediaUrl + (mediaUrl.includes('?') ? '&' : '?') + timestampParam;
                              
                              $.ajax({
                                url: zuwadPlugin.ajaxurl,
                                type: "POST",
                                data: {
                                  action: "send_whatsapp_report",
                                  phone_number: studentPhone.charAt(0) !== '+' ? '+' + studentPhone : studentPhone,
                                  media_url: mediaUrlWithTimestamp,
                                  message: CONFIG.WHATSAPP_MESSAGE_CAPTION || '',
                                  m_id: m_id,
                                  report_id: reportId,
                                  force_resend: true,
                                  _ajax_nonce: zuwadPlugin.nonce,
                                },
                                success: function(resendResponse) {
                                  if (resendResponse.success) {
                                    Swal.fire({
                                      icon: "success",
                                      title: "تم إعادة الإرسال بنجاح",
                                      text: "تم إرسال التقرير مرة أخرى عبر الواتساب",
                                      confirmButtonText: "حسناً",
                                    });
                                    
                                    // Update calendar if available
                                    if (typeof calendar !== 'undefined') {
                                      calendar.refetchEvents();
                                    }
                                  } else {
                                    Swal.fire({
                                      icon: "error",
                                      title: "فشل إعادة الإرسال",
                                      text: resendResponse.data?.message || "فشل في إعادة إرسال التقرير",
                                      confirmButtonText: "حسناً",
                                    });
                                  }
                                },
                                error: function() {
                                  Swal.fire({
                                    icon: "error",
                                    title: "خطأ في الاتصال",
                                    text: "فشل الاتصال بالخادم أثناء إعادة إرسال التقرير",
                                    confirmButtonText: "حسناً",
                                  });
                                }
                              });
                            })
                            .catch(uploadError => {
                              console.error("Failed to upload image:", uploadError);
                              Swal.fire({
                                icon: "error",
                                title: "فشل في إعادة الإرسال",
                                text: "فشل في تحميل الصورة: " + uploadError.message,
                                confirmButtonText: "حسناً",
                              });
                            });
                        }
                      });
                    } else {
                      // Regular success message for first-time sends
                      Swal.fire({
                        icon: "success",
                        title: "تم الإرسال بنجاح",
                        text: "تم إرسال التقرير بنجاح عبر واتساب",
                        confirmButtonText: "حسناً",
                      });
                    }
                    
                    // Update calendar if available
                    if (typeof calendar !== 'undefined') {
                      calendar.refetchEvents();
                    }
                    
                    return {
                      success: true,
                      reportData: reportData,
                      already_sent: isAlreadySent
                    };
                  } else {
                    Swal.fire({
                      icon: "error",
                      title: "فشل الإرسال",
                      text: response.data?.message || "فشل في إرسال التقرير عبر الواتساب",
                      confirmButtonText: "حسناً",
                    });
                    
                    throw new Error(response.data?.message || "Failed to send WhatsApp message");
                  }
                },
                error: function(xhr, status, error) {
                  console.error("AJAX Error:", xhr.responseText);
                  
                  // Always close any existing SweetAlert
                  Swal.close();
                  
                  Swal.fire({
                    icon: "error",
                    title: "خطأ في الاتصال",
                    text: "فشل الاتصال بالخادم أثناء إرسال التقرير",
                    confirmButtonText: "حسناً",
                  });
                  
                  throw new Error("AJAX Error: " + error);
                }
              });
            });
        })
        .catch(function(error) {
          console.error("Error in WhatsApp send process:", error);
          
          // Always close any existing SweetAlert
          Swal.close();
          
          Swal.fire({
            icon: "error",
            title: "خطأ في الإرسال",
            text: error.message || "حدث خطأ أثناء إرسال التقرير",
            confirmButtonText: "حسناً",
          });
          
          throw error;
        });
    }
  };

  /**
   * File Utilities - Handles file operations
   */
  const FileUtils = {
    /**
     * Remove temporary image
     * @param {string} imageUrl - The image URL to remove
     * @returns {Promise<Object>} Promise resolving when the image is removed
     */
    removeTemporaryImage: function(imageUrl) {
      if (!imageUrl) return Promise.resolve(null);
      
      return new Promise((resolve, reject) => {
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          type: "POST",
          data: {
            action: "remove_temp_image",
            image_url: imageUrl,
            remove_from_media: true,
            _ajax_nonce: zuwadPlugin.nonce,
          },
          success: function(response) {
            console.log("Temporary image removal response:", response);
            resolve(response);
          },
          error: function(xhr, status, error) {
            console.error("Failed to remove temporary image:", error);
            reject(new Error(error || "Error removing temporary image"));
          },
        });
      });
    },
    
    /**
     * Upload a generated image to server for WhatsApp
     * @param {string} imageDataUrl - The image data URL
     * @returns {Promise<string>} Promise resolving to the uploaded image URL
     */
    uploadTemporaryImage: function(imageDataUrl) {
      // Convert data URL to blob
      const blob = dataURItoBlob(imageDataUrl);
      const formData = new FormData();
      formData.append('action', 'upload_temp_whatsapp_image');
      formData.append('image', blob, 'report_image.png');
      formData.append('_ajax_nonce', zuwadPlugin.nonce);
      
      return new Promise((resolve, reject) => {
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(response) {
            if (response.success && response.data && response.data.url) {
              resolve(response.data.url);
            } else {
              reject(new Error("Failed to upload image"));
            }
          },
          error: function(xhr, status, error) {
            console.error("Image upload error:", error);
            reject(new Error(error || "Error uploading image"));
          }
        });
      });
    }
  };

  /**
   * Permission Checker - Handles permission checks
   */
  const PermissionChecker = {
    /**
     * Check if WhatsApp button should be hidden based on user role
     * @returns {boolean} True if button should be hidden, false otherwise
     */
    shouldHideWhatsAppButton: function() {
      return zuwadPlugin.userRole === "teacher";
    }
  };

  /**
   * Helper Functions
   */
  
  /**
   * Extract error message from response
   * @param {Object} response - The error response
   * @returns {string} The extracted error message
   */
  function extractErrorMessage(response) {
    let errorMessage = "حدث خطأ أثناء إرسال التقرير";
    
    if (response.data && response.data.response) {
      // First check for inner error in data.response.data
      if (response.data.response.data && response.data.response.data.status === "error") {
        if (response.data.response.data.message) {
          errorMessage = response.data.response.data.message;
        } else if (response.data.response.data.explanation) {
          errorMessage = response.data.response.data.explanation;
        }
      }
      // Then check other possible error locations
      else if (response.data.response.message) {
        errorMessage = response.data.response.message;
      } else if (response.data.response.explanation) {
        errorMessage = response.data.response.explanation;
      }
    } else if (response.data && response.data.message) {
      errorMessage = response.data.message;
    }
    
    return errorMessage;
  }
  
  /**
   * Convert data URI to Blob
   * @param {string} dataURI - The data URI
   * @returns {Blob} The blob object
   */
  function dataURItoBlob(dataURI) {
    // Convert base64/URLEncoded data component to raw binary data held in a string
    let byteString;
    if (dataURI.split(',')[0].indexOf('base64') >= 0) {
      byteString = atob(dataURI.split(',')[1]);
    } else {
      byteString = decodeURIComponent(dataURI.split(',')[1]);
    }

    // Separate out the mime component
    const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

    // Write the bytes of the string to a typed array
    const ia = new Uint8Array(byteString.length);
    for (let i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
    }

    return new Blob([ia], {type: mimeString});
  }

  // Export the modules to window
  window.ZuwadReportUtils = {
    CONFIG: CONFIG,
    ReportGenerator: ReportGenerator,
    WhatsAppSender: WhatsAppSender,
    FileUtils: FileUtils,
    PermissionChecker: PermissionChecker
  };

})(window, document, jQuery); 