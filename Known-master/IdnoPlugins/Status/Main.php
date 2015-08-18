<?php

    namespace IdnoPlugins\Status {

        class Main extends \Idno\Common\Plugin {
            
            function registerPages() {
                \Idno\Core\site()->addPageHandler('/status/edit/?', '\IdnoPlugins\Status\Pages\Edit');
                \Idno\Core\site()->addPageHandler('/status/edit/([A-Za-z0-9]+)/?', '\IdnoPlugins\Status\Pages\Edit');
                \Idno\Core\site()->addPageHandler('/reply/edit/?', '\IdnoPlugins\Status\Pages\Edit');
                \Idno\Core\site()->addPageHandler('/reply/edit/([A-Za-z0-9]+)/?', '\IdnoPlugins\Status\Pages\Edit');
                \Idno\Core\site()->addPageHandler('/status/delete/([A-Za-z0-9]+)/?', '\IdnoPlugins\Status\Pages\Delete');
                \Idno\Core\site()->addPageHandler('/reply/delete/([A-Za-z0-9]+)/?', '\IdnoPlugins\Status\Pages\Delete');
            }
            
            function registerContentTypes() {
                parent::registerContentTypes();
                
                \Idno\Common\ContentType::register($this->getNamespace() . '\\RepliesContentType');
            }
        }

    }