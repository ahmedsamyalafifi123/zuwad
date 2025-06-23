jQuery(document).ready(function ($) {

  // Set shortcode month and year selectors to current month and year on page load
  var currentMonth = new Date().getMonth() + 1; // JavaScript months are 0-indexed
  var currentYear = new Date().getFullYear();
  $("#shortcode-month-select").val(currentMonth);
  $("#shortcode-year-select").val(currentYear);

  function createMultiSelect(id, options, selectedValues = []) {
    let container = $('<div class="multi-select-container"></div>');
    let hiddenInput = $(
      `<input type="hidden" id="${id}" name="${id}" value="${selectedValues.join(
        ","
      )}">`
    );
    container.append(hiddenInput);

    options.forEach((option) => {
      let value = option.value || option;
      let label = option.label || option;
      let isSelected = selectedValues.includes(value);

      let checkbox = $(`
                  <div class="multi-select-option">
                      <label>
                          <input type="checkbox" value="${value}" ${
        isSelected ? "checked" : ""
      }>
                          <span class="option-label">${label}</span>
                          <span class="checkmark">${
                            isSelected ? "✔️" : ""
                          }</span>
                      </label>
                  </div>
              `);

      checkbox.find("input").on("change", function () {
        // When a checkbox is changed, update the hidden input value
        let values = [];
        container.find('input[type="checkbox"]:checked').each(function () {
          values.push($(this).val());
        });
        hiddenInput.val(values.join(","));

        // Update the checkmark display
        if ($(this).is(":checked")) {
          $(this).closest("label").find(".checkmark").text("✔️");
        } else {
          $(this).closest("label").find(".checkmark").text("");
        }
      });

      container.append(checkbox);
    });

    return container;
  }

  function updateMultiSelectValues(id) {
    let values = [];
    $(`#${id}`)
      .closest(".multi-select-container")
      .find('input[type="checkbox"]:checked')
      .each(function () {
        values.push($(this).val());
      });
    $(`#${id}`).val(values.join(","));
  }

  // Open modal when a teacher card is clicked
  $(".teacher-card").on("click", function () {
    var teacherId = $(this).data("teacher-id");
    var teacherName = $(this).find("h3").text();

    // Reset modal data before opening
    resetModalData();

    // Set modal header content
    $("#modal-teacher-name").text(teacherName);

    // Show the modal
    $("#teacher-modal").show();

    // Fetch initial data for the current month
    fetchTeacherData(teacherId);

    // Automatically fetch data when month or year changes
    $("#month-select, #year-select")
      .off("change")
      .on("change", function () {
        fetchTeacherData(teacherId);
      });
  });

  // Handle month/year change for the shortcode
  $("#shortcode-month-select, #shortcode-year-select").on(
    "change",
    function () {
      // console.log('Month/Year selector changed in shortcode'); // Log for debugging
      var teacherId = $(".teacher-dashboard").data("teacher-id"); // Get the teacher ID from the data attribute
      // console.log('Teacher ID:', teacherId); // Log for debugging
      fetchTeacherData(
        teacherId,
        "#shortcode-month-select",
        "#shortcode-year-select",
        true
      ); // Pass `true` to skip table update
    }
  );

  // Function to fetch teacher data based on selected month and year
  function fetchTeacherData(
    teacherId,
    monthSelector = "#month-select",
    yearSelector = "#year-select",
    skipTableUpdate = false
  ) {
    var selectedMonth = $(monthSelector).val();
    var selectedYear = $(yearSelector).val();

    // console.log('Fetching data for Teacher ID:', teacherId, 'Month:', selectedMonth, 'Year:', selectedYear); // Log for debugging

    $.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: {
        action: "get_teacher_students",
        teacher_id: teacherId,
        month: selectedMonth,
        year: selectedYear,
      },
      success: function (response) {
        // console.log('AJAX response:', response); // Log for debugging
        if (response.success) {
          // Update widgets with all-time and current month data
          $("#widget-all-time-lessons").text(
            response.data.all_time_lesson_count
          );
          $("#widget-all-time-hours").text(response.data.all_time_total_hours);
          $("#widget-current-month-lessons").text(
            response.data.current_month_lesson_count
          );
          $("#widget-current-month-hours").text(
            response.data.current_month_total_hours
          );

          // Update عدد الطلاب widget
          $("#widget-students").html(`
                        ${response.data.number_of_students_calculated}
                        <small style="font-size: 12px;">(${response.data.real_student_count} مشترك)</small>
                    `);

          // Update new widgets
          $("#widget-student-delays").text(response.data.student_delays);
          $("#widget-teacher-delays").text(response.data.teacher_delays);
          $("#widget-compensation-delays").text(
            response.data.compensation_absences
          );
          $("#widget-compensation-absences").text(
            response.data.compensation_delays
          );

          // Skip table update if `skipTableUpdate` is true
          if (!skipTableUpdate) {
            var students = response.data.students;

            // Sort students by m_id
            students.sort(function (a, b) {
              // sort by m_id
              // return a.m_id - b.m_id;
              //sort by name
              return a.name.localeCompare(b.name);
            });

            var tbody = $("#students-table tbody");
            tbody.empty(); // Clear existing rows

            // Populate the table with student data
            students.forEach(function (student) {
              var row = `
                                <tr>
                                    <td>${student.m_id}</td>
                                    <td>${student.name}</td>
                                    <td>${student.phone}</td>
                                  <td>${student.lessons_name}</td>

                                    <td>${student.age}</td>
                                    <td>${student.country}</td>
                                    <td>${student.lessons_number}</td>
                                    <td>${student.lesson_duration}</td>
                                    <td>${student.currency}</td>
                                    <td>${student.amount}</td>
                                    <td><span style="background-color: ${getPaymentStatusColor(
                                      student.payment_status
                                    )}; padding: 2px 6px; border-radius: 4px; display: inline-block; color: white; font-size: 0.9em;">${
                student.payment_status
              }</span></td>
                                    <td>${student.notes}</td>
                                    <td>
                                        <button class="edit-student" data-student-id="${
                                          student.m_id
                                        }" title="تعديل">✏️</button>
                                        <button class="delete-student" data-student-id="${
                                          student.m_id
                                        }" title="حذف">🗑️</button>
                                    </td>
                                </tr>
                            `;
              tbody.append(row);
            });
          }
        } else {
          alert("حدث خطأ أثناء جلب بيانات الطلاب.");
        }
      },
      error: function () {
        alert("حدث خطأ في الاتصال بالخادم.");
      },
    });
  }

  // Close modal when the close button is clicked
  $(".close-modal").on("click", function () {
    resetModalData();
    $("#teacher-modal").hide();
  });

  // Close modal when clicking outside of it
  $(window).on("click", function (e) {
    if ($(e.target).is("#teacher-modal")) {
      resetModalData();
      $("#teacher-modal").hide();
    }
  });

  // Function to reset modal data
  function resetModalData() {
    // Reset month and year selectors to current month and year
    var currentMonth = new Date().getMonth() + 1; // JavaScript months are 0-indexed
    var currentYear = new Date().getFullYear();
    
    $("#month-select").val(currentMonth);
    $("#year-select").val(currentYear);

    // Clear widgets data
    $("#widget-all-time-lessons").text("-");
    $("#widget-all-time-hours").text("-");
    $("#widget-current-month-lessons").text("-");
    $("#widget-current-month-hours").text("-");
    $("#widget-students").html("-");
    $("#widget-student-delays").text("-");
    $("#widget-teacher-delays").text("-");
    $("#widget-compensation-delays").text("-");
    $("#widget-compensation-absences").text("-");

    // Clear students table
    $("#students-table tbody").empty();
  }

  // Function to get payment status color
  function getPaymentStatusColor(paymentStatus) {
    const paymentStatusColors = {
      نشط: "rgba(5, 176, 72, 0.82)", // Light green
      "في انتظار الدفع": "rgba(223, 144, 0, 0.72)", // Light orange
      معلق: "rgba(255, 0, 0, 0.6)", // Light red
      متوقف: "rgba(128, 128, 128, 0.5)", // Light gray
    };

    return paymentStatusColors[paymentStatus] || "transparent";
  }

  $(document).on("click", ".delete-student", function () {
    var studentId = $(this).data("student-id"); // Get the student ID
    var studentRow = $(this).closest("tr"); // Get the row of the student to be deleted

    // Show a password confirmation prompt using SweetAlert2
    Swal.fire({
      title: "تأكيد الحذف",
      text: "يرجى إدخال كلمة المرور لتأكيد الحذف:",
      input: "password",
      inputAttributes: {
        autocapitalize: "off",
      },
      showCancelButton: true,
      confirmButtonText: "حذف",
      cancelButtonText: "إلغاء",
      preConfirm: (password) => {
        if (password !== "55555") {
          Swal.showValidationMessage("كلمة المرور غير صحيحة");
        }
        return password;
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Send an AJAX request to delete the student
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          type: "POST",
          data: {
            action: "delete_student",
            student_id: studentId,
            password: result.value, // Send the password
            _ajax_nonce: zuwadPlugin.nonce, // Send the nonce
          },
          success: function (response) {
            if (response.success) {
              // Remove the student row from the table
              studentRow.remove();
              Swal.fire("تم الحذف", "تم حذف الطالب بنجاح.", "success");
            } else {
              Swal.fire(
                "خطأ",
                response.data.message || "حدث خطأ أثناء حذف الطالب.",
                "error"
              );
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error); // Debugging
            Swal.fire("خطأ", "حدث خطأ في الاتصال بالخادم.", "error");
          },
        });
      }
    });
  });

  $(document).on("click", ".edit-student", function () {
    var studentId = $(this).data("student-id"); // This should already be m_id (e.g., "011501")
    // console.log('Student ID:', studentId); // Debugging

    // Fetch student data via AJAX
    $.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: {
        action: "get_student_data",
        student_id: studentId, // Send m_id as a string
      },
      success: function (response) {
        // console.log('AJAX Response:', response); // Debugging
        if (response.success) {
          var student = response.data.student;

          // Render the form and show the modal
          $("#student-modal").html(renderStudentForm());
          $("#student-modal").show(); // Show the modal

          // Populate the form with student data
          $("#student_id").val(studentId); // Set the student ID (m_id)
          $("#display_name").val(student.display_name);
          $("#email").val(student.email);
          $("#phone").val(student.phone);
          $("#dob").val(student.dob);
          $("#age").val(student.age);
          $("#country").val(student.country);
          $("#lessons_name").val(student.lessons_name);
          $("#lessons_number").val(student.lessons_number);
          $("#lesson_duration").val(student.lesson_duration);
          $("#currency").val(student.currency);
          $("#previous_lesson").val(student.previous_lesson);

          $("#amount").val(student.amount);
          $("#notes").val(student.notes);
          $("#payment_status").val(student.payment_status);

          // Fetch teachers and populate the dropdown
          fetchTeachers(function () {
            // Set the selected teacher
            $("#teacher").val(student.teacher_id);
          });

          // Add a change event listener to the previous_lesson field
          $("#previous_lesson").on("change", function () {
            var newPreviousLesson = $(this).val();

            // Call the function to update session numbers
            $.ajax({
              url: zuwadPlugin.ajaxurl,
              type: "POST",
              data: {
                action: "update_previous_lesson_and_session_numbers",
                student_id: studentId,
                previous_lesson: newPreviousLesson,
              },
              success: function (response) {
                // console.log('Session numbers updated:', response);
                if (response.success) {
                  alert("Session numbers updated successfully.");
                } else {
                  alert("Failed to update session numbers.");
                }
              },
              error: function (xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert("Failed to update session numbers.");
              },
            });
          });
        } else {
          alert("حدث خطأ أثناء جلب بيانات الطالب.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", status, error); // Debugging
        alert("حدث خطأ في الاتصال بالخادم.");
      },
    });
  });

  const countryCodes = {
    مصر: "+20",
    السعودية: "+966",
    الجزائر: "+213",
    البحرين: "+973",
    "جزر القمر": "+269",
    جيبوتي: "+253",
    العراق: "+964",
    الأردن: "+962",
    الكويت: "+965",
    لبنان: "+961",
    ليبيا: "+218",
    موريتانيا: "+222",
    المغرب: "+212",
    عمان: "+968",
    فلسطين: "+970",
    قطر: "+974",
    الصومال: "+252",
    السودان: "+249",
    سوريا: "+963",
    تونس: "+216",
    الإمارات: "+971",
    اليمن: "+967",

    أفغانستان: "+93",
    ألبانيا: "+355",
    أرمينيا: "+374",
    أستراليا: "+61",
    النمسا: "+43",
    أذربيجان: "+994",
    بربادوس: "+1-246",
    بنغلاديش: "+880",
    بيلاروسيا: "+375",
    بلجيكا: "+32",
    بليز: "+501",
    بنين: "+229",
    بوتان: "+975",
    بوليفيا: "+591",
    "البوسنة والهرسك": "+387",
    بوتسوانا: "+267",
    البرازيل: "+55",
    بروناي: "+673",
    بلغاريا: "+359",
    "بوركينا فاسو": "+226",
    بوروندي: "+257",
    كمبوديا: "+855",
    الكاميرون: "+237",
    كندا: "+1",
    "كيب فيردي": "+238",
    "جمهورية أفريقيا الوسطى": "+236",
    تشاد: "+235",
    شيلي: "+56",
    الصين: "+86",
    كولومبيا: "+57",
    "جزر القمر": "+269",
    الكونغو: "+242",
    "كونغو (جمهورية الكونغو الديمقراطية)": "+243",
    كوستاريكا: "+506",
    كرواتيا: "+385",
    كوبا: "+53",
    قبرص: "+357",
    "جمهورية التشيك": "+420",
    الدنمارك: "+45",
    جيبوتي: "+253",
    دومينيكا: "+1-767",
    "جمهورية الدومينيكان": "+1-809",
    "تيمور الشرقية": "+670",
    الإكوادور: "+593",
    مصر: "+20",
    السعودية: "+966",
    إلسلفادور: "+503",
    "غينيا الاستوائية": "+240",
    إريتريا: "+291",
    إستونيا: "+372",
    إثيوبيا: "+251",
    فيجي: "+679",
    فنلندا: "+358",
    فرنسا: "+33",
    غابون: "+241",
    غامبيا: "+220",
    جورجيا: "+995",
    ألمانيا: "+49",
    غانا: "+233",
    غرينادا: "+1-473",
    غواتيمالا: "+502",
    غينيا: "+224",
    "غينيا بيساو": "+245",
    غواديلوب: "+590",
    جوادلوب: "+590",
    "غينيا الاستوائية": "+240",
    هايتي: "+509",
    هندوراس: "+504",
    "هونغ كونغ": "+852",
    هنغاريا: "+36",
    أيسلندا: "+354",
    إندونيسيا: "+62",
    الهند: "+91",
    إيران: "+98",
    العراق: "+964",
    إيرلندا: "+353",
    إيطاليا: "+39",
    جامايكا: "+1-876",
    اليابان: "+81",
    الأردن: "+962",
    كازاخستان: "+7",
    كينيا: "+254",
    كيريباتي: "+686",
    "كوريا الجنوبية": "+82",
    "كوت ديفوار": "+225",
    كوسوفو: "+383",
    كينيا: "+254",
    كيوبيك: "+1-418",
    قيرغيزستان: "+996",
    لاوس: "+856",
    لاتفيا: "+371",
    لبنان: "+961",
    ليسوتو: "+266",
    ليبيريا: "+231",
    ليبيا: "+218",
    لكسمبورغ: "+352",
    مقدونيا: "+389",
    مدغشقر: "+261",
    ملاوي: "+265",
    ماليزيا: "+60",
    مالطا: "+356",
    مارشال: "+692",
    موريتانيا: "+222",
    موريشيوس: "+230",
    المكسيك: "+52",
    ميكرونيزيا: "+691",
    مولدوفا: "+373",
    منغوليا: "+976",
    مونتسيرات: "+1-664",
    موزمبيق: "+258",
    ميانمار: "+95",
    ناميبيا: "+264",
    ناورو: "+674",
    نيبال: "+977",
    هولندا: "+31",
    نيوزيلندا: "+64",
    نيكاراغوا: "+505",
    النيجر: "+227",
    نيجيريا: "+234",
    النرويج: "+47",
    أوكرانيا: "+380",
    أوغندا: "+256",
    أوروغواي: "+598",
    فانواتو: "+678",
    فنزويلا: "+58",
    فيتنام: "+84",
    "واليس وفوتونا": "+681",
    اليمن: "+967",
  };

  const arabCountries = Object.keys(countryCodes);

  function showModal() {
    $("#user_modal, #modal_overlay").show();
  }

  function hideModal() {
    $("#user_modal, #modal_overlay").hide();
  }

  function appendModalContent(content) {
    $("#user_modal").html(content);
    showModal();
  }

  function generatePhoneInput() {
    return `
            <div style="display: flex; gap: 10px;">
                <input type="text" id="phone" name="phone" style="flex: 1;" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
            </div><br>
        `;
  }

  function generateCommonFields() {
    return `
            <label for="display_name">الاسم:</label>
            <input type="text" id="display_name" name="display_name"><br>
            <label for="email">البريد الإلكتروني:</label>
            <input type="email" id="email" name="email"><br>
<label for="password">كلمة السر:</label>
<input type="password" id="password" name="password" placeholder="اتركه فارغًا إذا كنت لا تريد التغيير"><br>
            <label for="phone">رقم الهاتف مع كود الدولة :</label>
            ${generatePhoneInput()}
            <label for="dob">تاريخ الميلاد:</label>
            <input type="date" id="dob" name="dob"><br>
            <label for="age">السن:</label>
            <input type="number" id="age" name="age"><br>
            <label for="country">الدولة:</label>
            <select id="country" name="country">
                ${arabCountries
                  .map(
                    (country) =>
                      `<option value="${country}">${country}</option>`
                  )
                  .join("")}
            </select><br>
            <label for="photo">الصورة:</label>
            <input type="file" id="photo" name="photo"><br>
        `;
  }

  function renderStudentForm() {
    return `
            <div class="form-container">
                <span class="close-modal" style="position: absolute; top: 10px; right: 10px; cursor: pointer; font-size: 20px;">&times;</span>
                <div class="form-row">
                    <div class="form-column">
                        ${generateCommonFields()}

                        <label for="previous_lesson">الحصص السابقة:</label>
                        <input type="previous_lesson" id="previous_lesson" name="previous_lesson"><br>

                    </div>

                    <div class="form-column">
                        <input type="hidden" id="student_id" name="student_id"> <!-- Hidden field for student ID -->


                        <label for="teacher">اسم المعلم:</label>
                        <select id="teacher" name="teacher"></select><br>

                        <label for="lessons_name">اسم المادة :</label>
                        <select id="lessons_name" name="lessons_name">
                            <option value="قرآن">قرآن</option>
                            <option value="لغة عربية">لغة عربية</option>
                            <option value="تجويد">تجويد</option>
                            <option value="تربية اسلامية">تربية اسلامية</option>
                            <option value="قرآن لغير الناطقين">قرآن لغير الناطقين</option>
                            <option value="لغة عربية لغير الناطقين">لغة عربية لغير الناطقين</option>
                        </select><br>

                        <label for="lessons_number">عدد الحصص:</label>
                        <select id="lessons_number" name="lessons_number">
                            <option value="4">4</option>
                            <option value="8">8</option>
                            <option value="12">12</option>
                            <option value="16">16</option>
                            <option value="20">20</option>
                            <option value="24">24</option>
                        </select><br>

                        <label for="lesson_duration">مدة الحصة:</label>
                        <select id="lesson_duration" name="lesson_duration">
                            <option value="30">30</option>
                            <option value="45">45</option>
                            <option value="60">60</option>
                        </select><br>
                        <label for="currency">العملة:</label>
                        <select id="currency" name="currency">
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="AED">درهم إماراتي (AED)</option>
                            <option value="EGP">جنيه مصري (EGP)</option>
                            <option value="QAR">ريال قطري (QAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="OMR">ريال عماني (OMR)</option>
                            <!-- Add more currencies as needed -->
                        </select><br>

                        <label for="amount">المبلغ:</label>
                        <input type="number" id="amount" name="amount"><br>

                        <label for="payment_status">حالة الدفع:</label>
                        <select id="payment_status" name="payment_status">
                            <option value="نشط" style="background-color: rgba(0, 223, 87, 0.61);">نشط</option>
                            <option value="في انتظار الدفع" style="background-color: rgba(255, 165, 0, 0.5);">في انتظار الدفع</option>
                            <option value="معلق" style="background-color: rgba(255, 0, 0, 0.5);">معلق</option>
                            <option value="متوقف" style="background-color: rgba(128, 128, 128, 0.5);">متوقف</option>
                        </select><br>
                        <label for="notes">ملاحظات:</label>
                        <textarea id="notes" name="notes"></textarea><br>
                    </div>
                </div>
                <button id="update_student" class="button">تحديث الطالب</button>
            </div>
        `;
  }

  $(document).on("click", ".close-modal", function () {
    $("#student-modal").hide(); // Hide the modal
  });

  // Close modal when clicking outside of it
  $(window).on("click", function (e) {
    if ($(e.target).is("#student-modal")) {
      $("#student-modal").hide();
    }
  });

  $(document).on("click", "#update_student", function (e) {
    e.preventDefault();

    // Get the student ID from the hidden field
    var studentId = $("#student_id").val(); // This should be m_id
    // console.log('Student ID:', studentId); // Debugging

    // Get the selected teacher ID
    var teacherId = $("#teacher").val();
    // console.log('Teacher ID:', teacherId); // Debugging

    // Prepare form data
    let formData = new FormData();
    formData.append("action", "update_student");
    formData.append("student_id", studentId); // This is m_id
    formData.append("teacher", teacherId); // Add teacher ID
    formData.append("display_name", $("#display_name").val());
    formData.append("email", $("#email").val());
    formData.append("phone", $("#phone").val());
    formData.append("country_code", $("#country_code").val());
    formData.append("dob", $("#dob").val());
    formData.append("age", $("#age").val());
    formData.append("country", $("#country").val());
    formData.append("lessons_name", $("#lessons_name").val());
    formData.append("lessons_number", $("#lessons_number").val());
    formData.append("lesson_duration", $("#lesson_duration").val());
    formData.append("currency", $("#currency").val());
    formData.append("previous_lesson", $("#previous_lesson").val());

    formData.append("amount", $("#amount").val());
    formData.append("notes", $("#notes").val());
    formData.append("payment_status", $("#payment_status").val());
    formData.append("password", $("#password").val()); // Add password

    // Debugging: Log the form data
    for (let [key, value] of formData.entries()) {
      // console.log(key, value);
    }

    // Send AJAX request
    $.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        // console.log('AJAX Response:', response); // Debugging
        if (response.success) {
          alert("تم تحديث بيانات الطالب بنجاح.");
          $("#student-modal").hide();
          // Update the m_id if it has changed
          if (response.data.new_m_id) {
            $("#student_id").val(response.data.new_m_id);
            // Optionally, update the displayed m_id in the table or elsewhere
          }
          // Optionally, refresh the table or update the row
        } else {
          alert("حدث خطأ أثناء تحديث بيانات الطالب.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", status, error); // Debugging
        alert("حدث خطأ في الاتصال بالخادم.");
      },
    });
  });

  // Handle edit teacher click
  $(document).on("click", ".edit-teacher", function (e) {
    e.preventDefault();
    e.stopPropagation();
    const teacherId = $(this).closest(".teacher-card").data("teacher-id");

    // Hide the teacher modal
    $("#teacher-modal").hide();

    // Fetch teacher data
    $.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: {
        action: "get_teacher_data",
        teacher_id: teacherId,
      },
      success: function (response) {
        if (response.success) {
          const teacher = response.data.teacher;
          const formContent = renderTeacherEditForm(teacher);

          // Show the edit form in user_modal
          $("#user_modal").html(formContent);
          $("#user_modal, #modal_overlay").show();

          // Populate the form with teacher data
          populateTeacherForm(teacher);

          // Add close handler for this modal
          $("#user_modal .close-modal").on("click", function () {
            $("#user_modal, #modal_overlay").hide();
          });
          // Fetch and populate supervisors dropdown
          $.ajax({
            url: zuwadPlugin.ajaxurl,
            type: "POST",
            data: {
              action: "get_supervisors_list",
              nonce: zuwadPlugin.nonce,
            },
            success: function (response) {
              if (response.success) {
                const supervisorSelect = $("#supervisor");
                supervisorSelect.empty();
                response.data.forEach(function (supervisor) {
                  supervisorSelect.append(
                    `<option value="${supervisor.ID}">${supervisor.display_name}</option>`
                  );
                });
                supervisorSelect.val(teacher.supervisor_id);
              }
            },
          });
        }
      },
    });
  });

  function renderTeacherEditForm(teacher) {
    return `
      <div class="form-container">
          <span class="close-modal">&times;</span>
          <h3 style="text-align: center;">✏️ تعديل بيانات المعلم</h3>
          <div class="form-row">
              <div class="form-column">
                  ${generateCommonFields()}
              </div>
              <div class="form-column">
                  <label for="supervisor">اسم المشرف:</label>
                  <select id="supervisor" name="supervisor"></select><br>
                  
                  <label for="teacher_classification">التصنيف الحالي:</label>
                  <div id="teacher_classification_container"></div><br>

                  <label for="teacher_status">الحالة الحالية:</label>
                  <select id="teacher_status" name="teacher_status">
                      <option value="نشط عدد كامل">نشط عدد كامل 🟢</option>
                      <option value="نشط نصف عدد">نشط نصف عدد 🟢</option>
                      <option value="وقف الطلاب الجدد">وقف الطلاب الجدد 🟡</option>
                      <option value="اجازة اسبوعين">اجازة اسبوعين 🟡</option>
                      <option value="اعتزار مؤقت">اعتزار مؤقت 🔴</option>
                      <option value="في انتظار النقل">في انتظار النقل ⚫</option>
                      <option value="متوقف عن العمل">متوقف عن العمل ⚫</option>
                  </select><br>
                  
                  <label for="lessons_name">اسم المادة:</label>
                  <div id="lessons_name_container"></div><br>
              </div>
          </div>
          <button id="update_teacher" class="button" data-teacher-id="${
            teacher.ID
          }">تحديث المعلم</button>
      </div>
  `;
  }

  // Update populate function to set values for new fields
  function populateTeacherForm(teacher) {
    $("#display_name").val(teacher.display_name);
    $("#email").val(teacher.email);
    $("#phone").val(teacher.phone);
    $("#dob").val(teacher.dob);
    $("#age").val(teacher.age);
    $("#country").val(teacher.country);
    $("#supervisor").val(teacher.supervisor_id);
    $("#teacher_status").val(teacher.teacher_status || "نشط عدد كامل");

    // Initialize the multi-selects after the form is populated
    const classificationOptions = [
      "فئة اولى (غير الناطقين)",
      "فئة ثانية (خليج)",
      "فئة ثالثة (مصر أطفال)",
      "فئة رابعة (مصر كبار)",
      "فئة خامسة (موبايل)",
    ];

    const lessonOptions = [
      "قرآن",
      "لغة عربية",
      "تجويد",
      "تربية اسلامية",
      "قرآن لغير الناطقين",
      "لغة عربية لغير الناطقين",
    ];

    // Parse existing values from comma-separated strings
    const selectedClassifications = teacher.teacher_classification
      ? teacher.teacher_classification.split(",").map((item) => item.trim())
      : [];
    const selectedLessons = teacher.lessons_name
      ? teacher.lessons_name.split(",").map((item) => item.trim())
      : [];

    console.log("Selected classifications:", selectedClassifications);

    // Create multi-selects with existing values
    $("#teacher_classification_container").html("");
    $("#teacher_classification_container").append(
      createMultiSelect(
        "teacher_classification",
        classificationOptions,
        selectedClassifications
      )
    );

    $("#lessons_name_container").html("");
    $("#lessons_name_container").append(
      createMultiSelect("lessons_name", lessonOptions, selectedLessons)
    );
  }

  function updateTeacherCardStatus(teacherId, status, classification) {
    console.log("Updating teacher card:", teacherId, status, classification);

    const teacherCard = $(`.teacher-card[data-teacher-id="${teacherId}"]`);
    if (!teacherCard.length) {
      console.error("Teacher card not found for ID:", teacherId);
      return;
    }

    // Update status circle color
    let statusColor = "blue"; // Default color

    if (status.includes("نشط عدد كامل")) {
      statusColor = "green";
    }
    if (status.includes("نشط نصف عدد")) {
      statusColor = "whitegreen";
    } else if (
      status.includes("وقف الطلاب الجدد") ||
      status.includes("اجازة اسبوعين")
    ) {
      statusColor = "darkyellow";
    } else if (status.includes("اعتزار مؤقت")) {
      statusColor = "darkred";
    } else if (
      status.includes("في انتظار النقل") ||
      status.includes("متوقف عن العمل")
    ) {
      statusColor = "black";
    }

    console.log("New status color:", statusColor);
    teacherCard
      .find(".status-circle")
      .removeClass("green whitegreen blue darkyellow darkred black")
      .addClass(statusColor);

    // Update classification number
    if (classification) {
      const classificationArray = classification.split(",");
      const classificationMap = {
        "فئة اولى (غير الناطقين)": 1,
        "فئة ثانية (خليج)": 2,
        "فئة ثالثة (مصر أطفال)": 3,
        "فئة رابعة (مصر كبار)": 4,
        "فئة خامسة (موبايل)": 5,
      };

      let lowestNumber = 999;
      classificationArray.forEach((item) => {
        const trimmedItem = item.trim();
        if (
          classificationMap[trimmedItem] &&
          classificationMap[trimmedItem] < lowestNumber
        ) {
          lowestNumber = classificationMap[trimmedItem];
        }
      });

      const classificationNumber = lowestNumber < 999 ? lowestNumber : "-";
      console.log("New classification number:", classificationNumber);
      teacherCard.find(".status-number").text(classificationNumber);
    }
  }

  // Add this after the populateTeacherForm function
  $(document).on("click", "#update_teacher", function (e) {
    e.preventDefault();
    const teacherId = $(this).data("teacher-id");

    // Get values directly from the hidden inputs
    const teacherClassification = $("#teacher_classification").val();
    const teacherStatus = $("#teacher_status").val();
    const lessonsName = $("#lessons_name").val();

    console.log("Classification values before submit:", teacherClassification);

    // Prepare form data
    let formData = new FormData();
    formData.append("action", "update_teacher");
    formData.append("teacher_id", teacherId);
    formData.append("display_name", $("#display_name").val());
    formData.append("email", $("#email").val());
    formData.append("phone", $("#phone").val());
    formData.append("dob", $("#dob").val());
    formData.append("age", $("#age").val());
    formData.append("country", $("#country").val());
    formData.append("supervisor", $("#supervisor").val());
    formData.append("teacher_classification", teacherClassification);
    formData.append("teacher_status", teacherStatus);
    formData.append("lessons_name", lessonsName);
    formData.append("password", $("#password").val());

    // Send AJAX request
    $.ajax({
      url: zuwadPlugin.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          Swal.fire({
            title: "تم التحديث",
            text: "تم تحديث بيانات المعلم بنجاح",
            icon: "success",
            confirmButtonText: "حسناً",
          }).then(() => {
            // Close modal
            $("#user_modal").hide();
            $("#modal_overlay").hide();

            // Update the teacher card with new status and classification
            updateTeacherCardStatus(
              teacherId,
              teacherStatus,
              teacherClassification
            );
          });
        } else {
          Swal.fire({
            title: "خطأ",
            text: response.data || "حدث خطأ أثناء تحديث بيانات المعلم",
            icon: "error",
            confirmButtonText: "حسناً",
          });
        }
      },
      error: function () {
        Swal.fire({
          title: "خطأ",
          text: "حدث خطأ في الاتصال بالخادم",
          icon: "error",
          confirmButtonText: "حسناً",
        });
      },
    });
  });
});

