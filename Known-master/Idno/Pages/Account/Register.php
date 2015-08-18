<?php

    /**
     * Create a user
     */

    namespace Idno\Pages\Account {

        use Idno\Entities\Invitation;

        /**
         * Default class to serve the registration page
         */
        class Register extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->reverseGatekeeper();
                $code       = $this->getInput('code');
                $email      = $this->getInput('email');
                $onboarding = $this->getInput('onboarding');

                if (empty(\Idno\Core\site()->config()->open_registration)) {
                    if (!\Idno\Core\site()->config()->canAddUsers()) {
                        \Idno\Core\site()->session()->addErrorMessage("This site is closed to new users.");
                        $this->forward(\Idno\Core\site()->config()->getURL());
                    }
                    if (!\Idno\Entities\Invitation::validate($email, $code)) {
                        \Idno\Core\site()->session()->addErrorMessage("Your invitation doesn't seem to be valid, or has expired.");
                        $this->forward(\Idno\Core\site()->config()->getURL());
                    }
                }

                $t = \Idno\Core\site()->template();
                if (empty($onboarding)) {
                    $t->body  = $t->__(array('email' => $email, 'code' => $code))->draw('account/register');
                    $t->title = 'Create a new account';
                    $t->drawPage();
                } else {
                    $t->body  = $t->__(array('email' => $email, 'code' => $code, 'messages' => \Idno\Core\site()->session()->getAndFlushMessages()))->draw('onboarding/register');
                    $t->title = 'Create a new account';
                    echo $t->draw('shell/simple');
                }
            }

            function postContent()
            {
                $name       = $this->getInput('name');
                $handle     = trim($this->getInput('handle'));
                $password   = trim($this->getInput('password'));
                $password2  = trim($this->getInput('password2'));
                $email      = trim($this->getInput('email'));
                $code       = $this->getInput('code');
                $onboarding = $this->getInput('onboarding');

                /*if (!\Idno\Common\Page::isSSL() && !\Idno\Core\site()->config()->disable_cleartext_warning) {
                    \Idno\Core\site()->session()->addErrorMessage("Warning: Access credentials were sent over a non-secured connection! To disable this warning set disable_cleartext_warning in your config.ini");
                }*/

                if (empty(\Idno\Core\site()->config()->open_registration)) {
                    if (!($invitation = \Idno\Entities\Invitation::validate($email, $code))) {
                        \Idno\Core\site()->session()->addErrorMessage("Your invitation doesn't seem to be valid, or has expired.");
                        $this->forward(\Idno\Core\site()->config()->getURL());
                    } else {
                        // Removing this from here - invitation will be deleted once user is created
                        //$invitation->delete(); // Remove the invitation; it's no longer needed
                    }
                }

                $user = new \Idno\Entities\User();

                if (empty($handle) && empty($email)) {
                    \Idno\Core\site()->session()->addErrorMessage("Please enter a username and email address.");
                } else if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if (
                        !(\Idno\Core\site()->config()->emailIsBlocked($email)) &&
                        !($emailuser = \Idno\Entities\User::getByEmail($email)) &&
                        !($handleuser = \Idno\Entities\User::getByHandle($handle)) &&
                        !empty($handle) && strlen($handle) <= 32 &&
                        preg_match('/^[a-zA-Z0-9_]{1,}$/', $handle) &&
                        !substr_count($handle, '/') &&
                        $password == $password2 &&
                        \Idno\Entities\User::checkNewPasswordStrength($password)
                    ) {
                        $user         = new \Idno\Entities\User();
                        $user->email  = $email;
                        $user->handle = strtolower(trim($handle)); // Trim the handle and set it to lowercase
                        $user->setPassword($password);
                        $user->notifications['email'] = 'all';
                        if (empty($name)) {
                            $name = $user->handle;
                        }
                        $user->setTitle($name);
                        if (!\Idno\Entities\User::get()) {
                            $user->setAdmin(true);
                            $user->robot_state = '1'; // State for our happy robot helper
                            if (\Idno\Core\site()->config()->title == 'New Known site') {
                                if (!empty($_SESSION['set_name'])) {
                                    \Idno\Core\site()->config()->title = $_SESSION['set_name'];
                                } else {
                                    \Idno\Core\site()->config()->title = $user->getTitle() . '\'s Known';
                                }
                                \Idno\Core\site()->config()->theme             = 'Solo';
                                \Idno\Core\site()->config()->open_registration = false;
                                \Idno\Core\site()->config()->from_email        = $user->email;
                                \Idno\Core\site()->config()->save();
                            }
                            \Idno\Core\site()->triggerEvent('site/firstadmin', array('user' => $user)); // Event hook for first admin
                        } else {
                            \Idno\Core\site()->triggerEvent('site/newuser', array('user' => $user)); // Event hook for new user
                        }
                        $user->save();
                        // Now we can remove the invitation
                        if (!empty($invitation)) {
                            if ($invitation instanceof Invitation) {
                                $invitation->delete(); // Remove the invitation; it's no longer needed
                            }
                        }
                    } else {
                        if (empty($handle)) {
                            \Idno\Core\site()->session()->addErrorMessage("Please create a username.");
                        }
                        if (strlen($handle) > 32) {
                            \Idno\Core\site()->session()->addErrorMessage("Your username is too long.");
                        }
                        if (!preg_match('/^[a-zA-Z0-9_]{1,}$/', $handle)) {
                            \Idno\Core\site()->session()->addErrorMessage("Usernames can only have letters, numbers and underscores.");
                        }
                        if (substr_count($handle, '/')) {
                            \Idno\Core\site()->session()->addErrorMessage("Usernames can't contain a slash ('/') character.");
                        }
                        if (!empty($handleuser)) {
                            \Idno\Core\site()->session()->addErrorMessage("Unfortunately, someone is already using that username. Please choose another.");
                        }
                        if (!empty($emailuser)) {
                            \Idno\Core\site()->session()->addErrorMessage("Hey, it looks like there's already an account with that email address. Did you forget your login?");
                        }
                        if (!\Idno\Entities\User::checkNewPasswordStrength($password) || $password != $password2) {
                            \Idno\Core\site()->session()->addErrorMessage("Please check that your passwords match and that your password is at least 7 characters long.");
                        }
                    }
                } else {
                    \Idno\Core\site()->session()->addErrorMessage("That doesn't seem like it's a valid email address.");
                }

                if (!empty($user->_id)) {
                    \Idno\Core\site()->session()->addMessage("You've registered! You're ready to get started. Why not add a status update to say hello?");
                    \Idno\Core\site()->session()->logUserOn($user);
                    if (empty($onboarding)) {
                        $this->forward();
                    } else {
                        $this->forward(\Idno\Core\site()->config()->getURL() . 'begin/profile');
                    }
                } else {
                    \Idno\Core\site()->session()->addMessageAtStart("We couldn't register you.");
                    $this->forward($_SERVER['HTTP_REFERER']);
                }

            }

        }

    }