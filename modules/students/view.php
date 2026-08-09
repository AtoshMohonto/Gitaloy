<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/student_db.php';

requireRole(ROLE_ADMIN, ROLE_DIV_MANAGER, ROLE_DIST_MANAGER, ROLE_ACCOUNTANT, ROLE_TEACHER, ROLE_STUDENT);

$pdo = getDbConnection();

if (isStudent()) {
    $myStudent = getStudentByUserId((int) $_SESSION['user_id']);
    if (!$myStudent) {
        header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
        exit;
    }
    $studentId = (int) $myStudent['id'];
} else {
    $studentId = (int) ($_GET['id'] ?? 0);
}

$student = $studentId > 0 ? getStudentById($studentId) : null;
if (!$student) {
    header('Location: ' . appBaseUrl() . '/modules/dashboard/index.php');
    exit;
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isStudent()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_doc') {
        if (!empty($_FILES['document']['name'])) {
            $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                $error = 'Invalid document format (PDF, JPG, PNG, WebP only).';
            } elseif ($_FILES['document']['size'] > 5 * 1024 * 1024) {
                $error = 'Document exceeds 5MB.';
            } else {
                $dir = __DIR__ . '/../../uploads/students/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $fileName = uniqid('doc_', true) . '_' . basename($_FILES['document']['name']);
                if (move_uploaded_file($_FILES['document']['tmp_name'], $dir . $fileName)) {
                    addStudentDocument($studentId, 'uploads/students/' . $fileName);
                    $success = 'Document uploaded.';
                    logActivity('Uploaded a document for student #' . $studentId, 'students');
                } else {
                    $error = 'Upload failed.';
                }
            }
        }
    }
    if ($action === 'delete_doc') {
        $docId = (int) ($_POST['doc_id'] ?? 0);
        $pdo->prepare('DELETE FROM student_documents WHERE id = ? AND student_id = ?')->execute([$docId, $studentId]);
        $success = 'Document removed.';
        logActivity('Removed a document for student #' . $studentId, 'students');
    }
}

$yearId = activeYearId();
$attendance = getStudentAttendanceSummary($studentId, $yearId);
$marks = getStudentMarksSummary($studentId, $yearId);
$fees = getStudentFeeSummary($studentId, $yearId);
$documents = getStudentDocuments($studentId);

$distStmt = $pdo->prepare(
    'SELECT di.name AS item_name, di.unit, dst.quantity, dst.distributed_at, u.name AS added_by, dst.notes
     FROM distributions dst
     LEFT JOIN distribution_items di ON di.id = dst.item_id
     LEFT JOIN users u ON u.id = dst.added_by
     WHERE dst.student_id = ? ORDER BY dst.distributed_at DESC'
);
$distStmt->execute([$studentId]);
$distributions = $distStmt->fetchAll();

$historyStmt = $pdo->prepare(
    'SELECT ss.session_date, ss.type, a.status, c.name AS center_name
     FROM attendance a
     JOIN sessions ss ON ss.id = a.session_id
     LEFT JOIN centers c ON c.id = ss.center_id
     WHERE a.student_id = ? ORDER BY ss.session_date DESC LIMIT 20'
);
$historyStmt->execute([$studentId]);
$attendanceHistory = $historyStmt->fetchAll();

list($village, $upazila, $district, $division) = geoLabels($student);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="<?= appBaseUrl() ?>/modules/students/index.php" class="rounded-full border border-emerald-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">← All students</a>
                <?php if (isStaff()): ?>
                    <a href="<?= appBaseUrl() ?>/modules/reports/report_card.php?id=<?= (int) $student['id'] ?>" class="rounded-full bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">🖨️ Report card</a>
                <?php endif; ?>
            </div>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 flex flex-wrap items-center gap-6 px-6 py-8 sm:px-8">
        <div class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-white/15 ring-4 ring-white/10">
            <i data-lucide="graduation-cap" class="h-9 w-9 text-emerald-100"></i>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-300"><?= htmlspecialchars($student['student_id']) ?></p>
            <h1 class="mt-1 truncate text-2xl font-extrabold text-white sm:text-3xl"><?= htmlspecialchars($student['name']) ?></h1>
            <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-emerald-100/80">
                <span class="inline-flex items-center gap-1.5"><i data-lucide="book-open" class="h-4 w-4 text-emerald-300"></i><?= htmlspecialchars($class = $student['class_name'] ?: 'No class') ?></span>
                <span class="inline-flex items-center gap-1.5"><i data-lucide="map-pin" class="h-4 w-4 text-emerald-300"></i><?= htmlspecialchars($student['center_name'] ?: 'No center') ?></span>
                <span class="inline-flex items-center gap-1.5"><i data-lucide="badge-check" class="h-4 w-4 text-emerald-300"></i><?= htmlspecialchars($student['status']) ?></span>
            </p>
        </div>
        <div class="text-right text-sm text-emerald-100/80">
            <p><?= htmlspecialchars($village ?: 'No village') ?></p>
            <p><?= htmlspecialchars($upazila) ?></p>
            <p><?= htmlspecialchars($district) ?> - <?= htmlspecialchars($division) ?></p>
        </div>
    </div>
