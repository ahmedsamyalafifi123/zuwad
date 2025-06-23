/**
 * Zuwad Plugin - Student Reports Module
 * 
 * This file manages student report functionality including:
 * - Modal operations
 * - Student search and selection
 * - Report form handling
 * - Image upload handling
 * - Session number calculation
 */
(function($) {
    'use strict';

    // Module-level variables
    let lastAttendanceGroup = 'valid';
    
    /**
     * Modal Operations
     * Controls the opening and closing of report modals
     */
    const Modal = {
        init: function() {
    // Open modal
            $('#open-report-modal').click(function() {
        $('#report-modal').show();
    });

            // Close modal via close button
            $('#close-report-modal').click(function() {
        $('#report-modal').hide();
    });

    // Close modal when clicking outside
            $('#report-modal').on('click', function(e) {
        if (e.target === this) { // Ensure the click is on the overlay itself, not its children
            $('#report-modal').hide();
        }
    });
        }
    };

    /**
     * Student Search
     * Handles searching and selecting students for reports
     */
    const StudentSearch = {
        init: function() {
            // Student search input handler
            $('#student-search-report').on('input', function() {
        var searchQuery = $(this).val();
        if (searchQuery.length > 2) {
            $.ajax({
                url: zuwadPlugin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'search_students',
                    search: searchQuery
                },
                        success: function(response) {
                    if (response) {
                        $('#search-results-report').html(response).show();
                    } else {
                        $('#search-results-report').html('<div>لم يتم العثور على طلاب.</div>').show();
                    }
                }
            });
        } else {
            $('#search-results-report').hide();
        }
    });

            // Student selection from search results
            $(document).on('click', '.student-result', function() {
                StudentSearch.selectStudent($(this));
            });
        },

        selectStudent: function($selectedElement) {
            var studentName = $selectedElement.text();
            var studentId = $selectedElement.data('id');
        $('#student-search-report').val(studentName);
        $('#selected-student-id-report').val(studentId);
        $('#search-results-report').hide();
    
            // Fetch the last report date for the selected student
            StudentReports.fetchLastReportDate(studentId);
            
            // Refetch calendar events if calendar exists
            if (window.calendar) {
                window.calendar.refetchEvents();
            }
        }
    };

    /**
     * Student Reports
     * Handles report form operations and data
     */
    const StudentReports = {
        init: function() {
            // Check if user is a teacher and restrict attendance options
            this.setupAttendanceOptions();

            // DISABLED: Handle attendance selection changes
            // This was conflicting with teacher_calendar.js session number calculation
            // $('#attendance').change(function() {
            //     StudentReports.updateSessionNumber($(this).val());
            // });

            // Handle image uploads
            this.setupImageUpload();
        },

        setupAttendanceOptions: function() {
            // Check if current user is a teacher
            var isTeacher = $('body').hasClass('teacher') || 
                           (document.getElementById('teacher-calendar-container') && 
                            document.getElementById('teacher-calendar-container').dataset.isTeacher === 'true');
            
            // Restrict attendance options for teachers
            if (isTeacher) {
                var attendanceSelect = $('#attendance');
                attendanceSelect.empty();
                attendanceSelect.append('<option value="حضور">حضور</option>');
                attendanceSelect.append('<option value="غياب">غياب</option>');
            }
        },

        fetchLastReportDate: function(studentId) {
        $.ajax({
            url: zuwadPlugin.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_last_report_date',
                student_id: studentId
            },
                success: function(response) {
                if (response.success) {
                    $('#last-report-date').html('آخر انجاز: ' + response.data.date).show();
    
                        // Check if opened from button or calendar
                    var isOpenedFromButton = $('#open-report-modal').is(':visible');

                    // DISABLED: This was conflicting with teacher_calendar.js session number calculation
                    // The teacher_calendar.js already handles session number calculation correctly using backend data
                    // if (!isOpenedFromButton) {
                    //         StudentReports.calculateSessionNumber(response.data);
                    //     }
                    } else {
                        console.error('Failed to fetch last report date:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                }
            });
        },

        calculateSessionNumber: function(data) {
            // Parse data from response
            var lastSessionNumber = parseInt(data.last_session_number) || 0;
            var lessonsNumber = parseInt(data.lessons_number) || 0;
            var previousLesson = parseInt(data.previous_lesson) || 0;
            var previousLessonUsed = data.previous_lesson_used === '1';
    
                        // Calculate the base session number
                        let sessionNumberToSet = lastSessionNumber;
    
                        // Add previous_lesson only if it hasn't been used before
                        if (!previousLessonUsed) {
                            sessionNumberToSet += previousLesson;
                        }
    
                        // Apply the reset formula and increment for calendar events
            if (lessonsNumber > 0) {
                        sessionNumberToSet = (sessionNumberToSet - 1) % lessonsNumber + 1;
                        sessionNumberToSet = (sessionNumberToSet) % lessonsNumber + 1;
            }
    
                        // Set the calculated session number
                        $('#session-number').val(sessionNumberToSet);
        },

        updateSessionNumber: function(attendance) {
            var teacherAttendances = ['حضور', 'غياب'];
    var isTeacher = $('body').hasClass('teacher') ||
                   (document.getElementById('teacher-calendar-container') &&
                    document.getElementById('teacher-calendar-container').dataset.isTeacher === 'true');
        var validAttendances = isTeacher ? teacherAttendances : ['حضور', 'غياب', 'تأجيل المعلم', 'تأجيل ولي أمر'];
        var nonValidAttendances = ['تعويض التأجيل', 'تعويض الغياب', 'تجريبي'];

        // If this is a non-valid attendance type, always set session number to 0
        if (nonValidAttendances.includes(attendance)) {
            $('#session-number').val(0);
            return;
        }

        // Determine the current attendance group
        var currentAttendanceGroup = validAttendances.includes(attendance) ? 'valid' : 'non-valid';

        // Fetch the current session number
        var currentSessionNumber = parseInt($('#session-number').val()) || 0;

            // Fetch the student's lessons_number
        var studentId = $('#selected-student-id-report').val();
        if (studentId) {
            $.ajax({
                url: zuwadPlugin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_student_lessons_number',
                    student_id: studentId
                },
                    success: function(response) {
                    if (response.success) {
                        var lessonsNumber = parseInt(response.data.lessons_number) || 0;

                        // Calculate the next session number
                        var nextSessionNumber = currentSessionNumber;
                        if (lastAttendanceGroup === 'valid' && currentAttendanceGroup === 'non-valid') {
                            // Moving from valid to non-valid: decrement
                            nextSessionNumber = currentSessionNumber - 1;
                            if (nextSessionNumber < 1) {
                                nextSessionNumber = 0; // Set to 0 if it goes below 1
                            }
                        } else if (lastAttendanceGroup === 'non-valid' && currentAttendanceGroup === 'valid') {
                            // Moving from non-valid to valid: increment
                            nextSessionNumber = currentSessionNumber + 1;
                            if (nextSessionNumber > lessonsNumber) {
                                nextSessionNumber = 1; // Reset if exceeds lessons_number
                            }
                        }

                        // Update the last attendance group
                        lastAttendanceGroup = currentAttendanceGroup;

                        // Set the session number in the form
                        $('#session-number').val(nextSessionNumber);
                    }
                }
            });
        }
        },

        setupImageUpload: function() {
            // Display uploaded images preview in the form
            $('#zoom-image').on('change', function() {
    var preview = $('#zoom-image-preview');
    preview.empty(); // Clear previous previews

    if (this.files && this.files.length > 0) {
        for (var i = 0; i < this.files.length; i++) {
            var reader = new FileReader();
                        reader.onload = function(e) {
                // Wrap the image in an anchor tag for Lightbox2
                preview.append(
                    '<a href="' + e.target.result + '" data-lightbox="zoom-images">' +
                    '<img src="' + e.target.result + '" class="uploaded-image">' +
                    '</a>'
                );
            };
            reader.readAsDataURL(this.files[i]);
        }
    }
});
        },

        // Function to display existing images when retrieving a form
        displayExistingImages: function(imageData) {
            if (imageData && imageData.zoom_image_url) {
    var preview = $('#zoom-image-preview');
    preview.empty(); // Clear previous previews

                var imageUrls = JSON.parse(imageData.zoom_image_url);
    imageUrls.forEach(function(url) {
        // Wrap the image in an anchor tag for Lightbox2
        preview.append(
            '<a href="' + url + '" data-lightbox="zoom-images">' +
            '<img src="' + url + '" class="uploaded-image">' +
            '</a>'
        );
    });
}
        }
    };

    /**
     * Initialize all components when document is ready
     */
    $(document).ready(function() {
        Modal.init();
        StudentSearch.init();
        StudentReports.init();
        
        // Handle displaying existing images if there's a response with existing report data
        if (typeof response !== 'undefined' && response.data && response.data.existing_report) {
            StudentReports.displayExistingImages(response.data.existing_report);
        }
    });

})(jQuery);



