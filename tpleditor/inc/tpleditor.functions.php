<?php
/**
 * Template Editor API
 *
 * Файл: plugins/tpleditor/inc/tpleditor.functions.php
 *
 * ═════════════════════════════════════════════════════════════════════
 *   НАЗНАЧЕНИЕ ФАЙЛА
 * ═════════════════════════════════════════════════════════════════════
 *
 * Публичная библиотека функций плагина Template Editor. Содержит
 * четыре функции — по одной на каждую задачу:
 *
 *   tpleditor_flag()          — чтение булевой настройки плагина;
 *   tpleditor_roots()         — построение списка разрешённых корней;
 *   tpleditor_validate_path() — валидация относительного пути к .tpl;
 *   tpleditor_list_files()    — плоский рекурсивный список .tpl.
 *
 * Файл не выполняет никаких побочных действий: не меняет файлы, не
 * пишет в БД, не логирует, не выводит HTML. Все деструктивные
 * операции (запись content в файл, очистка кеша) выполняются в
 * вызывающем коде (tpleditor.ajax.php). Здесь — только чтение
 * конфигурации и файловой системы.
 *
 * Файл подключается через cot_incfile('tpleditor', 'plug') из двух
 * точек:
 *   - plugins/tpleditor/tpleditor.tools.php — админ-страница;
 *   - plugins/tpleditor/tpleditor.ajax.php  — AJAX-обработчик.
 *
 * cot_incfile() (system/functions.php) для типа 'plug' и $part по
 * умолчанию 'functions' возвращает plugins/<code>/inc/<code>.<part>.php
 * → plugins/tpleditor/inc/tpleditor.functions.php.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЗАВИСИМОСТИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Cot::$cfg['plugin']['tpleditor'][...]
 *     Конфигурация плагина, читается из $cfg['plugin'][config_cat]
 *     [config_name] (заполняется в system/common.php из
 *     $db_config.config_value для config_owner='plug').
 *     Ключи заданы в tpleditor.setup.php:
 *       edit_themes, edit_admin, edit_plugins, edit_modules.
 *
 *   Cot::$sys['baseDir']
 *     Абсолютный путь к корню установки (без завершающего слэша).
 *     Устанавливается в system/common.php:
 *       $sys['baseDir'] = realpath(dirname(__DIR__));
 *
 *   Cot::$usr['theme']
 *     Активная пользовательская тема (код каталога в themes/).
 *     Для гостя — $cfg['defaulttheme']; страницы плагина доступны
 *     только после авторизации, поэтому значение всегда непустое.
 *
 *   Cot::$cfg['themes_dir'], $cfg['plugins_dir'],
 *   $cfg['modules_dir'], $cfg['system_dir'], $cfg['admintheme']
 *     Стандартные ключи конфигурации Cotonti. По умолчанию:
 *       themes_dir = 'themes',
 *       plugins_dir = 'plugins',
 *       modules_dir = 'modules',
 *       system_dir = 'system'.
 *     Все три директории хранятся как ОТНОСИТЕЛЬНЫЕ пути от корня
 *     установки (без ведущего слэша). Это предположение важно для
 *     согласованности префиксов в tpleditor_validate_path().
 *
 *   cot_langfile('tpleditor', 'plug')
 *     Функции этого файла не используют Cot::$L напрямую. Lang-файл
 *     подключается в tpleditor.tools.php и tpleditor.ajax.php
 *     самостоятельно.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   МОДЕЛЬ ПУТЕЙ — ВНУТРЕННИЙ КОНТРАКТ
 * ═════════════════════════════════════════════════════════════════════
 *
 * Плагин работает с двумя формами путей:
 *
 *   1. ОТНОСИТЕЛЬНЫЙ путь (rel path).
 *      Строка без ведущего слэша, начинающаяся с одного из
 *      $root['path'] (см. tpleditor_roots()). Примеры:
 *        themes/mytheme/header.tpl
 *        plugins/tpleditor/tpl/tpleditor.editor.tpl
 *        system/admin/tpl/admin.tpl
 *      Эта форма используется во всех публичных интерфейсах:
 *        - GET/POST параметр file (tpleditor.tools.php, ajax.php);
 *        - тег FILE_REL в шаблонах;
 *        - поле 'rel' в результате tpleditor_list_files();
 *        - значения $root['path'].
 *
 *   2. АБСОЛЮТНЫЙ путь (abs path).
 *      Возвращает только tpleditor_validate_path() (через
 *      realpath()) — для безопасного чтения/записи. Может
 *      отличаться от $base . '/' . $relPath, если файл или
 *      его каталог — симлинк: realpath() разрешает симлинки.
 *
 * Ключевое свойство контракта: tpleditor_list_files() возвращает
 * 'rel' в той же форме, в какой tpleditor_validate_path() ожидает
 * параметр $relPath. Поэтому последовательность
 * «list_files → показ FILE_REL → клик → validate_path(FILE_REL)»
 * работает без дополнительной нормализации.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПРАВА ДОСТУПА
 * ═════════════════════════════════════════════════════════════════════
 *
 * Функции этого файла сами права не проверяют. Проверка выполняется
 * в точках входа:
 *   - tpleditor.tools.php: cot_block(cot_auth('admin','a','A')
 *                                     || cot_auth('plug','tpleditor','A'));
 *   - tpleditor.ajax.php:  if (!cot_auth('admin','a','A')
 *                             && !cot_auth('plug','tpleditor','A')) → 403;
 *   - system/admin/admin.other.php (до include tools-файла):
 *       list(..., $isadmin) = cot_auth('plug', 'tpleditor');
 *       cot_block($isadmin);
 *
 * То есть все четыре функции вызываются только после успешной
 * авторизации. Ответственность за это лежит на вызывающем коде.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ОБРАБОТКА ОШИБОК
 * ═════════════════════════════════════════════════════════════════════
 *
 * Функции придерживаются двух разных стратегий:
 *   - tpleditor_flag(), tpleditor_roots():
 *       Никогда не бросают исключений при корректной конфигурации.
 *       Ошибки чтения конфигурации отсутствуют — используется
 *       только ?? (null coalescing). Если каталог plugins/modules
 *       недоступен для scandir, соответствующий блок в roots просто
 *       пропускается (is_dir() возвращает false или foreach по
 *       false в PHP 8 выдаёт warning и не итерируется; warning
 *       подавляется display_errors=0 в ajax-обработчике).
 *
 *   - tpleditor_validate_path(), tpleditor_list_files():
 *       Возвращают безопасное пустое значение (null / []) при
 *       любой ошибке: несуществующий файл, невалидный путь,
 *       симлинк-побег, нечитаемый каталог. Исключения не
 *       пробрасываются.
 *
 * Ни один вызов из этого файла не выполняет SQL, не пишет в файлы
 * и не вызывает cot_redirect / cot_die_message. Это гарантирует,
 * что вызывающий код может управлять ответом (JSON, 400, редирект)
 * самостоятельно.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СВЯЗАННЫЕ ССЫЛКИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Cotonti:          https://github.com/Cotonti/Cotonti
 *
 * ─────────────────────────────────────────────────────────────────────
 *
 * Date: Nov 01, 2026
 * @package   tpleditor
 * @version   1.0.0
 * @author    webitproff
 * @copyright Copyright (c) 2026 webitproff
 * @license   BSD
 */
 
 
