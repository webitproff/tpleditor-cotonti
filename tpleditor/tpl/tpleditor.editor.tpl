<!-- 
	/**
	* tpleditor plugin for Cotonti v1.0.0, PHP 8.5, MySQL 8.4
	* Редактор .tpl-файлов темы, админ-темы, плагинов и модулей
	* прямо из админ-панели через CodeMirror 5.
	*
	* Filename: plugins/tpleditor/tpl/tpleditor.editor.tpl
	* Recommended: themes/admin/{admintheme}/plugins/tpleditor/tpleditor.editor.tpl
	* Main business logic: plugins/tpleditor/tpleditor.tools.php
	* AJAX backend: plugins/tpleditor/tpleditor.ajax.php (action=save)
	*
	* Cotonti:          https://github.com/Cotonti/Cotonti
	* CodeMirror:       https://codemirror.net/5/
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
	*   Шаблон режима «редактор» плагина Template Editor. Рендерится
	*   сервером из plugins/tpleditor/tpleditor.tools.php при наличии
	*   GET-параметра file и успешной валидации пути; показывается
	*   на адрес admin.php?m=other&p=tpleditor&file=<rel-path>.
	*   Содержит:
	*     • заголовок с именем файла (тег L_EDITING + <code>FILE_REL);
	*     • панель кнопок: «К списку», «Сохранить», «Скачать копию»,
	*       индикатор статуса (span#tpleditor-status);
	*     • контейнер #tpleditor-cm-wrap с <textarea id="tpleditor-source">,
	*       который JS заменяет на CodeMirror-редактор;
	*     • скрытый div#tpleditor-meta с data-* атрибутами,
	*       передающими JS значения из PHP (file, x, ajax-url,
	*       msg-saved, msg-error);
	*     • подключение CodeMirror 5.65.16 (CDN jsdelivr) и инлайн-JS,
	*       который создаёт редактор и обрабатывает сохранение через
	*       fetch POST на AJAX_URL.
	*
	*   ПРИМЕР admin.php?m=other&p=tpleditor&file=themes/mytheme/header.tpl
	*
	*   При отсутствии параметра file плагин рендерит другой шаблон
	*   (tpleditor.tools.tpl, режим «список файлов»), не этот.
	*
	* =====================================================================
	* КОНТРАКТ С PHP (tags, назначаемые в tools.php)
	* =====================================================================
	*   tpleditor.tools.php в ветке «редактор» выполняет
	*   $t->assign([...]); $t->parse('MAIN'); $pluginBody = $t->text('MAIN');.
	*   В шаблон попадают следующие значения:
	*
	*   {L_EDITING}       — Cot::$L['tpleditor_editing'] (ru: «Редактирование»).
	*   {FILE_REL}        — относительный путь к .tpl (htmlspecialchars в PHP).
	*                       Используется дважды: в видимом <code> и в
	*                       data-file у #tpleditor-meta. Оба раза значение
	*                       одинаковое — «сырой» относительный путь без
	*                       абсолютного префикса.
	*   {L_BACK}          — Cot::$L['tpleditor_back'] («К списку»).
	*   {BACK_URL}        — cot_url('admin', ['m'=>'other','p'=>'tpleditor']) —
	*                       ссылка возврата к списку файлов.
	*   {L_SAVE}          — Cot::$L['tpleditor_save'] («Сохранить»).
	*   {L_BACKUP}        — Cot::$L['tpleditor_backup'] («Скачать резервную копию»).
	*   {BACKUP_URL}      — cot_absoluteUrl('plug',
	*                         ['r'=>'tpleditor','a'=>'download'], '', true)
	*                       . '&file=' . $file . '&x=' . Cot::$sys['xk'].
	*                       Четвёртый аргумент cot_absoluteUrl —
	*                       $htmlspecialcharsBypass = true, поэтому
	*                       разделитель '&', а не '&amp;'.
	*                       Значение $file НЕ urlencode — безопасно для
	*                       типовых путей без & # ? = пробелов.
	*   {X_TOKEN}         — Cot::$sys['xk'] — CSRF-токен Cotonti.
	*                       Используется в data-x для JS.
	*   {AJAX_URL}        — cot_url('plug', ['r'=>'tpleditor']) =
	*                       'index.php?r=tpleditor' (через cot_url —
	*                       генерируется относительно текущего скрипта;
	*                       в контексте AJAX это index.php, router
	*                       разворачивает r= в extensionCode='tpleditor').
	*   {L_SAVED}         — Cot::$L['tpleditor_saved'] («Сохранено»).
	*                       Используется в data-msg-saved.
	*   {L_ERROR}         — Cot::$L['tpleditor_error'] («Ошибка»).
	*                       Используется в data-msg-error.
	*   {FILE_CONTENT}    — содержимое .tpl (htmlspecialchars в PHP).
	*                       Вставляется внутрь <textarea id="tpleditor-source">;
	*                       браузер декодирует HTML-сущности, и CodeMirror
	*                       получает исходный текст .tpl.
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
	*   Ошибки валидации пути и ошибки чтения файла в tools.php
	*   отображаются здесь.
	*
	* =====================================================================
	* ТЕГИ ШАБЛОНА — ПО ГРУППАМ
	* =====================================================================
	*   ===== Заголовок =====
	*     {L_EDITING}  — «Редактирование».
	*     {FILE_REL}   — путь к файлу (htmlspecialchars).
	*
	*   ===== Панель кнопок =====
	*     {BACK_URL}   — ссылка «К списку» (btn-secondary).
	*     {L_BACK}     — подпись.
	*     {L_SAVE}     — подпись «Сохранить» (btn-danger, id=tpleditor-save).
	*     {BACKUP_URL} — ссылка «Скачать» (btn-outline-success,
	*                    target="_blank", rel="noopener").
	*     {L_BACKUP}   — подпись.
	*
	*   ===== Редактор =====
	*     {FILE_CONTENT} — содержимое <textarea id="tpleditor-source">.
	*                      htmlspecialchars'ится в PHP — безопасно,
	*                      даже если .tpl содержит </textarea>.
	*
	*   ===== Метаданные для JS (div#tpleditor-meta) =====
	*     data-file="{FILE_REL}"      — относительный путь.
	*     data-x="{X_TOKEN}"          — CSRF-токен.
	*     data-ajax-url="{AJAX_URL}"  — endpoint AJAX.
	*     data-msg-saved="{L_SAVED}"  — текст успеха.
	*     data-msg-error="{L_ERROR}"  — текст ошибки.
	*     JS читает их через dataset: meta.dataset.file, .x,
	*     .ajaxUrl (camelCase от ajax-url), .msgSaved, .msgError.
	*
	* =====================================================================
	* JS-БЛОК (инлайн, IIFE)
	* =====================================================================
	*   Инициализация CodeMirror:
	*     var editor = CodeMirror.fromTextArea(
	*         document.getElementById('tpleditor-source'),
	*         {
	*             mode: 'htmlmixed',       // требует xml + javascript + css
	*             theme: 'eclipse',        // требует theme/eclipse.min.css
	*             lineNumbers: true,
	*             lineWrapping: true,
	*             matchBrackets: true,     // addon/edit/matchbrackets
	*             autoCloseBrackets: true, // addon/edit/closebrackets
	*             indentUnit: 4,
	*             tabSize: 4,
	*             extraKeys: {
	*                 'Ctrl-S': save, 'Cmd-S': save,
	*                 'Tab': add-indent, 'Shift-Tab': subtract-indent
	*             }
	*         });
	*   fromTextArea() скрывает оригинальный textarea; значение
	*   берётся из его содержимого (HTML-сущности уже декодированы
	*   браузером).
	*
	*   ResizeObserver:
	*     Если доступен, observe(wrap) вызывает editor.refresh()
	*     при каждом изменении размеров #tpleditor-cm-wrap.
	*     Дополнительно setTimeout(..., 0) однократно вызывает
	*     refresh() после того, как браузер посчитал размеры.
	*     Это нужно, потому что CodeMirror внутри элемента
	*     с height: 100% не пересчитывает размер при первой отрисовке.
	*
	*   save() (при клике #tpleditor-save и Ctrl/Cmd-S):
	*     1. statusEl.textContent = '...', statusEl.className = 'text-muted ms-2'
	*     2. saveBtn.disabled = true
	*     3. body = new URLSearchParams():
	*          x       = data-x
	*          a       = 'save'
	*          file    = data-file
	*          content = editor.getValue()
	*     4. fetch(AJAX_URL, {method:'POST',
	*          headers:{'Content-Type':'application/x-www-form-urlencoded'},
	*          body: body})
	*     5. .then(r => r.json())
	*     6. .then(data):
	*          - data.success === true:
	*              statusEl.textContent = msgSaved + ' (' + data.bytes + ' B)'
	*              statusEl.className = 'text-success ms-2'
	*          - иначе:
	*              statusEl.textContent = msgError + ': ' + data.message
	*              statusEl.className = 'text-danger ms-2'
	*          - в обоих случаях saveBtn.disabled = false
	*     7. .catch(err):
	*          statusEl.textContent = msgError + ': ' + err.message
	*          statusEl.className = 'text-danger ms-2'
	*          saveBtn.disabled = false
	*          console.error(err)
	*
	*   Обработчик клика: saveBtn.addEventListener('click', save).
	*
	*   Ожидаемая форма ответа AJAX — JSON (см. tpleditor.ajax.php):
	*     { success: true|false, message: string, bytes?: int }
	*   Код ответа (200/400/500) JS не проверяет — только data.success.
	*   При HTTP-ошибке сервер всё равно отдаёт JSON, и .then(r => r.json())
	*   его парсит; сетевые исключения (TypeError, DNS, offline) уходят
	*   в .catch.
	*
	* =====================================================================
	* ВНЕШНИЕ JS/CSS-ЗАВИСИМОСТИ
	* =====================================================================
	*   Все ресурсы подключаются с cdn.jsdelivr.net, фиксированная версия
	*   CodeMirror 5.65.16:
	*
	*   CSS (head):
	*     lib/codemirror.min.css                     — базовые стили;
	*     theme/eclipse.min.css                      — тема eclipse.
	*     Ссылка на theme/dracula.min.css закомментирована в шаблоне.
	*
	*   JS:
	*     lib/codemirror.min.js                      — ядро;
	*     mode/xml/xml.min.js                        — режим xml;
	*     mode/javascript/javascript.min.js          — режим js;
	*     mode/css/css.min.js                        — режим css;
	*     mode/htmlmixed/htmlmixed.min.js            — режим htmlmixed
	*                                                  (комбинирует xml+css+js);
	*     addon/edit/matchbrackets.min.js            — matchBrackets;
	*     addon/edit/closebrackets.min.js            — autoCloseBrackets.
	*
	*   Оффлайн-развёртывание: файлы можно положить локально
	*   (например, plugins/tpleditor/js/) и поменять href/src — PHP-код
	*   плагина изменений не требует.
	*
	* =====================================================================
	* CSS (инлайн <style>)
	* =====================================================================
	*   #tpleditor-cm-wrap:
	*     resize: vertical; overflow: hidden;
	*     height: 600px; min-height: 200px; max-height: 90vh;
	*     border: 1px solid #444.
	*     Пользователь может тянуть контейнер вертикально;
	*     ResizeObserver обновляет редактор.
	*
	*   #tpleditor-cm-wrap .CodeMirror:
	*     height: 100%; font-size: 14px.
	*     Редактор занимает всю высоту обёртки.
	*
	*   Стилизация .cm-s-eclipse — переопределение цветов темы
	*   (перекрывает значения из eclipse.min.css):
	*     .CodeMirror      background: #ffffff, color: #000000;
	*     .cm-tag          #500573  (html-теги);
	*     .cm-attribute    #d40f0f  (атрибуты);
	*     .cm-string       #0942ad  (строки в кавычках);
	*     .cm-comment      #008000 italic (комментарии);
	*     .cm-keyword      #0000ff  (ключевые слова JS);
	*     .cm-number       #ff6600  (числа);
	*     .cm-meta         #808080  (служебные символы < >).
	*   Эти правила важны: без них подсветка шла бы по дефолтной
	*   палитре eclipse.
	*
	* =====================================================================
	* ПРАВИЛА И ОГРАНИЧЕНИЯ
	* =====================================================================
	*   • Шаблон подключает CodeMirror с внешнего CDN. При отсутствии
	*     доступа к jsdelivr редактор не инициализируется —
	*     остаётся голый <textarea id="tpleditor-source"> с
	*     содержимым {FILE_CONTENT} без подсветки и line numbers.
	*     Сохранение при этом всё равно работает: save() читает
	*     editor.getValue(), но при отсутствии CodeMirror JS
	*     падает на строке CodeMirror.fromTextArea — сохранение
	*     через кнопку недоступно. Резервного пути (fallback на
	*     прямое чтение textarea.value) в шаблоне нет.
	*   • FILE_CONTENT и FILE_REL санитайзятся через htmlspecialchars
	*     в PHP (tpleditor.tools.php), поэтому не могут выйти
	*     за пределы своих тегов в разметке. XSS через имя файла
	*     или содержимое .tpl исключён на уровне PHP.
	*   • Форма отсутствует: сохранение выполняется JS через fetch,
	*     а не POST-формой. Поэтому CSRF-токен x передаётся вручную
	*     через URLSearchParams; cot_outputFilters() в
	*     system/functions.php не добавляет сюда <input name="x">
	*     (нет тега <form> с method="post").
	*   • Сохранение доступно только при успешной инициализации
	*     CodeMirror — см. первый пункт.
	*   • Ctrl/Cmd-S перехватывается JS: extraKeys 'Ctrl-S' / 'Cmd-S'
	*     вызывают save() и возвращают false. Стандартное поведение
	*     браузера (Save Page) подавлено.
	*   • Tab и Shift-Tab в редакторе выполняют indentSelection('add')
	*     и indentSelection('subtract'), а не переход фокуса.
	*   • BACKUP_URL использует GET-токен x; его проверяет
	*     cot_check_xg(false) в ajax.php (action=download).
	*     Значение $file в BACKUP_URL не urlencode — путь с
	*     символами & # ? = пробел сломает URL. Для типовых
	*     путей .tpl это не проблема.
	*   • Оба тега AJAX_URL в tools.php присвоены дважды подряд
	*     одинаковым значением (избыточная строка в PHP — не в
	*     этом шаблоне, поведение шаблона не задевает).
	*   • Индикатор статуса #tpleditor-status изначально пуст.
	*     Заполняется только после первой попытки сохранения.
	*     Сообщения об успехе/ошибке исчезают со страницы при
	*     перезагрузке (они не сохраняются в $_SESSION).
	*   • <textarea> и div#tpleditor-meta расположены до
	*     подключения CodeMirror — это обязательно, потому что
	*     fromTextArea вызывается сразу в IIFE, до DOMContentLoaded,
	*     и требует уже существующих элементов. Скрипты находятся
	*     в конце блока MAIN, поэтому к моменту их выполнения
	*     элементы выше уже есть в DOM.
	*
	* =====================================================================
	* ХУКИ
	* =====================================================================
	*   Шаблон не вызывает собственных хуков. Точка входа —
	*   tools (tpleditor.tools.php), тот же шаблон рендерится
	*   только при наличии GET file и успешной валидации пути.
	*
	* =====================================================================
	* ЯЗЫКОВЫЕ КЛЮЧИ
	* =====================================================================
	*   В шаблоне через {L_*} (переданы как теги из tools.php):
	*     tpleditor_editing, tpleditor_back, tpleditor_save,
	*     tpleditor_backup, tpleditor_saved, tpleditor_error.
	*   Через {PHP.cfg.*} — только системные пути:
	*     {PHP.cfg.themes_dir}, {PHP.cfg.admintheme}.
	*   Прямого доступа к {PHP.L.*} в этом шаблоне нет.
	*/
