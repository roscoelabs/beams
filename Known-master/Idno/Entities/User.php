<?php

    /**
     * User representation
     *
     * @package idno
     * @subpackage core
     */

    namespace Idno\Entities {

        use Idno\Common\Entity;
        use Idno\Core\Email;

        // We need the PHP 5.5 password API
        require_once \Idno\Core\site()->config()->path . '/external/password_compat/lib/password.php';

        class User extends \Idno\Common\Entity implements \JsonSerializable
        {

            /**
             * Overloading the constructor for users to make it explicit that
             * they don't have owners
             */

            function __construct()
            {

                parent::__construct();
                $this->owner = false;

            }

            /**
             * Register user-related events
             */
            static function registerEvents()
            {

                // Hook to add user data to webfinger
                \Idno\Core\site()->addEventHook('webfinger', function (\Idno\Core\Event $event) {

                    $eventdata = $event->data();
                    $user      = $eventdata['object'];

                    $links = $event->response();
                    if (empty($links)) $links = array();

                    if ($user instanceof User) {
                        $links = array(
                            array(
                                'rel'  => 'http://webfinger.net/rel/avatar',
                                'href' => $user->getIcon()
                            ),
                            array(
                                'rel'  => 'http://webfinger.net/rel/profile-page',
                                'href' => $user->getURL()
                            )
                        );
                    }

                    $event->setResponse($links);

                });

                // Refresh session user whenever it is saved
                \Idno\Core\site()->addEventHook('saved', function (\Idno\Core\Event $event) {

                    $eventdata = $event->data();
                    $user      = $eventdata['object'];

                    if ($user instanceof User) {
                        if ($currentUser = \Idno\Core\site()->session()->currentUser()) {
                            if ($user->getUUID() == $currentUser->getUUID()) {
                                \Idno\Core\site()->session()->refreshSessionUser($user);
                            }
                        }
                    }

                });

                // Email notifications
                \Idno\Core\site()->addEventHook('notify', function (\Idno\Core\Event $event) {

                    $eventdata = $event->data();
                    $user      = $eventdata['user'];

                    $eventdata = $event->data();
                    if ($user instanceof User && $context = $eventdata['context']) {

                        if (empty($user->notifications['email']) || $user->notifications['email'] == 'all' || ($user->notifications['email'] == 'comment' && in_array($context, array('comment', 'reply')))) {

                            $eventdata = $event->data();
                            $vars      = $eventdata['vars'];
                            if (empty($vars)) {
                                $vars = array();
                            }
                            $eventdata      = $event->data();
                            $vars['object'] = $eventdata['object'];

                            if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                                $email = new Email();
                                $email->setSubject($eventdata['message']);
                                $email->setHTMLBodyFromTemplate($eventdata['message_template'], $vars);
                                $email->setTextBodyFromTemplate($eventdata['message_template'], $vars);
                                $email->addTo($user->email);
                                $email->send();
                            }

                        }

                    }

                });

            }

            /**
             * Retrieve the URI to this user's avatar icon image
             * (if none has been saved, a default is returned)
             *
             * @return string
             */
            function getIcon()
            {
                $response = \Idno\Core\site()->triggerEvent('icon', array('object' => $this));
                if (!empty($response) && $response !== true) {
                    return $response;
                }
                if (!empty($this->image)) {
                    return $this->image;
                }
                if (!empty($this->icon)) {
                    return \Idno\Core\site()->config()->getDisplayURL() . 'file/' . $this->icon;
                }

                return \Idno\Core\site()->template()->__(array('user' => $this))->draw('entity/User/icon');
            }

            /**
             * A friendly alias for getTitle.
             * @return string
             */
            function getName()
            {
                return $this->getTitle();
            }

            /**
             * A friendly alias for SetTitle.
             * @param $name
             */
            function setName($name)
            {
                return $this->setTitle($name);
            }

            /**
             * Get the profile URL for this user
             * @return string
             */
            function getURL()
            {
                if (!empty($this->url)) {
                    return $this->url;
                }

                return \Idno\Core\site()->config()->getDisplayURL() . 'profile/' . $this->getHandle();
            }

            /**
             * Wrapper for getURL for consistency
             * @return string
             */
            function getDisplayURL()
            {
                return $this->getURL();
            }

            /**
             * Retrieve's this user's handle
             * @return string
             */

            function getHandle()
            {
                return $this->handle;
            }

            /**
             * Retrieves user by email address
             * @param string $email
             * @return User|false Depending on success
             */
            static function getByEmail($email)
            {
                if ($result = \Idno\Core\site()->db()->getObjects(get_called_class(), array('email' => $email), null, 1)) {
                    foreach ($result as $row) {
                        return $row;
                    }
                }

                return false;
            }

            function getOwner()
            {
                return $this;
            }

            function getOwnerID()
            {
                return $this->getUUID();
            }

            /**
             * Retrieve a text description of this user
             * @return string
             */
            function getDescription()
            {
                if (!empty($this->profile['description'])) {
                    return $this->profile['description'];
                }

                return '';
            }

            /**
             * Retrieve a one-line text description of this user
             *
             * @param int $words
             * @return string
             */
            function getShortDescription($words = 25)
            {
                if (!empty($this->profile['tagline'])) {
                    $tagline = $this->profile['tagline'];
                } else if (!empty($this->short_description)) {
                    $tagline = $this->short_description;
                } else {
                    $tagline = $this->getDescription();
                }

                if (!empty($tagline)) {
                    $description = strip_tags($tagline);
                    $description_words = explode(' ', $description);
                    $description = implode(' ', array_slice($description_words, 0, $words));
                    if (sizeof($description_words) > $words) {
                        $description .= ' ...';
                    }
                    return $description;
                }

                return '';
            }

            /**
             * Sets this user's username handle (and balks if someone's already using it)
             * @param string $handle
             * @return true|false True or false depending on success
             */

            function setHandle($handle)
            {
                $handle = trim($handle);
                $handle = strtolower($handle);
                if (!empty($handle) && ctype_alnum($handle)) {
                    if (!self::getByHandle($handle)) {
                        $this->handle = $handle;
                    }
                }

                return false;
            }

            /**
             * Retrieves user by handle
             * @param string $handle
             * @return User|false Depending on success
             */
            static function getByHandle($handle)
            {
                if ($result = \Idno\Core\site()->db()->getObjects(get_called_class(), array('handle' => $handle), null, 1)) {
                    foreach ($result as $row) {
                        return $row;
                    }
                }

                return false;
            }

            /**
             * Retrieve a user by their profile URL.
             * @param string $url
             * @return User|false
             */
            static function getByProfileURL($url)
            {
                // If user explicitly has a profile url set (generally this means it's a RemoteUser class
                if ($result = \Idno\Core\site()->db()->getObjects(get_called_class(), array('url' => $url), null, 1)) {
                    foreach ($result as $row) {
                        return $row;
                    }
                }
                // Ok, now try and see if we can get the local profile
                if (preg_match("~" . \Idno\Core\site()->config()->url . 'profile/([A-Za-z0-9]+)?~', $url, $matches))
                    return \Idno\Entities\User::getByHandle($matches[1]);

                // Can't find
                return false;
            }

            /**
             * Returns this user's unique key for use with the API, and generates a new one if they don't
             * have one yet
             * @return string
             */
            function getAPIkey()
            {
                if (!empty($this->apikey)) {
                    return $this->apikey;
                }

                return $this->generateAPIkey();
            }

            /**
             * Generate a semi-random API key for this user, and then return it
             * @return string
             */
            function generateAPIkey()
            {
                $token = new \Idno\Core\TokenProvider();
                
                $apikey       = strtolower(substr(base64_encode($token->generateToken(32)), 12, 16));
                $this->apikey = $apikey;
                $this->save();

                return $apikey;
            }

            /**
             * Is this user an admin?
             * @return bool
             */
            function isAdmin()
            {
                if (!empty($this->admin)) return true;

                return false;
            }

            /**
             * Set this user's site administrator status
             * @param bool $admin
             */
            function setAdmin($admin)
            {
                if ($admin == true) {
                    $this->admin = true;
                } else {
                    $this->admin = false;
                }
            }

            /**
             * Retrieve the URL required to edit this user
             * @return string
             */
            function getEditURL()
            {
                return \Idno\Core\site()->config()->url . 'profile/' . $this->getHandle() . '/edit';
            }

            /**
             * Sets the built-in password property to a safe hash (if the
             * password is acceptable)
             *
             * @param string $password
             * @return true|false
             */
            function setPassword($password)
            {
                if (!empty($password)) {
                    $this->password = \password_hash($password, PASSWORD_BCRYPT);

                    return true;
                }

                return false;
            }

            /**
             * Verifies that the supplied password matches this user's password
             *
             * @param string $password
             * @return true|false
             */
            function checkPassword($password)
            {
                return \password_verify($password, $this->password);
            }

            /**
             * Check that a new password is strong.
             * @param string $password
             * @return bool
             */
            static function checkNewPasswordStrength($password)
            {

                $default = false;

                // Default "base" password validation
                if (strlen($password) >= 7) {
                    $default = true;
                }

                return \Idno\Core\site()->triggerEvent('user/password/checkstrength', array(
                    'password' => $password
                ), $default);

            }

            /**
             * Retrieve the current password recovery code - if it's less than three hours old
             * @return string|false
             */
            function getPasswordRecoveryCode()
            {
                if ($code = $this->password_recovery_code) {
                    if ($this->password_recovery_code_time > (time() - (3600 * 3))) {
                        return $code;
                    }
                }

                return false;
            }

            /**
             * Add a password recovery code to the user
             * @return string The new recovery code, suitable for sending in an email
             */
            function addPasswordRecoveryCode()
            {
                $auth_code                         = md5(time() . rand(0, 9999) . $this->email);
                $this->password_recovery_code      = $auth_code;
                $this->password_recovery_code_time = time();

                return $auth_code;
            }

            /**
             * Clears this user's password recovery code (eg if they log in and don't need it anymore).
             */
            function clearPasswordRecoveryCode()
            {
                $this->password_recovery_code = false;
            }

            /**
             * Does this user have everything he or she needs to be a fully-fledged
             * Known member? This method checks to make sure the minimum number of
             * fields are filled in.
             *
             * @return true|false
             */

            function isComplete()
            {
                $handle = $this->getHandle();
                $title  = $this->getTitle();
                if (!empty($handle) && !empty($title)) return true;

                return false;
            }

            /**
             * Count the number of posts this user has made
             * @return int
             */
            function countPosts()
            {
                return \Idno\Entities\ActivityStreamPost::countFromX('Idno\Entities\ActivityStreamPost', array('owner' => $this->getUUID()));
            }

            /**
             * Given a user entity (or a UUID), marks them as being followed by this user.
             * Remember to save this user entity.
             *
             * @param \Idno\Entities\User|string $user
             * @return bool
             */
            function addFollowing($user)
            {
                if ($user instanceof \Idno\Entities\User) {
                    $users = $this->getFollowingUUIDs();
                    if (!in_array($user->getUUID(), $users, true)) {
                        $users[$user->getUUID()] = array('name' => $user->getTitle(), 'icon' => $user->getIcon(), 'url' => $user->getURL());
                        $this->following         = $users;

                        // Create/modify ACL for following user
                        $acl = \Idno\Entities\AccessGroup::getOne(array(
                            'owner'             => $this->getUUID(),
                            'access_group_type' => 'FOLLOWING'
                        ));

                        if (empty($acl)) {
                            $acl                    = new \Idno\Entities\AccessGroup();
                            $acl->title             = "People I follow...";
                            $acl->access_group_type = 'FOLLOWING';
                        }

                        $acl->addMember($user->getUUID());
                        $acl->save();

                        \Idno\Core\site()->triggerEvent('follow', array('user' => $this, 'following' => $user));

                        return true;
                    }
                }

                return false;
            }

            /**
             * Get a list of user UUIDs that this user marks as following
             * @return array|null
             */
            function getFollowingUUIDs()
            {
                if (!empty($this->following)) {
                    return array_keys($this->following);
                } else {
                    return array();
                }
            }

            /**
             * Returns a list of users that this user marks as following, where the UUID is the array key, and
             * the array is of the form ['name' => 'Name', 'url' => 'Profile URL', 'icon' => 'Icon URI']
             * @return array|null
             */
            function getFollowingArray()
            {
                if (!empty($this->following)) {
                    return $this->following;
                } else {
                    return array();
                }
            }

            /**
             * Given a user entity (or a UUID), removes them from this user's followed list.
             * Remember to save this user entity.
             *
             * @param \Idno\Entities\User|string $user
             * @return bool
             */
            function removeFollowing($user)
            {
                if ($user instanceof \Idno\Entities\User) {
                    $users = $this->getFollowingUUIDs();
                    unset($users[$user->getUUID()]);
                    $this->following = $users;

                    $acl = \Idno\Entities\AccessGroup::getOne(array(
                        'owner'             => $this->getUUID(),
                        'access_group_type' => 'FOLLOWING'
                    ));

                    if (!empty($acl)) {
                        $acl->removeMember($user->getUUID());
                        $acl->save();
                    }

                    \Idno\Core\site()->triggerEvent('unfollow', array('user' => $this, 'following' => $user));

                    return true;
                }

                return false;
            }

            /**
             * Is the given user following this user?
             *
             * @param \Idno\Entities\User $user
             * @return bool
             */
            function isFollowedBy($user)
            {
                if ($user instanceof \Idno\Entities\User) {
                    if ($user->isFollowing($this)) {
                        return true;
                    }
                }

                return false;
            }

            /**
             * Is the given user a followed by this user?
             *
             * @param \Idno\Entities\User|string $user
             * @return bool
             */
            function isFollowing($user)
            {
                if ($user instanceof \Idno\Entities\User) {
                    if (in_array($user->getUUID(), $this->getFollowingUUIDs())) {
                        return true;
                    }
                }

                return false;
            }

            /**
             * Array of access groups that this user can *read* entities
             * from
             *
             * @return array
             */

            function getReadAccessGroups()
            {
                return $this->getXAccessGroups('read');
            }

            /**
             * Get an array of access groups that this user has arbitrary permissions for
             *
             * @param string $permission The type of permission
             * @return array
             */
            function getXAccessGroups($permission)
            {
                $return = array('PUBLIC', 'SITE', $this->getUUID());
                if ($groups = \Idno\Core\site()->db()->getObjects('Idno\\Entities\\AccessGroup', array('members.' . $permission => $this->getUUID()), null, PHP_INT_MAX, 0)) {
                    $return = array_merge($return, $groups);
                }

                return $return;
            }

            /**
             * Array of access groups that this user can *write* entities
             * to
             *
             * @return array
             */

            function getWriteAccessGroups()
            {
                return $this->getXAccessGroups('write');
            }

            /**
             * Array of access group IDs that this user can *read* entities
             * from
             *
             * @return array
             */

            function getReadAccessGroupIDs()
            {
                return $this->getXAccessGroupIDs('read');
            }

            /**
             * Get an array of access group IDs that this user has an arbitrary permission for
             *
             * @param string $permission Permission type
             * @return array
             */
            function getXAccessGroupIDs($permission)
            {
                $return = array('PUBLIC', 'SITE', $this->getUUID());
                if ($groups = \Idno\Core\site()->db()->getRecords(array('uuid' => true),
                    array(
                        'entity_subtype'         => 'Idno\\Entities\\AccessGroup',
                        'members.' . $permission => $this->getUUID()),
                    PHP_INT_MAX,
                    0)
                ) {
                    foreach ($groups as $group) {
                        $return[] = $group['uuid'];
                    }
                }

                return $return;
            }

            /**
             * Array of access group IDs that this user can *write* entities
             * to
             *
             * @return type
             */

            function getWriteAccessGroupIDs()
            {
                return $this->getXAccessGroupIDs('write');
            }

            /**
             * Users are activity streams objects of type "person".
             *
             * @return string
             */
            function getActivityStreamsObjectType()
            {
                $uuid = $this->getUUID();
                if (!empty($uuid))
                    return 'person';

                return false;
            }

            /**
             * Get a user's settings for default content types on their homepage (or all the content types registered
             * if none have been listed).
             *
             * THIS IS A LEGACY FUNCTION AND DUE FOR REMOVAL.
             * @deprecated
             *
             * @return array
             */
            function getDefaultContentTypes()
            {
                $friendly_types = array();
                if ($temp_types = $this->settings['default_feed_content']) {
                    if (is_array($temp_types)) {
                        foreach ($temp_types as $temp_type) {
                            if ($content_type_class = \Idno\Common\ContentType::categoryTitleToClass($temp_type)) {
                                $friendly_types[] = $content_type_class;
                            }
                        }
                    }
                }

                return $friendly_types;
            }

            /**
             * Return the total size of all files owned by this user
             * @return int
             */
            function getFileUsage()
            {
                $bytes = 0;

                // Gather bytes

                return $bytes;
            }

            /**
             * Hook to provide a method of notifying a user - for example, sending an email or displaying a popup.
             *
             * @param string $message The short text message to notify the user with. (eg, a subject line.)
             * @param string $message_template Optionally, a template name pointing to a longer version of the message with more detail.
             * @param string $context Optionally, a string describing the kind of action. eg, "comment", "like" or "reshare".
             * @param array $vars Optionally, variables to pass to the template.
             * @param \Idno\Common\Entity|null $object Optionally, an object to pass
             * @param array|null $params Optionally, any parameters to pass to the process. NB: this should be used rarely.
             */
            public function notify($message, $message_template = '', $vars = array(), $context = '', $object = null, $params = null)
            {
                return \Idno\Core\site()->triggerEvent('notify', array(
                    'user'             => $this,
                    'message'          => $message,
                    'context'          => $context,
                    'vars'             => $vars,
                    'message_template' => $message_template,
                    'object'           => $object,
                    'parameters'       => $params
                ));
            }

            /**
             * Save form input
             * @param \Idno\Common\Page $page
             * @return bool|\Idno\Common\false|\Idno\Common\true|\Idno\Core\false|\Idno\Core\MongoID|null
             */
            function saveDataFromInput()
            {

                if (!$this->canEdit()) return false;

                $profile = \Idno\Core\site()->currentPage()->getInput('profile');
                if (!empty($profile)) {
                    $this->profile = $profile;
                }
                if ($name = \Idno\Core\site()->currentPage()->getInput('name')) {
                    $this->setName($name);
                }
                if (!empty($_FILES['avatar'])) {
                    if (in_array($_FILES['avatar']['type'], array('image/png', 'image/jpg', 'image/jpeg', 'image/gif'))) {
                        if (getimagesize($_FILES['avatar']['tmp_name'])) {
                            if ($icon = \Idno\Entities\File::createThumbnailFromFile($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name'], 300, true)) {
                                $this->icon = (string)$icon;
                            } else if ($icon = \Idno\Entities\File::createFromFile($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name'])) {
                                $this->icon = (string)$icon;
                            }
                        }
                    }
                }

                return $this->save();

            }

            /**
             * Remove this user and all its objects
             * @return bool
             */
            function delete()
            {

                // First, remove all owned objects
                while ($objects = Entity::get(array('owner' => $this->getUUID(), array(), 100))) {
                    foreach ($objects as $object) {
                        $object->delete();
                    }
                }

                return parent::delete();
            }

            public function jsonSerialize()
            {
                $data          = parent::jsonSerialize();
                $data['image'] = array('url' => $this->getIcon());

                return $data;
            }


        }

    }
