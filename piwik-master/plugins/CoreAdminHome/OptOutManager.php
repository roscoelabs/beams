<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreAdminHome;

use Piwik\Common;
use Piwik\Nonce;
use Piwik\Plugins\LanguagesManager\API as APILanguagesManager;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\Plugins\PrivacyManager\DoNotTrackHeaderChecker;
use Piwik\Tracker\IgnoreCookie;
use Piwik\Url;
use Piwik\View;

class OptOutManager
{
    /** @var DoNotTrackHeaderChecker */
    private $doNotTrackHeaderChecker;

    /** @var array */
    private $javascripts;

    /** @var array */
    private $stylesheets;

    /** @var string */
    private $title;

    /** @var View|null */
    private $view;

    /**
     * @param DoNotTrackHeaderChecker $doNotTrackHeaderChecker
     */
    public function __construct(DoNotTrackHeaderChecker $doNotTrackHeaderChecker = null)
    {
        $this->doNotTrackHeaderChecker = $doNotTrackHeaderChecker ?: new DoNotTrackHeaderChecker();

        $this->javascripts = array(
            'inline' => array(),
            'external' => array(),
        );

        $this->stylesheets = array(
            'inline' => array(),
            'external' => array(),
        );
    }

    /**
     * Add a javascript file|code into the OptOut View
     * Note: This method will not escape the inline javascript code!
     *
     * @param string $javascript
     * @param bool $inline
     */
    public function addJavascript($javascript, $inline = true)
    {
        $type = $inline ? 'inline' : 'external';
        $this->javascripts[$type][] = $javascript;
    }

    /**
     * @return array
     */
    public function getJavascripts()
    {
        return $this->javascripts;
    }

    /**
     * Add a stylesheet file|code into the OptOut View
     * Note: This method will not escape the inline css code!
     *
     * @param string $stylesheet Escaped stylesheet
     * @param bool $inline
     */
    public function addStylesheet($stylesheet, $inline = true)
    {
        $type = $inline ? 'inline' : 'external';
        $this->stylesheets[$type][] = $stylesheet;
    }

    /**
     * @return array
     */
    public function getStylesheets()
    {
        return $this->stylesheets;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return View
     * @throws \Exception
     */
    public function getOptOutView()
    {
        if ($this->view) {
            return $this->view;
        }

        $trackVisits = !IgnoreCookie::isIgnoreCookieFound();
        $dntFound = $this->getDoNotTrackHeaderChecker()->isDoNotTrackFound();

        $setCookieInNewWindow = Common::getRequestVar('setCookieInNewWindow', false, 'int');
        if ($setCookieInNewWindow) {
            $reloadUrl = Url::getCurrentQueryStringWithParametersModified(array(
                'showConfirmOnly' => 1,
                'setCookieInNewWindow' => 0,
            ));
        } else {
            $reloadUrl = false;

            $nonce = Common::getRequestVar('nonce', false);
            if ($nonce !== false && Nonce::verifyNonce('Piwik_OptOut', $nonce)) {
                Nonce::discardNonce('Piwik_OptOut');
                IgnoreCookie::setIgnoreCookie();
                $trackVisits = !$trackVisits;
            }
        }

        $language = Common::getRequestVar('language', '');
        $lang = APILanguagesManager::getInstance()->isLanguageAvailable($language)
            ? $language
            : LanguagesManager::getLanguageCodeForCurrentUser();

        $this->view = new View("@CoreAdminHome/optOut");
        $this->view->setXFrameOptions('allow');
        $this->view->dntFound = $dntFound;
        $this->view->trackVisits = $trackVisits;
        $this->view->nonce = Nonce::getNonce('Piwik_OptOut', 3600);
        $this->view->language = $lang;
        $this->view->isSafari = $this->isUserAgentSafari();
        $this->view->showConfirmOnly = Common::getRequestVar('showConfirmOnly', false, 'int');
        $this->view->reloadUrl = $reloadUrl;
        $this->view->javascripts = $this->getJavascripts();
        $this->view->stylesheets = $this->getStylesheets();
        $this->view->title = $this->getTitle();

        return $this->view;
    }

    /**
     * @return DoNotTrackHeaderChecker
     */
    protected function getDoNotTrackHeaderChecker()
    {
        return $this->doNotTrackHeaderChecker;
    }

    /**
     * @return bool
     */
    protected function isUserAgentSafari()
    {
        $userAgent = @$_SERVER['HTTP_USER_AGENT'] ?: '';
        return strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false;
    }
}
