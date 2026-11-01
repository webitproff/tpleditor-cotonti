<?php

/**
 * Template Editor — English language file.
 *
 * File: plugins/tpleditor/lang/tpleditor.en.lang.php
 *
 * Loaded by the core via cot_langfile('tpleditor', 'plug') when the
 * active language is English (Cot::$usr['lang'] == 'en'), and also
 * as the fallback when the language file for the active locale is
 * missing (cot_langfile() falls back to 'en').
 *
 * Two groups of keys:
 *
 *   1. UI strings $L['tpleditor_*'] — used in tpleditor.tools.php
 *      (assigned to templates) and in tpleditor.editor.tpl /
 *      tpleditor.tools.tpl via {L_*} tags. Error strings
 *      ($L['tpleditor_err_path'], $L['tpleditor_err_read']) are
 *      passed to cot_error() and rendered via warnings.tpl.
 *
 *   2. Configuration option labels $L['cfg_<name>'] / _params —
 *      read by the core in system/configuration.php:
 *        cot_config_titles()       → $L['cfg_<name>']  (field label);
 *        cot_config_selecttitles() → $L['cfg_<name>_params']
 *                                     (radio 0/1 captions: ['No','Yes']).
 *      <name> matches the rows of the BEGIN_COT_EXT_CONFIG block in
 *      tpleditor.setup.php (edit_themes, edit_admin, edit_plugins,
 *      edit_modules).
 *
 * If a key is missing in this file, the core falls back to defaults:
 * for labels — config_text from the setup file, for captions — the
 * raw option values.
 *
 * @package   tpleditor
 * @version   1.0.0
 * @author    webitproff
 * @copyright Copyright (c) 2026 webitproff
 * @license   BSD
 */

defined('COT_CODE') or die('Wrong URL.');

$L['tpleditor_title']       = 'Template Editor';
$L['tpleditor_editing']     = 'Editing';
$L['tpleditor_save']        = 'Save';
$L['tpleditor_back']        = 'Back to list';
$L['tpleditor_saved']       = 'Saved';
$L['tpleditor_error']       = 'Error';
$L['tpleditor_no_files']    = 'No .tpl files available';
$L['tpleditor_err_path']    = 'Invalid file path';
$L['tpleditor_err_read']    = 'Failed to read file';
$L['tpleditor_size']        = 'Size';
$L['tpleditor_mtime']       = 'Modified';
$L['tpleditor_backup']      = 'Download backup copy';

$L['cfg_edit_themes']           = 'Allow editing theme templates';
$L['cfg_edit_themes_params']    = ['No', 'Yes'];
$L['cfg_edit_admin']            = 'Allow editing admin theme templates';
$L['cfg_edit_admin_params']     = ['No', 'Yes'];
$L['cfg_edit_plugins']          = 'Allow editing plugin templates';
$L['cfg_edit_plugins_params']   = ['No', 'Yes'];
$L['cfg_edit_modules']          = 'Allow editing module templates';
$L['cfg_edit_modules_params']   = ['No', 'Yes'];