-->

<!-- BEGIN: MAIN -->
<h1>{L_EDITING} </h1>
<code>{FILE_REL}</code>
{FILE "{PHP.cfg.themes_dir}/admin/{PHP.cfg.admintheme}/warnings.tpl"}

<div class="d-flex gap-2 align-items-center my-3">
    <a href="{BACK_URL}" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> {L_BACK}
	</a>
    <button type="button" id="tpleditor-save" class="btn btn-danger">
        <i class="fa-solid fa-floppy-disk"></i> {L_SAVE}
	</button>
    <a href="{BACKUP_URL}" class="btn btn-outline-success" target="_blank" rel="noopener">
        <i class="fa-solid fa-download"></i> {L_BACKUP}
	</a>
    <span id="tpleditor-status" class="text-muted ms-2"></span>
</div>

<div id="tpleditor-cm-wrap">
    <textarea id="tpleditor-source">{FILE_CONTENT}</textarea>
</div>

<div id="tpleditor-meta"
data-file="{FILE_REL}"
data-x="{X_TOKEN}"
data-ajax-url="{AJAX_URL}"
data-msg-saved="{L_SAVED}"
data-msg-error="{L_ERROR}"></div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.css">
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/dracula.min.css"> -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/theme/eclipse.min.css">
<style>
    #tpleditor-cm-wrap {
	resize: vertical;
	overflow: hidden;
	height: 600px;
	min-height: 200px;
	max-height: 90vh;
	border: 1px solid #444;
    }
    #tpleditor-cm-wrap .CodeMirror {
	height: 100%;
	font-size: 14px;
    }
	
    /* фон и основной цвет текста */
    .cm-s-eclipse.CodeMirror {
	background: #ffffff;
	color: #000000;
    }
	
    /* html-теги: div, span, a и т.д. */
    .cm-s-eclipse span.cm-tag {
	color: #500573;
    }
	
    /* атрибуты: class, id, href */
    .cm-s-eclipse span.cm-attribute {
	color: #d40f0f;
    }
	
    /* строки в кавычках */
    .cm-s-eclipse span.cm-string {
	color: #0942ad;
    }
	
    /* комментарии */
    .cm-s-eclipse span.cm-comment {
	color: #008000;
	font-style: italic;
    }
	
    /* ключевые слова JS */
    .cm-s-eclipse span.cm-keyword {
	color: #0000ff;
    }
	
    /* числа */
    .cm-s-eclipse span.cm-number {
	color: #ff6600;
    }
	
    /* служебные символы, < > */
		.cm-s-eclipse span.cm-meta {
        color: #808080;
		}
	</style>
	<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/lib/codemirror.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/xml/xml.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/javascript/javascript.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/css/css.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/matchbrackets.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.16/addon/edit/closebrackets.min.js"></script>
	<script>
		(function () {
			var meta     = document.getElementById('tpleditor-meta');
			var file     = meta.dataset.file;
			var xToken   = meta.dataset.x;
			var ajaxUrl  = meta.dataset.ajaxUrl;
			var msgSaved = meta.dataset.msgSaved;
			var msgError = meta.dataset.msgError;
			var statusEl = document.getElementById('tpleditor-status');
			var saveBtn  = document.getElementById('tpleditor-save');
			var wrap     = document.getElementById('tpleditor-cm-wrap');
			
			var editor = CodeMirror.fromTextArea(document.getElementById('tpleditor-source'), {
				mode: 'htmlmixed',
				theme: 'eclipse',
				lineNumbers: true,
				lineWrapping: true,
				matchBrackets: true,
				autoCloseBrackets: true,
				indentUnit: 4,
				tabSize: 4,
				extraKeys: {
					'Ctrl-S': function () { save(); return false; },
					'Cmd-S': function () { save(); return false; },
					'Tab': function (cm) { cm.indentSelection('add'); },
					'Shift-Tab': function (cm) { cm.indentSelection('subtract'); }
				}
			});
			
			if (window.ResizeObserver) {
				new ResizeObserver(function () { editor.refresh(); }).observe(wrap);
			}
			
			// однократный refresh, когда браузер уже посчитал размеры контейнера
			setTimeout(function () { editor.refresh(); }, 0);
			
			function save() {
				statusEl.textContent = '...';
				statusEl.className = 'text-muted ms-2';
				saveBtn.disabled = true;
				
				var body = new URLSearchParams();
				body.set('x', xToken);
				body.set('a', 'save');
				body.set('file', file);
				body.set('content', editor.getValue());
				
				fetch(ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body
				})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					saveBtn.disabled = false;
					if (data.success) {
						statusEl.textContent = (msgSaved || 'Saved') + ' (' + data.bytes + ' B)';
						statusEl.className = 'text-success ms-2';
						} else {
						statusEl.textContent = (msgError || 'Error') + ': ' + (data.message || '?');
						statusEl.className = 'text-danger ms-2';
					}
				})
				.catch(function (err) {
					saveBtn.disabled = false;
					statusEl.textContent = (msgError || 'Error') + ': ' + err.message;
					statusEl.className = 'text-danger ms-2';
					console.error(err);
				});
			}
			
			saveBtn.addEventListener('click', save);
		})();
	</script>
<!-- END: MAIN -->