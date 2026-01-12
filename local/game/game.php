<?php
session_start();

/**
 * НАСТРОЙКА УРОВНЕЙ
 *
 * Каждый уровень — это:
 * - цель (какой вывод нужно получить)
 * - стартовый код (данные)
 * - описание задачи
 * - сложность
 *
 * Игра берёт стартовый код, добавляет к нему код игрока, запускает и
 * сравнивает фактический вывод с ожидаемым.
 */

$levels = [
    1 => [
        'difficulty'   => 'easy',
        'title'        => 'Привет, кинотеатр!',
        'story'        => 'Директор хочет увидеть простое приветствие на экране. Это твоя первая строка на PHP.',
        'instruction'  => 'Напиши код, который выведет строку: Привет, кинотеатр!',
        'given_code'   => "// Здесь пока нет данных.\n// Просто выведи нужную строку.\n",
        'expected_output' => "Привет, кинотеатр!",
        'must_contain' => [], // можно оставить пустым
        'hint'         => 'Попробуй: echo "Привет, кинотеатр!";'
    ],
    2 => [
        'difficulty'   => 'easy',
        'title'        => 'Имя кинотеатра',
        'story'        => 'Теперь у кинотеатра есть имя, и директор не хочет, чтобы оно было забито в коде «жёстко» — используй переменную.',
        'instruction'  => 'Используя переменную $cinemaName, выведи: Добро пожаловать в BigCity Cinema!',
        'given_code'   => "\$cinemaName = \"BigCity Cinema\";\n",
        'expected_output' => "Добро пожаловать в BigCity Cinema!",
        'must_contain' => ['$cinemaName'],
        'hint'         => 'Подумай про конкатенацию: echo \"Добро пожаловать в \" . $cinemaName . \"!\";'
    ],
    3 => [
        'difficulty'   => 'medium',
        'title'        => 'Сколько рядов в зале?',
        'story'        => 'В системе уже есть номера рядов. Кассиру нужно быстро узнать их количество.',
        'instruction'  => 'Используя массив $rows, выведи одно число — количество рядов.',
        'given_code'   => "\$rows = [1, 2, 3, 4, 5];\n",
        'expected_output' => "5",
        'must_contain' => ['count('],
        'hint'         => 'Вспомни функцию count($rows); и не забудь вывести результат через echo.'
    ],
    4 => [
        'difficulty'   => 'medium',
        'title'        => 'Парсинг мест в ряду',
        'story'        => 'Администратор записывает места в строку, а тебе нужно превратить это в данные.',
        'instruction'  => 'Из строки $row сделай массив мест и выведи количество мест в ряду (одно число).',
        'given_code'   => "\$row = \"1A,1B,1C,1D\";\n",
        'expected_output' => "4",
        'must_contain' => ['explode(', 'count('],
        'hint'         => 'Типичный шаблон: $seats = explode(\',\', $row); echo count($seats);'
    ],
    5 => [
        'difficulty'   => 'hard',
        'title'        => 'Только чётные ряды',
        'story'        => 'Для VIP-покупки нужны только чётные ряды. Отфильтруй зал.',
        'instruction'  => 'Используя массив $rows, выведи НОВЫЙ массив только чётных рядов в формате: 2,4,6',
        'given_code'   => "\$rows = [1, 2, 3, 4, 5, 6];\n",
        'expected_output' => "2,4,6",
        'must_contain' => ['foreach', 'if'],
        'hint'         => 'Пройди по массиву foreach-ом, проверяй $row % 2 === 0, складывай в новый массив и выведи implode(\',\', $evenRows);'
    ],
];

/**
 * ИНИЦИАЛИЗАЦИЯ ПРОГРЕССА
 */
$totalLevels = count($levels);

if (!isset($_SESSION['max_level'])) {
    $_SESSION['max_level'] = 1;
}
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 0;
}

/**
 * СБРОС ПРОГРЕССА
 */
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    session_destroy();
    header('Location: index.php');
    exit;
}

