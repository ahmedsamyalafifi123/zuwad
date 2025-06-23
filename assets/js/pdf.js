/**
 * Zuwad Academy PDF Report Generator
 * 
 * This script handles generating and sharing student reports from the report modal.
 * It uses the shared ZuwadReportUtils module for core functionality.
 * 
 * @author Zuwad Academy Team
 * @version 1.0.0
 */
(function(window, document, $, undefined) {
  'use strict';

  // Global variable to track temporary report image
  let tempReportImageUrl = null;

  /**
   * Report Actions - Handles user actions for reports
   */
  const ReportActions = {
    /**
     * Shows the report options dialog
     * @param {Object} renderData - The rendered report data (canvas, PDF, etc.)
     * @param {string} reportId - The report ID
     */
    showReportOptions: function(renderData, reportId) {
      Swal.close();
      
      Swal.fire({
        icon: "success",
        title: "تم إنشاء التقرير بنجاح",
        html: `
          <p>تم إنشاء التقرير بنجاح. اختر الإجراء التالي:</p>
          <button id="download-pdf" class="swal2-confirm swal2-styled" style="background-color: #6a041a;">تحميل التقرير (PDF)</button>
          <button id="download-image" class="swal2-confirm swal2-styled" style="background-color: #f7bf00;">تحميل كصورة (PNG)</button>
          ${
            ZuwadReportUtils.PermissionChecker.shouldHideWhatsAppButton()
              ? ""
              : '<button id="share-whatsapp" class="swal2-confirm swal2-styled" style="background-color: #25D366;">إرسال للواتساب</button>'
          }
          <div style="margin-top: 15px;">
            <img src="${renderData.imageDataUrl}" alt="تقرير الطالب" style="max-width: 100%; max-height: 400px; border: 1px solid #ddd; border-radius: 4px; margin-top: 10px;">
          </div>
        `,
        showConfirmButton: false,
        allowOutsideClick: false,
        customClass: {
          popup: 'report-preview-popup'
        }
      });
      
      // PDF download button
      document.getElementById("download-pdf").addEventListener("click", function() {
        renderData.pdf.save("student_report.pdf");
        Swal.close();
      });
      
      // Image download button
      document.getElementById("download-image").addEventListener("click", function() {
        const link = document.createElement("a");
        link.href = renderData.imageDataUrl;
        link.download = "student_report.png";
        link.click();
        Swal.close();
      });
      
      // WhatsApp share button
      if (!ZuwadReportUtils.PermissionChecker.shouldHideWhatsAppButton()) {
        document.getElementById("share-whatsapp").addEventListener("click", function() {
          // Use the shared utility to send the report with retry capability
          ZuwadReportUtils.WhatsAppSender.sendWithRetry(reportId, renderData, {
            maxRetries: 1
          });
        });
      }
    },
    
    /**
     * Handle the share report button click
     */
    handleShareReportClick: function() {
  // Get the currently opened report's ID
  const reportId = document.querySelector("#report-modal").dataset.reportId;

  if (!reportId) {
    Swal.fire("خطأ", "لم يتم العثور على التقرير المفتوح.", "error");
    return;
  }

  // Show loading indicator
  Swal.fire({
    title: "جارٍ إنشاء التقرير...",
    html: "يرجى الانتظار بينما نقوم بإنشاء التقرير.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

      // Fetch and generate the report using the shared utils
      ZuwadReportUtils.ReportGenerator.fetchReportData(reportId)
        .then((reportData) => {
          return ZuwadReportUtils.ReportGenerator.renderReport(reportData, { debug: false });
        })
        .then((renderData) => {
          this.showReportOptions(renderData, reportId);
        })
        .catch((error) => {
          console.error("Error generating report:", error);
    Swal.close();
          Swal.fire("خطأ", "حدث خطأ أثناء إنشاء التقرير.", "error");
        });
    }
  };

  /**
   * Initialize the module
   */
  function init() {
    // Add event listener for sharing report
    const shareReportBtn = document.getElementById("share-report");
    if (shareReportBtn) {
      shareReportBtn.addEventListener("click", ReportActions.handleShareReportClick.bind(ReportActions));
    }
    
    // Add event listener for modal close to remove temp image
    $("#report-modal").on("hidden.bs.modal", function() {
    if (tempReportImageUrl) {
        ZuwadReportUtils.FileUtils.removeTemporaryImage(tempReportImageUrl)
          .then(() => {
      tempReportImageUrl = null;
          })
          .catch((error) => {
            console.error("Error removing temporary image:", error);
          });
      }
    });
  }

  // Initialize when document is ready
  $(document).ready(init);

})(window, document, jQuery);
