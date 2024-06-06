<?php
session_start();
session_destroy();
header("Location: /newrequirement/dash/index.html");
exit;
?>