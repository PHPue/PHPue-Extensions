<?php
/**
 * Plugin Name: PHPue Elementor Widget
 * Plugin URI: https://phpue.co.uk/ue-extensions
 * Documentation URI: https://phpue.co.uk/docs
 * Description: Write PHP, HTML, and JavaScript in a single Elementor widget with PHPue.
 * Version: 1.0.0
 * Author: Edward Patch
 * Author URI: https://phpue.co.uk/ue-insights
 * Text Domain: phpue-widget
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('PHPUE_WIDGET_VERSION')) {
    define('PHPUE_WIDGET_VERSION', '1.0.0');
}
if (!defined('PHPUE_WIDGET_PATH')) {
    define('PHPUE_WIDGET_PATH', plugin_dir_path(__FILE__));
}
if (!defined('PHPUE_WIDGET_URL')) {
    define('PHPUE_WIDGET_URL', plugin_dir_url(__FILE__));
}

require_once PHPUE_WIDGET_PATH . 'class-phpue-compiler.php';
require_once PHPUE_WIDGET_PATH . 'class-phpue-ajax.php';

// ============================================================
// REGISTER AJAX FUNCTIONS FROM DATABASE
// ============================================================
add_action('init', function() {
    $ajax_functions = get_option('phpue_ajax_functions', []);
    
    foreach ($ajax_functions as $function_name => $function_data) {
        $code = $function_data['code'] ?? '';
        if (empty($code)) continue;
        
        if (!function_exists($function_name)) {
            try {
                eval($code);
            } catch (ParseError $e) {
                continue;
            }
        }
        
        if (!has_action('wp_ajax_' . $function_name)) {
            add_action('wp_ajax_' . $function_name, $function_name);
            add_action('wp_ajax_nopriv_' . $function_name, $function_name);
        }
    }
}, 1);

// ============================================================
// REGISTER WIDGET
// ============================================================
add_action('elementor/widgets/register', function($widgets_manager) {
    if (!class_exists('PHPue_Elementor_Widget')) {
        require_once PHPUE_WIDGET_PATH . 'class-phpue-widget.php';
    }
    $widgets_manager->register(new PHPue_Elementor_Widget());
});

add_action('elementor/init', function() {
    if (!class_exists('PHPue_Elementor_Widget')) {
        require_once PHPUE_WIDGET_PATH . 'class-phpue-widget.php';
    }
});

add_action('elementor/elements/categories_registered', function($elements_manager) {
    $elements_manager->add_category(
        'phpue',
        [
            'title' => __('PHPue Components', 'phpue-widget'),
            'icon' => 'fa fa-code',
        ]
    );
});

// ============================================================
// ENHANCED SYNTAX HIGHLIGHTING FOR ELEMENTOR EDITOR
// ============================================================
add_action('elementor/editor/after_enqueue_scripts', function() {
    ?>
    <style>
        /* Better PHP syntax highlighting in Elementor code editor */
        .elementor-control-phpue_script .CodeMirror,
        .elementor-control-phpue_template .CodeMirror,
        .elementor-control-phpue_cscript .CodeMirror {
            font-size: 13px;
            line-height: 1.6;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
            height: auto;
            min-height: 200px;
        }
        
        /* Highlight @AJAX annotations */
        .cm-phpue-ajax {
            color: #e74c3c !important;
            font-weight: bold !important;
            background: rgba(231, 76, 60, 0.1);
            padding: 0 4px;
            border-radius: 3px;
        }
        
        /* Highlight {{ $var }} in template and cscript */
        .cm-phpue-var {
            color: #2980b9 !important;
            font-weight: bold !important;
            background: rgba(41, 128, 185, 0.1);
            padding: 0 4px;
            border-radius: 3px;
        }
        
        /* Highlight p-directives */
        .cm-phpue-directive {
            color: #8e44ad !important;
            font-weight: bold !important;
        }
        
        /* PHP variables */
        .cm-variable-php {
            color: #e67e22 !important;
        }
        
        /* PHP keywords */
        .cm-keyword-php {
            color: #2c3e50 !important;
            font-weight: bold !important;
        }
        
        /* Strings */
        .cm-string-php {
            color: #27ae60 !important;
        }
        
        /* Comments */
        .cm-comment-php {
            color: #7f8c8d !important;
            font-style: italic !important;
        }
        
        /* HTML tags */
        .cm-tag-html {
            color: #e74c3c !important;
        }
        
        /* HTML attributes */
        .cm-attribute-html {
            color: #2980b9 !important;
        }
        
        /* JavaScript */
        .cm-keyword-js {
            color: #2c3e50 !important;
            font-weight: bold !important;
        }
        
        .cm-string-js {
            color: #27ae60 !important;
        }
        
        .cm-number-js {
            color: #8e44ad !important;
        }
        
        .cm-comment-js {
            color: #7f8c8d !important;
            font-style: italic !important;
        }

        /* PHPue widget icon in Elementor panel */
        .elementor-element-phpue_widget .elementor-element-title {
            color: #6C2BD9;
        }
        .elementor-element-phpue_widget .elementor-element-title:after {
            content: "⚡";
            margin-left: 5px;
        }
    </style>
    <?php
});

