<?php
// Add fields to the setting page
add_filter('admin_init', function() {
  foreach (Restapi::$settingsFields as $field) {
    add_settings_field(
      $field['id'],
      $field['title'][preg_match('/^ja/', get_option('WPLANG')) ? 'ja' : 'en'],
      $field['callback'],
      $field['page'],
      $field['section'],
      ['id' => $field['id']]
    );
    register_setting($field['page'], $field['id']);
  }
});

// Allow use of REST API only for the specified origins
// Note: There's no slash at the end of the request origin
add_action('rest_api_init', function() {
  if (get_option(Restapi::$settingsFields['origin']['id']) === '') return;
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
  add_filter('rest_pre_serve_request', function($value) {
    $origin = get_http_origin();
    if ($origin && in_array($origin, explode(',', str_replace(
      ' ', '', get_option(Restapi::$settingsFields['origin']['id'])
    )))) {
      header('Access-Control-Allow-Origin: ' . esc_url($origin));
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Credentials: true');
    }
    return $value;
  });
}, 15);

// Send a post request to webhooks when saving posts
add_action('save_post', function() {
  $option = get_option(Restapi::$settingsFields['webhook']['id']);
  if ($option === '') return;
  $urls = explode(',', str_replace(' ', '', $option));
  foreach ($urls as $url) {
    $ch = curl_init(esc_url($url));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, []);
    curl_exec($ch);
    curl_close($ch);
  }
});

// Redirect requests to pages if specified
add_action('template_redirect', function() {
  $option = get_option(Restapi::$settingsFields['redirection']['id']);
  if ($option === '') return;
  $url = explode(',', str_replace(' ', '', $option))[0];
  header('Location: ' . esc_url($url), true, 301);
  exit;
});

// Class as a namespace
class Restapi
{
  static public $settingsFields = [
    'origin' => [
      'id' => 'access_control_allow_origin',
      'title' => [
        'en' => 'Origins allowed requests to REST API',
        'ja' => 'REST APIへのリクエストを許可するオリジン'
      ],
      'callback' => ['Restapi', 'echoInput'],
      'page' => 'general',
      'section' => 'default'
    ],
    'webhook' => [
      'id' => 'webhook_url',
      'title' => [
        'en' => 'Webhook URL when saving posts',
        'ja' => '保存時にリクエストを送るWebhookのURL'
      ],
      'callback' => ['Restapi', 'echoInput'],
      'page' => 'general',
      'section' => 'default'
    ],
    'redirection' => [
      'id' => 'redirect_url',
      'title' => [
        'en' => 'Redirection URL for requests to pages',
        'ja' => 'ページへのリクエストに対するリダイレクトURL'
      ],
      'callback' => ['Restapi', 'echoInput'],
      'page' => 'general',
      'section' => 'default'
    ]
  ];
  // Outputs an input element with initial value
  static public function echoInput(array $args)
  {
    $id = $args['id'];
    $value = esc_html(get_option($id));
    echo "<input name=\"$id\" id=\"$id\" type=\"text\" value=\"$value\" class=\"regular-text code\">";
  }
}
