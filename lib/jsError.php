<?php
mail(
    'dan@mojavelinux.com', 
    $_SERVER['SERVER_NAME'] . ' - Javascript Error', 
    '
Javascript Error Summary Report
-------------------------------
Error:   ' . $_GET['msg'] . '
Line:    ' . $_GET['line'] . '
Url:     ' . $_GET['url'] . '
Browser: ' . $_SERVER['HTTP_USER_AGENT'] . '
Date:    ' . date('Y-m-d H:i:s'),
    'From: webmaster@' . $_SERVER['SERVER_NAME'] . '
Reply-To: webmaster@' . $_SERVER['SERVER_NAME'] . '
X-Mailer: PHP/' . phpversion()
);
?>
