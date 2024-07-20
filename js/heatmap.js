(function($) {
    $(document).ready(function() {
        $('body').on('click', function(e) {
            var data = {
                action: 'record_heatmap_click',
                x: e.pageX,
                y: e.pageY,
                url: window.location.href,
                nonce: heatmapVars.nonce
            };

            $.post(heatmapVars.ajaxurl, data, function(response) {
                console.log('Click recorded');
            });
        });
    });
})(jQuery);