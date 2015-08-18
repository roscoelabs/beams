# Piwik Platform Changelog

This is a changelog for Piwik platform developers. All changes for our HTTP API's, Plugins, Themes, etc will be listed here.

## Piwik 2.15.0

### Breaking Changes
* The method `Dimension::getId()` has been set as `final`. It is not allowed to overwrite this method.

## Piwik 2.14.0

### Breaking Changes
* The `UserSettings` API has been removed. The API was deprecated in earlier versions. Use `DevicesDetection`, `Resolution` and `DevicePlugins` API instead.
* Many translations have been moved to the new Intl plugin. Most of them will still work, but please update their usage. See https://github.com/piwik/piwik/pull/8101 for a full list 

### New features 
* The JavaScript Tracker does now track outlinks and downloads if a user opens the context menu if the `enabled` parameter of the `enableLinkTracking()` method is set to `true`. To use this new feature use `tracker.enableLinkTracking(true)` or `_paq.push(['enableLinkTracking', true]);`. This is not industry standard and is vulnerable to false positives since not every user will select "Open in a new tab" when the context menu is shown. Most users will do though and it will lead to more accurate results in most cases.
* The JavaScript Tracker now contains the 'heart beat' feature which can be used to obtain more accurate visit lengths by periodically sending 'ping' requests to Piwik. To use this feature use `tracker.enableHeartBeatTimer();` or `_paq.push(['enableHeartBeatTimer']);`. By default, a ping request will be sent every 15 seconds. You can specify a custom ping delay (in seconds) by passing an argument, eg, `tracker.enableHeartBeatTimer(10);` or `_paq.push(['enableHeartBeatTimer', 10]);`.
* New custom segment `languageCode` that lets you segment visitors that are using a particular language. Example values: `de`, `fr`, `en-gb`, `zh-cn`, etc.
* Segment `userId` now supports any segment operator (previously only operator Contains `=@` was supported for this segment).

### Commands updates
* The command `core:archive` now has two new parameter: `--force-idsegments` and `--skip-idsegments` that let you force (or skip) processing archives for one or several custom segments.
* The command `scheduled-tasks:run` now has an argument `task` that lets you force run a particular scheduled task.

### Library updates
* Updated pChart library from 2.1.3 to 2.1.4. The files were moved from the directory `libs/pChart2.1.3` to `libs/pChart`

### Internal change
* To execute UI tests "ImageMagick" is now required.
* The Q JavaScript promise library is now distributed with tests and can be used in the piwik.js tests.

## Piwik 2.13.0

### Breaking Changes
* The API method `Live.getLastVisitsDetails` does no longer support the API parameter `filter_sort_column` to prevent possible memory issues when `filter_offset` is large.
* The Event `Site.setSite` was removed as it causes performance problems.
* `piwik.php` does now return a HTTP 400 (Bad request) if requested without any tracking parameters (GET/POST). If you still want to use `piwik.php` for checks please use `piwik.php?rec=0`.

### Deprecations
* The method `Piwik\Archive::getBlob()` has been deprecated and will be removed from June 1st 2015. Use one of the methods `getDataTable*()` methods instead.
* The API parameter `countVisitorsToFetch` of the API method `Live.getLastVisitsDetails` has been deprecated as `filter_offset` and `filter_limit` work correctly now.

### New commands
* There is now a `diagnostic:run` command to run the system check from the command line.
* There is now an option `--xhprof` that can be used with any command to profile that command via XHProf.

### APIs Improvements
* Visitor details now additionally contain: `deviceTypeIcon`, `deviceBrand` and `deviceModel`
* In 2.6.0 we added the possibility to use `filter_limit` and `filter_offset` if an API returns an indexed array. This was not working in all cases and is fixed now. 
* The API parameter `filter_pattern` and `filter_offset[]` can now be used if an API returns an indexed array.

### Internal changes

