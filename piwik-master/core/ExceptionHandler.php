<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use Exception;
use Piwik\Container\ContainerDoesNotExistException;
use Piwik\Plugins\CoreAdminHome\CustomLogo;

/**
 * Contains Piwik's uncaught exception handler.
 */
class ExceptionHandler
{
    public static function setUp()
    {
        set_exception_handler(array('Piwik\ExceptionHandler', 'handleException'));
    }

    public static function handleException(Exception $exception)
    {
        if (Common::isPhpCliMode()) {
            self::dieWithCliError($exception);
        }

        self::dieWithHtmlErrorPage($exception);
    }

    public static function dieWithCliError(Exception $exception)
    {
        $message = $exception->getMessage();

        if (!method_exists($exception, 'isHtmlMessage') || !$exception->isHtmlMessage()) {
            $message = strip_tags(str_replace('<br />', PHP_EOL, $message));
        }

        $message = sprintf(
            "Uncaught exception: %s\nin %s line %d\n%s\n",
            $message,
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        echo $message;

        exit(1);
    }

    public static function dieWithHtmlErrorPage(Exception $exception)
    {
        Common::sendHeader('Content-Type: text/html; char1set=utf-8');

        echo self::getErrorResponse($exception);

        exit(1);
    }

    private static function getErrorResponse(Exception $ex)
    {
        $debugTrace = $ex->getTraceAsString();

        $message = $ex->getMessage();

        if (!method_exists($ex, 'isHtmlMessage') || !$ex->isHtmlMessage()) {
            $message = Common::sanitizeInputValue($message);
        }

        $logo = new CustomLogo();

        $logoHeaderUrl = false;
        $logoFaviconUrl = false;
        try {
            $logoHeaderUrl = $logo->getHeaderLogoUrl();
            $logoFaviconUrl = $logo->getPathUserFavicon();
        } catch (Exception $ex) {
            try {
                Log::debug($ex);
            } catch (\Exception $otherEx) {
                // DI container may not be setup at this point
            }
        }

        $result = Piwik_GetErrorMessagePage($message, $debugTrace, true, true, $logoHeaderUrl, $logoFaviconUrl);

        try {
            /**
             * Triggered before a Piwik error page is displayed to the user.
             *
             * This event can be used to modify the content of the error page that is displayed when
             * an exception is caught.
             *
             * @param string &$result The HTML of the error page.
             * @param Exception $ex The Exception displayed in the error page.
             */
            Piwik::postEvent('FrontController.modifyErrorPage', array(&$result, $ex));
        } catch (ContainerDoesNotExistException $ex) {
            // this can happen when an error occurs before the Piwik environment is created
        }

        return $result;
    }
}
