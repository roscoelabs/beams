<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Installation;

use Exception;
use Piwik\Access;
use Piwik\AssetManager;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Db;
use Piwik\Db\Adapter;
use Piwik\DbHelper;
use Piwik\Filesystem;
use Piwik\Http;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugins\Diagnostics\DiagnosticService;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\Plugins\SitesManager\API as APISitesManager;
use Piwik\Plugins\UserCountry\LocationProvider;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\ProxyHeaders;
use Piwik\SettingsPiwik;
use Piwik\Tracker\TrackerCodeGenerator;
use Piwik\Translation\Translator;
use Piwik\Updater;
use Piwik\Url;
use Piwik\Version;
use Zend_Db_Adapter_Exception;

/**
 * Installation controller
 *
 */
class Controller extends \Piwik\Plugin\ControllerAdmin
{
    public $steps = array(
        'welcome'           => 'Installation_Welcome',
        'systemCheck'       => 'Installation_SystemCheck',
        'databaseSetup'     => 'Installation_DatabaseSetup',
        'tablesCreation'    => 'Installation_Tables',
        'setupSuperUser'    => 'Installation_SuperUser',
        'firstWebsiteSetup' => 'Installation_SetupWebsite',
        'trackingCode'      => 'General_JsTrackingTag',
        'finished'          => 'Installation_Congratulations',
    );

    /**
     * Get installation steps
     *
     * @return array installation steps
     */
    public function getInstallationSteps()
    {
        return $this->steps;
    }

    /**
     * Get default action (first installation step)
     *
     * @return string function name
     */
    function getDefaultAction()
    {
        $steps = array_keys($this->steps);
        return $steps[0];
    }

