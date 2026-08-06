<?php

$url = "https://xrtemuwuseageaeeeeuq.supabase.co/storage/v1/bucket";
$key = "sb_publishable_te0VbMYKOOy_6rzDX8Nu9g_YrvMQzIO";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: {$key}",
    "Authorization: Bearer {$key}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "id" => "eie-storage",
    "name" => "eie-storage",
    "public" => true
]));

$res = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Create Bucket Status: " . $status . "\nResponse: " . $res . "\n\n";

// Test upload file
$uploadUrl = "https://xrtemuwuseageaeeeeuq.supabase.co/storage/v1/object/eie-storage/test.txt";
$ch2 = curl_init($uploadUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    "apikey: {$key}",
    "Authorization: Bearer {$key}",
    "Content-Type: text/plain",
    "x-upsert: true"
]);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, "Hola Supabase Storage EIE");

$res2 = curl_exec($ch2);
$status2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Upload File Status: " . $status2 . "\nResponse: " . $res2 . "\n";
