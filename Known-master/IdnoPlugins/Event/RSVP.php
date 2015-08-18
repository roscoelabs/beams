<?php

    namespace IdnoPlugins\Event {

        class RSVP extends \Idno\Common\Entity {

            function getTitle() {
                if (!empty($this->body)) {
                    return ucfirst($this->rsvp) . ': ' . $this->body;
                }
                return '';
            }

            function getDescription() {
                $body = $this->body;
                if (!empty($this->inreplyto)) {
                    $body = '<a href="'.$this->inreplyto.'" class="u-in-reply-to"></a>' . $body;
                }
                if (!empty($this->rsvp)) {
                    $body = '<data class="p-rsvp" value="'.$this->rsvp.'">' . $body . '</data>';
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
                    return \Idno\Core\site()->config()->url . 'rsvp/' . $this->getID() . '/';
                } else {
                    return parent::getURL();
                }
            }

            /**
             * Event objects have type 'article'
             * @return 'article'
             */
            function getActivityStreamsObjectType() {
                return 'rsvp';
            }

            /**
             * Event objects show up as h-event in a Microformats stream
             * @return string
             */
            function getMicroformats2ObjectType() {
                return 'h-entry';
            }

            function saveDataFromInput() {

                if (empty($this->_id)) {
                    $new = true;
                } else {
                    $new = false;
                }
                $body = \Idno\Core\site()->currentPage()->getInput('body');
                $rsvp = \Idno\Core\site()->currentPage()->getInput('rsvp');
                $access = \Idno\Core\site()->currentPage()->getInput('access');
                if (!empty($rsvp)) {
                    $this->body = $body;
                    $rsvp = strtolower($rsvp);
                    if ($rsvp != 'yes' && $rsvp != 'maybe') {
                        $rsvp = 'no';
                    }
                    $this->rsvp = $rsvp;
                    $this->inreplyto = \Idno\Core\site()->currentPage()->getInput('inreplyto');
                    $this->setAccess($access);
                    if ($this->save($new)) {
                        \Idno\Core\Webmention::pingMentions($this->getURL(), \Idno\Core\site()->template()->parseURLs($this->getDescription()));
                        \Idno\Core\site()->session()->addMessage('Your RSVP was successfully saved.');
                        return true;
                    }
                } else {
                    \Idno\Core\site()->session()->addErrorMessage('You can\'t save an RSVP with no status.');
                }
                return false;

            }

            function deleteData() {
                \Idno\Core\Webmention::pingMentions($this->getURL(), \Idno\Core\site()->template()->parseURLs($this->getDescription()));
            }

        }

    }