    /**
     * Installation Step 1: Welcome
     *
     * Can also display an error message when there is a failure early (eg. DB connection failed)
     */
    function welcome()
    {
        // Delete merged js/css files to force regenerations based on updated activated plugin list
        Filesystem::deleteAllCacheOnUpdate();

        $this->checkPiwikIsNotInstalled();
        $view = new View(
            '@Installation/welcome',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        $view->showNextStep = true;
        return $view->render();
    }

    /**
     * Installation Step 2: System Check
     */
    function systemCheck()
    {
        $this->checkPiwikIsNotInstalled();

        $this->deleteConfigFileIfNeeded();

        $view = new View(
            '@Installation/systemCheck',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        // Do not use dependency injection because this service requires a lot of sub-services across plugins
        /** @var DiagnosticService $diagnosticService */
        $diagnosticService = StaticContainer::get('Piwik\Plugins\Diagnostics\DiagnosticService');
        $view->diagnosticReport = $diagnosticService->runDiagnostics();

        $view->showNextStep = !$view->diagnosticReport->hasErrors();

        // On the system check page, if all is green, display Next link at the top
        $view->showNextStepAtTop = $view->showNextStep && !$view->diagnosticReport->hasWarnings();

        return $view->render();
    }

    /**
     * Installation Step 3: Database Set-up
     * @throws Exception|Zend_Db_Adapter_Exception
     */
    function databaseSetup()
    {
        $this->checkPiwikIsNotInstalled();

        $view = new View(
            '@Installation/databaseSetup',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        $view->showNextStep = false;

        $form = new FormDatabaseSetup();

        if ($form->validate()) {
            try {
                $dbInfos = $form->createDatabaseObject();

                DbHelper::checkDatabaseVersion();

                Db::get()->checkClientVersion();

                $this->createConfigFile($dbInfos);

                $this->redirectToNextStep(__FUNCTION__);
            } catch (Exception $e) {
                $view->errorMessage = Common::sanitizeInputValue($e->getMessage());
            }
        }
        $view->addForm($form);

        return $view->render();
    }

    /**
     * Installation Step 4: Table Creation
     */
    function tablesCreation()
    {
        $this->checkPiwikIsNotInstalled();

        $view = new View(
            '@Installation/tablesCreation',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        if ($this->getParam('deleteTables')) {
            Manager::getInstance()->clearPluginsInstalledConfig();
            Db::dropAllTables();
            $view->existingTablesDeleted = true;
        }

        $tablesInstalled = DbHelper::getTablesInstalled();
        $view->tablesInstalled = '';

        if (count($tablesInstalled) > 0) {

            // we have existing tables
            $view->tablesInstalled     = implode(', ', $tablesInstalled);
            $view->someTablesInstalled = true;

            $self = $this;
            Access::doAsSuperUser(function () use ($self, $tablesInstalled, $view) {
                Access::getInstance();
                if ($self->hasEnoughTablesToReuseDb($tablesInstalled) &&
                    count(APISitesManager::getInstance()->getAllSitesId()) > 0 &&
                    count(APIUsersManager::getInstance()->getUsers()) > 0
                ) {
                    $view->showReuseExistingTables = true;
                }
            });
        } else {

            DbHelper::createTables();
            DbHelper::createAnonymousUser();

            $this->updateComponents();

            Updater::recordComponentSuccessfullyUpdated('core', Version::VERSION);

            $view->tablesCreated = true;
            $view->showNextStep = true;
        }

        return $view->render();
    }

    function reuseTables()
    {
        $this->checkPiwikIsNotInstalled();

        $steps = $this->getInstallationSteps();
        $steps['tablesCreation'] = 'Installation_ReusingTables';

        $view = new View(
            '@Installation/reuseTables',
            $steps,
            'tablesCreation'
        );

        $oldVersion = Option::get('version_core');

        $result = $this->updateComponents();
        if ($result === false) {
            $this->redirectToNextStep('tablesCreation');
        }

        $view->coreError       = $result['coreError'];
        $view->warningMessages = $result['warnings'];
        $view->errorMessages   = $result['errors'];
        $view->deactivatedPlugins = $result['deactivatedPlugins'];
        $view->currentVersion  = Version::VERSION;
        $view->oldVersion  = $oldVersion;
        $view->showNextStep = true;

        return $view->render();
    }

    /**
     * Installation Step 5: General Set-up (superuser login/password/email and subscriptions)
     */
    function setupSuperUser()
    {
        $this->checkPiwikIsNotInstalled();

        $superUserAlreadyExists = Access::doAsSuperUser(function () {
            return count(APIUsersManager::getInstance()->getUsersHavingSuperUserAccess()) > 0;
        });

        if ($superUserAlreadyExists) {
            $this->redirectToNextStep('setupSuperUser');
        }

        $view = new View(
            '@Installation/setupSuperUser',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        $form = new FormSuperUser();

        if ($form->validate()) {

            try {
                $this->createSuperUser($form->getSubmitValue('login'),
                                       $form->getSubmitValue('password'),
                                       $form->getSubmitValue('email'));

                $email = $form->getSubmitValue('email');
                $newsletterPiwikORG = $form->getSubmitValue('subscribe_newsletter_piwikorg');
                $newsletterPiwikPRO = $form->getSubmitValue('subscribe_newsletter_piwikpro');
                $this->registerNewsletter($email, $newsletterPiwikORG, $newsletterPiwikPRO);
                $this->redirectToNextStep(__FUNCTION__);

            } catch (Exception $e) {
                $view->errorMessage = $e->getMessage();
            }
        }

        $view->addForm($form);

        return $view->render();
    }

    /**
     * Installation Step 6: Configure first web-site
     */
    public function firstWebsiteSetup()
    {
        $this->checkPiwikIsNotInstalled();

        $siteIdsCount = Access::doAsSuperUser(function () {
            return count(APISitesManager::getInstance()->getAllSitesId());
        });

        if ($siteIdsCount > 0) {
            // if there is a already a website, skip this step and trackingCode step
            $this->redirectToNextStep('trackingCode');
        }

        $view = new View(
            '@Installation/firstWebsiteSetup',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        $form = new FormFirstWebsiteSetup();

        if ($form->validate()) {
            $name = Common::sanitizeInputValue($form->getSubmitValue('siteName'));
            $url = Common::unsanitizeInputValue($form->getSubmitValue('url'));
            $ecommerce = (int)$form->getSubmitValue('ecommerce');

            try {
                $result = Access::doAsSuperUser(function () use ($name, $url, $ecommerce) {
                    return APISitesManager::getInstance()->addSite($name, $url, $ecommerce);
                });

                $params = array(
                    'site_idSite' => $result,
                    'site_name' => urlencode($name)
                );
                $this->addTrustedHosts($url);

                $this->redirectToNextStep(__FUNCTION__, $params);
            } catch (Exception $e) {
                $view->errorMessage = $e->getMessage();
            }
        }

        // Display previous step success message, when current step form was not submitted yet
        if (count($form->getErrorMessages()) == 0) {
            $view->displayGeneralSetupSuccess = true;
        }

        $view->addForm($form);
        return $view->render();
    }

    /**
     * Installation Step 7: Display JavaScript tracking code
     */
    public function trackingCode()
    {
        $this->checkPiwikIsNotInstalled();

        $view = new View(
            '@Installation/trackingCode',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        $siteName = Common::unsanitizeInputValue($this->getParam('site_name'));
        $idSite = $this->getParam('site_idSite');

        // Load the Tracking code and help text from the SitesManager
        $viewTrackingHelp = new \Piwik\View('@SitesManager/_displayJavascriptCode');
        $viewTrackingHelp->displaySiteName = $siteName;
        $javascriptGenerator = new TrackerCodeGenerator();
        $viewTrackingHelp->jsTag = $javascriptGenerator->generate($idSite, Url::getCurrentUrlWithoutFileName());
        $viewTrackingHelp->idSite = $idSite;
        $viewTrackingHelp->piwikUrl = Url::getCurrentUrlWithoutFileName();

        $view->trackingHelp = $viewTrackingHelp->render();
        $view->displaySiteName = $siteName;

        $view->displayfirstWebsiteSetupSuccess = true;
        $view->showNextStep = true;

        return $view->render();
    }

    /**
     * Installation Step 8: Finished!
     */
    public function finished()
    {
        $this->checkPiwikIsNotInstalled();

        $view = new View(
            '@Installation/finished',
            $this->getInstallationSteps(),
            __FUNCTION__
        );

        $form = new FormDefaultSettings();

        /**
         * Triggered on initialization of the form to customize default Piwik settings (at the end of the installation process).
         *
         * @param \Piwik\Plugins\Installation\FormDefaultSettings $form
         */
        Piwik::postEvent('Installation.defaultSettingsForm.init', array($form));

        $form->addElement('submit', 'submit', array('value' => Piwik::translate('General_ContinueToPiwik') . ' »', 'class' => 'btn btn-lg'));

        if ($form->validate()) {
            try {
                /**
                 * Triggered on submission of the form to customize default Piwik settings (at the end of the installation process).
                 *
                 * @param \Piwik\Plugins\Installation\FormDefaultSettings $form
                 */
                Piwik::postEvent('Installation.defaultSettingsForm.submit', array($form));

                $this->markInstallationAsCompleted();

                Url::redirectToUrl('index.php');
            } catch (Exception $e) {
                $view->errorMessage = $e->getMessage();
            }
        }

        $view->addForm($form);

        $view->showNextStep = false;
        $output = $view->render();

        return $output;
    }

    /**
     * System check will call this page which should load quickly,
     * in order to look at Response headers (eg. to detect if pagespeed is running)
     *
     * @return string
     */
    public function getEmptyPageForSystemCheck()
    {
        return 'Hello, world!';
    }

    /**
     * This controller action renders an admin tab that runs the installation
     * system check, so people can see if there are any issues w/ their running
     * Piwik installation.
     *
     * This admin tab is only viewable by the Super User.
     */
    public function systemCheckPage()
    {
        Piwik::checkUserHasSuperUserAccess();

        $view = new View(
            '@Installation/systemCheckPage',
            $this->getInstallationSteps(),
            __FUNCTION__
        );
        $this->setBasicVariablesView($view);

        /** @var DiagnosticService $diagnosticService */
        $diagnosticService = StaticContainer::get('Piwik\Plugins\Diagnostics\DiagnosticService');
        $view->diagnosticReport = $diagnosticService->runDiagnostics();

        return $view->render();
    }

    /**
     * Save language selection in session-store
     */
    public function saveLanguage()
    {
        $language = $this->getParam('language');
        LanguagesManager::setLanguageForSession($language);
        Url::redirectToReferrer();
    }

    /**
     * Prints out the CSS for installer/updater
     *
     * During installation and update process, we load a minimal Less file.
     * At this point Piwik may not be setup yet to write files in tmp/assets/
     * so in this case we compile and return the string on every request.
     */
    public function getBaseCss()
    {
        Common::sendHeader('Content-Type: text/css');
        return AssetManager::getInstance()->getCompiledBaseCss()->getContent();
    }

    private function getParam($name)
    {
        return Common::getRequestVar($name, false, 'string');
    }

    /**
     * Write configuration file from session-store
     */
    private function createConfigFile($dbInfos)
    {
        $config = Config::getInstance();

        // make sure DB sessions are used if the filesystem is NFS
        if (Filesystem::checkIfFileSystemIsNFS()) {
            $config->General['session_save_handler'] = 'dbtable';
        }
        if (count($headers = ProxyHeaders::getProxyClientHeaders()) > 0) {
            $config->General['proxy_client_headers'] = $headers;
        }
        if (count($headers = ProxyHeaders::getProxyHostHeaders()) > 0) {
            $config->General['proxy_host_headers'] = $headers;
        }

        if (Common::getRequestVar('clientProtocol', 'http', 'string') == 'https') {
            $protocol = 'https';
        } else {
            $protocol = ProxyHeaders::getProtocolInformation();
        }

        if (!empty($protocol)
            && !\Piwik\ProxyHttp::isHttps()) {
            $config->General['assume_secure_protocol'] = '1';
        }

        $config->General['salt'] = Common::generateUniqId();
        $config->General['installation_in_progress'] = 1;

        $config->database = $dbInfos;
        if (!DbHelper::isDatabaseConnectionUTF8()) {
            $config->database['charset'] = 'utf8';
        }

        $config->forceSave();

        // re-save the currently viewed language (since we saved the config file, there is now a salt which makes the
        // existing session cookie invalid)
        $this->resetLanguageCookie();
    }

    private function resetLanguageCookie()
    {
        /** @var Translator $translator */
        $translator = StaticContainer::get('Piwik\Translation\Translator');
        LanguagesManager::setLanguageForSession($translator->getCurrentLanguage());
    }

    private function checkPiwikIsNotInstalled()
    {
        if (!SettingsPiwik::isPiwikInstalled()) {
            return;
        }
        \Piwik\Plugins\Login\Controller::clearSession();
        $message = Piwik::translate('Installation_InvalidStateError',
            array('<br /><strong>',
                  // piwik-is-already-installed is checked against in checkPiwikServerWorking
                  '</strong><a id="piwik-is-already-installed" href=\'' . Common::sanitizeInputValue(Url::getCurrentUrlWithoutFileName()) . '\'>',
                  '</a>')
        );
        Piwik::exitWithErrorMessage($message);
    }

    /**
     * Write configuration file from session-store
     */
    private function markInstallationAsCompleted()
    {
        $config = Config::getInstance();
        unset($config->General['installation_in_progress']);
        $config->forceSave();
    }

    /**
     * Redirect to next step
     *
     * @param string $currentStep Current step
     * @return void
     */
    private function redirectToNextStep($currentStep, $parameters = array())
    {
        $steps = array_keys($this->steps);
        $nextStep = $steps[1 + array_search($currentStep, $steps)];
        Piwik::redirectToModule('Installation', $nextStep, $parameters);
    }

    /**
     * Extract host from URL
     *
     * @param string $url URL
     *
     * @return string|false
     */
    private function extractHost($url)
    {
        $urlParts = parse_url($url);
        if (isset($urlParts['host']) && strlen($host = $urlParts['host'])) {
            return $host;
        }

        return false;
    }

    /**
     * Add trusted hosts
     */
    private function addTrustedHosts($siteUrl)
    {
        $trustedHosts = array();

        // extract host from the request header
        if (($host = $this->extractHost('http://' . Url::getHost())) !== false) {
            $trustedHosts[] = $host;
        }

        // extract host from first web site
        if (($host = $this->extractHost(urldecode($siteUrl))) !== false) {
            $trustedHosts[] = $host;
        }

        $trustedHosts = array_unique($trustedHosts);
        if (count($trustedHosts)) {

            $general = Config::getInstance()->General;
            $general['trusted_hosts'] = $trustedHosts;
            Config::getInstance()->General = $general;

            Config::getInstance()->forceSave();
        }
    }

    private function createSuperUser($login, $password, $email)
    {
        $self = $this;
        Access::doAsSuperUser(function () use ($self, $login, $password, $email) {
            $api = APIUsersManager::getInstance();
            $api->addUser($login, $password, $email);
            $api->setSuperUserAccess($login, true);
        });
    }

    // should be private but there's a bug in php 5.3.6
    public function hasEnoughTablesToReuseDb($tablesInstalled)
    {
        if (empty($tablesInstalled) || !is_array($tablesInstalled)) {
            return false;
        }

        $archiveTables       = ArchiveTableCreator::getTablesArchivesInstalled();
        $baseTablesInstalled = count($tablesInstalled) - count($archiveTables);
        $minimumCountPiwikTables = 12;

        return $baseTablesInstalled >= $minimumCountPiwikTables;
    }

    private function deleteConfigFileIfNeeded()
    {
        $config = Config::getInstance();
        if ($config->existsLocalConfig()) {
            $config->deleteLocalConfig();

            // deleting the config file removes the salt, which in turns invalidates existing cookies (including the
            // one for selected language), so we re-save that cookie now
            $this->resetLanguageCookie();
        }
    }

    /**
     * @param $email
     * @param $newsletterPiwikORG
     * @param $newsletterPiwikPRO
     */
    protected function registerNewsletter($email, $newsletterPiwikORG, $newsletterPiwikPRO)
    {
        $url = Config::getInstance()->General['api_service_url'];
        $url .= '/1.0/subscribeNewsletter/';
        $params = array(
            'email'     => $email,
            'piwikorg'  => $newsletterPiwikORG,
            'piwikpro'  => $newsletterPiwikPRO,
            'url'       => Url::getCurrentUrlWithoutQueryString(),
        );
        if ($params['piwikorg'] == '1'
            || $params['piwikpro'] == '1'
        ) {
            if (!isset($params['piwikorg'])) {
                $params['piwikorg'] = '0';
            }
            if (!isset($params['piwikpro'])) {
                $params['piwikpro'] = '0';
            }
            $url .= '?' . http_build_query($params, '', '&');
            try {
                Http::sendHttpRequest($url, $timeout = 2);
            } catch (Exception $e) {
                // e.g., disable_functions = fsockopen; allow_url_open = Off
            }
        }
    }

    /**
     * @return array|bool
     */
    protected function updateComponents()
    {
        Access::getInstance();

        return Access::doAsSuperUser(function () {
            $updater = new Updater();
            $componentsWithUpdateFile = $updater->getComponentUpdates();

            if (empty($componentsWithUpdateFile)) {
                return false;
            }
            $result = $updater->updateComponents($componentsWithUpdateFile);
            return $result;
        });
    }
}
