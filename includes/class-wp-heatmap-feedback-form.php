<?php
class WP_Heatmap_Feedback_Form {
    public function __construct() {
        add_action('init', array($this, 'register_feedback_form_post_type'));
        add_action('add_meta_boxes', array($this, 'add_form_fields_meta_box'));
        add_action('add_meta_boxes', array($this, 'add_form_display_options_meta_box'));
        add_action('save_post', array($this, 'save_form_fields'));
        add_action('save_post', array($this, 'save_form_display_options'));
        add_action('wp_footer', array($this, 'maybe_display_form'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

	public function register_feedback_form_post_type() {
	    $capabilities = array(
	        'edit_post'          => 'edit_feedback_form',
	        'read_post'          => 'read_feedback_form',
	        'delete_post'        => 'delete_feedback_form',
	        'edit_posts'         => 'edit_feedback_forms',
	        'edit_others_posts'  => 'edit_others_feedback_forms',
	        'publish_posts'      => 'publish_feedback_forms',
	        'read_private_posts' => 'read_private_feedback_forms',
	    );

	    register_post_type('feedback_form', array(
	        'labels' => array(
	            'name' => 'Feedback Formulieren',
	            'singular_name' => 'Feedback Formulier',
	        ),
	        'public' => false,
	        'show_ui' => true,
	        'supports' => array('title'),
	        'menu_icon' => 'dashicons-feedback',
	        'capability_type' => 'feedback_form',
	        'capabilities' => $capabilities,
	        'map_meta_cap' => true,
	    ));
	}
	public static function activate() {
	    // Andere activatie code...

	    // Ken capabilities toe aan de beheerder
	    $role = get_role('administrator');
	    $capabilities = array(
	        'edit_feedback_form',
	        'read_feedback_form',
	        'delete_feedback_form',
	        'edit_feedback_forms',
	        'edit_others_feedback_forms',
	        'publish_feedback_forms',
	        'read_private_feedback_forms',
	    );

	    foreach ($capabilities as $cap) {
	        $role->add_cap($cap);
	    }
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

    public function add_form_display_options_meta_box() {
        add_meta_box(
            'feedback_form_display_options',
            'Weergave Opties',
            array($this, 'render_form_display_options_meta_box'),
            'feedback_form',
            'normal',
            'default'
        );
    }

    public function render_form_display_options_meta_box($post) {
        wp_nonce_field('save_form_display_options', 'feedback_form_display_options_nonce');

        $display_type = get_post_meta($post->ID, '_display_type', true);
        $display_delay = get_post_meta($post->ID, '_display_delay', true);
        $scroll_percentage = get_post_meta($post->ID, '_scroll_percentage', true);
        $specific_pages = get_post_meta($post->ID, '_specific_pages', true);
        $post_types = get_post_meta($post->ID, '_post_types', true);
        $taxonomies = get_post_meta($post->ID, '_taxonomies', true);

        ?>
        <p>
            <label for="display_type">Weergave Type:</label>
            <select name="display_type" id="display_type">
                <option value="immediate" <?php selected($display_type, 'immediate'); ?>>Direct</option>
                <option value="delay" <?php selected($display_type, 'delay'); ?>>Na vertraging</option>
                <option value="scroll" <?php selected($display_type, 'scroll'); ?>>Na scrollen</option>
                <option value="exit" <?php selected($display_type, 'exit'); ?>>Bij exit intent</option>
            </select>
        </p>
        <p>
            <label for="display_delay">Vertraging (seconden):</label>
            <input type="number" name="display_delay" id="display_delay" value="<?php echo esc_attr($display_delay); ?>">
        </p>
        <p>
            <label for="scroll_percentage">Scroll Percentage:</label>
            <input type="number" name="scroll_percentage" id="scroll_percentage" value="<?php echo esc_attr($scroll_percentage); ?>">
        </p>
        <p>
            <label for="specific_pages">Specifieke Pagina's (komma-gescheiden IDs):</label>
            <input type="text" name="specific_pages" id="specific_pages" value="<?php echo esc_attr($specific_pages); ?>">
        </p>
        <p>
            <label>Post Types:</label><br>
            <?php
            $all_post_types = get_post_types(array('public' => true), 'objects');
            foreach ($all_post_types as $pt) {
                echo '<label>';
                echo '<input type="checkbox" name="post_types[]" value="' . esc_attr($pt->name) . '" ' . checked(in_array($pt->name, (array)$post_types), true, false) . '>';
                echo esc_html($pt->label);
                echo '</label><br>';
            }
            ?>
        </p>
        <p>
            <label>Taxonomieën:</label><br>
            <?php
            $all_taxonomies = get_taxonomies(array('public' => true), 'objects');
            foreach ($all_taxonomies as $tax) {
                echo '<label>';
                echo '<input type="checkbox" name="taxonomies[]" value="' . esc_attr($tax->name) . '" ' . checked(in_array($tax->name, (array)$taxonomies), true, false) . '>';
                echo esc_html($tax->label);
                echo '</label><br>';
            }
            ?>
        </p>
        <?php
    }

    public function save_form_display_options($post_id) {
        if (!isset($_POST['feedback_form_display_options_nonce']) || !wp_verify_nonce($_POST['feedback_form_display_options_nonce'], 'save_form_display_options')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'display_type',
            'display_delay',
            'scroll_percentage',
            'specific_pages',
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        $post_types = isset($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : array();
        update_post_meta($post_id, '_post_types', $post_types);

        $taxonomies = isset($_POST['taxonomies']) ? array_map('sanitize_text_field', $_POST['taxonomies']) : array();
        update_post_meta($post_id, '_taxonomies', $taxonomies);
    }

	public function maybe_display_form() {
	    $forms = get_posts(array(
	        'post_type' => 'feedback_form',
	        'posts_per_page' => -1,
	    ));
    
	    error_log('Number of feedback forms: ' . count($forms));

	    foreach ($forms as $form) {
	        error_log('Checking form: ' . $form->ID);
	        if ($this->should_display_form($form->ID)) {
	            error_log('Displaying form: ' . $form->ID);
	            echo $this->render_form($form->ID);
	            break;
	        }
	    }
	}

    private function should_display_form($form_id) {
        $specific_pages = get_post_meta($form_id, '_specific_pages', true);
        $post_types = get_post_meta($form_id, '_post_types', true);
        $taxonomies = get_post_meta($form_id, '_taxonomies', true);

        // Check specific pages
        if (!empty($specific_pages)) {
            $page_ids = array_map('intval', explode(',', $specific_pages));
            if (is_singular() && in_array(get_the_ID(), $page_ids)) {
                return true;
            }
        }

        // Check post types
        if (!empty($post_types) && is_singular($post_types)) {
            return true;
        }

        // Check taxonomies
        if (!empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                if (is_tax($taxonomy)) {
                    return true;
                }
            }
        }

        return false;
    }

	public function render_form($form_id) {
	        $fields = get_post_meta($form_id, '_feedback_form_fields', true);
	        if (!is_array($fields)) {
	            return '';
	        }

	        $display_type = get_post_meta($form_id, '_display_type', true);
	        $display_delay = get_post_meta($form_id, '_display_delay', true);
	        $scroll_percentage = get_post_meta($form_id, '_scroll_percentage', true);

	        ob_start();
	        ?>
	        <div id="feedback-form-<?php echo $form_id; ?>" class="feedback-form" style="display: none; position: fixed; bottom: 20px; right: 20px; width: 300px; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);" 
	             data-display-type="<?php echo esc_attr($display_type); ?>"
	             data-display-delay="<?php echo esc_attr($display_delay); ?>"
	             data-scroll-percentage="<?php echo esc_attr($scroll_percentage); ?>">
	            <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
					<?php wp_nonce_field('submit_feedback_form', 'feedback_form_nonce'); ?>
					<input type="hidden" name="action" value="submit_feedback">
					<input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
	                <?php foreach ($fields as $field): ?>
	                    <div class="form-field">
	                        <label><?php echo esc_html($field['label']); ?></label>
	                        <?php 
	                        $field_name = sanitize_key($field['label']);
	                        if ($field['type'] === 'textarea'): ?>
	                            <textarea name="<?php echo esc_attr($field_name); ?>"></textarea>
	                        <?php elseif ($field['type'] === 'checkbox'): ?>
	                            <input type="checkbox" name="<?php echo esc_attr($field_name); ?>">
	                        <?php else: ?>
	                            <input type="text" name="<?php echo esc_attr($field_name); ?>">
	                        <?php endif; ?>
	                    </div>
	                <?php endforeach; ?>
	                <div class="form-field">
	                    <label>Mogen we contact met u opnemen?</label>
	                    <select name="contact_permission" id="contact_permission">
	                        <option value="no">Nee</option>
	                        <option value="yes">Ja</option>
	                    </select>
	                </div>
	                <div id="contact_details" style="display: none;">
	                    <div class="form-field">
	                        <label>Naam</label>
	                        <input type="text" name="contact_name">
	                    </div>
	                    <div class="form-field">
	                        <label>E-mailadres</label>
	                        <input type="email" name="contact_email">
	                    </div>
	                    <div class="form-field">
	                        <label>Telefoonnummer</label>
	                        <input type="tel" name="contact_phone">
	                    </div>
	                </div>
	                <input type="submit" value="Verstuur Feedback">
	            </form>
	        </div>
	        <script>
	        jQuery(document).ready(function($) {
	            var $form = $('#feedback-form-<?php echo $form_id; ?>');
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
	                    $(window).scroll(function() {
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

	            $('#contact_permission').change(function() {
	                if ($(this).val() === 'yes') {
	                    $('#contact_details').show();
	                } else {
	                    $('#contact_details').hide();
	                }
	            });

	            $form.find('form').on('submit', function(e) {
	                e.preventDefault();
	                var formData = $(this).serialize();
	                $.ajax({
	                    url: $(this).attr('action'),
	                    type: 'POST',
	                    data: formData,
	                    success: function(response) {
	                        if (response.success) {
	                            $form.html('<p>Bedankt voor uw feedback!</p>');
	                        } else {
	                            alert('Er is een fout opgetreden bij het versturen van uw feedback. Probeer het later opnieuw.');
	                        }
	                    },
	                    error: function() {
	                        alert('Er is een fout opgetreden bij het versturen van uw feedback. Probeer het later opnieuw.');
	                    }
	                });
	            });
	        });
	        </script>
	        <?php
	        return ob_get_clean();
	    }
		
		public function add_settings_page() {
		        add_submenu_page(
		            'edit.php?post_type=feedback_form',
		            'Feedback Instellingen',
		            'Instellingen',
		            'manage_options',
		            'feedback-settings',
		            array($this, 'render_settings_page')
		        );
		    }

		    public function register_settings() {
		        register_setting('feedback_settings', 'feedback_notification_email');
		    }

		    public function render_settings_page() {
		        ?>
		        <div class="wrap">
		            <h1>Feedback Instellingen</h1>
		            <form method="post" action="options.php">
		                <?php
		                settings_fields('feedback_settings');
		                do_settings_sections('feedback_settings');
		                ?>
		                <table class="form-table">
		                    <tr valign="top">
		                        <th scope="row">Notificatie E-mail</th>
		                        <td>
		                            <input type="email" name="feedback_notification_email" value="<?php echo esc_attr(get_option('feedback_notification_email')); ?>" />
		                            <p class="description">E-mailadres voor het ontvangen van nieuwe feedback meldingen.</p>
		                        </td>
		                    </tr>
		                </table>
		                <?php submit_button(); ?>
		            </form>
		        </div>
		        <?php
		    }
		}