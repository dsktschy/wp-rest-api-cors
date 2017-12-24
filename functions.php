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

// 「設定」>「一般」>「REST APIへのリクエストを許可するオリジン」
// 「設定」>「一般」>「保存時にリクエストを送るWebhookのURL」
// 「設定」>「一般」>「ページへのリクエストに対するリダイレクトURL」
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

// WP REST APIを利用するリクエストでのみ発火するフック
add_action('rest_api_init', function() {
  // rest_pre_serve_requestフィルターから
  // 元々設定されている関数rest_send_cors_headersを解除
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
  // rest_pre_serve_requestフィルターに新しく関数を設定
  add_filter('rest_pre_serve_request', function($value) {
    global $fields;
    // リクエスト元のオリジンを取得
    $origin = get_http_origin();
    // リクエスト許可ドメイン一覧の中にリクエスト元のオリジンが存在する場合
    if ($origin && in_array(
      $origin,
      // リクエスト許可ドメインのリスト
      // 「設定」>「一般」>「WP REST APIへのリクエストを許可するオリジン」
      // $originは末尾の/が無いことに注意
      explode(',', str_replace(' ', '', get_option($fields['origin']['id'])))
    )) {
      // リクエスト元のオリジンをAccess-Control-Allow-Originに設定
      header('Access-Control-Allow-Origin: ' . esc_url($origin));
      header('Access-Control-Allow-Methods: GET, OPTIONS');
      header('Access-Control-Allow-Credentials: true');
    }
    return $value;
  });
}, 15);

// 保存時にWebhookにリクエストを送る
// 「設定」>「一般」>「保存時にリクエストを送るWebhookのURL」
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
