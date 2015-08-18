<?php

    namespace IdnoPlugins\StaticPages\Pages {

        use Idno\Common\Page;

        class Admin extends Page {

            function getContent() {

                $this->adminGatekeeper();
                $staticpages = \Idno\Core\site()->plugins()->get('StaticPages');
                $categories = $staticpages->getCategories();
                $pages = $staticpages->getPagesAndCategories();
                $body = \Idno\Core\site()->template()->__(['categories' => $categories, 'pages' => $pages])->draw('staticpages/admin');
                \Idno\Core\site()->template()->__([
                    'title' => 'Pages', 'body' => $body
                ])->drawPage();

            }

            function postContent() {

                $this->adminGatekeeper();

            }

        }

    }