// ============================================================
// ADMIN SUBMISSIONS PAGE
// ============================================================
add_action('admin_menu', function() {
    add_submenu_page(
        'elementor',
        'PHPue Submissions',
        'PHPue Leads',
        'manage_options',
        'phpue-submissions',
        'phpue_render_submissions_page'
    );
});

function phpue_render_submissions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'phpue_submissions';
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    ?>
    <div class="wrap">
        <h1>📬 PHPue Form Submissions</h1>
        
        <?php if (!$table_exists): ?>
            <div class="notice notice-warning">
                <p>No submissions yet. Submit a form to create the database table.</p>
            </div>
        <?php else: ?>
            <?php
            $submissions = $wpdb->get_results("
                SELECT * FROM $table_name 
                ORDER BY created_at DESC 
                LIMIT 100
            ");
            
            if (empty($submissions)): 
            ?>
                <p>No submissions yet.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td><?php echo esc_html($sub->id); ?></td>
                                <td><strong><?php echo esc_html($sub->name); ?></strong></td>
                                <td><a href="mailto:<?php echo esc_attr($sub->email); ?>"><?php echo esc_html($sub->email); ?></a></td>
                                <td><?php echo esc_html(substr($sub->message, 0, 50)) . (strlen($sub->message) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <span style="color:<?php echo $sub->status === 'sent' ? 'green' : 'orange'; ?>;">
                                        <?php echo strtoupper($sub->status); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($sub->created_at); ?></td>
                                <td><?php echo esc_html($sub->ip); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p style="margin-top:10px;">
                    <a href="<?php echo admin_url('admin-post.php?action=phpue_export_csv'); ?>" class="button button-primary">
                        📥 Export CSV
                    </a>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

// ============================================================
// EXPORT SUBMISSIONS AS CSV
// ============================================================
add_action('admin_post_phpue_export_csv', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'phpue_submissions';
    
    $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="phpue-leads-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Email', 'Message', 'Status', 'Date', 'IP']);
    
    foreach ($submissions as $sub) {
        fputcsv($output, [
            $sub->id,
            $sub->name,
            $sub->email,
            $sub->message,
            $sub->status,
            $sub->created_at,
            $sub->ip
        ]);
    }
    
    fclose($output);
    exit;
});

// ============================================================
// CLEANUP ON DEACTIVATION
// ============================================================
register_deactivation_hook(__FILE__, function() {
    delete_option('phpue_ajax_functions');
    delete_option('phpue_component_functions');
});

// ============================================================
// ELEMENTOR DEPENDENCY NOTICE
// ============================================================
add_action('admin_notices', function() {
    if (!did_action('elementor/loaded')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>PHPue Elementor Widget</strong> requires 
                <a href="https://wordpress.org/plugins/elementor/" target="_blank">Elementor</a> 
                to be installed and activated.
            </p>
        </div>
        <?php
    }
});