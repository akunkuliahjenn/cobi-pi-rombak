
<?php
// process/simpan_resep_produk.php
// Script untuk memproses operasi CRUD resep produk dengan dukungan overhead dan tenaga kerja manual

session_start();
require_once __DIR__ . '/../config/db.php';

// Initialize response variables
$response = ['success' => false, 'message' => ''];

try {
    $conn = $db;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
        throw new Exception('Method tidak diizinkan');
    }

    // Handle GET request untuk hapus
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $recipeId = (int)$_GET['id'];
        $productId = (int)$_GET['product_id'];
        
        $stmt = $conn->prepare("DELETE FROM product_recipes WHERE id = ?");
        if ($stmt->execute([$recipeId])) {
            $_SESSION['resep_message'] = [
                'text' => 'Item berhasil dihapus dari resep',
                'type' => 'success'
            ];
        } else {
            $_SESSION['resep_message'] = [
                'text' => 'Gagal menghapus item dari resep',
                'type' => 'error'
            ];
        }
        
        header("Location: ../pages/resep_produk.php?product_id=" . $productId);
        exit;
    }

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_product_info':
                handleUpdateProductInfo($conn);
                break;
                
            case 'add_manual_overhead':
                handleAddManualOverhead($conn);
                break;
                
            case 'add_manual_labor':
                handleAddManualLabor($conn);
                break;
                
            default:
                handleRecipeItem($conn);
                break;
        }
    }

} catch (Exception $e) {
    error_log("Error in simpan_resep_produk.php: " . $e->getMessage());
    $_SESSION['resep_message'] = [
        'text' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'type' => 'error'
    ];
    
    $redirectUrl = isset($_POST['product_id']) ? "../pages/resep_produk.php?product_id=" . $_POST['product_id'] : "../pages/resep_produk.php";
    header("Location: " . $redirectUrl);
    exit;
}

// Function untuk update informasi produk
function handleUpdateProductInfo($conn) {
    $productId = (int)$_POST['product_id'];
    $productionYield = (int)$_POST['production_yield'];
    $productionTimeHours = (float)$_POST['production_time_hours'];
    $salePrice = (float)$_POST['sale_price'];
    
    // Validasi input
    if ($productionYield <= 0 || $productionTimeHours <= 0 || $salePrice < 0) {
        throw new Exception('Data produk tidak valid');
    }
    
    $stmt = $conn->prepare("
        UPDATE products 
        SET production_yield = ?, production_time_hours = ?, sale_price = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$productionYield, $productionTimeHours, $salePrice, $productId])) {
        $_SESSION['resep_message'] = [
            'text' => 'Informasi produk berhasil diperbarui',
            'type' => 'success'
        ];
    } else {
        throw new Exception('Gagal memperbarui informasi produk');
    }
    
    header("Location: ../pages/resep_produk.php?product_id=" . $productId);
    exit;
}

// Function untuk menambah overhead manual
function handleAddManualOverhead($conn) {
    $productId = (int)$_POST['product_id'];
    $overheadId = (int)$_POST['overhead_id'];
    $customAmount = isset($_POST['custom_amount']) && $_POST['custom_amount'] !== '' ? (float)$_POST['custom_amount'] : null;
    
    // Cek apakah overhead sudah ada untuk produk ini
    $checkStmt = $conn->prepare("SELECT id FROM product_overheads WHERE product_id = ? AND overhead_cost_id = ?");
    $checkStmt->execute([$productId, $overheadId]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Overhead ini sudah ditambahkan untuk produk ini');
    }
    
    // Ambil data overhead
    $overheadStmt = $conn->prepare("SELECT name, amount, allocation_method FROM overhead_costs WHERE id = ? AND is_active = 1");
    $overheadStmt->execute([$overheadId]);
    $overhead = $overheadStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$overhead) {
        throw new Exception('Data overhead tidak ditemukan');
    }
    
    $finalAmount = $customAmount !== null ? $customAmount : $overhead['amount'];
    
    // Insert ke product_overheads
    $stmt = $conn->prepare("
        INSERT INTO product_overheads (product_id, overhead_cost_id, custom_amount, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$productId, $overheadId, $customAmount])) {
        $_SESSION['resep_message'] = [
            'text' => 'Overhead berhasil ditambahkan ke produk',
            'type' => 'success'
        ];
    } else {
        throw new Exception('Gagal menambahkan overhead');
    }
    
    header("Location: ../pages/resep_produk.php?product_id=" . $productId);
    exit;
}

