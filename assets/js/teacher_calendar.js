document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("teacher-calendar");

  const savedView = localStorage.getItem("calendarView");
  const validViews = ["timeGridDay", "timeGridWeek", "dayGridMonth"];
  const initialView = validViews.includes(savedView)
    ? savedView
    : "timeGridWeek";

  // Initialize the calendar
  window.calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: initialView,
    locale: "ar-eg", // Set Arabic locale
    direction: "rtl",
    dayHeaderFormat: { weekday: "long" },

    // timeZone: 'Africa/Cairo', // Set the timezone to Egypt
    headerToolbar: {
      left: "prev,next", // Swap previous and next buttons to the right side
      center: "title",
      right: "timeGridDay,timeGridWeek,dayGridMonth",
    },
    allDaySlot: false,
    firstDay: 6,
    buttonText: {
      today: "اليوم",
      day: "يوم",
      week: "أسبوع",
      month: "شهر",
    },
    slotMinTime: "10:00:00", // Default to AM hours (6:00 AM)
    slotMaxTime: "24:00:00", // Default to all hours (6:00 PM)
    selectable: true, // Enable slot selection
    slotDuration: "00:15:00", // Set time slots to 15 minutes
    initialEvents: [], // Start with empty events

    select: function (info) {
      // Clear the selection immediately
      calendar.unselect();

      // Get all events in the calendar, excluding free slots
      const events = calendar
        .getEvents()
        .filter((event) => !event.extendedProps.isFreeSlot);

      // Check if the selected slot overlaps with any existing event
      const overlappingEvent = events.some((event) => {
        const eventStart = event.start;
        const eventEnd = event.end || eventStart;

        return (
          (info.start < eventEnd && info.end > eventStart) ||
          (info.start >= eventStart && info.start < eventEnd) ||
          (info.end > eventStart && info.end <= eventEnd)
        );
      });

      if (overlappingEvent) {
        Swal.fire({
          icon: "error",
          title: "تعذر الحجز",
          text: "هذا الوقت يتعارض مع حدث موجود.",
          confirmButtonText: "حسناً",
        });
        return;
      }

      // Get the teacher ID from either the filter or the container data attribute
      const container = document.getElementById("teacher-calendar-container");
      const isTeacher = container.dataset.isTeacher === "true";
      const teacherFilter = document.getElementById("teacher-filter");
      let teacher_id;

      if (isTeacher) {
        teacher_id = container.dataset.teacherId;
      } else {
        teacher_id = teacherFilter ? teacherFilter.value : null;
      }

      if (!teacher_id) {
        Swal.fire({
          icon: "error",
          title: "خطأ",
          text: "لم يتم اختيار معلم.",
          confirmButtonText: "حسناً",
        });
        return;
      }

      // Save the free slot to the database
      jQuery.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: {
          action: "save_free_slot",
          start: info.start.toISOString(),
          end: info.end.toISOString(),
          timezone: "Africa/Cairo",
          teacher_id: teacher_id,
          nonce: zuwadPlugin.nonce,
        },
        success: function (response) {
          if (response.success) {
            // Refetch all events
            calendar.refetchEvents();
          }
        },
        error: function () {
          // No error handling needed since the server silently rejects short slots
        },
      });
    },
    eventClick: function (info) {
      // Check if the clicked event is a free slot
      if (info.event.extendedProps.isFreeSlot) {
        Swal.fire({
          title: "هل أنت متأكد؟",
          text: "هل تريد حذف هذا الوقت الفراغ؟",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "نعم، احذفه",
          cancelButtonText: "إلغاء",
          reverseButtons: true,
        }).then((result) => {
          if (result.isConfirmed) {
            // Get the teacher ID from either the filter or the container data attribute
            const container = document.getElementById(
              "teacher-calendar-container"
            );
            const isTeacher = container.dataset.isTeacher === "true";
            const teacherFilter = document.getElementById("teacher-filter");
            let teacher_id;

            if (isTeacher) {
              teacher_id = container.dataset.teacherId;
            } else {
              teacher_id = teacherFilter ? teacherFilter.value : null;
            }

            if (!teacher_id) {
              Swal.fire({
                icon: "error",
                title: "خطأ",
                text: "لم يتم اختيار معلم.",
                confirmButtonText: "حسناً",
              });
              return;
            }

            // Log the start and end times
            // console.log('Deleting free slot with start:', info.event.start.toISOString());
            // console.log('Deleting free slot with end:', info.event.end.toISOString());

            // Send an AJAX request to delete the free slot
            jQuery.ajax({
              url: zuwadPlugin.ajaxurl,
              type: "POST",
              data: {
                action: "delete_free_slot",
                start: info.event.start.toISOString(), // Send in ISO format
                end: info.event.end.toISOString(), // Send in ISO format
                teacher_id: teacher_id, // Pass the selected teacher ID
                nonce: zuwadPlugin.nonce,
              },
              success: function (response) {
                // console.log('Delete response:', response); // Log the response

                if (response.success) {
                  // Remove the event from the calendar
                  info.event.remove();
                  Swal.fire({
                    icon: "success",
                    title: "تم الحذف",
                    text: "تم حذف الوقت الفراغ بنجاح.",
                    confirmButtonText: "حسناً",
                  });
                } else {
                  Swal.fire({
                    icon: "error",
                    title: "خطأ",
                    text: "فشل في حذف الوقت الفراغ.",
                    confirmButtonText: "حسناً",
                  });
                }
              },
              error: function (xhr, status, error) {
                console.error("Delete error:", xhr.responseText); // Log the error
                Swal.fire({
                  icon: "error",
                  title: "خطأ",
                  text: "فشل في حذف الوقت الفراغ.",
                  confirmButtonText: "حسناً",
                });
              },
            });
          }
        });
      } else {
        // Handle regular event clicks (existing logic)
        resetReportForm();

        if (
          info.event.extendedProps.eventStatus === "future" &&
          zuwadPlugin.userRole !== "administrator" &&
          zuwadPlugin.userRole !== "supervisor"
        ) {
          return;
        }

        const studentId = info.event.extendedProps.student_id;
        const eventDate = info.event.start;
        const localDate = new Date(
          eventDate.getTime() - eventDate.getTimezoneOffset() * 60000
        )
          .toISOString()
          .split("T")[0];
        const localTime = eventDate.toLocaleTimeString("en-US", {
          hour: "2-digit",
          minute: "2-digit",
          hour12: true,
        });

        const isAdHoc = info.event.extendedProps.isAdHoc;
        const existingReport = info.event.extendedProps.reportData;
        const reportId = existingReport?.id;

        openReportForm(
          studentId,
          false,
          localDate,
          localTime,
          isAdHoc,
          existingReport
        );

        const existingRemoveButton = document.querySelector(
          ".remove-report-button"
        );
        if (existingRemoveButton) {
          existingRemoveButton.remove();
        }

        if (info.event.extendedProps.isSubmitted || isAdHoc) {
          // Only show delete button for supervisor and administrator roles
          if (
            zuwadPlugin.userRole === "supervisor" ||
            zuwadPlugin.userRole === "administrator"
          ) {
            const removeButton = document.createElement("button");
            removeButton.innerHTML = "🗑️ حذف التقرير";
            removeButton.classList.add("remove-report-button");
            removeButton.addEventListener("click", function () {
              Swal.fire({
                title: "هل أنت متأكد؟",
                text: "هل أنت متأكد أنك تريد حذف هذا التقرير؟",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "نعم، احذفه",
                cancelButtonText: "إلغاء",
                reverseButtons: true,
              }).then((result) => {
                if (result.isConfirmed) {
                  deleteReport(reportId);
                }
              });
            });

            const buttonRow = document.querySelector(".button-row");
            buttonRow.appendChild(removeButton);
          }

          const shareReportButton = document.getElementById("share-report");
          shareReportButton.style.display = "inline-block";

          const buttonRow = document.querySelector(".button-row");
          buttonRow.appendChild(removeButton);
        } else {
          const shareReportButton = document.getElementById("share-report");
          shareReportButton.style.display = "none";
        }
      }
    },
    events: function (fetchInfo, successCallback, failureCallback) {
      const teacherFilter = document.getElementById("teacher-filter");
      const studentFilter = document.getElementById("student-filter");
      const calendarContainer = document.getElementById(
        "teacher-calendar-container"
      );

      const data = {
        action: "zuwad_get_teacher_schedule",
        start: fetchInfo.start.toISOString(),
        end: fetchInfo.end.toISOString(),
      };

      const isTeacher = calendarContainer.dataset.isTeacher === "true";
      let teacherId;

      if (isTeacher) {
        teacherId = calendarContainer.dataset.teacherId;
      } else if (teacherFilter && teacherFilter.value) {
        teacherId = teacherFilter.value;
      }

      if (teacherId) {
        data.teacher_id = teacherId;
      }

      if (studentFilter.value && studentFilter.value !== "") {
        data.student_id = studentFilter.value;
      }

      // console.log('Fetching teacher schedule with data:', data);

      jQuery.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: data,
        success: function (response) {
          // console.log('Teacher schedule response:', response);

          if (teacherId) {
            // console.log('Fetching free slots...');
            jQuery.ajax({
              url: zuwadPlugin.ajaxurl,
              type: "POST",
              data: {
                action: "get_free_slots",
                teacher_id: teacherId,
                start: fetchInfo.start.toISOString(),
                end: fetchInfo.end.toISOString(),
              },
              success: function (freeSlotsResponse) {
                // console.log('Free slots response:', freeSlotsResponse);

                if (freeSlotsResponse.success) {
                  const events = response.concat(freeSlotsResponse.data);
                  // console.log('Combined events:', events);
                  successCallback(events);
                } else {
                  console.error(
                    "Failed to fetch free slots:",
                    freeSlotsResponse.data
                  );
                  successCallback(response);
                }
              },
              error: function (xhr, status, error) {
                console.error("Error fetching free slots:", xhr.responseText);
                successCallback(response);
              },
            });
          } else {
            successCallback(response);
          }
        },
        error: function (xhr, status, error) {
          console.error("Error fetching teacher schedule:", xhr.responseText);
          failureCallback(xhr.responseText);
        },
      });
    },
    eventContent: function (info) {
      return {
        html: `
                <div style="text-align: center; white-space: normal; word-wrap: break-word;">
                    ${info.event.title}
                </div>
                `,
      };
    },
    eventDidMount: function (info) {
      const attendance = info.event.extendedProps.attendance;
      const isFreeSlot = info.event.extendedProps.isFreeSlot;
      const isSubmitted = info.event.extendedProps.isSubmitted;
      const eventDate = info.event.extendedProps.eventDate; // Get the event date from extendedProps

      // Get current date and yesterday in YYYY-MM-DD format
      const today = new Date();
      const todayFormatted = today.toISOString().split("T")[0];

      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);
      const yesterdayFormatted = yesterday.toISOString().split("T")[0];

      // Hide gray lessons (no report) from today
      if (!isFreeSlot && !isSubmitted && eventDate === todayFormatted) {
        info.el.style.display = "none";
        return;
      }

      // Rest of your existing color logic
      let backgroundColor = "gray";
      let textColor = "black";

      if (isFreeSlot) {
        backgroundColor = "#66b066";
        textColor = "white";
      } else {
        switch (attendance) {
          case "حضور":
            backgroundColor = "green";
            break;
          case "غياب":
            backgroundColor = "#a61515";
            break;
          case "تأجيل المعلم":
            backgroundColor = "orange";
            break;
          case "تأجيل ولي أمر":
            backgroundColor = "black";
            break;
          case "اجازة معلم":
            backgroundColor = "#009fa7";
            break;
          case "تعويض التأجيل":
            backgroundColor = "#d06b7c";
            break;
          case "تعويض الغياب":
            backgroundColor = "#076095";
            break;
          case "تجريبي":
            backgroundColor = "#9d9d02";
            break;
          default:
            backgroundColor = "gray";
        }

        if (attendance === "تأجيل ولي أمر" || attendance === "black") {
          textColor = "white";
        }
      }

      info.el.style.backgroundColor = backgroundColor;
      info.el.style.color = textColor;
    },
    loading: function (isLoading) {
      if (!isLoading) {
        // Add hours select after loading is complete
        const toolbar = document.querySelector(".fc-header-toolbar");
        if (toolbar && !document.getElementById("hours-select")) {
          const selectMenu = document.createElement("select");
          selectMenu.id = "hours-select";
          selectMenu.style.margin = "0";
          selectMenu.style.cursor = "pointer";
          selectMenu.style.width = "auto";

          const options = [
            { value: "am", text: "AM" },
            { value: "pm", text: "PM" },
            { value: "all", text: "All" },
          ];
          options.forEach((option) => {
            const optionElement = document.createElement("option");
            optionElement.value = option.value;
            optionElement.text = option.text;
            selectMenu.appendChild(optionElement);
          });

          const savedTimeRange = localStorage.getItem("timeRange");
          if (savedTimeRange) {
            selectMenu.value = savedTimeRange;

            if (savedTimeRange === "am") {
              calendar.setOption("slotMinTime", "00:00:00");
              calendar.setOption("slotMaxTime", "12:00:00");
            } else if (savedTimeRange === "pm") {
              calendar.setOption("slotMinTime", "10:00:00");
              calendar.setOption("slotMaxTime", "24:00:00");
            } else {
              calendar.setOption("slotMinTime", "00:00:00");
              calendar.setOption("slotMaxTime", "24:00:00");
            }
          }

          selectMenu.addEventListener("change", function () {
            const selectedValue = this.value;
            localStorage.setItem("timeRange", selectedValue);

            if (selectedValue === "am") {
              calendar.setOption("slotMinTime", "00:00:00");
              calendar.setOption("slotMaxTime", "12:00:00");
            } else if (selectedValue === "pm") {
              calendar.setOption("slotMinTime", "10:00:00");
              calendar.setOption("slotMaxTime", "24:00:00");
            } else {
              calendar.setOption("slotMinTime", "00:00:00");
              calendar.setOption("slotMaxTime", "24:00:00");
            }
          });

          const weekViewButton = toolbar.querySelector(
            ".fc-timeGridWeek-button"
          );
          if (weekViewButton) {
            weekViewButton.insertAdjacentElement("afterend", selectMenu);
          }

          // In the loading function, update the insertion point
          const monthViewButton = toolbar.querySelector(
            ".fc-dayGridMonth-button"
          );
          if (monthViewButton) {
            monthViewButton.insertAdjacentElement("afterend", selectMenu);
          }
        }
      }
    },
    viewDidMount: function (info) {
      localStorage.setItem("calendarView", info.view.type);
    },
  });

  // Set a flag after initial render
  calendar.on("eventSourceSuccess", function () {
    calendar.initialRenderComplete = true;
  });

  // Render the calendar
  window.calendar.render();

  function deleteReport(reportId) {
    // console.log('Attempting to delete report with ID:', reportId);

    jQuery.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: {
        action: "delete_report",
        report_id: reportId, // Pass the report ID
        _ajax_nonce: zuwadPlugin.nonce,
      },
      success: function (response) {
        // console.log('AJAX Response:', response);

        if (response.success) {
          Swal.fire({
            icon: "success",
            title: "تم الحذف",
            text: "تم حذف الانجاز بنجاح.",
            confirmButtonText: "تمام",
          });

          // Close the modal
          jQuery("#report-modal").hide();

          // Refresh the calendar
          if (window.calendar) {
            window.calendar.refetchEvents();
          }
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to delete the report.",
            confirmButtonText: "OK",
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", xhr.responseText);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "An error occurred while deleting the report.",
          confirmButtonText: "OK",
        });
      },
    });
  }

  // Save teacher filter selection to local storage
  if (document.getElementById("teacher-filter")) {
    const teacherFilter = document.getElementById("teacher-filter");
    teacherFilter.addEventListener("change", function () {
      localStorage.setItem("selectedTeacher", this.value);
      var teacherId = this.value;
      var studentFilter = document.getElementById("student-filter");

      if (teacherId) {
        // Fetch teacher's students
        jQuery.ajax({
          url: zuwadPlugin.ajaxurl,
          type: "POST",
          data: {
            action: "get_teacher_students",
            teacher_id: teacherId,
          },
          success: function (response) {
            if (response.success) {
              studentFilter.innerHTML = "";
              var allStudentsOption = document.createElement("option");
              allStudentsOption.value = "";
              allStudentsOption.text = "جميع الطلبة";
              studentFilter.appendChild(allStudentsOption);

              response.data.students.forEach(function (student) {
                var option = document.createElement("option");
                option.value = student.id;
                option.text = student.name;
                studentFilter.appendChild(option);
              });

              // Let FullCalendar handle event refetching
              calendar.refetchEvents();
            } else {
              console.error("Error fetching students:", response.data.message);
            }
          },
          error: function (error) {
            console.error("AJAX error:", error);
          },
        });
      } else {
        studentFilter.innerHTML = "";
        calendar.refetchEvents();
      }
    });
  }

  // Save student filter selection to local storage
  document
    .getElementById("student-filter")
    .addEventListener("change", function () {
      localStorage.setItem("selectedStudent", this.value);
      calendar.refetchEvents();
    });

  // Retrieve saved selections from local storage and set the dropdowns
  const savedTeacher = localStorage.getItem("selectedTeacher");
  const savedStudent = localStorage.getItem("selectedStudent");

  if (document.getElementById("teacher-filter")) {
    const teacherFilter = document.getElementById("teacher-filter");
    if (savedTeacher) {
      teacherFilter.value = savedTeacher;
    } else {
      // Select the first teacher by default if no teacher is saved
      teacherFilter.value = teacherFilter.options[0].value;
    }
    // Trigger the change event to update the student filter
    const event = new Event("change");
    teacherFilter.dispatchEvent(event);
  }

  if (savedStudent && document.getElementById("student-filter")) {
    document.getElementById("student-filter").value = savedStudent;
  }

  // Render the calendar
  window.calendar.render();

  // Manually refetch events to ensure the calendar shows the correct data
  if (savedTeacher || savedStudent) {
    calendar.refetchEvents();
  }

  var lastAttendanceGroup = "valid"; // Track the last attendance group

  // Function to open the report form
  function openReportForm(
    studentId,
    showSearch = false,
    eventDate = null,
    eventTime = null,
    isAdHoc = false,
    existingReport = null
) {
    // Clear previous form data
    jQuery("#report-form")[0].reset();
    jQuery("#selected-student-id-report").val(studentId);

    // Clear the zoom image preview container
    const preview = jQuery("#zoom-image-preview");
    preview.empty();

    // Store the report ID in the modal's dataset
    const reportModal = document.getElementById("report-modal");
    if (existingReport) {
        reportModal.dataset.reportId = existingReport.id;
    } else {
        delete reportModal.dataset.reportId;
    }

    // Show or hide the search input based on the showSearch parameter
    if (showSearch) {
        jQuery("#student-search-report").show();
        jQuery("#date").closest(".zuwad-form-col").show();
        jQuery("#session-number").closest(".zuwad-form-col").hide();
    } else {
        jQuery("#student-search-report").hide();
        jQuery("#date").closest(".zuwad-form-col").hide();
        jQuery("#session-number").closest(".zuwad-form-col").show();
        jQuery("#session-number").prop("readonly", true);
    }

    // Hide lesson number and date fields and their columns
    jQuery("#date").closest(".zuwad-form-col").hide();
    jQuery("#session-number").prop("required", false);
    jQuery("#date").prop("required", false);

    // Set default attendance based on the context and user role
    const attendanceSelect = jQuery("#attendance");
    
    // Check if the current user is a teacher
    const isTeacher = jQuery("body").hasClass("teacher") || 
                     (document.getElementById("teacher-calendar-container") && 
                      document.getElementById("teacher-calendar-container").dataset.isTeacher === "true");
    
    // Define attendance options based on user role
    const teacherAttendances = ["حضور", "غياب"];
    const validAttendances = isTeacher ? teacherAttendances : [
        "حضور",
        "غياب", 
        "تأجيل المعلم",
        "تأجيل ولي أمر",
        "اجازة معلم",
    ];
    const nonValidAttendances = ["تعويض التأجيل", "تعويض الغياب", "تجريبي"];

    // Handle attendance changes to update session numbers correctly
    jQuery("#attendance").off("change").on("change", function() {
        const selectedAttendance = jQuery(this).val();
        const studentId = jQuery("#selected-student-id-report").val();

        // Define non-valid attendances that should always use session number 0
        const nonValidAttendances = ['تعويض التأجيل', 'تعويض الغياب', 'تجريبي'];

        // If this is a non-valid attendance type, always set session number to 0
        if (nonValidAttendances.includes(selectedAttendance)) {
            jQuery("#session-number").val(0);
            return;
        }

        if (studentId && selectedAttendance) {
            // Call the new endpoint to get the correct session number for this attendance
            jQuery.ajax({
                url: zuwadPlugin.ajaxurl,
                type: "POST",
                data: {
                    action: "get_session_number_for_attendance",
                    student_id: studentId,
                    attendance: selectedAttendance
                },
                success: function(response) {
                    if (response.success) {
                        jQuery("#session-number").val(response.data.session_number);
                    } else {
                        console.error("Failed to get session number:", response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", xhr.responseText);
                }
            });
        }
    });

    if (showSearch || isAdHoc) {
        // Form opened from button - use session number 0 for these special cases
        attendanceSelect.empty();
        nonValidAttendances.forEach((attendance) => {
            attendanceSelect.append(
                `<option value="${attendance}">${attendance}</option>`
            );
        });
        attendanceSelect.val("تعويض التأجيل");
    } else if (existingReport) {
        // Form opened from existing report
        attendanceSelect.empty();
        validAttendances.forEach((attendance) => {
            attendanceSelect.append(
                `<option value="${attendance}">${attendance}</option>`
            );
        });
        attendanceSelect.val(existingReport.attendance);
    } else {
        // Form opened from calendar - new report
        attendanceSelect.empty();
        validAttendances.forEach((attendance) => {
            attendanceSelect.append(
                `<option value="${attendance}">${attendance}</option>`
            );
        });
        attendanceSelect.val("حضور");
    }

    // Set the date and time from the calendar event
    if (eventDate) {
        jQuery("#date").val(eventDate);
    }
    if (eventTime) {
        jQuery("#time").val(eventTime);
    } else if (isAdHoc) {
        const now = new Date("2025-02-16T09:20:15+02:00");
        const currentTime = now.toLocaleTimeString("ar-EG", {
            hour12: false,
            hour: "2-digit",
            minute: "2-digit",
        });
        jQuery("#time").val(currentTime);
        jQuery("#date").val(now.toISOString().split("T")[0]);
    } else if (eventDate) {
        const localDate = new Date(
            eventDate.getTime() - eventDate.getTimezoneOffset() * 60000
        )
            .toISOString()
            .split("T")[0];
        jQuery("#date").val(localDate);
        jQuery("#time").val(eventTime);
    }

    // Fetch session number from backend - this is the ONLY source of truth
    if (studentId) {
        jQuery.ajax({
            url: zuwadPlugin.ajaxurl,
            type: "POST",
            data: {
                action: "get_last_report_date",
                student_id: studentId,
            },
            success: function (response) {
                if (response.success) {
                    let sessionNumberToSet;

                    if (existingReport) {
                        // Use existing report's session number
                        sessionNumberToSet = existingReport.session_number;
                    } else if (showSearch) {
                        // Special reports always use 0
                        sessionNumberToSet = 0;
                    } else {
                        // Regular calendar reports use backend-calculated session number
                        sessionNumberToSet = response.data.next_session_number;
                    }

                    // Set the session number - this comes from backend only
                    jQuery("#session-number").val(sessionNumberToSet);
                } else {
                    console.error("Failed to fetch session data:", response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", xhr.responseText);
            },
        });
    }

    // Handle existing report data
    if (existingReport) {
        jQuery("#attendance").val(existingReport.attendance);
        jQuery("#session-number").val(existingReport.session_number);
        jQuery("#evaluation").val(existingReport.evaluation);
        jQuery("#tasmii").val(existingReport.tasmii);
        jQuery("#tahfiz").val(existingReport.tahfiz);
        jQuery("#mourajah").val(existingReport.mourajah);
        jQuery("#next_tasmii").val(existingReport.next_tasmii);
        jQuery("#next_mourajah").val(existingReport.next_mourajah);
        jQuery("#notes").val(existingReport.notes);

        // Handle zoom images
        const zoomImages = existingReport.zoom_image_url
            ? JSON.parse(existingReport.zoom_image_url)
            : [];
        if (Array.isArray(zoomImages)) {
            zoomImages.forEach(function (url) {
                preview.append(
                    '<a href="' +
                        url +
                        '" data-lightbox="zoom-images">' +
                        '<img src="' +
                        url +
                        '" style="max-width: 150px; margin-bottom: 20px;">' +
                        "</a>"
                );
            });
        }

        // Initialize Lightbox2
        lightbox.option({
            fadeDuration: 300,
            resizeDuration: 300,
            imageFadeDuration: 300,
            wrapAround: true,
            showImageNumberLabel: true,
        });
    }

    // Show the modal
    jQuery("#report-modal").show();

    // Hide share/remove buttons for button-opened forms
    if (showSearch) {
        const shareReportButton = document.getElementById("share-report");
        shareReportButton.style.display = "none";

        const removeButton = jQuery(".remove-report-button");
        if (removeButton.length) {
            removeButton.remove();
        }
    }
}

  // Open the report form with search input when the "إضافة انجاز" button is clicked
  jQuery("#open-report-modal").click(function () {
    openReportForm(null, true, null, null, true); // Set isAdHoc to true
  });

  // Close the modal when the close button is clicked
  jQuery("#close-report-modal").click(function () {
    jQuery("#report-modal").hide();
  });

  // Update session number when attendance changes
  // jQuery('#attendance').change(function() {
  //     var attendance = jQuery(this).val();
  //     var validAttendances = ['حضور', 'غياب', 'تأجيل المعلم', 'تأجيل ولي أمر'];
  //     var nonValidAttendances = ['تعويض الغياب', 'تعويض التأجيل', 'تجريبي'];
  //     // Determine the current attendance group
  //     var currentAttendanceGroup = validAttendances.includes(attendance) ? 'valid' : 'non-valid';

  //     // Fetch the current session number
  //     var currentSessionNumber = parseInt(jQuery('#session-number').val()) || 0;

  //     // Fetch the student's lessons_number from usermeta
  //     var studentId = jQuery('#selected-student-id-report').val();
  //     if (studentId) {
  //         jQuery.ajax({
  //             url: zuwadPlugin.ajaxurl,
  //             type: 'POST',
  //             data: {
  //                 action: 'get_student_lessons_number',
  //                 student_id: studentId
  //             },
  //             success: function(response) {
  //                 if (response.success) {
  //                     var lessonsNumber = parseInt(response.data.lessons_number) || 0;

  //                     // Calculate the next session number
  //                     var nextSessionNumber = currentSessionNumber;
  //                     if (lastAttendanceGroup === 'valid' && currentAttendanceGroup === 'non-valid') {
  //                         // Moving from valid to non-valid: decrement
  //                         nextSessionNumber = currentSessionNumber - 1;
  //                         if (nextSessionNumber < 1) {
  //                             nextSessionNumber = 0; // Set to 0 if it goes below 1
  //                         }
  //                     } else if (lastAttendanceGroup === 'non-valid' && currentAttendanceGroup === 'valid') {
  //                         // Moving from non-valid to valid: increment
  //                         nextSessionNumber = currentSessionNumber + 1;
  //                         if (nextSessionNumber > lessonsNumber) {
  //                             nextSessionNumber = 1; // Reset if exceeds lessons_number
  //                         }
  //                     }
  //                     // If staying within the same group, do not change the session number

  //                     // Update the last attendance group
  //                     lastAttendanceGroup = currentAttendanceGroup;

  //                     // Set the session number in the form
  //                     jQuery('#session-number').val(nextSessionNumber);
  //                 }
  //             }
  //         });
  //     }
  // });

  // // Save report (ensure the handler is attached only once)
  // if (!isFormHandlerAttached) {
  //     jQuery('#report-form').off('submit').on('submit', function(e) {
  //         e.preventDefault();
  //         e.stopPropagation(); // Prevent event bubbling
  //         // console.log('Form submitted'); // Debugging
  //         var studentId = jQuery('#selected-student-id-report').val();
  //         var formData = jQuery(this).serialize();

  //         if (studentId) {
  //             jQuery.ajax({
  //                 url: zuwadPlugin.ajaxurl,
  //                 type: 'POST',
  //                 data: {
  //                     action: 'save_report',
  //                     student_id: studentId,
  //                     form_data: formData
  //                 },
  //                 success: function(response) {
  //                     alert('تم حفظ الانجاز بنجاح!');
  //                     jQuery('#report-modal').hide();

  //                     // Change the color of the calendar event to green
  //                     var event = calendar.getEventById(studentId);
  //                     if (event) {
  //                         event.setProp('color', 'green');
  //                     }
  //                 },
  //                 error: function(xhr, status, error) {
  //                     alert('حدث خطأ أثناء حفظ الانجاز. يرجى المحاولة مرة أخرى.');
  //                     console.error(xhr.responseText);
  //                 }
  //             });
  //         } else {
  //             alert('الرجاء اختيار طالب.');
  //         }
  //     });

  //     isFormHandlerAttached = true; // Mark the handler as attached
  // }

  // Search functionality
  jQuery("#student-search").on("input", function () {
    var searchQuery = jQuery(this).val();
    if (searchQuery.length > 2) {
      jQuery.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: {
          action: "search_students",
          search: searchQuery,
        },
        success: function (response) {
          if (response) {
            jQuery("#search-results").html(response).show();
          } else {
            jQuery("#search-results")
              .html("<div>No students found.</div>")
              .show();
          }
        },
      });
    } else {
      jQuery("#search-results").hide();
    }
  });

  // // Handle student selection from search results
  // jQuery(document).on('click', '.student-result', function() {
  //     var studentId = jQuery(this).data('id');
  //     var events = calendar.getEvents();

  //     // Show or hide events based on the selected student
  //     events.forEach(function(event) {
  //         if (studentId && event.extendedProps.student_id != studentId) {
  //             event.setProp('display', 'none'); // Hide other students' events
  //         } else {
  //             event.setProp('display', 'auto'); // Show the selected student's events
  //         }
  //     });

  //     jQuery('#search-results').hide(); // Hide the search results after selection
  // });

  window.calendar.render();
  // Add filter functionality
  document
    .getElementById("student-filter")
    .addEventListener("change", function () {
      var studentId = this.value;
      var events = calendar.getEvents();

      // Show or hide events based on the selected student
      events.forEach(function (event) {
        if (studentId && event.extendedProps.student_id != studentId) {
          event.setProp("display", "none"); // Hide other students' events
        } else {
          event.setProp("display", "auto"); // Show the selected student's events
        }
      });
    });

  // Function to reset the form
  function resetReportForm() {
    // Reset form fields
    jQuery("#report-form")[0].reset();

    // Clear the image previews
    jQuery("#zoom-image-preview").empty();

    // Clear the selected student ID
    jQuery("#selected-student-id-report").val("");

    // Clear any validation errors or messages
    jQuery(".error-message").remove();
  }

  // When the modal is opened outside the calendar, reset the form
  jQuery("#open-report-modal").on("click", function () {
    resetReportForm();
    jQuery("#report-modal").show();
  });

  jQuery("#report-form").submit(function (e) {
    e.preventDefault();
    var studentId = jQuery("#selected-student-id-report").val();
    var reportId = jQuery("#report-modal").data("reportId"); // Get the report ID from the modal
    var formData = new FormData(this);
    var isNewReport = !reportId; // Flag to check if this is a new report (first save)

    // Add student_id, report_id, and action to the FormData object
    formData.append("student_id", studentId);
    formData.append("report_id", reportId); // Pass the report ID
    formData.append("action", "save_report");

    if (studentId) {
      // Show loading spinner
      Swal.fire({
        title: "جاري الحفظ...",
        text: "يرجى الانتظار أثناء حفظ البيانات.",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      jQuery.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          console.log('AJAX Response:', response); // Log the full response

          // Close the loading spinner
          Swal.close();

          if (response.success) {
            // Store the newly created report ID for potential WhatsApp sharing
            var savedReportId = response.data && response.data.report_id ? response.data.report_id : null;
            console.log("Saved Report ID:", savedReportId);
            console.log("Is New Report:", isNewReport);
            console.log("Should Hide WhatsApp Button:", shouldHideWhatsAppButton());

            // Get the selected attendance value
            var attendance = jQuery('#attendance').val();
            console.log("Attendance value:", attendance);

            // Attendance types that should trigger automatic WhatsApp sending
            const autoSendAttendances = ["حضور", "تعويض التأجيل", "تعويض الغياب", "تجريبي"];

            // Attendance types that should send special messages instead of reports
            const specialMessageAttendances = ["غياب", "تأجيل ولي أمر", "تأجيل المعلم"];

            // 3. Attendance type is one that triggers automatic sending
            // 4. User has permission to send WhatsApp messages
            // 5. The flag to enable auto-sending is true
            const enableAutoSend = true; // IMPORTANT: Set to true to enable auto-sending from this file

            if (enableAutoSend && isNewReport && savedReportId && autoSendAttendances.includes(attendance) && !shouldHideWhatsAppButton()) {
                // Show message about sending report via WhatsApp
                Swal.fire({
                    icon: "success",
                    title: "تم حفظ الانجاز بنجاح!",
                    text: "جاري ارسال التقرير عبر الواتساب...",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    // After saving, automatically generate and send the report via WhatsApp
                    sendReportViaWhatsApp(savedReportId);
                });
            } else if (enableAutoSend && isNewReport && savedReportId && specialMessageAttendances.includes(attendance) && !shouldHideWhatsAppButton()) {
                // Show message about sending special attendance message via WhatsApp
                Swal.fire({
                    icon: "success",
                    title: "تم حفظ الانجاز بنجاح!",
                    text: "جاري ارسال رسالة الحضور عبر الواتساب...",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    // Send special attendance message instead of report
                    sendAttendanceMessageViaWhatsApp(savedReportId, attendance);
                });
            } else {
                // Regular success message for updates or when WhatsApp is not sent
                Swal.fire({
                    icon: "success",
                    title: "تم حفظ الانجاز بنجاح!",
                    text: "مبروك! تم تسجيل الانجاز بنجاح.",
                    confirmButtonText: "حسناً",
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (window.calendar) {
                            window.calendar.refetchEvents();
                        }
                    }
                });
            }

            // Hide the modal
            jQuery("#report-modal").hide();

            // Reset the form
            resetReportForm();

            // Refresh the calendar to fetch the new event
            if (window.calendar) {
              window.calendar.refetchEvents();
            }
          } else {
            // Show error message
            Swal.fire({
              icon: "error",
              title: "حدث خطأ",
              text:
                "حدث خطأ أثناء حفظ الانجاز: " +
                (response.data || "Unknown error"),
              confirmButtonText: "حسناً",
            });
          }
        },
        error: function (xhr, status, error) {
          // Close the loading spinner
          Swal.close();

          // Show error message
          Swal.fire({
            icon: "error",
            title: "حدث خطأ",
            text: "حدث خطأ أثناء حفظ الانجاز. يرجى المحاولة مرة أخرى.",
            confirmButtonText: "حسناً",
          });
          console.error("AJAX Error:", xhr.responseText);
        },
      });
    } else {
      // Show error message
      Swal.fire({
        icon: "error",
        title: "خطأ",
        text: "الرجاء اختيار طالب.",
        confirmButtonText: "حسناً",
      });
    }
  });

  // Function to send attendance message via WhatsApp
  function sendAttendanceMessageViaWhatsApp(reportId, attendance) {
    if (!reportId) {
      console.error("No report ID provided for attendance message");
      return;
    }

    // Show loading indicator
    Swal.fire({
      title: "جارٍ إرسال رسالة الحضور...",
      html: "يرجى الانتظار بينما نقوم بإرسال الرسالة.",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    // Get report data to extract student information
    jQuery.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: {
        action: "handle_get_report_data",
        report_id: reportId,
        _ajax_nonce: zuwadPlugin.nonce
      },
      success: function(response) {
        if (response.success && response.data) {
          const reportData = response.data;
          const studentId = reportData.student_id;
          const m_id = reportData.m_id;

          // Get student phone number
          jQuery.ajax({
            url: zuwadPlugin.ajaxurl,
            type: "POST",
            data: {
              action: "get_student_phone",
              student_id: studentId,
              _ajax_nonce: zuwadPlugin.nonce
            },
            success: function(phoneResponse) {
              if (phoneResponse.success && phoneResponse.data.phone) {
                let phone_number = phoneResponse.data.phone;
                if (phone_number.charAt(0) !== '+') {
                  phone_number = '+' + phone_number;
                }

                // Send the attendance message
                jQuery.ajax({
                  url: zuwadPlugin.ajaxurl,
                  type: "POST",
                  data: {
                    action: "send_whatsapp_report",
                    phone_number: phone_number,
                    attendance: attendance,
                    attendance_message: 'true',
                    m_id: m_id,
                    report_id: reportId,
                    _ajax_nonce: zuwadPlugin.nonce
                  },
                  success: function(sendResponse) {
                    console.log("Attendance message send response:", sendResponse);
                    Swal.close();

                    if (sendResponse.success) {
                      Swal.fire({
                        icon: "success",
                        title: "تم الإرسال بنجاح",
                        text: "تم إرسال رسالة الحضور بنجاح عبر واتساب",
                        confirmButtonText: "حسناً",
                        timer: 3000,
                        timerProgressBar: true
                      });

                      // Refresh the calendar to update report status
                      if (window.calendar) {
                        window.calendar.refetchEvents();
                      }
                    } else {
                      // Extract error message more carefully
                      let errorMessage = "خطأ غير معروف";

                      if (sendResponse.data && sendResponse.data.message) {
                        errorMessage = sendResponse.data.message;
                      } else if (sendResponse.data && typeof sendResponse.data === 'string') {
                        errorMessage = sendResponse.data;
                      } else if (sendResponse.message) {
                        errorMessage = sendResponse.message;
                      }

                      console.error("Attendance message send failed:", sendResponse);

                      Swal.fire({
                        icon: "error",
                        title: "فشل الإرسال",
                        text: "حدث خطأ أثناء إرسال رسالة الحضور: " + errorMessage,
                        confirmButtonText: "حسناً"
                      });
                    }
                  },
                  error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire({
                      icon: "error",
                      title: "خطأ في الاتصال",
                      text: "حدث خطأ في الاتصال أثناء إرسال رسالة الحضور",
                      confirmButtonText: "حسناً"
                    });
                    console.error("Error sending attendance message:", xhr.responseText);
                  }
                });
              } else {
                Swal.close();
                Swal.fire({
                  icon: "error",
                  title: "خطأ",
                  text: "لم يتم العثور على رقم هاتف الطالب",
                  confirmButtonText: "حسناً"
                });
              }
            },
            error: function(xhr, status, error) {
              Swal.close();
              Swal.fire({
                icon: "error",
                title: "خطأ",
                text: "حدث خطأ أثناء جلب بيانات الطالب",
                confirmButtonText: "حسناً"
              });
              console.error("Error getting student phone:", xhr.responseText);
            }
          });
        } else {
          Swal.close();
          Swal.fire({
            icon: "error",
            title: "خطأ",
            text: "لم يتم العثور على بيانات التقرير",
            confirmButtonText: "حسناً"
          });
        }
      },
      error: function(xhr, status, error) {
        Swal.close();
        Swal.fire({
          icon: "error",
          title: "خطأ",
          text: "حدث خطأ أثناء جلب بيانات التقرير",
          confirmButtonText: "حسناً"
        });
        console.error("Error getting report data:", xhr.responseText);
      }
    });
  }

  // Function to send report via WhatsApp automatically
  function sendReportViaWhatsApp(reportId) {
    if (!reportId) {
      console.error("No report ID provided for WhatsApp sharing");
      return;
    }

    // Show loading indicator
    Swal.fire({
      title: "جارٍ إنشاء التقرير للواتساب...",
      html: "يرجى الانتظار بينما نقوم بإنشاء وإرسال التقرير.",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    // Add retry logic for fetching report data
    const maxRetries = 5;
    let retryCount = 0;

    function tryFetchReportData() {
      ZuwadReportUtils.ReportGenerator.fetchReportData(reportId)
        .then(function(reportData) {
          console.log("Report data fetched successfully");
          return ZuwadReportUtils.ReportGenerator.renderReport(reportData);
        })
        .then(function(renderData) {
          console.log("Report rendered successfully");
          return ZuwadReportUtils.WhatsAppSender.sendWithRetry(reportId, renderData, {
            showLoading: false
          });
        })
        .then(function(result) {
          console.log("WhatsApp send completed", result);
          Swal.close();
          
          // Show success message
          Swal.fire({
            icon: "success",
            title: "تم الإرسال بنجاح",
            text: result.already_sent ? 
              "تم إرسال التقرير بالفعل مسبقاً" : 
              "تم إرسال التقرير بنجاح عبر واتساب",
            confirmButtonText: "حسناً",
            timer: 3000,
            timerProgressBar: true
          });
          
          // Refresh the calendar to update report status
          if (window.calendar) {
            window.calendar.refetchEvents();
          }
        })
        .catch(function(error) {
          console.error("Error in report generation/sending:", error);
          
          // Get the error message based on error structure
          let errorMessage = "خطأ غير معروف";
          let isProcessingError = false;
          let isNetworkError = false;
          
          if (error && error.error && error.error.message) {
            // New error format from updated fetchReportData
            errorMessage = error.error.message;
            isProcessingError = error.isProcessing;
            isNetworkError = error.isNetworkError;
          } else if (error && error.message) {
            // Standard error object
            errorMessage = error.message;
          } else if (typeof error === 'string') {
            // String error
            errorMessage = error;
          }
          
          // Check if we should retry automatically
          if (retryCount < maxRetries && (isProcessingError || isNetworkError)) {
            retryCount++;
            console.log(`Retrying... Attempt ${retryCount} of ${maxRetries}`);
            
            // Wait progressively longer between retries: 2s, 4s, 6s, 8s, 10s
            setTimeout(tryFetchReportData, retryCount * 2000);
            return;
          }
          
          Swal.close();
          
          // Determine the error message to display
          let titleText = "فشل الإرسال";
          let detailedMessage = "";
          
          // Format the error message based on type
          if (isProcessingError) {
            titleText = "التقرير قيد المعالجة";
            detailedMessage = "لم يتم العثور على بيانات التقرير بعد، قد يكون التقرير لا يزال قيد المعالجة.";
          } else if (isNetworkError) {
            titleText = "خطأ في الاتصال";
            detailedMessage = "حدث خطأ في الاتصال بالخادم. يرجى التحقق من اتصالك بالإنترنت.";
          } else {
            detailedMessage = `حدث خطأ أثناء إرسال التقرير: ${errorMessage}`;
          }
          
          // Show error message with retry button
          Swal.fire({
            icon: "error",
            title: titleText,
            html: `
              <p>${detailedMessage}</p>
              <button id="retry-whatsapp-send" class="swal2-confirm swal2-styled" style="background-color: #25D366; margin-top: 10px;">إعادة المحاولة</button>
            `,
            showConfirmButton: true,
            confirmButtonText: "حسناً"
          }).then(() => {
            // Add event listener for retry button
            const retryButton = document.getElementById('retry-whatsapp-send');
            if (retryButton) {
              retryButton.addEventListener('click', function() {
                retryCount = 0; // Reset retry count
                sendReportViaWhatsApp(reportId);
              });
            }
          });
        });
    }

    // Start the first attempt
    tryFetchReportData();
  }

  // Check if WhatsApp button should be hidden based on user role
  function shouldHideWhatsAppButton() {
    // For testing purposes, we want auto-send to work for all roles including teachers
    // Return false to indicate it should NOT be hidden
    return false;
  }
  
  // Function to handle retrying WhatsApp sending
  function sendWhatsAppWithRetryData(retryData) {
    console.log("Retrying WhatsApp send with data:", retryData);
    
    // Show loading message
    Swal.fire({
      title: "جاري إعادة المحاولة...",
      text: "يرجى الانتظار قليلاً",
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    // Format phone number - ensure it starts with +
    let phone_number = retryData.studentPhone;
    if (phone_number && phone_number.charAt(0) !== '+') {
      phone_number = '+' + phone_number;
    }
    
    // Use our unified WhatsApp sending system
    const args = {
      action: "send_whatsapp_report",
      phone_number: phone_number,
      media_url: retryData.imageUrl, // Use media_url directly
      m_id: retryData.m_id, // Include m_id for device ID selection
      report_id: retryData.reportData.id, // Include report_id to prevent duplicate sends
      _ajax_nonce: zuwadPlugin.nonce
    };
    
    console.log("Sending WhatsApp with m_id:", retryData.m_id, "and report_id:", retryData.reportData.id);
    
    // Send the request using our unified system
    jQuery.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: args,
      success: function(response) {
        console.log("WhatsApp retry response:", response);
        
        // Check if sending was successful or if message was already sent
        if (response.success && (!response.has_inner_error || response.already_sent)) {
          // Remove the temporary image after successful sending
          jQuery.ajax({
            url: zuwadPlugin.ajaxurl,
            type: "POST",
            data: {
              action: "delete_temp_whatsapp_image",
              image_url: retryData.imageUrl,
              _ajax_nonce: zuwadPlugin.nonce
            },
            success: function(deleteResponse) {
              console.log("Image deletion response:", deleteResponse);
            }
          });
          
          // Update report WhatsApp status
          jQuery.ajax({
            url: zuwadPlugin.ajaxurl,
            type: "POST",
            data: {
              action: "update_report_whatsapp_status",
              report_id: retryData.reportData.id,
              _ajax_nonce: zuwadPlugin.nonce,
            },
            success: function() {
              let successMessage = "تم إرسال التقرير عبر الواتساب بنجاح";
              if (response.already_sent) {
                successMessage = "تم إرسال التقرير بالفعل مسبقاً";
              }
              
              Swal.fire({
                icon: "success",
                title: "تم الإرسال بنجاح",
                text: successMessage,
                confirmButtonText: "حسناً",
              });
              
              // Refresh the calendar
              if (window.calendar) {
                window.calendar.refetchEvents();
              }
            }
          });
        } else {
          // Still failed after retry
          const errorMessage = ZuwadReportUtils.extractErrorMessage ? 
            ZuwadReportUtils.extractErrorMessage(response) : 
            "فشل إرسال التقرير عبر الواتساب";
          
          // Show error with retry button again
          Swal.fire({
            icon: "warning",
            title: "تم الحفظ لكن فشل الإرسال",
            html: `
              <p>تم حفظ الانجاز بنجاح لكن ${errorMessage}</p>
              <button id="retry-whatsapp-send" class="swal2-confirm swal2-styled" style="background-color: #25D366; margin-top: 10px;">إعادة المحاولة</button>
            `,
            showConfirmButton: true,
            confirmButtonText: "حسناً",
          });
          
          // Add event listener for retry button
          setTimeout(() => {
            const retryButton = document.getElementById('retry-whatsapp-send');
            if (retryButton) {
              retryButton.addEventListener('click', function() {
                // Close the current Swal dialog
                Swal.close();
                
                // Try sending again with the stored data
                sendWhatsAppWithRetryData(retryData);
              });
            }
          }, 100);
        }
      },
      error: function(xhr, status, error) {
        console.error("WhatsApp retry request failed:", error);
        
        // Show error with retry button
        Swal.fire({
          icon: "error",
          title: "فشل الاتصال",
          html: `
            <p>فشل الاتصال بالخادم. يرجى التحقق من اتصالك بالإنترنت وإعادة المحاولة.</p>
            <button id="retry-whatsapp-send" class="swal2-confirm swal2-styled" style="background-color: #25D366; margin-top: 10px;">إعادة المحاولة</button>
          `,
          showConfirmButton: true,
          confirmButtonText: "حسناً",
        });
        
        // Add event listener for retry button
        setTimeout(() => {
          const retryButton = document.getElementById('retry-whatsapp-send');
          if (retryButton) {
            retryButton.addEventListener('click', function() {
              // Close the current Swal dialog
              Swal.close();
              
              // Try sending again with the stored data
              sendWhatsAppWithRetryData(retryData);
            });
          }
        }, 100);
      }
    });
  }

  // Function to reset the report form
  function resetReportForm() {
    jQuery("#report-form")[0].reset(); // Reset the form fields
    jQuery("#selected-student-id-report").val(""); // Clear the selected student ID
    jQuery("#report-modal").removeData("reportId"); // Clear the report ID
    jQuery(".remove-report-button").remove(); // Remove the remove button if it exists
    jQuery("#share-report").hide(); // Hide the share report button

    // Clear any relevant local storage items
    localStorage.removeItem("selectedStudentId");
    localStorage.removeItem("reportId");
    // window.location.reload(); // Reload the page
  }
  
  // Helper function to convert data URI to Blob object
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

  // Add click handler for the WhatsApp share button
  jQuery("#share-report").on("click", function() {
    const reportId = jQuery("#report-modal").data("reportId");
    
    if (!reportId) {
      Swal.fire({
        icon: "error",
        title: "خطأ",
        text: "لم يتم العثور على معرف التقرير",
        confirmButtonText: "حسناً"
      });
      return;
    }
    
    // Always show confirmation before sending
    Swal.fire({
      title: "إرسال التقرير عبر الواتساب",
      text: "هل تريد إرسال التقرير عبر الواتساب؟",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "نعم، أرسل",
      cancelButtonText: "إلغاء",
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        // Close the report modal
        jQuery("#report-modal").hide();
        
        // Send the report
        sendReportViaWhatsApp(reportId);
      }
    });
  });
});
