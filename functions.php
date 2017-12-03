<?php
$field_id_origin = 'access_control_allow_origin';
$field_id_webhook = 'webhook_url';

function echo_input(array $args) {
  $id = $args['id'];
  $value = esc_html(get_option($id));
  echo "<input name=\"$id\" id=\"$id\" type=\"text\" value=\"$value\" class=\"regular-text code\">";
}

// 「設定」>「一般」>「WP REST APIへのリクエストを許可するオリジン」
// 「設定」>「一般」>「保存時にリクエストを送るWebhookのURL」
add_filter('admin_init', function() {
  global $field_id_origin;
  global $field_id_webhook;
  add_settings_field(
    $field_id_origin,
    'WP REST APIへのリクエストを許可するオリジン',
    'echo_input',
    'general',
    'default',
    ['id' => $field_id_origin]
  );
  add_settings_field(
    $field_id_webhook,
    '保存時にリクエストを送るWebhookのURL',
    'echo_input',
    'general',
    'default',
    ['id' => $field_id_webhook]
  );
  register_setting('general', $field_id_origin);
  register_setting('general', $field_id_webhook);
});

// WP REST APIを利用するリクエストでのみ発火するフック
add_action('rest_api_init', function() {
  // rest_pre_serve_requestフィルターから
  // 元々設定されている関数rest_send_cors_headersを解除
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
  // rest_pre_serve_requestフィルターに新しく関数を設定
  add_filter('rest_pre_serve_request', function($value) {
    global $field_id_origin;
    // リクエスト元のオリジンを取得
    $origin = get_http_origin();
    // リクエスト許可ドメイン一覧の中にリクエスト元のオリジンが存在する場合
    if ($origin && in_array(
      $origin,
      // リクエスト許可ドメインのリスト
      // 「設定」>「一般」>「WP REST APIへのリクエストを許可するオリジン」
      // $originは末尾の/が無いことに注意
      explode(',', str_replace(' ', '', get_option($field_id_origin)))
    )) {
      // リクエスト元のオリジンをAccess-Control-Allow-Originに設定
      header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
      header('Access-Control-Allow-Methods: GET, OPTIONS');
      header('Access-Control-Allow-Credentials: true');
    }
    return $value;
  });
}, 15);

// 保存時にWebhookにリクエストを送る
// 「設定」>「一般」>「保存時にリクエストを送るWebhookのURL」
add_action('save_post', function() {
  global $field_id_webhook;
  $urls = explode(',', str_replace(' ', '', get_option($field_id_webhook)));
  foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, []);
    curl_exec($ch);
    curl_close($ch);
  }
});
