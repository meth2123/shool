<?php
function tableExists($link, $table) {
    $result = $link->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

function columnExists($link, $table, $column) {
    $result = $link->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function getDashboardStats($link, $admin_id = null) {
    $stats = [
        'students' => 0,
        'teachers' => 0,
        'courses' => 0,
        'classes' => 0,
        'my_classes' => 0 // Classes créées par l'admin connecté
    ];

    // Compter les étudiants
    if (tableExists($link, 'students')) {
        $has_created_by = columnExists($link, 'students', 'created_by');
        $sql = ($admin_id && $has_created_by)
            ? "SELECT COUNT(*) as count FROM students WHERE created_by = ?"
            : "SELECT COUNT(*) as count FROM students";
        
        if ($admin_id && $has_created_by) {
            $stmt = $link->prepare($sql);
            $stmt->bind_param("s", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $link->query($sql);
        }
        
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['students'] = $row['count'];
        }
    }

    // Compter les enseignants
    if (tableExists($link, 'teachers')) {
        $has_created_by = columnExists($link, 'teachers', 'created_by');
        $sql = ($admin_id && $has_created_by)
            ? "SELECT COUNT(*) as count FROM teachers WHERE created_by = ?"
            : "SELECT COUNT(*) as count FROM teachers";
        
        if ($admin_id && $has_created_by) {
            $stmt = $link->prepare($sql);
            $stmt->bind_param("s", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $link->query($sql);
        }
        
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['teachers'] = $row['count'];
        }
    }

    // Compter les cours
    if (tableExists($link, 'course')) {
        $has_created_by = columnExists($link, 'course', 'created_by');
        $sql = ($admin_id && $has_created_by)
            ? "SELECT COUNT(*) as count FROM course WHERE created_by = ?"
            : "SELECT COUNT(*) as count FROM course";
        
        if ($admin_id && $has_created_by) {
            $stmt = $link->prepare($sql);
            $stmt->bind_param("s", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $link->query($sql);
        }
        
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['courses'] = $row['count'];
        }
    }

    // Compter toutes les classes
    if (tableExists($link, 'class')) {
        $sql = "SELECT COUNT(*) as count FROM class";
        $result = $link->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['classes'] = $row['count'];
        }

        // Compter les classes créées par l'admin connecté
        if ($admin_id) {
            $has_created_by = columnExists($link, 'class', 'created_by');
            if ($has_created_by) {
                $sql = "SELECT COUNT(*) as count FROM class WHERE created_by = ?";
                $stmt = $link->prepare($sql);
                $stmt->bind_param("s", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['my_classes'] = $row['count'];
                }
            }
        }
    }

    return $stats;
}
?> 