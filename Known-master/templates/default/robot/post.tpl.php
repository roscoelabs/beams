<div class="row idno-entry idno-entry-helper">
    <div
        class="col-md-10 col-md-offset-1 idno-helper idno-object idno-content">
        <div class="e-content entry-content">

            <div class="robot-head" style="width: 100px; height: 130px; float: left">
                <p style="text-align: center">
                    <img src="<?=\Idno\Core\site()->config()->getDisplayURL()?>gfx/robots/1.png"/></a><br/>
                    Aleph
                </p>
            </div>

            <div class="col-md-10 robot-murmur">
                <?= $this->autop($vars['body']) ?>
                <div class="robot-footer">
                    <p>
                        <?php

                            echo \Idno\Core\site()->actions()->createLink(\Idno\Core\site()->config()->getDisplayURL() . 'robot/remove', "Power down robots. I can take it from here.");

                        ?>
                    </p>
                </div>
            </div>
            <br clear="both">
        </div>
    </div>
</div>