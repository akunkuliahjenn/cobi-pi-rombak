<?php
// process/simpan_resep_produk.php
// File ini menangani logika penyimpanan/pembaruan/penghapusan item resep produk.

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$redirectUrl = '/cornerbites-sia/pages/resep_produk.php'; // Default redirect

try {
    $conn = $db;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'save_recipe';

        if ($action === 'update_product_info') {
            // Handle product info update
            $product_id = $_POST['product_id'] ?? null;
            $production_yield = (int) ($_POST['production_yield'] ?? 1);
            $production_time_hours = (float) ($_POST['production_time_hours'] ?? 1);
            $sale_price = (float) ($_POST['sale_price'] ?? 0);

            if ($product_id) {
                $redirectUrl .= '?product_id=' . htmlspecialchars($product_id);
            }

            // Validasi
            $errors = [];
            if (empty($product_id)) { $errors[] = 'Produk belum dipilih.'; }
            if ($production_yield <= 0) { $errors[] = 'Hasil produksi harus lebih besar dari 0.'; }
            if ($production_time_hours <= 0) { $errors[] = 'Waktu produksi harus lebih besar dari 0.'; }
            if ($sale_price < 0) { $errors[] = 'Harga jual tidak boleh negatif.'; }

            if (!empty($errors)) {
                $_SESSION['resep_message'] = ['text' => implode('<br>', $errors), 'type' => 'error'];
                header("Location: " . $redirectUrl);
                exit();
            }

            // Update product info
            $stmt = $conn->prepare("UPDATE products SET production_yield = ?, production_time_hours = ?, sale_price = ?, updated_at = CURRENT_TIMESTAMP() WHERE id = ?");
            if ($stmt->execute([$production_yield, $production_time_hours, $sale_price, $product_id])) {
                $_SESSION['resep_message'] = ['text' => 'Info produk berhasil diperbarui!', 'type' => 'success'];
            } else {
                $_SESSION['resep_message'] = ['text' => 'Gagal memperbarui info produk.', 'type' => 'error'];
            }
            header("Location: " . $redirectUrl);
            exit();
        }

        // Handle recipe item save/update
        $recipe_item_id = $_POST['recipe_item_id'] ?? null;
        $product_id = $_POST['product_id'] ?? null;
        $raw_material_id = $_POST['raw_material_id'] ?? null;
        $quantity_used = (float) ($_POST['quantity_used'] ?? 0);
        $unit_measurement = trim($_POST['unit_measurement'] ?? '');

        // Set redirect URL kembali ke halaman resep produk yang sama
        if ($product_id) {
            $redirectUrl .= '?product_id=' . htmlspecialchars($product_id);
        }

        // Validasi dasar
        $errors = [];
        if (empty($product_id)) { $errors[] = 'Produk belum dipilih.'; }
        if (empty($raw_material_id)) { $errors[] = 'Bahan baku/kemasan belum dipilih.'; }
        if ($quantity_used <= 0) { $errors[] = 'Jumlah yang digunakan harus lebih besar dari 0.'; }
        if (empty($unit_measurement)) { $errors[] = 'Satuan pengukuran resep tidak boleh kosong.'; }

        if (!empty($errors)) {
            $_SESSION['resep_message'] = ['text' => implode('<br>', $errors), 'type' => 'error'];
            header("Location: " . $redirectUrl);
            exit();
        }

        if ($recipe_item_id) {
            // Update Item Resep
            $stmt = $conn->prepare("UPDATE product_recipes SET raw_material_id = ?, quantity_used = ?, unit_measurement = ?, updated_at = CURRENT_TIMESTAMP() WHERE id = ? AND product_id = ?");
            if ($stmt->execute([$raw_material_id, $quantity_used, $unit_measurement, $recipe_item_id, $product_id])) {
                $_SESSION['resep_message'] = ['text' => 'Item resep berhasil diperbarui!', 'type' => 'success'];
            } else {
                $_SESSION['resep_message'] = ['text' => 'Gagal memperbarui item resep.', 'type' => 'error'];
            }
        } else {
            // Tambah Item Resep Baru
            // Cek duplikasi: tidak boleh ada bahan baku/kemasan yang sama untuk produk yang sama
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM product_recipes WHERE product_id = ? AND raw_material_id = ?");
            $stmtCheck->execute([$product_id, $raw_material_id]);
            if ($stmtCheck->fetchColumn() > 0) {
                $_SESSION['resep_message'] = ['text' => 'Item ini sudah ada dalam resep produk ini. Silakan edit jika ingin mengubah jumlah.', 'type' => 'error'];
                header("Location: " . $redirectUrl);
                exit();
            }

            $stmt = $conn->prepare("INSERT INTO product_recipes (product_id, raw_material_id, quantity_used, unit_measurement) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$product_id, $raw_material_id, $quantity_used, $unit_measurement])) {
                // Otomatis mengurangi stok bahan baku/kemasan berdasarkan quantity yang digunakan
                $stmtGetMaterial = $conn->prepare("SELECT current_stock, default_package_quantity, name FROM raw_materials WHERE id = ?");
                $stmtGetMaterial->execute([$raw_material_id]);
                $materialInfo = $stmtGetMaterial->fetch(PDO::FETCH_ASSOC);

                if ($materialInfo) {
                    $currentStock = $materialInfo['current_stock'];
                    $packageQuantity = $materialInfo['default_package_quantity'];
                    $materialName = $materialInfo['name'];

                    // Hitung berapa kemasan yang dibutuhkan untuk quantity yang digunakan
                    $packagesNeeded = ceil($quantity_used / $packageQuantity);

                    // Update stok (kurangi kemasan yang dibutuhkan)
                    $newStock = max(0, $currentStock - $packagesNeeded);

                    $stmtUpdateStock = $conn->prepare("UPDATE raw_materials SET current_stock = ? WHERE id = ?");
                    $stmtUpdateStock->execute([$newStock, $raw_material_id]);

                    $_SESSION['resep_message'] = ['text' => "Item resep baru berhasil ditambahkan! Stok {$materialName} berkurang {$packagesNeeded} kemasan (sisa: {$newStock} kemasan).", 'type' => 'success'];
                } else {
                    $_SESSION['resep_message'] = ['text' => 'Item resep baru berhasil ditambahkan!', 'type' => 'success'];
                }
            } else {
                $_SESSION['resep_message'] = ['text' => 'Gagal menambahkan item resep baru.', 'type' => 'error'];
            }
        }
        header("Location: " . $redirectUrl);
        exit();

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $recipe_item_id_to_delete = (int) $_GET['id'];
        $product_id_for_redirect = $_GET['product_id'] ?? null; // Dapatkan product_id untuk redirect

        if ($product_id_for_redirect) {
            $redirectUrl .= '?product_id=' . htmlspecialchars($product_id_for_redirect);
        }

        // Ambil info resep yang akan dihapus terlebih dahulu
        $stmtGetRecipe = $conn->prepare("SELECT pr.quantity_used, rm.current_stock, rm.default_package_quantity, rm.name FROM product_recipes pr JOIN raw_materials rm ON pr.raw_material_id = rm.id WHERE pr.id = ?");
        $stmtGetRecipe->execute([$recipe_item_id_to_delete]);
        $recipeInfo = $stmtGetRecipe->fetch(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("DELETE FROM product_recipes WHERE id = ?");
        if ($stmt->execute([$recipe_item_id_to_delete])) {
            if ($recipeInfo) {
                // Kembalikan stok yang sudah digunakan
                $quantityUsed = $recipeInfo['quantity_used'];
                $currentStock = $recipeInfo['current_stock'];
                $packageQuantity = $recipeInfo['default_package_quantity'];
                $materialName = $recipeInfo['name'];

                // Hitung berapa kemasan yang dikembalikan
                $packagesReturn = ceil($quantityUsed / $packageQuantity);
                $newStock = $currentStock + $packagesReturn;

                // Update stok (tambah kemasan yang dikembalikan)
                $stmtUpdateStock = $conn->prepare("UPDATE raw_materials SET current_stock = ? WHERE id = (SELECT raw_material_id FROM product_recipes WHERE id = ? LIMIT 1)");
                $stmtUpdateStock->execute([$newStock, $recipe_item_id_to_delete]);

                $_SESSION['resep_message'] = ['text' => "Item resep berhasil dihapus! Stok {$materialName} dikembalikan {$packagesReturn} kemasan (total: {$newStock} kemasan).", 'type' => 'success'];
            } else {
                $_SESSION['resep_message'] = ['text' => 'Item resep berhasil dihapus!', 'type' => 'success'];
            }
        } else {
            $_SESSION['resep_message'] = ['text' => 'Gagal menghapus item resep.', 'type' => 'error'];
        }
        header("Location: " . $redirectUrl);
        exit();

    } else {
        // Jika diakses langsung tanpa POST/GET yang valid, redirect ke halaman resep utama
        header("Location: " . $redirectUrl);
        exit();
    }

} catch (PDOException $e) {
    error_log("Error simpan/hapus resep produk: " . $e->getMessage());
    $_SESSION['resep_message'] = ['text' => 'Terjadi kesalahan sistem: ' . $e->getMessage(), 'type' => 'error'];
    header("Location: " . $redirectUrl);
    exit();
}