* The referrer spam filter has moved from the `referrer_urls_spam` INI option (in `global.ini.php`) to a separate package (see [https://github.com/piwik/referrer-spam-blacklist](https://github.com/piwik/referrer-spam-blacklist)).

## Piwik 2.12.0

### Breaking Changes
* The deprecated method `Period::factory()` has been removed. Use `Period\Factory` instead.
* The deprecated method `Config::getConfigSuperUserForBackwardCompatibility()` has been removed.
* The deprecated methods `MenuAdmin::addEntry()` and `MenuAdmin::removeEntry()` have been removed. Use `Piwik\Plugin\Menu` instead.
* The deprecated methods `MenuTop::addEntry()` and `MenuTop::removeEntry()` have been removed. Use `Piwik\Plugin\Menu` instead.
* The deprecated method `SettingsPiwik::rewriteTmpPathWithInstanceId()` has been removed.
* The following deprecated methods from the `Piwik\IP` class have been removed, use `Piwik\Network\IP` instead:
  * `sanitizeIp()`
  * `sanitizeIpRange()`
  * `P2N()`
  * `N2P()`
  * `prettyPrint()`
  * `isIPv4()`
  * `long2ip()`
  * `isIPv6()`
  * `isMappedIPv4()`
  * `getIPv4FromMappedIPv6()`
  * `getIpsForRange()`
  * `isIpInRange()`
  * `getHostByAddr()`

### Deprecations
* `API` classes should no longer have a protected constructor. Classes with a protected constructor will generate a notice in the logs and should expose a public constructor instead.
* Update classes should not declare static `getSql()` and `update()` methods anymore. It is still supported to use those, but developers should instead override the `Updates::getMigrationQueries()` and `Updates::doUpdate()` instance methods.

### New features
* `API` classes can now use dependency injection in their constructor to inject other instances.

### New commands
* There is now a command `core:purge-old-archive-data` that can be used to manually purge temporary, error-ed and invalidated archives from one or more archive tables.
* There is now a command `usercountry:attribute` that can be used to re-attribute geolocated location data to existing visits and conversions. If you have visits that were tracked before setting up GeoIP, you can use this command to add location data to them.

## Piwik 2.11.0

### Breaking Changes
* The event `User.getLanguage` has been removed.
* The following deprecated event has been removed: `TaskScheduler.getScheduledTasks`
* Special handling for operating system `Windows` has been removed. Like other operating systems all versions will now only be reported as `Windows` with versions like `XP`, `7`, `8`, etc.
* Reporting for operating systems has been adjusted to report information according to browser information. Visitor details now contain: `operatingSystemName`, `operatingSystemIcon`, `operatingSystemCode` and `operatingSystemVersion`

### Deprecations
* The following methods have been deprecated in favor of the new `Piwik\Intl` component:
  * `Piwik\Common::getContinentsList()`: use `RegionDataProvider::getContinentList()` instead
  * `Piwik\Common::getCountriesList()`: use `RegionDataProvider::getCountryList()` instead
  * `Piwik\Common::getLanguagesList()`: use `LanguageDataProvider::getLanguageList()` instead
  * `Piwik\Common::getLanguageToCountryList()`: use `LanguageDataProvider::getLanguageToCountryList()` instead
  * `Piwik\Metrics\Formatter::getCurrencyList()`: use `CurrencyDataProvider::getCurrencyList()` instead
* The `Piwik\Translate` class has been deprecated in favor of `Piwik\Translation\Translator`.
* The `core:plugin` console has been deprecated in favor of the new `plugin:list`, `plugin:activate` and `plugin:deactivate` commands
* The following classes have been deprecated:
  * `Piwik\TaskScheduler`: use `Piwik\Scheduler\Scheduler` instead
  * `Piwik\ScheduledTask`: use `Piwik\Scheduler\Task` instead
* The API method `UserSettings.getLanguage` is deprecated and will be removed from May 1st 2015. Use `UserLanguage.getLanguage` instead
* The API method `UserSettings.getLanguageCode` is deprecated and will be removed from May 1st 2015. Use `UserLanguage.getLanguageCode` instead
* The `Piwik\Registry` class has been deprecated in favor of using the container:
  * `Registry::get('auth')` should be replaced with `StaticContainer::get('Piwik\Auth')`
  * `Registry::set('auth', $auth)` should be replaced with `StaticContainer::getContainer()->set('Piwik\Auth', $auth)`
 
### New features
* You can now generate UI / screenshot tests using the command `generate:test`
* During UI tests we do now add a CSS class to the HTML element called `uiTest`. This allows you do hide content when screenshots are captured.

### New commands
* A new command (core:fix-duplicate-log-actions) has been added which can be used to remove duplicate actions and correct references to them in other tables. Duplicates were caused by this bug: [#6436](https://github.com/piwik/piwik/issues/6436)

### Library updates
* Updated AngularJS from 1.2.26 to 1.2.28
* Updated piwik/device-detector from 2.8 to 3.0

### Internal change
* UI specs were moved from `tests/PHPUnit/UI` to `tests/UI`. We also moved the UI specs directly into the Piwik repository meaning the [piwik-ui-tests](https://github.com/piwik/piwik-ui-tests) repository contains only the expected screenshots from now on.
* There is a new command `development:sync-system-test-processed` for core developers that allows you to copy processed test results from travis to your local dev environment.

## Piwik 2.10.0

### Breaking Changes
* API responses containing visitor information will no longer contain the fields `screenType` and `screenTypeIcon` as those reports have been completely removed
* os, browser and browser plugin icons are now located in the DevicesDetection and DevicePlugins plugin. If you are not using the Reporting or Metadata API to get the icon locations please update your paths.
* The deprecated method `Piwik\SettingsPiwik::rewriteTmpPathWithHostname()` has been removed.
* The following events have been removed:
  * `Log.formatFileMessage`
  * `Log.formatDatabaseMessage`
  * `Log.formatScreenMessage`
  * These events have been removed as Piwik now uses the Monolog logging library. [Learn more.](http://developer.piwik.org/guides/logging)
* The event `Log.getAvailableWriters` has been removed: to add custom log backends, you now need to configure Monolog handlers
* The INI options `log_only_when_cli` and `log_only_when_debug_parameter` have been removed

### Library updates
* We added the `symfony/var-dumper` library allowing you to better print any arbitrary PHP variable via `dump($var1, $var2, ...)`.
* Piwik now uses [Monolog](https://github.com/Seldaek/monolog) as a logger.
* The tracker proxy (previously in `misc/proxy-hide-piwik-url/`) has been moved to a separate repository: [https://github.com/piwik/tracker-proxy](https://github.com/piwik/tracker-proxy).

### Deprecations
* Some duplicate reports from UserSettings plugin have been removed. Widget URLs for those reports will still work till May 1st 2015. Please update those to the new reports of DevicesDetection plugin.
* The API method `UserSettings.getBrowserVersion` is deprecated and will be removed from May 1st 2015. Use `DevicesDetection.getBrowserVersions` instead
* The API method `UserSettings.getBrowser` is deprecated and will be removed from May 1st 2015. Use `DevicesDetection.getBrowsers` instead
* The API method `UserSettings.getOSFamily` is deprecated and will be removed from May 1st 2015. Use `DevicesDetection.getOsFamilies` instead
* The API method `UserSettings.getOS` is deprecated and will be removed from May 1st 2015. Use `DevicesDetection.getOsVersions` instead
* The API method `UserSettings.getMobileVsDesktop` is deprecated and will be removed from May 1st 2015. Use `DevicesDetection.getType` instead
* The API method `UserSettings.getBrowserType` is deprecated and will be removed from May 1st 2015. Use `DevicesDetection.getBrowserEngines` instead
* The API method `UserSettings.getResolution` is deprecated and will be removed from May 1st 2015. Use `Resolution.getResolution` instead
* The API method `UserSettings.getConfiguration` is deprecated and will be removed from May 1st 2015. Use `Resolution.getConfiguration` instead
* The API method `UserSettings.getPlugin` is deprecated and will be removed from May 1st 2015. Use `DevicePlugins.getPlugin` instead
* The API method `UserSettings.getWideScreen` has been removed. Use `UserSettings.getScreenType` instead.
* `Piwik\SettingsPiwik::rewriteTmpPathWithInstanceId()` has been deprecated. Instead of hardcoding the `tmp/` path everywhere in the codebase and then calling `rewriteTmpPathWithInstanceId()`, developers should get the `path.tmp` configuration value from the DI container (e.g. `StaticContainer::getContainer()->get('path.tmp')`).
* The method `Piwik\Log::setLogLevel()` has been deprecated
* The method `Piwik\Log::getLogLevel()` has been deprecated

## Piwik 2.9.1

### Breaking Changes
* The HTTP Tracker API does now respond with a HTTP 400 instead of a HTTP 500 in case an invalid `idsite` is used

### New APIs
* New URL parameter `send_image=0` in the [HTTP Tracking API](http://developer.piwik.org/api-reference/tracking-api) to receive a HTTP 204 response code instead of a GIF image. This improves performance and can fix errors if images are not allowed to be obtained directly (eg Chrome Apps).

### New commands
* `core:plugin list` lists all plugins currently activated in Piwik.

## Piwik 2.9.0

### Breaking Changes
* Development related [console commands](http://developer.piwik.org/guides/piwik-on-the-command-line) are only available if the development mode is enabled. To enable the development mode execute `./console development:enable`.
* The command `php console core:update` does no longer have a parameter `--dry-run`. A dry run is now executed by default followed by a question whether one actually wants to execute the updates. To skip this confirmation step one can use the `--yes` option.

### Deprecations
* Most methods of `Piwik\IP` have been deprecated in favor of the new [piwik/network](https://github.com/piwik/component-network) component.
* The file `tests/PHPUnit/phpunit.xml` is no longer needed in order to run tests and we suggest to delete it. The test configuration is now done automatically if possible. In case the tests do no longer work check out the `[tests]` section in `config/global.ini.php`

### Library updates
* Code for manipulating IP addresses has been moved to a separate standalone component: [piwik/network](https://github.com/piwik/component-network). Backward compatibility is kept in Piwik core.

## Piwik 2.8.2

### Library updates
* Updated AngularJS from 1.2.25 to 1.2.26
* Updated jQuery from 1.11.0 to 1.11.1

## Piwik 2.8.0

### Breaking Changes
* The Auth interface has been modified, existing Auth implementations will have to be modified. Changes include:
  * The initSession method has been moved. Since this behavior must be executed for every Auth implementation, it has been put into a new class: SessionInitializer.
    If your Auth implementation implements its own session logic you will have to extend and override SessionInitializer.
  * The following methods have been added: setPassword, setPasswordHash, getTokenAuthSecret and getLogin.
  * Clarifying semantics of each method and what they must support and can support.
  * **Read the documentation for the [Auth interface](http://developer.piwik.org/api-reference/Piwik/Auth) to learn more.**
* The `Piwik\Unzip\*` classes have been extracted out of the Piwik repository into a separate component named [Decompress](https://github.com/piwik/component-decompress).
  * `Piwik\Unzip` has not moved, it is kept for backward compatibility. If you have been using that class, you don't need to change anything.
  * The `Piwik\Unzip\*` classes (Tar, PclZip, Gzip, ZipArchive) have moved to the `Piwik\Decompress\*` namespace (inside the new repository).
  * `Piwik\Unzip\UncompressInterface` has been moved and renamed to `Piwik\Decompress\DecompressInterface` (inside the new repository).

### Deprecations
* The `Piwik::setUserHasSuperUserAccess` method is deprecated, instead use Access::doAsSuperUser. This method will ensure that super user access is properly rescinded after the callback finishes.
* The class `\IntegrationTestCase` is deprecated and will be removed from February 6th 2015. Use `\Piwik\Tests\Framework\TestCase\SystemTestCase` instead.
* The class `\DatabaseTestCase` is deprecated and will be removed from February 6th 2015. Use `\Piwik\Tests\Framework\TestCase\IntegrationTestCase` instead.
* The class `\BenchmarkTestCase` is deprecated and will be removed from February 6th 2015. Use `\Piwik\Tests\Framework\TestCase\BenchmarkTestCase` instead.
* The class `\ConsoleCommandTestCase` is deprecated and will be removed from February 6th 2015. Use `\Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase` instead.
* The class `\FakeAccess` is deprecated and will be removed from February 6th 2015. Use `\Piwik\Tests\Framework\Mock\FakeAccess` instead.
* The class `\Piwik\Tests\Fixture` is deprecated and will be removed from February 6th 2015. Use `\Piwik\Tests\Framework\Fixture` instead.
* The class `\Piwik\Tests\OverrideLogin` is deprecated and will be removed from February 6ths 2015. Use `\Piwik\Framework\Framework\OverrideLogin` instead.

### New API Features
* The pivotBy and related query parameters can be used to pivot reports by another dimension. Read more about the new query parameters [here](http://developer.piwik.org/api-reference/reporting-api#optional-api-parameters).

### Library updates
* Updated AngularJS from 1.2.13 to 1.2.25

### New commands
* `generate:angular-directive` Let's you easily generate a template for a new angular directive for any plugin.

### Internal change
* Piwik 2.8.0 now requires PHP >= 5.3.3. 
 * If you use an older PHP version, please upgrade now to the latest PHP so you can enjoy improvements and security fixes in Piwik. 

## Piwik 2.7.0

### Reporting APIs
* Several APIs will now expose a new metric `nb_users` which measures the number of unique users when a [User ID](http://piwik.org/docs/user-id/) is set.
* New APIs have been added for [Content Tracking](http://piwik.org/docs/content-tracking/) feature: Contents.getContentNames, Contents.getContentPieces

### Deprecations
* The `Piwik\Menu\MenuAbstract::add()` method is deprecated in favor of `addItem()`. Read more about this here: [#6140](https://github.com/piwik/piwik/issues/6140). We do not plan to remove the deprecated method before Piwik 3.0.

### New APIs
* It is now easier to generate the URL for a menu item see [#6140](https://github.com/piwik/piwik/issues/6140), [urlForDefaultAction()](http://developer.piwik.org/api-reference/Piwik/Plugin/Menu#urlfordefaultaction), [urlForAction()](http://developer.piwik.org/api-reference/Piwik/Plugin/Menu#urlforaction), [urlForModuleAction()](http://developer.piwik.org/api-reference/Piwik/Plugin/Menu#urlformoduleaction)

### New commands
* `core:clear-caches` Lets you easily delete all caches. This command can be useful for instance after updating Piwik files manually.


## Piwik 2.6.0

### Deprecations
* The `'json'` API format is considered deprecated. We ask all new code to use the `'json2'` format. Eventually when Piwik 3.0 is released the `'json'` format will be replaced with `'json2'`. Differences in the json2 format include:
  * A bug in JSON formatting was fixed so API methods that return simple associative arrays like `array('name' => 'value', 'name2' => 'value2')` will now appear correctly as `{"name":"value","name2":"value2"}` in JSON API output instead of `[{"name":"value","name2":"value2"}]`. API methods like **SitesManager.getSiteFromId** & **UsersManager.getUser** are affected.

#### Reporting API
* If an API returns an indexed array, it is now possible to use `filter_limit` and `filter_offset`. This was before only possible if an API returned a DataTable.
* The Live API now returns only visitor information of activated plugins. So if for instance the Referrers plugin is deactivated a visitor won't contain any referrers related properties. This is a bugfix as the API was crashing before if some core plugins were deactivated. Affected methods are for instance `getLastVisitDetails` or `getVisitorProfile`. If all core plugins are enabled as by default there will be no change at all except the order of the properties within one visitor.

### New commands
* `core:run-scheduled-tasks` Let's you run all scheduled tasks due to run at this time. Useful for instance when testing tasks.

#### Internal change
 * We removed our own autoloader that was used to load Piwik files in favor of the composer autoloader which we already have been using for some libraries. This means the file `core/Loader.php` will no longer exist. In case you are using Piwik from Git make sure to run `php composer.phar self-update && php composer.phar install` to make your Piwik work again. Also make sure to no longer include `core/Loader.php` in case it is used in any custom script.
 * We do no longer store the list of plugins that are used during tracking in the config file. They are dynamically detect instead. The detection of a tracker plugin works the same as before. A plugin has to either listen to any `Tracker.*` or `Request.initAuthenticationObject` event or it has to define dimensions in order to be detected as a tracker plugin.

## Piwik 2.5.0

### Breaking Changes
* Javascript Tracking API: if you are using `getCustomVariable` function to access custom variables values that were set on previous page views, you now must also call `storeCustomVariablesInCookie` before the first call to `trackPageView`. Read more about [Javascript Tracking here](http://developer.piwik.org/api-reference/tracking-javascript).
* The [settings](http://developer.piwik.org/guides/piwik-configuration) API will receive the actual entered value and will no longer convert characters like `&` to `&amp;`. If you still want this behavior - for instance to prevent XSS - you can define a filter by setting the `transform` property like this:
  `$setting->transform = function ($value) { return Common::sanitizeInputValue($value); }`
* Config setting `disable_merged_assets` moved from `Debug` section to `Development`. The updater will automatically change the section for you.
* `API.getRowEvolution` will throw an exception if a report is requested that does not have a dimension, for instance `VisitsSummary.get`. This is a fix as an invalid format was returned before see [#5951](https://github.com/piwik/piwik/issues/5951)
* `MultiSites.getAll` returns from now on always an array of websites. In the past it returned a single object and it didn't contain all properties in case only one website was found which was a bug see [#5987](https://github.com/piwik/piwik/issues/5987)

### Deprecations
The following events are considered as deprecated and the new structure should be used in the future. We have not scheduled when those events will be removed but probably in Piwik 3.0 which is not scheduled yet and won't be soon. New features will be added only to the new classes.

* `API.getReportMetadata`, `API.getSegmentDimensionMetadata`, `Goals.getReportsWithGoalMetrics`, `ViewDataTable.configure`, `ViewDataTable.getDefaultType`: use [Report](http://developer.piwik.org/api-reference/Piwik/Plugin/Report) class instead to define new reports. There is an updated guide as well [Part1](http://developer.piwik.org/guides/getting-started-part-1)
* `WidgetsList.addWidgets`: use [Widgets](http://developer.piwik.org/api-reference/Piwik/Plugin/Widgets) class instead to define new widgets
* `Menu.Admin.addItems`, `Menu.Reporting.addItems`, `Menu.Top.addItems`: use [Menu](http://developer.piwik.org/api-reference/Piwik/Plugin/Menu) class instead
* `TaskScheduler.getScheduledTasks`: use [Tasks](http://developer.piwik.org/api-reference/Piwik/Plugin/Tasks) class instead to define new tasks
* `Tracker.recordEcommerceGoal`, `Tracker.recordStandardGoals`, `Tracker.newConversionInformation`: use [Conversion Dimension](http://developer.piwik.org/api-reference/Piwik/Plugin/Dimension/ConversionDimension) class instead
* `Tracker.existingVisitInformation`, `Tracker.newVisitorInformation`, `Tracker.getVisitFieldsToPersist`: use [Visit Dimension](http://developer.piwik.org/api-reference/Piwik/Plugin/Dimension/VisitDimension) class instead
* `ViewDataTable.addViewDataTable`: This event is no longer needed. Visualizations are automatically discovered if they are placed within a `Visualizations` directory inside the plugin.

### New features

#### Translation search
As a plugin developer you might want to reuse existing translation keys. You can now find all available translations and translation keys by opening the page "Settings => Development:Translation search" in your Piwik installation. Read more about [internationalization](http://developer.piwik.org/guides/internationalization) here.

#### Reporting API
It is now possible to use the `filter_sort_column` parameter when requesting `Live.getLastVisitDetails`. For instance `&filter_sort_column=visitCount`. 

#### @since annotation
We are using `@since` annotations in case we are introducing new API's to make it easy to see in which Piwik version a new method was added. This information is now displayed in the [Classes API-Reference](http://developer.piwik.org/api-reference/classes). 

### New APIs
* [Report](http://developer.piwik.org/api-reference/Piwik/Plugin/Report) to add a new report
* [Action Dimension](http://developer.piwik.org/api-reference/Piwik/Plugin/Dimension/ActionDimension) to add a dimension that tracks action related information
* [Visit Dimension](http://developer.piwik.org/api-reference/Piwik/Plugin/Dimension/VisitDimension) to add a dimension that tracks visit related information
* [Conversion Dimension](http://developer.piwik.org/api-reference/Piwik/Plugin/Dimension/ConversionDimension) to add a dimension that tracks conversion related information
* [Dimension](http://developer.piwik.org/api-reference/Piwik/Columns/Dimension) to add a basic non tracking dimension that can be used in `Reports`
* [Widgets](http://developer.piwik.org/api-reference/Piwik/Plugin/Widgets) to add or modfiy widgets
* These Menu classes got new methods that make it easier to add new items to a specific section
  * [MenuAdmin](http://developer.piwik.org/api-reference/Piwik/Menu/MenuAdmin) to add or modify admin menu items. 
  * [MenuReporting](http://developer.piwik.org/api-reference/Piwik/Menu/MenuReporting) to add or modify reporting menu items
  * [MenuUser](http://developer.piwik.org/api-reference/Piwik/Menu/MenuUser) to add or modify user menu items
* [Tasks](http://developer.piwik.org/api-reference/Piwik/Plugin/Tasks) to add scheduled tasks

### New commands
* `generate:theme` Let's you easily generate a new theme and customize colors, see the [Theming guide](http://developer.piwik.org/guides/theming)
* `generate:update` Let's you generate an update file
* `generate:report` Let's you generate a report
* `generate:dimension` Let's you enhance the tracking by adding new dimensions
* `generate:menu` Let's you generate a menu class to add or modify menu items
* `generate:widgets` Let's you generate a widgets class to add or modify widgets
* `generate:tasks` Let's you generate a tasks class to add or modify tasks
* `development:enable` Let's you enable the development mode which will will disable some caching to make code changes directly visible and it will assist developers by performing additional checks to prevent for instance typos. Should not be used in production.
* `development:disable` Let's you disable the development mode 

<!--
## Template: Piwik version number

### Breaking Changes
### Deprecations
### New features
### New APIs
### New commands
### New guides
### Library updates
### Internal change
 -->

Find the general Piwik Changelogs for each release at [piwik.org/changelog](http://piwik.org/changelog/)
 