defined('COT_CODE') or die('Wrong URL.');

require_once cot_langfile('tpleditor', 'plug');

/**
 * Проверяет, включена ли конкретная настройка плагина.
 *
 * Читает значение из конфигурации плагина по ключу $name и сравнивает
 * его со строкой '1' строгим оператором ===. Все значения конфигурации
 * хранятся в $db_config.config_value как строки (VARCHAR); Cotonti
 * заполняет Cot::$cfg['plugin'][<cat>][<name>] этими строками без
 * приведения типов (см. system/common.php, блок загрузки конфигурации).
 *
 * Используется только в tpleditor_roots() для проверки четырёх
 * ключей из tpleditor.setup.php:
 *   edit_themes  — включить корень текущей темы;
 *   edit_admin   — включить корень админ-темы;
 *   edit_plugins — включить корни плагинов;
 *   edit_modules — включить корни модулей.
 *
 * Семантика:
 *   - Возвращает true ТОЛЬКО при точном совпадении со строкой '1'.
 *   - Строка '0' → false.
 *   - Пустая строка '' → false.
 *   - Любое другое значение ('yes', 'true', '2') → false.
 *   - Целое число 1 или bool true (если попадут в конфиг через
 *     прямое редактирование БД или программно) → false (строгое
 *     сравнение строки со скаляром).
 *
 * Fallback на $default:
 *   Используется оператор ?? (null coalescing). Он срабатывает
 *   только если ключ отсутствует в массиве или его значение === null.
 *   Пустая строка '' — существующее значение, fallback НЕ применяется.
 *   Значение по умолчанию '$default = '1'' означает «включено»:
 *   если ключ потерян в конфигурации (например, при повреждении
 *   $db_config или при вызове до установки плагина), функция
 *   вернёт true — фича работает. Это осознанный выбор: плагин
 *   предпочтёт «показать и разрешить редактирование», чем
 *   «тихо отключиться».
 *
 * @param string $name    Имя ключа в конфигурации плагина
 *                        ('edit_themes', 'edit_admin', 'edit_plugins',
 *                        'edit_modules'). Регистр имеет значение —
 *                        ключи в $cfg['plugin']['tpleditor']
 *                        чувствительны к регистру.
 * @param string $default Значение, возвращаемое если ключ $name
 *                        отсутствует или равен null. По умолчанию
 *                        '1' (фича включена). При $default = '0'
 *                        отсутствующий ключ трактуется как выключенная
 *                        фича.
 *
 * @return bool true — значение точно равно строке '1'; false —
 *              любое другое значение или сработавший $default,
 *              не равный '1'.
 *
 * @uses Cot::$cfg['plugin']['tpleditor'] Массив конфигурации плагина.
 *
 * @see tpleditor_roots() Единственный потребитель функции в этом файле.
 */
