<?php

    namespace IdnoPlugins\Like {

        class Like extends \Idno\Common\Entity {

            function getTitle() {
                if (!empty($this->pageTitle)) {
                    return $this->pageTitle;
                }
                return strip_tags($this->body);
            }

            function getDescription() {
                $body = $this->body;
                if (!empty($this->description)) {
                    $body .= ' ' . $this->description;
                }
                return $body;
            }

            function getURL() {
                // If we have a URL override, use it
                if (!empty($this->url)) {
                    return $this->url;
                }

                if (!empty($this->canonical)) {
                    return $this->canonical;
                }
                if (!($this->getSlug()) && ($this->getID())) {
                    return \Idno\Core\site()->config()->url . 'bookmark/' . $this->getID() . '/' . $this->getPrettyURLTitle();
                } else {
                    return parent::getURL();
                }
            }

            /**
             * Returns a URL for syndication
             * @return mixed
             */
            function getSyndicationURL() {
                return $this->body;
            }

            /**
             * Like objects have type 'bookmark'
             * @return 'bookmark'
             */
            function getActivityStreamsObjectType() {
                return 'bookmark';
            }

            /**
             * Given a URL, returns the page title.
             * @param $Url
             * @return mixed
             */
            function getTitleFromURL($Url){
                $str = \Idno\Core\Webservice::file_get_contents($Url);
                if(strlen($str) > 0){
                    preg_match("/\<title\>(.*)\<\/title\>/siuU",$str,$title);
                    return htmlspecialchars_decode($title[1]);
                }
                return '';
            }

            /**
             * Saves changes to this object based on user input
             * @return true|false
             */
            function saveDataFromInput() {

                if (empty($this->_id)) {
                    $new = true;
                } else {
                    $new = false;
                }
                $body = \Idno\Core\site()->currentPage()->getInput('body');
                $description = \Idno\Core\site()->currentPage()->getInput('description');
                $tags = \Idno\Core\site()->currentPage()->getInput('tags');
                $title = \Idno\Core\site()->currentPage()->getInput('title');
                $access = \Idno\Core\site()->currentPage()->getInput('access');

                if ($time = \Idno\Core\site()->currentPage()->getInput('created')) {
                    if ($time = strtotime($time)) {
                        $this->created = $time;
                    }
                }

                $body = trim($body);
                if(filter_var($body, FILTER_VALIDATE_URL)){
                    if (!empty($body)) {
                        $this->body = $body;
                        $this->description = $description;
                        $this->tags = $tags;
                        if (empty($title)) {
                            if ($title = $this->getTitleFromURL($body)) {
                                $this->pageTitle = $title;
                            } else {
                                $this->pageTitle = '';
                            }
                        } else {
                        	$this->pageTitle = $title;
                        }
                        if (empty($title)) {
                            \Idno\Core\site()->session()->addErrorMessage('You need to specify a title.');
                            return false;
                        }
                        $this->setAccess($access);
                        if ($this->save($new)) {
                            return true;
                        }
                    } else {
                        \Idno\Core\site()->session()->addErrorMessage('You can\'t bookmark an empty URL.');
                    }
                } else {
                    \Idno\Core\site()->session()->addErrorMessage('That doesn\'t look like a valid URL.');
                }
                return false;

            }

            function deleteData() {
                \Idno\Core\Webmention::pingMentions($this->getURL(), \Idno\Core\site()->template()->parseURLs($this->getDescription()));
            }

        }

    }
