<?php
// TAR UMT's faculties and centres, used to populate the Faculty dropdown on
// registration and the account page instead of a free-text field.
function tarumt_faculties() {
    return [
        'Faculty of Accountancy, Finance and Business',
        'Faculty of Applied Sciences',
        'Faculty of Computing and Information Technology',
        'Faculty of Built Environment',
        'Faculty of Engineering and Technology',
        'Faculty of Communication and Creative Industries',
        'Faculty of Social Science and Humanities',
        'Centre for Pre-University Studies',
        'Centre for Postgraduate Studies and Research',
        'Centre for Continuing and Professional Education',
        'Centre for Business Incubation and Entrepreneurial Ventures',
        'SME Centre',
        'Student Career Development Centre',
        'Institute of Social Economic Research (ISER)',
    ];
}

// Fixed list of bookable hourly time slots, used to populate the Time Slot
// dropdown on the booking form instead of a free-text field.
function campus_time_slots() {
    return [
        '08:00 - 09:00',
        '09:00 - 10:00',
        '10:00 - 11:00',
        '11:00 - 12:00',
        '14:00 - 15:00',
        '15:00 - 16:00',
        '16:00 - 17:00',
        '17:00 - 18:00',
        '18:00 - 19:00',
        '19:00 - 20:00',
        '20:00 - 21:00',
    ];
}

// True if a time slot (e.g. "09:00 - 10:00") on the given date has already
// started relative to now - used to block booking a room/equipment slot
// that's already passed when the date is today (a future date is never "in
// the past" no matter the slot).
function is_slot_in_past($date, $timeSlot) {
    $startTime = trim(explode(' - ', $timeSlot)[0] ?? '');
    if ($startTime === '') {
        return false;
    }
    $slotStart = strtotime($date . ' ' . $startTime);
    return $slotStart !== false && $slotStart < time();
}

// Library overdue fine: RM0.50 per day late. Computed against the actual return
// date if the book has already been returned, or against today if it's still out -
// so a returned-but-late book keeps its fine on record instead of it disappearing
// once returned.
function book_fine_amount($due_date, $returned_at) {
    $rate = 0.50;
    $endDate = $returned_at ? date('Y-m-d', strtotime($returned_at)) : date('Y-m-d');
    $daysLate = (int)((strtotime($endDate) - strtotime($due_date)) / 86400);
    $daysLate = max(0, $daysLate);
    return $daysLate * $rate;
}

// Equipment overdue fine: RM0.50 per 30 minutes (or part thereof) late, with a
// 30-minute grace period after the booked time slot's end time. Computed against
// the actual return timestamp if already returned, or against now if it's still
// out - same "keeps its fine on record" behaviour as book_fine_amount().
function equipment_fine_amount($loan_date, $time_slot, $returned_at) {
    $rate = 0.50;
    $parts = explode(' - ', $time_slot);
    $endTime = trim(end($parts));
    $slotEnd = strtotime($loan_date . ' ' . $endTime);
    if ($slotEnd === false) {
        return 0;
    }

    $cutoff = $slotEnd + 30 * 60;
    $endReference = $returned_at ? strtotime($returned_at) : time();
    $secondsLate = $endReference - $cutoff;
    if ($secondsLate <= 0) {
        return 0;
    }

    $blocksLate = (int)ceil($secondsLate / (30 * 60));
    return $blocksLate * $rate;
}

