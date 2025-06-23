/**
 * Zuwad Plugin - Helper Functions
 * 
 * This file contains reusable utility functions used across the plugin.
 * Each function is documented with its purpose and usage.
 */

/**
 * Fetches teacher data from the server and populates a dropdown
 * 
 * @param {Function} callback - Optional callback function to execute after populating the dropdown
 * @returns {void}
 */
function fetchTeachers(callback) {
  jQuery.ajax({
    url: zuwadPlugin.ajaxurl,
    type: "POST",
    data: {
      action: "get_edit_teachers",
    },
    success: function (response) {
      if (response.success) {
        var teachers = response.data.teachers;

        // Sort teachers by Arabic alphabet
        teachers.sort(function (a, b) {
          return a.display_name.localeCompare(b.display_name, "ar");
        });

        var dropdown = jQuery("#teacher");
        dropdown.empty(); // Clear existing options
        dropdown.append(
          '<option value="" selected disabled>اختر معلم</option>'
        ); // Add default option

        // Populate dropdown with sorted teachers
        teachers.forEach(function (teacher) {
          dropdown.append(
            `<option value="${teacher.ID}">${teacher.display_name}</option>`
          );
        });

        // Execute the callback after populating the dropdown
        if (typeof callback === "function") {
          callback();
        }
      } else {
        console.error("Error fetching teacher data:", response);
        alert("حدث خطأ أثناء جلب بيانات المعلمين.");
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      alert("حدث خطأ في الاتصال بالخادم.");
    },
  });
}

/**
 * Format a date to a localized string (Arabic format)
 * 
 * @param {Date|string} date - Date object or date string
 * @returns {string} Formatted date string
 */
function formatDateArabic(date) {
  if (!(date instanceof Date)) {
    date = new Date(date);
  }
  
  return date.toLocaleDateString('ar-SA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

/**
 * Show a loading spinner and optionally disable a button
 * 
 * @param {string} buttonSelector - jQuery selector for the button
 * @param {string} loadingText - Text to show while loading
 * @returns {void}
 */
function showLoading(buttonSelector, loadingText) {
  const $button = jQuery(buttonSelector);
  $button.prop('disabled', true);
  $button.data('original-text', $button.text());
  $button.html(`<span class="spinner"></span> ${loadingText || 'جاري التحميل...'}`);
}

/**
 * Hide the loading spinner and restore button text
 * 
 * @param {string} buttonSelector - jQuery selector for the button
 * @returns {void}
 */
function hideLoading(buttonSelector) {
  const $button = jQuery(buttonSelector);
  $button.prop('disabled', false);
  $button.text($button.data('original-text'));
}
