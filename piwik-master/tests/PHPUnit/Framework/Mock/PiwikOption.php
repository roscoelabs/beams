<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\Framework\Mock;

use Piwik\Option;

/**
 * @since 2.8.0
 */
class PiwikOption extends Option
{
    private $forcedOptionValue = false;

    function __construct($forcedOptionValue)
    {
        $this->forcedOptionValue = $forcedOptionValue;
    }

    protected function getValue($name)
    {
        return $this->forcedOptionValue;
    }

    protected function setValue($name, $value, $autoLoad = 0)
    {
        $this->forcedOptionValue = $value;
    }
}
