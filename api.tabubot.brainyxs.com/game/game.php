<?php
include '../init.php';
$db = new mysql();
$result = $db->query("SELECT * FROM card")->fetch_all(MYSQLI_ASSOC);
echo json_encode($result);