# WordPress Options Page Class
Build custom options pages with repeaters for tabs and fields.

## Usage
There is a generator included in the repo as index.html ot you can just go to the [Github Page](https://westcoastdigital.github.io/WordPress-Options-Page-Class/) alternatively you can manually create the code (see examples below)

### Include the class
You need to include it within your theme or plugin eg<br/>
```require_once plugin_dir_path(__FILE__) . 'inc/class-wp-settings-generator.php';```


### Register Page
```
// Initialize the settings page as main menu
$settings = new WP_Settings_Generator(
    'my_plugin_settings', // ID
    'My Plugin Settings', // Page title
    'My Plugin', // Admin menu title
    'manage_options', // Access permission capability
    [
        'type' => 'menu', // Parent menu
        'position' => 80, // Menu position (2 – Dashboard, 4 – Separator, 5 – Posts, 10 – Media, 15 – Links, 20 – Pages, 25 – Comments, 59 – Separator, 60 – Appearance, 65 – Plugins, 70 – Users, 75 – Tools, 80 – Settings, 99 – Separator)
        'icon' => 'dashicons-admin-generic' // Menu icon
    ]
);
```

```
// Initialize the settings page as sub menu
 $settings = new WP_Settings_Generator(
    'my_plugin_settings', // ID
    'My Plugin Settings', // Page title
    'My Plugin', // Admin menu title
    'manage_options', // Access permission capability
    [
        'type' => 'submenu', // Parent menu
        'parent' => 'tools.php'
    ]
);
```

### Enable Tabs
You need to enable tabs otherwise fields will be rendered directly in page<br/>
```
// Without custom default tab
$settings->enable_tabs();
```
```
// With custom default tab
$settings->enable_tabs('display'); // change to tab id
```


### Register Tabs
This is done using ```add_tab($tab_id, $tab_label)```<br/>
eg: add multiple tabs<br/>
```
 // Add tabs
$settings->add_tab('general', 'General Settings')
         ->add_tab('display', 'Display Options')
         ->add_tab('advanced', 'Advanced Settings');
```

### Register Fields
This is varies for different fields however<br/>
```
$settings->field_type_function( // replace field_type_function witht the required field
    'my_plugin_field_id', // Field id
    __('Field Label'), // Field label
    [
        'description' => __('Field description goes here'), // Field description appears below the field
        'default' => 'default', // Default value
        // Custom args for each field will go here
    ],
    'assignment' // If using tabs add this otherwise if exists but enable_tabs is not defined the field will not render
);
```

### Retreiving Fields
Field values are stored in the options table under a single entry for the page_id used in the WP_Settings_Generator initialisation<br/>
For example<br/>
```
$settings = new WP_Settings_Generator(
    'my_custom_settings', // the ID for retrieving values
    'Rewards',
    'Simpli Rewards',
    'manage_options',
    [
        'type' => 'menu',
        'position' => 80,
        'icon' => 'dashicons-awards'
    ]
);
```
Then you would retrieve with ```$settings = get_option('my_custom_settings'); ``` and then from there get your field value from the field id.<br/>
eg:
```
$settings = get_option('page_id'); // replace page_id with the id of your page
$field_value = isset($settings['field_id']) ?  $settings['field_id'] : ''; // replace field_id with the id of the field you want to retrieve
```
Images are stored as an ID so if you want to output the image you need to
```
$settings = get_option('page_id');
$image_id = isset($settings['field_id']) ?  $settings['field_id'] : '';
if($image_id && $image_id != '') {
    $size = 'thumbnail'; // what size you want the image, thumbnail is default
    $image = wp_get_attachment_image($image_id, $size);
}
```
See [developer options](https://developer.wordpress.org/reference/functions/wp_get_attachment_image/) for more info<br/>
Date fields are stored in yyyy-mm-dd format so to output as different value<br/>
```
$settings = get_option('page_id');
$date_field = isset($settings['field_id']) ?  $settings['field_id'] : '';
$new_date = new DateTime($date_field);
$format = 'F j, Y';
$date = $new_date->format($format);
```
See [date formatting](https://wordpress.org/documentation/article/customize-date-and-time-format/) for more info<br/>


### Conditional Logic
You can add conditional logic to the field by adding conditional args eg<br/>
```
'condition' => [
    'field' => 'field_id', // change to match the field id to check against
    'value' => 'field_value' // change to match the field value to check against
]
```
eg:
```
// Radio field
$settings->add_radio_field(
    'layout_style',
    'Layout Style',
    [
        'description' => 'Choose your preferred layout',
        'default' => 'boxed',
        'options' => [
            'boxed' => 'Boxed Layout',
            'wide' => 'Wide Layout',
            'fullwidth' => 'Full Width Layout',
            'custom' => 'Custom'
        ]
    ],
    'display'
);

// Conditional Field
$settings->add_text_field(
    'site_title',
    'Site Title',
    [
        'description' => 'Enter your site title',
        'default' => get_bloginfo('name'),
        'placeholder' => 'Enter site title here',
        'condition' => [
            'field' => 'layout_style',
            'value' => 'custom'
        ]
    ],
    'display'
);
```

### Field Examples
All examples are set to be within a tab
```
// ===== GENERAl TAB FIELDS =====
// Text field
$settings->add_text_field(
    'site_title',
    'Site Title',
    [
        'description' => 'Enter your site title',
        'default' => get_bloginfo('name'),
        'placeholder' => 'Enter site title here'
    ],
    'general'
);

// Email field
$settings->add_email_field(
    'admin_email',
    'Admin Email',
    [
        'description' => 'Email for notifications',
        'default' => get_option('admin_email'),
        'placeholder' => 'your@email.com'
    ],
    'general'
);

// URL field
$settings->add_url_field(
    'website_url',
    'Website URL',
    [
        'description' => 'Your main website URL',
        'default' => home_url(),
        'placeholder' => 'https://example.com'
    ],
    'general'
);

// Password field
$settings->add_password_field(
    'api_key',
    'API Key',
    [
        'description' => 'Enter your API key (stored securely)',
        'placeholder' => 'Enter API key'
    ],
    'general'
);

// Textarea field
$settings->add_textarea_field(
    'site_description',
    'Site Description',
    [
        'description' => 'Enter your site description',
        'default' => get_bloginfo('description'),
        'rows' => 3,
        'placeholder' => 'Brief description of your site'
    ],
    'general'
);

// ===== DISPLAY TAB FIELDS =====

// Color field
$settings->add_color_field(
    'primary_color',
    'Primary Color',
    [
        'description' => 'Select your primary color',
        'default' => '#0073aa'
    ],
    'display'
);

// Media field (for logo)
$settings->add_media_field(
    'site_logo',
    'Site Logo',
    [
        'description' => 'Upload your site logo',
        'upload_button_text' => 'Select Logo',
        'remove_button_text' => 'Remove Logo'
    ],
    'display'
);

// Toggle field
$settings->add_toggle_field(
    'show_header',
    'Display Header',
    [
        'description' => 'Show or hide the site header',
        'default' => true,
        'on_text' => 'Visible',
        'off_text' => 'Hidden'
    ],
    'display'
);

// Checkbox field
$settings->add_checkbox_field(
    'show_footer',
    'Display Footer',
    [
        'description' => 'Show or hide the site footer',
        'default' => true,
        'checkbox_label' => 'Yes, display the footer'
    ],
    'display'
);

// Radio field
$settings->add_radio_field(
    'layout_style',
    'Layout Style',
    [
        'description' => 'Choose your preferred layout',
        'default' => 'boxed',
        'options' => [
            'boxed' => 'Boxed Layout',
            'wide' => 'Wide Layout',
            'fullwidth' => 'Full Width Layout'
        ]
    ],
    'display'
);

// Select field
$settings->add_select_field(
    'animation_effect',
    'Animation Effect',
    [
        'description' => 'Select a transition effect',
        'default' => 'fade',
        'options' => [
            'none' => 'No Animation',
            'fade' => 'Fade Effect',
            'slide' => 'Slide Effect',
            'zoom' => 'Zoom Effect'
        ]
    ],
    'display'
);

// ===== ADVANCED TAB FIELDS =====

// Number field
$settings->add_number_field(
    'post_count',
    'Posts Per Page',
    [
        'description' => 'Number of posts to display per page',
        'default' => 10,
        'min' => 1,
        'max' => 50,
        'step' => 1
    ],
    'advanced'
);

// Telephone field
$settings->add_tel_field(
    'contact_phone',
    'Contact Phone',
    [
        'description' => 'Enter a contact phone number',
        'placeholder' => '+1 (555) 123-4567',
        'pattern' => '[0-9+\-\(\) ]+'
    ],
    'advanced'
);

// Date field
$settings->add_date_field(
    'launch_date',
    'Launch Date',
    [
        'description' => 'Select your site launch date',
        'default' => date('Y-m-d')
    ],
    'advanced'
);

// Multiselect field
$settings->add_multiselect_field(
    'active_features',
    'Active Features',
    [
        'description' => 'Select which features to enable',
        'default' => ['comments', 'sharing'],
        'options' => [
            'comments' => 'Comments',
            'sharing' => 'Social Sharing',
            'ratings' => 'Content Ratings',
            'bookmarks' => 'Bookmarks',
            'newsletter' => 'Newsletter',
            'related' => 'Related Content'
        ],
        'size' => 6
    ],
    'advanced'
);

// WYSIWYG editor field
$settings->add_wysiwyg_field(
    'custom_css',
    'Custom CSS',
    [
        'description' => 'Add custom CSS to your site',
        'editor_settings' => [
            'textarea_rows' => 15,
            'teeny' => true,
            'media_buttons' => false
        ]
    ],
    'advanced'
);
```

### Full function example
```
<?php

require_once 'class-wp-settings-generator.php';

function simpli_rewards_admin_page_init() {
    $prefix = 'simpli_rewards_';
    // var_dump(simpli_user_roles());

    // Initialize the settings page
    $settings = new WP_Settings_Generator(
        $prefix . 'settings',
        'Rewards',
        'Simpli Rewards',
        'manage_options',
        [
            'type' => 'menu',
            'position' => 80,
            'icon' => 'dashicons-awards'
        ]
    );
    
    // Enable tabs
    $settings->enable_tabs('assignment');

    // Add tabs
    $settings->add_tab('dashboard', 'Customer Points')
            ->add_tab('assignment', 'Points Assignment')
            ->add_tab('redeeming', 'Points Redeeming')
            ->add_tab('email', 'Emails')
            ->add_tab('customisations', 'Customisations');
    // ===== ASSIGNMENT TAB FIELDS =====

    // Assign points to users
    $settings->add_radio_field(
        $prefix . 'assign_points',
        __('Assign points to'),
        [
            'description' => 'Choose whether to assign points to all users or only to specified user roles',
            'default' => 'all',
            'options' => [
                'all' => __('All users'),
                'custom' => __('Only specific roles')
            ]
        ],
        'assignment'
    );

    // Custom user roles to get points
    $settings->add_multiselect_field(
        $prefix . 'custom_roles',
        __('Assign points to roles'),
        [
            'description' => __('Choose which user roles can collect points with their purchases'),
            'default' => ['customer'],
            'options' => simpli_user_roles()
        ],
        'assignment'
    );

    // Points per expenditure
    $settings->add_number_field(
        $prefix . 'points_per_unit',
        __('Points per unit'),
        [
            'description' =>  __('Qty points earnt per unit'),
            'default' => 1,
            'min' => 1,
            // 'max' => 9999,
            'step' => 1
        ],
        'assignment'
    );

    // Points per expenditure
    $settings->add_number_field(
        $prefix . 'units',
        __('Units to spend'),
        [
            'description' =>  get_woocommerce_currency_symbol() . ' (' . get_option('woocommerce_currency') . ') ' . __('units for points'),
            'default' => 1,
            'min' => 1,
            // 'max' => 9999,
            'step' => 1
        ],
        'assignment'
    );

    // Tax consideration
    $settings->add_radio_field(
        $prefix . 'tax_incl',
        __('Calculate points considering product price with:'),
        [
            'description' => 'Calculate points considering product price with:',
            'default' => 'inc',
            'options' => [
                'inc' => __('Taxes included'),
                'excl' => __('Taxes excluded')
            ]
        ],
        'assignment'
    );

    // When to assign points
    $settings->add_multiselect_field(
        $prefix . 'checkout_status',
        __('Assign points when the order has status'),
        [
            'description' => __('Choose based on which order status to assign points to users'),
            'default' => ['order_completed', 'payment_completed'],
            'options' => [
                'order_completed' => __('Order Completed'),
                'payment_completed' => __('Payment Completed'),
                'order_processing' => __('Order Processing'),
            ]
        ],
        'assignment'
    );

    // Exclude sale items
    $settings->add_toggle_field(
        $prefix . 'excl_sale',
        __('Exclude on-sale products from points collection'),
        [
            'description' => __('If enabled, sale products will not assign points to your users'),
            'default' => false,
            'on_text' => __('True'),
            'off_text' => __('False')
        ],
        'assignment'
    );

    // Cancelled orders
    $settings->add_toggle_field(
        $prefix . 'cancel_order',
        __('Remove earned points if order is cancelled'),
        [
            'description' => __('Enable if you want to remove earned points when an order is cancelled'),
            'default' => true,
            'on_text' => __('True'),
            'off_text' => __('False')
        ],
        'assignment'
    );

    // Reassign points refunded orders
    $settings->add_toggle_field(
        $prefix . 'refund_order_reassign',
        __('Reassign points when an order is refunded'),
        [
            'description' => __('Enable if you want to reassign all the redeemed points to a customer when an order is refunded'),
            'default' => false,
            'on_text' => __('True'),
            'off_text' => __('False')
        ],
        'assignment'
    );

    // Remove points refunded orders
    $settings->add_toggle_field(
        $prefix . 'refund_order',
        __('Remove earned points if order is refunded'),
        [
            'description' => __('Enable to remove points when applying a total or partial refund of the order'),
            'default' => true,
            'on_text' => __('True'),
            'off_text' => __('False')
        ],
        'assignment'
    );

    // Coupon points
    $settings->add_toggle_field(
        $prefix . 'order_coupons',
        __('Do not assign points to the full order amount if a coupon is used'),
        [
            'description' => __('Enable this option if you do not want users to earn points on a full order amount if they use a coupon. Instead, they will only earn points on the amount minus the coupon discount. For example, the order total is $30 minus a $10 coupon discount, so the user earns points only on a $20 order value'),
            'default' => true,
            'on_text' => __('True'),
            'off_text' => __('False')
        ],
        'assignment'
    );

    // Redeeming points
    $settings->add_toggle_field(
        $prefix . 'order_redeemin',
        __('Do not assign points to orders in which the user is redeeming points'),
        [
            'description' => __('Enable to not assign points to orders in which the user redeems points'),
            'default' => true,
            'on_text' => __('True'),
            'off_text' => __('False')
        ],
        'assignment'
    );

    // Rounding points
    $settings->add_toggle_field(
        $prefix . 'assign_rounding',
        __('Do not assign points to orders in which the user is redeeming points'),
        [
            'description' => __('Select how to round the points. For example, if points are 1.5 and Round Up is selected, points will be 2. If Round Down is selected, points will be 1'),
            'default' => true,
            'on_text' => __('Round up'),
            'off_text' => __('Round down')
        ],
        'assignment'
    );

    // Expire points
    $settings->add_toggle_field(
        $prefix . 'assign_expiring',
        __('Set an expiration time for points'),
        [
            'description' => __('Enable if you want to set an expiration time on points assigned to users'),
            'default' => false,
            'on_text' => __('Expire'),
            'off_text' => __('No Expiration')
        ],
        'assignment'
    );

    // Point duration
    $settings->add_number_field(
        $prefix . 'points_duration',
        __('Points will expire after'),
        [
            'description' =>  __('Set a default expiration on points earned in your shop'),
            'default' => 12,
            'min' => 1,
            // 'max' => 9999,
            'step' => 1
        ],
        'assignment'
    );

    $settings->add_select_field(
        $prefix . 'points_duration_format',
        __('Expiry timeframe'),
        [
            'description' => __('Format for expiry timeframe'),
            'default' => 'months',
            'options' => [
                'days' => __('Days'),
                'months' => __('Months'),
            ]
        ],
        'assignment'
    );

}
add_action('init', 'simpli_rewards_admin_page_init');
```

### Post and Page Fields
Have added 2 new fields, one for retrieving posts and one for retrieving pages.<br/>
Both fields have support to be multiselect or singular
#### Page Field
```
 $settings->add_page_field(
    'new_page',
    __('Pages'),
    [
        'description' => __(''),
        'multi' => true // omit or set to false for singular select
    ],
    'dashboard'
);
```
#### Post Field
The post field can also be filtered by post type and taxonomy
```
$settings->add_post_field(
    'new_post',
    __('Posts'),
    [
        'description' => __(''),
        'multi' => true, // omit or set to false for singular select
        'post_type' => 'product', // define post type default is post
        'taxonomy' => 'product_cat', // define taxonomy default is category but only used if terms defined
        'field' => 'slug' // what to filter by term_id, slug or name. Default is slug but only used if terms defined
        'terms' => ['decor', 'music'], // omit to not filter by taxonomy, can be singular or array
    ],
    'dashboard'
);
```

### Create Custom Fields
To create a new custom field there is a hook, here is an example on how to create a page picker ```do_action('wp_settings_generator_render_field_' . $field['type'], $field, $name, $value, $this);``` where you replace the ```$field['type']``` with what you want it to be
Here is an example to create a page picker<br/>
```
function simpli_add_custom_field_page_picker($field, $name, $value) {
    $class = !empty($field['class']) ? $field['class'] . ' wp-settings-select' : 'wp-settings-select';
    $attrs = isset($field['attributes']) ? $field['attributes'] : [];
    $attr_string = '';
    foreach ($attrs as $attr => $val) {
        $attr_string .= sprintf(' %s="%s"', esc_attr($attr), esc_attr($val));
    }

    $pages = get_pages();
    ?>
    <select
        id="<?php echo esc_attr($field['id']); ?>"
        name="<?php echo esc_attr($name); ?>"
        class="<?php echo esc_attr($class); ?>"
        <?php echo $attr_string; ?>>
        <option value=""><?= __('Choose a page') ?></option>
        <?php foreach ($pages as $page) : ?>
            <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($value, $page->ID); ?>> // value is the page id
                <?php echo esc_html($page->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}
add_action('wp_settings_generator_render_field_page_picker', 'simpli_add_custom_field_page_picker', 10, 4); // page_picker is the field type
```
And then to include it in your settings page it would look like<br/>
```
$settings->add_field(
    'page_id', // Field ID
    __('Homepage'), // Label
    'page_picker', // Custom field type created in action
    [
        'description' => __('Select the page to be used as the homepage.'), // Optional description
    ],
    'customisations' // Tab ID (optional)
 );
```