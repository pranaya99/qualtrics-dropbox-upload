<?php
// save.php
// Receives video blob from Qualtrics and uploads to Dropbox
// Folder structure: /Qualtrics_Recordings/{ResponseID}/filename.webm

error_log("POST received. FILES=" . print_r($_FILES, true));

header("Access-Control-Allow-Origin: *");

if (!isset($_FILES["video-blob"])) {
  http_response_code(400);
  echo "No video file received.";
  exit;
}

$token = getenv("DROPBOX_ACCESS_TOKEN");
if (!$token) {
  http_response_code(500);
  echo "Dropbox token not set.";
  exit;
}

// Get Qualtrics ResponseID (sent from JS)
$responseId = $_POST["response_id"] ?? "UNKNOWN_PARTICIPANT";

// Dropbox paths
$baseFolder = "/Qualtrics_Recordings";
$participantFolder = $baseFolder . "/" . $responseId;
$filename = $_FILES["video-blob"]["name"];
$dropboxPath = $participantFolder . "/" . $filename;

// Read file
$fileData = file_get_contents($_FILES["video-blob"]["tmp_name"]);

// Upload to Dropbox
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
