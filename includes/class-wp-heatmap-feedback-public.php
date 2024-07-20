<?php
class WP_Heatmap_Feedback_Public {
    public function enqueue_styles() {
        wp_enqueue_style('wp-heatmap-feedback-public', WP_HEATMAP_FEEDBACK_URL . 'css/public.css', array(), '1.0.0', 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-heatmap-feedback-public', WP_HEATMAP_FEEDBACK_URL . 'js/public.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wp-heatmap-feedback-public', 'wpHeatmapFeedback', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-heatmap-feedback-nonce'),
        ));
    }

    public function render_forms() {
        if (is_singular('page')) {
            $post_id = get_the_ID();
            $form_id = get_post_meta($post_id, '_wp_heatmap_feedback_form_id', true);
            $display_type = get_post_meta($post_id, '_wp_heatmap_feedback_display_type', true);
            $display_delay = get_post_meta($post_id, '_wp_heatmap_feedback_display_delay', true);
            $scroll_percentage = get_post_meta($post_id, '_wp_heatmap_feedback_scroll_percentage', true);
            $desktop_position = get_post_meta($post_id, '_wp_heatmap_feedback_desktop_position', true);

            if ($form_id) {
                $form = new WP_Heatmap_Feedback_Form($form_id);
                $form_html = $form->render();

                echo '<div id="wp-heatmap-feedback-form" class="wp-heatmap-feedback-form" data-display-type="' . esc_attr($display_type) . '" data-display-delay="' . esc_attr($display_delay) . '" data-scroll-percentage="' . esc_attr($scroll_percentage) . '" data-desktop-position="' . esc_attr($desktop_position) . '">';
                echo $form_html;
                echo '</div>';
            }
        }
    }
}