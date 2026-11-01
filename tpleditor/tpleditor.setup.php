<?php
/* ====================
[BEGIN_COT_EXT]
Code=tpleditor
Name=Template Editor
Category=administration
Description=Edit .tpl files (theme, admin theme, plugins, modules) directly in admin panel with CodeMirror
Version=1.0.0
Date=2026-Nov-01
Author=webitproff
Copyright=(c) 2026 webitproff
Notes=
Auth_guests=
Lock_guests=RW12345A
Auth_members=
Lock_members=RW12345A
Requires_modules=
Requires_plugins=
Recommends_modules=
Recommends_plugins=
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
edit_themes=01:radio:0,1:1:Allow editing theme templates
edit_admin=02:radio:0,1:1:Allow editing admin theme templates
edit_plugins=03:radio:0,1:1:Allow editing plugin templates
edit_modules=04:radio:0,1:1:Allow editing module templates
[END_COT_EXT_CONFIG]
==================== */

/**
 * Setup & Registration File for the Plugin "Template Editor"
 * for Cotonti CMF v1.0.0, PHP 8.5, MySQL 8.4
 *
 * ═════════════════════════════════════════════════════════════════════
 *   НАЗНАЧЕНИЕ ФАЙЛА — ПАСПОРТ ПЛАГИНА
 * ═════════════════════════════════════════════════════════════════════
 *
 * Файл `tpleditor.setup.php` — единственная точка, через которую
 * плагин становится известен ядру Cotonti. Без него плагин для системы
 * не существует: его нет в списке расширений, его невозможно
 * установить, а все вызовы ядра, запрашивающие метаданные плагина,
 * завершатся неудачей.
 *
 * По роли это «паспорт» расширения. Всё, что система знает о плагине
 * до установки, ядро берёт из блока
 * `BEGIN_COT_EXT ... END_COT_EXT` в начале файла. Всё, что система
 * знает после установки, записано в таблицах БД и было взято оттуда
 * же при установке.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   МЕСТО В СИСТЕМЕ — ГДЕ И КЕМ ЧИТАЕТСЯ ФАЙЛ
 * ═════════════════════════════════════════════════════════════════════
 *
 * Файл читается ядром через `cot_infoget()` (system/extensions.php).
 * Она ищет маркеры `BEGIN_COT_EXT` / `END_COT_EXT` в первых 32 КБ
 * файла, разбирает содержимое как `Key = Value` и возвращает
 * ассоциативный массив.
 *
 * Точки чтения файла в ядре:
 *
 *   1. admin.php?m=extensions
 *      Список расширений и страница «details». Через
 *      `cot_extension_list_info()` ядро сканирует каталоги
 *      `plugins/` и `modules/`, ищет `<code>/<code>.setup.php`
 *      и вызывает `cot_infoget()`. Из полученных данных строятся
 *      карточки расширений в админке.
 *
 *   2. cot_extension_install() — установка / обновление
 *      Проверка наличия setup-файла и чтение метаданных:
 *      версия, имя, права, зависимости. Без файла установка
 *      завершится ошибкой `ext_setup_not_found`. Битый блок
 *      → `ext_invalid_format`.
 *
 *   3. cot_extension_dependencies_statisfied()
 *      Перед установкой проверяются поля `Requires_modules`
 *      и `Requires_plugins`.
 *
 *   4. ExtensionsHelper::getTitle() / getDescription()
 *      Если язык плагина не задаёт `tpleditor_title` /
 *      `tpleditor_description`, ядро берёт `Name` и `Description`
 *      прямо из setup-файла (fallback, работает и без lang).
 *
 *   5. admin.php?m=extensions&a=hooks
 *      Просмотр зарегистрированных хуков использует данные
 *      `$db_plugins`, куда записи попали при установке из
 *      setup-файла и из хуков `BEGIN_COT_EXT` в файлах частей.
 *
 *   6. cot_extension_uninstall()
 *      Для проверки зависимостей других расширений и, при
 *      необходимости, для вызова кастомного обработчика удаления.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ДЕЛАЕТ ФАЙЛ — ПОЛЯ И ИХ КУДА ЯДРО ЗАПИСЫВАЕТ
 * ═════════════════════════════════════════════════════════════════════
 *
 * Формат — построчный `Key = Value`. Значения могут содержать
 * пробелы и любые символы, кроме переводов строк.
 *
 *   Code              Уникальный код плагина. Должен совпадать
 *                     с именем каталога: `plugins/tpleditor/` →
 *                     `Code=tpleditor`. По этому совпадению
 *                     `cot_extension_list_info()` находит файл.
 *                     При установке записывается в:
 *                       $db_core.ct_code,
 *                       $db_auth.auth_option (при auth_code='plug'),
 *                       $db_config.config_cat,
 *                       $db_plugins.pl_code.
 *
 *   Name              Отображаемое имя. Записывается в
 *                     $db_core.ct_title и $db_plugins.pl_title.
 *                     Fallback для ExtensionsHelper::getTitle(),
 *                     если lang не задаёт `tpleditor_title`.
 *
 *   Category          Категория в списке расширений.
 *                     В БД не пишется — используется для
 *                     сортировки/группировки при выводе списка.
 *                     Локализуется через $L['ext_cat_<category>'].
 *                     Здесь: 'administration'.
 *
 *   Description       Краткое описание. В БД не пишется —
 *                     хранится только в setup-файле и читается
 *                     на лету. Fallback для
 *                     ExtensionsHelper::getDescription().
 *
 *   Version           Версия в формате A.B.C. Записывается в
 *                     $db_core.ct_version. По ней ядро сравнивает
 *                     текущую версию с той, что в БД
 *                     (`version_compare`), чтобы определить,
 *                     нуждается ли расширение в обновлении.
 *                     При $update=true и совпадении версий
 *                     возвращается COT_EXT_NOTHING_TO_UPDATE.
 *
 *   Date              Дата выпуска версии. Только для отображения.
 *                     В БД не пишется.
 *
 *   Author            Автор. Только для отображения.
 *   Copyright         Копирайт. Только для отображения.
 *   Notes             Примечания. Только для отображения.
 *
 *   Auth_guests       Базовые права для гостей (маска R/W/1..5/A).
 *                     При установке конвертируется через
 *                     `cot_auth_getvalue()` в число и пишется
 *                     в $db_auth.auth_rights для групп
 *                     COT_GROUP_GUESTS и COT_GROUP_INACTIVE.
 *                     Здесь: пусто → cot_auth_getvalue('') = 0.
 *
 *   Lock_guests       Заблокированные маски для гостей →
 *                     $db_auth.auth_rights_lock.
 *                     Здесь: 'RW12345A' → 255 (все биты заперты).
 *
 *   Auth_members      Базовые права для зарегистрированных
 *                     пользователей и произвольных групп →
 *                     $db_auth.auth_rights.
 *                     Здесь: пусто → 0.
 *
 *   Lock_members      Заблокированные маски для пользователей →
 *                     $db_auth.auth_rights_lock.
 *                     Здесь: 'RW12345A' → 255.
 *
 *   Специальные группы обрабатываются ядром отдельно:
 *     COT_GROUP_BANNED      → auth_rights=0,  auth_rights_lock=255
 *     COT_GROUP_SUPERADMINS → auth_rights=255, auth_rights_lock=255
 *   Эти значения ядро выставляет принудительно, минуя поля Auth_/
 *   Lock_ из setup-файла.
 *
 *   Requires_modules     Список кодов модулей через запятую,
 *                        обязательных для работы плагина.
 *                        Проверяется
 *                        cot_extension_dependencies_statisfied()
 *                        перед установкой. В БД не пишется.
 *
 *   Requires_plugins     То же для плагинов.
 *   Recommends_modules   Рекомендуемые (не блокируют установку).
 *   Recommends_plugins   Рекомендуемые (не блокируют установку).
 *
 * ═════════════════════════════════════════════════════════════════════
 *   БЛОК BEGIN_COT_EXT_CONFIG — КОНФИГУРАЦИЯ ПЛАГИНА
 * ═════════════════════════════════════════════════════════════════════
 *
 * Второй блок в этом файле — `BEGIN_COT_EXT_CONFIG ... END_COT_EXT_CONFIG`
 * — описывает настройки, которые при установке попадут в таблицу
 * `$db_config`. Читается ядром через `cot_infoget($file, 'COT_EXT_CONFIG')`
 * и передаётся в `cot_config_parse($info_cfg)` → `cot_config_add()`.
 *
 * Формат строки в блоке (5 полей через двоеточие):
 *     <name>=<order>:<type>:<variants>:<default>:<text>
 *
 * Расшифровка полей:
 *   name      — имя опции (ключ в $cfg['plugin']['tpleditor'][...]).
 *   order     — порядок в списке настроек ('01', '02', ...).
 *   type      — тип поля. Допустимые значения (см.
 *               cot_config_parse() в system/configuration.php):
 *                 string    → COT_CONFIG_TYPE_STRING
 *                 select    → COT_CONFIG_TYPE_SELECT
 *                 radio     → COT_CONFIG_TYPE_RADIO
 *                 callback  → COT_CONFIG_TYPE_CALLBACK
 *                 hidden    → COT_CONFIG_TYPE_HIDDEN
 *                 separator → COT_CONFIG_TYPE_SEPARATOR
 *                 range     → COT_CONFIG_TYPE_RANGE
 *                 custom    → COT_CONFIG_TYPE_CUSTOM
 *               любое другое → COT_CONFIG_TYPE_TEXT
 *   variants  — список вариантов через запятую (без пробелов),
 *               только для select / radio / custom.
 *   default   — значение по умолчанию.
 *   text      — текст описания (используется, если lang не задаёт
 *               $L['cfg_<name>']).
 *
 * Что куда попадает в БД при установке:
 *   $db_config.config_owner      = 'plug'
 *   $db_config.config_cat        = 'tpleditor'  (Code)
 *   $db_config.config_subcat     = ''
 *   $db_config.config_name       = <name>
 *   $db_config.config_type       = <type> (числовой код)
 *   $db_config.config_variants   = <variants>
 *   $db_config.config_value      = <default>
 *   $db_config.config_default    = <default>
 *   $db_config.config_order      = <order>
 *   $db_config.config_text       = <text>
 *   $db_config.config_donor      = ''
 *
 * После установки на странице конфигурации плагина в админке
 * (admin.php?m=config) отображаются поля, собранные из этих строк:
 *   - type=radio      → два radio (cot_config_input, ветка
 *                       COT_CONFIG_TYPE_RADIO). Если вариантов
 *                       нет — Yes/No из $L['Yes'] / $L['No'].
 *                       Если варианты есть — подписи берутся из
 *                       $L['cfg_<name>_params'] (через
 *                       cot_config_selecttitles()).
 *   - type=select     → <select> из вариантов.
 *   - type=range      → <select> с диапазоном from:to:step.
 *   - type=string     → однострочный input.
 *   - type=hidden     → не отображается, но хранится в БД.
 *   - type=separator  → визуальный разделитель, значения нет.
 *   - type=custom     → вызов функции, имя которой указано в
 *                       variants.
 *   - type=callback   → вызов функции для получения списка
 *                       вариантов.
 *   - default         → textarea (тип COT_CONFIG_TYPE_TEXT).
 *
 * Заголовок поля на странице настроек:
 *   $L['cfg_<name>']         — заголовок (в lang-файле плагина).
 *   $L['cfg_<name>_hint']    — подсказка под полем.
 *   $L['cfg_<name>_params']  — массив подписей для вариантов
 *                              (для radio и select).
 * Если ни один ключ не найден, используется значение <text>
 * из блока config.
 *
 * Конкретно в этом файле объявлено 4 опции:
 *
 *   edit_themes   (order 01, radio, variants='0,1', default='1')
 *     Управляет разрешением редактировать .tpl темы текущего
 *     пользователя. Проверяется через tpleditor_flag('edit_themes')
 *     в tpleditor_roots() — если '0', корень темы не попадает в
 *     список разрешённых и файл темы невозможно ни открыть, ни
 *     сохранить.
 *
 *   edit_admin    (order 02, radio, variants='0,1', default='1')
 *     Разрешение редактировать .tpl админ-темы
 *     (themes/admin/<admintheme>/).
 *
 *   edit_plugins  (order 03, radio, variants='0,1', default='1')
 *     Разрешение редактировать .tpl-файлы плагинов
 *     (plugins/<code>/tpl/).
 *
 *   edit_modules  (order 04, radio, variants='0,1', default='1')
 *     Разрешение редактировать .tpl-файлы модулей
 *     (modules/<code>/tpl/).
 *
 * Значение любой из опций читается в коде через
 * `Cot::$cfg['plugin']['tpleditor'][<name>]` (см. хелпер
 * tpleditor_flag() в inc/tpleditor.functions.php). Изменения через
 * админку подхватываются немедленно (без рестарта).
 *
 * При обновлении плагина (cot_extension_update) для каждой
 * существующей опции выполняется `cot_config_modify()` — обновляются
 * config_type / config_variants / config_default / config_order /
 * config_text. При этом config_value не перезаписывается, кроме
 * случая «изменился и type, и default» — тогда значение сбрасывается
 * на default (см. cot_config_update). Удалённые из setup-файла
 * опции удаляются из $db_config, кроме type=hidden.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ПРОИСХОДИТ ПРИ УСТАНОВКЕ (cot_extension_install)
 * ═════════════════════════════════════════════════════════════════════
 *
 * Порядок действий ядра:
 *
 *   1. Проверка наличия `tpleditor.setup.php`.
 *      Отсутствует → ошибка `ext_setup_not_found`.
 *
 *   2. cot_infoget($setup_file, 'COT_EXT').
 *      Блок не найден или повреждён → ошибка `ext_invalid_format`.
 *
 *   3. Проверка cot_extension_dependencies_statisfied() по
 *      полям Requires_*. Зависимости нет — проверка проходит.
 *
 *   4. Сканирование каталога плагина: каждый файл
 *      `tpleditor[.<part>].php`, кроме `setup`, `install`,
 *      `uninstall`, `configure` (список `$cot_ext_ignore_parts`
 *      в system/extensions.php), читается через
 *      `cot_infoget($file, 'COT_EXT')`. Из него берутся поля
 *      `Hooks` и `Order`. Все найденные хуки регистрируются в
 *      $db_plugins через `cot_plugin_add()`:
 *        pl_code   = tpleditor,
 *        pl_part   = <part из имени файла>,
 *        pl_file   = tpleditor/tpleditor.<part>.php,
 *        pl_hook   = tools | ajax,
 *        pl_order  = 10 (по умолчанию),
 *        pl_active = 1,
 *        pl_module = 0.
 *
 *   5. cot_extension_add($code, $name, $version, $is_plug=true) —
 *      запись в $db_core:
 *        ct_code    = tpleditor,
 *        ct_title   = Template Editor,
 *        ct_version = 1.0.0,
 *        ct_plug    = 1.
 *
 *   6. Запись прав в $db_auth для всех групп (кроме тех,
 *      у кого skiprights) по полям Auth_* / Lock_*. Для плагина
 *      строки пишутся с auth_code='plug' и
 *      auth_option='tpleditor'.
 *
 *   7. cot_config_parse($info_cfg) + cot_config_add() — запись
 *      4 опций в $db_config (см. блок выше).
 *
 *   8. Если есть `setup/tpleditor.install.sql` — выполняется.
 *      У tpleditor его нет (своих таблиц плагин не создаёт).
 *
 *   9. Если есть `setup/tpleditor.install.php` — выполняется.
 *      У tpleditor его нет.
 *
 *  10. $cache->clear(), cot_auth_reorder().
 *
 *  Хук `extension.install.done` вызывается после всех шагов.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ПРОИСХОДИТ ПРИ ОБНОВЛЕНИИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   При $update = true:
 *
 *   - Сравнивается $info['Version'] (из setup-файла) с
 *     $db_core.ct_version. Если версии равны и $force_update
 *     выключен → возвращается COT_EXT_NOTHING_TO_UPDATE.
 *
 *   - Иначе: старые привязки хуков удаляются из $db_plugins
 *     (cot_plugin_remove), каталог сканируется заново, хуки
 *     регистрируются с актуальными Hooks и Order.
 *
 *   - Для настроек вызывается cot_config_update():
 *       * опции, удалённые из setup-файла, удаляются из
 *         $db_config (кроме type=hidden);
 *       * новые опции добавляются с default;
 *       * изменённые (type/variants/default/order/text)
 *         обновляются через cot_config_modify();
 *       * config_value не перезаписывается, кроме случая
 *         «изменился и type, и default».
 *
 *   - Обновляются только auth_rights_lock в $db_auth — сами
 *     auth_rights не трогаются, чтобы не потерять настройки
 *     администратора.
 *
 *   - Выполняются патчи из setup/ (если есть).
 *   - Обновляется $db_core.ct_version.
 *
 *   Хук `extension.update.done` вызывается в конце.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЧТО ПРОИСХОДИТ ПРИ УДАЛЕНИИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   При cot_extension_uninstall:
 *
 *   - Удаляются записи из $db_plugins (pl_code='tpleditor').
 *   - Удаляются записи из $db_auth (auth_code='plug' и
 *     auth_option='tpleditor').
 *   - Удаляются записи из $db_config
 *     (config_owner='plug' AND config_cat='tpleditor').
 *   - Удаляется запись из $db_core (ct_code='tpleditor').
 *   - Setup-файл и остальные файлы плагина не удаляются —
 *     администратор удаляет их вручную.
 *   - Если нет setup/tpleditor.uninstall.sql / .php — других
 *     действий не выполняется.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПОЧЕМУ setup.php НЕ РЕГИСТРИРУЕТСЯ КАК ХУК
 * ═════════════════════════════════════════════════════════════════════
 *
 *   В system/extensions.php есть список:
 *
 *     $cot_ext_ignore_parts = ['configure', 'install', 'setup', 'uninstall'];
 *
 *   При сканировании каталога ядро пропускает файлы с этими
 *   суффиксами и не пытается зарегистрировать их как обработчики
 *   хуков. Поэтому `tpleditor.setup.php` — это не хук-файл, а
 *   метаданные. Хук-файлы плагина:
 *     tpleditor.tools.php  (хук `tools`),
 *     tpleditor.ajax.php   (хук `ajax`).
 *   Каждый из них имеет свой собственный маленький блок
 *   `BEGIN_COT_EXT` с полем `Hooks=...`.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   РОЛЬ ПЛАГИНА В АРХИТЕКТУРЕ COTONTI
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Плагин (plug), а не модуль (module):
 *     - не объявляет собственную область структуры;
 *     - не имеет публичной части — работает только в админ-панели;
 *     - не создаёт собственных таблиц в БД;
 *     - подключается через два хука: 'tools' и 'ajax'.
 *
 *   Работает со всеми шаблонами, находящимися в разрешённых корнях:
 *     - текущая тема (themes/<theme>/),
 *     - админ-тема (themes/admin/<admintheme>/),
 *     - плагины (plugins/<code>/tpl/),
 *     - модули (modules/<code>/tpl/),
 *     - ядро (system/admin/tpl/).
 *   Список корней формируется динамически в tpleditor_roots()
 *   на основе конфигурации (edit_themes, edit_admin, edit_plugins,
 *   edit_modules) и активной темы пользователя. Ядровой корень
 *   system/admin/tpl/ включён всегда.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ХУКИ ПЛАГИНА (регистрируются при установке)
 * ═════════════════════════════════════════════════════════════════════
 *
 *   tools  → tpleditor.tools.php
 *            Точка входа админ-страницы:
 *            admin.php?m=other&p=tpleditor
 *            Режимы: список файлов / редактор CodeMirror.
 *
 *   ajax   → tpleditor.ajax.php
 *            AJAX-эндпоинт: plug.php?r=tpleditor
 *            Действия: save / read / download.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СОСТАВ ПЛАГИНА
 * ═════════════════════════════════════════════════════════════════════
 *
 *   plugins/tpleditor/
 *   ├── tpleditor.setup.php           ← этот файл (метаданные)
 *   ├── tpleditor.tools.php           ← хук 'tools'
 *   ├── tpleditor.ajax.php            ← хук 'ajax'
 *   ├── inc/
 *   │   └── tpleditor.functions.php   ← бизнес-логика (API)
 *   ├── tpl/
 *   │   ├── tpleditor.tools.tpl       ← список файлов
 *   │   └── tpleditor.editor.tpl      ← редактор CodeMirror
 *   └── lang/
 *       ├── tpleditor.ru.lang.php
 *       └── tpleditor.en.lang.php
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ПРАВА ДОСТУПА
 * ═════════════════════════════════════════════════════════════════════
 *
 *   После установки страница плагина доступна пользователям,
 *   у которых:
 *     - cot_auth('admin', 'a', 'A')            — администратор
 *       сайта с правом A на раздел admin; ИЛИ
 *     - cot_auth('plug', 'tpleditor', 'A')     — право A
 *       на сам плагин.
 *
 *   Проверка выполняется в двух местах:
 *     1. В system/admin/admin.other.php до include
 *        tpleditor.tools.php:
 *          list(..., $usr['isadmin']) = cot_auth('plug', $p);
 *          cot_block($usr['isadmin']);
 *        То есть требуется право A на plug/tpleditor.
 *     2. В самом tpleditor.tools.php и tpleditor.ajax.php:
 *          cot_block(cot_auth('admin', 'a', 'A')
 *                    || cot_auth('plug', 'tpleditor', 'A'));
 *
 *   Третий аргумент 'A' в cot_auth(...) обязателен. При маске
 *   длиннее 1 символа cot_auth возвращает массив [R,W,A],
 *   и любой непустой массив в PHP truthy. Явная маска 'A' даёт
 *   скаляр bool.
 *
 *   Установочные Auth_/Lock_ в этом файле рассчитаны на режим
 *   «только суперадмин + блокировка изменения прав через UI»:
 *     Auth_guests='',  Lock_guests='RW12345A'  → гости и
 *        неактивные: rights=0, lock=255;
 *     Auth_members='', Lock_members='RW12345A' → зарегистрированные
 *        пользователи: rights=0, lock=255;
 *     SA и banned переопределяются ядром (255/255 и 0/255).
 *   Практическое следствие: после установки не-SA администраторы
 *   НЕ попадут на страницу плагина — admin.other.php отвергает их
 *   на своём cot_block($usr['isadmin']) (у группы auth_rights=0).
 *   Чтобы расширить доступ, администратор должен явно править
 *   права группы через SQL (UI заблокирован Lock=255).
 *
 * ═════════════════════════════════════════════════════════════════════
 *   ЗАВИСИМОСТИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Обязательных зависимостей от модулей и плагинов нет
 *   (Requires_modules и Requires_plugins пусты). Плагин не требует
 *   ни одной области структуры — он сканирует .tpl-файлы напрямую
 *   через filesystem API, независимо от structure.
 *
 *   Внешняя JS-зависимость: CodeMirror 5.65.16 (CDN jsdelivr
 *   в шаблоне tpleditor.editor.tpl: ядро, режимы
 *   xml/javascript/css/htmlmixed, addon matchbrackets/closebrackets).
 *   Может быть заменена на локальную копию без изменения PHP-кода
 *   плагина.
 *
 * ═════════════════════════════════════════════════════════════════════
 *   СВЯЗАННЫЕ ССЫЛКИ
 * ═════════════════════════════════════════════════════════════════════
 *
 *   Cotonti:          https://github.com/Cotonti/Cotonti
 *   CodeMirror:       https://codemirror.net/5/
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