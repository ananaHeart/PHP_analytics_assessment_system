<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'teacher') {
    header("Location: login.php"); exit();
}

$teacher_id = (int) $_SESSION['user']['user_id'];
$test_id = isset($_GET['test_id']) ? (int) $_GET['test_id'] : 0;
if ($test_id <= 0) {
    die("Invalid assessment.");
}

$test_check = $conn->prepare("SELECT t.test_id, t.test_name, t.class_id FROM test t JOIN class c ON t.class_id = c.class_id WHERE t.test_id = ? AND c.user_id = ? LIMIT 1");
$test_check->bind_param("ii", $test_id, $teacher_id);
$test_check->execute();
$test_res = $test_check->get_result();
$test = $test_res->fetch_assoc();

if (!$test) {
    die("Assessment not found or access denied.");
}

$class_id = (int) $test['class_id'];

$keywords_sql = "
    SELECT ct.competency_id, ct.competency_name
    FROM competency_tags ct
    JOIN curriculum cur ON ct.curriculum_id = cur.curriculum_id
    JOIN class c ON c.subject_id = cur.subject_id
    JOIN section sec ON c.section_id = sec.section_id
    WHERE c.class_id = ?
      AND cur.grade_level_id = sec.grade_level_id
    ORDER BY ct.competency_name ASC
";
$keywords_stmt = $conn->prepare($keywords_sql);
$keywords_stmt->bind_param("i", $class_id);
$keywords_stmt->execute();
$keywords_res = $keywords_stmt->get_result();
$keywords = [];
if ($keywords_res) {
    while($k = $keywords_res->fetch_assoc()) {
        $keywords[] = $k;
    }
}

