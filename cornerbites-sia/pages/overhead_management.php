
<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

$overhead_costs = [];
$labor_costs = [];

try {
    $conn = $db;
    
    // Get overhead costs
    $stmt = $conn->query("SELECT * FROM overhead_costs ORDER BY name ASC");
    $overhead_costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get labor costs
    $stmt = $conn->query("SELECT * FROM labor_costs ORDER BY position_name ASC");
    $labor_costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error di Overhead Management: " . $e->getMessage());
}

$message = '';
$message_type = '';
if (isset($_SESSION['overhead_message'])) {
    $message = $_SESSION['overhead_message']['text'];
    $message_type = $_SESSION['overhead_message']['type'];
    unset($_SESSION['overhead_message']);
}
?>

<?php include_once __DIR__ . '/../includes/header.php'; ?>
<div class="flex h-screen bg-gradient-to-br from-gray-50 to-gray-100 font-sans">
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col overflow-hidden">
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Manajemen Biaya Overhead & Tenaga Kerja</h1>
                    <p class="text-gray-600">Kelola biaya overhead dan upah tenaga kerja untuk perhitungan HPP yang akurat</p>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg border-l-4 <?php echo ($message_type == 'success' ? 'bg-green-50 border-green-400 text-green-700' : 'bg-red-50 border-red-400 text-red-700'); ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Overhead Costs Section -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6">Biaya Overhead</h3>
                        
                        <!-- Add Overhead Form -->
                        <form action="/cornerbites-sia/process/simpan_overhead.php" method="POST" class="mb-6">
                            <input type="hidden" name="type" value="overhead">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Biaya</label>
                                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp)</label>
                                    <input type="number" name="amount" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Metode Alokasi</label>
                                    <select name="allocation_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="percentage">Percentage</option>
                                        <option value="per_unit">Per Unit</option>
                                        <option value="per_hour">Per Hour</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        Tambah Overhead
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                        </form>

                        <!-- Overhead List -->
                        <div class="space-y-3">
                            <?php foreach ($overhead_costs as $overhead): ?>
                            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($overhead['name']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($overhead['description']); ?></p>
                                    <p class="text-sm text-blue-600">Metode: <?php echo ucfirst($overhead['allocation_method']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-green-600">Rp <?php echo number_format($overhead['amount'], 0, ',', '.'); ?></p>
                                    <div class="flex space-x-2 mt-2">
                                        <button class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</button>
                                        <button class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Labor Costs Section -->
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6">Upah Tenaga Kerja</h3>
                        
                        <!-- Add Labor Form -->
                        <form action="/cornerbites-sia/process/simpan_overhead.php" method="POST" class="mb-6">
                            <input type="hidden" name="type" value="labor">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi/Jabatan</label>
                                    <input type="text" name="position_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Upah per Jam (Rp)</label>
                                    <input type="number" name="hourly_rate" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Tambah Posisi
                            </button>
                        </form>

                        <!-- Labor List -->
                        <div class="space-y-3">
                            <?php foreach ($labor_costs as $labor): ?>
                            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($labor['position_name']); ?></h4>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-green-600">Rp <?php echo number_format($labor['hourly_rate'], 0, ',', '.'); ?>/jam</p>
                                    <div class="flex space-x-2 mt-2">
                                        <button class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</button>
                                        <button class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
