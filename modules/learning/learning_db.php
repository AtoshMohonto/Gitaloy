<?php
require_once __DIR__ . '/../../includes/helpers.php';

const LEVEL_NAMES = [
    1 => ['Concept Explorer', 'Basic understanding of the concept'],
    2 => ['Practice Player', 'Simple problem solving'],
    3 => ['Skill Builder', 'Mixed exercises'],
    4 => ['Logic Master', 'Explanation & reasoning'],
    5 => ['Exam Challenger', 'Exam-oriented application'],
];

function getChapters(?int $classId = null, ?int $subjectId = null, bool $onlyActive = true): array
{
    $pdo = getDbConnection();
    $where = ['1=1'];
    $params = [];
    if ($onlyActive) {
        $where[] = 'ch.is_active = 1';
    }
    if ($classId) {
        $where[] = 'ch.class_id = ?';
        $params[] = $classId;
    }
    if ($subjectId) {
        $where[] = 'ch.subject_id = ?';
        $params[] = $subjectId;
    }
    $sql = 'SELECT ch.*, cl.name AS class_name, sub.name AS subject_name
            FROM lc_chapters ch
            LEFT JOIN classes cl ON cl.id = ch.class_id
            LEFT JOIN subjects sub ON sub.id = ch.subject_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ch.sort_order ASC, ch.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getChapterById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT ch.*, cl.name AS class_name, sub.name AS subject_name
         FROM lc_chapters ch
         LEFT JOIN classes cl ON cl.id = ch.class_id
         LEFT JOIN subjects sub ON sub.id = ch.subject_id
         WHERE ch.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Creates a chapter and auto-seeds its 5 fixed levels. */
function createChapter(array $data): int
{
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO lc_chapters (class_id, subject_id, title, concept_card_body, sort_order, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['class_id'] ?: null,
            $data['subject_id'] ?: null,
            $data['title'],
            $data['concept_card_body'] !== '' ? $data['concept_card_body'] : null,
            (int) ($data['sort_order'] ?? 0),
            $data['created_by'] ?: null,
        ]);
        $chapterId = (int) $pdo->lastInsertId();

        $levelStmt = $pdo->prepare('INSERT INTO lc_levels (chapter_id, level_no, name, description) VALUES (?, ?, ?, ?)');
        foreach (LEVEL_NAMES as $levelNo => [$name, $desc]) {
            $levelStmt->execute([$chapterId, $levelNo, $name, $desc]);
        }

        $pdo->commit();
        return $chapterId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updateChapter(int $id, array $data): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'UPDATE lc_chapters SET class_id = ?, subject_id = ?, title = ?, concept_card_body = ?, sort_order = ? WHERE id = ?'
    );
    return $stmt->execute([
        $data['class_id'] ?: null,
        $data['subject_id'] ?: null,
        $data['title'],
        $data['concept_card_body'] !== '' ? $data['concept_card_body'] : null,
        (int) ($data['sort_order'] ?? 0),
        $id,
    ]);
}

function deleteChapter(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM lc_chapters WHERE id = ?')->execute([$id]);
}

function toggleChapter(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('UPDATE lc_chapters SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
}

function getLevelsForChapter(int $chapterId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM lc_levels WHERE chapter_id = ? ORDER BY level_no ASC');
    $stmt->execute([$chapterId]);
    return $stmt->fetchAll();
}

function getLevelById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT lv.*, ch.title AS chapter_title, ch.concept_card_body, ch.id AS chapter_id
         FROM lc_levels lv
         JOIN lc_chapters ch ON ch.id = lv.chapter_id
         WHERE lv.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getQuestionsForLevel(int $levelId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM lc_questions WHERE level_id = ? ORDER BY id ASC');
    $stmt->execute([$levelId]);
    return $stmt->fetchAll();
}

function getQuestionById(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM lc_questions WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function createQuestion(array $data): int
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO lc_questions (level_id, question_text, option_a, option_b, option_c, option_d, correct_option, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['level_id'], $data['question_text'],
        $data['option_a'], $data['option_b'], $data['option_c'], $data['option_d'],
        $data['correct_option'], $data['created_by'] ?: null,
    ]);
    return (int) $pdo->lastInsertId();
}

function updateQuestion(int $id, array $data): bool
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'UPDATE lc_questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE id = ?'
    );
    return $stmt->execute([
        $data['question_text'], $data['option_a'], $data['option_b'], $data['option_c'], $data['option_d'],
        $data['correct_option'], $id,
    ]);
}

