<?php
class WP_Heatmap_Feedback_Heatmap {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_heatmap_menu'));
        add_action('admin_init', array($this, 'register_heatmap_settings'));
        add_action('wp_footer', array($this, 'maybe_add_heatmap_script'));
    }

    public function add_heatmap_menu() {
        add_menu_page(
            'Heatmap Settings', 
            'Heatmap', 
            'manage_options', 
            'heatmap-settings', 
            array($this, 'render_heatmap_settings_page'), 
            'dashicons-chart-area', 
            30
        );
        add_submenu_page(
            'heatmap-settings',
            'Heatmap Results',
            'Results',
            'manage_options',
            'heatmap-results',
            array($this, 'render_heatmap_results_page')
        );
    }

    public function register_heatmap_settings() {
        register_setting('heatmap_settings', 'heatmap_enabled_pages');
        register_setting('heatmap_settings', 'heatmap_enabled_post_types');
        register_setting('heatmap_settings', 'heatmap_enabled_taxonomies');
    }

    public function render_heatmap_settings_page() {
        // ... (bestaande code voor instellingen pagina)
    }

    public function render_heatmap_results_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'heatmap_data';

        $results = $wpdb->get_results("
            SELECT url, COUNT(*) as visit_count
            FROM $table_name
            WHERE type = 'pageview'
            GROUP BY url
            ORDER BY visit_count DESC
        ");

        ?>
        <div class="wrap">
            <h1>Heatmap Results</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
						<th>Page URL</th>
						                        <th>Visit Count</th>
						                        <th>Actions</th>
						                    </tr>
						                </thead>
						                <tbody>
						                    <?php foreach ($results as $row): ?>
						                        <tr>
						                            <td><?php echo esc_html($row->url); ?></td>
						                            <td><?php echo esc_html($row->visit_count); ?></td>
						                            <td>
						                                <a href="<?php echo admin_url('admin.php?page=heatmap-results&view=details&url=' . urlencode($row->url)); ?>">
						                                    View Details
						                                </a>
						                            </td>
						                        </tr>
						                    <?php endforeach; ?>
						                </tbody>
						            </table>
						        </div>
						        <?php

						        if (isset($_GET['view']) && $_GET['view'] === 'details' && isset($_GET['url'])) {
						            $this->render_heatmap_details($_GET['url']);
						        }
						    }

						    public function render_heatmap_details($url) {
						        global $wpdb;
						        $table_name = $wpdb->prefix . 'heatmap_data';

						        $clicks = $wpdb->get_results($wpdb->prepare("
						            SELECT x, y, COUNT(*) as count
						            FROM $table_name
						            WHERE url = %s AND type = 'click'
						            GROUP BY x, y
						        ", $url));

						        $scroll_depths = $wpdb->get_results($wpdb->prepare("
						            SELECT y, COUNT(*) as count
						            FROM $table_name
						            WHERE url = %s AND type = 'scroll'
						            GROUP BY y
						            ORDER BY y DESC
						        ", $url));

						        $max_clicks = $wpdb->get_var($wpdb->prepare("
						            SELECT MAX(click_count) FROM (
						                SELECT COUNT(*) as click_count
						                FROM $table_name
						                WHERE url = %s AND type = 'click'
						                GROUP BY x, y
						            ) as click_counts
						        ", $url));

						        ?>
						        <h2>Heatmap Details for <?php echo esc_html($url); ?></h2>
						        <div id="heatmap-container" style="position: relative; width: 100%; height: 600px; border: 1px solid #ccc; overflow: hidden;">
						            <iframe src="<?php echo esc_url($url); ?>" style="width: 100%; height: 100%; border: none;"></iframe>
						            <div id="heatmap-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
						        </div>
						        <h3>Scroll Depths</h3>
						        <div id="scroll-depth-container" style="width: 100%; height: 20px; background: linear-gradient(to right, red, orange, yellow, green);"></div>
						        <script src="https://cdnjs.cloudflare.com/ajax/libs/heatmap.js/2.0.5/heatmap.min.js"></script>
						        <script>
						        (function() {
						            var heatmapInstance = h337.create({
						                container: document.querySelector('#heatmap-overlay'),
						                radius: 20,
						                maxOpacity: 0.5,
						                minOpacity: 0,
						                blur: .75
						            });

						            var points = <?php echo json_encode($clicks); ?>;
						            var max = <?php echo $max_clicks; ?>;
						            var data = {
						                max: max,
						                data: points.map(function(point) {
						                    return {
						                        x: point.x,
						                        y: point.y,
						                        value: point.count
						                    };
						                })
						            };

						            heatmapInstance.setData(data);

						            // Add click count labels
						            var overlay = document.getElementById('heatmap-overlay');
						            points.forEach(function(point) {
						                var label = document.createElement('div');
						                label.style.position = 'absolute';
						                label.style.left = point.x + 'px';
						                label.style.top = point.y + 'px';
						                label.style.background = 'rgba(0,0,0,0.5)';
						                label.style.color = 'white';
						                label.style.padding = '2px 5px';
						                label.style.borderRadius = '3px';
						                label.style.fontSize = '12px';
						                label.textContent = point.count;
						                overlay.appendChild(label);
						            });

						            // Render scroll depth
						            var scrollDepths = <?php echo json_encode($scroll_depths); ?>;
						            var maxScrollDepth = Math.max.apply(Math, scrollDepths.map(function(depth) { return depth.y; }));
						            var scrollContainer = document.getElementById('scroll-depth-container');
						            scrollDepths.forEach(function(depth) {
						                var marker = document.createElement('div');
						                marker.style.position = 'absolute';
						                marker.style.left = (depth.y / maxScrollDepth * 100) + '%';
						                marker.style.top = '0';
						                marker.style.width = '2px';
						                marker.style.height = '100%';
						                marker.style.background = 'rgba(0,0,0,0.5)';
						                scrollContainer.appendChild(marker);
						            });
						        })();
						        </script>
						        <?php
						    }

						    public function maybe_add_heatmap_script() {
						        $enabled_pages = array_map('intval', explode(',', get_option('heatmap_enabled_pages', '')));
						        $enabled_post_types = get_option('heatmap_enabled_post_types', array());
						        $enabled_taxonomies = get_option('heatmap_enabled_taxonomies', array());

						        $should_add_script = false;

						        if (is_singular() && in_array(get_the_ID(), $enabled_pages)) {
						            $should_add_script = true;
						        } elseif (is_singular($enabled_post_types)) {
						            $should_add_script = true;
						        } elseif (is_tax($enabled_taxonomies)) {
						            $should_add_script = true;
						        }

						        if ($should_add_script) {
						            ?>
						            <script>
						            (function() {
						                var sendHeatmapData = function(type, x, y) {
						                    var data = {
						                        action: 'record_heatmap_data',
						                        nonce: wpHeatmapFeedback.nonce,
						                        url: window.location.href,
						                        type: type,
						                        x: x,
						                        y: y
						                    };

						                    jQuery.post(wpHeatmapFeedback.ajax_url, data);
						                };

						                // Record pageview
						                sendHeatmapData('pageview', 0, 0);

						                // Record clicks
						                document.addEventListener('click', function(e) {
						                    sendHeatmapData('click', e.pageX, e.pageY);
						                });

						                // Record scroll depth
						                var maxScrollDepth = 0;
						                window.addEventListener('scroll', function() {
						                    var scrollDepth = window.pageYOffset + window.innerHeight;
						                    if (scrollDepth > maxScrollDepth) {
						                        maxScrollDepth = scrollDepth;
						                        sendHeatmapData('scroll', 0, maxScrollDepth);
						                    }
						                });
						            })();
						            </script>
						            <?php
						        }
						    }
						}