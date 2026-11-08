<?php
/**
 * Import College Data Pool (CSV / Excel / Direct Paste)
 * University-Level Education Consultancy CRM (EdrootsCRM)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_permission('college_data', 'add');

$currentUser = current_user();
$pageTitle = 'Import College Data';
$activePage = 'import_college_data';

// Handle Sample CSV Download
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="college_data_sample_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Name', 'Phone', 'Email', 'College Name', 'Degree', 'Stream', 'Graduation Year', 'City']);
    fputcsv($out, ['Aadhav Krishna', '9847112233', 'aadhav.k@gmail.com', 'St. Teresa College', 'BBA', 'Finance', '2025', 'Kochi']);
    fputcsv($out, ['Devika Ramesh', '+91 9745223344', 'devika.ramesh@outlook.com', 'Farook College', 'B.Sc', 'Computer Science', '2024', 'Calicut']);
    fputcsv($out, ['Nikhil Thomas', '09447334455', 'nikhil.thomas@gmail.com', 'Mar Ivanios College', 'B.Com', 'Cooperation', '2025', 'Trivandrum']);
    fclose($out);
    exit;
}

$branches = $pdo->query("SELECT id, name, city FROM branches WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

$importStats = null;

// Handle Form Submission (File Upload or Raw CSV Text Paste)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $branchId = (int)($_POST['branch_id'] ?? 0);
    $csvText = trim($_POST['csv_text'] ?? '');
    $uploadedFile = $_FILES['csv_file'] ?? null;
    
    $rows = [];
    $originalFilename = 'direct_paste_batch.csv';
    
    if (!empty($uploadedFile['tmp_name']) && is_uploaded_file($uploadedFile['tmp_name'])) {
        $originalFilename = $uploadedFile['name'];
        if (($handle = fopen($uploadedFile['tmp_name'], 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (!empty(array_filter($data))) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }
    } elseif (!empty($csvText)) {
        $lines = explode("\n", $csvText);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $rows[] = str_getcsv($line);
            }
        }
    }

    if (empty($rows)) {
        set_flash('danger', 'Please upload a valid CSV file or paste tabular student data to import.');
    } else {
        // Strip header if present
        $firstRow = $rows[0];
        $isHeader = (stripos($firstRow[0], 'name') !== false || stripos($firstRow[1] ?? '', 'phone') !== false);
        if ($isHeader) {
            array_shift($rows);
        }

        $totalRows = count($rows);
        $importedCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;
        $duplicateDetails = [];

        try {
            $pdo->beginTransaction();

            // Track batch import header
            $stmtBatch = $pdo->prepare("
                INSERT INTO college_data_imports (file_name, original_filename, total_rows, imported_rows, duplicate_rows, imported_by)
                VALUES (?, ?, ?, 0, 0, ?)
            ");
            $stmtBatch->execute([$originalFilename, $originalFilename, $totalRows, $currentUser['id']]);
            $importBatchId = $pdo->lastInsertId();

            $stmtInsert = $pdo->prepare("
                INSERT INTO college_data (
                    student_name, phone, email, college_name, degree, stream,
                    graduation_year, city, branch_id, import_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $checkCollegePhone = $pdo->prepare("SELECT id FROM college_data WHERE phone = ? LIMIT 1");

            foreach ($rows as $r) {
                $name = trim($r[0] ?? '');
                $rawPhone = trim($r[1] ?? '');
                $email = trim($r[2] ?? '');
                $college = trim($r[3] ?? '');
                $degree = trim($r[4] ?? '');
                $stream = trim($r[5] ?? '');
                $gradYear = !empty($r[6]) ? (int)preg_replace('/[^0-9]/', '', $r[6]) : null;
                $city = trim($r[7] ?? '');

                if (empty($name) || empty($rawPhone)) {
                    $errorCount++;
                    continue;
                }

                $cleanPhone = normalize_phone($rawPhone);
                if (strlen($cleanPhone) < 10) {
                    $errorCount++;
                    continue;
                }

                // Check active leads duplicate
                $existingLead = check_phone_duplicate($cleanPhone);
                if ($existingLead) {
                    $duplicateCount++;
                    $duplicateDetails[] = "{$name} ({$cleanPhone}) already exists as Active Lead ({$existingLead['lead_code']})";
                    continue;
                }

                // Check college_data duplicate
                $checkCollegePhone->execute([$cleanPhone]);
                if ($checkCollegePhone->fetch()) {
                    $duplicateCount++;
                    $duplicateDetails[] = "{$name} ({$cleanPhone}) already exists in College Data pool";
                    continue;
                }

                $stmtInsert->execute([
                    $name,
                    $cleanPhone,
                    $email,
                    $college,
                    $degree,
                    $stream,
                    $gradYear,
                    $city,
                    $branchId > 0 ? $branchId : ($currentUser['branch_id'] ?? null),
                    $importBatchId
                ]);
                $importedCount++;
            }

            // Update batch stats
            $stmtBatchUpd = $pdo->prepare("UPDATE college_data_imports SET imported_rows = ?, duplicate_rows = ? WHERE id = ?");
            $stmtBatchUpd->execute([$importedCount, $duplicateCount, $importBatchId]);

            log_activity(
                $currentUser['id'],
                null,
                'College Data Imported',
                "Imported {$importedCount} college student records from {$originalFilename}. {$duplicateCount} duplicates skipped."
            );

            $pdo->commit();

            $importStats = [
                'total' => $totalRows,
                'imported' => $importedCount,
                'duplicates' => $duplicateCount,
                'errors' => $errorCount,
                'details' => $duplicateDetails
            ];

            set_flash('success', "Batch import complete! <strong>{$importedCount}</strong> new students imported into college data pool. <strong>{$duplicateCount}</strong> duplicates safely skipped.");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', 'Error importing data: ' . htmlspecialchars($e->getMessage()));
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem; font-weight:700; color:var(--text-heading); margin:0 0 0.35rem 0;">Import College Data</h1>
    <p style="font-size:0.875rem; color:var(--text-muted); margin:0;">
      Upload bulk student lists from partner universities, career fairs, or campus drives. Automated canonical phone deduplication prevents duplicate records.
    </p>
  </div>
  <div style="display:flex; gap:0.5rem; align-items:center;">
    <a href="<?= base_url('data/import_college_data.php?download_sample=1') ?>" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-download"></i> Download CSV Template
    </a>
    <a href="<?= base_url('data/college_data.php') ?>" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Pool
    </a>
  </div>
</div>

<?php if ($importStats): ?>
  <div class="card" style="margin-bottom:1.5rem; border-left:4px solid var(--success); padding:1.5rem;">
    <h3 style="font-size:1.1rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
      <i class="fa-solid fa-circle-check" style="color:var(--success); margin-right:8px;"></i> Import Execution Summary
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1rem;">
      <div style="background:var(--bg-subtle); padding:0.85rem; border-radius:8px;">
        <span style="font-size:0.75rem; color:var(--text-muted); display:block;">Total Rows Evaluated</span>
        <strong style="font-size:1.3rem; color:var(--text-heading);"><?= $importStats['total'] ?></strong>
      </div>
      <div style="background:#dcfce7; padding:0.85rem; border-radius:8px;">
        <span style="font-size:0.75rem; color:#15803d; display:block;">Successfully Imported</span>
        <strong style="font-size:1.3rem; color:#15803d;"><?= $importStats['imported'] ?></strong>
      </div>
      <div style="background:#fef3c7; padding:0.85rem; border-radius:8px;">
        <span style="font-size:0.75rem; color:#b45309; display:block;">Duplicates Skipped</span>
        <strong style="font-size:1.3rem; color:#b45309;"><?= $importStats['duplicates'] ?></strong>
      </div>
      <div style="background:#fee2e2; padding:0.85rem; border-radius:8px;">
        <span style="font-size:0.75rem; color:#b91c1c; display:block;">Malformed / Invalid</span>
        <strong style="font-size:1.3rem; color:#b91c1c;"><?= $importStats['errors'] ?></strong>
      </div>
    </div>

    <?php if (!empty($importStats['details'])): ?>
      <div style="font-size:0.8rem; background:#fff; border:1px solid var(--border-color); border-radius:6px; padding:0.75rem; max-height:120px; overflow-y:auto;">
        <strong>Deduplication Audit Trail:</strong>
        <ul style="margin:0.5rem 0 0 1.25rem; padding:0; color:var(--text-muted);">
          <?php foreach (array_slice($importStats['details'], 0, 10) as $d): ?>
            <li><?= htmlspecialchars($d) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div style="margin-top:1rem;">
      <a href="<?= base_url('data/college_data.php') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-list"></i> View College Data Pool &rarr;
      </a>
    </div>
  </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start;">
  <!-- Upload Form -->
  <div class="card" style="padding:1.5rem;">
    <h3 style="font-size:1.15rem; font-weight:700; color:var(--text-heading); margin:0 0 1rem 0;">
      <i class="fa-solid fa-cloud-arrow-up" style="color:var(--primary); margin-right:8px;"></i> Upload Student Spreadsheet
    </h3>

    <form method="POST" action="<?= base_url('data/import_college_data.php') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="form-group" style="margin-bottom:1.25rem;">
        <label class="form-label">Target Branch Allocation</label>
        <select name="branch_id" class="form-control">
          <?php if (is_super_admin()): ?>
            <option value="0">Global Pool (Unassigned Branch)</option>
          <?php endif; ?>
          <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= (($currentUser['branch_id'] ?? 0) == $b['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['city']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <small style="color:var(--text-muted); font-size:0.75rem;">Allocates this student list to a specific regional consultancy branch.</small>
      </div>

      <div class="form-group" style="margin-bottom:1.25rem;">
        <label class="form-label">Choose CSV File</label>
        <input type="file" name="csv_file" class="form-control" accept=".csv, text/csv">
        <small style="color:var(--text-muted); font-size:0.75rem;">Accepted format: .csv (Comma Delimited)</small>
      </div>

      <div style="position:relative; text-align:center; margin:1.25rem 0;">
        <hr style="border:0; border-top:1px solid var(--border-color);">
        <span style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; padding:0 10px; font-size:0.75rem; color:var(--text-muted); font-weight:600;">OR DIRECT QUICK PASTE</span>
      </div>

      <div class="form-group" style="margin-bottom:1.25rem;">
        <label class="form-label">Paste CSV Rows</label>
        <textarea name="csv_text" class="form-control" rows="6" placeholder="Student Name, Phone, Email, College Name, Degree, Stream, Graduation Year, City
Rohit Menon, 9846011223, rohit.m@gmail.com, Rajagiri College, B.Com, Finance, 2025, Kochi
Sneha Varma, 9745122334, sneha.v@yahoo.com, Sacred Heart, B.Sc, Physics, 2024, Thevara"></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">
        <i class="fa-solid fa-file-import"></i> Execute Import & Deduplicate
      </button>
    </form>
  </div>

  <!-- Format Guidelines Card -->
  <div class="card" style="padding:1.5rem; background:var(--bg-subtle);">
    <h4 style="font-size:1rem; font-weight:700; color:var(--text-heading); margin:0 0 0.75rem 0;">
      <i class="fa-solid fa-circle-info" style="color:var(--primary); margin-right:6px;"></i> Import Column Sequence
    </h4>
    <p style="font-size:0.825rem; color:var(--text-muted); line-height:1.5;">
      Ensure your spreadsheet or CSV uses the following 8 standard columns. The first row may optionally be a header title row.
    </p>

    <div style="background:#fff; border:1px solid var(--border-color); border-radius:8px; padding:0.85rem; font-size:0.8rem; margin-bottom:1rem;">
      <ol style="margin:0 0 0 1.25rem; padding:0; line-height:1.7;">
        <li><strong>Student Name</strong> <span style="color:red;">*</span> (e.g. Rahul Nair)</li>
        <li><strong>Phone Number</strong> <span style="color:red;">*</span> (e.g. +91 9847112233)</li>
        <li><strong>Email Address</strong> (e.g. rahul@gmail.com)</li>
        <li><strong>College Name</strong> (e.g. Farook College)</li>
        <li><strong>Degree</strong> (e.g. B.Tech / BBA / B.Com)</li>
        <li><strong>Stream / Specialization</strong> (e.g. Computer Science)</li>
        <li><strong>Graduation Year</strong> (e.g. 2025)</li>
        <li><strong>City / Residence</strong> (e.g. Calicut)</li>
      </ol>
    </div>

    <div style="background:#e0e7ff; border-radius:8px; padding:0.85rem; font-size:0.8rem; color:#3730a3;">
      <strong>Automated Normalization Engine:</strong><br>
      Phone numbers are automatically normalized by stripping country codes (<code>+91</code>), leading zeroes, spaces, and hyphens into standard 10-digit canonical integers to guarantee zero duplicate lead leakage.
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
