<?php
include_once('main.php');
include_once('../../service/mysqlcon.php');

if(!empty($_GET)) {
    try {
        // Validate input
        $courseid = trim($_GET['courseid']);
        $classid = trim($_GET['classid']);
        $teacherid = trim($_GET['teacherid']);
        $courseName = trim($_GET['coursename']);

        if (empty($courseid) || empty($classid) || empty($teacherid) || empty($courseName)) {
            throw new Exception("All fields are required");
        }

        // Get students for the class using prepared statement
        $sql = "SELECT id FROM students WHERE classid = ?";
        $stmt = $link->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparing student query: " . $link->error);
        }

        $stmt->bind_param("s", $classid);
        if (!$stmt->execute()) {
            throw new Exception("Error fetching students: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $studentCount = $result->num_rows;

        if ($studentCount === 0) {
            throw new Exception("No students found in the specified class");
        }

        // Start transaction
        $link->begin_transaction();

        try {
            // Prepare course insertion statement
            $sql = "INSERT INTO course (id, name, teacherid, studentid, classid) VALUES (?, ?, ?, ?, ?)";
            $stmt = $link->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing course insertion: " . $link->error);
            }

            // Insert course for each student
            while ($row = $result->fetch_assoc()) {
                $studentid = $row['id'];
                $stmt->bind_param("sssss", $courseid, $courseName, $teacherid, $studentid, $classid);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error assigning course to student $studentid: " . $stmt->error);
                }
            }

            // Commit transaction
            $link->commit();
            echo "<div class='alert alert-success'>Course assigned successfully to $studentCount students</div>";

        } catch (Exception $e) {
            $link->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>
