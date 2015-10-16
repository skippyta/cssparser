<?php
/**
 * Entrypoint for uploading and parsing a CSS file
 */

/**
 * Declare all requirements at entrypoint level. Explicit requirements
 * at this level should make it easier to break out entrypoints into
 * separate services and deployment artifacts later.
 */
require('vendor/autoload.php');

function autoload_handler($className)
{
    include(__DIR__ . '/src/' . $className . '.php');
}
spl_autoload_register("autoload_handler");

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        /**
         * Not into throwing generic exceptions like this.
         * I also realize this doesn't actually print a 405, but
         * that's something I can easily polish at a later iteration.
         * Also, this generally belongs in whatever is handling the routing,
         * but I didn't take the time to implement a proper request router here.
         */
        throw new Exception('405 Method Not Allowed');
    }
    $uploadParseFlow = new ParseCSSFileFlow();
    $template = $uploadParseFlow->execute();
    $template->display('report.tpl');
} catch (Exception $e) {
    /**
     * NOTE: This catch-all is pretty heinous. I'd like to use these
     * control blocks to emit different HTTP status codes
     * based on the error caught, as well as different (perhaps stock)
     * HTML documents to render for specific errors, but that
     * can wait for now.
    */
    $smarty = new Smarty();
    $smarty->assign('errorMessage', $e->getMessage());
    $smarty->display('error.tpl');
}