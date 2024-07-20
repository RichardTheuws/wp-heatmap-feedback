<?php
class WP_Heatmap_Feedback {
    private $form;
    private $ajax;
    private $heatmap;

    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
		add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
    }

    private function load_dependencies() {
        $this->form = new WP_Heatmap_Feedback_Form();
        $this->ajax = new WP_Heatmap_Feedback_Ajax();
        $this->heatmap = new WP_Heatmap_Feedback_Heatmap();
    }

    private function define_admin_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    private function define_public_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
    }

    public function enqueue_admin_styles() {
        wp_enqueue_style('wp-heatmap-feedback-admin', WP_HEATMAP_FEEDBACK_URL . 'css/admin.css', array(), '1.0.0', 'all');
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('wp-heatmap-feedback-admin', WP_HEATMAP_FEEDBACK_URL . 'js/admin.js', array('jquery'), '1.0.0', true);
    }

    public function enqueue_public_styles() {
        wp_enqueue_style('wp-heatmap-feedback-public', WP_HEATMAP_FEEDBACK_URL . 'css/public.css', array(), '1.0.0', 'all');
    }

	public function enqueue_public_scripts() {
	    wp_enqueue_script('wp-heatmap-feedback-public', WP_HEATMAP_FEEDBACK_URL . 'js/public.js', array('jquery'), '1.0.0', false);
	    wp_localize_script('wp-heatmap-feedback-public', 'wpHeatmapFeedback', array(
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'nonce' => wp_create_nonce('submit_feedback_form')
	    ));
	}

    public function run() {
        // Deze methode kan leeg blijven of worden gebruikt voor extra initialisatie
    }
}