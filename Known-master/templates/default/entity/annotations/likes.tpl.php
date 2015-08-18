<?php

    if (!empty($vars['annotations']) && is_array($vars['annotations'])) {
        foreach($vars['annotations'] as $locallink => $annotation) {

            $permalink = $annotation['permalink'] ? $annotation['permalink'] : $locallink;

            str_replace('~','.',$permalink);    // This is temporarily here to clean up some issues with a previous PR
                                                // TODO: remove this

            ?>
            <div class="idno-annotation row">
                <div class="idno-annotation-image col-md-1 hidden-sm">
                    <p>
                        <a href="<?=$annotation['owner_url']?>" class="icon-container"><img src="<?=\Idno\Core\site()->config()->sanitizeAttachmentURL($annotation['owner_image'])?>" /></a>
                    </p>
                </div>
                <div class="idno-annotation-content col-md-6">
                    <p>
                        <a href="<?=htmlspecialchars($annotation['owner_url'])?>"><?=htmlentities($annotation['owner_name'], ENT_QUOTES, 'UTF-8')?></a>
                        liked this post
                    </p>
                    <p><small><a href="<?=$permalink?>"><?=date('M d Y', $annotation['time']);?></a> on <a href="<?=$permalink?>"><?=parse_url($permalink, PHP_URL_HOST)?></a></small></p>
                </div>
            </div>
            <?php
                $this->annotation_permalink = $locallink;

                if ($vars['object']->canEditAnnotation($annotation)) {
                    echo $this->draw('content/annotation/edit');
                }
            ?>
        <?php

        }
    }