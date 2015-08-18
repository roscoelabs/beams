<?php

    /**
     * Generic, backup viewer for entities
     */

    namespace Idno\Pages\Entity {

        /**
         * Default class to serve the homepage
         */
        class Edit extends \Idno\Common\Page
        {

            // Handle GET requests to the homepage

            function getContent()
            {
                if (!empty($this->arguments[0])) {
                    $object = \Idno\Common\Entity::getByID($this->arguments[0]);
                }
                if (empty($object)) $this->forward(); // TODO: 404
                if (!$object->canEdit()) $this->forward($object->getDisplayURL());

                session_write_close();

                $t = \Idno\Core\site()->template();
                $t->__(array(

                    'title' => $object->getTitle(),
                    'body'  => $object->drawEdit()

                ))->drawPage();
            }

        }

    }