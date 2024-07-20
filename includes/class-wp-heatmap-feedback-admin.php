<?php
class WP_Heatmap_Feedback_Admin {
    public function enqueue_styles() {
        wp_enqueue_style('wp-heatmap-feedback-admin', WP_HEATMAP_FEEDBACK_URL . 'css/admin.css', array(), '1.0.0', 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-heatmap-feedback-admin', WP_HEATMAP_FEEDBACK_URL . 'js/admin.js', array('jquery'), '1.0.0', true);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'wp_heatmap_feedback_form',
            'Feedback Formulier',
            array($this, 'render_meta_box'),
            'page',
            'side',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('wp_heatmap_feedback_meta_box', 'wp_heatmap_feedback_meta_box_nonce');

        $form_id = get_post_meta($post->ID, '_wp_heatmap_feedback_form_id', true);
        $display_type = get_post_meta($post->ID, '_wp_heatmap_feedback_display_type', true);
        $display_delay = get_post_meta($post->ID, '_wp_heatmap_feedback_display_delay', true);
        $scroll_percentage = get_post_meta($post->ID, '_wp_heatmap_feedback_scroll_percentage', true);
        $desktop_position = get_post_meta($post->ID, '_wp_heatmap_feedback_desktop_position', true);

        $forms = get_posts(array('post_type' => 'feedback_form', 'numberposts' => -1));

        echo '<p><label for="wp_heatmap_feedback_form_id">Selecteer Formulier:</label> ';
        echo '<select id="wp_heatmap_feedback_form_id" name="wp_heatmap_feedback_form_id">';
        echo '<option value="">Selecteer een formulier</option>';
        foreach ($forms as $form) {
            echo '<option value="' . esc_attr($form->ID) . '" ' . selected($form_id, $form->ID, false) . '>' . esc_html($form->post_title) . '</option>';
        }
        echo '</select></p>';

        echo '<p><label for="wp_heatmap_feedback_display_type">Weergave Type:</label> ';
        echo '<select id="wp_heatmap_feedback_display_type" name="wp_heatmap_feedback_display_type">';
        echo '<option value="immediate" ' . selected($display_type, 'immediate', false) . '>Direct</option>';
        echo '<option value="delay" ' . selected($display_type, 'delay', false) . '>Na vertraging</option>';
        echo '<option value="scroll" ' . selected($display_type, 'scroll', false) . '>Na scrollen</option>';
        echo '<option value="exit" ' . selected($display_type, 'exit', false) . '>Bij exit intent</option>';
        echo '</select></p>';

        echo '<p><label for="wp_heatmap_feedback_display_delay">Vertraging (seconden):</label> ';
        echo '<input type="number" id="wp_heatmap_feedback_display_delay" name="wp_heatmap_feedback_display_delay" value="' . esc_attr($display_delay) . '" min="0"></p>';

        echo '<p><label for="wp_heatmap_feedback_scroll_percentage">Scroll Percentage:</label> ';
        echo '<input type="number" id="wp_heatmap_feedback_scroll_percentage" name="wp_heatmap_feedback_scroll_percentage" value="' . esc_attr($scroll_percentage) . '" min="0" max="100"></p>';

        echo '<p><label for="wp_heatmap_feedback_desktop_position">Desktop Positie:</label> ';
        echo '<select id="wp_heatmap_feedback_desktop_position" name="wp_heatmap_feedback_desktop_position">';
        echo '<option value="bottom-right" ' . selected($desktop_position, 'bottom-right', false) . '>Rechtsonder</option>';
        echo '<option value="popup" ' . selected($desktop_position, 'popup', false) . '>Popup</option>';
        echo '</select></p>';
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['wp_heatmap_feedback_meta_box_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['wp_heatmap_feedback_meta_box_nonce'], 'wp_heatmap_feedback_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $form_id = isset($_POST['wp_heatmap_feedback_form_id']) ? sanitize_text_field($_POST['wp_heatmap_feedback_form_id']) : '';
        $display_type = isset($_POST['wp_heatmap_feedback_display_type']) ? sanitize_text_field($_POST['wp_heatmap_feedback_display_type']) : '';
        $display_delay = isset($_POST['wp_heatmap_feedback_display_delay']) ? absint($_POST['wp_heatmap_feedback_display_delay']) : 0;
        $scroll_percentage = isset($_POST['wp_heatmap_feedback_scroll_percentage']) ? absint($_POST['wp_heatmap_feedback_scroll_percentage']) : 0;
        $desktop_position = isset($_POST['wp_heatmap_feedback_desktop_position']) ? sanitize_text_field($_POST['wp_heatmap_feedback_desktop_position']) : '';

        update_post_meta($post_id, '_wp_heatmap_feedback_form_id', $form_id);
        update_post_meta($post_id, '_wp_heatmap_feedback_display_type', $display_type);
        update_post_meta($post_id, '_wp_heatmap_feedback_display_delay', $display_delay);
        update_post_meta($post_id, '_wp_heatmap_feedback_scroll_percentage', $scroll_percentage);
        update_post_meta($post_id, '_wp_heatmap_feedback_desktop_position', $desktop_position);
    }
}