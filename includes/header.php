<?php
require_once __DIR__ . '/auth.php';
$base = appBaseUrl();
if (function_exists('getSettings')) {
    $hdrSettings = getSettings();
    $siteLogo = $hdrSettings['site_logo'] ?? '';
    $siteFavicon = $hdrSettings['site_favicon'] ?? '';
} else {
    $siteLogo = '';
    $siteFavicon = '';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Gitaloy — Village Student Management') ?></title>
    <?php if ($siteFavicon !== ''): ?>
        <link rel="icon" href="<?= $base ?>/<?= htmlspecialchars($siteFavicon) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="min-h-screen bg-emerald-50/40 text-slate-800 antialiased">
    <div class="flex min-h-screen flex-col">
        <?php if (!empty($showTopBar) && function_exists('getSettings')): ?>
            <?php
            $tbSettings = $hdrSettings ?? getSettings();
            $tbEmail = $tbSettings['contact_email'] ?? '';
            $tbPhone = $tbSettings['contact_phone'] ?? '';
            $tbSocials = [
                'facebook' => $tbSettings['social_facebook'] ?? '',
                'youtube' => $tbSettings['social_youtube'] ?? '',
                'whatsapp' => $tbSettings['social_whatsapp'] ?? '',
                'instagram' => $tbSettings['social_instagram'] ?? '',
            ];
            $tbHasSocials = array_filter($tbSocials) !== [];
            ?>
            <?php if ($tbEmail !== '' || $tbPhone !== '' || $tbHasSocials): ?>
                <div class="hidden bg-emerald-950 text-emerald-100 sm:block">
                    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6 py-2 text-xs font-semibold lg:px-8">
                        <div class="flex flex-wrap items-center gap-4">
                            <?php if ($tbPhone !== ''): ?>
                                <span class="inline-flex items-center gap-1.5"><i data-lucide="phone" class="h-3.5 w-3.5 text-emerald-400"></i><?= htmlspecialchars($tbPhone) ?></span>
                            <?php endif; ?>
                            <?php if ($tbEmail !== ''): ?>
                                <span class="inline-flex items-center gap-1.5"><i data-lucide="mail" class="h-3.5 w-3.5 text-emerald-400"></i><?= htmlspecialchars($tbEmail) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($tbHasSocials): ?>
                            <div class="flex items-center gap-3">
                                <?php if ($tbSocials['facebook'] !== ''): ?><a href="<?= htmlspecialchars($tbSocials['facebook']) ?>" target="_blank" rel="noopener" class="text-emerald-200 transition hover:text-white" aria-label="Facebook"><i data-lucide="facebook" class="h-3.5 w-3.5"></i></a><?php endif; ?>
                                <?php if ($tbSocials['youtube'] !== ''): ?><a href="<?= htmlspecialchars($tbSocials['youtube']) ?>" target="_blank" rel="noopener" class="text-emerald-200 transition hover:text-white" aria-label="YouTube"><i data-lucide="youtube" class="h-3.5 w-3.5"></i></a><?php endif; ?>
                                <?php if ($tbSocials['whatsapp'] !== ''): ?><a href="<?= htmlspecialchars($tbSocials['whatsapp']) ?>" target="_blank" rel="noopener" class="text-emerald-200 transition hover:text-white" aria-label="WhatsApp"><i data-lucide="message-circle" class="h-3.5 w-3.5"></i></a><?php endif; ?>
                                <?php if ($tbSocials['instagram'] !== ''): ?><a href="<?= htmlspecialchars($tbSocials['instagram']) ?>" target="_blank" rel="noopener" class="text-emerald-200 transition hover:text-white" aria-label="Instagram"><i data-lucide="instagram" class="h-3.5 w-3.5"></i></a><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <header class="sticky top-0 z-30 border-b border-emerald-100 bg-white/95 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <button id="sidebar-open" type="button" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-700 transition hover:bg-emerald-50 lg:hidden" aria-label="Open menu" aria-controls="app-sidebar" aria-expanded="false">
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                    <button id="sidebar-toggle" type="button" class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-700 transition hover:bg-emerald-50 lg:inline-flex" aria-label="Toggle sidebar" aria-controls="app-sidebar" aria-pressed="false" title="Toggle sidebar">
                        <i data-lucide="panel-left" class="h-5 w-5"></i>
                    </button>
                <?php endif; ?>
                <a href="<?= $base ?>/<?= !empty($_SESSION['user_id']) ? 'modules/dashboard/index.php' : 'index.php' ?>" class="flex min-w-0 items-center gap-2.5">
                    <?php if ($siteLogo !== ''): ?>
                        <img src="<?= $base ?>/<?= htmlspecialchars($siteLogo) ?>" alt="Site logo" class="h-9 w-9 shrink-0 rounded-xl border border-emerald-100 object-cover">
                    <?php else: ?>
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-900 text-lg">🌾</span>
                    <?php endif; ?>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-extrabold text-slate-900">Gitaloy</span>
                        <span class="block truncate text-[11px] font-semibold text-emerald-600/80">Village Education Program</span>
                    </span>
                </a>
                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-900">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            <span class="hidden sm:inline">Hello, <?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['role_name'] ?? 'Guest') ?></span>
                        </span>
                        <a href="<?= $base ?>/modules/auth/login.php?logout=1" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-rose-600">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= $base ?>/modules/auth/login.php" class="inline-flex items-center gap-2 rounded-lg bg-emerald-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <i data-lucide="log-in" class="h-4 w-4"></i>Sign in
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <div id="sidebar-backdrop" class="fixed inset-0 z-40 hidden bg-emerald-950/60 backdrop-blur-sm lg:hidden"></div>
