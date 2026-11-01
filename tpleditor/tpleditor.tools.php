<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */

/**
 * Template Editor — страница инструмента «Редактор шаблонов» в админке.
 *
 * Файл: plugins/tpleditor/tpleditor.tools.php
 *
 * Назначение
 * ----------
 * Единственная точка входа административного интерфейса плагина. Cotonti
 * находит этот файл через хук tools: страница доступна по адресу
 * admin.php?m=other&p=tpleditor и открывается из раздела «Инструменты».
 * Других хуков в плагине нет — AJAX-обработчик (tpleditor.ajax.php)
 * вызывается через plug.php?r=tpleditor.
 *
 * Файл обслуживает два режима в зависимости от GET-параметра file:
 *   - file задан и валиден  → редактор CodeMirror (tpleditor.editor.tpl);
 *   - file пуст/не задан    → список доступных .tpl (tpleditor.tools.tpl).
 *
 * Что делает файл
 * ---------------
 *   1. Загружает inc/tpleditor.functions.php
 *      (tpleditor_flag, tpleditor_roots, tpleditor_validate_path,
 *       tpleditor_list_files).
 *   2. Проверяет права (cot_block) и при отказе уводит на 403.
 *   3. Устанавливает $adminTitle и $adminPath (хлебные крошки).
 *   4. Читает GET file через cot_import('file', 'G', 'TXT') — trim;
 *      сам фильтр TXT не режет спецсимволы, реальная защита —
 *      в tpleditor_validate_path().
 *   5. Ветка «редактор»:
 *        $abs = tpleditor_validate_path($file);
 *        если null или файл нечитаем → cot_error + cot_redirect;
 *        иначе читает содержимое и заполняет tpleditor.editor.tpl.
 *   6. Ветка «список»:
 *        $files = tpleditor_list_files();
 *        заполняет tpleditor.tools.tpl повторяющийся блок MAIN.FILE_ROW;
 *        при пустом массиве — парсит MAIN.NO_FILES.
 *   7. Обе ветки: cot_display_messages($t) → $t->parse('MAIN') →
 *      $pluginBody = $t->text('MAIN').
 *
 * Права доступа
 * -------------
 * cot_block(cot_auth('admin', 'a', 'A') || cot_auth('plug', 'tpleditor', 'A'))
 *
 * Третий аргумент 'A' в cot_auth('admin', 'a', 'A') обязателен.
 * cot_auth($area, $option, $mask): при длине $mask > 1 символа
 * возвращается массив [R,W,A] (system/functions.php), а массив всегда
 * truthy. Без явной маски 'A' первый член дизъюнкции всегда был бы
 * truthy и доступ получал бы любой залогиненный пользователь.
 * С 'A' возвращается скаляр bool — корректная семантика.
 *
 * Второй барьер (ядровой): system/admin/admin.other.php до include
 * этого файла выполняет
 *   list($auth_read, $auth_write, $isadmin) = cot_auth('plug', $p);
 *   cot_block($isadmin);
 * то есть требует бит A на самом плагине. Внутриплагинная проверка
 * добавляет альтернативный путь через cot_auth('admin', 'a', 'A').
 *
 * Выбор файла (GET file)
 * ----------------------
 * cot_import('file', 'G', 'TXT') — trim, спецсимволы сохраняются.
 * Валидация tpleditor_validate_path():
 *   - расширение обязано быть .tpl (регистронезависимо);
 *   - нет \0, пустых сегментов, '.' и '..';
 *   - путь начинается с одного из разрешённых корней
 *     (theme, admin theme, plugins/<code>/tpl, modules/<code>/tpl,
 *      system/admin/tpl);
 *   - realpath(файла) и realpath(корня) лежат внутри baseDir
 *     (защита от симлинк-побега);
 *   - файл существует и читаем.
 * При любой ошибке — cot_error() и cot_redirect() на URL без file.
 *
 * Ветка «редактор» (tpleditor.editor.tpl)
 * ---------------------------------------
 * Назначаемые теги:
 *   FILE_REL      — относительный путь (htmlspecialchars — обязательно,
 *                   иначе XSS через имя файла);
 *   FILE_CONTENT  — содержимое файла (htmlspecialchars — иначе в
 *                   <textarea> попадёт сырой HTML);
 *   BACK_URL      — ссылка «К списку», cot_url('admin', [...p=tpleditor]);
 *   BACKUP_URL    — ссылка на ajax-download: a=download + GET-токен
 *                   x=Cot::$sys['xk']. Строится как
 *                     cot_absoluteUrl('plug',
 *                         ['r' => 'tpleditor', 'a' => 'download'], '', true)
 *                     . '&file=' . $file . '&x=' . Cot::$sys['xk']
 *                   Четвёртый аргумент cot_absoluteUrl — это
 *                   $htmlspecialcharsBypass; true даёт разделитель '&',
 *                   а не '&amp;', иначе браузер ушлёт в GET литерал
 *                   '&amp;file' вместо параметра file. По этой же
 *                   причине FILE_URL в ветке «список» и BACKUP_URL
 *                   здесь строятся конкатенацией вручную, а не
 *                   передачей 'file' в $params. Значение $file при
 *                   этом не urlencode — для типичных .tpl-путей без
 *                   & # ? = пробелов работает; путь с такими символами
 *                   сломает URL;
 *   X_TOKEN       — Cot::$sys['xk'], уходит в data-x и проверяется
 *                   в ajax.php через cot_check_xp() (POST save/read)
 *                   и cot_check_xg(false) (GET download);
 *   AJAX_URL      — cot_url('plug', ['r' => 'tpleditor']).
 *                   В коде строка присвоена дважды одинаковым значением
 *                   ('AJAX_URL' => …; 'AJAX_URL' => …); второе
 *                   перезаписывает первое тем же значением — избыточно,
 *                   вреда нет;
 *   L_BACKUP, L_SAVE, L_BACK, L_EDITING, L_SAVED, L_ERROR
 *                 — языковые строки из lang/tpleditor.ru.lang.php
 *                   (lang-файл уже подключён admin.other.php через
 *                    require_once cot_langfile($p, TYPE_PLUGIN)).
 *
 * Ветка «список» (tpleditor.tools.tpl)
 * ------------------------------------
 * Теги MAIN: X_TOKEN, L_TITLE, L_NO_FILES, L_SIZE, L_MTIME.
 * Блок MAIN.FILE_ROW (одна строка на файл): FILE_REL, FILE_SIZE,
 * FILE_MTIME (cot_date('datetime_medium')), FILE_LABEL, FILE_URL.
 * FILE_URL строится так же, как BACKUP_URL — cot_absoluteUrl(..., true)
 * + конкатенация '&file=' . $f['rel'] без urlencode.
 * Если tpleditor_list_files() вернул [] — парсится MAIN.NO_FILES.
 *
 * Шаблоны
 * -------
 *   cot_tplfile('tpleditor.editor', 'plug', true)
 *   cot_tplfile('tpleditor.tools',  'plug', true)
 * Третий аргумент true — поиск с admin-флагом. Для плагина порядок
 * (system/functions.php::cot_tplfile, ветка 'plug' && $admin):
 *   themes/<admintheme_dir>/plugins/tpleditor/<name>.tpl  (см. ниже)
 * Фактический список каталогов:
 *   1. themes/admin/<admintheme>/plugins/tpleditor/
 *   2. themes/admin/<admintheme>/plugins/
 *   3. themes/<theme>/admin/plugins/tpleditor/
 *   4. themes/<theme>/admin/plugins/
 *   5. themes/<theme>/plugins/tpleditor/
 *   6. themes/<theme>/plugins/
 *   7. plugins/tpleditor/tpl/
 * Если файл не найден — XTemplate бросит исключение, поэтому tpl/
 * с двумя .tpl обязан присутствовать в поставке плагина.
 *
 * Сообщения
 * ---------
 * В обоих шаблонах внутри <!-- BEGIN: MAIN --> подключён
 * {FILE "{PHP.cfg.themes_dir}/admin/{PHP.cfg.admintheme}/warnings.tpl"}.
 * XTemplate::prepare() → restartIncludeFiles() разворачивает {FILE ...}
 * на этапе компиляции, поэтому блоки ERROR / ERROR.ERROR_ROW, WARNING,
 * DONE становятся вложенными блоками MAIN и присутствуют в $this->index
 * как 'MAIN.ERROR.ERROR_ROW' и т.д.
 * cot_display_messages($t) с аргументом по умолчанию 'MAIN' вызывает
 *   $tpl->parse('MAIN.ERROR.ERROR_ROW'); … $tpl->parse('MAIN.ERROR');
 * и аналогично для WARNING/DONE — эти пути в индексе есть, поэтому
 * сообщения корректно распарсятся, а в конце функция вызывает
 * cot_clear_messages().
 * Порядок «display_messages → parse('MAIN')» совпадает с banlist.admin.php,
 * urleditor.admin.php, page.admin.php — это штатный шаблон.
 *
 * Контракт с ядром (admin.other.php)
 * ----------------------------------
 * Для Hooks=tools system/admin/admin.other.php после include_once
 * читает ровно две переменные области видимости include:
 *   $pluginBody  — в обычном режиме;
 *   $plugin_body — только при Cot::$cfg['legacyMode'] == true.
 * Последовательность в ядре:
 *   if ($legacyMode) { $plugin_body = ''; }
 *   $pluginBody = '';
 *   include_once $includeFile;
 *   $adminMain .= $pluginBody;
 *   if ($legacyMode) { $adminMain .= $plugin_body; }
 * К $t после include_once ядро не обращается и $t->out() не вызывает.
 * Поэтому обе финальные строки обязательны:
 *   $t->parse('MAIN');
 *   $pluginBody = $t->text('MAIN');
 * Без них раздел админки откроется пустым.
 *
 * Чего НЕ делать в этом файле
 * ---------------------------
 *   - не вызывать $t->out('MAIN') — ядро выводит $adminMain само,
 *     получится двойной вывод;
 *   - не возвращать JSON — этот файл обслуживает HTML-страницу, JSON
 *     отдаёт tpleditor.ajax.php через plug.php?r=tpleditor;
 *   - не рассчитывать на автоматический вывод сообщений без
 *     {FILE ...warnings.tpl} в шаблоне — cot_display_messages()
 *     в конце всегда вызывает cot_clear_messages(), и без
 *     соответствующих блоков в индексе шаблона сообщения будут
 *     стёрты без вывода.
 *
 * Зависимости
 * -----------
 *   cot_incfile('tpleditor', 'plug')
 *      → plugins/tpleditor/inc/tpleditor.functions.php
 *   cot_tplfile(..., 'plug', true)     — поиск шаблона с admin-флагом
 *   cot_absoluteUrl / cot_url          — построение ссылок
 *   cot_auth / cot_block               — права
 *   cot_import('file', 'G', 'TXT')     — ввод
 *   cot_display_messages($t)           — вывод warnings.tpl
 *   Cot::$sys['xk']                    — токен CSRF
 *   Cot::$L['tpleditor_*']             — языковые строки (грузятся
 *                                         в admin.other.php)
 *
 * Замеченные особенности (не являются блокерами)
 * ---------------------------------------------
 *   - file в составе BACKUP_URL и FILE_URL не urlencode; для .tpl-путей
 *     без & # ? = пробелов работает корректно;
 *   - при отсутствии в шаблоне {FILE .../warnings.tpl} сообщения
 *     cot_error()/cot_message() не отобразятся (cot_clear_messages()
 *     вызовется безусловно в конце cot_display_messages()).
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СВЯЗАННЫЕ ССЫЛКИ
 * ═════════════════════════════════════════════════════════════════════
 *   Router:          system/router/Router.php::routeAdminOther
 *   Wrapper:         system/admin/admin.other.php
 *   Auth:            system/functions.php::cot_auth / cot_block
 *   Import:          system/functions.php::cot_import
 *   URL:             system/functions.php::cot_url / cot_absoluteUrl
 *   Template:        system/cotemplate.php
 *   tplfile:         system/functions.php::cot_tplfile
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

require_once cot_incfile('tpleditor', 'plug');

cot_block(cot_auth('admin', 'a', 'A') || cot_auth('plug', 'tpleditor', 'A'));

$adminTitle  = Cot::$L['tpleditor_title'];
$adminPath[] = [cot_url('admin', ['m' => 'other', 'p' => 'tpleditor']), Cot::$L['tpleditor_title']];

$file = cot_import('file', 'G', 'TXT');

if ($file !== null && $file !== '') {
    $abs = tpleditor_validate_path($file);
    if ($abs === null || !is_readable($abs)) {
        cot_error(Cot::$L['tpleditor_err_path']);
        cot_redirect(cot_url('admin', ['m' => 'other', 'p' => 'tpleditor'], '', true));
    }

    $adminPath[] = [cot_url('admin', ['m' => 'other', 'p' => 'tpleditor', 'file' => $file]), htmlspecialchars(basename($file))];

    $content = file_get_contents($abs);
    if ($content === false) {
        cot_error(Cot::$L['tpleditor_err_read']);
        cot_redirect(cot_url('admin', ['m' => 'other', 'p' => 'tpleditor'], '', true));
    }

    $t = new XTemplate(cot_tplfile('tpleditor.editor', 'plug', true));
    $t->assign([
        'FILE_REL'      => htmlspecialchars($file),
        'FILE_CONTENT'  => htmlspecialchars($content),
        'BACK_URL'      => cot_url('admin', ['m' => 'other', 'p' => 'tpleditor']),
        'X_TOKEN'       => Cot::$sys['xk'],
		
        'AJAX_URL'      => cot_url('plug', ['r' => 'tpleditor']),

        'BACKUP_URL'    => cot_absoluteUrl('plug', ['r' => 'tpleditor', 'a' => 'download'], '', true)
            . '&file=' . $file . '&x=' . Cot::$sys['xk'],
			
        'L_BACKUP'      => Cot::$L['tpleditor_backup'],
        'L_SAVE'        => Cot::$L['tpleditor_save'],
        'L_BACK'        => Cot::$L['tpleditor_back'],
        'L_EDITING'     => Cot::$L['tpleditor_editing'],
        'L_SAVED'       => Cot::$L['tpleditor_saved'],
        'L_ERROR'       => Cot::$L['tpleditor_error'],
    ]);
    cot_display_messages($t);
    $t->parse('MAIN');
    $pluginBody = $t->text('MAIN');

} else {
    $files = tpleditor_list_files();

    $t = new XTemplate(cot_tplfile('tpleditor.tools', 'plug', true));
    $t->assign([
        'X_TOKEN' => Cot::$sys['xk'],
        'L_TITLE' => Cot::$L['tpleditor_title'],
        'L_NO_FILES' => Cot::$L['tpleditor_no_files'],
        'L_SIZE'  => Cot::$L['tpleditor_size'],
        'L_MTIME' => Cot::$L['tpleditor_mtime'],
    ]);

    if (empty($files)) {
        $t->parse('MAIN.NO_FILES');
    } else {
        foreach ($files as $f) {
            $t->assign([
                'FILE_REL'   => htmlspecialchars($f['rel']),
                'FILE_SIZE'  => $f['size'],
                'FILE_MTIME' => cot_date('datetime_medium', $f['mtime']),
                //'FILE_URL'   => cot_url('admin', ['m' => 'other', 'p' => 'tpleditor', 'file' => $f['rel']]),
                'FILE_URL'   => cot_absoluteUrl('admin', ['m' => 'other', 'p' => 'tpleditor'], '', true)
                    . '&file=' . $f['rel'],
                'FILE_LABEL' => htmlspecialchars($f['label']),
            ]);
            $t->parse('MAIN.FILE_ROW');
        }
    }

    cot_display_messages($t);
    $t->parse('MAIN');
    $pluginBody = $t->text('MAIN');
}
