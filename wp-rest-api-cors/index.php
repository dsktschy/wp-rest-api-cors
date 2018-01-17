<?php
/*
Plugin Name: WP REST API CORS
Plugin URI: https://github.com/dsktschy/wp-rest-api-cors
Description: WP REST API CORS allows use of REST API only for the specified origins.
Version: 1.0.0
Author: dsktschy
Author URI: https://github.com/dsktschy
License: GPL2
*/

// Add fields to the setting page
add_filter('admin_init', function() {
  add_settings_field(
    WpRestApiCors::$fieldId,
    preg_match('/^ja/', get_option('WPLANG')) ?
      'REST APIへのリクエストを許可するオリジン' :
      'Origins allowed requests to REST API',
    ['WpRestApiCors', 'echoField'],
    WpRestApiCors::$fieldPage,
    'default',
    ['id' => WpRestApiCors::$fieldId]
  );
  register_setting(WpRestApiCors::$fieldPage, WpRestApiCors::$fieldId);
});

// Allow use of REST API only for the specified origins
// Note: There's no slash at the end of the request origin
add_action('rest_api_init', function() {
  if (get_option(WpRestApiCors::$fieldId) === '') return;
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
  add_filter('rest_pre_serve_request', function($value) {
    $origin = get_http_origin();
    if ($origin && in_array($origin, explode(',', str_replace(
      ' ', '', get_option(WpRestApiCors::$fieldId)
    )))) {
      header('Access-Control-Allow-Origin: ' . esc_url($origin));
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Credentials: true');
    }
    return $value;
  });
}, 15);

// Class as a namespace
class WpRestApiCors
{
  static public $fieldId = 'wp_rest_api_cors';
  static public $fieldPage = 'general';
  // Outputs an input element with initial value
  static public function echoField(array $args)
  {
    $id = $args['id'];
    $value = esc_html(get_option($id));
    echo "<input name=\"$id\" id=\"$id\" type=\"text\" value=\"$value\" class=\"regular-text code\">";
  }
}
