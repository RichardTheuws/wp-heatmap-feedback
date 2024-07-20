<?php
class WP_Heatmap_Feedback_Ajax {
    public function __construct() {
        add_action('wp_ajax_submit_feedback', array($this, 'handle_submit_feedback'));
        add_action('wp_ajax_nopriv_submit_feedback', array($this, 'handle_submit_feedback'));
        add_action('wp_ajax_record_heatmap_data', array($this, 'record_heatmap_data'));
        add_action('wp_ajax_nopriv_record_heatmap_data', array($this, 'record_heatmap_data'));
    }

    public function handle_submit_feedback() {
        if (!check_ajax_referer('submit_feedback_form', 'feedback_form_nonce', false)) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        // Log de ontvangen gegevens
        error_log('Received feedback data: ' . print_r($_POST, true));

        $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
        if (!$form_id) {
            wp_send_json_error('Invalid form ID');
        }

        $fields = get_post_meta($form_id, '_feedback_form_fields', true);
        if (!is_array($fields)) {
            wp_send_json_error('Invalid form structure');
        }

        $feedback_data = array();
        foreach ($fields as $field) {
            $field_name = sanitize_key($field['label']);
            if (isset($_POST[$field_name])) {
                switch ($field['type']) {
                    case 'text':
                        $feedback_data[$field_name] = sanitize_text_field($_POST[$field_name]);
                        break;
                    case 'textarea':
                        $feedback_data[$field_name] = sanitize_textarea_field($_POST[$field_name]);
                        break;
                    case 'checkbox':
                        $feedback_data[$field_name] = isset($_POST[$field_name]) ? 1 : 0;
                        break;
                }
            }
        }

        $feedback_data['contact_permission'] = sanitize_text_field($_POST['contact_permission']);
        if ($_POST['contact_permission'] === 'yes') {
            $feedback_data['contact_name'] = sanitize_text_field($_POST['contact_name']);
            $feedback_data['contact_email'] = sanitize_email($_POST['contact_email']);
            $feedback_data['contact_phone'] = sanitize_text_field($_POST['contact_phone']);
        }

        $feedback_id = wp_insert_post(array(
            'post_type' => 'feedback',
            'post_status' => 'publish',
            'post_title' => 'Feedback ' . date('Y-m-d H:i:s'),
        ));

        if (is_wp_error($feedback_id)) {
            wp_send_json_error('Failed to save feedback: ' . $feedback_id->get_error_message());
        }

        foreach ($feedback_data as $key => $value) {
            update_post_meta($feedback_id, $key, $value);
        }

        // Send email notification
        $notification_email = get_option('feedback_notification_email');
        if ($notification_email) {
            $subject = 'Nieuwe feedback ontvangen';
            $message = "Er is nieuwe feedback ontvangen:\n\n";
            foreach ($feedback_data as $key => $value) {
                $message .= ucfirst($key) . ": " . $value . "\n";
            }
            wp_mail($notification_email, $subject, $message);
        }

        wp_send_json_success('Feedback submitted successfully');
    }

    public function record_heatmap_data() {
        check_ajax_referer('wp-heatmap-feedback-nonce', 'nonce');

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $x = isset($_POST['x']) ? intval($_POST['x']) : 0;
        $y = isset($_POST['y']) ? intval($_POST['y']) : 0;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        global $wpdb;
        $table_name = $wpdb->prefix . 'heatmap_data';

        $result = $wpdb->insert(
            $table_name,
            array(
                'url' => $url,
                'x' => $x,
                'y' => $y,
                'type' => $type,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            wp_send_json_error('Failed to record heatmap data');
        } else {
            wp_send_json_success('Heatmap data recorded successfully');
        }
    }
}