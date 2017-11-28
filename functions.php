<?php
$new_settings_field_id = 'access_control_allow_origin';
function echo_input() {
  global $new_settings_field_id;
  $value = esc_html(get_option($new_settings_field_id));
  echo "<input name=\"$new_settings_field_id\" id=\"$new_settings_field_id\" type=\"text\" value=\"$value\" class=\"regular-text code\">";
}

// WP REST APIへのリクエストを許可するオリジンを「設定」>「一般」画面から設定可能にする
add_filter('admin_init', function() {
  global $new_settings_field_id;
  add_settings_field(
    $new_settings_field_id,
    'WP REST APIへのリクエストを許可するオリジン',
    'echo_input',
    'general'
  );
  register_setting('general', $new_settings_field_id);
});

// WP REST APIを利用するリクエストでのみ発火するフック
add_action('rest_api_init', function() {
  // rest_pre_serve_requestフィルターから
  // 元々設定されている関数rest_send_cors_headersを解除
  remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
  // rest_pre_serve_requestフィルターに新しく関数を設定
  add_filter('rest_pre_serve_request', function($value) {
    global $new_settings_field_id;
    // リクエスト元のオリジンを取得
    $origin = get_http_origin();
    // リクエスト許可ドメイン一覧の中にリクエスト元のオリジンが存在する場合
    if ($origin && in_array(
      $origin,
      // リクエスト許可ドメインのリスト
      // 「設定」>「一般」>「WP REST APIへのリクエストを許可するオリジン」
      // $originは末尾の/が無いことに注意
      explode(',', str_replace(' ', '', get_option($new_settings_field_id)))
    )) {
      // リクエスト元のオリジンをAccess-Control-Allow-Originに設定
      header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
      header('Access-Control-Allow-Methods: GET, OPTIONS');
      header('Access-Control-Allow-Credentials: true');
    }
    return $value;
  });
}, 15);
