
<?php
// process/simpan_resep_produk.php
// File ini menangani logika penyimpanan/pembaruan/penghapusan item resep produk dengan stok real-time

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

        // Ambil info bahan baku untuk validasi stok
        $stmtMaterial = $conn->prepare("SELECT name, current_stock, unit FROM raw_materials WHERE id = ?");
        $stmtMaterial->execute([$raw_material_id]);
        $materialInfo = $stmtMaterial->fetch(PDO::FETCH_ASSOC);

        if (!$materialInfo) {
            $_SESSION['resep_message'] = ['text' => 'Bahan baku/kemasan tidak ditemukan.', 'type' => 'error'];
            header("Location: " . $redirectUrl);
            exit();
        }

        if ($recipe_item_id) {
            // Update Item Resep - Perlu mengembalikan stok lama dan kurangi stok baru
            
            // Ambil quantity lama untuk mengembalikan stok
            $stmtOldQuantity = $conn->prepare("SELECT quantity_used FROM product_recipes WHERE id = ? AND product_id = ?");
            $stmtOldQuantity->execute([$recipe_item_id, $product_id]);
            $oldQuantity = $stmtOldQuantity->fetchColumn();

            if ($oldQuantity !== false) {
                // Kembalikan stok lama
                $stmtRestoreStock = $conn->prepare("UPDATE raw_materials SET current_stock = current_stock + ? WHERE id = ?");
                $stmtRestoreStock->execute([$oldQuantity, $raw_material_id]);

                // Cek apakah stok cukup untuk quantity baru
                $stmtCheckStock = $conn->prepare("SELECT current_stock FROM raw_materials WHERE id = ?");
                $stmtCheckStock->execute([$raw_material_id]);
                $currentStock = $stmtCheckStock->fetchColumn();

                if ($currentStock < $quantity_used) {
                    $_SESSION['resep_message'] = ['text' => "Stok {$materialInfo['name']} tidak mencukupi. Stok tersedia: {$currentStock} {$materialInfo['unit']}, diperlukan: {$quantity_used} {$unit_measurement}", 'type' => 'error'];
                    header("Location: " . $redirectUrl);
                    exit();
                }

                // Update resep dengan quantity baru
                $stmt = $conn->prepare("UPDATE product_recipes SET raw_material_id = ?, quantity_used = ?, unit_measurement = ?, updated_at = CURRENT_TIMESTAMP() WHERE id = ? AND product_id = ?");
                if ($stmt->execute([$raw_material_id, $quantity_used, $unit_measurement, $recipe_item_id, $product_id])) {
                    // Kurangi stok dengan quantity baru
                    $stmtUpdateStock = $conn->prepare("UPDATE raw_materials SET current_stock = current_stock - ? WHERE id = ?");
                    $stmtUpdateStock->execute([$quantity_used, $raw_material_id]);
                    
                    $_SESSION['resep_message'] = ['text' => "Item resep berhasil diperbarui! Stok {$materialInfo['name']} disesuaikan.", 'type' => 'success'];
                } else {
                    $_SESSION['resep_message'] = ['text' => 'Gagal memperbarui item resep.', 'type' => 'error'];
                }
            } else {
                $_SESSION['resep_message'] = ['text' => 'Data resep lama tidak ditemukan.', 'type' => 'error'];
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

            // Cek apakah stok cukup
            if ($materialInfo['current_stock'] < $quantity_used) {
                $_SESSION['resep_message'] = ['text' => "Stok {$materialInfo['name']} tidak mencukupi. Stok tersedia: {$materialInfo['current_stock']} {$materialInfo['unit']}, diperlukan: {$quantity_used} {$unit_measurement}", 'type' => 'error'];
                header("Location: " . $redirectUrl);
                exit();
            }

            // Tambahkan item resep baru
            $stmt = $conn->prepare("INSERT INTO product_recipes (product_id, raw_material_id, quantity_used, unit_measurement) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$product_id, $raw_material_id, $quantity_used, $unit_measurement])) {
                // Kurangi stok otomatis
                $stmtUpdateStock = $conn->prepare("UPDATE raw_materials SET current_stock = current_stock - ? WHERE id = ?");
                $stmtUpdateStock->execute([$quantity_used, $raw_material_id]);

                // Ambil stok terbaru untuk pesan
                $stmtNewStock = $conn->prepare("SELECT current_stock FROM raw_materials WHERE id = ?");
                $stmtNewStock->execute([$raw_material_id]);
                $newStock = $stmtNewStock->fetchColumn();

                $_SESSION['resep_message'] = ['text' => "Item resep baru berhasil ditambahkan! Stok {$materialInfo['name']} berkurang {$quantity_used} {$unit_measurement} (sisa: {$newStock} {$materialInfo['unit']}).", 'type' => 'success'];
            } else {
                $_SESSION['resep_message'] = ['text' => 'Gagal menambahkan item resep baru.', 'type' => 'error'];
            }
        }
        header("Location: " . $redirectUrl);
        exit();

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $recipe_item_id_to_delete = (int) $_GET['id'];
        $product_id_for_redirect = $_GET['product_id'] ?? null;

        if ($product_id_for_redirect) {
            $redirectUrl .= '?product_id=' . htmlspecialchars($product_id_for_redirect);
        }

        // Ambil info resep yang akan dihapus untuk mengembalikan stok
        $stmtGetRecipe = $conn->prepare("SELECT pr.quantity_used, pr.raw_material_id, rm.name, rm.unit FROM product_recipes pr JOIN raw_materials rm ON pr.raw_material_id = rm.id WHERE pr.id = ?");
        $stmtGetRecipe->execute([$recipe_item_id_to_delete]);
        $recipeInfo = $stmtGetRecipe->fetch(PDO::FETCH_ASSOC);

        if ($recipeInfo) {
            // Hapus item resep
            $stmt = $conn->prepare("DELETE FROM product_recipes WHERE id = ?");
            if ($stmt->execute([$recipe_item_id_to_delete])) {
                // Kembalikan stok
                $stmtUpdateStock = $conn->prepare("UPDATE raw_materials SET current_stock = current_stock + ? WHERE id = ?");
                $stmtUpdateStock->execute([$recipeInfo['quantity_used'], $recipeInfo['raw_material_id']]);

                // Ambil stok terbaru untuk pesan
                $stmtNewStock = $conn->prepare("SELECT current_stock FROM raw_materials WHERE id = ?");
                $stmtNewStock->execute([$recipeInfo['raw_material_id']]);
                $newStock = $stmtNewStock->fetchColumn();

                $_SESSION['resep_message'] = ['text' => "Item resep berhasil dihapus! Stok {$recipeInfo['name']} dikembalikan {$recipeInfo['quantity_used']} {$recipeInfo['unit']} (total: {$newStock} {$recipeInfo['unit']}).", 'type' => 'success'];
            } else {
                $_SESSION['resep_message'] = ['text' => 'Gagal menghapus item resep.', 'type' => 'error'];
            }
        } else {
            $_SESSION['resep_message'] = ['text' => 'Item resep tidak ditemukan.', 'type' => 'error'];
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
?>
