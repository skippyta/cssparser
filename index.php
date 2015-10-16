<?php
/**
 * Entrypoint to the CSS upload/parse feature. Simply displays an upload form.
 */
require('vendor/autoload.php');

$smarty = new Smarty();
$smarty->display('upload.tpl');
