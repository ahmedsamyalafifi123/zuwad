/**
 * Zuwad Plugin - Student Schedules Module
 * 
 * This file manages the student class scheduling functionality including:
 * - Modal operations
 * - Student search and selection
 * - Class time management (add, remove, update)
 * - Schedule submission and validation
 */
(function($) {
    'use strict';

    /**
     * Modal Operations
     * Controls the display of the schedule modal
     */
    const Modal = {
        init: function() {
            // Open modal button click
            $("#open-schedule-modal").click(function() {
    $("#schedule-modal").show();
  });

            // Close modal button click
            $("#close-modal").click(function() {
    $("#schedule-modal").hide();
  });

  // Close modal when clicking outside
            $("#schedule-modal").on("click", function(e) {
    if (e.target === this) {
      // Ensure the click is on the overlay itself, not its children
      $("#schedule-modal").hide();
    }
  });
        }
    };

    /**
     * Time Picker
     * Handles the time picker initialization and functionality
     */
    const TimePicker = {
        init: function() {
            // Initialize Flatpickr for existing time pickers
            this.initializeTimePickers($(".zuwad-time-picker"));
        },
        
        /**
         * Initialize time picker for specific elements
         * @param {jQuery} elements - The elements to initialize Flatpickr on
         */
        initializeTimePickers: function(elements) {
            elements.flatpickr({
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K", // Example: 08:00 AM
                minuteIncrement: 30,
            });
        }
    };

    /**
     * Class Time Management
     * Handles adding and removing class time slots
     */
    const ClassTimeManager = {
        init: function() {
  // Add more class time fields
            $("#add-class-time").click(this.addClassTime);
            
            // Remove class time
            $(document).on("click", ".zuwad-remove-class", this.removeClassTime);
        },
        
        /**
         * Add a new class time slot
         */
        addClassTime: function() {
    var maxClassTimes = parseInt($(this).data("max-times") || 7);
    if ($(".class-time").length < maxClassTimes) {
      var $newClassTime = $(`
              <div class="class-time">
                  <select class="zuwad-day-picker" required>
                      <option value="السبت">السبت</option>
                      <option value="الأحد">الأحد</option>
                      <option value="الاثنين">الاثنين</option>
                      <option value="الثلاثاء">الثلاثاء</option>
                      <option value="الأربعاء">الأربعاء</option>
                      <option value="الخميس">الخميس</option>
                      <option value="الجمعة">الجمعة</option>
                  </select>
                  <input type="text" class="zuwad-time-picker" placeholder="الموعد" readonly>
                  <button type="button" class="zuwad-remove-class">&times;</button>
              </div>
          `);

      $("#class-times").append($newClassTime);

                // Initialize Flatpickr for the new time picker
                TimePicker.initializeTimePickers($newClassTime.find(".zuwad-time-picker"));
    } else {
        Swal.fire({
            title: 'الحد الأقصى',
            text: `الحد الأقصى لعدد الحصص هو ${maxClassTimes}.`,
            icon: 'warning',
            confirmButtonText: 'حسناً'
        });
    }
        },

        /**
         * Remove a class time slot
         */
        removeClassTime: function() {
    Swal.fire({
        title: 'تأكيد الحذف',
        text: 'هل أنت متأكد من حذف هذا الموعد؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
      $(this).closest(".class-time").remove();
            Swal.fire({
                title: 'تم الحذف',
                text: 'تم الحذف بنجاح 🤓',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
        },
        
        /**
         * Populate class times from existing schedule data
         * @param {Array} schedule - The schedule data array
         */
        populateClassTimes: function(schedule) {
            $("#class-times").empty();
            
            if (schedule && schedule.length > 0) {
                schedule.forEach(function(classTime) {
                    var $newClassTime = $(`
                        <div class="class-time">
                            <select class="zuwad-day-picker" required>
                                <option value="السبت" ${classTime.day === "السبت" ? "selected" : ""}>السبت</option>
                                <option value="الأحد" ${classTime.day === "الأحد" ? "selected" : ""}>الأحد</option>
                                <option value="الاثنين" ${classTime.day === "الاثنين" ? "selected" : ""}>الاثنين</option>
                                <option value="الثلاثاء" ${classTime.day === "الثلاثاء" ? "selected" : ""}>الثلاثاء</option>
                                <option value="الأربعاء" ${classTime.day === "الأربعاء" ? "selected" : ""}>الأربعاء</option>
                                <option value="الخميس" ${classTime.day === "الخميس" ? "selected" : ""}>الخميس</option>
                                <option value="الجمعة" ${classTime.day === "الجمعة" ? "selected" : ""}>الجمعة</option>
                            </select>
                            <input type="text" class="zuwad-time-picker" value="${classTime.hour}" readonly>
                            <input type="hidden" class="zuwad-original-class" value='${JSON.stringify(classTime)}'>
                            <button type="button" class="zuwad-remove-class">&times;</button>
                        </div>
                    `);
                    $("#class-times").append($newClassTime);
                });
                
                // Initialize Flatpickr for all time pickers
                TimePicker.initializeTimePickers($("#class-times .zuwad-time-picker"));
            } else {
                // If no existing schedule, show one empty class time field
                var $newClassTime = $(`
                    <div class="class-time">
                        <select class="zuwad-day-picker" required>
                            <option value="السبت">السبت</option>
                            <option value="الأحد">الأحد</option>
                            <option value="الاثنين">الاثنين</option>
                            <option value="الثلاثاء">الثلاثاء</option>
                            <option value="الأربعاء">الأربعاء</option>
                            <option value="الخميس">الخميس</option>
                            <option value="الجمعة">الجمعة</option>
                        </select>
                        <input type="text" class="zuwad-time-picker" placeholder="الموعد" readonly>
                        <button type="button" class="zuwad-remove-class">&times;</button>
                    </div>
                `);
                $("#class-times").append($newClassTime);
                
                // Initialize Flatpickr for the new time picker
                TimePicker.initializeTimePickers($newClassTime.find(".zuwad-time-picker"));
            }
        },
        
        /**
         * Update the remaining classes information display
         * @param {number} maxClassTimes - Maximum allowed class times
         * @param {number} currentClassCount - Current number of class times
         */
        updateRemainingClassesInfo: function(maxClassTimes, currentClassCount) {
            var remainingClasses = maxClassTimes - currentClassCount;
            
            $("#remaining-classes-info").remove();
            
            if (remainingClasses > 0) {
                $("#class-times").before(
                    `<div id="remaining-classes-info" class="info-message">
                        يحب إضافة ${remainingClasses} حصص (الحد الأقصى هو ${maxClassTimes})
                    </div>`
                );
            } else if (remainingClasses <= 0) {
                $("#class-times").before(
                    `<div id="remaining-classes-info" class="info-message warning">
                        تم الوصول للحد الأقصى من الحصص (${maxClassTimes})
                    </div>`
                );
            }
        }
    };

    /**
     * Student Search
     * Handles student search and selection functionality
     */
    const StudentSearch = {
        init: function() {
  // Search for students
            $("#student-search").on("input", this.searchStudents);
            
            // Handle student selection from search results
            $(document).on("click", ".student-result", this.selectStudent);
        },
        
        /**
         * Search for students as user types
         */
        searchStudents: function() {
    var searchQuery = $(this).val();
    if (searchQuery.length > 2) {
      $.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: {
          action: "search_students",
          search: searchQuery,
        },
                    success: function(response) {
          if (response) {
            $("#search-results").html(response).show();
          } else {
                            $("#search-results").html("<div>لم يتم العثور على طلاب.</div>").show();
          }
        },
                    error: function(xhr, status, error) {
          console.error("AJAX Error:", error);
        },
      });
    } else {
      $("#search-results").hide();
    }
        },

        /**
         * Handle student selection from search results
         */
        selectStudent: function() {
    var studentName = $(this).text();
    var studentId = $(this).data("id");
    $("#student-search").val(studentName);
    $("#selected-student-id").val(studentId);
    $("#search-results").hide();

    // Fetch existing class times for the selected student
    $.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: {
        action: "fetch_student_schedule",
        student_id: studentId,
      },
        beforeSend: function() {
            Swal.fire({
                title: 'جاري التحميل',
                text: 'جاري تحميل بيانات الجدول...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
        if (response.success) {
                Swal.close();
                
          // Set the max class times based on student's lessons_number
          $("#add-class-time").data("max-times", response.data.max_class_times);

                // Update the remaining classes information
                ClassTimeManager.updateRemainingClassesInfo(
                    response.data.max_class_times,
                    response.data.schedule ? response.data.schedule.length : 0
                );
                
                // Populate class times from schedule data
                ClassTimeManager.populateClassTimes(response.data.schedule);
            } else {
                console.error("Error fetching schedule:", response);
                Swal.fire({
                    title: 'خطأ',
                    text: 'حدث خطأ أثناء جلب جدول الطالب.',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", error);
            Swal.fire({
                title: 'خطأ',
                text: 'حدث خطأ في الاتصال بالخادم.',
                icon: 'error',
                confirmButtonText: 'حسناً'
            });
        }
    });
        }
    };

    /**
     * Schedule Form
     * Handles the schedule form submission and validation
     */
    const ScheduleForm = {
        init: function() {
            // Submit schedule form
            $("#schedule-form").submit(this.submitSchedule);
        },
        
        /**
         * Submit the schedule form
         * @param {Event} e - The submit event
         */
        submitSchedule: function(e) {
    e.preventDefault();
        
    var studentId = $("#selected-student-id").val();
            if (!studentId) {
                Swal.fire({
                    title: 'تنبيه',
                    text: 'الرجاء اختيار طالب أولاً',
                    icon: 'warning',
                    confirmButtonText: 'حسناً'
                });
                return;
            }
        
            var classTimes = [];
            var isValid = true;
        
            // Validate and collect class times
            $(".class-time").each(function() {
                var $day = $(this).find(".zuwad-day-picker");
                var $time = $(this).find(".zuwad-time-picker");
                var $originalClass = $(this).find(".zuwad-original-class");
        
                var day = $day.val();
                var time = $time.val();
        
                if (!day || !time) {
                    isValid = false;
                    return false; // Break the loop
                }
        
                var classTime = {
          day: day,
                    hour: time
                };
        
                // If this class time has an original value, include it for comparison
                if ($originalClass.length) {
                    try {
                        classTime.original = JSON.parse($originalClass.val());
                    } catch (error) {
                        console.error("Error parsing original class time:", error);
                    }
                }
        
                classTimes.push(classTime);
            });
        
            if (!isValid) {
                Swal.fire({
                    title: 'بيانات غير مكتملة',
                    text: 'الرجاء إكمال جميع مواعيد الحصص',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
                return;
            }
        
            // Helper function to save the schedule after conflict check
            function saveSchedule(studentId, classTimes) {
      $.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: {
                        action: "update_student_schedule",
          student_id: studentId,
                        class_times: JSON.stringify(classTimes)
                    },
                    beforeSend: function() {
                        $("#submit-schedule").prop("disabled", true).text("جاري الحفظ...");
                        Swal.fire({
                            title: 'جاري الحفظ',
                            text: 'يرجى الانتظار...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
            Swal.fire({
                                title: 'تم الحفظ',
                                text: 'تم حفظ الجدول بنجاح',
                                icon: 'success',
                                confirmButtonText: 'حسناً'
                            }).then(() => {
                                $("#schedule-modal").hide();
                                // DO NOT refresh the page!
            });
          } else {
                            Swal.fire({
                                title: 'خطأ',
                                text: 'حدث خطأ أثناء حفظ الجدول: ' + response.data.message,
                                icon: 'error',
                                confirmButtonText: 'حسناً'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            title: 'خطأ',
                            text: 'حدث خطأ في الاتصال بالخادم',
                            icon: 'error',
                            confirmButtonText: 'حسناً'
                        });
                    },
                    complete: function() {
                        $("#submit-schedule").prop("disabled", false).text("حفظ المواعيد");
                    }
                });
            }
        
            // First check for conflicts
            $.ajax({
              url: zuwadPlugin.ajaxurl,
              type: "POST",
              data: {
                    action: "check_schedule_conflicts",
                student_id: studentId,
                    schedule: JSON.stringify(classTimes)
              },
                beforeSend: function() {
                    $("#submit-schedule").prop("disabled", true).text("جاري التحقق من التعارضات...");
                Swal.fire({
                        title: 'جاري التحقق',
                        text: 'التحقق من التعارضات...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                });
              },
                success: function(response) {
                    if (response.success && response.data && response.data.conflicts && response.data.conflicts.length > 0) {
                        // Show conflicts to the user with SweetAlert2 (NO continue button)
                        var conflictList = response.data.conflicts.map(function(conflict) {
                            return '<li style="text-align: right; direction: rtl;">' + conflict + '</li>';
                        }).join('');
                Swal.fire({
                            title: 'تم العثور على تعارضات',
                            html: '<div style="text-align: right; direction: rtl;">تم العثور على التعارضات التالية:</div><ul style="text-align: right; list-style-position: inside;">' + conflictList + '</ul>',
                            icon: 'error',
                            confirmButtonText: 'حسناً'
                        });
                        $("#submit-schedule").prop("disabled", false).text("حفظ المواعيد");
                    } else {
                        // No conflicts, proceed with saving
                        saveSchedule(studentId, classTimes);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
          Swal.fire({
                        title: 'خطأ',
                        text: 'حدث خطأ في التحقق من التعارضات',
                        icon: 'error',
                        confirmButtonText: 'حسناً'
                    });
                    $("#submit-schedule").prop("disabled", false).text("حفظ المواعيد");
                }
            });
        }
    };

    /**
     * Initialize all components when document is ready
     */
    $(document).ready(function() {
        Modal.init();
        TimePicker.init();
        ClassTimeManager.init();
        StudentSearch.init();
        ScheduleForm.init();
    });

})(jQuery);