// Falls back to a neutral placeholder until an admin uploads a real photo.
//
// An S3-stored photo's image_url is already a full https:// URL - returned
// as-is. A local-disk photo's image_url is root-relative ("/uploads/xxx.jpg")
// and gets turned into a path relative to the current script instead, because
// this app may be hosted as a subdirectory alongside sibling apps (not at the
// web server's document root) - a leading "/uploads/..." would then resolve
// to the wrong app's uploads folder (or nowhere).
function entity_image_url($row) {
    if (!empty($row['image_url'])) {
        if (str_starts_with($row['image_url'], 'https://') || str_starts_with($row['image_url'], 'http://')) {
            return $row['image_url'];
        }

        $relative = ltrim($row['image_url'], '/');
        $prefix = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : '';

        $path = __DIR__ . '/' . $relative;
        $version = is_file($path) ? '?v=' . filemtime($path) : '';
        return $prefix . $relative . $version;
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">'
         . '<rect width="100%" height="100%" fill="#e0e8e3"/>'
         . '<text x="50%" y="50%" font-size="18" fill="#647169" text-anchor="middle" dy=".3em">No photo yet</text>'
         . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Validates an uploaded photo, then stores it either on S3 (if AWS_S3_BUCKET
// is configured, see config.php) or on local disk (the default). Returns
// [webPath, error] - webPath is either a full S3 https:// URL or a
// root-relative "/uploads/xxx.jpg" path, or null if no file was uploaded or
// it failed.
function handle_image_upload($file, $uploadDir, $prefix = 'photo') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Image upload failed. Please try again.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return [null, 'Image must be smaller than 5MB.'];
    }

    // Check the actual file content, not just the extension/MIME the browser
    // claims, so a renamed .php file can't slip through.
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return [null, 'The uploaded file is not a valid image.'];
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowedMimes[$imageInfo['mime']])) {
        return [null, 'Only JPG, PNG, GIF or WEBP images are allowed.'];
    }

    $filename = uniqid($prefix . '_', true) . '.' . $allowedMimes[$imageInfo['mime']];

    if (AWS_S3_BUCKET !== '') {
        return s3_put_object($filename, file_get_contents($file['tmp_name']), $imageInfo['mime']);
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        return [null, 'Could not save the uploaded image.'];
    }

    return ['/uploads/' . $filename, null];
}

// Deletes a previously uploaded image, from S3 or local disk depending on
// which one image_url points at.
function delete_image_file($imageUrl, $uploadDir) {
    if (!$imageUrl) {
        return;
    }
    if (str_starts_with($imageUrl, 'https://') || str_starts_with($imageUrl, 'http://')) {
        s3_delete_object($imageUrl);
        return;
    }
    if (str_starts_with($imageUrl, '/uploads/')) {
        $path = $uploadDir . '/' . basename($imageUrl);
        if (is_file($path)) {
            unlink($path);
        }
    }
}

// ============================================================================
// S3 upload support (Signature Version 4, no AWS SDK/Composer dependency).
// Only used when AWS_S3_BUCKET is set in config.php - local disk is the
// default and needs none of this. The signing logic here is verified
// byte-for-byte against AWS's own published SigV4 test suite.
// ============================================================================

// Builds the canonical request + the list of header names that were signed,
// per the SigV4 spec: https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
function s3_canonical_request($method, $path, $headers, $payloadHash) {
    $sorted = $headers;
    ksort($sorted);
    $canonicalHeaders = '';
    foreach ($sorted as $name => $value) {
        $canonicalHeaders .= strtolower($name) . ':' . trim($value) . "\n";
    }
    $signedHeaders = implode(';', array_map('strtolower', array_keys($sorted)));
    $canonicalRequest = implode("\n", [$method, $path, '', $canonicalHeaders, $signedHeaders, $payloadHash]);
    return [$canonicalRequest, $signedHeaders];
}

// Signs an S3 request and returns [host, headers] with the Authorization
// header already filled in.
function s3_sign($method, $bucket, $region, $key, $payload, $credentials) {
    $host = "$bucket.s3.$region.amazonaws.com";
    $amzDate = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    $payloadHash = hash('sha256', $payload);

    $headers = [
        'Host' => $host,
        'X-Amz-Date' => $amzDate,
        'X-Amz-Content-Sha256' => $payloadHash,
    ];
    if (!empty($credentials['token'])) {
        $headers['X-Amz-Security-Token'] = $credentials['token'];
    }

    [$canonicalRequest, $signedHeaders] = s3_canonical_request($method, '/' . $key, $headers, $payloadHash);

    $service = 's3';
    $credentialScope = "$dateStamp/$region/$service/aws4_request";
    $stringToSign = implode("\n", [
        'AWS4-HMAC-SHA256',
        $amzDate,
        $credentialScope,
        hash('sha256', $canonicalRequest),
    ]);

    $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $credentials['secret_key'], true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $signingKey = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $signingKey);

    $headers['Authorization'] = "AWS4-HMAC-SHA256 Credential={$credentials['access_key']}/$credentialScope, "
        . "SignedHeaders=$signedHeaders, Signature=$signature";

    return [$host, $headers];
}

