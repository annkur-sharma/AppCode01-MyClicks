<?php
$uploadDir = '/app/photos/public/';
$logFile = '/app/logs/upload.log';
$errorLogFile = '/app/logs/upload-errors.log';

function logEntry($msg, $file) {
    file_put_contents($file, "[" . date("Y-m-d H:i:s") . "] $msg\n", FILE_APPEND);
}

function compressImage($source, $destination) {
    $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
    $tmpCompressed = $destination . ".compressed";

    if ($ext === 'png') {
        exec("pngquant --quality=65-80 --output \"$tmpCompressed\" --force \"$source\"");
        if (file_exists($tmpCompressed)) {
            rename($tmpCompressed, $destination);
        } else {
            copy($source, $destination);
        }
    } elseif (in_array($ext, ['jpg', 'jpeg'])) {
        exec("jpegoptim --max=80 --strip-all \"$source\"");
        copy($source, $destination);
    } else {
        copy($source, $destination);
    }
}

function autoDeleteOldestIfLimitReached($dir, $maxFiles, $logFile) {
    $photos = array_merge(
    glob($dir . '*.jpg'),
    glob($dir . '*.jpeg'),
    glob($dir . '*.png'),
    glob($dir . '*.gif')
    );
    usort($photos, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    if (count($photos) >= $maxFiles) {
        $oldest = $photos[0];
        if (is_file($oldest)) {
            $deletedFile = basename($oldest);
            unlink($oldest);
            logEntry("Deleted oldest photo: $deletedFile to maintain {$maxFiles}-photo limit", $logFile);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $tmpName = $_FILES['photo']['tmp_name'];

    if (!is_uploaded_file($tmpName)) {
        logEntry("Upload error: not a valid file", $errorLogFile);
        die("Upload failed.");
    }

    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        logEntry("Upload error: unsupported file type '$ext'", $errorLogFile);
        die("Unsupported file type.");
    }

    // Auto-delete oldest photo if over limit
    autoDeleteOldestIfLimitReached($uploadDir, 10, $logFile);

    $newName = 'photo-' . bin2hex(random_bytes(5)) . '.' . $ext;
    $destPath = $uploadDir . $newName;

    compressImage($tmpName, $destPath);

    if (file_exists($destPath)) {
        logEntry("Uploaded: $newName", $logFile);
        header("Location: /");
        exit;
    } else {
        logEntry("Compression failed or file not saved: $newName", $errorLogFile);
        die("Upload failed during compression.");
    }
}
?>