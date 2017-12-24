<?php
$fields = [
  'origin' => [
    'id' => 'access_control_allow_origin',
    'title' => [
      'en' => 'Origins allowed requests to REST API',
      'ja' => 'REST APIへのリクエストを許可するオリジン'
    ],
    'callback' => 'echo_input',
    'page' => 'general',
    'section' => 'default'
  ],
  'webhook' => [
    'id' => 'webhook_url',
    'title' => [
      'en' => 'Webhook URL when saving posts',
      'ja' => '保存時にリクエストを送るWebhookのURL'
    ],
    'callback' => 'echo_input',
    'page' => 'general',
    'section' => 'default'
  ],
  'redirect' => [
    'id' => 'redirect_url',
    'title' => [
      'en' => 'Redirection URL for requests to pages',
      'ja' => 'ページへのリクエストに対するリダイレクトURL'
    ],
    'callback' => 'echo_input',
    'page' => 'general',
    'section' => 'default'
  ]
];

// Add fields to the setting page
add_filter('admin_init', function() {
  global $fields;
  foreach ($fields as $field) {
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
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
  add_filter('rest_pre_serve_request', function($value) {
    global $fields;
    $origin = get_http_origin();
    if ($origin && in_array(
      $origin,
      explode(',', str_replace(' ', '', get_option($fields['origin']['id'])))
    )) {
      header('Access-Control-Allow-Origin: ' . esc_url($origin));
      header('Access-Control-Allow-Methods: GET, OPTIONS');
      header('Access-Control-Allow-Credentials: true');
    }
    return $value;
  });
}, 15);

// Send a post request to webhooks when saving posts
add_action('save_post', function() {
  global $fields;
  $urls = explode(',', str_replace(' ', '', get_option($fields['webhook']['id'])));
  foreach ($urls as $url) {
    $ch = curl_init(esc_url($url));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, []);
    curl_exec($ch);
    curl_close($ch);
  }
});

function echo_input(array $args) {
  $id = $args['id'];
  $value = esc_html(get_option($id));
  echo "<input name=\"$id\" id=\"$id\" type=\"text\" value=\"$value\" class=\"regular-text code\">";
}