</section>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Attendance (active year)</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $attendance['rate'] ?>%</p>
                    <p class="mt-1 text-xs text-slate-500"><?= $attendance['present'] ?> / <?= $attendance['total'] ?> sessions</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Task performance</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900"><?= $marks['pct'] ?>%</p>
                    <p class="mt-1 text-xs text-slate-500"><?= $marks['obtained'] ?> / <?= $marks['possible'] ?> marks, <?= $marks['completed'] ?>/<?= $marks['tasks'] ?> tasks done</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Fees (active year)</p>
                    <p class="mt-2 text-3xl font-semibold <?= $fees['due'] > 0 ? 'text-amber-600' : 'text-emerald-600' ?>"><?= number_format($fees['due'], 2) ?> BDT</p>
                    <p class="mt-1 text-xs text-slate-500">Paid <?= number_format($fees['paid'], 2) ?> of <?= number_format($fees['billed'], 2) ?></p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Guardian &amp; profile</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-slate-500">Guardian</dt><dd class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['guardian_name'] ?: '—') ?></dd></div>
                        <div><dt class="text-slate-500">Phone</dt><dd class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['guardian_phone'] ?: '—') ?></dd></div>
                        <div><dt class="text-slate-500">Date of birth</dt><dd class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['dob'] ?: '—') ?></dd></div>
                        <div><dt class="text-slate-500">Admission date</dt><dd class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['admission_date'] ?: '—') ?></dd></div>
                        <div><dt class="text-slate-500">Gender</dt><dd class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['gender'] ?: '—') ?></dd></div>
                        <div><dt class="text-slate-500">Login username</dt><dd class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars($student['login_username'] ?: '—') ?></dd></div>
                    </dl>

                    <?php if (isStaff()): ?>
                        <h3 class="mt-6 text-sm font-semibold text-slate-700">Documents</h3>
                        <form class="mt-2 flex flex-wrap items-center gap-2" method="post" enctype="multipart/form-data" action="<?= appBaseUrl() ?>/modules/students/view.php?id=<?= (int) $studentId ?>">
                            <input type="hidden" name="action" value="upload_doc">
                            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                            <button class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Upload</button>
                        </form>
                        <?php if (empty($documents)): ?>
                            <p class="mt-3 text-xs text-slate-500">No documents uploaded.</p>
                        <?php else: ?>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php foreach ($documents as $doc): ?>
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700">
                                        <a href="<?= appBaseUrl() ?>/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank">📎 View</a>
                                        <form method="post" action="<?= appBaseUrl() ?>/modules/students/view.php?id=<?= (int) $studentId ?>" class="inline">
                                            <input type="hidden" name="action" value="delete_doc">
                                            <input type="hidden" name="doc_id" value="<?= (int) $doc['id'] ?>">
                                            <button class="text-red-600">✕</button>
                                        </form>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>

                <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900">Items received</h2>
                    <?php if (empty($distributions)): ?>
                        <p class="mt-4 text-sm text-slate-500">No distribution items received yet.</p>
                    <?php else: ?>
                        <div class="mt-4 space-y-3">
                            <?php foreach ($distributions as $dist): ?>
                                <div class="rounded-2xl border border-emerald-100 bg-white p-4">
                                    <div class="flex items-center justify-between">
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($dist['item_name']) ?> × <?= (int) $dist['quantity'] ?> <?= htmlspecialchars($dist['unit'] ?: '') ?></p>
                                        <span class="text-xs text-slate-500"><?= htmlspecialchars($dist['distributed_at']) ?></span>
                                    </div>
                                    <?php if (!empty($dist['notes'])): ?>
                                        <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($dist['notes']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Attendance history</h2>
                <?php if (empty($attendanceHistory)): ?>
                    <p class="mt-4 text-sm text-slate-500">No attendance recorded yet.</p>
                <?php else: ?>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                    <th class="px-3 py-3">Date</th>
                                    <th class="px-3 py-3">Type</th>
                                    <th class="px-3 py-3">Center</th>
                                    <th class="px-3 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($attendanceHistory as $row): ?>
                                    <tr class="bg-white">
                                        <td class="px-3 py-3 text-slate-700"><?= htmlspecialchars($row['session_date']) ?></td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['type']) ?></td>
                                        <td class="px-3 py-3 text-slate-600"><?= htmlspecialchars($row['center_name']) ?></td>
                                        <td class="px-3 py-3">
                                            <span class="rounded-full <?= $row['status'] === 'Present' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?> px-2.5 py-0.5 text-xs font-medium"><?= htmlspecialchars($row['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
