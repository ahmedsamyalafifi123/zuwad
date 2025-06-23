/**
 * Payment Receipts JavaScript
 */

jQuery(document).ready(function($) {
    // Variables
    let currentPage = 1;
    let totalPages = 1;
    const perPage = 10;
    
    // Initial load
    loadPaymentReceipts();
    
    // Event listeners for filters
    $('#zuwad-search-student').on('input', debounce(function() {
        currentPage = 1;
        loadPaymentReceipts();
    }, 500));
    
    $('#zuwad-filter-payment-method, #zuwad-filter-currency').on('change', function() {
        currentPage = 1;
        loadPaymentReceipts();
    });
    
    $('#zuwad-filter-date-from, #zuwad-filter-date-to').on('change', function() {
        currentPage = 1;
        loadPaymentReceipts();
    });
    
    // Reset filters
    $('#zuwad-reset-filters').on('click', function() {
        $('#zuwad-search-student').val('');
        $('#zuwad-filter-payment-method').val('');
        $('#zuwad-filter-currency').val('');
        $('#zuwad-filter-date-from').val('');
        $('#zuwad-filter-date-to').val('');
        currentPage = 1;
        loadPaymentReceipts();
    });
    
    // Event delegation for action buttons
    $(document).on('click', '.zuwad-view-receipt', function() {
        const receiptId = $(this).data('receipt-id');
        viewReceiptDetails(receiptId);
    });
    
    $(document).on('click', '.zuwad-delete-receipt', function() {
        const receiptId = $(this).data('receipt-id');
        deleteReceipt(receiptId);
    });
    
    // Add ripple effect to action buttons
    $(document).on('mousedown', '.zuwad-action-button', function(e) {
        const button = $(this);
        const ripple = button.find('.zuwad-button-ripple');
        
        // Remove any existing animation
        ripple.css({
            'transform': 'scale(0)',
            'opacity': '0'
        });
        
        // Calculate ripple position
        const buttonRect = button[0].getBoundingClientRect();
        const offsetX = e.clientX - buttonRect.left;
        const offsetY = e.clientY - buttonRect.top;
        
        // Set ripple position and animate
        ripple.css({
            'top': offsetY + 'px',
            'left': offsetX + 'px',
            'transform': 'scale(0)',
            'opacity': '1'
        });
        
        // Trigger animation
        setTimeout(function() {
            ripple.css({
                'transform': 'scale(4)',
                'opacity': '0',
                'transition': 'transform 0.6s ease-out, opacity 0.6s ease-out'
            });
        }, 10);
    });
    
    // Pagination
    $('#zuwad-prev-page').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadPaymentReceipts();
        }
    });
    
    $('#zuwad-next-page').on('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            loadPaymentReceipts();
        }
    });
    
    // Modal close
    $('.zuwad-modal-close').on('click', function() {
        $('#zuwad-receipt-modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if ($(event.target).is('#zuwad-receipt-modal')) {
            $('#zuwad-receipt-modal').hide();
        }
    });
    
    /**
     * Update the summary statistics
     */
    function updateSummaryStats(data) {
        $('#zuwad-receipts-count').text(data.total_receipts);
        $('#zuwad-receipts-total-sar').text(data.total_amount_sar);
        $('#zuwad-receipts-total-aed').text(data.total_amount_aed);
        $('#zuwad-receipts-total-egp').text(data.total_amount_egp);
        $('#zuwad-receipts-total-qar').text(data.total_amount_qar);
        $('#zuwad-receipts-total-usd').text(data.total_amount_usd);
        
        // Update additional statistics if available
        if (data.stats) {
            $('#zuwad-receipts-recent').text(data.stats.recent_count || 0);
            $('#zuwad-receipts-paypal').text(data.stats.paypal_count || 0);
            $('#zuwad-receipts-vodafone-cash').text(data.stats.vodafone_cash_count || 0);
            $('#zuwad-receipts-insta-pay').text(data.stats.instapay_count || 0);
            $('#zuwad-receipts-bank-transfer').text(data.stats.bank_transfer_count || 0);
        }
    }
    
    /**
     * Load payment receipts via AJAX
     */
    function loadPaymentReceipts() {
        // Show loading
        $('#zuwad-receipts-body').html('<tr class="zuwad-loading-row"><td colspan="10">جاري تحميل البيانات...</td></tr>');
        
        // Get filter values
        const search = $('#zuwad-search-student').val();
        const paymentMethod = $('#zuwad-filter-payment-method').val();
        const currency = $('#zuwad-filter-currency').val();
        const dateFrom = $('#zuwad-filter-date-from').val();
        const dateTo = $('#zuwad-filter-date-to').val();
        
        // AJAX request
        $.ajax({
            url: zuwadPlugin.ajaxurl,
            type: 'POST',
            data: {
                action: 'zuwad_fetch_payment_receipts',
                page: currentPage,
                per_page: perPage,
                search: search,
                payment_method: paymentMethod,
                currency: currency,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(response) {
                if (response.success) {
                    displayReceipts(response.data);
                } else {
                    $('#zuwad-receipts-body').html('<tr><td colspan="10">' + response.data.message + '</td></tr>');
                }
            },
            error: function() {
                $('#zuwad-receipts-body').html('<tr><td colspan="10">حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.</td></tr>');
            }
        });
    }
    
    /**
     * Display receipts in the table
     */
    function displayReceipts(data) {
        const receipts = data.receipts;
        totalPages = data.total_pages;
        
        // Update pagination
        $('#zuwad-current-page').text(data.current_page);
        $('#zuwad-total-pages').text(data.total_pages);
        $('#zuwad-prev-page').prop('disabled', data.current_page <= 1);
        $('#zuwad-next-page').prop('disabled', data.current_page >= data.total_pages);
        
        // Update summary statistics
        updateSummaryStats(data);
        
        // Clear table
        $('#zuwad-receipts-body').empty();
        
        // Check if no receipts found
        if (receipts.length === 0) {
            $('#zuwad-receipts-body').html('<tr><td colspan="10">لم يتم العثور على إيصالات.</td></tr>');
            return;
        }
        
        // Add receipts to table
        $.each(receipts, function(index, receipt) {
            const row = $('<tr>');
            
            // Format date
            const createdDate = new Date(receipt.created_at);
            const formattedDate = formatDate(createdDate);
            
            // Format payment method
            let paymentMethodText = formatPaymentMethod(receipt.payment_method);
            
            // Format months display
            let monthsDisplay = receipt.months;
            if (receipt.months) {
                try {
                    // Check if it's already a string representation
                    if (typeof receipt.months === 'string') {
                        // Remove any extra whitespace or special characters that might cause issues
                        const cleanedString = receipt.months.trim().replace(/[\u200B-\u200D\uFEFF]/g, '');
                        
                        // Try to parse if it looks like JSON
                        if (cleanedString.startsWith('[') && cleanedString.endsWith(']')) {
                            const monthsArray = JSON.parse(cleanedString);
                            if (Array.isArray(monthsArray)) {
                                // Join array elements without quotes
                                monthsDisplay = monthsArray.join('، ');
                            }
                        }
                    } else if (Array.isArray(receipt.months)) {
                        // If it's already an array
                        monthsDisplay = receipt.months.join('، ');
                    }
                } catch (e) {
                    console.error('Error parsing months JSON:', e);
                    // If parsing fails, use the original value without quotes and brackets
                    if (typeof receipt.months === 'string') {
                        // Remove brackets and quotes if present
                        monthsDisplay = receipt.months.replace(/[\[\]"]/g, '');
                    }
                }
            }
            
            // Create table row with icons
            row.append($('<td>').html('<span class="zuwad-icon zuwad-icon-id"></span>' + receipt.student_id));
            row.append($('<td>').html('<span class="zuwad-icon zuwad-icon-user"></span>' + receipt.student_name));
            
            // Add amount with currency-specific styling
            const amountCell = $('<td>').html('<span class="zuwad-icon zuwad-icon-money"></span>' + receipt.amount);
            row.append(amountCell);
            
            // Add currency with specific styling based on currency type
            const currencyCell = $('<td>').addClass('zuwad-currency-' + receipt.currency.toLowerCase())
                .html('<span class="zuwad-icon zuwad-icon-currency"></span>' + receipt.currency);
            row.append(currencyCell);
            
            // Payment method with icon
            let paymentMethodIcon = '';
            if (paymentMethodText.includes('بنك')) {
                paymentMethodIcon = 'zuwad-icon-bank';
            } else if (paymentMethodText.includes('PayPal')) {
                paymentMethodIcon = 'zuwad-icon-paypal';
            } else if (paymentMethodText.includes('فودافون')) {
                paymentMethodIcon = 'zuwad-icon-vodafone';
            } else {
                paymentMethodIcon = 'zuwad-icon-payment';
            }
            row.append($('<td>').html('<span class="zuwad-icon ' + paymentMethodIcon + '"></span>' + paymentMethodText));
            
            // Bank name with icon
            row.append($('<td>').html('<span class="zuwad-icon zuwad-icon-bank"></span>' + (receipt.bank_name || '-')));
            
            // Months with icon
            row.append($('<td>').html('<span class="zuwad-icon zuwad-icon-calendar"></span>' + (monthsDisplay || '-')));
            
            // Date with icon
            row.append($('<td>').html('<span class="zuwad-icon zuwad-icon-date"></span>' + formattedDate));
            
            // Notes with icon
            row.append($('<td>').html('<span class="zuwad-icon zuwad-icon-notes"></span>' + (receipt.notes || '-')));
            
            // Create action buttons container
            const actionsContainer = $('<div>').addClass('zuwad-receipt-actions');
            
            // Add view button with icon
            const viewButton = $('<button>')
                .addClass('zuwad-view-receipt zuwad-action-button')
                .attr('data-receipt-id', receipt.id)
                .attr('title', 'عرض تفاصيل الإيصال');
                
            // Add eye icon and text to view button
            const viewIcon = $('<span>').addClass('zuwad-button-icon').html('👁️');
            const viewText = $('<span>').addClass('zuwad-button-text').text('عرض');
            viewButton.append(viewIcon).append(viewText);
            
            // Add ripple effect div
            const viewRipple = $('<span>').addClass('zuwad-button-ripple');
            viewButton.append(viewRipple);
            actionsContainer.append(viewButton);
            
            // Add delete button with icon
            const deleteButton = $('<button>')
                .addClass('zuwad-delete-receipt zuwad-action-button zuwad-action-delete')
                .attr('data-receipt-id', receipt.id)
                .attr('title', 'حذف الإيصال');
                
            // Add trash icon and text to delete button
            const deleteIcon = $('<span>').addClass('zuwad-button-icon').html('🗑️');
            const deleteText = $('<span>').addClass('zuwad-button-text').text('حذف');
            deleteButton.append(deleteIcon).append(deleteText);
            
            // Add ripple effect div
            const deleteRipple = $('<span>').addClass('zuwad-button-ripple');
            deleteButton.append(deleteRipple);
            actionsContainer.append(deleteButton);
            
            const actionsCell = $('<td>');
            actionsCell.append(actionsContainer);
            row.append(actionsCell);
            
            // Add row to table
            $('#zuwad-receipts-body').append(row);
        });
        
        // We'll add event delegation outside this function
        // to avoid adding multiple event handlers
    }
    
    /**
     * View receipt details
     */
    function viewReceiptDetails(receiptId) {
        $.ajax({
            url: zuwadPlugin.ajaxurl,
            type: 'POST',
            data: {
                action: 'zuwad_view_receipt_details',
                receipt_id: receiptId
            },
            success: function(response) {
                if (response.success) {
                    displayReceiptModal(response.data.receipt);
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('حدث خطأ أثناء تحميل تفاصيل الإيصال. يرجى المحاولة مرة أخرى.');
            }
        });
    }
    
    /**
     * Format payment method text
     */
    function formatPaymentMethod(method) {
        if (method === 'bank') {
            return 'تحويل بنكي';
        } else if (method === 'paypal') {
            return 'PayPal';
        } else if (method === 'vodafone_cash') {
            return 'Vodafone Cash';
        } else if (method === 'instapay') {
            return 'InstaPay';
        } else {
            return method;
        }
    }
    
    /**
     * Display receipt modal
     */
    function displayReceiptModal(receipt) {
        // Format date
        const createdDate = new Date(receipt.created_at);
        const formattedDate = formatDate(createdDate);
        
        // Format payment method
        const paymentMethodText = formatPaymentMethod(receipt.payment_method);
        
        // Build receipt details HTML with icons
        let detailsHtml = '<div class="zuwad-receipt-details-header"><h4>معلومات الدفع</h4></div>';
        detailsHtml += '<div class="zuwad-receipt-details-grid">';
        
        // Student info section
        detailsHtml += '<div class="zuwad-receipt-details-section">';
        detailsHtml += '<h5 class="zuwad-receipt-section-title">معلومات الطالب</h5>';
        detailsHtml += '<p><span class="zuwad-detail-icon zuwad-icon-id"></span><strong>رقم الطالب:</strong> <span class="zuwad-detail-value">' + receipt.student_id + '</span></p>';
        detailsHtml += '<p><span class="zuwad-detail-icon zuwad-icon-user"></span><strong>اسم الطالب:</strong> <span class="zuwad-detail-value">' + receipt.student_name + '</span></p>';
        detailsHtml += '</div>';
        
        // Payment info section
        detailsHtml += '<div class="zuwad-receipt-details-section">';
        detailsHtml += '<h5 class="zuwad-receipt-section-title">معلومات الدفع</h5>';
        
        // Add currency-specific class for styling
        const currencyClass = 'zuwad-currency-' + receipt.currency.toLowerCase();
        detailsHtml += '<p><span class="zuwad-detail-icon zuwad-icon-money"></span><strong>المبلغ:</strong> <span class="zuwad-detail-value ' + currencyClass + '">' + receipt.amount + ' ' + receipt.currency + '</span></p>';
        
        // Add payment method with appropriate icon
        let paymentMethodIcon = 'zuwad-icon-payment';
        if (receipt.payment_method === 'bank') {
            paymentMethodIcon = 'zuwad-icon-bank';
        } else if (receipt.payment_method === 'paypal') {
            paymentMethodIcon = 'zuwad-icon-paypal';
        } else if (receipt.payment_method === 'vodafone_cash') {
            paymentMethodIcon = 'zuwad-icon-vodafone';
        }
        
        detailsHtml += '<p><span class="zuwad-detail-icon ' + paymentMethodIcon + '"></span><strong>طريقة الدفع:</strong> <span class="zuwad-detail-value">' + paymentMethodText + '</span></p>';
        
        if (receipt.bank_name) {
            detailsHtml += '<p><span class="zuwad-detail-icon zuwad-icon-bank"></span><strong>البنك:</strong> <span class="zuwad-detail-value">' + receipt.bank_name + '</span></p>';
        }
        detailsHtml += '</div>';
        
        // Time and dates section
        detailsHtml += '<div class="zuwad-receipt-details-section">';
        detailsHtml += '<h5 class="zuwad-receipt-section-title">التواريخ والشهور</h5>';
        
        // Process months
        if (receipt.months) {
            let monthsDisplay = receipt.months;
            try {
                // Check if it's already a string representation
                if (typeof receipt.months === 'string') {
                    // Remove any extra whitespace or special characters that might cause issues
                    const cleanedString = receipt.months.trim().replace(/[\u200B-\u200D\uFEFF]/g, '');
                    
                    // Try to parse if it looks like JSON
                    if (cleanedString.startsWith('[') && cleanedString.endsWith(']')) {
                        const monthsArray = JSON.parse(cleanedString);
                        if (Array.isArray(monthsArray)) {
                            monthsDisplay = monthsArray.join('، ');
                        }
                    }
                } else if (Array.isArray(receipt.months)) {
                    // If it's already an array
                    monthsDisplay = receipt.months.join('، ');
                }
            } catch (e) {
                console.error('Error parsing months JSON:', e);
                // If parsing fails, use the original value without brackets and quotes
                if (typeof receipt.months === 'string') {
                    monthsDisplay = receipt.months.replace(/[\[\]"]/g, '');
                }
            }
            detailsHtml += '<p><span class="zuwad-detail-icon zuwad-icon-calendar"></span><strong>الشهور:</strong> <span class="zuwad-detail-value zuwad-months-value">' + monthsDisplay + '</span></p>';
        }
        
        detailsHtml += '<p><span class="zuwad-detail-icon zuwad-icon-date"></span><strong>تاريخ الإيصال:</strong> <span class="zuwad-detail-value">' + formattedDate + '</span></p>';
        detailsHtml += '</div>';
        
        // Notes section if available
        if (receipt.notes) {
            detailsHtml += '<div class="zuwad-receipt-details-section">';
            detailsHtml += '<h5 class="zuwad-receipt-section-title">ملاحظات إضافية</h5>';
            detailsHtml += '<p><span class="zuwad-detail-icon zuwad-icon-notes"></span><strong>ملاحظات:</strong> <span class="zuwad-detail-value zuwad-notes-value">' + receipt.notes + '</span></p>';
            detailsHtml += '</div>';
        }
        
        // Close the grid container
        detailsHtml += '</div>';
        
        // Set details HTML
        $('#zuwad-receipt-details').html(detailsHtml);
        
        // Set receipt image/file
        let fileHtml = '';
        const fileExt = getFileExtension(receipt.file_url);
        
        if (fileExt === 'pdf') {
            fileHtml = '<iframe src="' + receipt.file_url + '"></iframe>';
        } else {
            fileHtml = '<img src="' + receipt.file_url + '" alt="إيصال الدفع">';
        }
        
        $('#zuwad-receipt-image').html(fileHtml);
        
        // Show modal
        $('#zuwad-receipt-modal').show();
    }
    
    /**
     * Helper function to format date
     */
    function formatDate(date) {
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        
        return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
    }
    
    /**
     * Helper function to get file extension
     */
    function getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }
    
    /**
     * Debounce function to limit how often a function is called
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
    
    /**
     * Delete receipt
     */
    function deleteReceipt(receiptId) {
        Swal.fire({
            title: 'تأكيد الحذف',
            text: 'هل أنت متأكد من حذف هذا الإيصال؟ هذا الإجراء لا يمكن التراجع عنه.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، حذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: zuwadPlugin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'zuwad_delete_payment_receipt',
                        receipt_id: receiptId,
                        security: zuwadPlugin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'تم الحذف',
                                text: 'تم حذف الإيصال بنجاح',
                                icon: 'success',
                                confirmButtonColor: '#6a041a'
                            });
                            loadPaymentReceipts(); // Reload the table
                        } else {
                            Swal.fire({
                                title: 'خطأ',
                                text: response.data.message || 'حدث خطأ أثناء حذف الإيصال',
                                icon: 'error',
                                confirmButtonColor: '#6a041a'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'خطأ',
                            text: 'حدث خطأ أثناء حذف الإيصال. يرجى المحاولة مرة أخرى.',
                            icon: 'error',
                            confirmButtonColor: '#6a041a'
                        });
                    }
                });
            }
        });
    }
});