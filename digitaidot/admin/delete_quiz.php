<?php
// DigiTaidot Kuntoon! - Kokeen poisto
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Tarkista admin-oikeudet
$functions->requireAdmin();

// Käsittele kokeen poisto
$quiz_id = (int)($_GET['id'] ?? 0);

if ($quiz_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        
        header('Location: quizzes.php?success=quiz_deleted');
        exit();
    } catch(PDOException $e) {
        error_log("Kokeen poistovirhe: " . $e->getMessage());
        header('Location: quizzes.php?error=quiz_delete_failed');
        exit();
    }
}

// Jos ei ID:tä, ohjaa takaisin
header('Location: quizzes.php');
exit();
?>
