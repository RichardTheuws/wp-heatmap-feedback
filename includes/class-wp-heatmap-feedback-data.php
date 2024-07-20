<?php
class WP_Heatmap_Feedback_Data {
    public function __construct() {
        add_action('init', array($this, 'register_feedback_post_type'));
        add_action('admin_menu', array($this, 'add_feedback_menu'));
        add_action('manage_feedback_posts_custom_column', array($this, 'custom_feedback_column'), 10, 2);
        add_filter('manage_feedback_posts_columns', array($this, 'set_custom_feedback_columns'));
    }

    public function register_feedback_post_type() {
        register_post_type('feedback', array(
            'labels' => array(
                'name' => 'Feedback',
                'singular_name' => 'Feedback Item',
            ),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false,
            ),
            'map_meta_cap' => true,
            'supports' => array('title'),
        ));
    }

    public function add_feedback_menu() {
        add_submenu_page(
            'edit.php?post_type=feedback',
            'Feedback Overzicht',
            'Overzicht',
            'manage_options',
            'feedback-overview',
            array($this, 'render_feedback_overview')
        );
    }

    public function render_feedback_overview() {
        $feedback = $this->get_all_feedback();
        ?>
        <div class="wrap">
            <h1>Feedback Overzicht</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Formulier</th>
                        <th>Antwoorden</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedback as $item): ?>
                        <tr>
                            <td><?php echo get_the_date('', $item->ID); ?></td>
                            <td><?php echo get_the_title($item->post_parent); ?></td>
                            <td>
                                <?php
                                $meta = get_post_meta($item->ID);
                                foreach ($meta as $key => $value) {
                                    if ($key !== '_feedback_form_id') {
                                        echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value[0]) . '<br>';
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function get_all_feedback() {
        return get_posts(array(
            'post_type' => 'feedback',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
    }

    public function set_custom_feedback_columns($columns) {
        $columns = array(
            'cb' => $columns['cb'],
            'title' => __('Titel'),
            'form' => __('Formulier'),
            'answers' => __('Antwoorden'),
            'date' => __('Datum'),
        );
        return $columns;
    }

    public function custom_feedback_column($column, $post_id) {
        switch ($column) {
            case 'form':
                echo get_the_title(get_post_meta($post_id, '_feedback_form_id', true));
                break;
            case 'answers':
                $meta = get_post_meta($post_id);
                foreach ($meta as $key => $value) {
                    if ($key !== '_feedback_form_id') {
                        echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value[0]) . '<br>';
                    }
                }
                break;
        }
    }
}