// Fetches temporary credentials for the IAM role attached to this EC2
// instance via the instance metadata service (IMDSv2). Returns null quickly
// (short timeouts) when there's no such role to ask - e.g. running locally -
// so this never hangs a request.
function s3_instance_credentials() {
    $tokenCtx = stream_context_create(['http' => [
        'method' => 'PUT',
        'header' => "X-aws-ec2-metadata-token-ttl-seconds: 21600\r\n",
        'timeout' => 1,
        'ignore_errors' => true,
    ]]);
    $token = @file_get_contents('http://169.254.169.254/latest/api/token', false, $tokenCtx);
    if ($token === false || $token === '') {
        return null;
    }

    $metaCtx = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => "X-aws-ec2-metadata-token: $token\r\n",
        'timeout' => 1,
        'ignore_errors' => true,
    ]]);
    $roleName = trim((string)@file_get_contents(
        'http://169.254.169.254/latest/meta-data/iam/security-credentials/',
        false,
        $metaCtx
    ));
    if ($roleName === '') {
        return null;
    }

    $credsJson = @file_get_contents(
        "http://169.254.169.254/latest/meta-data/iam/security-credentials/$roleName",
        false,
        $metaCtx
    );
    $creds = $credsJson ? json_decode($credsJson, true) : null;
    if (!isset($creds['AccessKeyId'], $creds['SecretAccessKey'], $creds['Token'])) {
        return null;
    }

    return [
        'access_key' => $creds['AccessKeyId'],
        'secret_key' => $creds['SecretAccessKey'],
        'token' => $creds['Token'],
    ];
}

// Uploads $data to S3 under $key. Returns [publicUrl, error], matching the
// shape handle_image_upload()'s callers already expect.
function s3_put_object($key, $data, $contentType) {
    $credentials = s3_instance_credentials();
    if (!$credentials) {
        return [null, 'Could not reach S3: no IAM role credentials available from this instance. '
            . 'Is an IAM role with s3:PutObject attached to this EC2 instance?'];
    }

    [$host, $headers] = s3_sign('PUT', AWS_S3_BUCKET, AWS_S3_REGION, $key, $data, $credentials);
    $headers['Content-Type'] = $contentType;

    $headerLines = '';
    foreach ($headers as $name => $value) {
        $headerLines .= "$name: $value\r\n";
    }

    $context = stream_context_create(['http' => [
        'method' => 'PUT',
        'header' => $headerLines,
        'content' => $data,
        'timeout' => 20,
        'ignore_errors' => true,
    ]]);

    @file_get_contents("https://$host/$key", false, $context);
    $status = s3_response_status($http_response_header ?? []);

    if ($status !== 200) {
        return [null, "S3 upload failed (HTTP $status)."];
    }

    return ["https://$host/$key", null];
}

// Deletes an object previously uploaded to S3, given the URL stored in
// image_url. Does nothing if the URL doesn't belong to the configured
// bucket (defensive - shouldn't happen in practice).
function s3_delete_object($url) {
    $host = AWS_S3_BUCKET . '.s3.' . AWS_S3_REGION . '.amazonaws.com';
    $prefix = "https://$host/";
    if (!str_starts_with($url, $prefix)) {
        return;
    }
    $key = substr($url, strlen($prefix));

    $credentials = s3_instance_credentials();
    if (!$credentials) {
        return;
    }

    [, $headers] = s3_sign('DELETE', AWS_S3_BUCKET, AWS_S3_REGION, $key, '', $credentials);
    $headerLines = '';
    foreach ($headers as $name => $value) {
        $headerLines .= "$name: $value\r\n";
    }

    $context = stream_context_create(['http' => [
        'method' => 'DELETE',
        'header' => $headerLines,
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);
    @file_get_contents("https://$host/$key", false, $context);
}

// Pulls the HTTP status code out of the $http_response_header array that
// PHP's stream wrapper populates after a file_get_contents() HTTP request.
function s3_response_status($responseHeaders) {
    foreach ($responseHeaders as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) {
            return (int)$m[1];
        }
    }
    return 0;
}
