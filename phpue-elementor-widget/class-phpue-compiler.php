<?php
/* Author(s): Edward Patch */

if (!defined('ABSPATH')) {
    exit;
}

class PHPue_String_Compiler {

    private $ajaxFunctions = [];
    private $currentPageName = '';
    private $componentId = '';

    public function convert($pvue_content, $component_id) {
        $this->componentId = $component_id;
        
        $script = $this->extract_between($pvue_content, '<script>', '</script>');
        $template = $this->extract_between($pvue_content, '<template>', '</template>');
        $cscript_raw = $this->extract_between($pvue_content, '<cscript>', '</cscript>');

        // Process cscript with the same pattern
        $cscript = $cscript_raw;
        if (!empty($cscript_raw)) {
            $cscript = $this->convertTemplateVars($cscript_raw);
        }

        $this->currentPageName = 'widget-' . $component_id;
        $script = $this->processAjaxAnnotations($script);
        $this->syncAjaxFunctions();

        $output = "<?php\n";
        $output .= "// Component: " . $component_id . "\n";
        $output .= "if (!defined('ABSPATH')) exit;\n\n";
        
        if (!empty($this->ajaxFunctions[$this->currentPageName])) {
            foreach ($this->ajaxFunctions[$this->currentPageName] as $function_name => $function_data) {
                if (!isset($GLOBALS['phpue_ajax_functions'])) {
                    $GLOBALS['phpue_ajax_functions'] = [];
                }
                if (!isset($GLOBALS['phpue_ajax_functions'][$function_name])) {
                    $GLOBALS['phpue_ajax_functions'][$function_name] = $function_data['code'];
                }
                
                $output .= "if (!function_exists('" . $function_name . "')) {\n";
                $output .= "    " . $function_data['code'] . "\n";
                $output .= "}\n\n";
                
                $output .= "add_action('wp_ajax_" . $function_name . "', '" . $function_name . "');\n";
                $output .= "add_action('wp_ajax_nopriv_" . $function_name . "', '" . $function_name . "');\n\n";
            }
        }
        
        if (!empty($script)) {
            $output .= $script . "\n\n";
        }
        
        $output .= "?>\n";
        
        if (!empty($template)) {
            $template = $this->convertTemplate($template);
            $output .= $template . "\n";
        }

        if (!empty($cscript)) {
            $output .= $cscript . "\n";
        }

        return $output;
    }

    /**
     * Convert {{ }} syntax to PHP echo statements
     * Supports:
     * - {{ $var }}
     * - {{ $var['key'] }}
     * - {{ $var["key"] }}
     * - {{ $var->property }}
     * - {{ $var['key']['nested'] }}
     * - {{ $var->method()->property }}
     */
    private function convertTemplateVars($content) {
        return preg_replace_callback(
            '/\{\{\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\[[\'"][^\'"]+[\'"]\]|\[[0-9]+\]|->[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*|\([^)]*\))*)\s*\}\}/',
            function($matches) {
                return "<?= " . $matches[1] . " ?>";
            },
            $content
        );
    }

    private function syncAjaxFunctions() {
        $current_functions = [];
        if (!empty($this->ajaxFunctions[$this->currentPageName])) {
            foreach ($this->ajaxFunctions[$this->currentPageName] as $function_name => $function_data) {
                $current_functions[$function_name] = [
                    'code' => $function_data['code'],
                    'method' => $function_data['method'],
                    'component_id' => $this->componentId,
                    'last_updated' => current_time('mysql')
                ];
            }
        }

        $stored_functions = get_option('phpue_ajax_functions', []);
        $component_functions = get_option('phpue_component_functions', []);
        
        if (!empty($current_functions)) {
            $component_functions[$this->componentId] = array_keys($current_functions);
            foreach ($current_functions as $function_name => $function_data) {
                $stored_functions[$function_name] = $function_data;
            }
        } else {
            if (isset($component_functions[$this->componentId])) {
                unset($component_functions[$this->componentId]);
            }
        }

        $all_component_functions = [];
        foreach ($component_functions as $component_id => $function_names) {
            foreach ($function_names as $function_name) {
                $all_component_functions[] = $function_name;
            }
        }
        
        $functions_to_remove = array_diff(array_keys($stored_functions), $all_component_functions);
        
        if (!empty($functions_to_remove)) {
            foreach ($functions_to_remove as $function_name) {
                unset($stored_functions[$function_name]);
            }
        }

        update_option('phpue_ajax_functions', $stored_functions);
        update_option('phpue_component_functions', $component_functions);
    }

