<?php
/**
 * Parse a newline-separated string of image URLs into a clean array.
 */
function parse_image_urls(string $raw): array {
    $list = [];
    if (trim($raw) === '') return $list;
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $url) {
        $url = trim($url);
        if ($url !== '') $list[] = $url;
    }
    return $list;
}

/**
 * Handle one or more uploaded image files.
 * Returns an array of paths relative to the project root, e.g. ['images/abc_1a2b3c4d.jpg'].
 * Silently skips files that fail validation (wrong type, too big, upload error).
 */
function handle_image_uploads(
    array $files,
    string $uploadDir = 'images/',
    int $maxSize = 5 * 1024 * 1024   // 5 MB
): array {
    $saved = [];

    // The field is <input name="image_files[]" multiple>; PHP nests the arrays.
    // If the user chose nothing, $_FILES['image_files'] is ['name' => '', ...] (scalar).
    if (empty($files) || !isset($files['name']) || !is_array($files['name'])) {
        return $saved;
    }

    $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    $uploadDir = rtrim($uploadDir, '/') . '/';

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK)                continue;
        $tmp = $files['tmp_name'][$i];
        if (!is_uploaded_file($tmp))                              continue;
        if ($files['size'][$i] > $maxSize)                        continue;

        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true))                   continue;

        $mime = @mime_content_type($tmp);
        if (!in_array($mime, $allowedMime, true))                 continue;

        // Sanitise the base name, then make it unique so two uploads can't collide
        $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($files['name'][$i], PATHINFO_FILENAME));
        $base = substr($base ?: 'img', 0, 50);
        $newName = $base . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (move_uploaded_file($tmp, $uploadDir . $newName)) {
            $saved[] = $uploadDir . $newName;
        }
    }
    return $saved;
}

/**
 * Merge kept-existing + newly-uploaded + manually-pasted URLs into one comma-separated string.
 */
function combine_image_sources(array $kept = [], array $uploaded = [], array $urls = []): string {
    $all = array_merge($kept, $uploaded, $urls);
    $all = array_values(array_filter(array_map('trim', $all)));
    return empty($all) ? 'images/placeholder.png' : implode(',', $all);
}

/**
 * Split a stored image_url column into an array. Used by store.php too, if you like.
 */
function parse_stored_images(string $raw): array {
    $out = [];
    foreach (explode(',', $raw) as $url) {
        $url = trim($url);
        if ($url !== '') $out[] = $url;
    }
    return $out;
}