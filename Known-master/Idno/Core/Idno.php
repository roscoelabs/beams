<?php

    /**
     * Base Idno class
     *
     * @package idno
     * @subpackage core
     */

    namespace Idno\Core {

        use Idno\Common\Page;
        use Idno\Entities\User;

        class Idno extends \Idno\Common\Component
        {

            public $db;
            public $filesystem;
            public $config;
            public $session;
            public $template;
            public $actions;
            public $plugins;
            public $dispatcher;
            public $pagehandlers;
            public $public_pages;
            public $syndication;
            public $logging;
            public static $site;
            public $currentPage;
            public $known_hub;
            public $helper_robot;
            public $reader;
            public $cache;

            function init()
            {
                self::$site       = $this;
                $this->dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
                $this->config     = new Config();
                if ($this->config->isDefaultConfig()) {
                    header('Location: ./warmup/');
                    exit; // Load the installer
                }
                switch ($this->config->database) {
                    case 'mongodb':
                        $this->db = new DataConcierge();
                        break;
                    case 'mysql':
                        $this->db = new \Idno\Data\MySQL();
                        break;
                    case 'beanstalk-mysql': // A special instance of MYSQL designed for use with Amazon Elastic Beanstalk
                        $this->config->dbhost = $_SERVER['RDS_HOSTNAME'];
                        $this->config->dbuser = $_SERVER['RDS_USERNAME'];
                        $this->config->dbpass = $_SERVER['RDS_PASSWORD'];
                        $this->config->dbport = $_SERVER['RDS_PORT'];
                        if (empty($this->config->dbname)) {
                            $this->config->dbname = $_SERVER['RDS_DB_NAME'];
                        }
                        $this->db = new \Idno\Data\MySQL();
                        break;
                    default:
                        if (class_exists("Idno\\Data\\{$this->config->database}")) {
                            $db       = "Idno\\Data\\{$this->config->database}";
                            $this->db = new $db();
                        }
                        if (empty($this->db)) {
                            $this->db = new DataConcierge();
                        }
                        break;
                }

                switch ($this->config->filesystem) {
                    case 'local':
                        $this->filesystem = new \Idno\Files\LocalFileSystem();
                        break;
                    default:
                        if (class_exists("Idno\\Files\\{$this->config->filesystem}")) {
                            $filesystem       = "Idno\\Files\\{$this->config->filesystem}";
                            $this->filesystem = new $filesystem();
                        }
                        if (empty($this->filesystem)) {
                            if ($fs = $this->db()->getFilesystem()) {
                                $this->filesystem = $fs;
                            }
                        }
                        break;
                }
                $this->config->load();
                $this->session      = new Session();
                $this->actions      = new Actions();
                $this->template     = new Template();
                $this->syndication  = new Syndication();
                $this->logging      = new Logging($this->config->log_level);
                $this->reader       = new Reader();
                $this->helper_robot = new HelperRobot();
                
                // Attempt to create a cache object, making use of support present on the system
                if (extension_loaded('xcache')) {
                    $this->cache = new \Idno\Caching\XCache();
                }
                // TODO: Support other persistent caching methods

                // No URL is a critical error, default base fallback is now a warning (Refs #526)
                if (!$this->config->url) throw new \Exception('Known was unable to work out your base URL! You might try setting url="http://yourdomain.com/" in your config.ini');
                if ($this->config->url == '/') \Idno\Core\site()->logging->log('Base URL has defaulted to "/" because Known was unable to detect your server name. '
                    . 'This may be because you\'re loading Known via a script. '
                    . 'Try setting url="http://yourdomain.com/" in your config.ini to remove this message', LOGLEVEL_WARNING);

                // Connect to a Known hub if one is listed in the configuration file
                // (and this isn't the hub!)
                if (empty(site()->session()->hub_connect)) {
                    site()->session()->hub_connect = 0;
                }
                if (
                    !empty($this->config->known_hub) &&
                    !substr_count($_SERVER['REQUEST_URI'], '.') &&
                    $this->config->known_hub != $this->config->url
                ) {
                    site()->session()->hub_connect = time();
                    \Idno\Core\site()->known_hub   = new \Idno\Core\Hub($this->config->known_hub);
                    \Idno\Core\site()->known_hub->connect();
                }

                site()->session()->APIlogin();
                User::registerEvents();
                site()->session()->refreshCurrentSessionuser();
            }

            /**
             * Registers some core page URLs
             */
            function registerPages()
            {

                /** Homepage */
                $this->addPageHandler('', '\Idno\Pages\Homepage');
                $this->addPageHandler('/', '\Idno\Pages\Homepage');
                $this->addPageHandler('/content/([A-Za-z\-\/]+)+', '\Idno\Pages\Homepage');

                /** Individual entities / posting / deletion */
                $this->addPageHandler('/view/([\%A-Za-z0-9]+)/?', '\Idno\Pages\Entity\View');
                $this->addPageHandler('/s/([\%A-Za-z0-9]+)/?', '\Idno\Pages\Entity\Shortlink');
                $this->addPageHandler('/[0-9]+/([\%A-Za-z0-9\-\_]+)/?', '\Idno\Pages\Entity\View');
                $this->addPageHandler('/edit/([A-Za-z0-9]+)/?', '\Idno\Pages\Entity\Edit');
                $this->addPageHandler('/delete/([A-Za-z0-9]+)/?', '\Idno\Pages\Entity\Delete');
                $this->addPageHandler('/withdraw/([A-Za-z0-9]+)/?', '\Idno\Pages\Entity\Withdraw');

                /** Annotations */
                $this->addPageHandler('/view/([A-Za-z0-9]+)/annotations/([A-Za-z0-9]+)?', '\Idno\Pages\Annotation\View');
                $this->addPageHandler('/[0-9]+/([\%A-Za-z0-9\-\_]+)/annotations/([A-Za-z0-9]+)?', '\Idno\Pages\Annotation\View');
                $this->addPageHandler('/[0-9]+/([\%\A-Za-z0-9\-\_]+)/annotations/([A-Za-z0-9]+)/delete/?', '\Idno\Pages\Annotation\Delete'); // Delete annotation
                $this->addPageHandler('/annotation/post/?', '\Idno\Pages\Annotation\Post');

                /** Bookmarklets and sharing */
                $this->addPageHandler('/share/?', '\Idno\Pages\Entity\Share');
                $this->addPageHandler('/bookmarklet\.js', '\Idno\Pages\Entity\Bookmarklet', true);

                /** Mobile integrations */
                $this->addPageHandler('/chrome/manifest\.json', '\Idno\Pages\Chrome\Manifest', true);

                /** Files */
                $this->addPageHandler('/file/upload/?', '\Idno\Pages\File\Upload', true);
                $this->addPageHandler('/file/picker/?', '\Idno\Pages\File\Picker', true);
                $this->addPageHandler('/filepicker/?', '\Idno\Pages\File\Picker', true);
                $this->addPageHandler('/file/([A-Za-z0-9]+)(/.*)?', '\Idno\Pages\File\View', true);

                /** Users */
                $this->addPageHandler('/profile/([^\/]+)/?', '\Idno\Pages\User\View');
                $this->addPageHandler('/profile/([^\/]+)/edit/?', '\Idno\Pages\User\Edit');

                /** Search */
                $this->addPageHandler('/search/?', '\Idno\Pages\Search\Forward');
                $this->addPageHandler('/search/mentions\.json', '\Idno\Pages\Search\Mentions');
                $this->addPageHandler('/tag/([^\s]+)\/?', '\Idno\Pages\Search\Tags');

                /** robots.txt */
                $this->addPageHandler('/robots\.txt', '\Idno\Pages\Txt\Robots');

                /** Autosave / preview */
                $this->addPageHandler('/autosave/?', '\Idno\Pages\Entity\Autosave');

                /** Installation / first use */
                $this->addPageHandler('/begin/?', '\Idno\Pages\Onboarding\Begin', true);
                $this->addPageHandler('/begin/register/?', '\Idno\Pages\Onboarding\Register', true);
                $this->addPageHandler('/begin/profile/?', '\Idno\Pages\Onboarding\Profile');
                $this->addPageHandler('/begin/connect/?', '\Idno\Pages\Onboarding\Connect');
                $this->addPageHandler('/begin/connect\-forwarder/?', '\Idno\Pages\Onboarding\ConnectForwarder');
                $this->addPageHandler('/begin/publish/?', '\Idno\Pages\Onboarding\Publish');

                $this->themes  = new Themes();
                $this->plugins = new Plugins(); // This must be loaded last

            }

            /**
             * Return the database layer loaded as part of this site
             * @return \Idno\Core\DataConcierge
             */

            function &db()
            {
                return $this->db;
            }

            /**
             * Return the event dispatcher loaded as part of this site
             * @return \Symfony\Component\EventDispatcher\EventDispatcher
             */

            function &events()
            {
                return $this->dispatcher;
            }

            /**
             * Returns the current filesystem
             * @return \Idno\Files\FileSystem
             */
            function &filesystem()
            {
                return $this->filesystem;
            }

            /**
             * Returns the current Known hub
             * @return \Idno\Core\Hub
             */
            function &hub()
            {
                return $this->known_hub;
            }

            /**
             * Returns the current logging interface
             * @return \Idno\Core\Logging
             */
            function &logging()
            {
                return $this->logging;
            }
            
            /**
             * Return a persistent cache object.
             * @return \Idno\Caching\PersistentCache
             */
            function &cache() 
            {
                return $this->cache;
            }

            /**
             * Shortcut to trigger an event: supply the event name and
             * (optionally) an array of data, and get a variable back.
             *
             * @param string $eventName The name of the event to trigger
             * @param array $data Data to pass to the event
             * @param mixed $default Default response (if not forwarding)
             * @return mixed
             */

            function triggerEvent($eventName, $data = array(), $default = true)
            {
                $event = new Event($data);
                $event->setResponse($default);
                $event = $this->events()->dispatch($eventName, $event);
                if (!$event->forward()) {
                    return $event->response();
                } else {
                    header('Location: ' . $event->forward());
                    exit;
                }
            }

            /**
             * Helper function that returns the current configuration object
             * for this site (or a configuration setting value)
             *
             * @param The configuration setting value to retrieve (optional)
             *
             * @return \Idno\Core\Config
             */
            function &config($setting = false)
            {
                if ($setting === false)
                    return $this->config;
                else
                    return $this->config->$setting;
            }

            /**
             * Helper function that returns the current syndication object for this site
             * @return \Idno\Core\Syndication
             */
            function &syndication()
            {
                return $this->syndication;
            }

            /**
             * Return the session handler associated with this site
             * @return \Idno\Core\Session
             */

            function &session()
            {
                return $this->session;
            }

            /**
             * Return the plugin handler associated with this site
             * @return \Idno\Core\Plugins
             */
            function &plugins()
            {
                return $this->plugins;
            }

            /**
             * Return the theme handler associated with this site
             * @return \Idno\Core\Themes
             */
            function &themes()
            {
                return $this->themes;
            }

            /**
             * Return the template handler associated with this site
             * @return \Idno\Core\Template
             */

            function &template()
            {
                return $this->template;
            }

            /**
             * Return the action helper associated with this site
             * @return \Idno\Core\Actions
             */
            function &actions()
            {
                return $this->actions;
            }

            /**
             * Return the reader associated with this site
             * @return \Idno\Core\Reader
             */
            function &reader()
            {
                return $this->reader;
            }

            /**
             * Tells the system that callable $listener wants to be notified when
             * event $event is triggered. $priority is an optional integer
             * that specifies order priority; the higher the number, the earlier
             * in the chain $listener will be notified.
             *
             * @param string $event
             * @param callable $listener
             * @param int $priority
             */

            function addEventHook($event, $listener, $priority = 0)
            {
                if (is_callable($listener)) {
                    $this->dispatcher->addListener($event, $listener, $priority);
                }
            }

            /**
             * Registers a page handler for a given pattern, using Toro
             * page handling syntax
             *
             * @param string $pattern The pattern to match
             * @param callable $handler The handler callable that will serve the page
             * @param bool $public If set to true, this page is always public, even on non-public sites
             */

            function addPageHandler($pattern, $handler, $public = false)
            {
                if (defined('KNOWN_SUBDIRECTORY')) {
                    if (substr($pattern, 0, 1) != '/') {
                        $pattern = '/' . $pattern;
                    }
                    $pattern = '/' . KNOWN_SUBDIRECTORY . $pattern;
                }
                if (class_exists($handler)) {
                    $this->pagehandlers[$pattern] = $handler;
                    if ($public == true) {
                        $this->public_pages[] = $handler;
                    }
                }
            }

            /**
             * Registers a page handler for a given pattern, using Toro
             * page handling syntax - and ensures it will be handled first
             *
             * @param string $pattern The pattern to match
             * @param callable $handler The handler callable that will serve the page
             * @param bool $public If set to true, this page is always public, even on non-public sites
             */
            function hijackPageHandler($pattern, $handler, $public = false)
            {
                if (class_exists($handler)) {
                    unset($this->pagehandlers[$pattern]);
                    unset($this->public_pages[$pattern]);
                    $this->pagehandlers = array($pattern => $handler) + $this->pagehandlers;
                    if ($public == true) {
                        $this->public_pages = array($pattern => $handler) + $this->public_pages;
                    }
                }
            }

            /**
             * Mark a page handler class as offering public content even on walled garden sites
             * @param $class
             */
            function addPublicPageHandler($class)
            {
                if (class_exists($class)) {
                    $this->public_pages[] = $class;
                }
            }

            /**
             * Retrieve an array of walled garden page handlers
             * @return array
             */
            function getPublicPageHandlers()
            {
                if (!empty($this->public_pages)) {
                    return $this->public_pages;
                }

                return array();
            }

            /**
             * Does the specified page handler class represent a public page, even on walled gardens?
             * @param $class
             * @return bool
             */
            function isPageHandlerPublic($class)
            {
                if (!empty($class)) {
                    if (in_array($class, $this->getPublicPageHandlers())) {
                        return true;
                    }
                    if ($class[0] != "\\") {
                        $class = "\\" . $class;
                        if (in_array($class, $this->getPublicPageHandlers())) {
                            return true;
                        }
                    }
                }

                return false;
            }

            /**
             * Retrieves an instantiated version of the page handler class responsible for
             * a particular page (if any). May also be a whole URL.
             *
             * @param string $path_info The path, including the initial /, or the URL
             * @return bool|\Idno\Common\Page
             */

            function getPageHandler($path_info)
            {
                if (substr_count($path_info, \Idno\Core\site()->config()->url)) {
                    $path_info = '/' . str_replace(\Idno\Core\site()->config()->url, '', $path_info);
                }
                if ($q = strpos($path_info, '?')) {
                    $path_info = substr($path_info, 0, $q);
                }
                $tokens             = array(
                    ':string' => '([a-zA-Z]+)',
                    ':number' => '([0-9]+)',
                    ':alpha'  => '([a-zA-Z0-9-_]+)'
                );
                $discovered_handler = false;
                $matches            = array();
                foreach ($this->pagehandlers as $pattern => $handler_name) {
                    $pattern = strtr($pattern, $tokens);
                    if (preg_match('#^/?' . $pattern . '/?$#', $path_info, $matches)) {
                        $discovered_handler = $handler_name;
                        $regex_matches      = $matches;
                        break;
                    }
                }
                if (class_exists($discovered_handler)) {
                    $page = new $discovered_handler();
                    if ($page instanceof \Idno\Common\Page) {
                        unset($matches[0]);
                        $page->arguments = array_values($matches);

                        return $page;
                    }
                }

                return false;
            }

            /**
             * Sets the current page (if any) for access throughout the system
             * @param \Idno\Common\Page $page
             */
            function setCurrentPage($page)
            {
                $this->currentPage = $page;
            }

            /**
             * Retrieve the current page
             * @return bool|\Idno\Common\Page
             */
            function currentPage()
            {
                if (!empty($this->currentPage)) {
                    return $this->currentPage;
                }

                return new Page();
            }

            /**
             * Retrieves admins for this site
             * @return array
             */
            function getAdmins()
            {
                return User::get(['admin' => true], [], 9999);
            }

            /**
             * Retrieve this version of Known's version number
             * @return string
             */
            function version()
            {
                return '0.8.2';
            }

            /**
             * Alias for version()
             * @return string
             */
            function getVersion()
            {
                return $this->version();
            }

            /**
             * Retrieve a machine-readale version of Known's version number
             * @return string
             */
            function machineVersion()
            {
                return '2015072201';
            }

            /**
             * Alias for getMachineVersion
             * @return string
             */
            function getMachineVersion()
            {
                return $this->machineVersion();
            }

            /**
             * Can a specified user (either an explicitly specified user ID
             * or the currently logged-in user if this is left blank) edit
             * this entity?
             *
             * In this instance this specifically means "Can a given user create
             * new content or
             *
             * @param string $user_id
             * @return true|false
             */

            function canEdit($user_id = '')
            {

                if (!\Idno\Core\site()->session()->isLoggedOn()) return false;

                if (empty($user_id)) {
                    $user_id = \Idno\Core\site()->session()->currentUserUUID();
                }

                if ($user = \Idno\Entities\User::getByUUID($user_id)) {

                    if ($user->isAdmin()) {
                        return true;
                    }

                }

                return false;
            }

            /**
             * Can a specified user (either an explicitly specified user ID
             * or the currently logged-in user if this is left blank) publish
             * to the site?
             *
             * @param string $user_id
             * @return true|false
             */

            function canWrite($user_id = '')
            {
                if (!\Idno\Core\site()->session()->isLoggedOn()) return false;

                if (empty($user_id)) {
                    $user_id = \Idno\Core\site()->session()->currentUserUUID();
                }

                if ($user = \Idno\Entities\User::getByUUID($user_id)) {

                    // Remote users can't ever create anything :( - for now
                    if ($user instanceof \Idno\Entities\RemoteUser)
                        return false;

                    // But local users can
                    if ($user instanceof \Idno\Entities\User)
                        return true;

                }

                return false;
            }

            /**
             * Can a specified user (either an explicitly specified user ID
             * or the currently logged-in user if this is left blank) view
             * this entity?
             *
             * Always returns true at the moment, but might be a good way to build
             * walled garden functionality.
             *
             * @param string $user_id
             * @return true|false
             */

            function canRead($user_id = '')
            {
                return true;
            }

            /**
             * Retrieve site icons.
             * Retrieve a set of one or more icon for the current site, allowing plugins and other components
             * access icons for displaying in various contexts
             *
             * @returns array An associative array of various icons => url
             */
            function getSiteIcons()
            {
                $icons = [];

                // Set our defaults (TODO: Set these cleaner, perhaps through the template system)
                $icons['defaults'] = [
                    'default'     => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/logo_k.png',
                    'default_16'  => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/logo_k_16.png',
                    'default_32'  => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/logo_k_32.png',
                    'default_64'  => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/logo_k_64.png',

                    // Apple logos
                    'default_57'  => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/apple-icon-57x57.png',
                    'default_72'  => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/apple-icon-72x72.png',
                    'default_114' => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/apple-icon-114x114.png',
                    'default_144' => \Idno\Core\site()->config()->getDisplayURL() . 'gfx/logos/apple-icon-144x144.png',
                ];

                // If we're on a page, see if that has a specific icon
                if ($page = \Idno\Core\site()->currentPage()) {
                    if ($page_icons = $page->getIcon()) {
                        $icons['page'] = $page_icons;
                    }
                }

                // Now, return a list of icons, but pass it through an event hook to override
                return $this->triggerEvent('site/icons', ['object' => $this], $icons);
            }

            /**
             * Retrieve notices (eg notifications that a new version has been released) from Known HQ
             * @return mixed
             */
            function getVendorMessages()
            {

                if (!empty(site()->config()->noping)) {
                    return '';
                }
                $web_client = new Webservice();
                $results    = $web_client->post('https://withknown.com/vendor-services/messages/', array(
                    'url'     => site()->config()->getURL(),
                    'title'   => site()->config()->getTitle(),
                    'version' => site()->getVersion(),
                    'public'  => site()->config()->isPublicSite(),
                    'hub'     => site()->config()->known_hub
                ));
                if ($results['response'] == 200) {
                    return $results['content'];
                }

            }

            /**
             * Is this site being run in embedded mode? Hides the navigation bar, maybe more.
             * @return bool
             */
            function embedded()
            {
                if (site()->currentPage()->getInput('unembed')) {
                    $_SESSION['embedded'] = false;

                    return false;
                }
                if (!empty($_SESSION['embedded'])) {
                    return true;
                }
                if (site()->currentPage()->getInput('embedded')) {
                    $_SESSION['embedded'] = true;

                    return true;
                }

                return false;
            }

            /**
             * Detects if this site is being accessed securely or not
             * @return bool
             */
            function isSecure()
            {
                return
                    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || $_SERVER['SERVER_PORT'] == 443
                    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
            }

            /**
             * This is a state dependant object, and so can not be serialised.
             * @return array
             */
            function __sleep()
            {
                return [];
            }
        }

        /**
         * Helper function that returns the current site object
         * @return \Idno\Core\Idno
         */
        function &site()
        {
            return \Idno\Core\Idno::$site;
        }

    }