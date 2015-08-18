<?php

    namespace IdnoPlugins\Status\Pages {

        class Edit extends \Idno\Common\Page {

            function getContent() {

                $this->createGatekeeper();    // This functionality is for logged-in users only

                // Are we loading an entity?
                if (!empty($this->arguments)) {
                    $object = \IdnoPlugins\Status\Status::getByID($this->arguments[0]);
                } else {
                    $object = \IdnoPlugins\Status\Status::factory();
                }

                $t = \Idno\Core\site()->template();
                $body = $t->__(array(
                    'object' => $object,
                    'url' => $this->getInput('url'),
                    'body' => $this->getInput('body'),
                    'tags' => $this->getInput('tags')
                ))->draw('entity/Status/edit');

                if (empty($object)) {
                    $title = 'What are you up to?';
                } else {
                    $title = 'Edit status update';
                }

                if (!empty($this->xhr)) {
                    echo $body;
                } else {
                    $t->__(array('body' => $body, 'title' => $title))->drawPage();
                }
            }

            function postContent() {
                $this->createGatekeeper();

                $new = false;
                if (!empty($this->arguments)) {
                    $object = \IdnoPlugins\Status\Status::getByID($this->arguments[0]);
                }
                if (empty($object)) {
                    $object = \IdnoPlugins\Status\Status::factory();
                }

                if ($object->saveDataFromInput($this)) {
                    $forward = $this->getInput('forward-to', $object->getDisplayURL());
                    $this->forward($forward);
                }

            }

        }

    }