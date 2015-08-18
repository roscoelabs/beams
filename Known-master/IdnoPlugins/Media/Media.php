<?php

    namespace IdnoPlugins\Media {

        class Media extends \Idno\Common\Entity
        {

            function getTitle()
            {
                if (empty($this->title)) {
                    return 'Untitled';
                } else {
                    return $this->title;
                }
            }

            function getDescription()
            {
                return $this->body;
            }

            /**
             * Media objects have type 'media'
             * @return 'media'
             */
            function getActivityStreamsObjectType()
            {
                return 'media';
            }

            /**
             * Saves changes to this object based on user input
             * @return bool
             */
            function saveDataFromInput()
            {

                if (empty($this->_id)) {
                    $new = true;
                } else {
                    $new = false;
                }

                if ($new) {
                    if (!\Idno\Core\site()->triggerEvent("file/upload",[],true)) {
                        return false;
                    }
                }

                $this->title = \Idno\Core\site()->currentPage()->getInput('title');
                $this->body  = \Idno\Core\site()->currentPage()->getInput('body');
                $this->tags  = \Idno\Core\site()->currentPage()->getInput('tags');
                $access = \Idno\Core\site()->currentPage()->getInput('access');
                $this->setAccess($access);

                if ($time = \Idno\Core\site()->currentPage()->getInput('created')) {
                    if ($time = strtotime($time)) {
                        $this->created = $time;
                    }
                }

                // This flag will tell us if it's safe to save the object later on
                if ($new) {
                    $ok = false;
                } else {
                    $ok = true;
                }

                // Get media
                if ($new) {
                    // This is awful, but unfortunately, browsers can't be trusted to send the right mimetype.
                    $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
                    if (!empty($ext)) {
                        if (in_array($ext,
                            array(
                                'mp4',
                                'mov',
                                'webm',
                                'ogg',
                                'mpeg',
                                'mp3',
                                'vorbis'
                            )
                        )
                        ) {
                            $media_file = $_FILES['media'];
                            if ($media_file['type'] == 'application/octet-stream') {
                                switch ($ext) {
                                    case 'mp4':
                                        $media_file['type'] = 'video/mp4';
                                        break;
                                    case 'mov':
                                        $media_file['type'] = 'video/mov';
                                        break;
                                    case 'webm':
                                        $media_file['type'] = 'video/webm';
                                        break;
                                    case 'ogg':
                                        $media_file['type'] = 'audio/ogg';
                                        break;
                                    case 'mp3':
                                        $media_file['type'] = 'audio/mpeg';
                                        break;
                                    case 'mpeg':
                                        $media_file['type'] = 'video/mpeg';
                                        break;
                                    case 'ogv':
                                        $media_file['type'] = 'audio/ogv';
                                        break;
                                }
                            }
                            $this->media_type = $media_file['type'];
                            if ($media = \Idno\Entities\File::createFromFile($media_file['tmp_name'], $media_file['name'], $media_file['type'], true)) {
                                $this->attachFile($media);
                                $ok = true;
                            } else {
                                \Idno\Core\site()->session()->addErrorMessage('Media wasn\'t attached.');
                            }
                        } else {
                            \Idno\Core\site()->session()->addErrorMessage('This doesn\'t seem to be a media file .. ' . $_FILES['media']['type']);
                        }
                    } else {
                        \Idno\Core\site()->session()->addErrorMessage('We couldn\'t access your media. Please try again.');

                        return false;
                    }
                }

                // If a media file wasn't attached, don't save the file.
                if (!$ok) {
                    return false;
                }

                if ($this->save($new)) {
                    \Idno\Core\Webmention::pingMentions($this->getURL(), \Idno\Core\site()->template()->parseURLs($this->getTitle() . ' ' . $this->getDescription()));

                    return true;
                } else {
                    return false;
                }

            }

        }

    }