<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=ajax
[END_COT_EXT]
==================== */


/**
 * Template Editor — AJAX-обработчик сохранения, чтения и скачивания
 * .tpl файлов.
 *
 * Файл: plugins/tpleditor/tpleditor.ajax.php
 *
 * Назначение
 * ----------
 * Единственная точка приёма AJAX-запросов от редактора плагина
 * (admin.php?m=other&p=tpleditor при выбранном файле). Вызывается
 * фронтендом через fetch() на URL plug.php?r=tpleditor:
 *   - кнопка «Сохранить» (EDITOR) → action=save, POST;
 *   - кнопка «Скачать резервную копию» (BACKUP_URL в шаблоне) →
 *     action=download, GET;
 *   - action=read — резервное действие без UI-вызова в текущей версии
 *     шаблонов (в tpleditor.editor.tpl нет обращения к нему).
 *
 * Обработчик принимает действие и путь файла, проверяет права и CSRF,
 * валидирует путь через tpleditor_validate_path(), выполняет чтение,
 * запись или отдачу файла и возвращает JSON (кроме download, где
 * отдаётся сам файл). Рендеринга HTML и подключения шаблонов не делает.
 *
 * Регистрация
 * -----------
 * Хук ajax в блоке BEGIN_COT_EXT. Router (system/router/Router.php,
 * метод route()) при запросе plug.php?r=tpleditor:
 *   $ajax = cot_import('r', 'G', 'ALP');          // 'tpleditor'
 *   $extensionCode = 'tpleditor';
 *   $extensionType = ExtensionsService::getType('tpleditor'); // 'plug'
 *   processController(...) → null (контроллера нет);
 *   processFrontIncludeFiles('tpleditor', 'plug', true, false):
 *     $hook = 'ajax';
 *     $route->includeFiles = cot_getextplugins('ajax', true,
 *                                             'tpleditor', 'plug');
 *   Внутри cot_getextplugins() (system/functions.php) для каждого
 *   обработчика проверяется:
 *     cot_auth($cat, $opt, $permission),
 *   где для плагина $cat='plug', $opt='tpleditor', $permission='R'
 *   (значение по умолчанию). То есть ядро уже отсекает запросы от
 *   групп без права R на плагин tpleditor до include этого файла.
 *
 * Права доступа (двойной барьер)
 * ------------------------------
 * Первый (ядровой): cot_getextplugins('ajax', ..., 'plug') требует
 * R на plug/tpleditor (см. выше).
 *
 * Второй (внутри файла):
 *   if (!cot_auth('admin', 'a', 'A') && !cot_auth('plug', 'tpleditor', 'A'))
 *       → 403 Forbidden
 *
 * Третий аргумент 'A' в обоих вызовах обязателен. По контракту
 * cot_auth($area, $option, $mask): если длина $mask > 1 символа —
 * возвращается массив [R,W,A], и массив всегда truthy в PHP. Без явной
 * маски 'A' проверка была бы всегда ложной. С маской из одного символа
 * возвращается скаляр bool — корректная семантика.
 * Итог: доступ получают только пользователи с правом A на admin
 * или с правом A на сам плагин.
 *
 * CSRF
 * ----
 * Проверка зависит от action:
 *   - action=download → cot_check_xg(false).
 *     cot_check_xg() читает GET-параметр x и сравнивает с Cot::$sys['xk']
 *     (или Cot::$sys['xk_prev']). Аргумент false означает «не редиректить
 *     на message, вернуть bool» — ответ будет JSON 400.
 *     GET не проверяется ядром (common.php обрабатывает x только для
 *     POST), поэтому эта проверка — единственный CSRF-барьер для
 *     download.
 *   - остальные action → cot_check_xp().
 *     cot_check_xp() (system/functions.php): если COT_NO_ANTIXSS или
 *     COT_AUTH не определены — возвращает isset($_POST['x']).
 *     Для обычного POST common.php раньше уже выполнил cot_die_message(950),
 *     если x не совпал с $sys['xk'] (см. блок «Anti-XSS protection»),
 *     так что здесь cot_check_xp() фактически дополнительный фильтр:
 *     он отсекает запросы с x в GET (common.php берёт GET-x как
 *     fallback, а isset($_POST['x']) тогда = false).
 *
 * Подавление вывода и буферизация
 * -------------------------------
 * В начале файла:
 *   ini_set('display_errors', '0'); error_reporting(0);
 *   ob_start();
 * — любые PHP-предупреждения (в т.ч. внутри file_get_contents,
 * file_put_contents, readfile) не испортят JSON. Все ветки завершаются
 * ob_end_clean(); header('Content-Type: application/json; charset=utf-8');
 * echo json_encode($response); exit;
 *
 * В ветке download буферы очищаются в цикле
 *   while (ob_get_level() > 0) { ob_end_clean(); }
 * перед readfile(), чтобы большой файл не буферизовался в памяти и
 * побочный вывод не попал в поток байтов .tpl.
 *
 * Формат запроса
 * --------------
 * Метод: POST для action=save и action=read; GET для action=download
 * (URL из BACKUP_URL в tpleditor.editor.tpl).
 * Параметры читаются напрямую из $_POST с fallback на $_GET (без
 * cot_import):
 *   - a       — action: 'save' | 'read' | 'download'. Остальные значения
 *               приводят к RuntimeException('Unknown action').
 *   - file    — относительный путь к .tpl (trim). Валидация ниже.
 *   - content — тело файла для action=save. Наличие поля определяется
 *               через array_key_exists('content', $_POST), а не через
 *               пустоту — пустая строка допустима (запись пустого файла
 *               технически разрешена, но см. проверку маркеров).
 *   - x       — CSRF-токен Cotonti (Cot::$sys['xk']):
 *               POST для save/read, GET для download.
 *
 * Режимы работы
 * -------------
 * 1. action === 'download' (GET):
 *      cot_check_xg(false) → 400 при ошибке;
 *      is_readable($abs) → 404, если файл исчез между валидацией и
 *        readfile (валидация уже проверяет is_file, но окно есть);
 *      заголовки:
 *        Content-Type: application/octet-stream
 *        Content-Disposition: attachment; filename="basename($abs)"
 *        Content-Length: filesize($abs)
 *        X-Content-Type-Options: nosniff
 *      readfile($abs); exit — тело файла отдаётся как есть.
 *
 * 2. action === 'read' (POST):
 *      is_readable → RuntimeException('File not readable') → 200 +
 *        success:false;
 *      иначе response = ['success' => true, 'content' => file_get_contents].
 *
 * 3. action === 'save' (POST):
 *      is_writable → RuntimeException('File not writable');
 *      $original = file_get_contents($abs) — для проверки ниже;
 *      защита от потери маркеров шаблона:
 *        если $original !== false
 *        && preg_match('/<!--\s*BEGIN\s*:/i', $original)
 *        && !preg_match('/<!--\s*BEGIN\s*:/i', $content)
 *        → RuntimeException('Content rejected: template markers missing').
 *        Смысл: если в исходнике присутствовал Cotonti-маркер
 *        <!-- BEGIN: ... -->, а в новом содержимом нет ни одного такого
 *        (case-insensitive), запись отклоняется — защита от случайного
 *        сохранения пустого/обрезанного файла. Проверяется только BEGIN,
 *        не END/IF/FOR; достаточно одного совпадения, чтобы пройти.
 *      file_put_contents($abs, $content) → при false RuntimeException;
 *      очистка кеша Cotonti: if (!empty(Cot::$cache)) Cot::$cache->clear();
 *      response = ['success' => true,
 *                  'message' => Cot::$L['tpleditor_saved'] ?? 'Saved',
 *                  'bytes'   => $bytes].
 *
 * 4. Любой другой $action:
 *      RuntimeException('Unknown action') → 200 + success:false + текст.
 *
 * Валидация пути (tpleditor_validate_path)
 * ----------------------------------------
 * Вызывается для всех трёх действий до открытия файла. Реализация
 * — plugins/tpleditor/inc/tpleditor.functions.php. Проверки:
 *   - расширение .tpl (регистронезависимо);
 *   - нет \0;
 *   - нет сегментов '', '.', '..';
 *   - префикс пути должен совпадать с одним из корней из
 *     tpleditor_roots() (theme, admin theme, plugins/<code>/tpl,
 *     modules/<code>/tpl, system/admin/tpl; какие именно — определяют
 *     конфиги edit_themes/edit_admin/edit_plugins/edit_modules);
 *   - realpath файла внутри realpath базовой директории Cotonti;
 *   - realpath файла внутри realpath выбранного корня (защита от
 *     симлинк-побега);
 *   - is_file.
 * Возвращает реальный абсолютный путь или null. null → 400 Invalid path.
 *
 * Формат ответа
 * -------------
 * Все ветки, кроме download, отвечают JSON:
 *   Content-Type: application/json; charset=utf-8
 *   { "success": true|false, "message": "...", "content"?: "...",
 *     "bytes"?: int }
 *
 * HTTP-коды, которые выставляются явно:
 *   403 — не выполнены условия cot_auth(...) — Forbidden;
 *   400 — невалидный CSRF (download через cot_check_xg(false),
 *         save/read через cot_check_xp());
 *   400 — пустой file или отсутствует content для не-download
 *         (Invalid file);
 *   400 — tpleditor_validate_path вернул null (Invalid path);
 *   404 — download, is_readable($abs) == false.
 *
 * HTTP-код при catch(Throwable):
 *   http_response_code явно не выставляется, ответ уходит с кодом по
 *   умолчанию (200) и success:false + текст исключения. Это касается
 *   RuntimeException('File not readable' / 'File not writable' /
 *   'Content rejected: template markers missing' / 'Unknown action' /
 *   'Failed to write file').
 *
 * Инициализация $response
 * -----------------------
 * До try-блока: $response = ['success' => false, 'message' => 'Unknown error'].
 * Это дефолт на случай непредвиденного поведения, при котором ни одна
 * ветка не присвоит $response (в текущем коде недостижимо — все ветки
 * либо exit, либо присваивают $response).
 *
 * Зависимости
 * -----------
 *   cot_incfile('tpleditor', 'plug')
 *     → plugins/tpleditor/inc/tpleditor.functions.php
 *       (tpleditor_flag, tpleditor_roots, tpleditor_validate_path,
 *        tpleditor_list_files)
 *   cot_langfile('tpleditor', 'plug')
 *     → plugins/tpleditor/lang/tpleditor.<lang>.lang.php
 *       (admin.other.php подключает его же для страницы, но при AJAX
 *        admin.other.php не выполняется, поэтому подгрузка нужна
 *        именно здесь)
 *   cot_check_xg / cot_check_xp       — system/functions.php
 *   cot_auth                          — system/functions.php
 *   Cot::$sys['xk']                   — system/common.php
 *   Cot::$cache                       — system/Cot.php, Cache
 *   Cot::$L['tpleditor_saved']        — lang-файл плагина
 *
 * Логирование
 * -----------
 * Файл сам в лог не пишет. Ни успешные сохранения, ни отказы
 * (CSRF, путь, права) в лог не попадают.
 *
 * Особенности и ограничения
 * -------------------------
 *   - $_POST['content'] идёт в file_put_contents без санитайза —
 *     содержимое .tpl записывается как есть. Это осознанное поведение
 *     редактора; барьеры — права A на admin/плагин, CSRF, валидация
 *     пути, защита от потери маркеров;
 *   - action=read в текущих шаблонах плагина не используется
 *     (tpleditor.editor.tpl не вызывает read; содержимое приходит из
 *     tpleditor.tools.php через assign FILE_CONTENT при серверном
 *     рендере);
 *   - Content-Disposition использует basename($abs) без дополнительной
 *     очистки имени файла — безопасно для типовых имён .tpl;
 *   - при параллельной работе двух администраторов возможна ситуация
 *     «файл удалён между валидацией и readfile» (download) или
 *     «file_put_contents вернул false» (save, если файл стал
 *     read-only) — оба варианта корректно возвращают ошибку;
 *   - обработчик не создаёт резервных копий перед save. Восстановление
 *     возможно только из внешнего источника (git, ручной бэкап).
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СВЯЗАННЫЕ ССЫЛКИ
 * ═════════════════════════════════════════════════════════════════════
 *   Router:          system/router/Router.php::route
 *   Hook resolve:    system/functions.php::cot_getextplugins
 *   Auth:            system/functions.php::cot_auth / cot_block
 *   CSRF:            system/functions.php::cot_check_xg / cot_check_xp
 *   Error kill:      system/common.php (блок Anti-XSS protection)
 *   Cache:           system/Cot.php::$cache
 *   Cotonti:         https://github.com/Cotonti/Cotonti
 *
 * ─────────────────────────────────────────────────────────────────────
 *
 * Date: Nov 01, 2026
 * @package tpleditor
 * @version 1.0.0
 * @author webitproff
 * @copyright Copyright (c) 2026 webitproff
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

ini_set('display_errors', '0');
error_reporting(0);
ob_start();

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    require_once cot_incfile('tpleditor', 'plug');
    require_once cot_langfile('tpleditor', 'plug');

    if (!cot_auth('admin', 'a', 'A') && !cot_auth('plug', 'tpleditor', 'A')) {
        ob_end_clean();
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    /* FIX START: action и file читаются и из GET — download приходит GET-ссылкой, не POST. */
    $action     = isset($_POST['a'])    ? (string) $_POST['a']
                : (isset($_GET['a'])    ? (string) $_GET['a']    : '');
    $file       = isset($_POST['file']) ? trim((string) $_POST['file'])
                : (isset($_GET['file']) ? trim((string) $_GET['file']) : '');
    $hasContent = array_key_exists('content', $_POST);
    $content    = $hasContent ? (string) $_POST['content'] : '';
    /* FIX END */

    /* FIX START: для download (GET) проверяем cot_check_xg(), для save/read (POST) — cot_check_xp().
       Раньше cot_check_xp() вызывался всегда — GET-запрос на download падал с «Invalid CSRF token». */
    if ($action === 'download') {
        if (!cot_check_xg(false)) {
            ob_end_clean();
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }
    } else {
        if (!cot_check_xp()) {
            ob_end_clean();
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }
    }
    /* FIX END */

    /* FIX START: download — GET без content, не требуем $hasContent. */
    // if ($file === '' || ($action !== 'download' && !$hasContent)) {
    if ($file === '' || ($action === 'save' && !$hasContent)) {
    /* FIX END */
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid file']);
        exit;
    }

    $abs = tpleditor_validate_path($file);
    if ($abs === null) {
        ob_end_clean();
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid path']);
        exit;
    }

    if ($action === 'download') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!is_readable($abs)) {
            http_response_code(404);
            exit;
        }
        /* FIX START: nosniff — браузер не пытается отрисовать ответ как JSON/HTML в текущей вкладке. */
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($abs) . '"');
        header('Content-Length: ' . filesize($abs));
        header('X-Content-Type-Options: nosniff');
        readfile($abs);
        exit;
        /* FIX END */

    } elseif ($action === 'read') {
        if (!is_readable($abs)) {
            throw new RuntimeException('File not readable');
        }
        $response = ['success' => true, 'content' => file_get_contents($abs)];

    } elseif ($action === 'save') {
        if (!is_writable($abs)) {
            throw new RuntimeException('File not writable');
        }

        $original = file_get_contents($abs);
        if (
            $original !== false
            && preg_match('/<!--\s*BEGIN\s*:/i', $original)
            && !preg_match('/<!--\s*BEGIN\s*:/i', $content)
        ) {
            throw new RuntimeException('Content rejected: template markers missing');
        }

        $bytes = file_put_contents($abs, $content);
        if ($bytes === false) {
            throw new RuntimeException('Failed to write file');
        }

        if (!empty(Cot::$cache)) {
            try { Cot::$cache->clear(); } catch (Throwable $e) { /* ignore */ }
        }

        $response = [
            'success' => true,
            'message' => Cot::$L['tpleditor_saved'] ?? 'Saved',
            'bytes'   => $bytes,
        ];

    } else {
        throw new RuntimeException('Unknown action');
    }

/* } catch (Throwable $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
} */
} catch (Throwable $e) {
    http_response_code(500);
    $response = ['success' => false, 'message' => $e->getMessage()];
}
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;