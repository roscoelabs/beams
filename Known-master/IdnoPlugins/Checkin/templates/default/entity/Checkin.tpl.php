<?php

    $object = $vars['object'];
    /*
     * @var \Idno\Common\Entity $object
     */

?>

<div class="">

    <h2 class="p-geo">
        <?php
            if (empty($vars['feed_view'])) {
                ?>
                <a href="<?= $object->getURL() ?>" class="p-name"><?= $object->getTitle() ?></a>
            <?php

            }

            ?>
        <span class="h-geo">
            <data class="p-latitude" value="<?= $object->lat ?>"></data>
            <data class="p-longitude" value="<?= $object->long ?>"></data>
        </span>
    </h2>

<?php

    if (empty($vars['feed_view'])) {

        ?>
        <div id="map_<?= $object->_id ?>" style="height: 250px"></div>
    <?php
    }
    ?>
    <div class="p-map">
        <?php
            if (!empty($object->body)) {
                echo $this->autop($this->parseURLs($this->parseHashtags($object->body)));
            }

            if (!empty($object->tags)) {
                ?>

                <p class="tag-row"><i class="icon-tag"></i> <?= $this->parseHashtags($object->tags) ?></p>

            <?php } ?>
    </div>

</div>
<?php

    if (empty($vars['feed_view'])) {

        ?>
        <script>
            var map<?=$object->_id?> = L.map('map_<?=$object->_id?>', {
                touchZoom: false,
                scrollWheelZoom: false
            }).setView([<?=$object->lat?>, <?=$object->long?>], 16);
            var layer<?=$object->_id?> = new L.StamenTileLayer("toner-lite");
            map<?=$object->_id?>.addLayer(layer<?=$object->_id?>);
            var marker<?=$object->_id?> = L.marker([<?=$object->lat?>, <?=$object->long?>]);
            marker<?=$object->_id?>.addTo(map<?=$object->_id?>);
            //map<?=$object->_id?>.zoomControl.disable();
            map<?=$object->_id?>.scrollWheelZoom.disable();
            map<?=$object->_id?>.touchZoom.disable();
            map<?=$object->_id?>.doubleClickZoom.disable();
        </script>
    <?php
    }