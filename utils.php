<?php
require_once "pdo.php";
function flashMessages() {

    if (isset($_SESSION['error'])) {
        echo('<p class="flash" style="color: red;">'.htmlentities($_SESSION['error'])."</p>\n");
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo('<p class="flash" style="color: green;">'.htmlentities($_SESSION['success'])."</p>\n");
        unset($_SESSION['success']);
            }
    if (isset($_SESSION['failure'])) {
        echo('<p class="flash" style="color: red;">'.htmlentities($_SESSION['failure'])."</p>\n");
        unset($_SESSION['failure']);
    }
         
}


function getFormData(&$email, &$name) {
    // Initialize variables with default values
   if (isset($_SESSION['form_data'])) {
        $name = isset($_SESSION['form_data']['name']) ? $_SESSION['form_data']['name'] : '';
        $email = isset($_SESSION['form_data']['email']) ? $_SESSION['form_data']['email'] : '';
        unset($_SESSION['form_data']);

        }

}

function validatePos() {
    for ($i = 1; $i <= 9; $i++) {
        if (!isset($_POST['year' . $i])) continue;
        if (!isset($_POST['desc' . $i])) continue;
        $year = $_POST['year' . $i];
        $desc = $_POST['desc' . $i];
        if (strlen($year) == 0 || strlen($desc) == 0) {
            return "All position fields are required";
        }

        if (!is_numeric($year)) {
            return "Position year must be numeric";
        }
    }
    return true;
}


function fileProcess($filegiven) {

    // Validate file
    $allowedTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];
    $maxFileSize = 40 * 1024 * 1024;
    $file = $filegiven;

    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['failure'] = "Invalid file type. Only PDF, DOCX, TXT, JPEG, PNG, and GIF files are allowed";
        header("Location: add.php");
        exit();
    }
    
    if ($file['size'] > $maxFileSize) {
        $failure = "File too large. Maximum size is 40MB";
        $_SESSION['failure'] = $failure;
        header("Location: add.php");
        exit();
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $failure = "Error uploading file";
        $_SESSION['failure'] = $failure;
        header("Location: add.php");
        exit();
    }

    // Handle file upload
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        $failure = "Failed to save file";
        $_SESSION['failure'] = $failure;
        header("Location: add.php");
        exit();
    }
    return $filePath;

}

function loginSecurity() {
    if (!isset($_SESSION['name'])) {
    $_SESSION['error'] = "You are not logged in, please log in";
    header('Location: login.php');
    exit();
    }
}


function checkSummaryOwnership($pdo, $summary_id, $user_id) {
    // Ensure summary_id is numeric to prevent injection
    if (!is_numeric($summary_id)) {
        $_SESSION['error'] = "Invalid summary ID";
        header("Location: index.php");
        exit();
    }

    // Query to check ownership
    $stmt = $pdo->prepare("SELECT user_id FROM summaries WHERE summary_id = :id");
    $stmt->execute([':id' => $summary_id]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if summary exists and user owns it
    if ($summary === false || $summary['user_id'] !== $user_id) {
        $_SESSION['error'] = "You do not have permission to access this summary";
        header("Location: index.php");
        exit();
    }

    // Return true if ownership is confirmed
    return true;
}