function tpleditor_flag(string $name, string $default = '1'): bool
{
    $val = Cot::$cfg['plugin']['tpleditor'][$name] ?? $default;
    return $val === '1';
}


/**
 * Возвращает список разрешённых корневых директорий для чтения и
 * редактирования .tpl-файлов.
 *
 * Каждый корень — это пара «относительный путь от корня установки» +
 * «человекочитаемая метка» + «группа». Список динамический: зависит
 * от конфигурации плагина и активной темы пользователя. Функция
 * вызывается как из tpleditor_validate_path() (для проверки префикса),
 * так и из tpleditor_list_files() (для обхода каталогов).
 *
 * Состав корней и правила включения:
 *
 *   1. Тема пользователя.
 *      Включается при tpleditor_flag('edit_themes') == true.
 *      Путь: <themes_dir>/<Cot::$usr['theme']>
 *      Метка: 'Theme: <theme>'
 *      Группа: 'theme'
 *      Для гостя usr['theme'] = $cfg['defaulttheme']; в админке
 *      значение всегда непустое.
 *
 *   2. Админ-тема.
 *      Включается при tpleditor_flag('edit_admin') == true
 *      И непустом $cfg['admintheme']. Условие через !empty():
 *      пустая строка, null, '0' — выключают корень.
 *      Путь: <themes_dir>/admin/<admintheme>
 *      Метка: 'Admin theme: <admintheme>'
 *      Группа: 'admin'
 *
 *   3. Плагины.
 *      Включается при tpleditor_flag('edit_plugins') == true.
 *      Делается scandir по абсолютному пути
 *      <baseDir>/<plugins_dir>. Для каждого подкаталога <code>
 *      проверяется существование <code>/tpl. Только при наличии
 *      подкаталога tpl/ добавляется корень:
 *        Путь: <plugins_dir>/<code>/tpl
 *        Метка: 'Plugin: <code>'
 *        Группа: 'plugin_<code>'
 *      Плагины без tpl/ (например, использующие только
 *      шаблоны из темы) в список не попадают.
 *      Специальные записи scandir ('.', '..', '') отбрасываются.
 *
 *   4. Модули.
 *      Включается при tpleditor_flag('edit_modules') == true.
 *      Аналогично плагинам:
 *        Путь: <modules_dir>/<code>/tpl
 *        Метка: 'Module: <code>'
 *        Группа: 'module_<code>'
 *
 *   5. Ядровая админка (core admin).
 *      Добавляется ВСЕГДА, без проверки флагов. Это сделано
 *      осознанно: редактирование <system_dir>/admin/tpl/ (общие
 *      админские шаблоны ядра) не относится к темам/плагинам/модулям
 *      и не регулируется флагами edit_*. Корень присутствует в
 *      списке даже если все четыре флага выключены.
 *        Путь: <system_dir>/admin/tpl
 *        Метка: 'Core admin'
 *        Группа: 'core'
 *
 * Ключевые свойства возвращаемого массива:
 *
 *   - Все пути ОТНОСИТЕЛЬНЫЕ (без ведущего слэша). Это критично
 *     для tpleditor_validate_path(): префиксная проверка
 *     strpos($relPath, $prefix) === 0 работает только при
 *     согласованности относительной формы. Если
 *     $cfg['themes_dir'] или $cfg['plugins_dir'] в конфигурации
 *     абсолютные (с ведущим '/'), префиксная проверка даст
 *     false — все пути будут отвергнуты.
 *
 *   - Порядок корней детерминирован: тема → админ-тема → плагины
 *     (в порядке scandir, обычно алфавитный) → модули → core.
 *     При совпадении файла с несколькими префиксами
 *     tpleditor_validate_path берёт первый (это может быть
 *     актуально, если тема пользователя совпадает с админ-темой —
 *     теоретически возможно при неверной конфигурации).
 *
 *   - Дублирования не выполняется. Один и тот же физический путь
 *     может встретиться дважды, если совпадают имена подкаталогов
 *     в plugins_dir и modules_dir (маловероятно) — записи будут
 *     с разными группами ('plugin_x' vs 'module_x') и разными
 *     путями (префикс plugins/ vs modules/).
 *
 *   - Возвращаемый массив никогда не пуст: минимум один элемент
 *     (core admin).
 *
 * Производительность:
 *   Функция НЕ кеширует результат. Каждый вызов выполняет два
 *   is_dir() и два scandir() (для plugins_dir и modules_dir, если
 *   соответствующие флаги включены). При вызове из
 *   tpleditor_validate_path() это означает, что при сохранении
 *   каждого файла каталоги плагинов и модулей сканируются заново.
 *   Для текущего масштаба (один AJAX-запрос на сохранение) это
 *   несущественно; при массовой обработке (импорт сотен файлов)
 *   стоит кешировать результат в статической переменной.
 *
 * @return array<int, array{path: string, label: string, group: string}>
 *     Непустой список корней. Формат каждой записи:
 *       - 'path'  — относительный путь от корня установки, без
 *                   ведущего и завершающего слэшей;
 *       - 'label' — человекочитаемая метка для UI
 *                   ('Theme: ...', 'Plugin: ...', 'Core admin');
 *       - 'group' — код группы: 'theme' | 'admin' | 'plugin_<code>'
 *                   | 'module_<code>' | 'core'. Используется
 *                   клиентским кодом для группировки.
 *
 * @uses Cot::$cfg['plugin']['tpleditor'] — через tpleditor_flag();
 * @uses Cot::$cfg['themes_dir']       — для корней темы и админ-темы;
 * @uses Cot::$cfg['plugins_dir']      — для корней плагинов;
 * @uses Cot::$cfg['modules_dir']      — для корней модулей;
 * @uses Cot::$cfg['system_dir']       — для ядрового корня;
 * @uses Cot::$cfg['admintheme']       — проверка непустоты;
 * @uses Cot::$usr['theme']            — текущая тема пользователя;
 * @uses Cot::$sys['baseDir']          — абсолютный префикс для is_dir/scandir.
 *
 * @see tpleditor_flag()          Чтение флагов edit_*.
 * @see tpleditor_validate_path() Проверка префикса входящего пути.
 * @see tpleditor_list_files()    Обход всех разрешённых директорий.
 */
