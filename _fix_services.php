<?php
require_once 'config.php';
require_once 'includes/db.php';
$db = db();
$db->query("UPDATE service_categories SET name='Invitation', slug='invitation', description='Custom printed invitations for weddings, birthdays, and events' WHERE slug='souvenirs'");
echo $db->affected_rows . " row(s) updated\n";
$r = $db->query("SELECT name, slug FROM service_categories WHERE category='basic' ORDER BY sort_order");
while ($row = $r->fetch_assoc()) echo $row['slug'] . ' => ' . $row['name'] . "\n";
