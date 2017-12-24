<?php
(function() {
  // Redirect if specified
  $url = explode(',', str_replace(' ', '', get_option('redirect_url')))[0];
  if ($url !== '') {
    header('Location: ' . esc_url($url));
    exit;
  }
})();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title></title>
</head>
<body></body>
</html>
