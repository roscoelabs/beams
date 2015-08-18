<?php

    /* @var \Idno\Common\Entity $vars ['object'] */
    $replies = $vars['object']->countAnnotations('reply');
    $likes = $vars['object']->countAnnotations('like');
    $has_liked = false;
    if ($like_annotations = $vars['object']->getAnnotations('like')) {
        foreach ($like_annotations as $like) {
            if (\Idno\Core\site()->session()->isLoggedOn()) {
                if ($like['owner_url'] == \Idno\Core\site()->session()->currentUser()->getDisplayURL()) {
                    $has_liked = true;
                }
            }
        }
    }

?>
<div class="permalink">
    <p>
        <?= $this->draw('content/edit') ?>
        <?= $this->draw('content/end/links') ?>
        <?php

            if (\Idno\Core\site()->currentPage()->isPermalink() && \Idno\Core\site()->config()->indieweb_citation) {

                ?>
                <span class="citation"><?= $vars['object']->getCitation() ?></span>
            <?php

            }

        ?>
    </p>
</div>
<div class="interactions">
	<span class="annotate-icon">
    <?php

        if ($vars['object']->access != 'PUBLIC') {
            ?><i class="fa fa-lock"> </i><?php
        }

    ?>
    <?php
        if (!$has_liked) {
            $heart_only = '<i class="fa fa-star-o"></i>';
        } else {
            $heart_only = '<i class="fa fa-star"></i>';
        }
        if ($likes == 1) {
            $heart_text = '1 star';
        } else {
            $heart_text = $likes . ' stars';
        }
        $heart = $heart_only . ' ' . $heart_text;
        if (\Idno\Core\site()->session()->isLoggedOn()) {
            echo \Idno\Core\site()->actions()->createLink(\Idno\Core\site()->config()->getDisplayURL() . 'annotation/post', $heart_only, ['type' => 'like', 'object' => $vars['object']->getUUID()], ['method' => 'POST', 'class' => 'stars']);
            ?>
            <a class="stars" href="<?= $vars['object']->getDisplayURL() ?>#comments"><?= $heart_text ?></a>
        <?php
        } else {
            ?>
            <a class="stars" href="<?= $vars['object']->getDisplayURL() ?>#comments"><?= $heart ?></a></span>
        <?php
        }
    ?>
    	    <span class="annotate-icon"><a class="comments" href="<?= $vars['object']->getDisplayURL() ?>#comments"><i class="fa fa-comments"></i> <?php

            //echo $replies;
            if ($replies == 1) {
                echo '1 comment';
            } else {
                echo $replies . ' comments';
            }

        ?></a></span>
    <a class="shares" href="<?= $vars['object']->getDisplayURL() ?>#comments"><?php if ($shares = $vars['object']->countAnnotations('share')) {
            echo '<i class="fa fa-retweet"></i> ' . $shares;
        } ?></a>
    <a class="rsvps" href="<?= $vars['object']->getDisplayURL() ?>#comments"><?php if ($rsvps = $vars['object']->countAnnotations('rsvp')) {
            echo '<i class="fa fa-calendar-o"></i> ' . $rsvps;
        } ?></a>
</div>
<br clear="all"/>
<?php

    if (\Idno\Core\site()->currentPage()->isPermalink()) {

        if (!empty($likes) || !empty($replies) || !empty($shares) || !empty($rsvps)) {

            ?>

            <div class="annotations">

                <a name="comments"></a>
                <?= $this->draw('content/end/annotations') ?>
                <?php

                    if ($replies = $vars['object']->getAnnotations('reply')) {
                        echo $this->__(['annotations' => $replies])->draw('entity/annotations/replies');
                    }
                    if ($likes = $vars['object']->getAnnotations('like')) {
                        echo $this->__(['annotations' => $likes])->draw('entity/annotations/likes');
                    }
                    if ($shares = $vars['object']->getAnnotations('share')) {
                        echo $this->__(['annotations' => $shares])->draw('entity/annotations/shares');
                    }
                    if ($rsvps = $vars['object']->getAnnotations('rsvp')) {
                        echo $this->__(['annotations' => $rsvps])->draw('entity/annotations/rsvps');
                    }

                ?>

            </div>

        <?php

        }

        echo $this->draw('entity/annotations/comment/main');

        echo $this->draw('content/syndication/links');

    } else {

        if (\Idno\Core\site()->session()->isLoggedOn()) {
            echo $this->draw('entity/annotations/comment/mini');
        }

    }

?>