function tpleditor_roots(): array
{
    $cfg  = Cot::$cfg;
    $base = Cot::$sys['baseDir'];
    $roots = [];

    if (tpleditor_flag('edit_themes')) {
        $dir = rtrim($cfg['themes_dir'], '/') . '/' . Cot::$usr['theme'];
        $roots[] = ['path' => $dir, 'label' => 'Theme: ' . Cot::$usr['theme'], 'group' => 'theme'];
    }
    if (tpleditor_flag('edit_admin') && !empty($cfg['admintheme'])) {
        $dir = rtrim($cfg['themes_dir'], '/') . '/admin/' . $cfg['admintheme'];
        $roots[] = ['path' => $dir, 'label' => 'Admin theme: ' . $cfg['admintheme'], 'group' => 'admin'];
    }
    if (tpleditor_flag('edit_plugins')) {
        $pluginsAbs = $base . '/' . rtrim($cfg['plugins_dir'], '/');
        if (is_dir($pluginsAbs)) {
            foreach (scandir($pluginsAbs) as $code) {
                if ($code === '.' || $code === '..' || $code === '') continue;
                $tplDir = $pluginsAbs . '/' . $code . '/tpl';
                if (is_dir($tplDir)) {
                    $roots[] = [
                        'path'  => rtrim($cfg['plugins_dir'], '/') . '/' . $code . '/tpl',
                        'label' => 'Plugin: ' . $code,
                        'group' => 'plugin_' . $code,
                    ];
                }
            }
        }
    }
    if (tpleditor_flag('edit_modules')) {
        $modulesAbs = $base . '/' . rtrim($cfg['modules_dir'], '/');
        if (is_dir($modulesAbs)) {
            foreach (scandir($modulesAbs) as $code) {
                if ($code === '.' || $code === '..' || $code === '') continue;
                $tplDir = $modulesAbs . '/' . $code . '/tpl';
                if (is_dir($tplDir)) {
                    $roots[] = [
                        'path'  => rtrim($cfg['modules_dir'], '/') . '/' . $code . '/tpl',
                        'label' => 'Module: ' . $code,
                        'group' => 'module_' . $code,
                    ];
                }
            }
        }
    }
    $roots[] = ['path' => rtrim($cfg['system_dir'], '/') . '/admin/tpl', 'label' => 'Core admin', 'group' => 'core'];

    return $roots;
}

