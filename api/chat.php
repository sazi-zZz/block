<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../models/Message.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$messageModel = new Message($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $max_size = ini_get('post_max_size');
        echo json_encode(['error' => "Total attachment size exceeds the server limit ($max_size). Please upload smaller files."]);
        exit;
    }

    $content = sanitizeInput($_POST['content'] ?? '');
    $receiver_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $media = null;

    if ($receiver_id) {
        $hasFiles = isset($_FILES['chat_file']) && !empty($_FILES['chat_file']['name'][0]);
        $hasFolder = isset($_FILES['chat_folder']) && !empty($_FILES['chat_folder']['name'][0]);

        if ($hasFiles || $hasFolder) {
            $totalSize = 0;
            if ($hasFiles) {
                $totalSize += is_array($_FILES['chat_file']['size']) ? array_sum($_FILES['chat_file']['size']) : $_FILES['chat_file']['size'];
            }
            if ($hasFolder) {
                $totalSize += is_array($_FILES['chat_folder']['size']) ? array_sum($_FILES['chat_folder']['size']) : $_FILES['chat_folder']['size'];
            }

            if ($totalSize > 100 * 1024 * 1024) {
                echo json_encode(['error' => 'Total attachment size exceeds 100MB limit.']);
                exit;
            }

            $totalFiles = 0;
            if ($hasFiles) {
                $totalFiles += is_array($_FILES['chat_file']['name']) ? count($_FILES['chat_file']['name']) : 1;
            }
            if ($hasFolder) {
                $totalFiles += is_array($_FILES['chat_folder']['name']) ? count($_FILES['chat_folder']['name']) : 1;
            }

            if ($totalFiles == 1 && $hasFiles) {
                // Just 1 regular file
                $fileName = is_array($_FILES['chat_file']['name']) ? $_FILES['chat_file']['name'][0] : $_FILES['chat_file']['name'];
                $tmpName = is_array($_FILES['chat_file']['tmp_name']) ? $_FILES['chat_file']['tmp_name'][0] : $_FILES['chat_file']['tmp_name'];
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $media = uniqid('file_') . '.' . $ext;
                $targetDir = '../public/files/chat_uploads/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                move_uploaded_file($tmpName, $targetDir . $media);
            }
            else {
                // Zip multiple files / folders
                $media = uniqid('archive_') . '.zip';
                $targetDir = '../public/files/chat_uploads/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $zipPath = $targetDir . $media;
                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                    if ($hasFiles) {
                        $count = is_array($_FILES['chat_file']['name']) ? count($_FILES['chat_file']['name']) : 1;
                        for ($i = 0; $i < $count; $i++) {
                            $tmp = is_array($_FILES['chat_file']['tmp_name']) ? $_FILES['chat_file']['tmp_name'][$i] : $_FILES['chat_file']['tmp_name'];
                            $name = is_array($_FILES['chat_file']['name']) ? $_FILES['chat_file']['name'][$i] : $_FILES['chat_file']['name'];
                            if (is_array($_FILES['chat_file']['error']) ? $_FILES['chat_file']['error'][$i] === UPLOAD_ERR_OK : $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
                                $zip->addFile($tmp, $name);
                            }
                        }
                    }
                    if ($hasFolder) {
                        $count = is_array($_FILES['chat_folder']['name']) ? count($_FILES['chat_folder']['name']) : 1;
                        $paths = $_POST['folder_paths'] ?? [];
                        for ($i = 0; $i < $count; $i++) {
                            $tmp = is_array($_FILES['chat_folder']['tmp_name']) ? $_FILES['chat_folder']['tmp_name'][$i] : $_FILES['chat_folder']['tmp_name'];
                            $name = is_array($_FILES['chat_folder']['name']) ? $_FILES['chat_folder']['name'][$i] : $_FILES['chat_folder']['name'];
                            $error = is_array($_FILES['chat_folder']['error']) ? $_FILES['chat_folder']['error'][$i] : $_FILES['chat_folder']['error'];
                            if ($error === UPLOAD_ERR_OK) {
                                $relativePath = isset($paths[$i]) ? $paths[$i] : $name;
                                $zip->addFile($tmp, ltrim($relativePath, '/'));
                            }
                        }
                    }
                    $zip->close();
                }
                else {
                    $media = null;
                }
            }
        }
    }

    if (!empty($content) || $media) {
        if ($receiver_id) {
            $success = $messageModel->createPrivateMessage($_SESSION['user_id'], $receiver_id, $content, $media);
        }
        else {
            $success = $messageModel->createGlobalMessage($_SESSION['user_id'], $content);
        }

        if ($success) {
            echo json_encode(['success' => true]);
        }
        else {
            echo json_encode(['error' => 'Failed to send']);
        }
    }
    else {
        echo json_encode(['error' => 'Empty message']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    if ($target_user_id) {
        $messages = $messageModel->getPrivateMessages($_SESSION['user_id'], $target_user_id, 50);
        // Mark as read — is_read column confirmed present in live DB
        $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
        $updateStmt->execute([$_SESSION['user_id'], $target_user_id]);
    }
    else {
        $messages = $messageModel->getGlobalMessages(50);
    }

    // Add current user ID to messages for frontend styling
    foreach ($messages as &$msg) {
        $msg['is_mine'] = ($msg['sender_id'] == $_SESSION['user_id']);
        $msg['time_ago'] = timeElapsedString($msg['created_at']);
        $msg['exact_time'] = date('M j, Y, g:i A', strtotime($msg['created_at']));
    }
    echo json_encode($messages);
    exit;
}