// Function untuk menambah tenaga kerja manual
function handleAddManualLabor($conn) {
    $productId = (int)$_POST['product_id'];
    $laborId = (int)$_POST['labor_id'];
    $customHours = isset($_POST['custom_hours']) && $_POST['custom_hours'] !== '' ? (float)$_POST['custom_hours'] : null;
    
    // Cek apakah labor sudah ada untuk produk ini
    $checkStmt = $conn->prepare("SELECT id FROM product_labors WHERE product_id = ? AND labor_cost_id = ?");
    $checkStmt->execute([$productId, $laborId]);
    
    if ($checkStmt->fetch()) {
        throw new Exception('Tenaga kerja ini sudah ditambahkan untuk produk ini');
    }
    
    // Ambil data labor
    $laborStmt = $conn->prepare("SELECT position_name, hourly_rate FROM labor_costs WHERE id = ? AND is_active = 1");
    $laborStmt->execute([$laborId]);
    $labor = $laborStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$labor) {
        throw new Exception('Data tenaga kerja tidak ditemukan');
    }
    
    // Insert ke product_labors
    $stmt = $conn->prepare("
        INSERT INTO product_labors (product_id, labor_cost_id, custom_hours, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$productId, $laborId, $customHours])) {
        $_SESSION['resep_message'] = [
            'text' => 'Tenaga kerja berhasil ditambahkan ke produk',
            'type' => 'success'
        ];
    } else {
        throw new Exception('Gagal menambahkan tenaga kerja');
    }
    
    header("Location: ../pages/resep_produk.php?product_id=" . $productId);
    exit;
}

// Function untuk handle recipe item (tambah/edit)
function handleRecipeItem($conn) {
    $productId = (int)$_POST['product_id'];
    $rawMaterialId = (int)$_POST['raw_material_id'];
    $quantityUsed = (float)$_POST['quantity_used'];
    $unitMeasurement = trim($_POST['unit_measurement']);
    $recipeItemId = isset($_POST['recipe_item_id']) && $_POST['recipe_item_id'] !== '' ? (int)$_POST['recipe_item_id'] : null;
    
    // Validasi input
    if ($productId <= 0 || $rawMaterialId <= 0 || $quantityUsed <= 0 || empty($unitMeasurement)) {
        throw new Exception('Data resep tidak lengkap atau tidak valid');
    }
    
    // Validasi bahwa raw material exists
    $materialStmt = $conn->prepare("SELECT name FROM raw_materials WHERE id = ?");
    $materialStmt->execute([$rawMaterialId]);
    if (!$materialStmt->fetch()) {
        throw new Exception('Bahan baku/kemasan tidak ditemukan');
    }
    
    // Validasi bahwa product exists
    $productStmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $productStmt->execute([$productId]);
    if (!$productStmt->fetch()) {
        throw new Exception('Produk tidak ditemukan');
    }
    
    if ($recipeItemId) {
        // Update existing recipe item
        $stmt = $conn->prepare("
            UPDATE product_recipes 
            SET raw_material_id = ?, quantity_used = ?, unit_measurement = ?, updated_at = NOW() 
            WHERE id = ? AND product_id = ?
        ");
        
        if ($stmt->execute([$rawMaterialId, $quantityUsed, $unitMeasurement, $recipeItemId, $productId])) {
            $_SESSION['resep_message'] = [
                'text' => 'Item resep berhasil diperbarui',
                'type' => 'success'
            ];
        } else {
            throw new Exception('Gagal memperbarui item resep');
        }
    } else {
        // Check jika kombinasi product_id dan raw_material_id sudah ada
        $checkStmt = $conn->prepare("SELECT id FROM product_recipes WHERE product_id = ? AND raw_material_id = ?");
        $checkStmt->execute([$productId, $rawMaterialId]);
        
        if ($checkStmt->fetch()) {
            throw new Exception('Bahan ini sudah ada dalam resep. Gunakan fitur edit untuk mengubah jumlahnya.');
        }
        
        // Insert new recipe item
        $stmt = $conn->prepare("
            INSERT INTO product_recipes (product_id, raw_material_id, quantity_used, unit_measurement, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$productId, $rawMaterialId, $quantityUsed, $unitMeasurement])) {
            $_SESSION['resep_message'] = [
                'text' => 'Item berhasil ditambahkan ke resep',
                'type' => 'success'
            ];
        } else {
            throw new Exception('Gagal menambahkan item ke resep');
        }
    }
    
    header("Location: ../pages/resep_produk.php?product_id=" . $productId);
    exit;
}
?>
