<?php
session_start();
unset($_SESSION['tournament_editor']);
session_regenerate_id(true);
header('Location: ../login.php');
exit;