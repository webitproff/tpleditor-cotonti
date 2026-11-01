<!-- 
	/**
	* tpleditor plugin for Cotonti v1.0.0, PHP 8.5, MySQL 8.4
	* Список всех доступных .tpl-файлов темы, админ-темы, плагинов
	* и модулей для редактора CodeMirror.
	*
	* Filename: plugins/tpleditor/tpl/tpleditor.tools.tpl
	* Recommended: themes/admin/{admintheme}/plugins/tpleditor/tpleditor.tools.tpl
	* Main business logic: plugins/tpleditor/tpleditor.tools.php
	*
	* Cotonti:          https://github.com/Cotonti/Cotonti
	*
	* Date: Nov 01, 2026
	* @package tpleditor
	* @version 1.0.0
	* @author webitproff
	* @copyright Copyright (c) webitproff 2026
	* @license BSD
	*
	* =====================================================================
	* НАЗНАЧЕНИЕ
	* =====================================================================
	*   Шаблон режима «список файлов» плагина Template Editor. Рендерится
	*   сервером из plugins/tpleditor/tpleditor.tools.php когда GET-параметр
	*   file пуст или не задан; показывается на адрес
	*   admin.php?m=other&p=tpleditor без параметра file.
	*   Содержит:
	*     • заголовок страницы (иконка fa-file-code + L_TITLE);
	*     • включает {FILE .../warnings.tpl} — блок сообщений Cotonti;
	*     • список <a>-строк list-group: иконка, <code>FILE_REL</code>,
	*       бейдж-метка корня (FILE_LABEL), размер (FILE_SIZE) и дата
	*       изменения (FILE_MTIME) справа; каждая строка — ссылка
	*       на редактор этого файла (FILE_URL);
	*     • блок пустого состояния MAIN.NO_FILES — если список пуст.
	*
	*   ПРИМЕР admin.php?m=other&p=tpleditor
	*
	*   При наличии GET file рендерится другой шаблон
	*   (tpleditor.editor.tpl, режим «редактор»), не этот.
	*
	* =====================================================================
	* КОНТРАКТ С PHP (tags, назначаемые в tools.php)
	* =====================================================================
	*   tpleditor.tools.php в ветке «список» выполняет:
	*     - $t->assign([...]) для тегов шапки;
	*     - if (empty($files)) $t->parse('MAIN.NO_FILES');
	*       else { foreach ($files as $f) { $t->assign([...]);
	*                                     $t->parse('MAIN.FILE_ROW'); } }
	*     - $t->parse('MAIN'); $pluginBody = $t->text('MAIN');
	*   В шаблон попадают следующие значения:
	*
	*   ===== Шапка =====
	*   {L_TITLE}     — Cot::$L['tpleditor_title'] («Редактор шаблонов»).
	*   {L_NO_FILES}  — Cot::$L['tpleditor_no_files']
	*                   («Нет доступных .tpl файлов»).
	*
	*   ===== Строка списка (MAIN.FILE_ROW) =====
	*   Источник данных — tpleditor_list_files() (см.
	*   plugins/tpleditor/inc/tpleditor.functions.php). Возвращает
	*   отсортированный по 'rel' (strcasecmp) массив записей
	*   {rel, size, mtime, label}.
	*
	*   {FILE_REL}   — $f['rel'] через htmlspecialchars. Относительный
	*                  путь от корня установки, например:
	*                    themes/mytheme/header.tpl
	*                    plugins/tpleditor/tpl/tpleditor.tools.tpl
	*                    system/admin/tpl/admin.tpl
	*                  Форма совместима с параметром file у
	*                  tpleditor_validate_path() — клик по строке
	*                  отправляет это же значение в GET file,
	*                  и валидация примет его без нормализации.
	*
	*   {FILE_SIZE}  — $f['size'], int (байты). Выводится как есть
	*                  (число). Рядом в шаблоне стоит ' B' — образует
	*                  строку вида «1234 B». Форматирования через
	*                  cot_build_filesize() (KB/MB) в этом шаблоне
	*                  нет — показываются именно байты.
	*
	*   {FILE_MTIME} — $f['mtime'] после cot_date('datetime_medium', ...)
	*                  в PHP. То есть уже локализованная строка
	*                  (день, дата, время) в часовом поясе текущего
	*                  пользователя, а не unix timestamp.
	*
	*   {FILE_LABEL} — $f['label'] через htmlspecialchars. Метка
	*                  корня из tpleditor_roots():
	*                    'Theme: <theme>'        (текущая тема)
	*                    'Admin theme: <theme>'  (админ-тема)
	*                    'Plugin: <code>'        (плагин)
	*                    'Module: <code>'        (модуль)
	*                    'Core admin'            (ядро, всегда)
	*                  Выводится как бейдж <span class="badge bg-secondary">.
	*
	*   {FILE_URL}   — URL редактора этого файла:
	*                    cot_absoluteUrl('admin',
	*                        ['m'=>'other','p'=>'tpleditor'], '', true)
	*                    . '&file=' . $f['rel']
	*                  Четвёртый аргумент cot_absoluteUrl —
	*                  $htmlspecialcharsBypass = true, поэтому
	*                  разделитель '&', а не '&amp;'. Значение
	*                  $f['rel'] НЕ urlencode — безопасно для типовых
	*                  путей без & # ? = пробелов.
	*
	*   Теги {L_*} уже локализованы на момент assign; если ключ в lang
	*   отсутствует, PHP выведет null → пустую строку.
	*
	* =====================================================================
	* ВКЛЮЧАЕМЫЙ БЛОК WARNINGS
	* =====================================================================
	*   {FILE "{PHP.cfg.themes_dir}/admin/{PHP.cfg.admintheme}/warnings.tpl"}
	*   XTemplate::prepare() → restartIncludeFiles() разворачивает
	*   {FILE ...} на этапе компиляции (до разбора блоков). Содержимое
	*   warnings.tpl (блоки ERROR/ERROR_ROW, WARNING/WARNING_ROW,
	*   DONE/DONE_ROW) становится вложенными блоками MAIN и попадает
	*   в $this->index по путям MAIN.ERROR.ERROR_ROW и т.д.
	*   cot_display_messages($t) в tools.php (вызывается до parse('MAIN'))
	*   находит эти блоки и выводит накопленные за запрос сообщения;
	*   после этого вызывает cot_clear_messages().
	*   В этой ветке tools.php сообщения появляются только при
	*   неудачной валидации пути или при ошибке чтения файла (см.
	*   ветку «редактор») — при успешном открытии списка их нет.
	*
	* =====================================================================
	* СТРУКТУРА БЛОКОВ
	* =====================================================================
	*   MAIN
	*   ├─ (inline) <h1> с L_TITLE
	*   ├─ (inline) {FILE .../warnings.tpl}
	*   ├─ (inline) <div class="list-group"> — контейнер списка
	*   │   └─ FILE_ROW  (BEGIN: FILE_ROW ... END: FILE_ROW)
	*   │       одна <a> на файл. Парсится в цикле foreach по $files.
	*   └─ NO_FILES      (BEGIN: NO_FILES ... END: NO_FILES)
	*       блок пустого состояния. Парсится один раз, если
	*       tpleditor_list_files() вернул пустой массив.
	*
	*   Обратите внимание: FILE_ROW вложен внутри <div class="list-group">,
	*   но НЕ обёрнут в промежуточный контейнер-список вроде <ul>/<ol>.
	*   Блок NO_FILES находится ЗА пределами <div class="list-group">.
	*   Соответственно, при пустом списке разметка <div class="list-group">
	*   в HTML всё равно останется пустой — это не ошибка, просто
	*   Bootstrap корректно отрисует пустой контейнер.
	*
	* =====================================================================
	* ПРАВИЛА И ОГРАНИЧЕНИЯ
	* =====================================================================
	*   • FILE_REL и FILE_LABEL санитайзятся через htmlspecialchars в PHP.
	*     XSS через имя файла или метку корня исключён на уровне PHP.
	*   • FILE_URL передаётся в href без htmlspecialchars — значение
	*     получено через cot_absoluteUrl(..., true) и содержит '&',
	*     что валидно в HTML-атрибуте href (не требует '&amp;' для
	*     HTML5). $f['rel'] в URL не urlencode — путь с символами
	*     & # ? = пробел сломает URL. Для типовых .tpl-путей это
	*     не проблема.
	*   • FILE_SIZE выводится в байтах, без человекочитаемого
	*     форматирования (KB/MB). cot_build_filesize() в шаблоне
	*     не используется — если нужно «1.2 MB» вместо «1234567 B»,
	*     это делается в PHP (в tools.php) или через тег-фильтр,
	*     а не здесь.
	*   • FILE_MTIME приходит уже в виде локализованной строки —
	*     шаблон её не форматирует.
	*   • Список отсортирован сервером (usort по strcasecmp в
	*     tpleditor_list_files()). Шаблон не сортирует — порядок
	*     соответствует возвращённому массиву.
	*   • Формы и JS в этом шаблоне нет. Переход в редактор —
	*     обычный GET по href. Никакие фильтры/сортировки на этой
	*     странице не предусмотрены.
	*   • Разделитель «·» между размером и датой — статический
	*     символ в шаблоне, не языковая строка.
	*   • Bootstrap-классы text-muted в BS 5.3 помечен deprecated
	*     (актуально text-body-secondary), но визуально работает.
	*     Это не ошибка шаблона — оставлено для совместимости
	*     с предыдущими версиями BS 5.x.
	*   • Строка списка — единый <a>, обёрнутый в
	*     .list-group-item-action. Кликабельна вся строка, включая
	*     бейдж и размер/дату.
	*
	* =====================================================================
	* ХУКИ
	* =====================================================================
	*   Шаблон не вызывает собственных хуков. Точка входа —
	*   tools (tpleditor.tools.php), рендерится только в режиме
	*   «список» (без GET file).
	*
	* =====================================================================
	* ЯЗЫКОВЫЕ КЛЮЧИ
	* =====================================================================
	*   В шаблоне через {L_*} (переданы как теги из tools.php):
	*     tpleditor_title    — заголовок страницы,
	*     tpleditor_no_files — текст пустого состояния.
	*   Через {PHP.cfg.*} — только системные пути:
	*     {PHP.cfg.themes_dir}, {PHP.cfg.admintheme}.
	*   Прямого доступа к {PHP.L.*} в этом шаблоне нет.
	*/
-->

<!-- BEGIN: MAIN -->
<h1><i class="fa-solid fa-file-code"></i> {L_TITLE}</h1>

{FILE "{PHP.cfg.themes_dir}/admin/{PHP.cfg.admintheme}/warnings.tpl"}



<div class="list-group mt-3">
    <!-- BEGIN: FILE_ROW -->
    <a href="{FILE_URL}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="fa-regular fa-file-code"></i> <code>{FILE_REL}</code>
            <span class="badge bg-secondary ms-2">{FILE_LABEL}</span></span>
        <small class="text-muted">{FILE_SIZE} B · {FILE_MTIME}</small>
    </a>
    <!-- END: FILE_ROW -->
</div>

<!-- BEGIN: NO_FILES -->
<div class="alert alert-info mt-3">{L_NO_FILES}</div>
<!-- END: NO_FILES -->
<!-- END: MAIN -->