/**
 * Валидирует относительный путь к .tpl-файлу и возвращает его
 * реальный абсолютный путь.
 *
 * Единственная точка безопасности, гарантирующая, что запрошенный
 * файл лежит внутри одной из разрешённых директорий (см.
 * tpleditor_roots()) и что через него нельзя выйти за пределы
 * установки Cotonti. Вызывается как при чтении (tools.php, ajax
 * action=read), так и при записи (ajax action=save) и при
 * скачивании (ajax action=download) — до любых файловых операций.
 *
 * Последовательность проверок (каждая при провале возвращает null):
 *
 *   1. Нормализация.
 *      Замена '\\' на '/' (Windows-разделители), trim().
 *      Затем ltrim('/', ...) — отбрасывает ведущие слэши. Это
 *      нужно, чтобы клиент мог присылать путь как с ведущим '/' ,
 *      так и без — форма результата единообразна.
 *
 *   2. Проверка пустоты и расширения.
 *      - Пустая строка → null.
 *      - Расширение должно заканчиваться на '.tpl'
 *        (preg_match '`\.tpl$`i' — регистронезависимо,
 *         '.TPL', '.Tpl' тоже проходят).
 *
 *   3. Null-byte.
 *      strpos($relPath, "\0") !== false → null. Защита от
 *      обхода через усечение строки в C-функциях ФС.
 *
 *   4. Сегменты пути.
 *      Путь разбивается по '/'; каждый сегмент проверяется:
 *      - пустая строка (двойной '//') → null;
 *      - '.'  → null;
 *      - '..' → null.
 *      Защита от path traversal до попадания в realpath().
 *
 *   5. Префиксная проверка корня.
 *      Для каждого $root из tpleditor_roots():
 *        $prefix = rtrim($root['path'], '/') . '/';
 *        if (strpos($relPath, $prefix) === 0) — совпадение.
 *      Проверка через strpos(), а не через начинается-с-подстроки,
 *      исключает ложные срабатывания по хвосту.
 *      Если ни один корень не подошёл → null.
 *      Запоминается $matchRoot['path'] (относительный) — для
 *      проверки №8.
 *
 *   6. Существование и тип файла.
 *      $real = realpath($base . '/' . $relPath);
 *      - realpath вернул false (файла или каталога нет) → null;
 *      - !is_file($real) (это каталог, симлинк на каталог,
 *        битый симлинк) → null.
 *
 *   7. Повторная проверка расширения после realpath.
 *      $real должен заканчиваться на '.tpl'. Избыточно после
 *      шага 2, но защищает от симлинка на файл с другим
 *      расширением (например, 'foo.tpl' → '/etc/passwd').
 *
 *   8. Симлинк-побег за baseDir.
 *      $realBase = realpath($base);
 *      if (strpos($real, $realBase . '/') !== 0) → null.
 *      Гарантирует, что разрешённый (канонический) путь лежит
 *      внутри корня установки.
 *
 *   9. Симлинк-побег за конкретный корень.
 *      $realRoot = realpath($base . '/' . $matchRoot);
 *      if (strpos($real, $realRoot . '/') !== 0) → null.
 *      Дополнительно к шагу 8: даже внутри baseDir файл не
 *      должен выходить из корня, к которому он относился по
 *      относительному пути. Пример атаки, отсекаемой здесь:
 *      'themes/mytheme/header.tpl' — симлинк, реально
 *      указывающий на 'plugins/foo/tpl/header.tpl'. Формально
 *      внутри baseDir, но не внутри разрешённого корня темы.
 *      Проверка через realpath($realRoot) разрешает и симлинки
 *      в самом пути корня (например, если themes/foo/tpl — это
 *      симлинк на другой каталог): корень тоже канонизируется.
 *
 * Возвращаемое значение:
 *   - При успехе — $real: реальный абсолютный путь (результат
 *     realpath(), симлинки разрешены). Это гарантирует, что
 *     последующие file_get_contents() / file_put_contents()
 *     будут работать с фактическим файлом, а не с симлинком.
 *     ВНИМАНИЕ: для симлинков возвращённый путь НЕ равен
 *     $base . '/' . $relPath. Если вызывающему коду нужен
 *     именно исходный путь — он должен сохранить его отдельно
 *     до вызова (это и делает tpleditor.tools.php: 'FILE_REL'
 *     содержит исходный $file, а не realpath).
 *   - При любой ошибке — null.
 *
 * Производительность:
 *   Выполняет три realpath() (файл + base + matchRoot) и один
 *   вызов tpleditor_roots() (а значит — scandir плагинов и
 *   модулей, если включены соответствующие флаги). Для одиночного
 *   AJAX-запроса незаметно; при пакетной проверке сотен файлов
 *   стоит кешировать roots.
 *
 * @param string $relPath Относительный путь к .tpl-файлу, полученный
 *                        из недоверенного источника (GET/POST file).
 *                        Слэш в начале допускается — будет отброшен.
 *                        Обратный слэш тоже допускается — заменяется
 *                        на прямой. Регистр расширения не важен.
 *
 * @return string|null Реальный абсолютный путь (realpath) при
 *                     успешной валидации; null при любой ошибке
 *                     (пустой путь, не .tpl, null-byte, path
 *                     traversal, нет корня-родителя, файл не
 *                     существует, не файл, симлинк-побег).
 *
 * @uses Cot::$sys['baseDir'] Абсолютный корень установки.
 *
 * @see tpleditor_roots()           Список разрешённых префиксов.
 * @see tpleditor_list_files()      Формирует 'rel' в совместимом формате.
 */
