<?php

    namespace Idno\Pages\Following {

        use Idno\Common\Page;

        class Refresh extends Page
        {

            function getContent()
            {

                $this->gatekeeper();
                \Idno\Core\site()->reader()->parseAndSaveFeeds();

            }

        }

    }