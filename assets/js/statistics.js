jQuery(document).ready(function($) {
    // Variables for pagination
    let currentPage = 1;
    let totalPages = 1;
    
    // Initial data load
    loadData();
    
    // Search button click handler
    $('#zuwad-search-button').on('click', function() {
        currentPage = 1; // Reset to first page on new search
        loadData();
    });
    
    // Enter key press in search input
    $('#zuwad-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            currentPage = 1; // Reset to first page on new search
            loadData();
        }
    });
    
    // Filter change handlers
    $('#zuwad-filter-type, #zuwad-filter-status').on('change', function() {
        currentPage = 1; // Reset to first page on filter change
        loadData();
    });
    
    // Pagination handlers
    $('#zuwad-prev-page').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadData();
        }
    });
    
    $('#zuwad-next-page').on('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            loadData();
        }
    });
    
    // Function to load data from the server
    function loadData() {
        const searchTerm = $('#zuwad-search-input').val();
        const userType = $('#zuwad-filter-type').val();
        const status = $('#zuwad-filter-status').val();
        
        // Show loading indicator
        $('#zuwad-data-table tbody').html('<tr><td colspan="12" style="text-align:center;">جاري تحميل البيانات...</td></tr>');
        
        $.ajax({
            url: zuwadPlugin.ajaxurl,
            type: 'POST',
            data: {
                action: 'zuwad_fetch_users_data',
                nonce: zuwadPlugin.nonce,
                search: searchTerm,
                type: userType,
                status: status,
                page: currentPage,
                per_page: 20
            },
            success: function(response) {
                if (response && response.success) {
                    // Update pagination info
                    totalPages = response.pages;
                    updatePaginationUI(response.current_page, response.pages, response.total);
                    
                    // Clear the table
                    $('#zuwad-data-table tbody').empty();
                    
                    // Check if data is an array before using forEach
                    if (Array.isArray(response.data) && response.data.length > 0) {
                        // Populate the table with data
                        response.data.forEach(function(item) {
                            const row = `
                                <tr>
                                    <td>${item.m_id || '-'}</td>
                                    <td>${item.name || '-'}</td>
                                    <td>${item.type || '-'}</td>
                                    <td>${item.teacher || '-'}</td>
                                    <td>${item.phone || '-'}</td>
                                    <td>${item.country || '-'}</td>
                                    <td>${item.lessons_number || '-'}</td>
                                    <td>${item.lesson_duration || '-'}</td>
                                    <td>${item.currency || '-'}</td>
                                    <td>${item.amount || '-'}</td>
                                    <td>${item.payment_status || '-'}</td>
                                    <td>${item.notes || '-'}</td>
                                </tr>
                            `;
                            $('#zuwad-data-table tbody').append(row);
                        });
                    } else {
                        // Handle empty data or non-array response
                        $('#zuwad-data-table tbody').html('<tr><td colspan="12" style="text-align:center;">لا توجد بيانات متاحة</td></tr>');
                    }
                } else {
                    // Handle error response
                    $('#zuwad-data-table tbody').html('<tr><td colspan="12" style="text-align:center;">حدث خطأ أثناء تحميل البيانات</td></tr>');
                    console.error('Error loading data:', response);
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX error
                $('#zuwad-data-table tbody').html('<tr><td colspan="12" style="text-align:center;">حدث خطأ في الاتصال بالخادم</td></tr>');
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    }
    
    // Function to update pagination UI
    function updatePaginationUI(currentPage, totalPages, totalItems) {
        $('#zuwad-page-info').text(`صفحة ${currentPage} من ${totalPages} (إجمالي: ${totalItems})`);
        
        // Update buttons state
        $('#zuwad-prev-page').prop('disabled', currentPage <= 1);
        $('#zuwad-next-page').prop('disabled', currentPage >= totalPages);
    }
});
