<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Fixtures;

use Piwik\Access;
use Piwik\Plugins\Goals\API as APIGoals;
use Piwik\Plugins\SegmentEditor\API as APISegmentEditor;
use Piwik\Plugins\UserCountry\LocationProvider\GeoIp;
use Piwik\Plugins\UserCountry\LocationProvider;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\OverrideLogin;

/**
 * Imports visits from several log files using the python log importer.
 */
class ManySitesImportedLogs extends Fixture
{
    public $dateTime = '2012-08-09 11:22:33';
    public $idSite = 1;
    public $idSite2 = 2;
    public $idGoal = 1;
    public $segments = null; // should be array mapping segment name => segment definition

    public $addSegments = false;
    public $includeIisWithCustom = false;
    public $includeNetscaler = false;
    public $includeCloudfront = false;
    public $includeCloudfrontRtmp = false;
    public $includeNginxJson = false;
    public $includeApiCustomVarMapping = false;

    public function setUp()
    {
        $this->setUpWebsitesAndGoals();
        self::downloadGeoIpDbs();

        LocationProvider::$providers = null;
        GeoIp::$geoIPDatabaseDir = 'tests/lib/geoip-files';
        LocationProvider::setCurrentProvider('geoip_php');

        $this->trackVisits();
        $this->setupSegments();
    }

    public function tearDown()
    {
        LocationProvider::$providers = null;
        GeoIp::$geoIPDatabaseDir = 'tests/lib/geoip-files';
        ManyVisitsWithGeoIP::unsetLocationProvider();
    }

    public function setUpWebsitesAndGoals()
    {
        // for conversion testing
        if (!self::siteCreated($idSite = 1)) {
            self::createWebsite($this->dateTime);
        }

        if (!self::goalExists($idSite = 1, $idGoal = 1)) {
            APIGoals::getInstance()->addGoal($this->idSite, 'all', 'url', 'http', 'contains', false, 5);
        }

        if (!self::siteCreated($idSite = 2)) {
            self::createWebsite($this->dateTime, $ecommerce = 0, $siteName = 'Piwik test two',
                $siteUrl = 'http://example-site-two.com');
        }
    }

    const SEGMENT_PRE_ARCHIVED = 'visitCount<=5;visitorType!=non-existing-type;daysSinceFirstVisit<=50';
    const SEGMENT_PRE_ARCHIVED_CONTAINS_ENCODED = 'visitCount<=5;visitorType!=re%2C%3Btest%20is%20encoded;daysSinceFirstVisit<=50';

    public function getDefaultSegments()
    {
        return array(
            'segmentOnlyOneSite'   => array('definition'      => 'browserCode==IE',
                                            'idSite'          => $this->idSite,
                                            'autoArchive'     => true,
                                            'enabledAllUsers' => true),

            'segmentNoAutoArchive' => array('definition'      => 'customVariableName1==Not-bot',
                                            'idSite'          => false,
                                            'autoArchive'     => false,
                                            'enabledAllUsers' => true),

            'segmentPreArchived' => array('definition'=> self::SEGMENT_PRE_ARCHIVED,
                                                  'idSite'          => 1,
                                                  'autoArchive'     => true,
                                                  'enabledAllUsers' => true),

            'segmentPreArchivedWithUrlEncoding' => array('definition'=> self::SEGMENT_PRE_ARCHIVED_CONTAINS_ENCODED,
                                                  'idSite'          => 1,
                                                  'autoArchive'     => true,
                                                  'enabledAllUsers' => true)

            // fails randomly and I really could not find why.
//            'segmentOnlySuperuser' => array('definition'      => 'actions>1;customVariablePageName1=='.urlencode('HTTP-code'),
//                                            'idSite'          => false,
//                                            'autoArchive'     => true,
//                                            'enabledAllUsers' => false),
        );
    }

    private function trackVisits()
    {
        $this->logVisitsWithStaticResolver();
        $this->logVisitsWithAllEnabled();
        $this->replayLogFile();
        $this->logCustomFormat();

        if ($this->includeIisWithCustom) {
            $this->logIisWithCustomFormat();
        }

        if ($this->includeNetscaler) {
            $this->logNetscaler();
        }

        if ($this->includeCloudfront) {
            $this->logCloudfront();
        }

        if ($this->includeCloudfrontRtmp) {
            $this->logCloudfrontRtmp();
        }

        if ($this->includeNginxJson) {
            $this->logNginxJsonLog();
        }

        if ($this->includeApiCustomVarMapping) {
            $this->logIisWithCustomFormat($mapToCustom = true);
        }
    }

    private function setupSegments()
    {
        if (!$this->addSegments) {
            return;
        }

        if ($this->segments === null) {
            $this->segments = $this->getDefaultSegments();
        }

        foreach ($this->segments as $segmentName => $info) {
            $idSite = false;
            if (isset($info['idSite'])) {
                $idSite = $info['idSite'];
            }

            $autoArchive = true;
            if (isset($info['autoArchive'])) {
                $autoArchive = $info['autoArchive'];
            }

            $enabledAllUsers = true;
            if (isset($info['enabledAllUsers'])) {
                $enabledAllUsers = $info['enabledAllUsers'];
            }

            APISegmentEditor::getInstance()->add($segmentName, $info['definition'], $idSite, $autoArchive, $enabledAllUsers);
        }
    }

