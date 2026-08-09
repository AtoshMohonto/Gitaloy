<?php
$activeFile = basename($_SERVER['PHP_SELF']);
$base = appBaseUrl();
$tabs = [
    ['index.php', 'settings', 'Overview'],
    ['geography.php', 'map', 'Geography'],
    ['centers.php', 'school', 'Centers'],
    ['classes.php', 'book-open', 'Classes'],
    ['subjects.php', 'flask-conical', 'Subjects'],
    ['years.php', 'calendar', 'Years'],
    ['users.php', 'users', 'Users'],
    ['fee_heads.php', 'wallet', 'Fee Heads'],
    ['items.php', 'package', 'Items'],
    ['activity.php', 'clock', 'Activity'],
];
?>
<div class="no-print flex flex-wrap gap-2">
    <?php foreach ($tabs as [$file, $icon, $label]): ?>
        <?php $active = $activeFile === $file; ?>
        <a href="<?= $base ?>/admin/<?= $file ?>"
           class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition
                  <?= $active ? 'bg-emerald-900 text-white shadow-sm' : 'border border-emerald-200 bg-white text-slate-700 hover:bg-emerald-50' ?>">
            <i data-lucide="<?= $icon ?>" class="h-4 w-4 <?= $active ? 'text-emerald-300' : 'text-emerald-600' ?>"></i>
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>
