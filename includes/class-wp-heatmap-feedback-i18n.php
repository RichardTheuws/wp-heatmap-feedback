
<?php

class WP_Heatmap_Feedback_i18n {

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wp-heatmap-feedback',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}
?>