/**
 * ТЕКУЩИЙ УРОВЕНЬ
 */
$currentLevelId = isset($_GET['level']) ? (int)$_GET['level'] : 1;
if ($currentLevelId < 1 || $currentLevelId > $totalLevels) {
    $currentLevelId = 1;
}
if ($currentLevelId > $_SESSION['max_level']) {
    $currentLevelId = $_SESSION['max_level'];
}

/**
 * ФУНКЦИЯ ЗАПУСКА КОДА ИГРОКА
 *
 * - объединяем стартовый код и код пользователя
 * - убираем <?php и ?>
 * - запускаем через eval
 * - забираем вывод
 */
function run_user_code(string $givenCode, string $userCode, ?string &$error): string
{
    $error = null;

    // убираем возможные теги PHP
    $userCode = preg_replace('/^\s*<\?(php)?/i', '', $userCode);
    $userCode = preg_replace('/\?>\s*$/', '', $userCode);

    $fullCode = $givenCode . "\n" . $userCode;

    // буфер вывода
    ob_start();
    try {
        eval($fullCode);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    $output = ob_get_clean();

    // нормализуем переносы строк
    $output = str_replace(["\r\n", "\r"], "\n", $output);

    return $output;
}

/**
 * ОБРАБОТКА ОТВЕТА ИГРОКА
 */
$message = '';
$messageType = ''; // success | error | info
$lastOutput = '';
$lastError = '';
$rawUserCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'answer') {
    $levelId = (int)($_POST['level_id'] ?? 0);
    $rawUserCode = (string)($_POST['code'] ?? '');

    if (isset($levels[$levelId])) {
        $level = $levels[$levelId];

        $error = null;
        $output = run_user_code($level['given_code'], $rawUserCode, $error);

        $lastOutput = $output;
        $lastError  = $error;

        $expected = str_replace(["\r\n", "\r"], "\n", $level['expected_output']);

        $trimOutput   = trim($output);
        $trimExpected = trim($expected);

        // проверка must_contain (например использование foreach, explode, переменной)
        $missingParts = [];
        if (!empty($level['must_contain'])) {
            foreach ($level['must_contain'] as $piece) {
                if (stripos($rawUserCode, $piece) === false) {
                    $missingParts[] = $piece;
                }
            }
        }

        if ($error !== null) {
            $message = "❌ В коде ошибка: {$error}";
            $messageType = 'error';
        } elseif ($trimOutput !== $trimExpected) {
            $message = "❌ Вывод не совпадает с ожидаемым. Подсказка: " . $level['hint'];
            $messageType = 'error';
        } elseif (!empty($missingParts)) {
            $msgParts = implode(', ', $missingParts);
            $message  = "⚠️ Результат правильный, но ты не использовал(а) нужные конструкции: {$msgParts}. Попробуй переписать решение.";
            $messageType = 'error';
        } else {
            $message = "✅ Идеально! Уровень пройден.";
            $messageType = 'success';

            // очки в зависимости от сложности
            $addScore = 10;
            if ($level['difficulty'] === 'medium') $addScore = 15;
            if ($level['difficulty'] === 'hard')   $addScore = 25;

            $_SESSION['score'] += $addScore;

            if ($_SESSION['max_level'] < $levelId + 1 && $levelId < $totalLevels) {
                $_SESSION['max_level'] = $levelId + 1;
            }
        }

        $currentLevelId = $levelId;
    }
}

