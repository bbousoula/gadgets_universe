<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';
require_once 'image_upload.php';

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = (int)$_GET['id'];
$message = "";

$stmt = $conn->prepare("SELECT * FROM gadgets WHERE id = :id");
$stmt->execute([':id' => $id]);
$gadget = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gadget) {
    header("Location: dashboard.php");
    exit();
}

try {
    $cat_stmt = $conn->prepare("SELECT DISTINCT category FROM gadgets ORDER BY category ASC");
    $cat_stmt->execute();
    $existing_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $existing_categories = ['Smartphones', 'Audio', 'Wearables', 'Accessories'];
}

$existing_images = parse_stored_images($gadget['image_url'] ?? '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name        = trim($_POST['name']);
    $brand       = trim($_POST['brand']);
    $buying_price  = trim($_POST['buying_price']);
    $selling_price = trim($_POST['selling_price']);
    $quantity    = intval($_POST['quantity']);
    $description = trim($_POST['description']);

    $removed  = $_POST['remove_images'] ?? [];
    $kept     = array_values(array_filter($existing_images, fn($u) => !in_array($u, $removed, true)));

    $uploaded   = handle_image_uploads($_FILES['image_files'] ?? []);
    $manualUrls = parse_image_urls($_POST['image_urls'] ?? '');
    $image_url  = combine_image_sources($kept, $uploaded, $manualUrls);

    $category = ($_POST['category_select'] === 'NEW')
        ? trim($_POST['category_new'])
        : trim($_POST['category_select']);

    if (!empty($name) && !empty($brand) && !empty($buying_price) && !empty($selling_price) && !empty($category)) {
        try {
            $update_stmt = $conn->prepare("UPDATE gadgets SET name = :name, brand = :brand, buying_price = :bprice, selling_price = :sprice, category = :category, quantity = :quantity, image_url = :image_url, description = :description WHERE id = :id");
            $update_stmt->execute([
                ':name'        => $name,
                ':brand'       => $brand,
                ':bprice'      => $buying_price,
                ':sprice'      => $selling_price,
                ':category'    => $category,
                ':quantity'    => $quantity,
                ':image_url'   => $image_url,
                ':description' => $description,
                ':id'          => $id
            ]);
            header("Location: dashboard.php");
            exit();
        } catch(PDOException $e) {
            $message = "<p style='color: red;'>Failed modifying element structure: " . $e->getMessage() . "</p>";
        }
    } else {
        $message = "<p style='color: red;'>Please fulfill all required fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Gadget - Gadgets Universe</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
    /* Force the background */
    body {
        background-image: url('images/background-texture.jpg') !important;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-attachment: fixed !important;
    }
    
    /* Make wrappers transparent */
    .crud-container, 
    .admin-container, 
    .orders-container, 
    .auth-container {
        background: transparent !important;
    }

    /* Fix text colors for light background */
    h2, h3, p {
        color: #2c3e50 !important;
        text-shadow: none !important;
    }
</style>

    <style>
        select   { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background-color: #fff; margin-bottom: 5px; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; resize: vertical; font-family: inherit; }
        .upload-btn { display: inline-flex; align-items: center; gap: 8px; background: #3498db; color: #fff; padding: 10px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
        .upload-btn:hover { background: #2980b9; }
        .upload-hint { font-size: 0.78rem; color: #7f8c8d; margin-top: 6px; }
        .existing-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
        .existing-thumb { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 6px; border: 1px solid #ddd; border-radius: 6px; background: #fafafa; font-size: 0.72rem; color: #555; }
        .existing-thumb img { width: 72px; height: 72px; object-fit: cover; border-radius: 4px; background: #fff; }
        #upload-preview, #url-preview { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
        #upload-preview img, #url-preview img { width: 56px; height: 56px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; background: #f8f8f8; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Modify Gadget Specifications</h2>
        <?php echo $message; ?>
        <form action="update.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Item Description:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($gadget['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Manufacturer:</label>
                <input type="text" name="brand" value="<?php echo htmlspecialchars($gadget['brand']); ?>" required>
            </div>

            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>Buying Price (Rs):</label>
                    <input type="number" step="0.01" name="buying_price" value="<?php echo htmlspecialchars($gadget['buying_price']); ?>" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Selling Price (Rs):</label>
                    <input type="number" step="0.01" name="selling_price" value="<?php echo htmlspecialchars($gadget['selling_price']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Inventory Quantity:</label>
                <input type="number" name="quantity" min="0" value="<?php echo htmlspecialchars($gadget['quantity']); ?>" required>
            </div>

            <div class="form-group">
                <label>Product Category:</label>
                <select name="category_select" id="category_select" onchange="toggleNewCategoryField(this.value)" required>
                    <?php foreach ($existing_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($gadget['category'] === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                    <option value="NEW">+ Add New Category...</option>
                </select>
                <input type="text" name="category_new" id="category_new" placeholder="Type new category name here" style="display:none; margin-top:10px;">
            </div>

            <?php if (!empty($existing_images)): ?>
                <div class="form-group">
                    <label>Current Images (tick to remove):</label>
                    <div class="existing-grid">
                        <?php foreach ($existing_images as $img): ?>
                            <label class="existing-thumb">
                                <img src="<?php echo htmlspecialchars($img); ?>" onerror="this.style.opacity=0.2;">
                                <input type="checkbox" name="remove_images[]" value="<?php echo htmlspecialchars($img); ?>">
                                Remove
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Add New Image(s) — upload:</label>
                <label for="image_files" class="upload-btn">📁 Choose Image File(s)</label>
                <input type="file" id="image_files" name="image_files[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                <div id="upload-preview"></div>
            </div>

            <div class="form-group">
                <label>Or add image URL(s) — one per line:</label>
                <textarea name="image_urls" id="image_urls" rows="3" placeholder="images/new.jpg"></textarea>
                <div id="url-preview"></div>
            </div>

            <div class="form-group">
                <label>Description Details</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($gadget['description']); ?></textarea>
            </div>
            <button type="submit" class="btn">Save Modified Adjustments</button>
        </form>
        <p style="text-align: center; margin-top: 15px;"><a href="dashboard.php" style="text-decoration: none; color: #e74c3c;">Discard Changes</a></p>
    </div>

    <script>
        function toggleNewCategoryField(val) {
            const newCatInput = document.getElementById('category_new');
            newCatInput.style.display = (val === 'NEW') ? 'block' : 'none';
            newCatInput.required = (val === 'NEW');
        }
        document.getElementById('image_files').addEventListener('change', function (e) {
            const preview = document.getElementById('upload-preview');
            preview.innerHTML = '';
            Array.from(e.target.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = ev => {
                    const img = document.createElement('img');
                    img.src = ev.target.result;
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
</body>
</html>