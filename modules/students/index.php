<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/student_db.php';

requirePermission('students.view');

$pdo = getDbConnection();
$success = null;
$error = null;

// Scoped center list for forms
$centerFilter = getCenterScopeFilter();
$centerJoins = getCenterScopeJoins();
$stmt = $pdo->query("SELECT c.id, c.name, v.name AS village_name
                     FROM centers c $centerJoins WHERE $centerFilter ORDER BY c.name");
$scopedCenters = $stmt->fetchAll();

$isTeacher = isTeacher();
$teacherCenterId = $isTeacher ? (int) (currentUser()['center_id'] ?? 0) : null;

$formData = [
    'id' => 0,
    'name' => '',
    'guardian_name' => '',
    'guardian_phone' => '',
    'dob' => '',
    'gender' => '',
    'village_id' => '',
    'center_id' => $teacherCenterId ?: '',
    'class_id' => '',
    'admission_date' => date('Y-m-d'),
    'status' => 'Active',
];

$editStudent = null;
if (isset($_GET['edit'])) {
    $editStudent = getStudentById((int) $_GET['edit']);
    if ($editStudent) {
        $formData = [
            'id' => (int) $editStudent['id'],
            'name' => $editStudent['name'],
            'guardian_name' => $editStudent['guardian_name'],
            'guardian_phone' => $editStudent['guardian_phone'],
            'dob' => $editStudent['dob'],
            'gender' => $editStudent['gender'],
            'village_id' => $editStudent['village_id'],
            'center_id' => $editStudent['center_id'],
            'class_id' => $editStudent['class_id'],
            'admission_date' => $editStudent['admission_date'],
            'status' => $editStudent['status'],
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $formData['name'] = trim($_POST['name'] ?? '');
        $formData['guardian_name'] = trim($_POST['guardian_name'] ?? '');
        $formData['guardian_phone'] = trim($_POST['guardian_phone'] ?? '');
        $formData['dob'] = trim($_POST['dob'] ?? '');
        $formData['gender'] = trim($_POST['gender'] ?? '');
        $formData['village_id'] = trim($_POST['village_id'] ?? '');
        $formData['center_id'] = trim($_POST['center_id'] ?? '');
        $formData['class_id'] = trim($_POST['class_id'] ?? '');
        $formData['admission_date'] = trim($_POST['admission_date'] ?? '');
        $formData['status'] = trim($_POST['status'] ?? 'Active');

        $errors = [];
        if ($formData['name'] === '') {
            $errors[] = 'Student name is required.';
        }
        if ($formData['center_id'] === '') {
            $errors[] = 'Center is required.';
        }
        if ($isTeacher && (int) $formData['center_id'] !== $teacherCenterId) {
            $errors[] = 'You can only assign students to your own center.';
        }
        if ($formData['village_id'] !== '' && !in_array((int) $formData['village_id'], array_map('intval', array_column(getVillages(), 'id')), true)) {
            $errors[] = 'Please choose a valid village.';
        }

        if (count($errors) === 0) {
            $data = [
                'name' => $formData['name'],
                'guardian_name' => $formData['guardian_name'] !== '' ? $formData['guardian_name'] : null,
                'guardian_phone' => $formData['guardian_phone'] !== '' ? $formData['guardian_phone'] : null,
                'dob' => $formData['dob'] !== '' ? $formData['dob'] : null,
                'gender' => $formData['gender'] !== '' ? $formData['gender'] : null,
                'village_id' => $formData['village_id'] !== '' ? (int) $formData['village_id'] : null,
                'center_id' => (int) $formData['center_id'],
                'class_id' => $formData['class_id'] !== '' ? (int) $formData['class_id'] : null,
                'admission_date' => $formData['admission_date'] !== '' ? $formData['admission_date'] : null,
                'status' => $formData['status'],
            ];

            try {
                if ($action === 'update') {
                    updateStudent((int) $formData['id'], $data);
                    $success = 'Student updated successfully.';
                    logActivity('Updated student: ' . $data['name'], 'students');
                } else {
                    $data['student_id'] = nextStudentId();
                    createStudent($data);
                    $success = 'Student added successfully. Student ID: ' . $data['student_id'];
                    logActivity('Added student: ' . $data['name'] . ' (' . $data['student_id'] . ')', 'students');
                }
                $editStudent = null;
                $formData = array_merge($formData, [
                    'id' => 0, 'name' => '', 'guardian_name' => '', 'guardian_phone' => '',
                    'dob' => '', 'gender' => '', 'village_id' => '', 'class_id' => '',
                    'admission_date' => date('Y-m-d'), 'status' => 'Active',
                    'center_id' => $teacherCenterId ?: '',
                ]);
            } catch (Throwable $e) {
                $error = 'Could not save the student: ' . $e->getMessage();
            }
        } else {
            $error = implode(' ', $errors);
        }
    }

    if ($action === 'delete') {
        $deleteId = (int) ($_POST['student_id'] ?? 0);
        try {
            deleteStudent($deleteId);
            $success = 'Student deleted.';
            logActivity('Deleted student #' . $deleteId, 'students');
        } catch (Throwable $e) {
            $error = 'Could not delete student: ' . $e->getMessage();
        }
    }

    if ($action === 'gen_login') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $student = getStudentById($studentId);
        if ($student) {
            $username = $student['student_id'];
            if (studentUsernameExists($username, $student['user_id'] ?? null)) {
                $error = 'A login already exists for this student.';
            } else {
                $plain = 'gitaloy123';
                createStudentLogin($studentId, $username, $plain);
                $success = 'Login created for ' . htmlspecialchars($username) . ' with password "' . $plain . '".';
                logActivity('Created login for student: ' . $username, 'students');
            }
        }
    }

    if ($action === 'reset_pass') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $student = getStudentById($studentId);
        if ($student && !empty($student['user_id'])) {
            $plain = 'gitaloy123';
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($plain, PASSWORD_DEFAULT), $student['user_id']]);
            $success = 'Password reset for ' . htmlspecialchars($student['student_id']) . ' to "' . $plain . '".';
            logActivity('Reset password for student: ' . $student['student_id'], 'students');
        }
    }
}