function tpleditor_validate_path(string $relPath): ?string
{
    $relPath = str_replace('\\', '/', trim($relPath));
    $relPath = ltrim($relPath, '/');

    if ($relPath === '' || !preg_match('`\.tpl$`i', $relPath)) {
        return null;
    }
    if (strpos($relPath, "\0") !== false) {
        return null;
    }
    foreach (explode('/', $relPath) as $seg) {
        if ($seg === '' || $seg === '.' || $seg === '..') {
            return null;
        }
    }

    // Должен попадать в один из разрешённых корней.
    $matchRoot = null;
    foreach (tpleditor_roots() as $root) {
        $prefix = rtrim($root['path'], '/') . '/';
        if (strpos($relPath, $prefix) === 0) {
            $matchRoot = $root['path'];
            break;
        }
    }
    if ($matchRoot === null) {
        return null;
    }

    $base = Cot::$sys['baseDir'];
    $real = realpath($base . '/' . $relPath);
    if ($real === false || !is_file($real)) {
        return null;
    }
    if (!preg_match('`\.tpl$`i', $real)) {
        return null;
    }
    // Защита от симлинк-побега
    $realBase = realpath($base);
    if ($realBase === false || strpos($real, $realBase . '/') !== 0) {
        return null;
    }
    $realRoot = realpath($base . '/' . $matchRoot);
    if ($realRoot === false || strpos($real, $realRoot . '/') !== 0) {
        return null;
    }

    return $real;
}

