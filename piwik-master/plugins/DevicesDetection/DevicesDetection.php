<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\DevicesDetection;

use Piwik\ArchiveProcessor;
use Piwik\Db;
use Piwik\Piwik;

require_once PIWIK_INCLUDE_PATH . '/plugins/DevicesDetection/functions.php';

class DevicesDetection extends \Piwik\Plugin
{
    /**
     * @see Piwik\Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        return array(
            'Live.getAllVisitorDetails' => 'extendVisitorDetails'
        );
    }

    public function extendVisitorDetails(&$visitor, $details)
    {
        $instance = new Visitor($details);

        $visitor['deviceType']               = $instance->getDeviceType();
        $visitor['deviceTypeIcon']           = $instance->getDeviceTypeIcon();
        $visitor['deviceBrand']              = $instance->getDeviceBrand();
        $visitor['deviceModel']              = $instance->getDeviceModel();
        $visitor['operatingSystem']          = $instance->getOperatingSystem();
        $visitor['operatingSystemName']      = $instance->getOperatingSystemName();
        $visitor['operatingSystemIcon']      = $instance->getOperatingSystemIcon();
        $visitor['operatingSystemCode']      = $instance->getOperatingSystemCode();
        $visitor['operatingSystemVersion']   = $instance->getOperatingSystemVersion();
        $visitor['browserFamily']            = $instance->getBrowserEngine();
        $visitor['browserFamilyDescription'] = $instance->getBrowserEngineDescription();
        $visitor['browser']                  = $instance->getBrowser();
        $visitor['browserName']              = $instance->getBrowserName();
        $visitor['browserIcon']              = $instance->getBrowserIcon();
        $visitor['browserCode']              = $instance->getBrowserCode();
        $visitor['browserVersion']           = $instance->getBrowserVersion();
    }

}
