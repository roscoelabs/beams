<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Framework\Mock;

use Piwik\Plugins\UserCountry\LocationProvider as CountryLocationProvider;

/**
 * @since 2.8.0
 */
class LocationProvider extends CountryLocationProvider
{
    const ID = 'mock_provider';

    public static $locations = array();
    private $currentLocation = 0;
    private $ipToLocations   = array();

    public function getLocation($info)
    {
        $ip = $info['ip'];

        if (isset($this->ipToLocations[$ip])) {
            $result = $this->ipToLocations[$ip];
        } else {
            $result = self::$locations[$this->currentLocation];
            $this->currentLocation = ($this->currentLocation + 1) % count(self::$locations);

            $this->ipToLocations[$ip] = $result;
        }

        $this->completeLocationResult($result);

        return $result;
    }

    public function getInfo()
    {
        return array('id' => self::ID, 'title' => 'mock provider', 'description' => 'mock provider', 'order' => 10);
    }

    public function isAvailable()
    {
        return true;
    }

    public function isWorking()
    {
        return true;
    }

    public function getSupportedLocationInfo()
    {
        return array(); // unimplemented
    }
}
