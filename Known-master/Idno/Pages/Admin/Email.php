<?php

    /**
     * Administration page: email settings
     */

    namespace Idno\Pages\Admin {

        class Email extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->adminGatekeeper(); // Admins only
                $t        = \Idno\Core\site()->template();
                $t->body  = $t->draw('admin/email');
                $t->title = 'Email';
                $t->drawPage();

            }

            function postContent()
            {
                $this->adminGatekeeper(); // Admins only

                $email                                             = $this->getInput('from_email');
                \Idno\Core\site()->config->config['smtp_host']     = $this->getInput('smtp_host');
                \Idno\Core\site()->config->config['smtp_username'] = $this->getInput('smtp_username');
                \Idno\Core\site()->config->config['smtp_password'] = $this->getInput('smtp_password');
                \Idno\Core\site()->config->config['smtp_port']     = (int)$this->getInput('smtp_port');
                \Idno\Core\site()->config->config['smtp_secure']   = $this->getInput('smtp_secure');

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    \Idno\Core\site()->config->config['from_email'] = $this->getInput('from_email');
                }

                \Idno\Core\site()->config()->save();
                $this->forward(\Idno\Core\site()->config()->getURL() . 'admin/email');
            }

        }

    }