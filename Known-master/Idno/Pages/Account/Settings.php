<?php

    /**
     * Change user settings
     */

    namespace Idno\Pages\Account {

        /**
         * Default class to serve the homepage
         */
        class Settings extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->createGatekeeper(); // Logged-in only please
                $t        = \Idno\Core\site()->template();
                $t->body  = $t->draw('account/settings');
                $t->title = 'Account settings';
                $t->drawPage();
            }

            function postContent()
            {
                $this->createGatekeeper(); // Logged-in only please
                $user     = \Idno\Core\site()->session()->currentUser();
                $name     = $this->getInput('name');
                $email    = $this->getInput('email');
                $password = trim($this->getInput('password'));
                $username = trim($this->getInput('handle'));

                /*if (!\Idno\Common\Page::isSSL() && !\Idno\Core\site()->config()->disable_cleartext_warning) {
                    \Idno\Core\site()->session()->addErrorMessage("Warning: Access credentials were sent over a non-secured connection! To disable this warning set disable_cleartext_warning in your config.ini");
                }*/

                if (!empty($name)) {
                    $user->setTitle($name);
                }

                if (!empty($username) && $username != $user->getHandle()) {
                    $user->setHandle($username);
                }

                if (!empty($email) && $email != $user->email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if (!\Idno\Entities\User::getByEmail($email)) {
                        $user->email = $email;
                    } else {
                        \Idno\Core\site()->session()->addErrorMessage('Someone is already using ' . $email . ' as their email address.');
                    }
                }

                if (!empty($password)) {
                    if (\Idno\Entities\User::checkNewPasswordStrength($password)) {
                        \Idno\Core\site()->session()->addMessage("Your password has been updated.");
                        $user->setPassword($password);
                    } else {
                        \Idno\Core\site()->session()->addErrorMessage('Sorry, your password is too weak');
                    }
                }

                if ($user->save()) {
                    \Idno\Core\site()->session()->addMessage("Your details were saved.");
                }
                $this->forward($_SERVER['HTTP_REFERER']);
            }

        }

    }