$filters = [
    'keyword' => trim($_GET['q'] ?? ''),
    'division_id' => trim($_GET['division_id'] ?? ''),
    'district_id' => trim($_GET['district_id'] ?? ''),
    'upazila_id' => trim($_GET['upazila_id'] ?? ''),
    'village_id' => trim($_GET['village_id'] ?? ''),
    'center_id' => trim($_GET['center_id'] ?? ''),
    'class_id' => trim($_GET['class_id'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
];

$students = getStudents(array_filter($filters, fn($v) => $v !== ''));
$divisions = getDivisions();
$classes = getClasses();
$apiBase = appBaseUrl();

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 flex flex-wrap items-end justify-between gap-4 px-6 py-8 sm:px-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Student registry</p>
            <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Students</h1>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Manage village students, guardian details, and their study center with modern zone-wise filtering.</p>
        </div>
        <a href="<?= appBaseUrl() ?>/modules/students/index.php?add=1#student-form" class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-emerald-800 shadow-sm transition hover:bg-emerald-50">
            <i data-lucide="user-plus" class="h-4 w-4"></i>Add student
        </a>
    </div>
</section>

            <?php if ($success !== null): ?>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= $success ?></div>
            <?php endif; ?>
            <?php if ($error !== null): ?>
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $error ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['add']) || $editStudent): ?>
                <section id="student-form" class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-900"><?= $editStudent ? 'Edit student' : 'Add new student' ?></h2>
                    <form class="mt-5 grid gap-4 lg:grid-cols-3" method="post" action="<?= appBaseUrl() ?>/modules/students/index.php" novalidate>
                        <input type="hidden" name="action" value="<?= $editStudent ? 'update' : 'create' ?>">
                        <?php if ($editStudent): ?>
                            <input type="hidden" name="id" value="<?= (int) $editStudent['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Full name</label>
                            <input type="text" name="name" class="w-full rounded-xl border border-emerald-200 px-3 py-2" value="<?= htmlspecialchars($formData['name']) ?>" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Guardian name</label>
                            <input type="text" name="guardian_name" class="w-full rounded-xl border border-emerald-200 px-3 py-2" value="<?= htmlspecialchars($formData['guardian_name']) ?>">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Guardian phone</label>
                            <input type="text" name="guardian_phone" class="w-full rounded-xl border border-emerald-200 px-3 py-2" value="<?= htmlspecialchars($formData['guardian_phone']) ?>">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Date of birth</label>
                            <input type="date" name="dob" class="w-full rounded-xl border border-emerald-200 px-3 py-2" value="<?= htmlspecialchars($formData['dob']) ?>">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Gender</label>
                            <select name="gender" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <option value="">—</option>
                                <option value="Male" <?= $formData['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $formData['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Village</label>
                            <select name="village_id" id="form-village" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <option value="">Select village</option>
                                <?php foreach (getVillages() as $village): ?>
                                    <option value="<?= (int) $village['id'] ?>" <?= (string) $village['id'] === $formData['village_id'] ? 'selected' : '' ?>><?= htmlspecialchars($village['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Study center</label>
                            <select name="center_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2" required>
                                <option value="">Select center</option>
                                <?php foreach ($scopedCenters as $center): ?>
                                    <option value="<?= (int) $center['id'] ?>" <?= (string) $center['id'] === $formData['center_id'] ? 'selected' : '' ?>><?= htmlspecialchars($center['name']) ?><?= !empty($center['village_name']) ? ' — ' . htmlspecialchars($center['village_name']) : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Class</label>
                            <select name="class_id" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <option value="">Select class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= (int) $class['id'] ?>" <?= (string) $class['id'] === $formData['class_id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Admission date</label>
                            <input type="date" name="admission_date" class="w-full rounded-xl border border-emerald-200 px-3 py-2" value="<?= htmlspecialchars($formData['admission_date']) ?>">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Status</label>
                            <select name="status" class="w-full rounded-xl border border-emerald-200 px-3 py-2">
                                <option value="Active" <?= $formData['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $formData['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="lg:col-span-3 flex items-center gap-3">
                            <button class="rounded-full bg-emerald-700 px-6 py-3 font-semibold text-white"><?= $editStudent ? 'Save changes' : 'Add student' ?></button>
                            <?php if ($editStudent): ?>
                                <a href="<?= appBaseUrl() ?>/modules/students/index.php" class="rounded-full border border-emerald-200 px-5 py-3 text-sm font-semibold text-slate-700">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Find students</h2>
                        <p class="text-sm text-slate-600">Search by name/ID/phone or filter by zone, center, and class.</p>
                    </div>
                    <input type="search" id="student-search" placeholder="Live search table..." class="w-full rounded-xl border border-emerald-200 px-4 py-2 text-sm lg:max-w-xs">
                </div>

                <form class="mt-4 grid gap-3 md:grid-cols-4 xl:grid-cols-8" method="get" action="<?= appBaseUrl() ?>/modules/students/index.php">
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['keyword']) ?>" placeholder="Name / ID / phone" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm md:col-span-2 xl:col-span-2">
                    <select name="division_id" id="f-division" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        <option value="">All divisions</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?= (int) $division['id'] ?>" <?= $filters['division_id'] === (string) $division['id'] ? 'selected' : '' ?>><?= htmlspecialchars($division['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="district_id" id="f-district" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        <option value="">All districts</option>
                        <?php foreach (getDistricts($filters['division_id'] !== '' ? (int) $filters['division_id'] : null) as $district): ?>
                            <option value="<?= (int) $district['id'] ?>" <?= $filters['district_id'] === (string) $district['id'] ? 'selected' : '' ?>><?= htmlspecialchars($district['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="upazila_id" id="f-upazila" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        <option value="">All upazilas</option>
                        <?php foreach (getUpazilas($filters['district_id'] !== '' ? (int) $filters['district_id'] : null) as $upazila): ?>
                            <option value="<?= (int) $upazila['id'] ?>" <?= $filters['upazila_id'] === (string) $upazila['id'] ? 'selected' : '' ?>><?= htmlspecialchars($upazila['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="village_id" id="f-village" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        <option value="">All villages</option>
                        <?php foreach (getVillages($filters['upazila_id'] !== '' ? (int) $filters['upazila_id'] : null) as $village): ?>
                            <option value="<?= (int) $village['id'] ?>" <?= $filters['village_id'] === (string) $village['id'] ? 'selected' : '' ?>><?= htmlspecialchars($village['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="center_id" id="f-center" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        <option value="">All centers</option>
                        <?php foreach (getCenters($filters['village_id'] !== '' ? (int) $filters['village_id'] : null) as $center): ?>
                            <option value="<?= (int) $center['id'] ?>" <?= $filters['center_id'] === (string) $center['id'] ? 'selected' : '' ?>><?= htmlspecialchars($center['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="class_id" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        <option value="">All classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>" <?= $filters['class_id'] === (string) $class['id'] ? 'selected' : '' ?>><?= htmlspecialchars($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="rounded-xl border border-emerald-200 px-3 py-2 text-sm">
                        <option value="">Any status</option>
                        <option value="Active" <?= $filters['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $filters['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <div class="flex gap-2 md:col-span-4 xl:col-span-8">
                        <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white">Apply filters</button>
                        <a href="<?= appBaseUrl() ?>/modules/students/index.php" class="rounded-full border border-emerald-200 px-5 py-2 text-sm font-semibold text-slate-700">Clear</a>
                    </div>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Student ID</th>
                                <th class="px-3 py-3">Name</th>
                                <th class="px-3 py-3">Guardian / Phone</th>
                                <th class="px-3 py-3">Village</th>
                                <th class="px-3 py-3">Center</th>
                                <th class="px-3 py-3">Class</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Login</th>
                                <th class="px-3 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="students-table-body" class="divide-y divide-slate-100">
                            <?php if (empty($students)): ?>
                                <tr><td colspan="9" class="px-3 py-10 text-center text-slate-500">No students found. Add your first student.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-4 font-semibold text-slate-800"><?= htmlspecialchars($student['student_id']) ?></td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($student['name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($student['gender'] ?: '—') ?></p>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600">
                                        <p><?= htmlspecialchars($student['guardian_name'] ?: '—') ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($student['guardian_phone'] ?: '') ?></p>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($student['village_name'] ?: '—') ?></td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($student['center_name'] ?: '—') ?></td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($student['class_name'] ?: '—') ?></td>
                                    <td class="px-3 py-4">
                                        <span class="rounded-full <?= $student['status'] === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?> px-2.5 py-0.5 text-xs font-medium"><?= htmlspecialchars($student['status']) ?></span>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600">
                                        <?php if (!empty($student['user_id'])): ?>
                                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">Enabled</span>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="<?= appBaseUrl() ?>/modules/students/view.php?id=<?= (int) $student['id'] ?>" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">View</a>
                                            <a href="<?= appBaseUrl() ?>/modules/students/index.php?edit=<?= (int) $student['id'] ?>#student-form" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Edit</a>
                                            <?php if (empty($student['user_id'])): ?>
                                                <form method="post" action="<?= appBaseUrl() ?>/modules/students/index.php" class="inline">
                                                    <input type="hidden" name="action" value="gen_login">
                                                    <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                                    <button class="rounded-full border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Create login</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" action="<?= appBaseUrl() ?>/modules/students/index.php" class="inline">
                                                    <input type="hidden" name="action" value="reset_pass">
                                                    <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                                    <button class="rounded-full border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50">Reset pass</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" action="<?= appBaseUrl() ?>/modules/students/index.php" class="inline" onsubmit="return confirm('Delete this student and their login?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="student_id" value="<?= (int) $student['id'] ?>">
                                                <button class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>
<input type="hidden" id="api-base" value="<?= appBaseUrl() ?>">
<script src="<?= appBaseUrl() ?>/assets/js/students.js"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
