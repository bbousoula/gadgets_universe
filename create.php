<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';
require_once 'image_upload.php';
$message = "";

try {
    $cat_stmt = $conn->prepare("SELECT DISTINCT category FROM gadgets ORDER BY category ASC");
    $cat_stmt->execute();
    $existing_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $existing_categories = ['Smartphones', 'Audio', 'Wearables', 'Accessories'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name        = trim($_POST['name']);
    $brand       = trim($_POST['brand']);
    $buying_price  = trim($_POST['buying_price']);
    $selling_price = trim($_POST['selling_price']);
    $quantity    = intval($_POST['quantity']);
    $description = trim($_POST['description']);

    $uploaded   = handle_image_uploads($_FILES['image_files'] ?? []);
    $manualUrls = parse_image_urls($_POST['image_urls'] ?? '');
    $image_url  = combine_image_sources([], $uploaded, $manualUrls);

    $category = ($_POST['category_select'] === 'NEW')
        ? trim($_POST['category_new'])
        : trim($_POST['category_select']);

    if (!empty($name) && !empty($brand) && !empty($buying_price) && !empty($selling_price) && !empty($category)) {
        try {
            $stmt = $conn->prepare("INSERT INTO gadgets (name, brand, buying_price, selling_price, category, quantity, image_url, description) VALUES (:name, :brand, :bprice, :sprice, :category, :quantity, :image_url, :description)");
            $stmt->execute([
                ':name'        => $name,
                ':brand'       => $brand,
                ':bprice'      => $buying_price,
                ':sprice'      => $selling_price,
                ':category'    => $category,
                ':quantity'    => $quantity,
                ':image_url'   => $image_url,
                ':description' => $description
            ]);
            header("Location: dashboard.php");
            exit();
        } catch(PDOException $e) {
            $message = "<p style='color: red;'>Database operation failure: " . $e->getMessage() . "</p>";
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
    <title>Add Gadget - Gadgets Universe</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
    /* Force the background */
    body {
        background-color: #dae0ea;
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
        .upload-btn { display: inline-flex; align-items: center; gap: 8px; background: #3498db; color: #fff; padding: 10px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: background 0.15s; }
        .upload-btn:hover { background: #2980b9; }
        .upload-hint { font-size: 0.78rem; color: #7f8c8d; margin-top: 6px; }
        #upload-preview, #url-preview { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
        #upload-preview img, #url-preview img { width: 56px; height: 56px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; background: #f8f8f8; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Add New Tech Gadget</h2>
        <?php echo $message; ?>
        <form action="create.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Item Description:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Manufacturer:</label>
                <input type="text" name="brand" required>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;">
                    <label>Buying Price (Rs):</label>
                    <input type="number" step="0.01" name="buying_price" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Selling Price (Rs):</label>
                    <input type="number" step="0.01" name="selling_price" required>
                </div>
            </div>

            <div class="form-group">
                <label>Inventory Quantity:</label>
                <input type="number" name="quantity" min="0" value="0" required>
            </div>

            <div class="form-group">
                <label>Product Category:</label>
                <select name="category_select" id="category_select" onchange="toggleNewCategoryField(this.value)" required>
                    <option value="" disabled selected>-- Select an Existing Category --</option>
                    <?php foreach ($existing_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                    <option value="NEW">+ Add New Category...</option>
                </select>
                <input type="text" name="category_new" id="category_new" placeholder="Type new category name here" style="display:none; margin-top:10px;">
            </div>

            <div class="form-group">
                <label>Product Image(s):</label>
                <label for="image_files" class="upload-btn">📁 Choose Image File(s)</label>
                <input type="file" id="image_files" name="image_files[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                <span id="file-count" class="upload-hint"></span>
                <div id="upload-preview"></div>
            </div>

            <div class="form-group">
                <label>Or paste image URL(s) — one per line:</label>
                <textarea name="image_urls" id="image_urls" rows="3" placeholder="images/existing.jpg"></textarea>
                <div id="url-preview"></div>
            </div>

            <div class="form-group">
                <label>Detailed Specifications:</label>
                <textarea name="description" rows="4"></textarea>
            </div>
            <button type="submit" class="btn">Commit to Database</button>
        </form>
        <p style="text-align: center; margin-top: 15px;"><a href="dashboard.php" style="text-decoration: none; color: #3498db;">Cancel & Go Back</a></p>
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