$parts_res = $conn->query("SELECT tp.*, ct.competency_name FROM test_part tp LEFT JOIN competency_tags ct ON tp.competency_id = ct.competency_id WHERE tp.test_id = '$test_id' ORDER BY tp.test_part_id ASC");
$parts = [];
if ($parts_res) {
    while($p = $parts_res->fetch_assoc()) {
        $parts[] = $p;
    }
}
$part_count = count($parts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Test Parts - SMART</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .grid-builder { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-top: 15px; background: #F8FAFC; padding: 20px; border-radius: 12px; }
        .q-row { display: flex; align-items: center; gap: 10px; background: white; padding: 10px; border-radius: 8px; border: 1px solid #EDF2F7; }
        .q-num { font-weight: bold; font-size: 12px; color: var(--text-gray); width: 25px; }
        .btn-opt { width: 30px; height: 30px; border: 1px solid #E2E8F0; background: white; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; }
        .btn-opt.selected { background: var(--primary-green); color: white; border-color: var(--primary-green); }
        .hidden-key { display: none; }
        .parts-editor { margin-top: 24px; }
        .part-editor-card { border: 1px solid #e5ebf2; border-radius: 12px; padding: 14px; margin-bottom: 12px; background: #fafcff; }
        .part-grid { display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; }
        .part-grid textarea { min-height: 72px; resize: vertical; }
        .part-grid .full { grid-column: 1 / -1; }
        @media (max-width: 900px) {
            .part-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="teacher-layout">

    <nav class="top-nav">
        <div style="font-weight:800; font-size:18px;"><span style="color:var(--primary-green);">🎓</span> SMART Assessment</div>
        <a href="view_tests.php?class_id=<?php echo $class_id; ?>" style="color:var(--text-gray); text-decoration:none; font-weight:600;">← Back to Assessment List</a>
    </nav>

    <div class="teacher-container">
        <h1>Edit Assessment Setup</h1>
        <p style="color:var(--text-gray);"><?php echo htmlspecialchars($test['test_name']); ?> | Add or update test parts and answer keys</p>
        <p style="color:var(--text-gray); margin-top:6px;">This assessment becomes usable only after at least one test part is saved.</p>

        <div class="card" style="margin-top:20px;">
            <h3 style="margin-top:0;">Add New Test Part</h3>
            <form action="../actions/save_test_part.php" method="POST" id="partForm">
                <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">

                <div style="display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label>Part Order (e.g. Part I)</label>
                        <input type="text" name="part_order" placeholder="Part I" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label>Part Type</label>
                        <select name="part_type" id="partType" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                            <option value="Multiple Choice">Multiple Choice</option>
                            <option value="Identification">Identification</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; gap:20px; margin-bottom:20px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:160px;">
                        <label>Number of Items</label>
                        <input type="number" name="number_of_items" id="numItems" min="1" max="100" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                    </div>
                    <div style="flex:1; min-width:160px;">
                        <label>Points per Item</label>
                        <input type="number" name="points_per_item" value="1" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                    </div>
                    <div style="flex:2; min-width:220px;">
                        <label>Competency Tag</label>
                        <select name="competency_id" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;">
                            <option value="">-- Select Skill --</option>
                            <?php foreach($keywords as $k): ?>
                                <option value="<?php echo (int) $k['competency_id']; ?>"><?php echo htmlspecialchars($k['competency_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="builderSection">
                    <label><strong>Answer Key Builder</strong></label>
                    <div id="answerGrid" class="grid-builder">
                        <p style="color:var(--text-gray); font-size:13px;">Enter number of items to generate grid...</p>
                    </div>
                    <textarea name="answer_key" id="finalAnswerKey" class="hidden-key" required></textarea>
                </div>

                <div style="margin-top:30px; display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" name="action" value="add" class="btn-green">Add Another Part</button>
                    <button type="submit" name="action" value="finish" class="btn-green" style="background:#2C3E50;">Save Part & Finish Setup</button>
                </div>
            </form>

            <div class="parts-editor">
                <h3 style="margin-top:0;">Edit Existing Parts</h3>
                <?php if (!empty($parts)): ?>
                    <?php foreach($parts as $part): ?>
                        <form action="../actions/update_test_part.php" method="POST" class="part-editor-card">
                            <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
                            <input type="hidden" name="test_part_id" value="<?php echo (int) $part['test_part_id']; ?>">

                            <div style="font-weight:700; margin-bottom:10px; color:#1f2937;">
                                Part <?php echo (int) $part['test_part_id']; ?>
                            </div>

                            <div class="part-grid">
                                <div>
                                    <label>Part Order</label>
                                    <input type="text" name="part_order" value="<?php echo htmlspecialchars($part['part_order'] ?? ''); ?>" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                                </div>
                                <div>
                                    <label>Part Type</label>
                                    <select name="part_type" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                                        <option value="Multiple Choice" <?php echo ($part['part_type'] === 'Multiple Choice') ? 'selected' : ''; ?>>Multiple Choice</option>
                                        <option value="Identification" <?php echo ($part['part_type'] === 'Identification') ? 'selected' : ''; ?>>Identification</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Number of Items</label>
                                    <input type="number" name="number_of_items" min="1" max="100" value="<?php echo (int) ($part['number_of_items'] ?? 1); ?>" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                                </div>
                                <div>
                                    <label>Points per Item</label>
                                    <input type="number" name="points_per_item" min="1" value="<?php echo (int) ($part['points_per_item'] ?? 1); ?>" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                                </div>
                                <div class="full">
                                    <label>Competency Tag</label>
                                    <select name="competency_id" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                                        <option value="">-- Select Skill --</option>
                                        <?php foreach($keywords as $k): ?>
                                            <option value="<?php echo (int) $k['competency_id']; ?>" <?php echo ((int)$part['competency_id'] === (int)$k['competency_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($k['competency_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="full">
                                    <label>Answer Key</label>
                                    <textarea name="answer_key" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required><?php echo htmlspecialchars($part['answer_key'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div style="margin-top:12px;">
                                <button type="submit" class="btn-green" style="margin-top:0;">Update Part</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:var(--text-gray); margin:0;">No parts added yet.</p>
                <?php endif; ?>

                <?php if ($part_count > 0): ?>
                    <div style="margin-top:16px;">
                        <a href="view_tests.php?class_id=<?php echo $class_id; ?>" class="btn-green" style="display:inline-block; background:#1f2937;">
                            Finish Setup
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const numItemsInput = document.getElementById('numItems');
        const answerGrid = document.getElementById('answerGrid');
        const finalKey = document.getElementById('finalAnswerKey');
        const selections = {};

        numItemsInput.addEventListener('input', function() {
            const count = parseInt(this.value, 10);
            answerGrid.innerHTML = '';
            if (!count || count <= 0) return;

            for (let i = 1; i <= count; i++) {
                const div = document.createElement('div');
                div.className = 'q-row';
                div.innerHTML = `
                    <span class="q-num">Q${i}</span>
                    <button type="button" class="btn-opt" onclick="setAns(${i}, 'A', event)">A</button>
                    <button type="button" class="btn-opt" onclick="setAns(${i}, 'B', event)">B</button>
                    <button type="button" class="btn-opt" onclick="setAns(${i}, 'C', event)">C</button>
                    <button type="button" class="btn-opt" onclick="setAns(${i}, 'D', event)">D</button>
                `;
                answerGrid.appendChild(div);
            }
        });

        window.setAns = function(qNum, val, ev) {
            const row = answerGrid.children[qNum - 1];
            const buttons = row.querySelectorAll('.btn-opt');
            buttons.forEach(btn => btn.classList.remove('selected'));

            if (ev && ev.target) {
                ev.target.classList.add('selected');
            }

            selections[qNum] = val;
            const keys = [];
            for (let i = 1; i <= parseInt(numItemsInput.value || '0', 10); i++) {
                keys.push(selections[i] || '?');
            }
            finalKey.value = keys.join(',');
        };
    </script>
</body>
</html>