/**
 * Рекурсивно собирает плоский список всех .tpl-файлов в разрешённых
 * директориях.
 *
 * Обходит каждый корень из tpleditor_roots() через
 * RecursiveIteratorIterator (LEAVES_ONLY, SKIP_DOTS), собирает
 * метаданные и возвращает единый массив, отсортированный по
 * относительному пути (регистронезависимо).
 *
 * Алгоритм:
 *
 *   Для каждого $root из tpleditor_roots():
 *     1. Вычислить абсолютный путь корня:
 *        $abs = $base . '/' . ltrim($root['path'], '/')
 *     2. Если $abs не каталог — пропустить корень (is_dir false;
 *        например, тема пользователя не установлена, но
 *        edit_themes=true).
 *     3. Создать итератор:
 *        new RecursiveIteratorIterator(
 *            new RecursiveDirectoryIterator(
 *                $abs, FilesystemIterator::SKIP_DOTS),
 *            RecursiveIteratorIterator::LEAVES_ONLY)
 *        Обёрнуто в try/catch Throwable: если каталог
 *        нечитаем (permissions), итерация по корню
 *        пропускается — без исключения наружу.
 *     4. Для каждого элемента итератора $f (SplFileInfo):
 *        - !$f->isFile() → continue (каталоги, симлинки на
 *          каталоги, битые ссылки);
 *        - имя файла не оканчивается на '.tpl' (регистро-
 *          независимо) → continue;
 *        - $real = $f->getRealPath(); если false (объект
 *          создан, но файл исчез между stat и getRealPath) →
 *          continue;
 *        - сформировать запись:
 *            'rel'   = realPath с отрезанным префиксом $base,
 *                      '\\' заменены на '/', ведущий '/' снят
 *                      через ltrim;
 *            'size'  = (int) $f->getSize();
 *            'mtime' = (int) $f->getMTime();
 *            'label' = $root['label'] (метка корня-родителя).
 *
 *   После обхода всех корней — сортировка:
 *     usort($out, fn($a, $b) => strcasecmp($a['rel'], $b['rel']));
 *   Сортировка регистронезависимая, алфавитная, по относительному
 *   пути. Это удобно для UI: темы идут отдельным блоком
 *   ('themes/...'), плагины — 'plugins/...', ядро — 'system/...'.
 *
 * Свойства результата:
 *
 *   - 'rel' совместим по форме с параметром tpleditor_validate_path():
 *     'themes/mytheme/header.tpl', 'plugins/tpleditor/tpl/...',
 *     'system/admin/tpl/...'. То есть list_files → validate_path
 *     работает без дополнительной нормализации.
 *
 *   - 'rel' вычисляется из getRealPath(), а НЕ из $f->getPathname().
 *     Для симлинков внутри tpl/ это означает:
 *       а) если симлинк указывает на файл внутри того же корня —
 *          'rel' будет путём к цели (не к симлинку), но
 *          tpleditor_validate_path при последующем клике
 *          разрешит его (realpath совпадёт с целью);
 *       б) если симлинк указывает ЗА пределы $base или в чужой
 *          корень — 'rel' вычисляется через substr($real, strlen($base))
 *          и может дать неверную строку или пустоту.
 *          Такая запись отобразится в списке, но при попытке
 *          открыть её tpleditor_validate_path вернёт null →
 *          клиент получит 400 Invalid path. То есть список
 *          может содержать «мёртвые» ссылки для симлинков,
 *          указывающих вне разрешённой зоны. Это косметическая
 *          проблема, а не дыра в безопасности.
 *
 *   - Файлы с одинаковым 'rel' теоретически возможны при
 *     совпадении корней (крайне маловероятно); usort их
 *     не дедуплицирует — попадут оба.
 *
 * Производительность:
 *   Читает всю файловую систему разрешённых корней. При сотнях
 *   .tpl-файлов (типовая установка Cotonti: ~100-300 файлов
 *   в themes+plugins+modules) обход и сортировка занимают
 *   несколько миллисекунд. Кеширование не реализовано; каждый
 *   показ списка (tpleditor.tools.php без параметра file)
 *   сканирует ФС заново.
 *
 * @return array<int, array{
 *     rel: string,
 *     size: int,
 *     mtime: int,
 *     label: string
 * }> Плоский отсортированный список. Каждая запись:
 *     - 'rel'   — относительный путь от корня установки без
 *                 ведущего слэша, '/' как разделитель;
 *     - 'size'  — размер файла в байтах (int);
 *     - 'mtime' — время последнего изменения (unix timestamp);
 *     - 'label' — метка корня, к которому относится файл
 *                 ('Theme: ...', 'Plugin: ...', 'Core admin').
 *     Пустой массив, если ни один корень не доступен или
 *     в разрешённых каталогах нет .tpl-файлов.
 *
 * @uses tpleditor_roots()       Список корней для обхода.
 * @uses Cot::$sys['baseDir']    Абсолютный префикс.
 *
 * @see tpleditor_validate_path() Принимает 'rel' на вход при
 *                                последующих операциях.
 */
function tpleditor_list_files(): array
{
    $base = Cot::$sys['baseDir'];
    $out  = [];

    foreach (tpleditor_roots() as $root) {
        $abs = $base . '/' . ltrim($root['path'], '/');
        if (!is_dir($abs)) {
            continue;
        }
        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
        } catch (Throwable $e) {
            continue;
        }
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            if (!preg_match('`\.tpl$`i', $f->getFilename())) continue;
            $real = $f->getRealPath();
            if ($real === false) continue;
            if (strpos($real, $base . '/') !== 0) continue;
            $out[] = [
                'rel'   => ltrim(str_replace('\\', '/', substr($real, strlen($base))), '/'),
                'size'  => (int) $f->getSize(),
                'mtime' => (int) $f->getMTime(),
                'label' => $root['label'],
            ];
        }
    }

    usort($out, static fn($a, $b) => strcasecmp($a['rel'], $b['rel']));

    return $out;
}