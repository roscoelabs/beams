<?php

    /**
     * Plugin administration
     */

    namespace Idno\Pages\Admin {

        /**
         * Default class to serve the homepage
         */
        class Plugins extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->adminGatekeeper(); // Admins only
                $t        = \Idno\Core\site()->template();
                $t->body  = $t->__(array(
                    'plugins_stored' => \Idno\Core\site()->plugins()->getStored(),
                    'plugins_loaded' => \Idno\Core\site()->plugins()->getLoaded(),
                ))->draw('admin/plugins');
                $t->title = 'Plugins';
                $t->drawPage();
            }

            function postContent()
            {
                $this->adminGatekeeper(); // Admins only
                $plugin = $this->getInput('plugin');
                $action = $this->getInput('action');
                if (defined('KNOWN_MULTITENANT_HOST')) {
                    $host = KNOWN_MULTITENANT_HOST;
                }
                if (
                    preg_match('/^[a-zA-Z0-9]+$/', $plugin) &&
                    (
                        file_exists(\Idno\Core\site()->config()->path . '/IdnoPlugins/' . $plugin) ||
                        (!empty(\Idno\Core\site()->config()->external_plugin_path) && file_exists(\Idno\Core\site()->config()->external_plugin_path . '/IdnoPlugins/' . $plugin)) ||
                        (!empty($host) && file_exists(\Idno\Core\site()->config()->path . '/hosts/' . $host . '/IdnoPlugins/' . $plugin))
                    )
                ) {
                    switch ($action) {
                        case 'install':
                            \Idno\Core\site()->config->config['plugins'][] = $plugin;
                            if (!empty(\Idno\Core\site()->config()->external_plugin_path) && file_exists(\Idno\Core\site()->config()->external_plugin_path . '/IdnoPlugins/' . $plugin)) {
                                \Idno\Core\site()->config->config['directloadplugins'][$plugin] = \Idno\Core\site()->config()->external_plugin_path . '/IdnoPlugins/' . $plugin;
                            }
                            \Idno\Core\site()->session()->addMessage('The plugin was installed.');
                            break;
                        case 'uninstall':
                            if (($key = array_search($plugin, \Idno\Core\site()->config->config['plugins'])) !== false) {
                                \Idno\Core\site()->triggerEvent('plugin/unload/' . $plugin);
                                unset(\Idno\Core\site()->config->config['plugins'][$key]);
                                unset(\Idno\Core\site()->config->config['directloadplugins'][$key]);
                                \Idno\Core\site()->session()->addMessage('The plugin was uninstalled.');
                            }
                            break;
                    }
                    \Idno\Core\site()->config->config['plugins'] = array_unique(\Idno\Core\site()->config->config['plugins']);
                    \Idno\Core\site()->config()->save();
                }
                $this->forward(\Idno\Core\site()->config()->getURL() . 'admin/plugins/');
            }

        }

    }