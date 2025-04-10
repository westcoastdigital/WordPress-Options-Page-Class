<?php

/**
 * WordPress Settings Page Generator Class
 * 
 * A flexible class for creating WordPress admin settings pages with support for tabs
 * and various field types.
 */

if (!class_exists('WP_Settings_Generator')) {

    class WP_Settings_Generator
    {

        /**
         * Unique identifier for the settings page
         *
         * @var string
         */
        private $page_id;

        /**
         * Page title
         *
         * @var string
         */
        private $page_title;

        /**
         * Menu title
         *
         * @var string
         */
        private $menu_title;

        /**
         * Capability required to access the page
         *
         * @var string
         */
        private $capability;

        /**
         * Option group name
         *
         * @var string
         */
        private $option_group;

        /**
         * Page location settings
         *
         * @var array
         */
        private $location;

        /**
         * Fields configuration
         *
         * @var array
         */
        private $fields = [];

        /**
         * Tabs configuration
         *
         * @var array
         */
        private $tabs = [];

        /**
         * Whether this settings page uses tabs
         *
         * @var bool
         */
        private $has_tabs = false;

        /**
         * Current active tab
         *
         * @var string
         */
        private $active_tab = '';

        /**
         * Default tab (if using tabs)
         *
         * @var string
         */
        private $default_tab = '';

        /**
         * Constructor
         *
         * @param string $page_id Unique identifier for the settings page
         * @param string $page_title Page title
         * @param string $menu_title Menu title
         * @param string $capability Capability required to access the page
         * @param array $location Location settings for the page
         */
        public function __construct($page_id, $page_title, $menu_title, $capability = 'manage_options', $location = [])
        {
            $this->page_id = $page_id;
            $this->page_title = $page_title;
            $this->menu_title = $menu_title;
            $this->capability = $capability;
            $this->option_group = $page_id . '_group';

            // Set default location if not provided
            $this->location = wp_parse_args($location, [
                'type' => 'menu', // 'menu', 'submenu'
                'parent' => '', // Parent slug for submenu
                'position' => null, // Position in menu
                'icon' => 'dashicons-admin-generic', // Menu icon (for top-level menu)
            ]);

            // Initialize hooks
            add_action('admin_menu', [$this, 'add_settings_page']);
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        }

        /**
         * Enqueue required assets for the settings page
         *
         * @param string $hook Current admin page
         */
        public function enqueue_assets($hook)
        {
            global $pagenow;

            // Only load on our settings page
            if (($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === $this->page_id) ||
                ($this->location['type'] === 'submenu' && strpos($hook, $this->page_id) !== false)
            ) {

                // Core WordPress scripts needed for fields
                wp_enqueue_media(); // For media fields
                wp_enqueue_style('wp-color-picker'); // For color picker
                wp_enqueue_script('wp-color-picker');
                wp_enqueue_script('jquery-ui-datepicker'); // For date picker
                wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css');

                // Add our custom scripts and styles
                add_action('admin_footer', [$this, 'print_field_scripts']);
                add_action('admin_head', [$this, 'print_field_styles']);
            }
        }

        /**
         * Print custom field scripts
         */
        public function print_field_scripts()
        {
?>
            <script>
                jQuery(document).ready(function($) {
                    // Initialize color pickers
                    $('.wp-settings-color-field').wpColorPicker();

                    // Initialize date pickers
                    $('.wp-settings-date-field').datepicker({
                        dateFormat: 'yy-mm-dd',
                        changeMonth: true,
                        changeYear: true
                    });

                    // Initialize media fields
                    $('.wp-settings-media-upload').click(function(e) {
                        e.preventDefault();

                        var button = $(this);
                        var field = button.siblings('.wp-settings-media-field');
                        var preview = button.siblings('.wp-settings-media-preview');
                        var removeButton = button.siblings('.wp-settings-media-remove');

                        var frame = wp.media({
                            title: 'Select or Upload Media',
                            button: {
                                text: 'Use this media'
                            },
                            multiple: false
                        });

                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            field.val(attachment.id);

                            // Update preview based on attachment type
                            if (attachment.type === 'image') {
                                preview.html('<img src="' + attachment.url + '" alt="" style="max-width: 150px; max-height: 150px;" />');
                            } else {
                                preview.html('<div class="media-info"><strong>Type:</strong> ' + attachment.type + '<br><strong>Name:</strong> ' + attachment.filename + '</div>');
                            }

                            removeButton.show();
                        });

                        frame.open();
                    });

                    // Handle remove media button
                    $('.wp-settings-media-remove').click(function(e) {
                        e.preventDefault();

                        var button = $(this);
                        var field = button.siblings('.wp-settings-media-field');
                        var preview = button.siblings('.wp-settings-media-preview');

                        field.val('');
                        preview.html('');
                        button.hide();
                    });

                    // Toggle switch behavior
                    $('.wp-settings-toggle input').change(function() {
                        var label = $(this).siblings('.wp-settings-toggle-text');
                        var onText = $(this).data('on-text') || 'On';
                        var offText = $(this).data('off-text') || 'Off';

                        if ($(this).is(':checked')) {
                            label.text(onText);
                        } else {
                            label.text(offText);
                        }
                    });

                    // Multiselect functionality
                    $('.wp-settings-multiselect').on('change', function() {
                        var values = [];
                        $(this).find('option:selected').each(function() {
                            values.push($(this).val());
                        });
                        $(this).siblings('.wp-settings-multiselect-value').val(JSON.stringify(values));
                    });

                    // Initialize multiselect fields from hidden input
                    $('.wp-settings-multiselect').each(function() {
                        var values = $(this).siblings('.wp-settings-multiselect-value').val();
                        if (values) {
                            try {
                                var selectedValues = JSON.parse(values);
                                $(this).val(selectedValues);
                            } catch (e) {
                                console.error('Invalid multiselect value:', values);
                            }
                        }
                    });
                });
            </script>
        <?php
        }

        /**
         * Print custom field styles
         */
        public function print_field_styles()
        {
        ?>
            <style>
                /* Toggle Switch Styles */
                .wp-settings-toggle {
                    position: relative;
                    display: inline-block;
                    cursor: pointer;
                }

                .wp-settings-toggle input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .wp-settings-toggle-slider {
                    position: relative;
                    display: inline-block;
                    width: 40px;
                    height: 20px;
                    background-color: #ccc;
                    border-radius: 20px;
                    transition: .4s;
                    vertical-align: middle;
                }

                .wp-settings-toggle-slider:before {
                    position: absolute;
                    content: "";
                    height: 16px;
                    width: 16px;
                    left: 2px;
                    bottom: 2px;
                    background-color: white;
                    border-radius: 50%;
                    transition: .4s;
                }

                .wp-settings-toggle input:checked+.wp-settings-toggle-slider {
                    background-color: #2271b1;
                }

                .wp-settings-toggle input:focus+.wp-settings-toggle-slider {
                    box-shadow: 0 0 1px #2271b1;
                }

                .wp-settings-toggle input:checked+.wp-settings-toggle-slider:before {
                    transform: translateX(20px);
                }

                .wp-settings-toggle-text {
                    margin-left: 10px;
                    vertical-align: middle;
                }

                /* Radio and Checkbox Fields */
                .wp-settings-radio-group {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .wp-settings-radio-option {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                /* Media Fields */
                .wp-settings-media-field-container {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .wp-settings-media-preview {
                    margin: 5px 0;
                }

                .wp-settings-media-buttons {
                    display: flex;
                    gap: 5px;
                }

                .wp-settings-media-remove {
                    color: #a00;
                }

                .media-info {
                    background: #f0f0f1;
                    padding: 8px;
                    border-radius: 4px;
                    font-size: 12px;
                }

                /* Field Spacing */
                .form-table td .description {
                    margin-top: 8px;
                }

                /* Select and Multiselect */
                .wp-settings-select {
                    min-width: 200px;
                }

                .wp-settings-multiselect {
                    min-width: 200px;
                    min-height: 100px;
                }
            </style>
        <?php
        }

        /**
         * Enable tabbed interface
         *
         * @param string $default_tab Default tab ID
         * @return $this
         */
        public function enable_tabs($default_tab = '')
        {
            $this->has_tabs = true;
            $this->default_tab = $default_tab;
            return $this;
        }

        /**
         * Add a new tab
         *
         * @param string $tab_id Unique identifier for the tab
         * @param string $tab_title Tab title
         * @param string $description Optional tab description
         * @return $this
         */
        public function add_tab($tab_id, $tab_title, $description = '')
        {
            $this->tabs[$tab_id] = [
                'title' => $tab_title,
                'description' => $description
            ];

            // Set first tab as default if no default is set
            if (empty($this->default_tab)) {
                $this->default_tab = $tab_id;
            }

            return $this;
        }

        /**
         * Add a field to the settings page
         *
         * @param string $field_id Unique identifier for the field
         * @param string $title Field title
         * @param string $type Field type
         * @param array $args Additional arguments for the field
         * @param string $tab_id Tab ID (if using tabs)
         * @return $this
         */
        public function add_field($field_id, $title, $type, $args = [], $tab_id = '')
        {
            $field = wp_parse_args($args, [
                'id' => $field_id,
                'title' => $title,
                'type' => $type,
                'description' => '',
                'default' => '',
                'placeholder' => '',
                'options' => [], // For radio, select, etc.
                'sanitize_callback' => [$this, 'default_sanitize_callback'],
                'tab' => $tab_id,
                'class' => '',
                'attributes' => []
            ]);

            $this->fields[$field_id] = $field;

            return $this;
        }

        /**
         * Add text field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_text_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'text', $args, $tab_id);
        }

        /**
         * Add textarea field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_textarea_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'textarea', $args, $tab_id);
        }

        /**
         * Add WYSIWYG editor field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_wysiwyg_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'wysiwyg', $args, $tab_id);
        }

        /**
         * Add checkbox field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_checkbox_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'checkbox', $args, $tab_id);
        }

        /**
         * Add toggle field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_toggle_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'toggle', $args, $tab_id);
        }

        /**
         * Add radio field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_radio_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'radio', $args, $tab_id);
        }

        /**
         * Add select field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_select_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'select', $args, $tab_id);
        }

        /**
         * Add multiselect field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_multiselect_field($field_id, $title, $args = [], $tab_id = '')
        {
            // Ensure default is an array if provided
            if (isset($args['default']) && !is_array($args['default'])) {
                $args['default'] = [$args['default']];
            } elseif (!isset($args['default'])) {
                $args['default'] = [];
            }

            return $this->add_field($field_id, $title, 'multiselect', $args, $tab_id);
        }

        /**
         * Set default values for a multiselect field
         * 
         * Example: $settings->set_multiselect_defaults('my_field', ['option1', 'option2']);
         *
         * @param string $field_id Field ID
         * @param array $default_values Array of default values
         * @return $this
         */
        public function set_multiselect_defaults($field_id, array $default_values)
        {
            if (isset($this->fields[$field_id]) && $this->fields[$field_id]['type'] === 'multiselect') {
                $this->fields[$field_id]['default'] = $default_values;
            }
            return $this;
        }

        /**
         * Add media field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_media_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'media', $args, $tab_id);
        }

        /**
         * Add email field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_email_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'email', $args, $tab_id);
        }

        /**
         * Add URL field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_url_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'url', $args, $tab_id);
        }

        /**
         * Add password field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_password_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'password', $args, $tab_id);
        }

        /**
         * Add number field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_number_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'number', $args, $tab_id);
        }

        /**
         * Add telephone field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_tel_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'tel', $args, $tab_id);
        }

        /**
         * Add date field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_date_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'date', $args, $tab_id);
        }

        /**
         * Add color field
         * 
         * @param string $field_id Field ID
         * @param string $title Field title
         * @param array $args Field arguments
         * @param string $tab_id Tab ID
         * @return $this
         */
        public function add_color_field($field_id, $title, $args = [], $tab_id = '')
        {
            return $this->add_field($field_id, $title, 'color', $args, $tab_id);
        }

        /**
         * Register the settings page in WordPress admin
         */
        public function add_settings_page()
        {
            switch ($this->location['type']) {
                case 'menu':
                    add_menu_page(
                        $this->page_title,
                        $this->menu_title,
                        $this->capability,
                        $this->page_id,
                        [$this, 'render_settings_page'],
                        $this->location['icon'],
                        $this->location['position']
                    );
                    break;

                case 'submenu':
                    add_submenu_page(
                        $this->location['parent'],
                        $this->page_title,
                        $this->menu_title,
                        $this->capability,
                        $this->page_id,
                        [$this, 'render_settings_page'],
                        $this->location['position']
                    );
                    break;
            }
        }

        /**
         * Register settings and fields
         */
        public function register_settings()
        {
            // Register setting
            register_setting(
                $this->option_group,
                $this->page_id,
                [
                    'sanitize_callback' => [$this, 'sanitize_settings']
                ]
            );

            // Get tabs or create a default one
            $sections = $this->has_tabs ? $this->tabs : ['default' => ['title' => '']];

            foreach ($sections as $section_id => $section) {
                $section_title = $section['title'];

                // Register section
                add_settings_section(
                    $this->page_id . '_' . $section_id,
                    $section_title,
                    function () use ($section_id, $section) {
                        // Section description can go here if needed
                        if (!empty($section['description'])) {
                            echo '<p>' . wp_kses_post($section['description']) . '</p>';
                        }
                    },
                    $this->page_id . ($this->has_tabs ? '_' . $section_id : '')
                );

                // Add fields to this section
                foreach ($this->fields as $field_id => $field) {
                    // Skip fields that don't belong to this tab (if using tabs)
                    if ($this->has_tabs && $field['tab'] !== $section_id) {
                        continue;
                    }

                    // Skip fields with tab specified when not using that tab
                    if (!$this->has_tabs && !empty($field['tab'])) {
                        continue;
                    }

                    add_settings_field(
                        $field_id,
                        $field['title'],
                        [$this, 'render_field'],
                        $this->page_id . ($this->has_tabs ? '_' . $section_id : ''),
                        $this->page_id . '_' . ($this->has_tabs ? $section_id : 'default'),
                        [
                            'field' => $field,
                            'label_for' => $field_id
                        ]
                    );
                }
            }
        }

        /**
         * Sanitize settings before saving
         *
         * @param array $input The submitted settings
         * @return array Sanitized settings
         */
        public function sanitize_settings($input)
        {
            $output = get_option($this->page_id, []);

            foreach ($this->fields as $field_id => $field) {
                if (isset($input[$field_id])) {
                    $sanitize_callback = $field['sanitize_callback'];
                    $output[$field_id] = call_user_func($sanitize_callback, $input[$field_id], $field);
                }
            }

            return $output;
        }

        /**
         * Default sanitization callback
         *
         * @param mixed $value The value to sanitize
         * @param array $field Field configuration
         * @return mixed Sanitized value
         */
        public function default_sanitize_callback($value, $field)
        {
            switch ($field['type']) {
                case 'text':
                    return sanitize_text_field($value);

                case 'textarea':
                    return sanitize_textarea_field($value);

                case 'wysiwyg':
                    return wp_kses_post($value);

                case 'checkbox':
                case 'toggle':
                    return (bool) $value;

                case 'radio':
                case 'select':
                    return isset($field['options'][$value]) ? $value : $field['default'];

                case 'multiselect':
                    // Handle multiselect values
                    $selected_values = [];

                    // If value is already an array (direct form submission)
                    if (is_array($value)) {
                        $selected_values = $value;
                    }
                    // If value is a JSON string (from hidden field)
                    else if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (is_array($decoded)) {
                            $selected_values = $decoded;
                        }
                    }

                    // If no values are selected but we have defaults and this is a new option
                    if (empty($selected_values) && !empty($field['default'])) {
                        $options = get_option($this->page_id, null);
                        if ($options === null) { // This is a new option being added
                            return $field['default'];
                        }
                    }

                    // Check each value against available options
                    return array_filter($selected_values, function ($item) use ($field) {
                        return isset($field['options'][$item]);
                    });

                case 'email':
                    return sanitize_email($value);

                case 'url':
                    return esc_url_raw($value);

                case 'number':
                    return is_numeric($value) ? floatval($value) : $field['default'];

                case 'tel':
                    // Basic sanitization for telephone
                    return preg_replace('/[^\d+\-\(\) ]/', '', $value);

                case 'color':
                    // Validate color in hexadecimal format
                    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                        return $value;
                    }
                    return $field['default'];

                case 'date':
                    // Validate date format (YYYY-MM-DD)
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        return $value;
                    }
                    return $field['default'];

                case 'media':
                    // Media IDs should be integers
                    return absint($value);

                case 'password':
                    // Don't sanitize passwords (they'll be hashed elsewhere if needed)
                    return $value;

                default:
                    // Allow custom sanitization via hooks
                    $custom_sanitized = apply_filters('wp_settings_generator_sanitize_' . $field['type'], $value, $field);
                    if ($custom_sanitized !== null) {
                        return $custom_sanitized;
                    }

                    // Fallback to basic sanitization
                    return sanitize_text_field($value);
            }
        }

        /**
         * Render the settings page
         */
        public function render_settings_page()
        {
            if (!current_user_can($this->capability)) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }

            // Get the active tab if using tabs
            if ($this->has_tabs) {
                $this->active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $this->default_tab;
            }

            // Get saved options
            $options = get_option($this->page_id, []);

        ?>
            <div class="wrap">
                <h1><?php echo esc_html($this->page_title); ?></h1>

                <?php if ($this->has_tabs && !empty($this->tabs)) : ?>
                    <h2 class="nav-tab-wrapper">
                        <?php foreach ($this->tabs as $tab_id => $tab) : ?>
                            <a href="?page=<?php echo esc_attr($this->page_id); ?>&tab=<?php echo esc_attr($tab_id); ?>"
                                class="nav-tab <?php echo $this->active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                                <?php echo esc_html($tab['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </h2>
                <?php endif; ?>

                <form method="post" action="options.php">
                    <?php
                    if ($this->has_tabs) {
                        settings_fields($this->option_group);
                        do_settings_sections($this->page_id . '_' . $this->active_tab);
                    } else {
                        settings_fields($this->option_group);
                        do_settings_sections($this->page_id);
                    }
                    submit_button();
                    ?>
                </form>
            </div>
        <?php
        }

        /**
         * Render a field based on its type
         *
         * @param array $args Field arguments
         */
        public function render_field($args)
        {
            $field = $args['field'];
            $field_id = $field['id'];
            $options = get_option($this->page_id, []);


            // Special handling for multiselect defaults
            if ($field['type'] === 'multiselect') {
                // If the option doesn't exist yet or is empty, use the default
                $value = isset($options[$field_id]) && !empty($options[$field_id])
                    ? $options[$field_id]
                    : $field['default'];
            } else {
                // Normal handling for other field types
                $value = isset($options[$field_id]) ? $options[$field_id] : $field['default'];
            }

            $name = $this->page_id . '[' . $field_id . ']';

            // Build common attributes
            $field_class = !empty($field['class']) ? $field['class'] : '';

            switch ($field['type']) {
                case 'text':
                    $this->render_text_field($field, $name, $value);
                    break;

                case 'textarea':
                    $this->render_textarea_field($field, $name, $value);
                    break;

                case 'wysiwyg':
                    $this->render_wysiwyg_field($field, $name, $value);
                    break;

                case 'checkbox':
                    $this->render_checkbox_field($field, $name, $value);
                    break;

                case 'toggle':
                    $this->render_toggle_field($field, $name, $value);
                    break;

                case 'toggle':
                    $this->render_toggle_field($field, $name, $value);
                    break;

                case 'radio':
                    $this->render_radio_field($field, $name, $value);
                    break;

                case 'select':
                    $this->render_select_field($field, $name, $value);
                    break;

                case 'multiselect':
                    $this->render_multiselect_field($field, $name, $value);
                    break;

                case 'media':
                    $this->render_media_field($field, $name, $value);
                    break;

                case 'email':
                    $this->render_email_field($field, $name, $value);
                    break;

                case 'url':
                    $this->render_url_field($field, $name, $value);
                    break;

                case 'password':
                    $this->render_password_field($field, $name, $value);
                    break;

                case 'number':
                    $this->render_number_field($field, $name, $value);
                    break;

                case 'tel':
                    $this->render_tel_field($field, $name, $value);
                    break;

                case 'date':
                    $this->render_date_field($field, $name, $value);
                    break;

                case 'color':
                    $this->render_color_field($field, $name, $value);
                    break;

                default:
                    // Allow custom field types via hook
                    do_action('wp_settings_generator_render_field_' . $field['type'], $field, $name, $value, $this);
                    break;
            }

            if (!empty($field['description'])) {
                echo '<p class="description">' . wp_kses_post($field['description']) . '</p>';
            }
        }

        /**
         * Render a text field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_text_field($field, $name, $value)
        {
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
            $class = !empty($field['class']) ? $field['class'] : 'regular-text';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="text"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo $attr_string; ?> />
        <?php
        }

        /**
         * Render a textarea field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_textarea_field($field, $name, $value)
        {
            $rows = isset($field['rows']) ? $field['rows'] : 5;
            $cols = isset($field['cols']) ? $field['cols'] : 50;
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
            $class = !empty($field['class']) ? $field['class'] : 'large-text';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <textarea
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                rows="<?php echo esc_attr($rows); ?>"
                cols="<?php echo esc_attr($cols); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo $attr_string; ?>><?php echo esc_textarea($value); ?></textarea>
        <?php
        }

        /**
         * Render a WYSIWYG editor field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_wysiwyg_field($field, $name, $value)
        {
            $editor_settings = isset($field['editor_settings']) ? $field['editor_settings'] : [];
            $editor_settings = wp_parse_args($editor_settings, [
                'textarea_name' => $name,
                'textarea_rows' => 10,
                'media_buttons' => true,
                'teeny' => false,
            ]);

            wp_editor($value, $field['id'], $editor_settings);
        }

        /**
         * Render a checkbox field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_checkbox_field($field, $name, $value)
        {
            $label = isset($field['checkbox_label']) ? $field['checkbox_label'] : '';
            $class = !empty($field['class']) ? $field['class'] : '';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="checkbox"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="1"
                class="<?php echo esc_attr($class); ?>"
                <?php checked($value, true); ?>
                <?php echo $attr_string; ?> />
            <?php if (!empty($label)) : ?>
                <label for="<?php echo esc_attr($field['id']); ?>">
                    <?php echo esc_html($label); ?>
                </label>
            <?php endif; ?>
        <?php
        }

        /**
         * Render a toggle switch field (styled checkbox)
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_toggle_field($field, $name, $value)
        {
            $on_text = isset($field['on_text']) ? $field['on_text'] : __('On');
            $off_text = isset($field['off_text']) ? $field['off_text'] : __('Off');
            $class = !empty($field['class']) ? $field['class'] : '';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <label class="wp-settings-toggle">
                <input
                    type="checkbox"
                    id="<?php echo esc_attr($field['id']); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    value="1"
                    class="<?php echo esc_attr($class); ?>"
                    <?php checked($value, true); ?>
                    data-on-text="<?php echo esc_attr($on_text); ?>"
                    data-off-text="<?php echo esc_attr($off_text); ?>"
                    <?php echo $attr_string; ?> />
                <span class="wp-settings-toggle-slider"></span>
                <span class="wp-settings-toggle-text">
                    <?php echo $value ? esc_html($on_text) : esc_html($off_text); ?>
                </span>
            </label>
            <?php
        }

        /**
         * Render a radio field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_radio_field($field, $name, $value)
        {
            if (empty($field['options']) || !is_array($field['options'])) {
                return;
            }

            $class = !empty($field['class']) ? $field['class'] : '';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

            echo '<div class="wp-settings-radio-group">';

            foreach ($field['options'] as $option_value => $option_label) {
                $radio_id = $field['id'] . '-' . sanitize_key($option_value);
            ?>
                <div class="wp-settings-radio-option">
                    <input
                        type="radio"
                        id="<?php echo esc_attr($radio_id); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        value="<?php echo esc_attr($option_value); ?>"
                        class="<?php echo esc_attr($class); ?>"
                        <?php checked($value, $option_value); ?>
                        <?php echo $attr_string; ?> />
                    <label for="<?php echo esc_attr($radio_id); ?>">
                        <?php echo esc_html($option_label); ?>
                    </label>
                </div>
            <?php
            }

            echo '</div>';
        }

        /**
         * Render a select field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_select_field($field, $name, $value)
        {
            if (empty($field['options']) || !is_array($field['options'])) {
                return;
            }

            $class = !empty($field['class']) ? $field['class'] . ' wp-settings-select' : 'wp-settings-select';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

            ?>
            <select
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                class="<?php echo esc_attr($class); ?>"
                <?php echo $attr_string; ?>>
                <?php foreach ($field['options'] as $option_value => $option_label) : ?>
                    <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php
        }

        /**
         * Render a multiselect field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_multiselect_field($field, $name, $value)
        {
            if (empty($field['options']) || !is_array($field['options'])) {
                return;
            }

            // Ensure the value is an array
            if (!is_array($value)) {
                $value = [];
            }

            $class = !empty($field['class']) ? $field['class'] . ' wp-settings-multiselect' : 'wp-settings-multiselect';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

            // Size attribute for multiselect
            $size = isset($field['size']) ? intval($field['size']) : 5;

        ?>
            <select
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>[]"
                class="<?php echo esc_attr($class); ?>"
                multiple="multiple"
                size="<?php echo esc_attr($size); ?>"
                <?php echo $attr_string; ?>>
                <?php foreach ($field['options'] as $option_value => $option_label) : ?>
                    <option
                        value="<?php echo esc_attr($option_value); ?>"
                        <?php echo in_array($option_value, $value) ? 'selected="selected"' : ''; ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php
        }

        /**
         * Render a media field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_media_field($field, $name, $value)
        {
            $upload_button_text = isset($field['upload_button_text']) ? $field['upload_button_text'] : __('Select Media');
            $remove_button_text = isset($field['remove_button_text']) ? $field['remove_button_text'] : __('Remove');
            $preview = '';

            // Get attachment details if we have a value
            if (!empty($value)) {
                $attachment = wp_get_attachment_url($value);
                if ($attachment) {
                    // Check if it's an image
                    $attachment_type = get_post_mime_type($value);
                    if (strpos($attachment_type, 'image') !== false) {
                        $preview = '<img src="' . esc_url($attachment) . '" alt="" style="max-width: 150px; max-height: 150px;" />';
                    } else {
                        $attachment_data = get_post($value);
                        $preview = '<div class="media-info"><strong>Type:</strong> ' . esc_html($attachment_type) . '<br><strong>Name:</strong> ' . esc_html(basename($attachment)) . '</div>';
                    }
                }
            }

        ?>
            <div class="wp-settings-media-field-container">
                <input
                    type="hidden"
                    id="<?php echo esc_attr($field['id']); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    class="wp-settings-media-field" />

                <div class="wp-settings-media-preview">
                    <?php echo $preview; ?>
                </div>

                <div class="wp-settings-media-buttons">
                    <button class="button wp-settings-media-upload"><?php echo esc_html($upload_button_text); ?></button>
                    <button class="button wp-settings-media-remove" <?php echo empty($value) ? 'style="display:none;"' : ''; ?>><?php echo esc_html($remove_button_text); ?></button>
                </div>
            </div>
        <?php
        }

        /**
         * Render an email field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_email_field($field, $name, $value)
        {
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
            $class = !empty($field['class']) ? $field['class'] : 'regular-text';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="email"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo $attr_string; ?> />
        <?php
        }

        /**
         * Render a URL field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_url_field($field, $name, $value)
        {
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
            $class = !empty($field['class']) ? $field['class'] : 'regular-text';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="url"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo $attr_string; ?> />
        <?php
        }

        /**
         * Render a password field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_password_field($field, $name, $value)
        {
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
            $class = !empty($field['class']) ? $field['class'] : 'regular-text';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="password"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                autocomplete="new-password"
                <?php echo $attr_string; ?> />
        <?php
        }

        /**
         * Render a number field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_number_field($field, $name, $value)
        {
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
            $class = !empty($field['class']) ? $field['class'] : 'regular-text';
            $min = isset($field['min']) ? $field['min'] : '';
            $max = isset($field['max']) ? $field['max'] : '';
            $step = isset($field['step']) ? $field['step'] : '1';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="number"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo !empty($min) ? 'min="' . esc_attr($min) . '"' : ''; ?>
                <?php echo !empty($max) ? 'max="' . esc_attr($max) . '"' : ''; ?>
                step="<?php echo esc_attr($step); ?>"
                <?php echo $attr_string; ?> />
        <?php
        }

        /**
         * Render a telephone field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_tel_field($field, $name, $value)
        {
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : '';
            $class = !empty($field['class']) ? $field['class'] : 'regular-text';
            $pattern = isset($field['pattern']) ? $field['pattern'] : '';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="tel"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo !empty($pattern) ? 'pattern="' . esc_attr($pattern) . '"' : ''; ?>
                <?php echo $attr_string; ?> />
        <?php
        }

        /**
         * Render a date field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_date_field($field, $name, $value)
        {
            $placeholder = !empty($field['placeholder']) ? $field['placeholder'] : 'YYYY-MM-DD';
            $class = !empty($field['class']) ? $field['class'] . ' wp-settings-date-field' : 'regular-text wp-settings-date-field';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

        ?>
            <input
                type="text"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo $attr_string; ?> />
        <?php
        }

        /**
         * Render a color field
         *
         * @param array $field Field configuration
         * @param string $name Field name
         * @param mixed $value Field value
         */
        private function render_color_field($field, $name, $value)
        {
            $class = !empty($field['class']) ? $field['class'] . ' wp-settings-color-field' : 'wp-settings-color-field';
            $attrs = isset($field['attributes']) ? $field['attributes'] : [];
            $attr_string = $this->build_attributes($attrs);

            // Set default color if empty
            if (empty($value)) {
                $value = isset($field['default']) ? $field['default'] : '#000000';
            }

        ?>
            <input
                type="text"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                class="<?php echo esc_attr($class); ?>"
                <?php echo $attr_string; ?> />
<?php
        }

        /**
         * Build HTML attributes string from array
         *
         * @param array $attrs Attributes as key-value pairs
         * @return string HTML attributes string
         */
        private function build_attributes($attrs)
        {
            $attributes = [];

            foreach ($attrs as $key => $value) {
                if (is_bool($value)) {
                    if ($value) {
                        $attributes[] = esc_attr($key);
                    }
                } else {
                    $attributes[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
                }
            }

            return implode(' ', $attributes);
        }
    }
}