function deleteQuestion(int $id): bool
{
    $pdo = getDbConnection();
    return $pdo->prepare('DELETE FROM lc_questions WHERE id = ?')->execute([$id]);
}

/** First-visit setup: level 1 unlocked, levels 2-5 locked. Safe to call repeatedly (INSERT IGNORE). */
function ensureProgressRows(int $studentId, int $chapterId): void
{
    $pdo = getDbConnection();
    $levels = getLevelsForChapter($chapterId);
    $stmt = $pdo->prepare('INSERT IGNORE INTO lc_student_progress (student_id, level_id, status) VALUES (?, ?, ?)');
    foreach ($levels as $level) {
        $status = ((int) $level['level_no'] === 1) ? 'unlocked' : 'locked';
        $stmt->execute([$studentId, $level['id'], $status]);
    }
}

/** Progress rows for every level of a chapter, keyed by level_no. */
function getStudentProgressForChapter(int $studentId, int $chapterId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT lv.level_no, lv.id AS level_id, lv.name, p.status, p.score, p.attempts
         FROM lc_levels lv
         LEFT JOIN lc_student_progress p ON p.level_id = lv.id AND p.student_id = ?
         WHERE lv.chapter_id = ?
         ORDER BY lv.level_no ASC'
    );
    $stmt->execute([$studentId, $chapterId]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['status'] = $row['status'] ?? 'locked';
        $out[(int) $row['level_no']] = $row;
    }
    return $out;
}

