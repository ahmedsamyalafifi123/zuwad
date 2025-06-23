/**
 * WhatsApp API Media Sender JavaScript
 */
jQuery(document).ready(function ($) {
    // Variables
    let sending = false;
    let imageBase64 = "";
    let visibleTeachers = new Set();
    let allTeachersIds = [];
    let currentPage = 1;
    let sendingStatus = 'running';
  
    // Debug waapiData
    // console.log('waapiData:', waapiData);
  
    // Configure SweetAlert2 defaults
    const Toast = Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener("mouseenter", Swal.stopTimer);
        toast.addEventListener("mouseleave", Swal.resumeTimer);
      },
    });

    // Initialize teacher filter
    function initializeTeacherFilter() {
        // Toggle dropdown when clicking on the header
        $("#teacher-filter-wrapper .dropdown-header").off("click").on("click", function(e) {
            e.stopPropagation();
            $("#teacher-filter-wrapper .dropdown-content").toggle();
        });
        
        // Handle item clicks
        $("#teacher-filter-wrapper .dropdown-item").off("click").on("click", function(e) {
            e.stopPropagation();
            const value = $(this).data("value");
            
            console.log('Clicked filter item:', value);
            
            if (value === "show_all") {
                // Show all teachers
                visibleTeachers.clear();
                allTeachersIds.forEach(id => {
                    visibleTeachers.add(id);
                });
                
                $("#teacher-filter-wrapper .teacher-item").each(function() {
                    const teacherName = $(this).text().replace("✔️ ", "").trim();
                    $(this).text("✔️ " + teacherName);
                    $(this).data("visible", "1");
                });
                
            } else if (value === "hide_all") {
                // Hide all teachers
                visibleTeachers.clear();
                
                $("#teacher-filter-wrapper .teacher-item").each(function() {
                    const teacherName = $(this).text().replace("✔️ ", "").trim();
                    $(this).text(teacherName);
                    $(this).data("visible", "0");
                });
                
            } else {
                // Toggle individual teacher
                const isVisible = $(this).data("visible") === "1";
                const teacherName = $(this).text().replace("✔️ ", "").trim();
                
                if (isVisible) {
                    // Currently visible, so hide it
                    $(this).text(teacherName);
                    $(this).data("visible", "0");
                    visibleTeachers.delete(value.toString());
                } else {
                    // Currently hidden, so show it
                    $(this).text("✔️ " + teacherName);
                    $(this).data("visible", "1");
                    visibleTeachers.add(value.toString());
                }
            }
            
            // Update the header text to show selected count
            updateHeaderText();
            
            // Log the current state for debugging
            console.log('Selected teachers:', Array.from(visibleTeachers));
            console.log('Number of selected teachers:', visibleTeachers.size);
            
            // Get student count for selected teachers
            $.ajax({
                url: waapiData.ajaxurl,
                type: "POST",
                data: {
                    action: "waapi_get_student_count",
                    visible_teachers: Array.from(visibleTeachers),
                    _ajax_nonce: waapiData.nonce
                },
                success: function(response) {
                    console.log('Student count response:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Error getting student count:', error);
                }
            });
            
            return false;
        });
        
        // Get all teacher IDs and mark them as visible initially
        $("#teacher-filter-wrapper .teacher-item").each(function() {
            const value = $(this).data("value").toString();
            allTeachersIds.push(value);
            visibleTeachers.add(value);
            
            // Make sure the UI shows the check mark
            const teacherName = $(this).text().replace("✔️ ", "").trim();
            $(this).text("✔️ " + teacherName);
            $(this).data("visible", "1");
        });
        
        // Update header text initially
        updateHeaderText();
        
        // Close dropdown when clicking outside
        $(document).on("click", function(e) {
            if (!$(e.target).closest("#teacher-filter-wrapper").length) {
                $("#teacher-filter-wrapper .dropdown-content").hide();
            }
        });
    }

    // Function to update header text
    function updateHeaderText() {
        const selectedCount = visibleTeachers.size;
        const totalCount = allTeachersIds.length;
        let headerText = "تحديد المعلمين";
        
        if (selectedCount === totalCount) {
            headerText = "تم تحديد جميع المعلمين";
        } else if (selectedCount > 0) {
            headerText = `تم تحديد ${selectedCount} معلم`;
            if (selectedCount > 2) {
                headerText += "ين";
            }
        }
        
        $("#teacher-filter-wrapper .dropdown-header span:first").text(headerText);
    }

    // Initialize the teacher filter
    initializeTeacherFilter();
  
    // Handle image preview
    $("#waapi-image").on("change", function () {
      const file = this.files[0];
      if (file) {
        if (!file.type.match("image.*")) {
          Toast.fire({
            icon: "error",
            title: "يرجى اختيار ملف صورة فقط",
          });
          return;
        }
  
        // Update file label
        $(".waapi-file-label").text(file.name);
  
        const reader = new FileReader();
  
        reader.onload = function (e) {
          imageBase64 = e.target.result;
  
          // Display the image preview
          $("#waapi-image-preview")
            .html('<img src="' + imageBase64 + '" alt="معاينة">')
            .show();
        };
  
        reader.readAsDataURL(file);
      }
    });
  
    // Handle form submission (test send)
    $("#waapi-media-form").on("submit", function (e) {
      e.preventDefault();
  
      if (sending) {
        return;
      }
  
      const message = $("#waapi-message").val();
  
      if (!message) {
        Toast.fire({
          icon: "error",
          title: "يرجى كتابة نص الرسالة",
        });
        return;
      }
      
      Swal.fire({
        title: "إرسال إلى رقم الاختبار",
        text: "سيتم إرسال الرسالة إلى رقم الاختبار فقط",
        icon: "info",
        showCancelButton: true,
        confirmButtonText: "إرسال",
        cancelButtonText: "إلغاء",
        confirmButtonColor: "#8b0628",
        cancelButtonColor: "#666",
      }).then((result) => {
        if (result.isConfirmed) {
          sendTestMessage();
        }
      });
    });
      
    // Handle stop button
    $("#waapi-stop-btn").on("click", function () {
      Swal.fire({
        title: "إيقاف الإرسال؟",
        text: "هل أنت متأكد من رغبتك في إيقاف إرسال الرسائل؟",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "نعم، إيقاف",
        cancelButtonText: "لا",
        confirmButtonColor: "#f7bf00",
        cancelButtonColor: "#8b0628",
      }).then((result) => {
        if (result.isConfirmed) {
          stopSending();
        }
      });
    });

    // Handle send to all button
    $("#waapi-send-all-btn").on("click", function() {
      // Check if any teachers are selected
      if (visibleTeachers.size === 0) {
        Toast.fire({
          icon: "error",
          title: "يرجى تحديد معلم واحد على الأقل",
        });
        return;
      }

      Swal.fire({
        title: "إرسال إلى الطلاب",
        text: "هل أنت متأكد من رغبتك في إرسال هذه الرسالة إلى طلاب المعلمين المحددين؟",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "نعم، إرسال",
        cancelButtonText: "إلغاء",
        confirmButtonColor: "#8b0628",
        cancelButtonColor: "#666",
      }).then((result) => {
        if (result.isConfirmed) {
          sendToAllStudents();
        }
      });
    });
      
    // Function to send test message
    function sendTestMessage() {
      sending = true;
      
      $("#waapi-send-btn").prop("disabled", true).text("جاري الإرسال...");
      
      // Show loading
      Swal.fire({
        title: "إرسال رسالة اختبار",
        html: "جاري الإرسال إلى رقم الاختبار...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });
      
      $.ajax({
        url: waapiData.ajaxurl,
        type: "POST",
        data: {
          action: "waapi_send_media",
          image_data: imageBase64,
          message: $("#waapi-message").val(),
          _ajax_nonce: waapiData.nonce,
          m_id: '01', // Use default test m_id '01' for instance 43271
          is_media: imageBase64 ? true : false // Explicitly indicate if this is a media message
        },
        success: function (response) {
          Swal.close();
          
          if (response.success) {
            // Get student count for selected teachers
            $.ajax({
              url: waapiData.ajaxurl,
              type: "POST",
              data: {
                action: "waapi_get_student_count",
                visible_teachers: Array.from(visibleTeachers),
                _ajax_nonce: waapiData.nonce
              },
              success: function(studentResponse) {
                // Show success message with student information
                Swal.fire({
                  title: "تم الاختبار بنجاح",
                  html: `
                    <div style="text-align: right; margin-top: 20px;">
                      <p><strong>تم إرسال رسالة الاختبار بنجاح إلى الرقم:</strong> 201146048290</p>
                      <p><strong>عدد الطلاب المعنيين:</strong> ${studentResponse.total_students}</p>
                      <p><strong>المعلمون المحددون:</strong> ${studentResponse.selected_teachers}</p>
                      <p><strong>الرسالة:</strong> ${$("#waapi-message").val()}</p>
                      ${imageBase64 ? '<p><strong>الصورة:</strong> مضمنة</p>' : ''}
                      ${studentResponse.total_students === 0 ? '<p style="color: red;"><strong>تنبيه:</strong> لن يتلقى أي طالب الرسالة لأنه لم يتم تحديد أي معلم أو أن المعلمين المحددين ليس لديهم طلاب.</p>' : ''}
                    </div>
                  `,
                  icon: "success",
                  showCancelButton: true,
                  confirmButtonText: "إرسال إلى الطلاب",
                  cancelButtonText: "إلغاء",
                  confirmButtonColor: "#8b0628",
                  cancelButtonColor: "#666",
                }).then((result) => {
                  if (result.isConfirmed) {
                    // Hide the test send button and show send to all button
                    $("#waapi-send-btn").hide();
                    $("#waapi-send-all-btn").show();
                    // Start sending process
                    sendToAllStudents();
                  } else {
                    // If cancelled, reset the form
                    resetForm();
                  }
                });
                
                // Add to log with enhanced styling
                $(".waapi-log-container").show();
                addLogEntry(
                  "رقم الاختبار",
                  "201146048290",
                  "تم إرسال رسالة الاختبار بنجاح",
                  "success",
                  "رسالة اختبار"
                );
              },
              error: function() {
                // If we can't get student count, still show success but without the details
                Swal.fire({
                  title: "تم الاختبار بنجاح",
                  text: "تم إرسال الرسالة إلى رقم الاختبار بنجاح.",
                  icon: "success",
                  confirmButtonText: "حسنًا",
                  confirmButtonColor: "#8b0628",
                });
              }
            });
          } else {
            Swal.fire({
              title: "خطأ",
              text: response.message,
              icon: "error",
              confirmButtonText: "حسنًا",
              confirmButtonColor: "#8b0628",
            });
            resetForm();
          }
        },
        error: function (xhr, status, error) {
          Swal.close();
          Swal.fire({
            title: "خطأ في الاتصال",
            text: "حدث خطأ أثناء الإرسال: " + error,
            icon: "error",
            confirmButtonText: "حسنًا",
            confirmButtonColor: "#8b0628",
          });
          resetForm();
        },
      });
    }
      
    // Function to send to all students
    function sendToAllStudents() {
      sending = true;
      $("#waapi-send-all-btn").hide();
      $("#waapi-stop-btn").show();
      $("#waapi-progress-container").show();
        
      // First, start the sending process
      $.ajax({
        url: waapiData.ajaxurl,
        type: "POST",
        data: {
          action: "waapi_manage_sending",
          action_type: "start",
          _ajax_nonce: waapiData.nonce
        },
        success: function(startResponse) {
          if (startResponse.success) {
            // Now proceed with sending messages
            sendBatch();
          } else {
            Swal.fire({
              title: "خطأ",
              text: "تعذر بدء إرسال الرسائل",
              icon: "error",
              confirmButtonText: "حسنًا",
              confirmButtonColor: "#8b0628",
            });
            resetForm();
          }
        },
        error: function(xhr, status, error) {
          Swal.fire({
            title: "خطأ في الاتصال",
            text: "حدث خطأ أثناء بدء الإرسال: " + error,
            icon: "error",
            confirmButtonText: "حسنًا",
            confirmButtonColor: "#8b0628",
          });
          resetForm();
        }
      });
    }
      
    // Function to send a batch of messages
    function sendBatch() {
      if (!sending) return;
      
      $.ajax({
        url: waapiData.ajaxurl,
        type: "POST",
        data: {
          action: "waapi_send_to_all_students",
          image_data: imageBase64,
          message: $("#waapi-message").val(),
          visible_teachers: Array.from(visibleTeachers),
          page: currentPage,
          _ajax_nonce: waapiData.nonce,
          is_media: imageBase64 ? true : false // Explicitly indicate if this is a media message
        },
        success: function(response) {
          if (response.success) {
            // Update the summary section
            updateSummarySection(response);
                    
            // Update progress bar
            $("#waapi-progress .waapi-progress-bar").width(response.progress + "%");
            $("#waapi-progress-percentage").text(response.progress + "%");
                    
            // Add log entries for each result
            response.results.forEach(result => {
              addLogEntry(
                result.name,
                result.phone,
                result.result.message,
                result.result.success === true ? "success" : result.result.success === "skipped" ? "skipped" : "error"
              );
            });
                    
            // Continue sending if there are more users and status is running
            if (response.has_more && response.status === "running") {
              currentPage++;
              setTimeout(() => {
                sendBatch();
              }, 1000);
            } else {
              // Sending completed or stopped
              if (response.status === "terminated") {
                Toast.fire({
                  icon: "warning",
                  title: "تم إيقاف الإرسال بواسطة المستخدم",
                });
              } else {
                Toast.fire({
                  icon: "success",
                  title: "تم إكمال الإرسال بنجاح",
                });
              }
              resetForm();
            }
          } else {
            // Handle specific error cases
            if (response.status === "terminated") {
              Toast.fire({
                icon: "warning",
                title: "تم إيقاف الإرسال بواسطة المستخدم",
              });
            } else if (response.status === "paused") {
              Toast.fire({
                icon: "info",
                title: "تم إيقاف الإرسال مؤقتًا",
              });
            } else {
              Swal.fire({
                title: "خطأ",
                text: response.message || "حدث خطأ",
                icon: "error",
                confirmButtonText: "حسنًا",
                confirmButtonColor: "#8b0628",
              });
            }
            resetForm();
          }
        },
        error: function(xhr, status, error) {
          Swal.fire({
            title: "خطأ في الاتصال",
            text: "حدث خطأ: " + error,
            icon: "error",
            confirmButtonText: "حسنًا",
            confirmButtonColor: "#8b0628",
          });
          resetForm();
        }
      });
    }
      
    // Function to stop sending
    function stopSending() {
      $.ajax({
        url: waapiData.ajaxurl,
        type: "POST",
        data: {
          action: "waapi_manage_sending",
          action_type: "terminate",
          _ajax_nonce: waapiData.nonce
        },
        success: function(response) {
          if (response.success) {
            sending = false;
            Toast.fire({
              icon: "success",
              title: "تم إيقاف الإرسال",
            });
            resetForm();
          }
        },
        error: function(xhr, status, error) {
          console.error('Error stopping sending:', error);
        }
      });
    }
      
    // Helper function to add log entry
    function addLogEntry(name, phone, message, status, type = '') {
      const timestamp = new Date().toLocaleTimeString();
      const entry = `
        <div class="waapi-log-entry ${status}">
          <div class="waapi-log-header">
            <div class="waapi-log-name">${name} (${phone})</div>
            <div class="waapi-log-timestamp">${timestamp}</div>
          </div>
          <div class="waapi-log-content">
            <div class="waapi-log-status">${message}</div>
            ${type ? `<div class="waapi-log-type">${type}</div>` : ''}
          </div>
        </div>
      `;
      $("#waapi-log").prepend(entry);
    }
      
    // Update the updateSummarySection function
    function updateSummarySection(response) {
      if (!response) return;
        
      // Get current student count
      $.ajax({
        url: waapiData.ajaxurl,
        type: "POST",
        data: {
          action: "waapi_get_student_count",
          visible_teachers: Array.from(visibleTeachers),
          _ajax_nonce: waapiData.nonce
        },
        success: function(studentResponse) {
          if (studentResponse.success) {
            const summaryHtml = `
              <div class="waapi-summary-section">
                <div class="waapi-summary-header">
                  <h3>حالة الإرسال الحالية</h3>
                  <div class="waapi-summary-status ${response.status || 'running'}">${response.status === 'running' ? 'جاري الإرسال' : response.status === 'paused' ? 'مؤقت' : 'مكتمل'}</div>
                </div>
                <div class="waapi-summary-content">
                  <div class="waapi-summary-item">
                    <span class="waapi-summary-label">التقدم:</span>
                    <span class="waapi-summary-value">${response.progress || 0}%</span>
                  </div>
                  <div class="waapi-summary-item">
                    <span class="waapi-summary-label">الرسائل المرسلة:</span>
                    <span class="waapi-summary-value">${response.total_sent || response.sent || 0}</span>
                  </div>
                  <div class="waapi-summary-item">
                    <span class="waapi-summary-label">الرسائل المتخطاة:</span>
                    <span class="waapi-summary-value">${response.total_skipped || response.skipped || 0}</span>
                  </div>
                  <div class="waapi-summary-item">
                    <span class="waapi-summary-label">إجمالي الطلاب:</span>
                    <span class="waapi-summary-value">${studentResponse.total_students || 0}</span>
                  </div>
                  <div class="waapi-summary-item full-width">
                    <span class="waapi-summary-label">المعلمون المحددون:</span>
                    <span class="waapi-summary-value">${studentResponse.selected_teachers}</span>
                  </div>
                </div>
              </div>
            `;
                    
            // Insert the summary before the log container
            if ($(".waapi-summary-section").length === 0) {
              $(".waapi-log-container").before(summaryHtml);
            } else {
              $(".waapi-summary-section").replaceWith(summaryHtml);
            }
          }
        },
        error: function(xhr, status, error) {
          console.error('Error getting student count:', error);
        }
      });
    }
      
    // Reset form after sending
    function resetForm() {
      sending = false;
      $("#waapi-send-btn").show().prop("disabled", false).text("إرسال إلى رقم الاختبار");
      $("#waapi-send-all-btn").hide();
      $("#waapi-stop-btn").hide();
      $("#waapi-progress-container").hide();
      $(".waapi-summary-section").remove();
      
      setTimeout(() => {
        $("#waapi-progress .waapi-progress-bar").width("0%");
        $("#waapi-progress-percentage").text("0%");
        $("#waapi-progress-container").hide();
      }, 3000);
    }
});