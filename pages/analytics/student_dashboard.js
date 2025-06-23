jQuery(document).ready(function ($) {
  // Variables to track current state
  let currentPage = 1;
  let totalPages = 1;
  let searchTimer;
  let isLoading = false;

  // Log to check if script is loaded
  // console.log("Student Dashboard JS loaded");

  // Check if the AJAX URL is available
  if (typeof zuwadDashboard !== "undefined" && zuwadDashboard.ajaxurl) {
    // console.log("Using zuwadDashboard.ajaxurl");
    var ajaxUrl = zuwadDashboard.ajaxurl;
  } else if (typeof zuwadPlugin !== "undefined" && zuwadPlugin.ajaxurl) {
    // console.log("Using zuwadPlugin.ajaxurl");
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

  // Add click handlers for analytics cards
  setupAnalyticsCardHandlers();

  // Setup month selector
  setupMonthSelector();

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

    // console.log("Fetching data with filters:", {
    //   search,
    //   role,
    //   status,
    //   country,
    //   page: currentPage,
    // });

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
        // console.log("AJAX response:", response);

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
    loadFirstLessonsChart();
    loadPaymentStatusChart();
    loadCountriesChart();
  }

  // Load revenue chart
  function loadRevenueChart(selectedMonth = 'current') {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_analytics_fetch_revenue_chart_data',
        selected_month: selectedMonth
      },
      success: function(response) {
        if (response.success) {
          const chartType = response.data.chart_type;
          const chartTitle = chartType === 'monthly' ?
            'الإيرادات الشهرية (نشط) ج.م' :
            'الإيرادات اليومية (نشط) ج.م';

          const xAxisTitle = chartType === 'monthly' ? 'الأشهر' : 'أيام الشهر';

          window.revenueChartInstance = createLineChart('revenueChart', {
            labels: response.data.labels,
            datasets: [{
              label: chartTitle,
              data: response.data.data,
              borderColor: '#3498db',
              backgroundColor: 'rgba(52, 152, 219, 0.1)',
              borderWidth: 3,
              fill: true,
              tension: 0.4
            }]
          }, xAxisTitle);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading revenue chart:', error);
      }
    });
  }

  // Load lessons chart
  function loadLessonsChart(selectedMonth = 'current') {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_analytics_fetch_lessons_chart_data',
        selected_month: selectedMonth
      },
      success: function(response) {
        if (response.success) {
          const chartType = response.data.chart_type;
          const chartTitle = chartType === 'monthly' ?
            'عدد الحصص الشهرية' :
            'عدد الحصص اليومية';

          const xAxisTitle = chartType === 'monthly' ? 'الأشهر' : 'أيام الشهر';

          window.lessonsChartInstance = createBarChart('lessonsChart', {
            labels: response.data.labels,
            datasets: [{
              label: chartTitle,
              data: response.data.data,
              backgroundColor: 'rgba(46, 204, 113, 0.8)',
              borderColor: '#2ecc71',
              borderWidth: 2
            }]
          }, xAxisTitle);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading lessons chart:', error);
      }
    });
  }

  // Load first lessons chart
  function loadFirstLessonsChart(selectedMonth = 'current') {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_analytics_fetch_first_lessons_chart_data',
        selected_month: selectedMonth
      },
      success: function(response) {
        if (response.success) {
          const chartType = response.data.chart_type;
          const chartTitle = chartType === 'monthly' ?
            'الطلاب الجدد الشهرية' :
            'الطلاب الجدد اليومية';

          const xAxisTitle = chartType === 'monthly' ? 'الأشهر' : 'أيام الشهر';

          window.firstLessonsChartInstance = createBarChart('firstLessonsChart', {
            labels: response.data.labels,
            datasets: [{
              label: chartTitle,
              data: response.data.data,
              backgroundColor: 'rgba(155, 89, 182, 0.8)',
              borderColor: '#9b59b6',
              borderWidth: 2
            }]
          }, xAxisTitle);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error loading first lessons chart:', error);
      }
    });
  }

  // Load payment status chart
  function loadPaymentStatusChart() {
    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_analytics_fetch_payment_status_chart_data'
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
                '#f39c12', // ملغي - orange
                '#3498db'  // Any other status - blue
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
        action: 'zuwad_analytics_fetch_countries_chart_data'
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
  function createLineChart(canvasId, data, xAxisTitle = '') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    return new Chart(ctx, {
      type: 'line',
      data: data,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          intersect: false,
        },
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              boxWidth: 12,
              padding: 10
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              maxTicksLimit: 6,
              callback: function(value) {
                return value.toLocaleString('ar-EG');
              }
            }
          },
          x: {
            ticks: {
              maxTicksLimit: 10
            },
            title: {
              display: xAxisTitle ? true : false,
              text: xAxisTitle
            }
          }
        }
      }
    });
  }

  // Helper function to create bar chart
  function createBarChart(canvasId, data, xAxisTitle = '') {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    return new Chart(ctx, {
      type: 'bar',
      data: data,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: 'top',
            labels: {
              boxWidth: 12,
              padding: 10
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              maxTicksLimit: 6
            }
          },
          x: {
            ticks: {
              maxTicksLimit: 10
            },
            title: {
              display: xAxisTitle ? true : false,
              text: xAxisTitle
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
        cutout: '60%',
        plugins: {
          legend: {
            display: true,
            position: 'bottom',
            labels: {
              boxWidth: 12,
              padding: 8,
              usePointStyle: true
            }
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
              stepSize: 1,
              maxTicksLimit: 6
            }
          },
          y: {
            ticks: {
              maxTicksLimit: 8
            }
          }
        }
      }
    });
  }

  // Setup click handlers for analytics cards
  function setupAnalyticsCardHandlers() {
    $('.clickable-card').on('click', function() {
      const cardType = $(this).data('type');

      if (cardType.includes('revenue')) {
        showRevenueBreakdown(cardType);
      } else if (cardType.includes('first-lessons')) {
        showFirstLessonsBreakdown(cardType);
      } else if (cardType.includes('lessons')) {
        showLessonsBreakdown(cardType);
      }
    });
  }

  // Setup month selector
  function setupMonthSelector() {
    $('#analytics-month-selector').on('change', function() {
      const selectedMonth = $(this).val();
      console.log('Month changed to:', selectedMonth);

      // Update analytics cards if needed
      updateAnalyticsCards(selectedMonth);

      // Reload charts with new month data
      reloadChartsForMonth(selectedMonth);
    });
  }

  // Update analytics cards for selected month
  function updateAnalyticsCards(selectedMonth) {
    // For now, we'll keep the current month data in cards
    // and only update charts. Cards will show current month data
    // while charts show selected month/all months data
  }

  // Reload charts for selected month
  function reloadChartsForMonth(selectedMonth) {
    // Clear existing charts
    if (window.revenueChartInstance) {
      window.revenueChartInstance.destroy();
    }
    if (window.lessonsChartInstance) {
      window.lessonsChartInstance.destroy();
    }
    if (window.firstLessonsChartInstance) {
      window.firstLessonsChartInstance.destroy();
    }

    // Reload charts with new month
    loadRevenueChart(selectedMonth);
    loadLessonsChart(selectedMonth);
    loadFirstLessonsChart(selectedMonth);

    // Payment status and countries charts don't change with month selection
    // They always show current overall data
  }

  // Show revenue breakdown popup
  function showRevenueBreakdown(cardType) {
    const breakdownType = cardType === 'revenue-today' ? 'today' : 'month';
    const title = cardType === 'revenue-today' ? 'تفاصيل إيرادات اليوم' : 'تفاصيل إيرادات الشهر';

    // Show loading
    Swal.fire({
      title: 'جاري تحميل التفاصيل...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_analytics_fetch_revenue_breakdown',
        breakdown_type: breakdownType
      },
      success: function(response) {
        if (response.success) {
          const data = response.data;
          let htmlContent = '<div style="text-align: right; direction: rtl;">';

          if (data.breakdown.length === 0) {
            htmlContent += '<p>لا توجد إيرادات في هذه الفترة</p>';
          } else {
            htmlContent += '<div style="margin-bottom: 15px;">';
            htmlContent += `<strong>إجمالي الإيرادات: ${data.total_revenue.toLocaleString('ar-EG')} ج.م</strong>`;
            htmlContent += '</div>';

            htmlContent += '<table style="width: 100%; border-collapse: collapse;">';
            htmlContent += '<thead><tr style="background-color: #f8f9fa;">';
            htmlContent += '<th style="padding: 10px; border: 1px solid #ddd;">العملة</th>';
            htmlContent += '<th style="padding: 10px; border: 1px solid #ddd;">المبلغ</th>';
            htmlContent += '<th style="padding: 10px; border: 1px solid #ddd;">عدد الطلاب</th>';
            htmlContent += '</tr></thead><tbody>';

            data.breakdown.forEach(function(item) {
              htmlContent += '<tr>';
              htmlContent += `<td style="padding: 10px; border: 1px solid #ddd;">${item.currency}</td>`;
              htmlContent += `<td style="padding: 10px; border: 1px solid #ddd;">${item.formatted_amount}</td>`;
              htmlContent += `<td style="padding: 10px; border: 1px solid #ddd;">${item.student_count}</td>`;
              htmlContent += '</tr>';
            });

            htmlContent += '</tbody></table>';
          }

          htmlContent += '</div>';

          Swal.fire({
            title: title,
            html: htmlContent,
            width: '600px',
            confirmButtonText: 'إغلاق'
          });
        } else {
          Swal.fire('خطأ', 'حدث خطأ أثناء تحميل البيانات', 'error');
        }
      },
      error: function() {
        Swal.fire('خطأ', 'حدث خطأ أثناء تحميل البيانات', 'error');
      }
    });
  }

  // Show lessons breakdown popup
  function showLessonsBreakdown(cardType) {
    const breakdownType = cardType === 'lessons-today' ? 'today' : 'month';
    const title = cardType === 'lessons-today' ? 'تفاصيل حصص اليوم' : 'تفاصيل حصص الشهر';

    // Show loading
    Swal.fire({
      title: 'جاري تحميل التفاصيل...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_analytics_fetch_lessons_breakdown',
        breakdown_type: breakdownType
      },
      success: function(response) {
        if (response.success) {
          const data = response.data;
          let htmlContent = '<div style="text-align: right; direction: rtl;">';

          if (data.breakdown.length === 0) {
            htmlContent += '<p>لا توجد حصص في هذه الفترة</p>';
          } else {
            htmlContent += '<div style="margin-bottom: 15px;">';
            htmlContent += `<strong>إجمالي الحصص: ${data.total_lessons.toLocaleString('ar-EG')}</strong>`;
            htmlContent += '</div>';

            htmlContent += '<div style="display: grid; gap: 10px;">';

            data.breakdown.forEach(function(item) {
              const percentage = ((item.count / data.total_lessons) * 100).toFixed(1);
              htmlContent += '<div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid ' + item.color + ';">';
              htmlContent += `<div style="display: flex; align-items: center; gap: 10px;">`;
              htmlContent += `<span style="font-size: 1.2em;">${item.icon}</span>`;
              htmlContent += `<span style="font-weight: bold;">${item.attendance}</span>`;
              htmlContent += `</div>`;
              htmlContent += `<div style="text-align: left;">`;
              htmlContent += `<span style="font-size: 1.1em; font-weight: bold; color: ${item.color};">${item.count}</span>`;
              htmlContent += `<span style="font-size: 0.9em; color: #666; margin-right: 5px;">(${percentage}%)</span>`;
              htmlContent += `</div>`;
              htmlContent += '</div>';
            });

            htmlContent += '</div>';
          }

          htmlContent += '</div>';

          Swal.fire({
            title: title,
            html: htmlContent,
            width: '600px',
            confirmButtonText: 'إغلاق'
          });
        } else {
          Swal.fire('خطأ', 'حدث خطأ أثناء تحميل البيانات', 'error');
        }
      },
      error: function() {
        Swal.fire('خطأ', 'حدث خطأ أثناء تحميل البيانات', 'error');
      }
    });
  }

  // Show first lessons breakdown popup
  function showFirstLessonsBreakdown(cardType) {
    const breakdownType = cardType === 'first-lessons-today' ? 'today' : 'month';
    const title = cardType === 'first-lessons-today' ? 'تفاصيل الطلاب الجدد اليوم' : 'تفاصيل الطلاب الجدد الشهر';

    // Show loading
    Swal.fire({
      title: 'جاري تحميل التفاصيل...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    console.log('Making AJAX request to:', ajaxUrl);
    console.log('Action:', 'zuwad_analytics_fetch_first_lessons_breakdown');
    console.log('Breakdown type:', breakdownType);

    $.ajax({
      url: ajaxUrl,
      type: 'POST',
      data: {
        action: 'zuwad_analytics_fetch_first_lessons_breakdown',
        breakdown_type: breakdownType
      },
      success: function(response) {
        console.log('First lessons breakdown response:', response);
        console.log('Response type:', typeof response);

        // Check if response is a string (HTML error page)
        if (typeof response === 'string') {
          console.error('Server returned HTML instead of JSON:', response);
          Swal.fire('خطأ', 'خطأ في الخادم - تم إرجاع HTML بدلاً من JSON', 'error');
          return;
        }

        if (response.success) {
          const data = response.data;
          console.log('Breakdown data:', data);

          // Handle simple test response
          if (typeof data === 'string') {
            Swal.fire('نجح الاختبار!', data, 'success');
            return;
          }

          let htmlContent = '<div style="text-align: right; direction: rtl;">';

          if (!data.breakdown || data.breakdown.length === 0) {
            htmlContent += '<p>لا توجد حصص أولى في هذه الفترة</p>';
          } else {
            htmlContent += '<div style="margin-bottom: 15px;">';
            htmlContent += `<strong>إجمالي الطلاب الجدد: ${data.total_first_lessons.toLocaleString('ar-EG')}</strong>`;
            htmlContent += '</div>';

            htmlContent += '<div style="display: grid; gap: 10px;">';

            data.breakdown.forEach(function(item) {
              const percentage = ((item.count / data.total_first_lessons) * 100).toFixed(1);
              htmlContent += '<div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background-color: #f8f9fa; border-radius: 8px; border-left: 4px solid ' + item.color + ';">';
              htmlContent += `<div style="display: flex; align-items: center; gap: 10px;">`;
              htmlContent += `<span style="font-size: 1.2em;">${item.icon}</span>`;
              htmlContent += `<span style="font-weight: bold;">${item.attendance}</span>`;
              htmlContent += `</div>`;
              htmlContent += `<div style="text-align: left;">`;
              htmlContent += `<span style="font-size: 1.1em; font-weight: bold; color: ${item.color};">${item.count}</span>`;
              htmlContent += `<span style="font-size: 0.9em; color: #666; margin-right: 5px;">(${percentage}%)</span>`;
              htmlContent += `</div>`;
              htmlContent += '</div>';

              // Add country breakdown
              if (item.countries && Object.keys(item.countries).length > 0) {
                htmlContent += '<div style="margin-top: 8px; margin-right: 20px; font-size: 0.9em;">';

                const countries = Object.entries(item.countries);
                const countryTexts = countries.map(([country, count]) => `${count} من ${country}`);

                htmlContent += '<span style="color: #666;">';
                htmlContent += countryTexts.join(' • ');
                htmlContent += '</span>';

                htmlContent += '</div>';
              }
            });

            htmlContent += '</div>';
          }

          htmlContent += '</div>';

          Swal.fire({
            title: title,
            html: htmlContent,
            width: '600px',
            confirmButtonText: 'إغلاق'
          });
        } else {
          console.error('Server returned error:', response);
          Swal.fire('خطأ', response.data || 'حدث خطأ أثناء تحميل البيانات', 'error');
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX error:', xhr, status, error);
        console.error('Response text:', xhr.responseText);
        Swal.fire('خطأ', 'حدث خطأ في الاتصال: ' + error, 'error');
      }
    });
  }
});
