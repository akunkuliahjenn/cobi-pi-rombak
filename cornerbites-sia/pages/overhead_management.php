
<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

// Pagination settings
$limit = 10;
$page_overhead = isset($_GET['page_overhead']) ? (int)$_GET['page_overhead'] : 1;
$page_labor = isset($_GET['page_labor']) ? (int)$_GET['page_labor'] : 1;
$offset_overhead = ($page_overhead - 1) * $limit;
$offset_labor = ($page_labor - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = " AND (name LIKE ? OR description LIKE ?)";
    $searchParams = ["%$search%", "%$search%"];
}

$overhead_costs = [];
$labor_costs = [];
$total_overhead = 0;
$total_labor = 0;

try {
    $conn = $db;
    
    // Get overhead costs with pagination and search
    $sql = "SELECT * FROM overhead_costs WHERE is_active = 1" . $searchCondition . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $params = array_merge($searchParams, [$limit, $offset_overhead]);
    $stmt->execute($params);
    $overhead_costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total overhead records
    $countSql = "SELECT COUNT(*) FROM overhead_costs WHERE is_active = 1" . $searchCondition;
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($searchParams);
    $total_overhead = $countStmt->fetchColumn();
    
    // Get labor costs with pagination and search
    $laborSearchCondition = !empty($search) ? " AND position_name LIKE ?" : "";
    $laborSearchParams = !empty($search) ? ["%$search%"] : [];
    
    $laborSql = "SELECT * FROM labor_costs WHERE is_active = 1" . $laborSearchCondition . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $laborStmt = $conn->prepare($laborSql);
    $laborParams = array_merge($laborSearchParams, [$limit, $offset_labor]);
    $laborStmt->execute($laborParams);
    $labor_costs = $laborStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total labor records
    $laborCountSql = "SELECT COUNT(*) FROM labor_costs WHERE is_active = 1" . $laborSearchCondition;
    $laborCountStmt = $conn->prepare($laborCountSql);
    $laborCountStmt->execute($laborSearchParams);
    $total_labor = $laborCountStmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Error di Overhead Management: " . $e->getMessage());
    // Debug: tampilkan error untuk troubleshooting
    echo "<!-- Debug Error: " . $e->getMessage() . " -->";
    echo "<!-- Debug SQL: " . $sql . " -->";
    echo "<!-- Debug Params: " . json_encode($params) . " -->";
    echo "<!-- Debug: Overhead count: " . count($overhead_costs) . ", Labor count: " . count($labor_costs) . " -->";
    
    // Fallback: coba query sederhana tanpa search
    try {
        $simpleSql = "SELECT * FROM overhead_costs WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10";
        $simpleStmt = $conn->prepare($simpleSql);
        $simpleStmt->execute();
        $overhead_costs = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $simpleLaborSql = "SELECT * FROM labor_costs WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10";
        $simpleLaborStmt = $conn->prepare($simpleLaborSql);
        $simpleLaborStmt->execute();
        $labor_costs = $simpleLaborStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<!-- Fallback query worked. Overhead: " . count($overhead_costs) . ", Labor: " . count($labor_costs) . " -->";
    } catch (PDOException $fallbackError) {
        echo "<!-- Fallback query failed: " . $fallbackError->getMessage() . " -->";
    }
}

$message = '';
$message_type = '';
if (isset($_SESSION['overhead_message'])) {
    $message = $_SESSION['overhead_message']['text'];
    $message_type = $_SESSION['overhead_message']['type'];
    unset($_SESSION['overhead_message']);
}

