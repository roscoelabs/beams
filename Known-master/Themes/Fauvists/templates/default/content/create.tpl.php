<?php

    if (!empty($vars['contentTypes']) && is_array($vars['contentTypes'])) {

        ?>
        <div class="buttonBar">
        <div class="row ">
        <div class="col-md-12">
        <div id="contentTypeButtonBar">
            <?php

                foreach ($vars['contentTypes'] as $contentType) {
                    /* @var Idno\Common\ContentType $contentType */
                    ?>

                    <a class="contentTypeButton" id="<?= $contentType->getClassSelector() ?>Button"
                       href="<?= $contentType->getEditURL() ?>"
                       onclick="contentCreateForm('<?= $contentType->camelCase($contentType->getEntityClassName()) ?>'); return false;">
                        <span class="contentTypeLogo"><?= $contentType->getIcon() ?></span>
                        
                    </a>
                    
                    <!--<?= $contentType->getTitle() ?>-->

                <?php

                }

            ?>
            <br clear="all" style="line-height: 0em"/>
        </div>
    <?php

    }

?>
    </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div id="contentCreate"></div>
        </div>
    </div>
</div>
<?php

    if (empty($vars['items']) && sizeof($vars['contentTypes']) <= 1 &&
        \Idno\Core\site()->session()->isLoggedIn() &&
        \Idno\Core\site()->session()->currentUser()->isAdmin()) {

        ?>
        <div class="row" style="margin-top: 5em">
            <div class="col-md-6 col-md-offset-3">
                <div class="welcome">
                    <p>
                        <a href="https://withknown.com/" target="_blank"><img src="https://withknown.com/img/logo_k.png" style="width: 4em; border: 0"></a>
                    </p>
                    <p>
                        Welcome to your Known site!<br />
                        <a href="<?=\Idno\Core\site()->config()->getDisplayURL()?>admin/">Click here to start configuring your site</a>.
                    </p>
                </div>
            </div>
        </div>

<?php

    }

?>
<a name="feed"></a>