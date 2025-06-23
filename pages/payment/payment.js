jQuery(document).ready(function ($) {
  // Global variables for pagination
  let currentPage = 1;
  let rowsPerPage = 10;
  let filteredRows = [];
  
  // Add these functions at the top
  function filterTable() {
    const reminderFilter = $("#reminder-filter").val();
    const historyFilter = $("#history-filter").val();
    const searchQuery = $("#search-input").val().toLowerCase();

    // Reset filtered rows
    filteredRows = [];

    $("#students-table tbody tr").each(function () {
      const $row = $(this);
      const currentReminder = $row.find(".reminder-dropdown").val();
      const reminderHistoryAttr = $row
        .find(".student-name")
        .attr("data-reminder-date");
      const statusDateAttr = $row
        .find(".student-name")
        .attr("data-status-date");
      const studentName = $row.find(".student-name").text().trim();
      const studentId = $row.find("td:first-child").text().trim();
      const teacherName = $row.find("td:nth-child(3)").text().trim();
      const phoneNumber = $row.find(".payment-phone").text().trim();
      const country = $row.find("td:nth-child(5)").text().trim();

      let showRow = true;

      // Search filter
      if (searchQuery) {
        const rowText = `${studentId} ${studentName} ${teacherName} ${phoneNumber} ${country}`.toLowerCase();
        showRow = rowText.includes(searchQuery);
      }

      // Filter by reminder status if filter is selected
      if (showRow && reminderFilter && reminderFilter !== "") {
        showRow = currentReminder === reminderFilter;
      }

      // Filter by history date if history filter is selected
      if (showRow && historyFilter && historyFilter !== "") {
        try {
          const currentDate = new Date();
          const filterDays = parseInt(historyFilter);
          let foundMatchingEntry = false;

          // Always check payment status change date for ALL filters
          if (statusDateAttr) {
            const statusDate = new Date(statusDateAttr);
            const daysDiff = Math.floor(
              (currentDate - statusDate) / (1000 * 60 * 60 * 24)
            );

            // If no reminder filter is selected, or if reminder filter matches current reminder
            if (
              !reminderFilter ||
              reminderFilter === "" ||
              reminderFilter === currentReminder
            ) {
              if (daysDiff === filterDays) {
                foundMatchingEntry = true;
              }
            }
          }

          // Also check reminder history for today's changes (especially for filterDays === 0)
          if (
            !foundMatchingEntry &&
            reminderHistoryAttr &&
            reminderHistoryAttr !== ""
          ) {
            try {
              const history = JSON.parse(reminderHistoryAttr);
              if (Array.isArray(history) && history.length > 0) {
                // Check if any reminder change matches the filter days
                foundMatchingEntry = history.some((entry) => {
                  if (entry.timestamp) {
                    const entryDate = new Date(entry.timestamp);
                    const daysDiff = Math.floor(
                      (currentDate - entryDate) / (1000 * 60 * 60 * 24)
                    );

                    // If reminder filter is selected, check if this entry matches the filter
                    if (reminderFilter && reminderFilter !== "") {
                      return (
                        entry.status === reminderFilter &&
                        daysDiff === filterDays
                      );
                    } else {
                      // No reminder filter, just check date
                      return daysDiff === filterDays;
                    }
                  }
                  return false;
                });
              }
            } catch (e) {
              // Ignore parsing errors
            }
          }

          // If still no match found and student has no reminder history,
          // but has status date, check if it matches the filter
          if (
            !foundMatchingEntry &&
            (!reminderHistoryAttr ||
              reminderHistoryAttr === "" ||
              reminderHistoryAttr === "لا يوجد سجل تنبيهات") &&
            statusDateAttr
          ) {
            const statusDate = new Date(statusDateAttr);
            const daysDiff = Math.floor(
              (currentDate - statusDate) / (1000 * 60 * 60 * 24)
            );

            // If no reminder filter is selected, or if reminder filter matches current reminder
            if (
              !reminderFilter ||
              reminderFilter === "" ||
              reminderFilter === currentReminder
            ) {
              foundMatchingEntry = daysDiff === filterDays;
            }
          }

          showRow = foundMatchingEntry;
        } catch (e) {
          console.error(
            `Error parsing reminder history for ${studentName}:`,
            e,
            reminderHistoryAttr
          );
          showRow = false;
        }
      }

      // Store filtered rows for pagination
      if (showRow) {
        filteredRows.push($row);
      }
      
      // Hide all rows initially - pagination will show the correct ones
      $row.hide();
    });

    // Reset to first page when filters change
    currentPage = 1;
    
    // Apply pagination to filtered rows
    applyPagination();
    
    // Update visible rows count and statistics
    updateVisibleRowsCount(filteredRows.length);
    updateStatistics();
  }
  
  // Function to apply pagination
  function applyPagination() {
    // Hide all rows first
    $("#students-table tbody tr").hide();
    
    // Calculate which rows to show
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, filteredRows.length);
    
    // Show only the rows for current page
    for (let i = startIndex; i < endIndex; i++) {
      filteredRows[i].show();
    }
    
    // Update pagination controls
    updatePaginationControls();
  }
  
  // Function to update pagination controls
  function updatePaginationControls() {
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    
    // Clear existing pagination
    $(".pagination-container").remove();
    
    // Don't show pagination if there's only one page or no results
    if (totalPages <= 1) {
      return;
    }
    
    // Create pagination container
    const $paginationContainer = $('<div class="pagination-container"></div>');
    
    // Add previous button
    const $prevButton = $('<button class="pagination-btn prev-btn">السابق</button>');
    if (currentPage === 1) {
      $prevButton.addClass('disabled');
    }
    $paginationContainer.append($prevButton);
    
    // Determine which page numbers to show
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    // Adjust if we're near the end
    if (endPage - startPage < 4 && startPage > 1) {
      startPage = Math.max(1, endPage - 4);
    }
    
    // Add page numbers
    for (let i = startPage; i <= endPage; i++) {
      const $pageBtn = $(`<button class="pagination-btn page-btn${i === currentPage ? ' active' : ''}">${i}</button>`);
      $paginationContainer.append($pageBtn);
    }
    
    // Add next button
    const $nextButton = $('<button class="pagination-btn next-btn">التالي</button>');
    if (currentPage === totalPages) {
      $nextButton.addClass('disabled');
    }
    $paginationContainer.append($nextButton);
    
    // Append pagination to table
    $("#students-table").after($paginationContainer);
    
    // Add event listeners
    $(".prev-btn").on("click", function() {
      if (currentPage > 1) {
        currentPage--;
        applyPagination();
      }
    });
    
    $(".next-btn").on("click", function() {
      if (currentPage < totalPages) {
        currentPage++;
        applyPagination();
      }
    });
    
    $(".page-btn").on("click", function() {
      currentPage = parseInt($(this).text());
      applyPagination();
    });
  }

  // Add function to update visible rows count
  function updateVisibleRowsCount(count) {
    let countDisplay = $("#visible-rows-count");
    if (!countDisplay.length) {
      countDisplay = $(
        '<div id="visible-rows-count" style="/*! margin: -20px 0; */margin-top: -20px;margin-bottom: -10px;"></div>'
      );
      $("#students-table").before(countDisplay);
    }
    
    const totalPages = Math.ceil(count / rowsPerPage);
    const currentPageInfo = count > 0 ? `الصفحة ${currentPage} من ${totalPages}` : '';
    countDisplay.text(`عدد النتائج: ${count} ${currentPageInfo}`);
  }

  // Add function to update statistics (optimized for performance)
  function updateStatistics() {
    // Use filteredRows instead of visibleRows to count ALL matching rows regardless of pagination
    // This ensures statistics reflect all data that matches the current filters

    // Count by reminder status
    let noReminderCount = 0;
    let firstReminderCount = 0;
    let secondReminderCount = 0;
    let thirdReminderCount = 0;
    let noResponseCount = 0;
    let currencyTotals = {}; // Object to store totals by currency
    let todayChanges = 0;

    const today = new Date();
    const todayString = today.toISOString().split("T")[0]; // Get today's date in YYYY-MM-DD format

    // Loop through all filtered rows, not just visible ones
    $.each(filteredRows, function(index, row) {
      // row is already a jQuery object, no need to wrap it with $()
      const reminderStatus = row.find(".reminder-dropdown").val();
      const amount =
        parseFloat(
          row
            .find("td:nth-child(9)")
            .text()
            .replace(/[^\d.-]/g, "")
        ) || 0; // Amount column
      const currency = row.find("td:nth-child(8)").text().trim(); // Currency column
      const statusDate = row.find(".student-name").attr("data-status-date");

      // Count reminder statuses
      switch (reminderStatus) {
        case "لا يوجد":
          noReminderCount++;
          break;
        case "التنبيه الاول":
          firstReminderCount++;
          break;
        case "التنبيه الثاني":
          secondReminderCount++;
          break;
        case "التنبيه التالت":
          thirdReminderCount++;
          break;
        case "لم يتم الرد":
          noResponseCount++;
          break;
      }

      // Sum amounts by currency
      if (amount > 0 && currency) {
        if (!currencyTotals[currency]) {
          currencyTotals[currency] = 0;
        }
        currencyTotals[currency] += amount;
      }

      // Count today's changes - check both payment status changes AND reminder changes
      let hasChangeToday = false;

      // Check payment status change date
      if (statusDate) {
        const statusDateOnly = statusDate.split(" ")[0]; // Get just the date part (YYYY-MM-DD)
        if (statusDateOnly === todayString) {
          hasChangeToday = true;
        }
      }

      // Check reminder history for today's changes
      if (!hasChangeToday) {
        const reminderHistoryAttr = row
          .find(".student-name")
          .attr("data-reminder-date");
        if (reminderHistoryAttr && reminderHistoryAttr !== "") {
          try {
            const history = JSON.parse(reminderHistoryAttr);
            if (Array.isArray(history) && history.length > 0) {
              // Check if any reminder change happened today
              hasChangeToday = history.some((entry) => {
                if (entry.timestamp) {
                  const entryDateOnly = entry.timestamp.split(" ")[0];
                  return entryDateOnly === todayString;
                }
                return false;
              });
            }
          } catch (e) {
            // Ignore parsing errors
          }
        }
      }

      if (hasChangeToday) {
        todayChanges++;
      }
    });

    // Update statistics cards (always fresh data, no caching)
    $("#total-students").text(filteredRows.length);
    $("#no-reminder-count").text(noReminderCount);
    $("#first-reminder-count").text(firstReminderCount);
    $("#second-reminder-count").text(secondReminderCount);
    $("#third-reminder-count").text(thirdReminderCount);
    $("#no-response-count").text(noResponseCount);

    // Format amounts by currency
    let amountDisplay = "";
    const currencyKeys = Object.keys(currencyTotals);

    if (currencyKeys.length > 0) {
      const formattedAmounts = currencyKeys.map((currency) => {
        const amount = currencyTotals[currency];
        // Use a more reliable number formatting that works consistently
        const formattedAmount = Math.round(amount)
          .toString()
          .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        return `${formattedAmount} ${currency}`;
      });
      amountDisplay = formattedAmounts.join(" | ");
    } else {
      amountDisplay = "0";
    }

    $("#total-amount").text(amountDisplay);
    $("#today-changes").text(todayChanges);
  }

  // Add event listeners for filters
  $("#reminder-filter, #history-filter").on("change", filterTable);
  
  // Add event listener for search input with debounce
  let searchTimeout;
  $("#search-input").on("input", function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(filterTable, 300);
  });

  // Initialize filters, pagination and statistics on page load
  filterTable();

  // Update statistics when page loads
  $(document).ready(function () {
    setTimeout(function () {
      updateStatistics();
    }, 100); // Reduced delay for faster loading
  });

  // Add click handlers for statistics cards to filter table (entire card clickable)
  $(document).on("click", ".stat-card:has(#no-reminder-count)", function () {
    $("#reminder-filter").val("لا يوجد");
    $("#history-filter").val("");
    filterTable();
  });

  $(document).on("click", ".stat-card:has(#first-reminder-count)", function () {
    $("#reminder-filter").val("التنبيه الاول");
    $("#history-filter").val("");
    filterTable();
  });

  $(document).on(
    "click",
    ".stat-card:has(#second-reminder-count)",
    function () {
    $("#reminder-filter").val("التنبيه الثاني");
    $("#history-filter").val("");
    filterTable();
    }
  );

  $(document).on("click", ".stat-card:has(#third-reminder-count)", function () {
    $("#reminder-filter").val("التنبيه التالت");
    $("#history-filter").val("");
    filterTable();
  });

  $(document).on("click", ".stat-card:has(#no-response-count)", function () {
    $("#reminder-filter").val("لم يتم الرد");
    $("#history-filter").val("");
    filterTable();
  });

  $(document).on("click", ".stat-card:has(#today-changes)", function () {
    $("#reminder-filter").val("");
    $("#history-filter").val("0");
    filterTable();
  });

  $(document).on("click", ".stat-card:has(#total-students)", function () {
    $("#reminder-filter").val("");
    $("#history-filter").val("");
    filterTable();
  });

  $(document).on("click", ".stat-card:has(#total-amount)", function () {
    $("#reminder-filter").val("");
    $("#history-filter").val("");
    filterTable();
  });

  // Fetch payment data and populate the table (always fresh data, no caching)
  $.ajax({
    url: zuwadPlugin.ajaxurl,
    method: "POST",
    cache: false, // Disable caching to ensure fresh data
    data: {
      action: "fetch_payment_data",
      _: new Date().getTime(), // Cache buster
    },
    success: function (response) {
      // console.log("AJAX success response:", response); // Log the response
      if (response.success) {
        var tbody = $("#payments-table tbody");
        tbody.empty(); // Clear existing rows

        // Populate the table with payment data
        response.data.forEach(function (student) {
          var row = `
                        <tr>
                            <td>${student.m_id}</td>
                            <td>${student.name}</td>
                            <td>${student.teacher}</td>
                            <td class="payment-phone" data-student-id="${
                              student.m_id
                            }">${student.payment_phone}</td>
                            <td>${student.country}</td>
                            <td>${student.lessons_number}</td>
                            <td>${student.lesson_duration}</td>
                            <td>${student.currency}</td>
                            <td>${student.amount}</td>
                            <td>
                                <select class="payment-status-dropdown" data-student-id="${
                                  student.m_id
                                }" data-previous="${student.payment_status}">
                                    <option value="في انتظار الدفع" ${
                                      student.payment_status ===
                                      "في انتظار الدفع"
                                        ? "selected"
                                        : ""
                                    }>في انتظار الدفع</option>
                                    <option value="مدفوع" ${
                                      student.payment_status === "مدفوع"
                                        ? "selected"
                                        : ""
                                    }>مدفوع</option>
                                    <option value="ملغي" ${
                                      student.payment_status === "ملغي"
                                        ? "selected"
                                        : ""
                                    }>ملغي</option>
                                </select>
                            </td>
                            <td class="notes" data-student-id="${
                              student.m_id
                            }">${student.notes}</td>
                            <td><button class="edit-student" data-student-id="${
                              student.m_id
                            }">تعديل</button></td>
                        </tr>
                    `;
          tbody.append(row);
        });
      } else {
        alert("Failed to fetch payment data.");
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX error:", status, error); // Log any AJAX errors
    },
  });

  // Payment status is now automatically updated when a receipt is uploaded

  // Handle payment phone click
  $(".payment-phone").click(function () {
    var $this = $(this); // Store reference to the clicked element
    var studentId = $this.data("student-id"); // This should hold the m_id
    var currentPhone = $this.text().trim();

    // Show a prompt to change the payment phone
    Swal.fire({
      title: "تغيير رقم الهاتف",
      input: "text",
      inputValue: currentPhone,
      showCancelButton: true,
      confirmButtonText: "تأكيد",
      cancelButtonText: "إلغاء",
      preConfirm: (newPhone) => {
        if (!newPhone) {
          Swal.showValidationMessage("يرجى إدخال رقم الهاتف.");
        }
        return newPhone;
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Update the payment phone in the database
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          method: "POST",
          data: {
            action: "update_payment_phone",
            student_id: studentId, // This is m_id
            payment_phone: result.value,
          },
          success: function (response) {
            // console.log("AJAX success response:", response); // Log the response
            if (response.success) {
              // Update the displayed phone number in the table
              $this.text(result.value); // Update the text in the table
            } else {
              Swal.fire("خطأ", "حدث خطأ أثناء تحديث رقم الهاتف.", "error");
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX error:", status, error); // Log any AJAX errors
          },
        });
      }
    });
  });

  // Handle notes click
  $(".notes").click(function () {
    var $this = $(this); // Store reference to the clicked element
    var studentId = $this.data("student-id"); // This should hold the m_id
    var currentNotes = $this.text().trim();

    // Show a prompt to change the notes
    Swal.fire({
      title: "تغيير الملاحظات",
      input: "textarea",
      inputValue: currentNotes,
      showCancelButton: true,
      confirmButtonText: "تأكيد",
      cancelButtonText: "إلغاء",
      preConfirm: (newNotes) => {
        // if (!newNotes) {
        //     Swal.showValidationMessage('يرجى إدخال الملاحظات.');
        // }
        return newNotes;
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Update the notes in the database
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          method: "POST",
          data: {
            action: "update_notes",
            student_id: studentId, // This is m_id
            notes: result.value,
          },
          success: function (response) {
            // console.log("AJAX success response:", response); // Log the response
            if (response.success) {
              // Update the displayed notes in the table
              $this.text(result.value); // Update the text in the table
            } else {
              Swal.fire("خطأ", "حدث خطأ أثناء تحديث الملاحظات.", "error");
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX error:", status, error); // Log any AJAX errors
          },
        });
      }
    });
  });

  // Handle notes click
  $(".payment_notes").click(function () {
    var $this = $(this); // Store reference to the clicked element
    var studentId = $this.data("student-id"); // This should hold the m_id
    var currentNotes = $this.text().trim();

    // Show a prompt to change the notes
    Swal.fire({
      title: "تغيير الملاحظات",
      input: "textarea",
      inputValue: currentNotes,
      showCancelButton: true,
      confirmButtonText: "تأكيد",
      cancelButtonText: "إلغاء",
      preConfirm: (newNotes) => {
        // if (!newNotes) {
        //     Swal.showValidationMessage('يرجى إدخال الملاحظات.');
        // }
        return newNotes;
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Update the notes in the database
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          method: "POST",
          data: {
            action: "update_payment_notes",
            student_id: studentId, // This is m_id
            payment_notes: result.value,
          },
          success: function (response) {
            // console.log("AJAX success response:", response); // Log the response
            if (response.success) {
              // Update the displayed notes in the table
              $this.text(result.value); // Update the text in the table
            } else {
              Swal.fire("خطأ", "حدث خطأ أثناء تحديث الملاحظات.", "error");
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX error:", status, error); // Log any AJAX errors
          },
        });
      }
    });
  });

  $(document).on("focus", ".reminder-dropdown", function () {
    $(this).data("previous-value", $(this).val());
  });

  // Handle reminder dropdown changes
  $(document).on("change", ".reminder-dropdown", function () {
    const studentId = $(this).data("student-id");
    const newReminder = $(this).val();
    const previousReminder = $(this).data("previous-value") || "لا يوجد";
    const $dropdown = $(this);
    
    // Check if this is a valid transition that would trigger a WhatsApp message
    const isValidTransition =
      (previousReminder === "لا يوجد" && newReminder === "التنبيه الاول") ||
      (previousReminder === "التنبيه الاول" &&
        newReminder === "التنبيه الثاني") ||
      (previousReminder === "التنبيه الثاني" &&
        newReminder === "التنبيه التالت") ||
      (previousReminder === "التنبيه التالت" && newReminder === "لم يتم الرد");
    
    // If this is a valid transition, show confirmation dialog first
    if (isValidTransition) {
      Swal.fire({
        title: "تأكيد إرسال رسالة واتساب",
        text: `هل أنت متأكد أنك تريد تغيير التنبيه من "${previousReminder}" إلى "${newReminder}" وإرسال رسالة واتساب؟`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "نعم، أرسل الرسالة",
        cancelButtonText: "لا، تراجع",
      }).then((result) => {
        if (result.isConfirmed) {
          // User confirmed, proceed with the update and message sending
          updateReminderAndSendMessage();
        } else {
          // User canceled, revert the dropdown to previous value
          $dropdown.val(previousReminder);
        }
      });
    } else {
      // Not a transition that sends a message, just update the reminder
      updateReminderAndSendMessage();
    }
    
    // Function to update reminder and send WhatsApp message
    function updateReminderAndSendMessage() {
      // First update the reminder status
      $.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: {
          action: "update_reminder",
          student_id: studentId,
          reminder: newReminder,
        },
        success: function (response) {
          if (response.success) {
            // Then update the reminder date
            $.ajax({
              url: zuwadPlugin.ajaxurl,
              method: "POST",
              data: {
                action: "update_reminder_date",
                student_id: studentId,
                reminder: newReminder,
                current_time: new Date().toISOString(),
              },
              success: function (dateResponse) {
                if (dateResponse.success) {
                  // Update the data attribute for the name cell with raw history
                  const $nameCell = $(
                    `.student-name[data-student-id="${studentId}"]`
                  );
                  $nameCell.attr(
                    "data-reminder-date",
                    JSON.stringify(dateResponse.data.raw_history)
                  );

                  // Refresh the filter to update the table display and statistics
                  filterTable();

                  // Only send WhatsApp message if this is a valid transition
                  if (isValidTransition) {
                    // Show loading indicator
                    Swal.fire({
                      title: "جاري إرسال الرسالة...",
                      text: "يرجى الانتظار بينما يتم إرسال رسالة واتساب",
                      allowOutsideClick: false,
                      didOpen: () => {
                        Swal.showLoading();
                      },
                    });
                    
                    // Send the WhatsApp message
                    $.ajax({
                      url: zuwadPlugin.ajaxurl,
                      method: "POST",
                      data: {
                        action: "send_whatsapp_reminder",
                        student_id: studentId,
                        reminder: newReminder,
                        previous_reminder: previousReminder,
                      },
                      success: function (whatsappResponse) {
                        // console.log("WhatsApp API response:", whatsappResponse);
                        
                        if (whatsappResponse.success) {
                          // Log more details about the response
                          // console.log("WhatsApp reminder sent successfully!", whatsappResponse);
                          
                          // Show a small notification that message was sent
                          Swal.fire({
                            title: "تم إرسال الرسالة",
                            text: "تم إرسال رسالة واتساب بنجاح",
                            icon: "success",
                            timer: 2000,
                            showConfirmButton: false,
                          });
                        } else {
                          // Log detailed error information
                          console.error(
                            "Failed to send WhatsApp reminder:",
                            whatsappResponse
                          );
                          
                          // Get error message from response if available
                          let errorMsg = "حدث خطأ أثناء إرسال رسالة واتساب";
                          if (
                            whatsappResponse.data &&
                            whatsappResponse.data.message
                          ) {
                            errorMsg = whatsappResponse.data.message;
                          }
                          
                          // Only show error if there was a valid transition attempt
                          if (
                            (previousReminder === "لا يوجد" &&
                              newReminder === "التنبيه الاول") ||
                            (previousReminder === "التنبيه الاول" &&
                              newReminder === "التنبيه الثاني") ||
                            (previousReminder === "التنبيه الثاني" &&
                              newReminder === "التنبيه التالت") ||
                            (previousReminder === "التنبيه التالت" &&
                              newReminder === "لم يتم الرد")
                          ) {
                            Swal.fire({
                              title: "خطأ في إرسال الرسالة",
                              text: errorMsg,
                              icon: "error",
                              showConfirmButton: true,
                            });
                          }
                        }
                      },
                      error: function (xhr, status, error) {
                        console.error(
                          "AJAX error sending WhatsApp:",
                          status,
                          error
                        );
                        Swal.fire({
                          title: "خطأ في الاتصال",
                          text: "فشل الاتصال بالخادم أثناء إرسال رسالة واتساب",
                          icon: "error",
                          showConfirmButton: true,
                        });
                      },
                    });
                  }
                }
              },
            });
          } else {
            alert("Failed to update reminder status");
          }
        },
        error: function () {
          alert("Error updating reminder status");
        },
      });
    }
  });

  // Handle name click for dates popup
  $(document).on("click", ".student-name", function () {
    const reminderHistoryAttr = $(this).attr("data-reminder-date");
    const statusDate = $(this).attr("data-status-date") || "لا يوجد";

    let reminderHtml = "لا يوجد سجل تنبيهات";
    if (reminderHistoryAttr && reminderHistoryAttr !== "") {
      try {
        const historyArray = JSON.parse(reminderHistoryAttr);

        if (Array.isArray(historyArray) && historyArray.length > 0) {
          reminderHtml = historyArray
            .map((entry) => {
              // Properly decode the status if it contains Unicode escape sequences
              let decodedStatus = entry.status;
              if (
                typeof decodedStatus === "string" &&
                decodedStatus.includes("u0")
              ) {
                try {
                  // Replace Unicode escape sequences with proper characters
                  decodedStatus = decodedStatus.replace(
                    /u([0-9a-fA-F]{4})/g,
                    function (match, code) {
                    return String.fromCharCode(parseInt(code, 16));
                    }
                  );
                } catch (e) {
                  console.warn(
                    "Failed to decode Unicode in status:",
                    decodedStatus
                  );
                  // Keep original status if decoding fails
                }
              }
              return `${decodedStatus} : ${entry.date} الساعة ${entry.time}`;
            })
            .join("<br>");
        }
      } catch (e) {
        console.error(
          "Error parsing reminder history:",
          e,
          reminderHistoryAttr
        );
        reminderHtml = "لا يوجد سجل تنبيهات";
      }
    }

    Swal.fire({
      title: "تواريخ مهمة",
      html: `
            <div style="text-align: right; direction: rtl;">
                <p><strong>تاريخ تغيير حالة الدفع:</strong> ${statusDate}</p>
                <hr>
                <p><strong>سجل التنبيهات:</strong></p>
                <div style="max-height: 200px; overflow-y: auto;">
                    ${reminderHtml}
                </div>
            </div>
        `,
      confirmButtonText: "إغلاق",
    });
  });

  // Custom Modal Logic
  function showReceiptModal(studentId, studentName, currency, amount) {
    // Fill modal fields
    $("#modal-student-id").val(studentId);
    $("#modal-student-name").text(studentName);
    $("#modal-student-name-hidden").val(studentName);
    $("#modal-currency").text(currency);
    $("#modal-currency-hidden").val(currency);
    $("#modal-amount").val(amount);
    $("#modal-payment-method").val("");
    $("#modal-bank-row").hide();
    $("#modal-bank-name").val("");
    $("#modal-notes").val("");
    $("#modal-file").val("");
    $("#file-preview-area").empty();
    // Clear custom month select
    $(".custom-month-option").removeClass("selected");
    // Set default to current month
    var monthNames = [
      "يناير",
      "فبراير",
      "مارس",
      "ابريل",
      "مايو",
      "يونيو",
      "يوليو",
      "اغسطس",
      "سبتمبر",
      "اكتوبر",
      "نوفمبر",
      "ديسمبر",
    ];
    var now = new Date();
    var currentMonth = monthNames[now.getMonth()];
    $(".custom-month-option").each(function () {
      if ($(this).data("value") === currentMonth) {
        $(this).addClass("selected");
      }
    });
    updateMonthSelectDisplay();
    $("#receipt-upload-modal").fadeIn(150);
  }

  // Custom Month Select Logic
  function updateMonthSelectDisplay() {
    var selected = [];
    $(".custom-month-option.selected").each(function () {
      selected.push($(this).data("value"));
    });
    // Update hidden input
    $("#modal-months-hidden").val(JSON.stringify(selected));
    // Update display
    var $select = $("#custom-month-select");
    if (selected.length === 0) {
      $select.html("اختر الشهر");
    } else {
      $select.html(
        selected
          .map(function (m) {
            return '<span class="selected-month-tag">' + m + "</span>";
          })
          .join(" ")
      );
    }
  }

  // Open/close dropdown
  $(document).on("click", "#custom-month-select", function (e) {
    e.stopPropagation();
    var $dropdown = $("#custom-month-dropdown");
    $(".custom-month-dropdown").not($dropdown).hide();
    $dropdown.toggle();
    $(this).toggleClass("active");
  });
  // Toggle month selection
  $(document).on("click", ".custom-month-option", function (e) {
    e.stopPropagation();
    $(this).toggleClass("selected");
    updateMonthSelectDisplay();
  });
  // Close dropdown on outside click
  $(document).on("click", function () {
    $(".custom-month-dropdown").hide();
    $("#custom-month-select").removeClass("active");
  });

  // File preview logic
  $("#modal-file").on("change", function () {
    var file = this.files[0];
    var $preview = $("#file-preview-area");
    $preview.empty();
    if (!file) return;
    var type = file.type;
    if (type.startsWith("image/")) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $preview.html(
          '<img src="' + e.target.result + '" alt="صورة الإيصال" />'
        );
      };
      reader.readAsDataURL(file);
    } else if (type === "application/pdf") {
      $preview.html(
        '<span class="pdf-icon">📄</span><span class="file-name">' +
          file.name +
          "</span>"
      );
    } else {
      $preview.html('<span class="file-name">' + file.name + "</span>");
    }
  });

  // Open modal on upload button click
  $(document).on("click", ".upload-receipt-btn", function () {
    const studentId = $(this).data("student-id");
    const studentName = $(this).data("student-name");
    const currency = $(this).data("currency");
    const amount = $(this).data("amount");
    showReceiptModal(studentId, studentName, currency, amount);
  });

  // Show/hide bank select
  $("#modal-payment-method").on("change", function () {
    if ($(this).val() === "bank") {
      $("#modal-bank-row").show();
    } else {
      $("#modal-bank-row").hide();
      $("#modal-bank-name").val("");
    }
  });

  // Hide modal on cancel
  $(document).on(
    "click",
    ".receipt-modal-cancel, .receipt-modal-overlay",
    function () {
      $("#receipt-upload-modal").fadeOut(120);
    }
  );

  // Handle form submit
  $("#receipt-upload-form").on("submit", function (e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    formData.append("action", "upload_payment_receipt");
    // Use hidden months input
    var months = $("#modal-months-hidden").val() || "[]";
    formData.set("months", months);
    // Simple validation
    if (!$("#modal-file").val()) {
      alert("يرجى رفع ملف الإيصال");
      return;
    }
    if (JSON.parse(months).length === 0) {
      alert("يرجى اختيار شهر واحد على الأقل");
      return;
    }
    $(".receipt-modal-submit").prop("disabled", true).text("...جاري الرفع");
    $.ajax({
      url: zuwadPlugin.ajaxurl,
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        $(".receipt-modal-submit").prop("disabled", false).text("رفع");
        if (response.success) {
          $("#receipt-upload-modal").fadeOut(120);
          
          // Get the student ID and remove the row from the table
          const studentId = $("#modal-student-id").val();
          const $studentRow = $(".upload-receipt-btn[data-student-id='" + studentId + "']").closest("tr");
          
          Swal.fire({
            icon: "success",
            title: "تم رفع إيصال الدفع بنجاح",
            text: "تم تغيير حالة الدفع إلى نشط تلقائياً",
            showConfirmButton: false,
            timer: 2500,
            didClose: function() {
              // Remove the row with animation after the alert is closed
              $studentRow.fadeOut(400, function() {
                $(this).remove();
                // Update statistics and pagination after removing the row
                filterTable();
                updateStatistics();
                
                // Refresh the payment receipts table if it exists on the page
                if (typeof loadPaymentReceipts === 'function') {
                  // If we're on the same page with the receipts table
                  loadPaymentReceipts();
                } else if (window.parent && window.parent.jQuery) {
                  // If receipts table is in another frame/tab, try to refresh it there
                  try {
                    const $receiptsContainer = window.parent.jQuery('.zuwad-receipts-table-container');
                    if ($receiptsContainer.length > 0 && window.parent.loadPaymentReceipts) {
                      window.parent.loadPaymentReceipts();
                    }
                  } catch (e) {
                    console.log('Could not refresh receipts table in parent window');
                  }
                }
              });
            }
          });
        } else {
          alert(
            response.data && response.data.message
              ? response.data.message
              : "حدث خطأ أثناء رفع الإيصال"
          );
        }
      },
      error: function () {
        $(".receipt-modal-submit").prop("disabled", false).text("رفع");
        alert("فشل الاتصال بالخادم");
      },
    });
  });
});
