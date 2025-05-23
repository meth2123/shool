<?php
include_once('../../../service/mysqlcon.php');

// Disable foreign key checks
$link->query("SET FOREIGN_KEY_CHECKS=0");

// Create temporary table
$link->query("CREATE TABLE IF NOT EXISTS course_temp LIKE course");
$link->query("ALTER TABLE course_temp MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");

// Copy data
$link->query("INSERT INTO course_temp (name, teacherid, classid, created_by) SELECT name, teacherid, classid, created_by FROM course");

// Drop original table
$link->query("DROP TABLE course");

// Rename temp table
$link->query("RENAME TABLE course_temp TO course");

// Re-enable foreign key checks
$link->query("SET FOREIGN_KEY_CHECKS=1");

echo "Mise à jour de la structure de la table course terminée.<br>";
?> 