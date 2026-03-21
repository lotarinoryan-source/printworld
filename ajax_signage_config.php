<?php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
require_once 'config.php';
require_once 'includes/db.php';
ob_clean();
header('Content-Type: application/json');
$db = db();
$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action === 'services') {
    $res = $db->query("SELECT id, name, slug, icon FROM service_categories WHERE category='signage' AND is_active=1 ORDER BY sort_order, id");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}
if ($action === 'config') {
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(isset($_GET['slug']) ? $_GET['slug'] : ''));
    if (!$slug) { echo json_encode(array('types'=>array(),'lights'=>array())); exit; }
    $st = $db->prepare("SELECT type_label FROM signage_type_options WHERE service_slug=? ORDER BY sort_order");
    $st->bind_param('s', $slug); $st->execute();
    $types = array(); foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) { $types[] = $r['type_label']; }
    $sl = $db->prepare("SELECT light_label FROM signage_light_options WHERE service_slug=? ORDER BY id");
    $sl->bind_param('s', $slug); $sl->execute();
    $lights = array(); foreach ($sl->get_result()->fetch_all(MYSQLI_ASSOC) as $r) { $lights[] = $r['light_label']; }
    if (empty($types))  { $types  = array('Single Face','Double Face','Single Frame','Double Face Frame','Special Design'); }
    if (empty($lights)) { $lights = array('Lighted','Non-lighted'); }
    echo json_encode(array('types'=>$types,'lights'=>$lights));
    exit;
}
echo json_encode(array('error'=>'Invalid action'));