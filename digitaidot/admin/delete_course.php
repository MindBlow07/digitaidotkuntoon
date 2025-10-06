<?php
// DigiTaidot Kuntoon! - Kurssin poisto
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Tarkista admin-oikeudet
$functions->requireAdmin();

// Käsittele kurssin poisto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)($_POST['course_id'] ?? 0);
    
    if ($course_id) {
        $success = $functions->deleteCourse($course_id);
        
        if ($success) {
            header('Location: index.php?success=course_deleted');
            exit();
        } else {
            header('Location: index.php?error=course_delete_failed');
            exit();
        }
    }
}

// Jos ei POST-pyyntöä, ohjaa takaisin
header('Location: index.php');
exit();
?>
