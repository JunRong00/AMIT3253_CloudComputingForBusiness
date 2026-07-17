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
// Returns a path relative to the current script rather than a root-relative
// one, because this app may be hosted as a subdirectory alongside sibling
// apps (not at the web server's document root) - a leading "/uploads/..."
// would then resolve to the wrong app's uploads folder (or nowhere).
function entity_image_url($row) {
    if (!empty($row['image_url'])) {
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

// Validates and stores an uploaded photo on local disk. Returns [webPath, error] -
// webPath is a root-relative URL like "/uploads/xxx.jpg", or null if no file was
// uploaded or it failed.
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

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid($prefix . '_', true) . '.' . $allowedMimes[$imageInfo['mime']];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        return [null, 'Could not save the uploaded image.'];
    }

    return ['/uploads/' . $filename, null];
}

// Deletes a previously uploaded local image file.
function delete_image_file($imageUrl, $uploadDir) {
    if ($imageUrl && str_starts_with($imageUrl, '/uploads/')) {
        $path = $uploadDir . '/' . basename($imageUrl);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
