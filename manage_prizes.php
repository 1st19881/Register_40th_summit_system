<?php

/**
 * Summit Auto Body Industry - Prize Management System
 * จัดการของรางวัลสำหรับการจับรางวัล
 */

require_once 'config/config.php';

// Handle AJAX Actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get all prizes
if ($action === 'get_prizes') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $query = "SELECT prize_id, prize_name, prize_qty, create_date FROM prizes ORDER BY prize_id ASC";
        $stid = oci_parse($conn, $query);
        if (!$stid || !oci_execute($stid)) {
            throw new Exception('Failed to fetch prizes');
        }
        $prizes = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
            $prizes[] = [
                'id' => $row['PRIZE_ID'],
                'name' => $row['PRIZE_NAME'],
                'qty' => $row['PRIZE_QTY'] ?? 1,
                'created' => $row['CREATE_DATE'] ?? null
            ];
        }
        oci_free_statement($stid);
        echo json_encode(['success' => true, 'prizes' => $prizes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Add a new prize
if ($action === 'add_prize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        if (empty($input['name'])) {
            throw new Exception('Prize name is required');
        }
        $name = trim($input['name']);
        $qty = isset($input['qty']) ? (int)$input['qty'] : 1;

        // Get next ID
        $max_query = "SELECT NVL(MAX(prize_id), 0) + 1 AS next_id FROM prizes";
        $max_stid = oci_parse($conn, $max_query);
        if (!$max_stid || !oci_execute($max_stid)) {
            throw new Exception('Failed to get next ID');
        }
        $max_row = oci_fetch_array($max_stid, OCI_ASSOC);
        $next_id = $max_row['NEXT_ID'];
        oci_free_statement($max_stid);

        // Insert prize
        $insert_query = "INSERT INTO prizes (prize_id, prize_name, prize_qty, create_date) VALUES (:id, :name, :qty, SYSDATE)";
        $ins_stid = oci_parse($conn, $insert_query);
        if (!$ins_stid) {
            throw new Exception('Failed to parse insert query');
        }
        oci_bind_by_name($ins_stid, ":id", $next_id);
        oci_bind_by_name($ins_stid, ":name", $name);
        oci_bind_by_name($ins_stid, ":qty", $qty);
        if (!oci_execute($ins_stid, OCI_COMMIT_ON_SUCCESS)) {
            throw new Exception('Failed to insert prize');
        }
        oci_free_statement($ins_stid);
        echo json_encode(['success' => true, 'message' => 'เพิ่มของรางวัลสำเร็จ', 'id' => $next_id]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Update a prize
if ($action === 'update_prize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        if (empty($input['id']) || empty($input['name'])) {
            throw new Exception('Prize ID and name are required');
        }
        $id = (int)$input['id'];
        $name = trim($input['name']);
        $qty = isset($input['qty']) ? (int)$input['qty'] : 1;

        $update_query = "UPDATE prizes SET prize_name = :name, prize_qty = :qty WHERE prize_id = :id";
        $upd_stid = oci_parse($conn, $update_query);
        if (!$upd_stid) {
            throw new Exception('Failed to parse update query');
        }
        oci_bind_by_name($upd_stid, ":id", $id);
        oci_bind_by_name($upd_stid, ":name", $name);
        oci_bind_by_name($upd_stid, ":qty", $qty);
        if (!oci_execute($upd_stid, OCI_COMMIT_ON_SUCCESS)) {
            throw new Exception('Failed to update prize');
        }
        oci_free_statement($upd_stid);
        echo json_encode(['success' => true, 'message' => 'อัพเดทของรางวัลสำเร็จ']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Delete a prize
if ($action === 'delete_prize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        if (empty($input['id'])) {
            throw new Exception('Prize ID is required');
        }
        $id = (int)$input['id'];

        // Check if prize is used in winners
        $check_query = "SELECT COUNT(*) AS cnt FROM winners WHERE prize_name = (SELECT prize_name FROM prizes WHERE prize_id = :id)";
        $chk_stid = oci_parse($conn, $check_query);
        oci_bind_by_name($chk_stid, ":id", $id);
        if (!oci_execute($chk_stid)) {
            throw new Exception('Failed to check prize usage');
        }
        $chk_row = oci_fetch_array($chk_stid, OCI_ASSOC);
        oci_free_statement($chk_stid);

        if ($chk_row['CNT'] > 0) {
            throw new Exception('ไม่สามารถลบได้ เนื่องจากของรางวัลนี้มีผู้ถูกจับรางวัลแล้ว');
        }

        $delete_query = "DELETE FROM prizes WHERE prize_id = :id";
        $del_stid = oci_parse($conn, $delete_query);
        if (!$del_stid) {
            throw new Exception('Failed to parse delete query');
        }
        oci_bind_by_name($del_stid, ":id", $id);
        if (!oci_execute($del_stid, OCI_COMMIT_ON_SUCCESS)) {
            throw new Exception('Failed to delete prize');
        }
        oci_free_statement($del_stid);
        echo json_encode(['success' => true, 'message' => 'ลบของรางวัลสำเร็จ']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการของรางวัล | Prize Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --gold-gradient: linear-gradient(135deg, #bf953f 0%, #fcf6ba 50%, #aa771c 100%);
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
            min-height: 100vh;
        }

        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            box-shadow: 0 10px 40px rgba(30, 60, 114, 0.3);
        }

        .page-header h1 {
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            opacity: 1;
            color: white;
            transform: translateY(-50%) translateX(-5px);
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .action-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .action-card h5 {
            color: #1e3c72;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-card h5 i {
            font-size: 1.2rem;
            color: #bf953f;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #1e3c72;
            box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.1);
        }

        .btn-add {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.3);
            color: white;
        }

        .prize-table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .prize-table thead {
            background: var(--primary-gradient);
            color: white;
        }

        .prize-table thead th {
            padding: 18px 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
        }

        .prize-table tbody tr {
            transition: all 0.3s ease;
        }

        .prize-table tbody tr:hover {
            background: #f8fafc;
        }

        .prize-table tbody td {
            padding: 18px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .prize-name {
            font-weight: 600;
            color: #1e3c72;
        }

        .prize-qty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            min-width: 40px;
            height: 40px;
            border-radius: 10px;
            font-weight: 700;
        }

        .btn-action {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.3s ease;
            margin: 0 3px;
        }

        .btn-edit {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stats-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
        }

        .stats-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e3c72;
        }

        .stats-card .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 30px 20px;
                border-radius: 0 0 30px 30px;
            }

            .action-card,
            .prize-table {
                border-radius: 15px;
            }

            .back-btn {
                position: relative;
                left: auto;
                transform: none;
                display: block;
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>
    <!-- Page Header -->
    <header class="page-header">
        <div class="container position-relative">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left me-2"></i> กลับหน้าหลัก
            </a>
            <div class="text-center">
                <h1><i class="fas fa-gift me-3"></i>จัดการของรางวัล</h1>
                <p class="mb-0 opacity-75">Prize Management System</p>
            </div>
        </div>
    </header>

    <div class="content-container">
        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 fade-in" style="animation-delay: 0.1s;">
                <div class="stats-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="stat-value" id="totalPrizes">0</div>
                    <div class="stat-label">จำนวนรางวัลทั้งหมด</div>
                </div>
            </div>
            <div class="col-md-6 fade-in" style="animation-delay: 0.2s;">
                <div class="stats-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #bf953f 0%, #fcf6ba 100%); color: #1e3c72;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-value" id="totalQty">0</div>
                    <div class="stat-label">จำนวนรางวัลที่จับได้ทั้งหมด</div>
                </div>
            </div>
        </div>

        <!-- Add Prize Form -->
        <div class="action-card fade-in" style="animation-delay: 0.3s;">
            <h5><i class="fas fa-plus-circle"></i> เพิ่มของรางวัลใหม่</h5>
            <form id="addPrizeForm">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">ชื่อของรางวัล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="prizeName" placeholder="เช่น ทอง 1 สลึง" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">จำนวน (รางวัล)</label>
                        <input type="number" class="form-control" id="prizeQty" value="1" min="1">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-add w-100">
                            <i class="fas fa-plus me-2"></i>เพิ่ม
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Prize List Table -->
        <div class="prize-table fade-in" style="animation-delay: 0.4s;">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>ชื่อของรางวัล</th>
                        <th style="width: 120px;" class="text-center">จำนวน</th>
                        <th style="width: 120px;" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="prizeTableBody">
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="fas fa-gift"></i>
                            <p class="mb-0">กำลังโหลดข้อมูล...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <footer class="text-center text-muted py-4 mt-4">
            <p>© 2026 Summit Auto Body Industry - QR Check-in System</p>
        </footer>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>แก้ไขของรางวัล</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editPrizeForm">
                        <input type="hidden" id="editPrizeId">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ชื่อของรางวัล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editPrizeName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">จำนวน (รางวัล)</label>
                            <input type="number" class="form-control" id="editPrizeQty" min="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-add" onclick="updatePrize()">
                        <i class="fas fa-save me-2"></i>บันทึก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white" style="border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="fas fa-trash-alt text-danger" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p class="mb-0">คุณต้องการลบของรางวัล<br><strong id="deletePrizeName" class="text-danger"></strong><br>ใช่หรือไม่?</p>
                    <input type="hidden" id="deletePrizeId">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>ลบ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const prizeTableBody = document.getElementById('prizeTableBody');
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        // Fetch and display prizes
        async function fetchPrizes() {
            try {
                const res = await fetch('?action=get_prizes');
                const data = await res.json();

                if (data.success) {
                    const prizes = data.prizes;
                    document.getElementById('totalPrizes').textContent = prizes.length;
                    document.getElementById('totalQty').textContent = prizes.reduce((sum, p) => sum + (parseInt(p.qty) || 1), 0);

                    if (prizes.length === 0) {
                        prizeTableBody.innerHTML = `
                            <tr>
                                <td colspan="4" class="empty-state">
                                    <i class="fas fa-gift"></i>
                                    <p class="mb-0">ยังไม่มีของรางวัล กรุณาเพิ่มของรางวัลใหม่</p>
                                </td>
                            </tr>
                        `;
                    } else {
                        prizeTableBody.innerHTML = prizes.map((p, idx) => `
                            <tr>
                                <td class="text-muted">${idx + 1}</td>
                                <td class="prize-name">${escapeHtml(p.name)}</td>
                                <td class="text-center"><span class="prize-qty">${p.qty || 1}</span></td>
                                <td class="text-center">
                                    <button class="btn-action btn-edit" onclick="openEditModal(${p.id}, '${escapeJs(p.name)}', ${p.qty || 1})">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="openDeleteModal(${p.id}, '${escapeJs(p.name)}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    }
                } else {
                    showToast('error', data.message);
                }
            } catch (err) {
                console.error('Error fetching prizes:', err);
                prizeTableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="empty-state text-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <p class="mb-0">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                        </td>
                    </tr>
                `;
            }
        }

        // Add new prize
        document.getElementById('addPrizeForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const name = document.getElementById('prizeName').value.trim();
            const qty = parseInt(document.getElementById('prizeQty').value) || 1;

            if (!name) {
                showToast('error', 'กรุณากรอกชื่อของรางวัล');
                return;
            }

            try {
                const res = await fetch('?action=add_prize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        qty
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showToast('success', data.message);
                    document.getElementById('prizeName').value = '';
                    document.getElementById('prizeQty').value = '1';
                    fetchPrizes();
                } else {
                    showToast('error', data.message);
                }
            } catch (err) {
                showToast('error', 'เกิดข้อผิดพลาด: ' + err.message);
            }
        });

        // Open edit modal
        function openEditModal(id, name, qty) {
            document.getElementById('editPrizeId').value = id;
            document.getElementById('editPrizeName').value = name;
            document.getElementById('editPrizeQty').value = qty;
            editModal.show();
        }

        // Update prize
        async function updatePrize() {
            const id = document.getElementById('editPrizeId').value;
            const name = document.getElementById('editPrizeName').value.trim();
            const qty = parseInt(document.getElementById('editPrizeQty').value) || 1;

            if (!name) {
                showToast('error', 'กรุณากรอกชื่อของรางวัล');
                return;
            }

            try {
                const res = await fetch('?action=update_prize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id,
                        name,
                        qty
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showToast('success', data.message);
                    editModal.hide();
                    fetchPrizes();
                } else {
                    showToast('error', data.message);
                }
            } catch (err) {
                showToast('error', 'เกิดข้อผิดพลาด: ' + err.message);
            }
        }

        // Open delete modal
        function openDeleteModal(id, name) {
            document.getElementById('deletePrizeId').value = id;
            document.getElementById('deletePrizeName').textContent = name;
            deleteModal.show();
        }

        // Confirm delete
        async function confirmDelete() {
            const id = document.getElementById('deletePrizeId').value;

            try {
                const res = await fetch('?action=delete_prize', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showToast('success', data.message);
                    deleteModal.hide();
                    fetchPrizes();
                } else {
                    showToast('error', data.message);
                }
            } catch (err) {
                showToast('error', 'เกิดข้อผิดพลาด: ' + err.message);
            }
        }

        // Utility functions
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function escapeJs(str) {
            if (!str) return '';
            return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
        }

        function showToast(type, message) {
            // Create toast container if not exists
            let container = document.getElementById('toastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toastContainer';
                container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            toast.style.cssText = 'min-width: 300px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); border-radius: 12px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            container.appendChild(toast);

            setTimeout(() => toast.remove(), 4000);
        }

        // Initialize
        window.onload = fetchPrizes;
    </script>
</body>

</html>