# Notes

## Changes to WP

### Adding the custom image editor

Add in `wp-includes/media.php` on

- line 2479:
```php
require_once ABSPATH . 'wp-content/themes/swp-wpperfect/class-wp-image-editor-imagick-swp.php';
```
- line 2489:
```php
$implementations = apply_filters( 'wp_image_editors', array('WP_Image_Editor_Imagick_Swp', 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );
```

## Plugins

### RICG Responsive Images

- Link: <https://wordpress.org/plugins/ricg-responsive-images/>
- Why: srcset (functions)

### WP Retina 2x

- Link: <https://wordpress.org/plugins/wp-retina-2x/>
- Why: Generating @2x-Images

### NOPE

#### EWWW Image Optimizer

- Link: <https://wordpress.org/plugins/ewww-image-optimizer/changelog/>
- Why: Progressive JPEGs, avoiding GD (handles color spaces wrong)
