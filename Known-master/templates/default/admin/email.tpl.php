<div class="row">

    <div class="col-md-10 col-md-offset-1">
        <?= $this->draw('admin/menu') ?>
        <h1>Email settings</h1>

        <div class="explanation">
            <p>
                Known tries to send email using your server's default email settings. If you'd like it to do
                something else - for example, if you'd like to send email using an external provider - enter the
                new SMTP settings below. You can also
                <a href="<?=\Idno\Core\site()->config()->getDisplayURL()?>account/settings/notifications/">change your notification settings</a>.
            </p>
        </div>
    </div>
    <div class="col-md-10 col-md-offset-1">
        <form action="<?= \Idno\Core\site()->config()->getDisplayURL() ?>admin/email" class="form-horizontal"
              method="post">

            <div class="row">
                <div class="col-md-10 email-settings">
                    <p>
                        Fill in the site email address if you would like your site to send email.
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <p class="control-label" for="name"><strong>Site email address</strong></p>
                </div>
                <div class="col-md-4">
                    <input type="text" id="from_email" placeholder="Site email address" class="form-control" name="from_email"
                           value="<?= htmlspecialchars(\Idno\Core\site()->config()->from_email) ?>">
                </div>
                <div class="col-md-6">
                    <p class="config-desc">This is the address that every notification will be sent from.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 email-settings">
                    <p>
                        You can often leave the following settings blank. However, you may wish to set them if you're
                        using a third-party service to send email.
                    </p>
                </div>
            </div>


            <div class="row">
                <div class="col-md-2">
                    <p class="control-label" for="name"><strong>SMTP host</strong></p>
                </div>
                <div class="col-md-4">
                    <input type="text" id="smtp_host" placeholder="SMTP host" class="form-control" name="smtp_host"
                           value="<?= htmlspecialchars(\Idno\Core\site()->config()->smtp_host) ?>">
                </div>
                <div class="col-md-6">
                    <p class="config-desc">This is the address of the server that will send email for you.</p>
                </div>
            </div>


            <div class="row">
                <div class="col-md-2">
                    <p class="control-label" for="name"><strong>SMTP username</strong></p>
                </div>
                <div class="col-md-4">
                    <input type="text" id="smtp_username" placeholder="SMTP username" class="form-control" name="smtp_username"
                           value="<?= htmlspecialchars(\Idno\Core\site()->config()->smtp_username) ?>">
                </div>
                <div class="col-md-6">
                    <p class="config-desc">If your mail server needs a username, enter it here.</p>
                </div>
            </div>


            <div class="row">
                <div class="col-md-2">
                    <p class="control-label" for="name"><strong>SMTP password</strong></p>
                </div>
                <div class="col-md-4">
                    <input type="password" id="smtp_password" placeholder="SMTP password" class="form-control"
                           name="smtp_password"
                           value="<?= htmlspecialchars(\Idno\Core\site()->config()->smtp_password) ?>">
                </div>
                <div class="col-md-6">
                    <p class="config-desc">If your mail server needs a password, enter it here.</p>
                </div>
            </div>


            <div class="row">
                <div class="col-md-2">
                    <p class="control-label" for="name"><strong>SMTP port</strong></p>
                </div>
                <div class="col-md-4">
                    <input type="text" id="smtp_port" placeholder="SMTP password" class="form-control" name="smtp_port"
                           value="<?php

                               $port = (int)\Idno\Core\site()->config()->smtp_port;
                               if (empty($port)) {
                                   $port = 25;
                               }
                               echo $port;

                           ?>">
                </div>
                <div class="col-md-6">
                    <p class="config-desc">
                        This is normally 25 or 587.
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-2">
                    <p class="control-label" for="name"><strong>Secure connection</strong></p>
                </div>
                <div class="col-md-4">
                    <select name="smtp_secure">
                        <?php
                            foreach ([
                                         'No'        => false,
                                         'Yes (TLS)' => 'tls',
                                         'Yes (SSL)' => 'ssl'
                                     ] as $field => $value) {
                                ?>
                                <option
                                    value="<?= $value; ?>" <?php if (\Idno\Core\site()->config()->smtp_secure === $value) {
                                    echo "selected";
                                } ?>><?= $field; ?></option>
                            <?php
                            }
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <p class="config-desc">Select yes if you use secure logins to your mail server.</p>
                </div>
            </div>


            <div class="control-group">
                <div class="controls-save">
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </div>
            </div>

            <?= \Idno\Core\site()->actions()->signForm('/admin/email') ?>
        </form>
    </div>

    <?php if (\Idno\Core\site()->config()->from_email) { ?>
    <div class="col-md-10 col-md-offset-1" style="margin-top: 5em">
                <form action="<?= \Idno\Core\site()->config()->getDisplayURL() ?>admin/emailtest" class="form-horizontal"
                      method="post">

                    <div class="row">
                        <div class="col-md-2">
                            <p class="control-label" for="to_email"><strong>Send a test message to:</strong></p>
                        </div>
                        <div class="col-md-4">
                            <input type="text" id="to_email" placeholder="To address" class="form-control" name="to_email"
                                   value="<?= htmlspecialchars(\Idno\Core\site()->config()->from_email) ?>">
                        </div>
                        <div class="col-md-4">
                            <p class="config-desc">Check your email settings by sending a test email.</p>
                        </div>
                    </div>

                    <div class="control-group">
                        <div class="controls-save">
                            <button type="submit" class="btn btn-primary">Test settings</button>
                        </div>
                    </div>
                    <?= \Idno\Core\site()->actions()->signForm('/admin/emailtest') ?>
                </form>
        </div>
    <?php } ?>
</div>