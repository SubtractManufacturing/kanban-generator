<?php
/**
 * Plugin Name: Fusion to Arda Converter Ultimate
 * Plugin URI: https://yoursite.com
 * Description: Fully customizable Fusion 360 to Arda.cards CSV converter with UI editor
 * Version: 3.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FusionArdaConverterUltimate {

    private $option_logic = 'fusion_arda_converter_logic';
    private $option_styles = 'fusion_arda_converter_styles';
    private $option_texts = 'fusion_arda_converter_texts';

    public function __construct() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Frontend hooks
        add_shortcode('fusion_arda_converter', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        // AJAX handlers
        add_action('wp_ajax_fusion_arda_save_settings', array($this, 'ajax_save_settings'));

        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        // Set defaults if none exist
        if (get_option($this->option_logic) === false) {
            update_option($this->option_logic, $this->get_default_logic());
        }
        if (get_option($this->option_styles) === false) {
            update_option($this->option_styles, $this->get_default_styles());
        }
        if (get_option($this->option_texts) === false) {
            update_option($this->option_texts, $this->get_default_texts());
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Fusion Arda Converter',
            'Fusion Converter',
            'manage_options',
            'fusion-arda-converter',
            array($this, 'admin_page'),
            'dashicons-update',
            30
        );

        add_submenu_page(
            'fusion-arda-converter',
            'Customization',
            'Customize UI',
            'manage_options',
            'fusion-arda-customize',
            array($this, 'customize_page')
        );

        add_submenu_page(
            'fusion-arda-converter',
            'Conversion Logic',
            'Edit Logic',
            'manage_options',
            'fusion-arda-logic',
            array($this, 'logic_editor_page')
        );
    }

    public function settings_init() {
        register_setting('fusion_arda_settings', $this->option_logic);
        register_setting('fusion_arda_settings', $this->option_styles);
        register_setting('fusion_arda_settings', $this->option_texts);
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'fusion-arda') !== false) {
            // Color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            // Code editor for logic page
            if (strpos($hook, 'logic') !== false) {
                wp_enqueue_code_editor(array('type' => 'application/javascript'));
                wp_enqueue_script('wp-theme-plugin-editor');
                wp_enqueue_style('wp-codemirror');
            }

            // Custom admin script
            wp_add_inline_script('wp-color-picker', $this->get_admin_js());

            // Custom admin styles
            wp_add_inline_style('wp-color-picker', '
                .fac-admin-container {
                    max-width: 1200px;
                    margin: 20px 0;
                }
                .fac-settings-section {
                    background: white;
                    padding: 20px;
                    margin: 20px 0;
                    border: 1px solid #ccd0d4;
                    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                }
                .fac-settings-row {
                    display: flex;
                    align-items: center;
                    margin: 15px 0;
                }
                .fac-settings-label {
                    width: 200px;
                    font-weight: 600;
                }
                .fac-settings-input {
                    flex: 1;
                    max-width: 400px;
                }
                .fac-settings-input input[type="text"] {
                    width: 100%;
                }
                .fac-preview {
                    background: #f9f9f9;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    margin: 20px 0;
                }
                .fac-live-preview {
                    margin: 20px 0;
                    padding: 20px;
                    border: 2px dashed #ccc;
                    border-radius: 8px;
                }
                .CodeMirror {
                    border: 1px solid #ddd;
                    height: 500px;
                }
            ');
        }
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Fusion to Arda Converter</h1>
            <div class="fac-admin-container">
                <div class="card">
                    <h2>Quick Setup</h2>
                    <ol>
                        <li>Add shortcode: <code>[fusion_arda_converter]</code> to any page</li>
                        <li>Customize the appearance in <a href="?page=fusion-arda-customize">Customize UI</a></li>
                        <li>Edit conversion logic in <a href="?page=fusion-arda-logic">Edit Logic</a></li>
                    </ol>
                </div>

                <div class="card">
                    <h2>Current Settings</h2>
                    <?php
                    $texts = get_option($this->option_texts, $this->get_default_texts());
                    $styles = get_option($this->option_styles, $this->get_default_styles());
                    ?>
                    <p><strong>Title:</strong> <?php echo esc_html($texts['title']); ?></p>
                    <p><strong>Upload Button:</strong> <?php echo esc_html($texts['upload_button']); ?></p>
                    <p><strong>Convert Button:</strong> <?php echo esc_html($texts['convert_button']); ?></p>
                    <p><strong>Primary Color:</strong> <span style="display:inline-block;width:20px;height:20px;background:<?php echo esc_attr($styles['primary_color']); ?>;border:1px solid #ccc;vertical-align:middle;"></span> <?php echo esc_html($styles['primary_color']); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function customize_page() {
        // Handle form submission
        if (isset($_POST['submit'])) {
            $styles = array(
                'primary_color' => sanitize_hex_color($_POST['primary_color']),
                'success_color' => sanitize_hex_color($_POST['success_color']),
                'button_radius' => sanitize_text_field($_POST['button_radius']),
                'button_padding' => sanitize_text_field($_POST['button_padding']),
                'container_bg' => sanitize_hex_color($_POST['container_bg']),
                'container_padding' => sanitize_text_field($_POST['container_padding']),
                'text_color' => sanitize_hex_color($_POST['text_color'])
            );

            $texts = array(
                'title' => sanitize_text_field($_POST['title']),
                'subtitle' => sanitize_text_field($_POST['subtitle']),
                'upload_button' => sanitize_text_field($_POST['upload_button']),
                'convert_button' => sanitize_text_field($_POST['convert_button']),
                'success_message' => sanitize_text_field($_POST['success_message']),
                'error_message' => sanitize_text_field($_POST['error_message']),
                'loading_message' => sanitize_text_field($_POST['loading_message']),
                'info_title' => sanitize_text_field($_POST['info_title']),
                'info_items' => sanitize_textarea_field($_POST['info_items'])
            );

            update_option($this->option_styles, $styles);
            update_option($this->option_texts, $texts);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $styles = get_option($this->option_styles, $this->get_default_styles());
        $texts = get_option($this->option_texts, $this->get_default_texts());
        ?>
        <div class="wrap">
            <h1>Customize Converter UI</h1>
            <form method="post" action="">
                <?php wp_nonce_field('fusion_arda_customize'); ?>

                <div class="fac-admin-container">
                    <!-- Text Customization -->
                    <div class="fac-settings-section">
                        <h2>Text & Labels</h2>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Title:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="title" value="<?php echo esc_attr($texts['title']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Subtitle:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="subtitle" value="<?php echo esc_attr($texts['subtitle']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Upload Button Text:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="upload_button" value="<?php echo esc_attr($texts['upload_button']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Convert Button Text:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="convert_button" value="<?php echo esc_attr($texts['convert_button']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Success Message:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="success_message" value="<?php echo esc_attr($texts['success_message']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Error Message:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="error_message" value="<?php echo esc_attr($texts['error_message']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Loading Message:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="loading_message" value="<?php echo esc_attr($texts['loading_message']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Info Box Title:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="info_title" value="<?php echo esc_attr($texts['info_title']); ?>" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Info Items (one per line):</label>
                            <div class="fac-settings-input">
                                <textarea name="info_items" rows="5" style="width:100%;"><?php echo esc_textarea($texts['info_items']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Style Customization -->
                    <div class="fac-settings-section">
                        <h2>Colors & Styles</h2>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Primary Color:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="primary_color" value="<?php echo esc_attr($styles['primary_color']); ?>" class="color-picker" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Success Color:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="success_color" value="<?php echo esc_attr($styles['success_color']); ?>" class="color-picker" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Text Color:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="text_color" value="<?php echo esc_attr($styles['text_color']); ?>" class="color-picker" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Container Background:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="container_bg" value="<?php echo esc_attr($styles['container_bg']); ?>" class="color-picker" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Button Border Radius:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="button_radius" value="<?php echo esc_attr($styles['button_radius']); ?>" placeholder="e.g., 4px" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Button Padding:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="button_padding" value="<?php echo esc_attr($styles['button_padding']); ?>" placeholder="e.g., 12px 24px" />
                            </div>
                        </div>

                        <div class="fac-settings-row">
                            <label class="fac-settings-label">Container Padding:</label>
                            <div class="fac-settings-input">
                                <input type="text" name="container_padding" value="<?php echo esc_attr($styles['container_padding']); ?>" placeholder="e.g., 20px" />
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="fac-settings-section">
                        <h2>Live Preview</h2>
                        <div class="fac-live-preview">
                            <div id="preview-container" style="background: <?php echo $styles['container_bg']; ?>; padding: <?php echo $styles['container_padding']; ?>; border-radius: 8px;">
                                <h3 id="preview-title" style="color: <?php echo $styles['text_color']; ?>;"><?php echo $texts['title']; ?></h3>
                                <p id="preview-subtitle" style="color: <?php echo $styles['text_color']; ?>; opacity: 0.8;"><?php echo $texts['subtitle']; ?></p>

                                <button id="preview-upload" style="background: <?php echo $styles['primary_color']; ?>; color: white; padding: <?php echo $styles['button_padding']; ?>; border-radius: <?php echo $styles['button_radius']; ?>; border: none; width: 100%; margin: 10px 0; cursor: pointer;">
                                    <?php echo $texts['upload_button']; ?>
                                </button>

                                <button id="preview-convert" style="background: <?php echo $styles['success_color']; ?>; color: white; padding: <?php echo $styles['button_padding']; ?>; border-radius: <?php echo $styles['button_radius']; ?>; border: none; width: 100%; margin: 10px 0; cursor: pointer;">
                                    <?php echo $texts['convert_button']; ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary button-large" value="Save All Settings">
                        <button type="button" onclick="resetToDefaults()" class="button button-large">Reset to Defaults</button>
                    </p>
                </div>
            </form>
        </div>

        <script>
        function resetToDefaults() {
            if (confirm('Reset all UI settings to defaults?')) {
                // Reset form values to defaults
                location.reload();
            }
        }
        </script>
        <?php
    }

    public function logic_editor_page() {
        if (isset($_POST['submit'])) {
            $logic = stripslashes($_POST['conversion_logic']);
            update_option($this->option_logic, $logic);
            echo '<div class="notice notice-success"><p>Conversion logic updated successfully!</p></div>';
        }

        $current_logic = get_option($this->option_logic, $this->get_default_logic());
        ?>
        <div class="wrap">
            <h1>Edit Conversion Logic</h1>
            <div class="fac-admin-container">
                <form method="post" action="">
                    <?php wp_nonce_field('fusion_arda_save_logic'); ?>

                    <div class="card">
                        <h2>JavaScript Conversion Function</h2>
                        <p>Customize how the CSV data is processed and mapped to Arda fields.</p>
                    </div>

                    <div class="card">
                        <textarea id="conversion_logic" name="conversion_logic" style="width: 100%;"><?php echo esc_textarea($current_logic); ?></textarea>
                    </div>

                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary button-large" value="Save Logic">
                        <button type="button" onclick="validateLogic()" class="button button-large">Validate Syntax</button>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            if (wp.codeEditor) {
                wp.codeEditor.initialize($('#conversion_logic'), {
                    codemirror: {
                        mode: 'javascript',
                        lineNumbers: true,
                        lineWrapping: true
                    }
                });
            }
        });

        function validateLogic() {
            try {
                var logic = document.getElementById('conversion_logic').value;
                new Function('csvData', 'headers', logic);
                alert('Syntax is valid!');
            } catch (e) {
                alert('Syntax Error: ' + e.message);
            }
        }
        </script>
        <?php
    }

    public function render_shortcode() {
        $styles = get_option($this->option_styles, $this->get_default_styles());
        $texts = get_option($this->option_texts, $this->get_default_texts());

        ob_start();
        ?>
        <div class="fusion-arda-converter" id="fac-converter">
            <div class="fac-header">
                <h3><?php echo esc_html($texts['title']); ?></h3>
                <p><?php echo esc_html($texts['subtitle']); ?></p>
            </div>

            <?php if (!empty($texts['info_title'])) : ?>
            <div class="fac-info-box">
                <h4><?php echo esc_html($texts['info_title']); ?></h4>
                <ul>
                    <?php
                    $items = explode("\n", $texts['info_items']);
                    foreach ($items as $item) {
                        if (trim($item)) {
                            echo '<li>' . esc_html(trim($item)) . '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="fac-upload-area">
                <label for="fac-file-input" class="fac-upload-button">
                    <?php echo esc_html($texts['upload_button']); ?>
                </label>
                <input type="file" id="fac-file-input" accept=".csv" style="display: none;" />
                <div id="fac-file-name" style="display: none;"></div>
            </div>

            <button id="fac-convert-btn" class="fac-convert-button" disabled>
                <?php echo esc_html($texts['convert_button']); ?>
            </button>

            <div id="fac-progress" style="display: none;">
                <div class="fac-progress-bar">
                    <div id="fac-progress-fill"></div>
                </div>
            </div>

            <div id="fac-status" style="display: none;"></div>
        </div>

        <style>
        .fusion-arda-converter {
            max-width: 100%;
            width: 100%;
            margin: 20px auto;
            padding: <?php echo esc_attr($styles['container_padding']); ?>;
            background: <?php echo esc_attr($styles['container_bg']); ?>;
            border-radius: 8px;
            color: <?php echo esc_attr($styles['text_color']); ?>;
        }
        @media (min-width: 981px) {
            .fusion-arda-converter {
                max-width: 900px;
            }
        }
        @media (min-width: 1200px) {
            .fusion-arda-converter {
                max-width: 1100px;
            }
        }
        .fac-header h3 {
            margin-top: 0;
            color: <?php echo esc_attr($styles['text_color']); ?>;
        }
        .fac-header p {
            opacity: 0.8;
            color: <?php echo esc_attr($styles['text_color']); ?>;
        }
        .fac-info-box {
            background: rgba(255,255,255,0.5);
            padding: 15px;
            margin: 20px 0;
            border-radius: <?php echo esc_attr($styles['button_radius']); ?>;
            border-left: 4px solid <?php echo esc_attr($styles['primary_color']); ?>;
        }
        .fac-info-box h4 {
            margin-top: 0;
            color: <?php echo esc_attr($styles['primary_color']); ?>;
        }
        .fac-info-box ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
        .fac-info-box li {
            margin: 5px 0;
            list-style: none;
            position: relative;
            padding-left: 20px;
        }
        .fac-info-box li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: <?php echo esc_attr($styles['primary_color']); ?>;
        }
        .fac-upload-button, .fac-convert-button {
            display: block;
            width: 100%;
            padding: <?php echo esc_attr($styles['button_padding']); ?>;
            margin: 10px 0;
            text-align: center;
            border-radius: <?php echo esc_attr($styles['button_radius']); ?>;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        .fac-upload-button {
            background: <?php echo esc_attr($styles['primary_color']); ?>;
            color: white;
        }
        .fac-upload-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .fac-convert-button {
            background: <?php echo esc_attr($styles['success_color']); ?>;
            color: white;
        }
        .fac-convert-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .fac-convert-button:not(:disabled):hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        #fac-file-name {
            padding: 10px;
            margin: 10px 0;
            background: rgba(255,255,255,0.5);
            border-radius: <?php echo esc_attr($styles['button_radius']); ?>;
        }
        .fac-progress-bar {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        #fac-progress-fill {
            height: 100%;
            background: <?php echo esc_attr($styles['success_color']); ?>;
            width: 0;
            transition: width 0.3s;
        }
        #fac-status {
            padding: 12px;
            margin: 10px 0;
            border-radius: <?php echo esc_attr($styles['button_radius']); ?>;
            text-align: center;
        }
        #fac-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        #fac-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    public function enqueue_frontend_scripts() {
        if (has_shortcode(get_post()->post_content, 'fusion_arda_converter')) {
            wp_enqueue_script('jquery');

            $conversion_logic = get_option($this->option_logic, $this->get_default_logic());
            $texts = get_option($this->option_texts, $this->get_default_texts());

            wp_add_inline_script('jquery', $this->get_frontend_js($conversion_logic, $texts));
        }
    }

    private function get_frontend_js($conversion_logic, $texts) {
        return "
jQuery(document).ready(function($) {
    var fileContent = null;
    var texts = " . json_encode($texts) . ";

    $('#fac-file-input').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            $('#fac-file-name').show().text('Selected: ' + file.name);
            $('#fac-convert-btn').prop('disabled', false);

            var reader = new FileReader();
            reader.onload = function(e) {
                fileContent = e.target.result;
                showStatus(texts.loading_message || 'File loaded. Ready to convert!', 'success');
            };
            reader.readAsText(file);
        }
    });

    $('#fac-convert-btn').on('click', function() {
        if (!fileContent) return;

        $('#fac-progress').show();
        $('#fac-progress-fill').css('width', '50%');

        try {
            var lines = fileContent.replace(/\\r\\n/g, '\\n').split('\\n').filter(function(l) {
                return l.length > 0;
            });

            var headers = parseCSVLine(lines[0]);
            var csvData = [];

            for (var i = 1; i < lines.length; i++) {
                var values = parseCSVLine(lines[i]);
                if (Math.abs(values.length - headers.length) <= 1) {
                    var row = {};
                    headers.forEach(function(h, idx) {
                        row[h] = values[idx] || '';
                    });
                    csvData.push(row);
                }
            }

            var conversionFunction = new Function('csvData', 'headers', " . json_encode($conversion_logic) . ");
            var outputRows = conversionFunction(csvData, headers);

            // Generate clean CSV (only quote when necessary)
            var cols = ['Item Name', 'Notes', 'SKU', 'Supplier', 'Location', 'Minimum', 'Order Quantity', 'Product URL', 'Image URL', 'Color Coding'];

            // Build CSV with minimal quoting
            var csvRows = [];
            csvRows.push(cols.join(','));  // Header row without quotes

            outputRows.forEach(function(row) {
                var rowValues = cols.map(function(c) {
                    var val = row[c] || '';
                    // Only add quotes if value contains comma, quotes, or newlines
                    if (val && (val.indexOf(',') >= 0 || val.indexOf('\"') >= 0 || val.indexOf('\\n') >= 0)) {
                        return '\"' + String(val).replace(/\"/g, '\"\"') + '\"';
                    }
                    return val;
                });
                csvRows.push(rowValues.join(','));
            });

            var csv = csvRows.join('\\n');

            $('#fac-progress-fill').css('width', '100%');

            downloadCSV(csv, 'arda_import.csv');
            $('#fac-progress').hide();
            showStatus(texts.success_message || 'Conversion successful!', 'success');

        } catch (error) {
            $('#fac-progress').hide();
            showStatus((texts.error_message || 'Error') + ': ' + error.message, 'error');
        }
    });

    function parseCSVLine(line) {
        var result = [];
        var current = '';
        var inQuotes = false;

        for (var i = 0; i < line.length; i++) {
            var c = line[i];
            if (inQuotes) {
                if (c === '\"' && line[i + 1] === '\"') {
                    current += '\"';
                    i++;
                } else if (c === '\"') {
                    inQuotes = false;
                } else {
                    current += c;
                }
            } else {
                if (c === '\"') {
                    inQuotes = true;
                } else if (c === ',') {
                    result.push(current);
                    current = '';
                } else {
                    current += c;
                }
            }
        }
        result.push(current);
        return result;
    }

    function downloadCSV(content, filename) {
        var blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function showStatus(message, type) {
        $('#fac-status').text(message)
            .removeClass('success error')
            .addClass(type)
            .show();

        if (type === 'success') {
            setTimeout(function() {
                $('#fac-status').hide();
            }, 5000);
        }
    }
});
        ";
    }

    private function get_admin_js() {
        return "
jQuery(document).ready(function($) {
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            updatePreview();
        }
    });

    $('input[type=\"text\"]').on('input', function() {
        updatePreview();
    });

    function updatePreview() {
        $('#preview-title').text($('input[name=\"title\"]').val());
        $('#preview-subtitle').text($('input[name=\"subtitle\"]').val());
        $('#preview-upload').text($('input[name=\"upload_button\"]').val());
        $('#preview-convert').text($('input[name=\"convert_button\"]').val());

        $('#preview-container').css({
            'background': $('input[name=\"container_bg\"]').val(),
            'padding': $('input[name=\"container_padding\"]').val()
        });

        $('#preview-title, #preview-subtitle').css('color', $('input[name=\"text_color\"]').val());

        $('#preview-upload').css({
            'background': $('input[name=\"primary_color\"]').val(),
            'padding': $('input[name=\"button_padding\"]').val(),
            'border-radius': $('input[name=\"button_radius\"]').val()
        });

        $('#preview-convert').css({
            'background': $('input[name=\"success_color\"]').val(),
            'padding': $('input[name=\"button_padding\"]').val(),
            'border-radius': $('input[name=\"button_radius\"]').val()
        });
    }
});
        ";
    }

    private function get_default_logic() {
        return file_get_contents(__DIR__ . '/default-logic.js');
    }

    private function get_default_styles() {
        return array(
            'primary_color' => '#0073aa',
            'success_color' => '#46b450',
            'text_color' => '#333333',
            'container_bg' => '#f9f9f9',
            'button_radius' => '4px',
            'button_padding' => '12px 24px',
            'container_padding' => '20px'
        );
    }

    private function get_default_texts() {
        return array(
            'title' => 'Fusion → Arda.cards Converter',
            'subtitle' => 'Convert Fusion 360 Tool Library CSV to Arda.cards Import Format',
            'upload_button' => '📁 Select Fusion Tool Library CSV',
            'convert_button' => '🔄 Convert to Arda Format',
            'success_message' => '✅ Conversion successful! File downloaded.',
            'error_message' => '❌ Conversion error',
            'loading_message' => 'File loaded. Ready to convert!',
            'info_title' => 'What This Tool Does',
            'info_items' => "Converts Fusion 360 Tool Library CSV to Arda.cards bulk import CSV\nDe-duplicates tools by Tool Index (keeps first occurrence)\nMaps tool types to appropriate product images\nFormats item names as: [Number] - [Description]\nPreserves vendor, product ID, and product links"
        );
    }
}

// Initialize the plugin
new FusionArdaConverterUltimate();
?>