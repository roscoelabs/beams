<?php

    /**
     * User profile editing for onboarding
     */

    namespace Idno\Pages\Onboarding {

        class Profile extends \Idno\Common\Page
        {

            function getContent()
            {

                $this->gatekeeper();

                $user = \Idno\Core\site()->session()->currentUser();

                $t = \Idno\Core\site()->template();
                echo $t->__(array(

                    'title'    => "Create your profile",
                    'body'     => $t->__(array('user' => $user))->draw('onboarding/profile'),
                    'messages' => \Idno\Core\site()->session()->getAndFlushMessages()

                ))->draw('shell/simple');

            }

        }

    }