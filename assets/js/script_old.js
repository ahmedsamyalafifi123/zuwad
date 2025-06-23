jQuery(document).ready(function ($) {
    let role = "";
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
              <input type="password" id="password" name="password"><br>
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
  
    function renderSupervisorForm() {
      return `
              <div class="form-container">
                      <span class="close-modal" style="position: absolute; top: 10px; right: 10px; cursor: pointer; font-size: 20px;">&times;</span>
                  <div class="form-row">
                      <div class="form-column">
                          ${generateCommonFields()}
                          <label for="manual_id">معرف المشرف (اختياري):</label>
                          <input type="text" id="manual_id" name="manual_id"><br>
                      </div>
                  </div>
                  <button id="save_supervisor" class="button">حفظ المشرف</button>
              </div>
          `;
    }
  
    function renderTeacherForm() {
      return `
          <div class="form-container">
              <span class="close-modal" style="position: absolute; top: 10px; right: 10px; cursor: pointer; font-size: 20px;">&times;</span>
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
              <button id="save_teacher" class="button">حفظ المعلم</button>
          </div>
      `;
    }
  
    function renderStudentForm() {
      return `
          <div class="form-container">
              <span class="close-modal" style="position: absolute; top: 10px; right: 10px; cursor: pointer; font-size: 20px;">&times;</span>
              <div class="form-row">
                  <div class="form-column">
                      ${generateCommonFields()}
                  </div>
                  <div class="form-column">
                      <label for="teacher">اسم المعلم:</label>
                      <select id="teacher" name="teacher">
                          <option value="" selected disabled>اختر معلم</option>
                      </select><br>
  
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
      <option value="نشط" style="color: green;">نشط</option>
      <option value="في انتظار الدفع" style="color: orange;">في انتظار الدفع</option>
      <option value="معلق" style="color: red;">معلق</option>
      <option value="متوقف" style="color: gray;">متوقف</option>
  </select><br>
                      <label for="notes">ملاحظات:</label>
                      <textarea id="notes" name="notes"></textarea><br>
                  </div>
              </div>
              <button id="save_student" class="button">حفظ الطالب</button>
          </div>
      `;
    }
  
    function fetchSupervisors() {
      $.ajax({
        url: zuwadPlugin.ajaxurl,
        type: "POST",
        data: { action: "get_supervisors" },
        success: function (response) {
          if (response.success) {
            $("#supervisor").html(
              response.data
                .map((s) => `<option value="${s.id}">${s.name}</option>`)
                .join("")
            );
          } else {
            alert("فشل في تحميل المشرفين");
          }
        },
        error: function () {
          alert("خطأ في الاتصال بالخادم");
        },
      });
    }
  
    $("body").append(
      '<div id="modal_overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 999;"></div>'
    );
  
    $("button[data-role]").on("click", function () {
      role = $(this).data("role");
      $("#user_modal").html("");
      if (role === "supervisor") {
        appendModalContent(renderSupervisorForm());
      } else if (role === "teacher") {
        appendModalContent(renderTeacherForm());
        fetchSupervisors();
      } else if (role === "student") {
        appendModalContent(renderStudentForm());
        fetchTeachers();
      } else {
        appendModalContent(`
                  <div class="form-container">
                      <span class="close-modal" style="position: absolute; top: 10px; right: 10px; cursor: pointer; font-size: 20px;">&times;</span>
                      <div class="form-row">
                          <div class="form-column">
                              ${generateCommonFields()}
                          </div>
                      </div>
                      <button id="save_user" class="button">حفظ المستخدم</button>
                  </div>
              `);
      }
    });
  
    $(document).on(
      "click",
      "#save_supervisor, #save_teacher, #save_student, #save_user",
      function (e) {
        e.preventDefault();
        if (
          (role === "teacher" && !$("#supervisor").val()) ||
          (role === "student" && !$("#teacher").val())
        ) {
          alert(`الرجاء اختيار ${role === "teacher" ? "مشرف" : "معلم"}.`);
          return;
        }
  
        let formData = new FormData();
        formData.append("action", "create_user");
        formData.append("role", role);
        formData.append(
          "first_name",
          $("#display_name").val() || $("#first_name").val()
        );
        formData.append("email", $("#email").val());
        formData.append("phone", $("#phone").val()); // Send only the phone number
        // formData.append('country_code', $('#country_code').val()); // Send country code separately
        formData.append("dob", $("#dob").val());
        formData.append("password", $("#password").val());
        formData.append("age", $("#age").val());
        formData.append("country", $("#country").val());
        if (role === "supervisor")
          formData.append("manual_id", $("#manual_id").val());
        if (role === "teacher") {
          formData.append("supervisor", $("#supervisor").val());
          formData.append("lessons_name", $("#lessons_name").val());
          formData.append(
            "teacher_classification",
            $("#teacher_classification").val()
          );
          formData.append("teacher_status", $("#teacher_status").val());
        }
  
        if ($("#teacher").val() === "") {
          alert("الرجاء اختيار معلم.");
          return;
        }
  
        if (role === "student") {
          formData.append("teacher", $("#teacher").val());
          formData.append("lessons_name", $("#lessons_name").val());
  
          formData.append("lessons_number", $("#lessons_number").val());
          formData.append("lesson_duration", $("#lesson_duration").val());
          formData.append("currency", $("#currency").val()); // Add currency
          formData.append("amount", $("#amount").val()); // Add amount
          formData.append("notes", $("#notes").val());
          formData.append("payment_status", $("#payment_status").val());
        }
        let file = $("#photo")[0].files[0];
        if (file) formData.append("photo", file);
  
        $.ajax({
          url: zuwadPlugin.ajaxurl,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: function (response) {
            if (response.success) {
              alert(`تم إنشاء المستخدم بنجاح. م (ID): ${response.data.m_id}`);
              hideModal();
            } else {
              alert(`حدث خطأ: ${response.data.message}`);
            }
          },
          error: function () {
            alert("حدث خطأ في الاتصال بالخادم.");
          },
        });
      }
    );
  
    // Close modal when clicking outside of it
    $("#modal_overlay").on("click", function (e) {
      if (e.target === this) {
        // Ensure the click is on the overlay itself, not its children
        hideModal();
      }
    });
  
    // Close modal when clicking the "X" button
    $(document).on("click", ".close-modal", function (e) {
      e.stopPropagation(); // Prevent event bubbling to the overlay
      hideModal();
    });
  });
  