    /**
     * Logs a couple visits for Aug 9, Aug 10, Aug 11 of 2012, for site we create.
     */
    private function logVisitsWithStaticResolver()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs.log'; # log file

        // We do not pass the "--token_auth" parameter here to make sure import_logs.py finds the auth_token
        // automatically if needed
        $opts = array('--idsite'                    => $this->idSite,
                      '--enable-testmode'           => false,
                      '--recorders'                 => '1',
                      '--recorder-max-payload-size' => '1');

        self::executeLogImporter($logFile, $opts);
    }

    /**
     * Logs a couple visits for the site we created and two new sites that do not
     * exist yet. Visits are from Aug 12, 13 & 14 of 2012.
     */
    public function logVisitsWithDynamicResolver()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_dynamic.log'; # log file

        // We do not pass the "--token_auth" parameter here to make sure import_logs.py finds the auth_token
        // automatically if needed
        $opts = array('--add-sites-new-hosts'       => false,
                      '--enable-testmode'           => false,
                      '--recorders'                 => '1',
                      '--recorder-max-payload-size' => '1');
        self::executeLogImporter($logFile, $opts);
    }

    /**
     * Logs a couple visits for the site we created w/ all log importer options
     * enabled. Visits are for Aug 11 of 2012.
     */
    private function logVisitsWithAllEnabled()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_enable_all.log';

        $opts = array('--idsite'                    => $this->idSite,
                      '--token-auth'                => self::getTokenAuth(),
                      '--recorders'                 => '1',
                      '--recorder-max-payload-size' => '1',
                      '--enable-static'             => false,
                      '--enable-bots'               => false,
                      '--enable-http-errors'        => false,
                      '--enable-http-redirects'     => false,
                      '--enable-reverse-dns'        => false,
                      '--force-lowercase-path'      => false);

        self::executeLogImporter($logFile, $opts);
    }

    /**
     * Logs a couple visit using log entries that are tracking requests to a piwik.php file.
     * Adds two visits to idSite=1 and two to non-existant sites.
     */
    private function replayLogFile()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_replay.log';

        $opts = array('--login'                     => 'superUserLogin',
                      '--password'                  => 'superUserPass',
                      '--recorders'                 => '1',
                      '--recorder-max-payload-size' => '1',
                      '--replay-tracking'           => false);

        self::executeLogImporter($logFile, $opts);
    }

    /**
     * Imports a log file in custom format that contains generation time
     */
    private function logCustomFormat()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_custom.log';

        $opts = array('--idsite'           => $this->idSite,
                      '--token-auth'       => self::getTokenAuth(),
                      '--log-format-regex' => '(?P<ip>\S+) - - \[(?P<date>.*?) (?P<timezone>.*?)\] (?P<status>\S+) '
                          . '\"\S+ (?P<path>.*?) \S+\" (?P<generation_time_micro>\S+)');

        self::executeLogImporter($logFile, $opts);
    }

    private function logIisWithCustomFormat($mapToCustom = false)
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_custom_iis.log';

        $opts = array('--idsite'           => $this->idSite,
                      '--token-auth'       => self::getTokenAuth(),
                      '--w3c-map-field'    => array('date-local=date', 'time-local=time', 'cs(Host)=cs-host', 'TimeTakenMS=time-taken'),
                      '--enable-http-errors'        => false,
                      '--enable-http-redirects'     => false);

        if ($mapToCustom) {
            $opts['--regex-group-to-visit-cvar'] = 'userid=User Name';
            $opts['--regex-group-to-page-cvar'] = array(
                'generation_time_milli=Generation Time',
                'win32_status=Windows Status Code'
            );
            $opts['--ignore-groups'] = 'userid';
            $opts['--w3c-field-regex'] = 'sc-win32-status=(?P<win32_status>\S+)';
            $opts['--w3c-time-taken-milli'] = false;
        }

        self::executeLogImporter($logFile, $opts);
    }

    private function logNetscaler()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_netscaler.log';

        $opts = array('--idsite'                    => $this->idSite,
                      '--token-auth'                => self::getTokenAuth(),
                      '--w3c-map-field'             => array(),
                      '--enable-http-redirects'     => false);

        return self::executeLogImporter($logFile, $opts);
    }

    private function logCloudfront()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_cloudfront.log';

        $opts = array('--idsite'                    => $this->idSite,
                      '--token-auth'                => self::getTokenAuth());

        return self::executeLogImporter($logFile, $opts);
    }

    private function logCloudfrontRtmp()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_cloudfront_rtmp.log';

        $opts = array('--idsite'                    => $this->idSite,
                      '--token-auth'                => self::getTokenAuth());

        return self::executeLogImporter($logFile, $opts);
    }

    private function logNginxJsonLog()
    {
        $logFile = PIWIK_INCLUDE_PATH . '/tests/resources/access-logs/fake_logs_nginx_json.log';

        $opts = array('--token-auth' => self::getTokenAuth());

        return self::executeLogImporter($logFile, $opts);
    }
}