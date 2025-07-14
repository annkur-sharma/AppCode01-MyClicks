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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $tmpName = $_FILES['photo']['tmp_name'];
    if (!is_uploaded_file($tmpName)) {
        logEntry("Upload error: not a valid file", $errorLogFile);
        die("Upload failed.");
    }

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $newName = 'photo-' . bin2hex(random_bytes(5)) . '.' . strtolower($ext);
    $destPath = $uploadDir . $newName;

    compressImage($tmpName, $destPath);

    if (file_exists($destPath)) {
        logEntry("Uploaded: $newName", $logFile);
        header("Location: /");
        exit;
    } else {
        logEntry("Compression failed: $newName", $errorLogFile);
        die("Upload failed during compression.");
    }
}
?>