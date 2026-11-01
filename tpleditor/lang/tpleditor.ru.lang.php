<?php

/**
 * Template Editor — Russian language file.
 *
 * Файл: plugins/tpleditor/lang/tpleditor.ru.lang.php
 *
 * Загружается ядром через cot_langfile('tpleditor', 'plug') при активном
 * русском языке (Cot::$usr['lang'] == 'ru'). Задаёт строки интерфейса
 * и заголовки конфигурационных опций плагина.
 *
 * Два блока ключей:
 *
 *   1. Интерфейсные строки $L['tpleditor_*'] — используются в
 *      tpleditor.tools.php (assign в шаблоны) и в tpleditor.editor.tpl /
 *      tpleditor.tools.tpl через теги {L_*}. Ошибки
 *      ($L['tpleditor_err_path'], $L['tpleditor_err_read']) передаются
 *      в cot_error() → выводятся через warnings.tpl.
 *
 *   2. Настройки конфигурации $L['cfg_<name>'] / _params — читаются
 *      ядром в system/configuration.php:
 *        cot_config_titles()       → $L['cfg_<name>']  (заголовок поля);
 *        cot_config_selecttitles() → $L['cfg_<name>_params']  (подписи
 *                                     вариантов radio 0/1: ['Нет','Да']).
 *      <name> соответствует строкам блока BEGIN_COT_EXT_CONFIG
 *      в tpleditor.setup.php (edit_themes, edit_admin, edit_plugins,
 *      edit_modules).
 *
 * При отсутствии ключа в этом файле ядро использует значения по
 * умолчанию: для заголовков — текст из config_text в setup-файле,
 * для подписей — сами значения вариантов.
 *
 * @package   tpleditor
 * @version   1.0.0
 * @author    webitproff
 * @copyright Copyright (c) 2026 webitproff
 * @license   BSD
 */

defined('COT_CODE') or die('Wrong URL.');

$L['tpleditor_title']       = 'Редактор шаблонов';
$L['tpleditor_editing']     = 'Редактирование';
$L['tpleditor_save']        = 'Сохранить';
$L['tpleditor_back']        = 'К списку';
$L['tpleditor_saved']       = 'Сохранено';
$L['tpleditor_error']       = 'Ошибка';
$L['tpleditor_no_files']    = 'Нет доступных .tpl файлов';
$L['tpleditor_err_path']    = 'Недопустимый путь к файлу';
$L['tpleditor_err_read']    = 'Не удалось прочитать файл';
$L['tpleditor_size']        = 'Размер';
$L['tpleditor_mtime']       = 'Изменён';
$L['tpleditor_backup']      = 'Скачать резервную копию';

$L['cfg_edit_themes']           = 'Разрешить редактирование шаблонов темы';
$L['cfg_edit_themes_params']    = ['Нет', 'Да'];
$L['cfg_edit_admin']            = 'Разрешить редактирование шаблонов админ-темы';
$L['cfg_edit_admin_params']     = ['Нет', 'Да'];
$L['cfg_edit_plugins']          = 'Разрешить редактирование шаблонов плагинов';
$L['cfg_edit_plugins_params']   = ['Нет', 'Да'];
$L['cfg_edit_modules']          = 'Разрешить редактирование шаблонов модулей';
$L['cfg_edit_modules_params']   = ['Нет', 'Да'];