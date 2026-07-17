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

// True if a time slot label (e.g. "09:00 - 10:00") on the given date has
// already started relative to now - used to block booking a slot that's
// already passed when the booking date is today (a future date is never
// "in the past" no matter the slot).
function is_slot_in_past($date, $timeSlotLabel) {
    $startTime = trim(explode(' - ', $timeSlotLabel)[0] ?? '');
    if ($startTime === '') {
        return false;
    }
    $slotStart = strtotime($date . ' ' . $startTime);
    return $slotStart !== false && $slotStart < time();
}

// Falls back to a neutral placeholder until an admin uploads a real photo
// via the admin Facilities form.
function facility_image_url($facility) {
    if (!empty($facility['image_url'])) {
        return $facility['image_url'];
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300">'
         . '<rect width="100%" height="100%" fill="#e1e0d9"/>'
         . '<text x="50%" y="50%" font-size="18" fill="#898781" text-anchor="middle" dy=".3em">No photo yet</text>'
         . '</svg>';

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Falls back to null (no placeholder) - the layout diagram is optional
// supplementary content, not something every facility needs to show.
function facility_layout_url($facility) {
    return !empty($facility['layout_url']) ? $facility['layout_url'] : null;
}

// Validates and stores an uploaded facility photo (or layout diagram) on
// local disk. Returns [webPath, error] - webPath is a root-relative URL like
// "/uploads/facility_xxx.jpg", or null if no file was uploaded or it failed.
function handle_facility_image_upload($file, $uploadDir, $prefix = 'facility') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Image upload failed. Please try again.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return [null, 'Image must be smaller than 5MB.'];
    }

    // Check the actual file content, not just the extension/MIME the
    // browser claims, so a renamed .php file can't slip through.
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

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid($prefix . '_', true) . '.' . $allowedMimes[$imageInfo['mime']];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        return [null, 'Could not save the uploaded image.'];
    }

    return ['/uploads/' . $filename, null];
}

// Deletes a previously uploaded local image file (ignores S3/external URLs,
// which aren't ours to delete).
function delete_facility_image_file($imageUrl, $uploadDir) {
    if ($imageUrl && str_starts_with($imageUrl, '/uploads/')) {
        $path = $uploadDir . '/' . basename($imageUrl);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
