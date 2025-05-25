<?php
// Script to check database collations
require_once __DIR__ . '/service/mysqlcon.php';

// Check if the database connection is established
if (!$link) {
    die("Database connection failed");
}

// Function to get table and column collations
function getCollations($link, $tables) {
    $result = [];
    
    foreach ($tables as $table) {
        $query = "SHOW FULL COLUMNS FROM $table";
        $stmt = mysqli_query($link, $query);
        
        if (!$stmt) {
            echo "Error querying table $table: " . mysqli_error($link) . "\n";
            continue;
        }
        
        while ($row = mysqli_fetch_assoc($stmt)) {
            $result[] = [
                'table' => $table,
                'column' => $row['Field'],
                'collation' => $row['Collation']
            ];
        }
    }
    
    return $result;
}

// Tables involved in the query
$tables = ['students', 'student_teacher_course', 'course', 'class'];

// Get collation information
$collations = getCollations($link, $tables);

// Display collation information
echo "<h1>Database Collation Information</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table</th><th>Column</th><th>Collation</th></tr>";

foreach ($collations as $info) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($info['table']) . "</td>";
    echo "<td>" . htmlspecialchars($info['column']) . "</td>";
    echo "<td>" . htmlspecialchars($info['collation'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Now let's specifically check the columns involved in the problematic query
$criticalColumns = [
    ['students', 'id'],
    ['students', 'classid'],
    ['student_teacher_course', 'student_id'],
    ['student_teacher_course', 'course_id'],
    ['student_teacher_course', 'grade_type'],
    ['student_teacher_course', 'grade_number'],
    ['student_teacher_course', 'semester']
];

echo "<h2>Critical Columns for the Query</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table</th><th>Column</th><th>Collation</th></tr>";

foreach ($criticalColumns as $column) {
    $table = $column[0];
    $col = $column[1];
    
    $query = "SHOW FULL COLUMNS FROM $table WHERE Field = '$col'";
    $stmt = mysqli_query($link, $query);
    
    if (!$stmt) {
        echo "Error querying column $col in table $table: " . mysqli_error($link) . "\n";
        continue;
    }
    
    $row = mysqli_fetch_assoc($stmt);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($table) . "</td>";
    echo "<td>" . htmlspecialchars($col) . "</td>";
    echo "<td>" . htmlspecialchars($row['Collation'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
