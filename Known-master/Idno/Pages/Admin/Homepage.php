<?php

    namespace Idno\Pages\Admin {

        use Idno\Common\Page;

        class Homepage extends Page
        {

            function getContent()
            {
                $this->createGatekeeper(); // Logged-in only please
                $t                     = \Idno\Core\site()->template();
                $t->content_types      = \Idno\Common\ContentType::getRegistered();
                $default_content_types = \Idno\Core\site()->config()->getHomepageContentTypes(); //\Idno\Core\site()->session()->currentUser()->settings['default_feed_content'];

                if (empty($default_content_types)) {
                    foreach ($t->content_types as $content_type) {
                        $default_content_types[] = $content_type->getEntityClass();
                    }
                }

                $t->default_content_types = $default_content_types;
                $t->body                  = $t->draw('admin/homepage');
                $t->title                 = 'Homepage';
                $t->drawPage();
            }

            function postContent()
            {
                $this->createGatekeeper(); // Logged-in only please
                $user = \Idno\Core\site()->session()->currentUser();

                $default_feed_content = $this->getInput('default_feed_content');
                if (empty($default_feed_content) || !is_array($default_feed_content)) {
                    $default_feed_content = false;
                }

                $config                       = \Idno\Core\site()->config;
                $config->default_feed_content = $default_feed_content;
                \Idno\Core\site()->config     = $config;

                if (\Idno\Core\site()->config->save()) {
                    \Idno\Core\site()->session()->addMessage("The default homepage content types were saved.");
                }
                $this->forward(\Idno\Core\site()->config()->getURL() . 'admin/homepage/');
            }

        }

    }