<?php
session_id('test_session');
session_start();
$_SESSION['user_type'] = 'mukhyashikshak';
$_SESSION['user_id'] = 1;
$_SESSION['shakha_id'] = 1;
session_write_close();
echo "Session created.\n";