function getProgressRow(int $studentId, int $levelId): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM lc_student_progress WHERE student_id = ? AND level_id = ?');
    $stmt->execute([$studentId, $levelId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function awardXp(int $studentId, int $amount): void
{
    if ($amount <= 0) {
        return;
    }
    $pdo = getDbConnection();
    $pdo->prepare('UPDATE students SET xp_total = xp_total + ? WHERE id = ?')->execute([$amount, $studentId]);
}

/** Unlocks the next level in sequence, unless it's already completed. */
function unlockNextLevel(int $studentId, int $chapterId, int $currentLevelNo): void
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT id FROM lc_levels WHERE chapter_id = ? AND level_no = ?');
    $stmt->execute([$chapterId, $currentLevelNo + 1]);
    $nextLevelId = $stmt->fetchColumn();
    if (!$nextLevelId) {
        return;
    }
    $pdo->prepare(
        "INSERT INTO lc_student_progress (student_id, level_id, status) VALUES (?, ?, 'unlocked')
         ON DUPLICATE KEY UPDATE status = IF(status = 'completed', status, 'unlocked')"
    )->execute([$studentId, $nextLevelId]);
}

/** Level 1 has no quiz — reading the concept card completes it. +10 XP, matches "Concept understood". */
function markConceptRead(int $studentId, array $level): array
{
    $pdo = getDbConnection();
    $already = getProgressRow($studentId, (int) $level['id']);
    $wasCompleted = $already && $already['status'] === 'completed';

    $pdo->prepare(
        "INSERT INTO lc_student_progress (student_id, level_id, status, attempts, completed_at)
         VALUES (?, ?, 'completed', 1, NOW())
         ON DUPLICATE KEY UPDATE
            status = 'completed',
            attempts = attempts + 1,
            completed_at = COALESCE(completed_at, NOW())"
    )->execute([$studentId, $level['id']]);

    if (!$wasCompleted) {
        awardXp($studentId, 10);
        unlockNextLevel($studentId, (int) $level['chapter_id'], (int) $level['level_no']);
    }

    $badges = checkAndAwardBadges($studentId);
    return ['xp_awarded' => $wasCompleted ? 0 : 10, 'badges' => $badges];
}

/**
 * Scores a level-2..5 quiz submission. Education-safe points, no negative marking:
 * +5 XP per correct answer (always), +10 XP bonus the first time the level is passed,
 * +3 XP consolation on a failed retry — mirrors the "Study Game Framework" points table
 * (Concept understood +10 / Correct answer +5 / Retry after mistake +3, no zero player,
 * no penalties) from Step-By-Step-Learning-Center's readme.
 *
 * @param array $answers question_id => selected option letter ('a'..'d')
 */
function submitLevelAttempt(int $studentId, array $level, array $answers): array
{
    $questions = getQuestionsForLevel((int) $level['id']);
    $total = count($questions);
    $correct = 0;
    $results = [];
    foreach ($questions as $q) {
        $selected = $answers[$q['id']] ?? null;
        $isCorrect = $selected !== null && $selected === $q['correct_option'];
        if ($isCorrect) {
            $correct++;
        }
        $results[] = ['question_id' => (int) $q['id'], 'selected' => $selected, 'correct_option' => $q['correct_option'], 'is_correct' => $isCorrect];
    }

    $scorePct = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
    $passed = $scorePct >= 60;
    $already = getProgressRow($studentId, (int) $level['id']);
    $wasCompleted = $already && $already['status'] === 'completed';

    $pdo = getDbConnection();
    $status = ($passed || $wasCompleted) ? 'completed' : 'unlocked';
    $pdo->prepare(
        "INSERT INTO lc_student_progress (student_id, level_id, status, score, attempts, completed_at)
         VALUES (?, ?, ?, ?, 1, ?)
         ON DUPLICATE KEY UPDATE
            status = ?,
            score = GREATEST(score, VALUES(score)),
            attempts = attempts + 1,
            completed_at = CASE WHEN ? = 'completed' THEN COALESCE(completed_at, NOW()) ELSE completed_at END"
    )->execute([
        $studentId, $level['id'], $status, $scorePct, $passed ? date('Y-m-d H:i:s') : null,
        $status, $status,
    ]);

    $xp = $correct * 5;
    if ($passed && !$wasCompleted) {
        $xp += 10;
        unlockNextLevel($studentId, (int) $level['chapter_id'], (int) $level['level_no']);
    } elseif (!$passed) {
        $xp += 3;
    }
    awardXp($studentId, $xp);

    $badges = checkAndAwardBadges($studentId);

    return [
        'total' => $total, 'correct' => $correct, 'score_pct' => $scorePct,
        'passed' => $passed, 'xp_awarded' => $xp, 'results' => $results, 'badges' => $badges,
    ];
}

/** Evaluates lc_badges criteria for a student and awards any newly earned ones. */
function checkAndAwardBadges(int $studentId): array
{
    $pdo = getDbConnection();

    $stmt1 = $pdo->prepare(
        "SELECT COUNT(DISTINCT lv.chapter_id) c FROM lc_student_progress p
         JOIN lc_levels lv ON lv.id = p.level_id
         WHERE p.student_id = ? AND lv.level_no = 1 AND p.status = 'completed'"
    );
    $stmt1->execute([$studentId]);
    $conceptsMastered = (int) $stmt1->fetch()['c'];

    $stmt2 = $pdo->prepare(
        "SELECT COUNT(*) c FROM (
            SELECT lv.chapter_id FROM lc_student_progress p
            JOIN lc_levels lv ON lv.id = p.level_id
            WHERE p.student_id = ? AND p.status = 'completed'
            GROUP BY lv.chapter_id
            HAVING COUNT(*) >= 5
         ) t"
    );
    $stmt2->execute([$studentId]);
    $chaptersFinished = (int) $stmt2->fetch()['c'];

    $stmt3 = $pdo->prepare('SELECT COALESCE(SUM(attempts), 0) c FROM lc_student_progress WHERE student_id = ?');
    $stmt3->execute([$studentId]);
    $totalAttempts = (int) $stmt3->fetch()['c'];

    $stats = [
        'concepts_mastered' => $conceptsMastered,
        'chapters_finished' => $chaptersFinished,
        'total_attempts' => $totalAttempts,
    ];

    $badges = $pdo->query('SELECT * FROM lc_badges')->fetchAll();
    $newlyAwarded = [];
    $insert = $pdo->prepare('INSERT IGNORE INTO lc_student_badges (student_id, badge_id) VALUES (?, ?)');
    foreach ($badges as $badge) {
        $have = $stats[$badge['criteria_type']] ?? 0;
        if ($have >= (int) $badge['criteria_value']) {
            $insert->execute([$studentId, $badge['id']]);
            if ($insert->rowCount() > 0) {
                $newlyAwarded[] = $badge;
            }
        }
    }
    return $newlyAwarded;
}

function getStudentBadges(int $studentId): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT b.*, sb.earned_at FROM lc_student_badges sb
         JOIN lc_badges b ON b.id = sb.badge_id
         WHERE sb.student_id = ? ORDER BY sb.earned_at DESC'
    );
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}
