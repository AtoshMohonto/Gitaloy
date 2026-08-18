        <?php
        $ftrSettings = function_exists('getSettings') ? getSettings() : [];
        $ftrText = $ftrSettings['footer_text'] ?? 'Gitaloy — Village Student Management System · Free education for underprivileged village children';
        $ftrSocials = [
            'facebook' => $ftrSettings['social_facebook'] ?? '',
            'youtube' => $ftrSettings['social_youtube'] ?? '',
            'whatsapp' => $ftrSettings['social_whatsapp'] ?? '',
            'instagram' => $ftrSettings['social_instagram'] ?? '',
        ];
        $ftrHasSocials = array_filter($ftrSocials) !== [];
        ?>
        <footer class="border-t border-emerald-100 bg-white py-6 text-center text-xs text-slate-500">
            <p><?= htmlspecialchars($ftrText) ?></p>
            <?php if ($ftrHasSocials): ?>
                <div class="mt-3 flex items-center justify-center gap-3">
                    <?php if ($ftrSocials['facebook'] !== ''): ?>
                        <a href="<?= htmlspecialchars($ftrSocials['facebook']) ?>" target="_blank" rel="noopener" class="grid h-8 w-8 place-items-center rounded-full border border-emerald-100 text-emerald-700 transition hover:bg-emerald-50" aria-label="Facebook"><i data-lucide="facebook" class="h-4 w-4"></i></a>
                    <?php endif; ?>
                    <?php if ($ftrSocials['youtube'] !== ''): ?>
                        <a href="<?= htmlspecialchars($ftrSocials['youtube']) ?>" target="_blank" rel="noopener" class="grid h-8 w-8 place-items-center rounded-full border border-emerald-100 text-emerald-700 transition hover:bg-emerald-50" aria-label="YouTube"><i data-lucide="youtube" class="h-4 w-4"></i></a>
                    <?php endif; ?>
                    <?php if ($ftrSocials['whatsapp'] !== ''): ?>
                        <a href="<?= htmlspecialchars($ftrSocials['whatsapp']) ?>" target="_blank" rel="noopener" class="grid h-8 w-8 place-items-center rounded-full border border-emerald-100 text-emerald-700 transition hover:bg-emerald-50" aria-label="WhatsApp"><i data-lucide="message-circle" class="h-4 w-4"></i></a>
                    <?php endif; ?>
                    <?php if ($ftrSocials['instagram'] !== ''): ?>
                        <a href="<?= htmlspecialchars($ftrSocials['instagram']) ?>" target="_blank" rel="noopener" class="grid h-8 w-8 place-items-center rounded-full border border-emerald-100 text-emerald-700 transition hover:bg-emerald-50" aria-label="Instagram"><i data-lucide="instagram" class="h-4 w-4"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </footer>
    </div>
    <div id="toast-root" class="fixed bottom-5 right-5 z-[60] space-y-2"></div>
    <script src="<?= appBaseUrl() ?>/assets/js/app.js"></script>
</body>
</html>
