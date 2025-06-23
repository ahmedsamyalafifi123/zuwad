jQuery(document).ready(function ($) {
  // Variables to track current state
  let currentPage = 1;
  let totalPages = 1;
  let searchTimer;
  let isLoading = false;

  // Log to check if script is loaded
  console.log("Student Dashboard JS loaded");

  // Check if the AJAX URL is available
  if (typeof zuwadDashboard !== "undefined" && zuwadDashboard.ajaxurl) {
    console.log("Using zuwadDashboard.ajaxurl");
    var ajaxUrl = zuwadDashboard.ajaxurl;
  } else if (typeof zuwadPlugin !== "undefined" && zuwadPlugin.ajaxurl) {
    console.log("Using zuwadPlugin.ajaxurl");
    var ajaxUrl = zuwadPlugin.ajaxurl;
  } else {
    console.error("AJAX URL not found. Dashboard will not function properly.");
    $("#zuwad-users-table tbody").html(
      '<tr><td colspan="12" class="zuwad-loading">خطأ في تحميل البيانات: AJAX URL غير متوفر</td></tr>'
    );
    return;
  }

  // Initial load of data
  loadUsersData();

  // Load Chart.js and initialize charts
  loadChartsLibrary();

  // Handle search input with debounce
  $("#zuwad-search-input").on("input", function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      currentPage = 1;
      loadUsersData();
    }, 500);
  });

  // Handle filter changes
  $("#zuwad-role-filter, #zuwad-status-filter, #zuwad-country-filter").on(
    "change",
    function () {
      currentPage = 1;
      loadUsersData();
    }
  );

  // Handle pagination
  $("#zuwad-prev-page").on("click", function () {
    if (currentPage > 1 && !isLoading) {
      currentPage--;
      loadUsersData();
    }
  });

  $("#zuwad-next-page").on("click", function () {
    if (currentPage < totalPages && !isLoading) {
      currentPage++;
      loadUsersData();
    }
  });

  // Function to load users data
  function loadUsersData() {
    isLoading = true;

    // Show loading indicator
    $("#zuwad-users-table tbody").html(
      '<tr><td colspan="12" class="zuwad-loading">جاري تحميل البيانات...</td></tr>'
    );

    // Get filter values
    const search = $("#zuwad-search-input").val();
    const role = $("#zuwad-role-filter").val();
    const status = $("#zuwad-status-filter").val();
    const country = $("#zuwad-country-filter").val();

    console.log("Fetching data with filters:", {
      search,
      role,
      status,
      country,
      page: currentPage,
    });

    // Make AJAX request
    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: {
        action: "zuwad_fetch_users_data",
        search: search,
        role: role,
        status: status,
        country: country,
        page: currentPage,
        per_page: 20,
      },
      success: function (response) {
        console.log("AJAX response:", response);

        if (response.success) {
          const data = response.data;

          // Update pagination
          totalPages = data.total_pages;
          $("#zuwad-current-page").text(data.page);
          $("#zuwad-total-pages").text(data.total_pages);

          // Enable/disable pagination buttons
          $("#zuwad-prev-page").prop("disabled", data.page <= 1);
          $("#zuwad-next-page").prop("disabled", data.page >= data.total_pages);

          // Clear table
          $("#zuwad-users-table tbody").empty();

          // Check if there are users
          if (data.users.length === 0) {
            $("#zuwad-users-table tbody").html(
              '<tr><td colspan="12" class="zuwad-loading">لا توجد بيانات متطابقة مع البحث</td></tr>'
            );
          } else {
            // Populate table with users
            $.each(data.users, function (index, user) {
              const row = `
                                <tr>
                                    <td>${user.m_id || ""}</td>
                                    <td>${user.name || ""}</td>
                                    <td>${translateRole(user.role) || ""}</td>
                                    <td>${user.teacher || ""}</td>
                                    <td>${user.phone || ""}</td>
                                    <td>${user.country || ""}</td>
                                    <td>${user.lessons_number || ""}</td>
                                    <td>${user.lesson_duration || ""}</td>
                                    <td>${user.currency || ""}</td>
                                    <td>${user.amount || ""}</td>
                                    <td><span class="status-indicator ${
                                      user.status_class || ""
                                    }">${user.payment_status || ""}</span></td>
                                    <td>${user.notes || ""}</td>
                                </tr>
                            `;
              $("#zuwad-users-table tbody").append(row);
            });
          }
        } else {
          // Show error message
          $("#zuwad-users-table tbody").html(
            '<tr><td colspan="12" class="zuwad-loading">حدث خطأ أثناء تحميل البيانات</td></tr>'
          );
          console.error("Error loading data:", response);
        }

        isLoading = false;
      },
      error: function (xhr, status, error) {
        // Show error message
        $("#zuwad-users-table tbody").html(
          '<tr><td colspan="12" class="zuwad-loading">حدث خطأ أثناء تحميل البيانات</td></tr>'
        );
        console.error("AJAX error:", xhr.responseText, status, error);
        isLoading = false;
      },
    });
  }

  // Helper function to translate role names to Arabic
  function translateRole(role) {
    const translations = {
      student: "طالب",
      teacher: "معلم",
      supervisor: "مشرف",
      administrator: "مدير",
      KPI: "مراقب أداء",
      sales: "مبيعات",
      Accountant: "محاسب",
    };

    // Handle multiple roles (comma separated)
    if (role && role.includes(",")) {
      const roles = role.split(",");
      return roles.map((r) => translations[r.trim()] || r.trim()).join(", ");
    }

    return translations[role] || role;
  }

  // Add export functionality
  $("#zuwad-export-btn").on("click", function () {
    // Get current filter values
    const search = $("#zuwad-search-input").val();
    const role = $("#zuwad-role-filter").val();
    const status = $("#zuwad-status-filter").val();
    const country = $("#zuwad-country-filter").val();

    // Show loading message
    Swal.fire({
      title: "جاري تحضير الملف...",
      text: "يرجى الانتظار",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    // Make AJAX request to get all data (without pagination)
    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: {
        action: "zuwad_fetch_users_data",
        search: search,
        role: role,
        status: status,
        country: country,
        page: 1,
        per_page: 5000, // Increased from 1000 to 5000 to handle all students
      },
      success: function (response) {
        if (response.success) {
          const data = response.data.users;

          // Create CSV content
          let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // Add BOM for proper Arabic encoding

          // Add headers
          csvContent +=
            "م,الاسم,الدور,المعلم,الهاتف,البلد,الحصص,المدة,العملة,المبلغ,حالة الدفع,ملاحظات\n";

          // Add data rows
          data.forEach(function (user) {
            const row = [
              user.m_id || "",
              user.name || "",
              translateRole(user.role) || "",
              user.teacher || "",
              user.phone || "",
              user.country || "",
              user.lessons_number || "",
              user.lesson_duration || "",
              user.currency || "",
              user.amount || "",
              user.payment_status || "",
              user.notes || "",
            ];

            // Escape commas and quotes in the data
            const escapedRow = row.map((field) => {
              // If field contains commas or quotes, wrap in quotes
              if (field && (field.includes(",") || field.includes('"'))) {
                return '"' + field.replace(/"/g, '""') + '"';
              }
              return field;
            });

            csvContent += escapedRow.join(",") + "\n";
          });

          // Create download link
          const encodedUri = encodeURI(csvContent);
          const link = document.createElement("a");
          link.setAttribute("href", encodedUri);
          link.setAttribute("download", "zuwad_users_data.csv");
          document.body.appendChild(link);

          // Trigger download
          link.click();
          document.body.removeChild(link);

          // Close loading message
          Swal.close();
        } else {
          // Show error message
          Swal.fire({
            icon: "error",
            title: "خطأ",
            text: "حدث خطأ أثناء تصدير البيانات",
          });
          console.error("Error exporting data:", response);
        }
      },
      error: function (xhr, status, error) {
        // Show error message
        Swal.fire({
          icon: "error",
          title: "خطأ",
          text: "حدث خطأ أثناء تصدير البيانات",
        });
        console.error("AJAX error:", xhr.responseText, status, error);
      },
    });
  });

  // Function to load Chart.js library and initialize charts
  function loadChartsLibrary() {
    // Check if Chart.js is already loaded
    if (typeof Chart !== 'undefined') {
      initializeCharts();
      return;
    }

    // Load Chart.js from CDN
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
    script.onload = function() {
      console.log('Chart.js loaded successfully');
      initializeCharts();
    };
    script.onerror = function() {
      console.error('Failed to load Chart.js');
    };
    document.head.appendChild(script);
  }

  // Function to initialize all charts
  function initializeCharts() {
    loadRevenueChart();
    loadLessonsChart();
    loadPaymentStatusChart();
    loadCountriesChart();
  }

  // Load revenue chart
  function loadRevenueChart() {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_fetch_revenue_chart_data'
      },
      success: function(response) {
        if (response.success) {
          createLineChart('revenueChart', {
            labels: response.data.labels,
            datasets: [{
              label: 'الإيرادات اليومية (ج.م)',
              data: response.data.data,
              borderColor: '#3498db',
              backgroundColor: 'rgba(52, 152, 219, 0.1)',
              borderWidth: 3,
              fill: true,
              tension: 0.4
            }]
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading revenue chart:', error);
      }
    });
  }

  // Load lessons chart
  function loadLessonsChart() {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_fetch_lessons_chart_data'
      },
      success: function(response) {
        if (response.success) {
          createBarChart('lessonsChart', {
            labels: response.data.labels,
            datasets: [{
              label: 'عدد الحصص',
              data: response.data.data,
              backgroundColor: 'rgba(46, 204, 113, 0.8)',
              borderColor: '#2ecc71',
              borderWidth: 2
            }]
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading lessons chart:', error);
      }
    });
  }

  // Load payment status chart
  function loadPaymentStatusChart() {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_fetch_payment_status_chart_data'
      },
      success: function(response) {
        if (response.success) {
          createDoughnutChart('paymentStatusChart', {
            labels: response.data.labels,
            datasets: [{
              data: response.data.data,
              backgroundColor: [
                '#2ecc71', // نشط - green
                '#e74c3c', // في انتظار الدفع - red
                '#95a5a6', // متوقف - gray
                '#3498db', // مدفوع - blue
                '#f39c12'  // ملغي - orange
              ],
              borderWidth: 2,
              borderColor: '#fff'
            }]
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading payment status chart:', error);
      }
    });
  }

  // Load countries chart
  function loadCountriesChart() {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_fetch_countries_chart_data'
      },
      success: function(response) {
        if (response.success) {
          createHorizontalBarChart('countriesChart', {
            labels: response.data.labels,
            datasets: [{
              label: 'عدد الطلاب',
              data: response.data.data,
              backgroundColor: [
                '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6',
                '#1abc9c', '#34495e', '#e67e22', '#95a5a6', '#f1c40f'
              ],
              borderWidth: 1
            }]
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading countries chart:', error);
      }
    });
  }

  // Helper function to create line chart
  function createLineChart(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
      type: 'line',
      data: data,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return value.toLocaleString('ar-EG');
              }
            }
          },
          x: {
            title: {
              display: true,
              text: 'أيام الشهر'
            }
          }
        }
      }
    });
  }

  // Helper function to create bar chart
  function createBarChart(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
      type: 'bar',
      data: data,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top'
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          },
          x: {
            title: {
              display: true,
              text: 'أيام الشهر'
            }
          }
        }
      }
    });
  }

  // Helper function to create doughnut chart
  function createDoughnutChart(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
      type: 'doughnut',
      data: data,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'bottom'
          }
        }
      }
    });
  }

  // Helper function to create horizontal bar chart
  function createHorizontalBarChart(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
      type: 'bar',
      data: data,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        }
      }
    });
  }
});
