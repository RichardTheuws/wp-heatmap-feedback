(function($) {
    'use strict';

    $(document).ready(function() {
        // Feedback form submission
        $('.feedback-form form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var formData = $form.serialize();

            $.ajax({
                url: wpHeatmapFeedback.ajax_url,
                type: 'POST',
                data: formData + '&action=submit_feedback&feedback_form_nonce=' + wpHeatmapFeedback.nonce,
                success: function(response) {
                    if (response.success) {
                        $form.html('<p>Bedankt voor uw feedback!</p>');
                    } else {
                        console.error('Error:', response.data);
                        alert('Er is een fout opgetreden bij het versturen van uw feedback: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    alert('Er is een fout opgetreden bij het versturen van uw feedback. Probeer het later opnieuw.');
                }
            });
        });

        // Show/hide contact details based on permission
        $('#contact_permission').change(function() {
            if ($(this).val() === 'yes') {
                $('#contact_details').show();
            } else {
                $('#contact_details').hide();
            }
        });

        // Heatmap functionality
        var sendHeatmapData = function(type, x, y) {
            var data = {
                action: 'record_heatmap_data',
                security: wpHeatmapFeedback.nonce,
                url: window.location.href,
                type: type,
                x: x,
                y: y
            };

            $.post(wpHeatmapFeedback.ajax_url, data, function(response) {
                if (!response.success) {
                    console.error('Failed to record heatmap data:', response.data);
                }
            });
        };

        // Record pageview
        sendHeatmapData('pageview', 0, 0);

        // Record clicks
        $(document).on('click', function(e) {
            sendHeatmapData('click', e.pageX, e.pageY);
        });

        // Record scroll depth
        var maxScrollDepth = 0;
        $(window).on('scroll', function() {
            var scrollDepth = $(window).scrollTop() + $(window).height();
            if (scrollDepth > maxScrollDepth) {
                maxScrollDepth = scrollDepth;
                sendHeatmapData('scroll', 0, maxScrollDepth);
            }
        });

        // Feedback form display logic
        $('.feedback-form').each(function() {
            var $form = $(this);
            var displayType = $form.data('display-type');
            var displayDelay = $form.data('display-delay');
            var scrollPercentage = $form.data('scroll-percentage');

            function showForm() {
                $form.show();
            }

            switch (displayType) {
                case 'immediate':
                    showForm();
                    break;
                case 'delay':
                    setTimeout(showForm, displayDelay * 1000);
                    break;
                case 'scroll':
                    $(window).on('scroll', function() {
                        var scrolled = $(window).scrollTop();
                        var docHeight = $(document).height();
                        var winHeight = $(window).height();
                        var scrollPercent = (scrolled / (docHeight - winHeight)) * 100;
                        if (scrollPercent > scrollPercentage) {
                            showForm();
                            $(window).off('scroll');
                        }
                    });
                    break;
                case 'exit':
                    $(document).on('mouseleave', function(e) {
                        if (e.clientY < 0) {
                            showForm();
                            $(document).off('mouseleave');
                        }
                    });
                    break;
            }
        });
    });
})(jQuery);
