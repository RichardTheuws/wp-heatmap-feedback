<?php
class WP_Heatmap_Feedback_Form_Admin {
    public function __construct() {
        add_action('init', array($this, 'register_feedback_form_post_type'));
        add_action('add_meta_boxes', array($this, 'add_form_fields_meta_box'));
        add_action('save_post', array($this, 'save_form_fields'));
    }

    public function register_feedback_form_post_type() {
        register_post_type('feedback_form', array(
            'labels' => array(
                'name' => 'Feedback Formulieren',
                'singular_name' => 'Feedback Formulier',
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title'),
        ));
    }

    public function add_form_fields_meta_box() {
        add_meta_box(
            'feedback_form_fields',
            'Formulier Velden',
            array($this, 'render_form_fields_meta_box'),
            'feedback_form',
            'normal',
            'default'
        );
    }

    public function render_form_fields_meta_box($post) {
        wp_nonce_field('save_form_fields', 'feedback_form_fields_nonce');

        $fields = get_post_meta($post->ID, '_feedback_form_fields', true);
        if (!is_array($fields)) {
            $fields = array();
        }

        echo '<div id="feedback_form_fields">';
        foreach ($fields as $index => $field) {
            $this->render_field_inputs($index, $field);
        }
        echo '</div>';

        echo '<button type="button" id="add_form_field">Veld Toevoegen</button>';

        ?>
        <script>
            jQuery(document).ready(function($) {
                var fieldIndex = <?php echo count($fields); ?>;

                $('#add_form_field').on('click', function() {
                    var newField = <?php echo json_encode($this->render_field_inputs('INDEX', array(), false)); ?>;
                    newField = newField.replace(/INDEX/g, fieldIndex);
                    $('#feedback_form_fields').append(newField);
                    fieldIndex++;
                });

                $('#feedback_form_fields').on('click', '.remove_field', function() {
                    $(this).closest('.field').remove();
                });
            });
        </script>
        <?php
    }

    private function render_field_inputs($index, $field = array(), $echo = true) {
        $output = '<div class="field">';
        $output .= '<p><label>Label: <input type="text" name="fields[' . $index . '][label]" value="' . (isset($field['label']) ? esc_attr($field['label']) : '') . '"></label></p>';
        $output .= '<p><label>Type: <select name="fields[' . $index . '][type]">';
        $types = array('text' => 'Tekst', 'textarea' => 'Tekstgebied', 'checkbox' => 'Checkbox');
        foreach ($types as $value => $label) {
            $output .= '<option value="' . $value . '"' . (isset($field['type']) && $field['type'] === $value ? ' selected' : '') . '>' . $label . '</option>';
        }
        $output .= '</select></label></p>';
        $output .= '<button type="button" class="remove_field">Verwijder Veld</button>';
        $output .= '</div>';

        if ($echo) {
            echo $output;
        }
        return $output;
    }

    public function save_form_fields($post_id) {
        if (!isset($_POST['feedback_form_fields_nonce']) || !wp_verify_nonce($_POST['feedback_form_fields_nonce'], 'save_form_fields')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            $fields = array_values($_POST['fields']); // Reset array keys
            update_post_meta($post_id, '_feedback_form_fields', $fields);
        } else {
            delete_post_meta($post_id, '_feedback_form_fields');
        }
    }
}