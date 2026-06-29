<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class PHPue_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'phpue_widget';
    }

    public function get_title() {
        return __('PHPue Component', 'phpue-widget');
    }

    public function get_icon() {
        return 'eicon-code';
    }

    public function get_categories() {
        return ['phpue', 'general'];
    }

    public function get_keywords() {
        return ['php', 'code', 'component', 'phpue', 'script', 'template', 'dynamic'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'phpue_script_section',
            [
                'label' => __('PHPue Script (PHP)', 'phpue-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'phpue_script',
            [
                'label' => __('Server Script (PHP)', 'phpue-widget'),
                'type' => \Elementor\Controls_Manager::CODE,
                'language' => 'php',
                'rows' => 12,
                'placeholder' => "// Fetch data from WordPress\n\$posts = get_posts(['numberposts' => 5]);\n\$message = 'Hello from PHPue!';\n\n@AJAX('POST')\nfunction submitForm(\$input) {\n    // Handle AJAX request\n    \$name = \$input['name'] ?? '';\n    wp_send_json_success(['message' => 'Hello ' . \$name]);\n}",
                'description' => __('Write PHP code here. Use @AJAX(\'POST\') or @AJAX(\'GET\') to create AJAX endpoints.', 'phpue-widget'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'phpue_template_section',
            [
                'label' => __('PHPue Template (HTML + PHP)', 'phpue-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'phpue_template',
            [
                'label' => __('Template (HTML / PHP)', 'phpue-widget'),
                'type' => \Elementor\Controls_Manager::CODE,
                'language' => 'html',
                'rows' => 15,
                'placeholder' => "<div class=\"phpue-component\">\n    <h2>{{ \$message }}</h2>\n    <div p-for=\"\$post in \$posts\">\n        <p>{{ \$post->post_title }}</p>\n    </div>\n    <form onsubmit=\"submitForm(event, 'submitForm')\">\n        <input name=\"name\" placeholder=\"Your name\">\n        <button type=\"submit\">Submit</button>\n    </form>\n</div>",
                'description' => __('Write HTML and PHP here. Use {{ &#36;var }} for escaped output, or use p-for and p-if directives.', 'phpue-widget'),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'phpue_cscript_section',
            [
                'label' => __('PHPue Client Script (JS + PHP)', 'phpue-widget'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'phpue_cscript',
            [
                'label' => __('Client Script (JavaScript)', 'phpue-widget'),
                'type' => \Elementor\Controls_Manager::CODE,
                'language' => 'javascript',
                'rows' => 10,
                'placeholder' => "// Client-side JavaScript\nconsole.log('PHPue component mounted!');\nconsole.log('Message: ' + {{ \$message }});\n\n// AJAX function for form submission\nasync function submitForm(event, methodName) {\n    event.preventDefault();\n    const form = event.target;\n    const data = new FormData(form);\n    const response = await fetch('/wp-admin/admin-ajax.php', {\n        method: 'POST',\n        body: new URLSearchParams({\n            action: methodName,\n            ...Object.fromEntries(data)\n        })\n    });\n    const result = await response.json();\n    console.log(result);\n}",
                'description' => __('Write JavaScript here. Use {{ &#36;var }} to embed PHP variables.', 'phpue-widget'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<!-- PHPue Widget: Insufficient permissions -->';
            return;
        }

        $settings = $this->get_settings_for_display();
        $widget_id = $this->get_id();

        $script = $settings['phpue_script'] ?? '';
        $template = $settings['phpue_template'] ?? '';
        $cscript = $settings['phpue_cscript'] ?? '';

        if (empty($template) && empty($script) && empty($cscript)) {
            echo '<!-- PHPue Widget: Empty component -->';
            return;
        }

        $component_data = $this->execute_component_with_vars($script, $template, $widget_id);
        
        $html_output = $component_data['html'] ?? '';
        $variables = $component_data['variables'] ?? [];

        echo '<div id="phpue-widget-' . esc_attr($widget_id) . '" class="phpue-component" data-widget-id="' . esc_attr($widget_id) . '">';

        if (!empty($variables)) {
            foreach ($variables as $key => $value) {
                if (!is_array($value) && !is_object($value)) {
                    $html_output = str_replace('{{$' . $key . '}}', esc_html($value), $html_output);
                }
            }
        }

        $html_output = preg_replace('/\{\{\s*\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\}\}/', '', $html_output);

        echo $html_output;
        echo '</div>';

        if (!empty($cscript)) {
            $this->enqueue_client_script($cscript, $widget_id, $variables);
        }
    }

    private function execute_component_with_vars($script, $template, $widget_id) {
        $pvue_content = '';
        if (!empty($script)) {
            $pvue_content .= "<script>\n" . $script . "\n</script>\n\n";
        }
        if (!empty($template)) {
            $pvue_content .= "<template>\n" . $template . "\n</template>";
        }

        if (empty($pvue_content)) {
            return ['html' => '', 'variables' => []];
        }

        $compiler = new PHPue_String_Compiler();
        $compiled = $compiler->convert($pvue_content, 'widget-' . $widget_id);

        $html_output = '';
        $variables = [];

        try {
            $closure = function() use ($compiled, &$html_output, &$variables) {
                ob_start();
                eval('?>' . $compiled);
                $html_output = ob_get_clean();
                
                $all_vars = get_defined_vars();
                foreach ($all_vars as $key => $value) {
                    if (!in_array($key, ['compiled', 'html_output', 'variables', 'all_vars'])) {
                        $variables[$key] = $value;
                    }
                }
            };
            $closure();
        } catch (Throwable $e) {
            $html_output = '<div style="color:red;padding:10px;border:1px solid red;background:#ffe0e0;margin:10px 0;">';
            $html_output .= '<strong>PHPue Error:</strong> ' . esc_html($e->getMessage());
            $html_output .= '<br><small>Line: ' . esc_html($e->getLine()) . '</small>';
            $html_output .= '</div>';
        }

        return [
            'html' => $html_output,
            'variables' => $variables
        ];
    }

    private function enqueue_client_script($cscript, $widget_id, $variables = []) {
        add_action('wp_footer', function() use ($cscript, $widget_id, $variables) {
            $processed_cscript = $cscript;
            
            if (!empty($variables)) {
                foreach ($variables as $key => $value) {
                    if (!is_array($value) && !is_object($value)) {
                        $js_value = json_encode($value);
                        $processed_cscript = str_replace('{{$' . $key . '}}', $js_value, $processed_cscript);
                    }
                }
            }

            $processed_cscript = preg_replace('/\{\{\s*\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\}\}/', 'null', $processed_cscript);

            $js_vars = json_encode($variables, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
            ?>
            <script>
                (function() {
                    const container = document.getElementById('phpue-widget-<?php echo esc_js($widget_id); ?>');
                    if (!container) {
                        console.warn('PHPue: Container not found for widget <?php echo esc_js($widget_id); ?>');
                        return;
                    }

                    const phpVars = <?php echo $js_vars; ?>;
                    if (phpVars && typeof phpVars === 'object') {
                        container.dataset.phpVars = JSON.stringify(phpVars);
                    }

                    try {
                        <?php echo $processed_cscript; ?>
                    } catch (error) {
                        console.error('PHPue Client Error:', error);
                    }
                })();
            </script>
            <?php
        }, 100);
    }

    public function render_plain_content() {
        echo '<!-- PHPue Widget (Preview) -->';
    }
}