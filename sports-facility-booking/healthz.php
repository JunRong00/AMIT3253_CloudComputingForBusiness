<?php
// Lightweight target for an ALB health check. config.php already sends a
// 500 status and stops here if the database is unreachable, so reaching
// this line at all means the app can actually serve requests.
require 'config.php';
header('Content-Type: text/plain');
echo 'OK';