$total_pages_overhead = ceil($total_overhead / $limit);
$total_pages_labor = ceil($total_labor / $limit);
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

                <!-- Search Bar -->
                <div class="mb-6 bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label for="search" class="sr-only">Cari overhead atau tenaga kerja</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       placeholder="Cari nama overhead atau posisi tenaga kerja...">
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                            <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Filter
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="overhead_management.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Overhead Costs Section -->
                    <div class="space-y-6">
                        <!-- Form Container -->
                        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6">Biaya Overhead</h3>
                            
                            <!-- Add/Edit Overhead Form -->
                            <form action="/cornerbites-sia/process/simpan_overhead.php" method="POST" class="mb-6">
                                <input type="hidden" name="type" value="overhead">
                                <input type="hidden" id="overhead_id_to_edit" name="overhead_id" value="">
                                
                                <h4 id="overhead_form_title" class="text-lg font-medium text-gray-900 mb-4">Tambah Biaya Overhead Baru</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Biaya</label>
                                        <input type="text" id="overhead_name" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp)</label>
                                        <input type="number" id="overhead_amount" name="amount" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                        <textarea id="overhead_description" name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <button type="submit" id="overhead_submit_button" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Tambah Overhead
                                    </button>
                                    <button type="button" id="overhead_cancel_edit_button" onclick="resetOverheadForm()" class="hidden px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Batal Edit
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Overhead List Container -->
                        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6">Daftar Biaya Overhead</h3>
                            
                            <?php if (empty($overhead_costs)): ?>
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Belum ada biaya overhead
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($overhead_costs as $overhead): ?>
                                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($overhead['name']); ?></h4>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($overhead['description']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-green-600">Rp <?php echo number_format($overhead['amount'], 0, ',', '.'); ?></p>
                                            <div class="flex space-x-2 mt-2">
                                                <button onclick="editOverhead(<?php echo htmlspecialchars(json_encode($overhead)); ?>)" 
                                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 transition-colors">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button onclick="deleteOverhead(<?php echo $overhead['id']; ?>, '<?php echo htmlspecialchars($overhead['name']); ?>')" 
                                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 transition-colors">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination for Overhead -->
                                <?php if ($total_pages_overhead > 1): ?>
                                <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
                                    <div class="text-sm text-gray-700">
                                        Menampilkan <?php echo (($page_overhead - 1) * $limit) + 1; ?> - <?php echo min($page_overhead * $limit, $total_overhead); ?> dari <?php echo $total_overhead; ?> data overhead
                                    </div>
                                    <div class="flex space-x-1">
                                        <?php if ($page_overhead > 1): ?>
                                            <a href="?page_overhead=<?php echo $page_overhead - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page_labor > 1 ? '&page_labor=' . $page_labor : ''; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                                Sebelumnya
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page_overhead - 2); $i <= min($total_pages_overhead, $page_overhead + 2); $i++): ?>
                                            <a href="?page_overhead=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page_labor > 1 ? '&page_labor=' . $page_labor : ''; ?>" 
                                               class="px-3 py-2 text-sm <?php echo $i == $page_overhead ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border border-gray-300 rounded-md">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page_overhead < $total_pages_overhead): ?>
                                            <a href="?page_overhead=<?php echo $page_overhead + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page_labor > 1 ? '&page_labor=' . $page_labor : ''; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                                Selanjutnya
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Labor Costs Section -->
                    <div class="space-y-6">
                        <!-- Form Container -->
                        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6">Upah Tenaga Kerja</h3>
                            
                            <!-- Add/Edit Labor Form -->
                            <form action="/cornerbites-sia/process/simpan_overhead.php" method="POST" class="mb-6">
                                <input type="hidden" name="type" value="labor">
                                <input type="hidden" id="labor_id_to_edit" name="labor_id" value="">
                                
                                <h4 id="labor_form_title" class="text-lg font-medium text-gray-900 mb-4">Tambah Posisi Tenaga Kerja Baru</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Posisi/Jabatan</label>
                                        <input type="text" id="labor_position_name" name="position_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Upah per Jam (Rp)</label>
                                        <input type="number" id="labor_hourly_rate" name="hourly_rate" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <button type="submit" id="labor_submit_button" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Tambah Posisi
                                    </button>
                                    <button type="button" id="labor_cancel_edit_button" onclick="resetLaborForm()" class="hidden px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Batal Edit
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Labor List Container -->
                        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6">Daftar Posisi Tenaga Kerja</h3>
                            
                            <?php if (empty($labor_costs)): ?>
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                    </svg>
                                    Belum ada posisi tenaga kerja
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($labor_costs as $labor): ?>
                                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($labor['position_name']); ?></h4>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-green-600">Rp <?php echo number_format($labor['hourly_rate'], 0, ',', '.'); ?>/jam</p>
                                            <div class="flex space-x-2 mt-2">
                                                <button onclick="editLabor(<?php echo htmlspecialchars(json_encode($labor)); ?>)" 
                                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 transition-colors">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </button>
                                                <button onclick="deleteLabor(<?php echo $labor['id']; ?>, '<?php echo htmlspecialchars($labor['position_name']); ?>')" 
                                                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 transition-colors">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination for Labor -->
                                <?php if ($total_pages_labor > 1): ?>
                                <div class="flex justify-between items-center mt-6 pt-4 border-t border-gray-200">
                                    <div class="text-sm text-gray-700">
                                        Menampilkan <?php echo (($page_labor - 1) * $limit) + 1; ?> - <?php echo min($page_labor * $limit, $total_labor); ?> dari <?php echo $total_labor; ?> data tenaga kerja
                                    </div>
                                    <div class="flex space-x-1">
                                        <?php if ($page_labor > 1): ?>
                                            <a href="?page_labor=<?php echo $page_labor - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page_overhead > 1 ? '&page_overhead=' . $page_overhead : ''; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                                Sebelumnya
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page_labor - 2); $i <= min($total_pages_labor, $page_labor + 2); $i++): ?>
                                            <a href="?page_labor=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page_overhead > 1 ? '&page_overhead=' . $page_overhead : ''; ?>" 
                                               class="px-3 py-2 text-sm <?php echo $i == $page_labor ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> border border-gray-300 rounded-md">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page_labor < $total_pages_labor): ?>
                                            <a href="?page_labor=<?php echo $page_labor + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page_overhead > 1 ? '&page_overhead=' . $page_overhead : ''; ?>" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                                Selanjutnya
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="/cornerbites-sia/assets/js/overhead.js"></script>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
