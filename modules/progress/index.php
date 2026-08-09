<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../students/student_db.php';
require_once __DIR__ . '/../fees/fee_db.php';

requirePermission('progress.view');

$pdo = getDbConnection();
$yearId = activeYearId();

$group = $_GET['group'] ?? 'class';
if (!in_array($group, ['class', 'village'], true)) {
    $group = 'class';
}

$students = getStudents([]);

$rows = [];
foreach ($students as $student) {
    $att = getStudentAttendanceSummary((int) $student['id'], $yearId);
    $marks = getStudentMarksSummary((int) $student['id'], $yearId);
    $fees = getStudentFeeSummary((int) $student['id'], $yearId);

    $rows[] = [
        'student' => $student,
        'attendance' => $att,
        'marks' => $marks,
        'fees' => $fees,
    ];
}

$groups = [];
foreach ($rows as $row) {
    $key = $group === 'class' ? ($row['student']['class_name'] ?: 'No class') : ($row['student']['village_name'] ?: 'No village');
    if (!isset($groups[$key])) {
        $groups[$key] = ['count' => 0, 'att_sum' => 0, 'mark_pct_sum' => 0, 'fee_billed' => 0, 'fee_paid' => 0];
    }
    $groups[$key]['count']++;
    $groups[$key]['att_sum'] += $row['attendance']['rate'];
    $groups[$key]['mark_pct_sum'] += $row['marks']['pct'];
    $groups[$key]['fee_billed'] += $row['fees']['billed'];
    $groups[$key]['fee_paid'] += $row['fees']['paid'];
}

foreach ($groups as $key => $g) {
    $groups[$key]['att_avg'] = round($g['att_sum'] / max($g['count'], 1), 1);
    $groups[$key]['mark_avg'] = round($g['mark_pct_sum'] / max($g['count'], 1), 1);
    $groups[$key]['fee_due'] = round($g['fee_billed'] - $g['fee_paid'], 2);
}

ksort($groups);

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="flex-1 flex bg-emerald-50/40">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6 lg:p-10">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="relative overflow-hidden rounded-2xl bg-emerald-900 shadow-sm">
    <div class="pointer-events-none absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-600/30 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 right-32 h-52 w-52 rounded-full bg-emerald-500/25 blur-3xl"></div>
    <div class="relative z-10 px-6 py-8 sm:px-8">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-300">Performance overview</p>
        <h1 class="mt-1 text-2xl font-extrabold text-white sm:text-3xl">Progress</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-emerald-100/80">Attendance and task performance grouped <?= $group === 'class' ? 'by class' : 'by village' ?> for the active academic year.</p>
    </div>
</section>

            <div class="flex flex-wrap items-center gap-2">
                <a href="<?= appBaseUrl() ?>/modules/progress/index.php?group=class" class="rounded-full px-5 py-2 text-sm font-semibold <?= $group === 'class' ? 'bg-slate-900 text-white' : 'border border-emerald-200 bg-white text-slate-700' ?>">By class</a>
                <a href="<?= appBaseUrl() ?>/modules/progress/index.php?group=village" class="rounded-full px-5 py-2 text-sm font-semibold <?= $group === 'village' ? 'bg-slate-900 text-white' : 'border border-emerald-200 bg-white text-slate-700' ?>">By village</a>
            </div>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Summary</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3"><?= $group === 'class' ? 'Class' : 'Village' ?></th>
                                <th class="px-3 py-3">Students</th>
                                <th class="px-3 py-3">Attendance</th>
                                <th class="px-3 py-3">Performance</th>
                                <th class="px-3 py-3 text-right">Fee paid</th>
                                <th class="px-3 py-3 text-right">Fee due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($groups)): ?>
                                <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No students found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($groups as $name => $g): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-4 font-semibold text-slate-800"><?= htmlspecialchars($name) ?></td>
                                    <td class="px-3 py-4 text-slate-600"><?= $g['count'] ?></td>
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 w-32 rounded-full bg-slate-200"><div class="h-2 rounded-full <?= $g['att_avg'] >= 75 ? 'bg-emerald-600' : ($g['att_avg'] >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width:<?= min(100, $g['att_avg']) ?>%"></div></div>
                                            <span class="text-xs font-semibold text-slate-700"><?= $g['att_avg'] ?>%</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-2 w-32 rounded-full bg-slate-200"><div class="h-2 rounded-full <?= $g['mark_avg'] >= 75 ? 'bg-emerald-600' : ($g['mark_avg'] >= 50 ? 'bg-amber-500' : 'bg-red-500') ?>" style="width:<?= min(100, $g['mark_avg']) ?>%"></div></div>
                                            <span class="text-xs font-semibold text-slate-700"><?= $g['mark_avg'] ?>%</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right text-emerald-700 font-semibold"><?= number_format($g['fee_paid'], 2) ?></td>
                                    <td class="px-3 py-4 text-right <?= $g['fee_due'] > 0 ? 'text-amber-600 font-semibold' : 'text-slate-500' ?>"><?= number_format($g['fee_due'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900">Student detail (<?= count($rows) ?>)</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-slate-500 border-b border-emerald-100">
                                <th class="px-3 py-3">Student</th>
                                <th class="px-3 py-3">Class</th>
                                <th class="px-3 py-3">Village</th>
                                <th class="px-3 py-3">Attendance</th>
                                <th class="px-3 py-3">Tasks</th>
                                <th class="px-3 py-3 text-right">Fee due</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($rows as $row): ?>
                                <tr class="bg-white">
                                    <td class="px-3 py-4">
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($row['student']['name']) ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($row['student']['student_id']) ?></p>
                                    </td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($row['student']['class_name'] ?: '—') ?></td>
                                    <td class="px-3 py-4 text-slate-600"><?= htmlspecialchars($row['student']['village_name'] ?: '—') ?></td>
                                    <td class="px-3 py-4">
                                        <span class="font-semibold <?= $row['attendance']['rate'] >= 75 ? 'text-emerald-700' : ($row['attendance']['rate'] >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $row['attendance']['rate'] ?>%</span>
                                        <span class="text-xs text-slate-400">(<?= $row['attendance']['present'] ?>/<?= $row['attendance']['total'] ?>)</span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <span class="font-semibold <?= $row['marks']['pct'] >= 75 ? 'text-emerald-700' : ($row['marks']['pct'] >= 50 ? 'text-amber-600' : 'text-red-600') ?>"><?= $row['marks']['pct'] ?>%</span>
                                        <span class="text-xs text-slate-400">(<?= $row['marks']['completed'] ?>/<?= $row['marks']['tasks'] ?> tasks)</span>
                                    </td>
                                    <td class="px-3 py-4 text-right <?= $row['fees']['due'] > 0 ? 'text-amber-600 font-semibold' : 'text-emerald-700' ?>"><?= number_format($row['fees']['due'], 2) ?></td>
                                    <td class="px-3 py-4">
                                        <a href="<?= appBaseUrl() ?>/modules/students/view.php?id=<?= (int) $row['student']['id'] ?>" class="rounded-full border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-slate-700">View</a>
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
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
