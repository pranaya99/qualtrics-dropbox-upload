<?php
// save.php — Qualtrics → Railway → Dropbox

// ---------- CORS (REQUIRED FOR QUALTRICS) ----------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// ---------- DEBUG ----------
error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("FILES: " . print_r($_FILES, true));
error_log("POST: " . print_r($_POST, true));

// ---------- FILE CHECK ----------
if (!isset($_FILES["video-blob"])) {
  http_response_code(400);
  echo "No video file received.";
  exit;
}

// ---------- DROPBOX TOKEN ----------
$token = getenv("DROPBOX_ACCESS_TOKEN");
if (!$token) {
  http_response_code(500);
  echo "Dropbox token not set.";
  exit;
}

// ---------- PARTICIPANT ----------
$responseId = $_POST["response_id"] ?? "UNKNOWN_PARTICIPANT";

// ---------- DROPBOX PATH ----------
$baseFolder = "/Qualtrics_Recordings";
$participantFolder = $baseFolder . "/" . $responseId;
$filename = $_FILES["video-blob"]["name"];
$dropboxPath = $participantFolder . "/" . $filename;

// ---------- READ FILE ----------
$fileData = file_get_contents($_FILES["video-blob"]["tmp_name"]);

// ---------- UPLOAD ----------
$ch = curl_init("https://content.dropboxapi.com/2/files/upload");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer $token",
  "Content-Type: application/octet-stream",
  "Dropbox-API-Arg: " . json_encode([
    "path" => $dropboxPath,
    "mode" => "add",
    "autorename" => true
  ])
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
  http_response_code(500);
  echo "Dropbox upload failed.";
  exit;
}

echo "OK";