$currentLevel = $levels[$currentLevelId];
$score = $_SESSION['score'];
$maxLevelUnlocked = $_SESSION['max_level'];

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>PHP Cinema Quest — Code Hunt по PHP</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at top, #1e293b 0, #020617 60%);
            color: #e5e7eb;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 1080px;
            margin: 0 auto;
            padding: 20px 16px 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .title {
            font-size: 24px;
            font-weight: 800;
        }
        .subtitle {
            font-size: 13px;
            color: #9ca3af;
        }
        .score-box {
            text-align: right;
        }
        .score-box span {
            display: block;
            font-size: 13px;
        }
        .score-value {
            font-size: 20px;
            font-weight: 700;
            color: #facc15;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            border: 1px solid #4b5563;
            background: rgba(15,23,42,.7);
        }
        .levels-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }
        .level-btn {
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid #4b5563;
            background: #020617;
            color: #e5e7eb;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, transform .1s, border-color .15s;
        }
        .level-btn:hover {
            background: #111827;
            transform: translateY(-1px);
        }
        .level-btn.current {
            background: #22c55e;
            border-color: #bbf7d0;
            color: #022c22;
            font-weight: 600;
        }
        .level-btn.locked {
            opacity: .4;
            cursor: not-allowed;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.1fr);
            gap: 16px;
        }
        @media (max-width: 900px) {
            .layout {
                grid-template-columns: minmax(0, 1fr);
            }
            .score-box {
                text-align: left;
            }
        }

        .card {
            background: rgba(15,23,42,.92);
            border-radius: 16px;
            padding: 16px 16px 18px;
            box-shadow: 0 18px 35px rgba(0,0,0,.5);
            border: 1px solid #1f2937;
        }
        .card h2 {
            margin-top: 0;
            margin-bottom: 6px;
            font-size: 18px;
        }
        .difficulty {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .1em;
            opacity: .7;
            margin-bottom: 6px;
        }
        .difficulty.easy { color: #22c55e; }
        .difficulty.medium { color: #3b82f6; }
        .difficulty.hard { color: #f97316; }

        .story {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 10px;
        }
        .instruction {
            font-size: 14px;
            margin-bottom: 10px;
        }

        pre.code {
            background: #020617;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            overflow-x: auto;
            border: 1px solid #111827;
        }
        pre.code code {
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .textarea {
            width: 100%;
            min-height: 150px;
            border-radius: 12px;
            border: 1px solid #4b5563;
            background: #020617;
            color: #e5e7eb;
            padding: 8px 10px;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 13px;
            resize: vertical;
        }

        .label-small {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background .15s, transform .1s, box-shadow .15s;
        }
        .btn-primary {
            background: #22c55e;
            color: #022c22;
            box-shadow: 0 10px 22px rgba(34,197,94,.45);
        }
        .btn-primary:hover {
            background: #4ade80;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #111827;
            color: #e5e7eb;
        }
        .btn-secondary:hover {
            background: #1f2937;
            transform: translateY(-1px);
        }
        .btn-small {
            padding: 4px 10px;
            font-size: 11px;
        }

        .footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .message {
            margin-top: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 13px;
        }
        .message.success {
            background: rgba(22,163,74,.12);
            border: 1px solid rgba(22,163,74,.8);
            color: #bbf7d0;
        }
        .message.error {
            background: rgba(248,113,113,.08);
            border: 1px solid rgba(248,113,113,.8);
            color: #fecaca;
        }

        .output-block {
            margin-top: 8px;
        }
        .output-label {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 3px;
        }
        pre.output {
            background: #020617;
            border-radius: 10px;
            padding: 6px 8px;
            font-size: 12px;
            overflow-x: auto;
            border: 1px solid #111827;
            min-height: 40px;
        }

        .reset-link {
            font-size: 11px;
            color: #fda4af;
            text-decoration: none;
        }
        .reset-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div>
            <div class="title">PHP Cinema Quest 🎬</div>
            <div class="subtitle">
                Мини Code Hunt по PHP: пишешь код → получаешь правильный вывод → открываешь новые уровни
            </div>
        </div>
        <div class="score-box">
            <span>Очки:</span>
            <span class="score-value"><?php echo (int)$score; ?></span>
            <span><span class="badge">Уровень: <?php echo (int)$maxLevelUnlocked; ?> / <?php echo (int)$totalLevels; ?></span></span>
        </div>
    </div>

    <div class="levels-nav">
        <?php foreach ($levels as $id => $lvl): ?>
            <?php
            $locked = $id > $maxLevelUnlocked;
            $classes = ['level-btn'];
            if ($id == $currentLevelId) $classes[] = 'current';
            if ($locked) $classes[] = 'locked';
            ?>
            <?php if ($locked): ?>
                <span class="<?php echo implode(' ', $classes); ?>">
                    L<?php echo $id; ?> · <?php echo htmlspecialchars($lvl['title'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            <?php else: ?>
                <a class="<?php echo implode(' ', $classes); ?>"
                   href="?level=<?php echo $id; ?>">
                    L<?php echo $id; ?> · <?php echo htmlspecialchars($lvl['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="layout">
        <!-- Левая колонка: описание уровня -->
        <div class="card">
            <div class="difficulty <?php echo htmlspecialchars($currentLevel['difficulty'], ENT_QUOTES, 'UTF-8'); ?>">
                Сложность · <?php echo strtoupper($currentLevel['difficulty']); ?>
            </div>
            <h2><?php echo htmlspecialchars($currentLevel['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="story">
                <?php echo htmlspecialchars($currentLevel['story'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="instruction">
                <?php echo htmlspecialchars($currentLevel['instruction'], ENT_QUOTES, 'UTF-8'); ?>
            </div>

            <div class="label-small">Дано (стартовый код):</div>
            <pre class="code"><code><?php echo htmlspecialchars("<?php\n" . $currentLevel['given_code'], ENT_QUOTES, 'UTF-8'); ?></code></pre>

            <div class="label-small" style="margin-top:10px;">Ожидаемый вывод:</div>
            <pre class="code"><code><?php echo htmlspecialchars($currentLevel['expected_output'], ENT_QUOTES, 'UTF-8'); ?></code></pre>

            <div style="margin-top:12px; font-size:11px; color:#9ca3af;">
                Подсказка по структуре:
                <br>– не добавляй в редактор <code>&lt;?php</code> и <code>?&gt;</code>, игра сама запускает твой код;
                <br>– пиши только PHP-код (echo, переменные, циклы...).
            </div>

            <div style="margin-top:12px;">
                <?php if ($maxLevelUnlocked > 1 || $score > 0): ?>
                    <a class="reset-link" href="?reset=1" onclick="return confirm('Сбросить прогресс и очки?');">
                        🔄 Сбросить прогресс
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Правая колонка: редактор кода + вывод -->
        <div class="card">
            <form method="post">
                <input type="hidden" name="action" value="answer">
                <input type="hidden" name="level_id" value="<?php echo (int)$currentLevelId; ?>">

                <div class="label-small">Твой код:</div>
                <textarea
                    class="textarea"
                    name="code"
                    placeholder="// Пиши свой PHP-код здесь. Пример:\n// echo \"Привет, кинотеатр!\";"
                ><?php echo htmlspecialchars($rawUserCode, ENT_QUOTES, 'UTF-8'); ?></textarea>

                <div class="footer-actions">
                    <button type="submit" class="btn btn-primary">
                        ▶️ Запустить и проверить
                    </button>

                    <div>
                        <?php if ($currentLevelId < $maxLevelUnlocked && $currentLevelId < $totalLevels): ?>
                            <a class="btn btn-secondary btn-small"
                               href="?level=<?php echo $currentLevelId + 1; ?>">
                                ➡️ Следующий уровень
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if ($message): ?>
                <div class="message <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="output-block">
                <div class="output-label">Твой вывод:</div>
                <pre class="output"><code><?php echo htmlspecialchars($lastOutput, ENT_QUOTES, 'UTF-8'); ?></code></pre>
            </div>

            <?php if ($lastError): ?>
                <div class="output-block">
                    <div class="output-label">Ошибка PHP:</div>
                    <pre class="output"><code><?php echo htmlspecialchars($lastError, ENT_QUOTES, 'UTF-8'); ?></code></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