    private function processAjaxAnnotations($scriptContent) {
        if (empty($this->currentPageName)) {
            $this->currentPageName = 'widget';
        }

        if (!isset($this->ajaxFunctions[$this->currentPageName])) {
            $this->ajaxFunctions[$this->currentPageName] = [];
        }

        $pattern = '/@AJAX\(\'([^\']+)\'\)\s*(function\s+(\w+)\s*\([^)]*\))\s*\{/s';
        
        if (preg_match_all($pattern, $scriptContent, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $functionsToRemove = [];
            
            foreach ($matches as $match) {
                $httpMethod = $match[1][0];
                $functionHeader = $match[2][0];
                $functionName = $match[3][0];
                $annotationStartPos = $match[0][1];
                $bracePos = $annotationStartPos + strlen($match[0][0]);
                
                $functionBody = $this->extractCompleteFunctionBody($scriptContent, $bracePos);
                
                if ($functionBody !== null) {
                    $completeFunction = $functionHeader . "{" . $functionBody . "}";
                    
                    $this->ajaxFunctions[$this->currentPageName][$functionName] = [
                        'code' => trim($completeFunction),
                        'method' => $httpMethod,
                        'name' => $functionName
                    ];
                    
                    $fullFunctionStart = $annotationStartPos;
                    $fullFunctionEnd = $bracePos + strlen($functionBody) + 1;
                    
                    $functionsToRemove[] = [
                        'start' => $fullFunctionStart,
                        'end' => $fullFunctionEnd,
                        'name' => $functionName
                    ];
                }
            }
            
            usort($functionsToRemove, function($a, $b) {
                return $b['start'] - $a['start'];
            });
            
            foreach ($functionsToRemove as $remove) {
                $length = $remove['end'] - $remove['start'];
                $replacement = "// AJAX function '{$remove['name']}' handled automatically";
                $scriptContent = substr_replace($scriptContent, $replacement, $remove['start'], $length);
            }
        }
        
        return $scriptContent;
    }

    private function extractCompleteFunctionBody($content, $startAfterBrace) {
        $braceCount = 1;
        $pos = $startAfterBrace;
        $length = strlen($content);
        
        while ($pos < $length && $braceCount > 0) {
            $char = $content[$pos];
            
            if ($char === '"' || $char === "'") {
                $stringChar = $char;
                $pos++;
                while ($pos < $length) {
                    if ($content[$pos] === '\\') {
                        $pos += 2;
                        continue;
                    }
                    if ($content[$pos] === $stringChar) {
                        break;
                    }
                    $pos++;
                }
            } elseif ($char === '/' && $pos + 1 < $length) {
                $nextChar = $content[$pos + 1];
                if ($nextChar === '/') {
                    $pos += 2;
                    while ($pos < $length && $content[$pos] !== "\n") {
                        $pos++;
                    }
                } elseif ($nextChar === '*') {
                    $pos += 2;
                    while ($pos < $length - 1) {
                        if ($content[$pos] === '*' && $content[$pos + 1] === '/') {
                            $pos += 2;
                            break;
                        }
                        $pos++;
                    }
                }
            } else {
                if ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;
                    if ($braceCount === 0) {
                        $bodyLength = $pos - $startAfterBrace;
                        return substr($content, $startAfterBrace, $bodyLength);
                    }
                }
            }
            $pos++;
        }
        return null;
    }

    public function getAjaxFunctions() {
        return $this->ajaxFunctions;
    }

    private function extract_between($content, $start, $end) {
        $pattern = '/' . preg_quote($start, '/') . '(.*?)' . preg_quote($end, '/') . '/s';
        preg_match($pattern, $content, $matches);
        return $matches[1] ?? '';
    }

    private function convertTemplate($template) {
        // Convert {{ }} syntax
        $template = $this->convertTemplateVars($template);
        
        // Convert p-for directives
        $template = $this->convertPFor($template);
        
        // Convert p-if directives
        $template = $this->convertPIfWithStack($template);
        
        // Remove any remaining p-directives from HTML attributes
        $template = preg_replace('/\s+p-(if|for)="[^"]*"/', '', $template);

        return $template;
    }

    private function convertPFor($template) {
        $pattern = '/<(\w+)([^>]*?)\s+p-for="\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+in\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"([^>]*)>([\s\S]*?)<\/\1>/';
        
        return preg_replace_callback(
            $pattern,
            function($matches) {
                $tag = $matches[1];
                $attrs_before = $matches[2];
                $item = $matches[3];
                $array = $matches[4];
                $attrs_after = $matches[5];
                $content = $matches[6];
                
                $attrs = $attrs_before . $attrs_after;
                $attrs = preg_replace('/\s+p-for="[^"]*"/', '', $attrs);
                
                return "<?php if(isset($" . $array . ") && is_array($" . $array . ")): foreach($" . $array . " as $" . $item . "): ?>" .
                    "<" . $tag . $attrs . ">" . $content . "</" . $tag . ">" .
                    "<?php endforeach; endif; ?>";
            },
            $template
        );
    }

    private function convertPIfWithStack($template) {
        $lines = explode("\n", $template);
        $output = [];
        $pIfStack = [];
        
        foreach ($lines as $line) {
            if (preg_match('/<(\w+)([^>]*)\s+p-if="([^"]*)"([^>]*)>/', $line, $matches)) {
                $tag = $matches[1];
                $attrs = $matches[2] . $matches[4];
                $condition = $matches[3];
                
                $cleanAttrs = preg_replace('/\s+p-if="[^"]*"/', '', $attrs);
                
                $pIfStack[] = ['tag' => $tag, 'condition' => $condition, 'attrs' => $cleanAttrs, 'depth' => 1];
                
                $output[] = "<?php if(" . $condition . "): ?>";
                $output[] = "<" . $tag . $cleanAttrs . ">";
                continue;
            }

            if (!empty($pIfStack)) {
                $currentPIf = &$pIfStack[count($pIfStack) - 1];
                $currentTag = $currentPIf['tag'];
                
                if (preg_match("/<" . $currentTag . "[^>]*>/", $line) && !preg_match("/<\/" . $currentTag . ">/", $line)) {
                    $currentPIf['depth']++;
                }
                
                if (preg_match("/<\/" . $currentTag . ">/", $line)) {
                    $currentPIf['depth']--;
                    if ($currentPIf['depth'] === 0) {
                        $output[] = $line;
                        $output[] = "<?php endif; ?>";
                        array_pop($pIfStack);
                        continue;
                    }
                }
            }
            $output[] = $line;
        }
        
        while (!empty($pIfStack)) {
            array_pop($pIfStack);
            $output[] = "<?php endif; ?>";
        }
        
        return implode("\n", $output);
    }
}