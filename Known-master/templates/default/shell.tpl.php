<?php

    $messages = $vars['messages'];

    header('Content-type: text/html');
    header("Access-Control-Allow-Origin: *");

    if (empty($vars['title']) && !empty($vars['description'])) {
        $vars['title'] = implode(' ', array_slice(explode(' ', strip_tags($vars['description'])), 0, 10));
    }

    // Use appropriate language
    $lang = 'en';
    if (!empty(\Idno\Core\site()->config()->lang))
        $lang = \Idno\Core\site()->config()->lang;
?>
<!DOCTYPE html>
<html lang="<?= $lang; ?>">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($vars['title']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="initial-scale=1.0" media="(device-height: 568px)"/>
    <meta name="description" content="<?= htmlspecialchars(strip_tags($vars['description'])) ?>">
    <meta name="generator" content="Known https://withknown.com">
    <meta http-equiv="Content-Language" content="<?= $lang; ?>">

    <?= $this->draw('shell/icons'); ?>
    <?= $this->draw('shell/favicon'); ?>

    <?php

        if (\Idno\Core\site()->session()->isLoggedIn()) {

            ?>
            <!-- <link rel="manifest" href="<?= \Idno\Core\site()->config()->getDisplayURL() ?>chrome/manifest.json"> -->
        <?php
            if (Idno\Core\site()->isSecure()) {
        ?>
            <!-- <script>
                window.addEventListener('load', function () {
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('<?=\Idno\Core\site()->config()->getDisplayURL()?>chrome/service-worker.js', {scope: '/'})
                            .then(function (r) {
                                console.log('Registered service worker');
                            })
                            .catch(function (whut) {
                                console.error('Could not register service worker');
                                console.error(whut);
                            });
                    }
                });
            </script> -->
        <?php
        }

        }

        $opengraph = array(
            'og:type'      => 'website',
            'og:title'     => htmlspecialchars(strip_tags($vars['title'])),
            'og:site_name' => htmlspecialchars(strip_tags(\Idno\Core\site()->config()->title)),
            'og:image'     => Idno\Core\site()->currentPage()->getIcon()
        );

        if (\Idno\Core\site()->currentPage() && \Idno\Core\site()->currentPage()->isPermalink()) {

            $opengraph['og:url'] = \Idno\Core\site()->currentPage()->currentUrl();

            if (!empty($vars['object'])) {
                $owner  = $vars['object']->getOwner();
                $object = $vars['object'];

                $opengraph['og:title']       = htmlspecialchars(strip_tags($vars['object']->getTitle()));
                $opengraph['og:description'] = htmlspecialchars($vars['object']->getShortDescription());
                $opengraph['og:type']        = 'article'; //htmlspecialchars($vars['object']->getActivityStreamsObjectType());
                $opengraph['og:image']       = $vars['object']->getIcon(); //$owner->getIcon(); //Icon, for now set to being the author profile pic

                if ($icon = $vars['object']->getIcon()) {
                    if ($icon_file = \Idno\Entities\File::getByURL($icon)) {
                        if (!empty($icon_file->metadata['width'])) {
                            $opengraph['og:image:width'] = $icon_file->metadata['width'];
                        }
                        if (!empty($icon_file->metadata['height'])) {
                            $opengraph['og:image:height'] = $icon_file->metadata['height'];
                        }
                    }
                }

                if ($url = $vars['object']->getDisplayURL()) {
                    $opengraph['og:url'] = $vars['object']->getDisplayURL();
                }
            }

        }

        foreach ($opengraph as $key => $value)
            echo "<meta property=\"$key\" content=\"$value\" />\n";

    ?>


    <!-- Dublin Core -->
    <link rel="schema.DC" href="http://purl.org/dc/elements/1.1/">
    <meta name="DC.title" content="<?= htmlspecialchars($vars['title']) ?>">
    <meta name="DC.description" content="<?= htmlspecialchars($vars['description']) ?>"><?php

        if (\Idno\Core\site()->currentPage() && \Idno\Core\site()->currentPage()->isPermalink()) {
            /* @var \Idno\Common\Entity $object */
            if ($object instanceof \Idno\Common\Entity) {

                if ($creator = $object->getOwner()) {
                    ?>
                    <meta name="DC.creator" content="<?= htmlentities($creator->getTitle()) ?>"><?php
                }
                if ($created = $object->created) {
                    ?>
                    <meta name="DC.date" content="<?= date('c', $created) ?>"><?php
                }
                if ($url = $object->getDisplayURL()) {
                    ?>
                    <meta name="DC.identifier" content="<?= htmlspecialchars($url) ?>"><?php
                }

            }
        }

    ?>

    <!-- We need jQuery at the top of the page -->
    <script src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/jquery/' ?>jquery.min.js"></script>

    <!-- Le styles -->
    <link href="<?= \Idno\Core\site()->config()->getStaticURL() . 'external/bootstrap/' ?>assets/css/bootstrap.min.css"
          rel="stylesheet" />
    <link href="<?= \Idno\Core\site()->config()->getStaticURL() . 'external/bootstrap/' ?>assets/css/bootstrap-theme.min.css" />
    <script src="<?= \Idno\Core\site()->config()->getStaticURL() . 'external/bootstrap/' ?>assets/js/bootstrap.min.js"></script>

    <!-- Accessibility -->
    <link rel="stylesheet" href="<?= \Idno\Core\site()->config()->getStaticURL() . 'external/paypal-bootstrap-accessibility-plugin/' ?>plugins/css/bootstrap-accessibility_1.0.3.css">
    <script  src="<?= \Idno\Core\site()->config()->getStaticURL() . 'external/paypal-bootstrap-accessibility-plugin/' ?>plugins/js/bootstrap-accessibility_1.0.3.min.js"></script>

    <!-- Fonts -->
    <link rel="stylesheet"
          href="<?= \Idno\Core\site()->config()->getStaticURL() ?>external/font-awesome/css/font-awesome.css">
    <!--<link rel="stylesheet"
          href="<?= \Idno\Core\site()->config()->getStaticURL() ?>external/font-awesome/css/font-awesome.min.css">-->
    <style>
        body {
            padding-top: 100px; /* 60px to make the container go all the way to the bottom of the topbar */
        }
    </style>
    
    <?=$this->draw('shell/css');?>

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script
        src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/bootstrap/' ?>assets/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Default Known JavaScript -->
    <script src="<?= \Idno\Core\site()->config()->getStaticURL() . 'js/default.js?20150406' ?>"></script>

    <!-- To silo is human, to syndicate divine -->
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($vars['title']) ?>"
          href="<?= $this->getURLWithVar('_t', 'rss'); ?>"/>
    <link rel="feed" type="application/rss+xml" title="<?= htmlspecialchars($vars['title']) ?>"
          href="<?= $this->getURLWithVar('_t', 'rss'); ?>"/>
    <link rel="alternate feed" type="application/rss+xml"
          title="<?= htmlspecialchars(\Idno\Core\site()->config()->title) ?>: all content"
          href="<?= \Idno\Core\site()->config()->getDisplayURL() ?>content/all?_t=rss"/>
    <link rel="feed" type="text/html" title="<?= htmlspecialchars(\Idno\Core\site()->config()->title) ?>"
          href="<?= \Idno\Core\site()->config()->getDisplayURL() ?>content/all"/>

    <!-- Webmention endpoint -->
    <link href="<?= \Idno\Core\site()->config()->getURL() ?>webmention/" rel="http://webmention.org/"/>
    <link href="<?= \Idno\Core\site()->config()->getURL() ?>webmention/" rel="webmention"/>

    <?php $this->draw('shell/identities') ?>
    <?php if (!empty(\Idno\Core\site()->config()->hub)) { ?>
        <!-- Pubsubhubbub -->
        <link href="<?= \Idno\Core\site()->config()->hub ?>" rel="hub"/>
    <?php } ?>

    <?php
        // Load style assets
        if ((\Idno\Core\site()->currentPage()) && $style = \Idno\Core\site()->currentPage->getAssets('css')) {
            foreach ($style as $css) {
                ?>
                <link href="<?= $css; ?>" rel="stylesheet">
            <?php
            }
        }
    ?>

    <script src="<?= \Idno\Core\site()->config()->getStaticURL() ?>external/fragmention/fragmention.js"></script>

    <!-- Syndication -->
    <link
        href="<?= \Idno\Core\site()->config()->getStaticURL() ?>external/bootstrap-toggle/css/bootstrap-toggle.min.css"
        rel="stylesheet"/>
    <script
        src="<?= \Idno\Core\site()->config()->getStaticURL() ?>external/bootstrap-toggle/js/bootstrap-toggle.js"></script>

    <?= $this->draw('shell/head', $vars); ?>

</head>

<body class="<?php

    echo(str_replace('\\', '_', strtolower(get_class(\Idno\Core\site()->currentPage()))));
    if ($path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
        if ($path = explode('/', $path)) {
            $page_class = '';
            foreach ($path as $element) {
                if (!empty($element)) {
                    if (!empty($page_class)) {
                        $page_class .= '-';
                    }
                    $page_class .= $element;
                    echo ' page-' . $page_class;
                }
            }
        }
    }
    if (\Idno\Core\site()->session()->isLoggedIn()) {
        echo ' logged-in';
    } else {
        echo ' logged-out';
    }

?>">
<div id="pjax-container" class="page-container">
    <?php
        $currentPage = \Idno\Core\site()->currentPage();

        if (!empty($currentPage)) {
            $hidenav = \Idno\Core\site()->embedded(); //\Idno\Core\site()->currentPage()->getInput('hidenav');
        }
        if (empty($vars['hidenav']) && empty($hidenav)) {

            echo $this->draw('shell/toolbar/main');

        } else {

            ?>
            <style>
                body {
                    padding-top: 0px !important; /* 60px to make the container go all the way to the bottom of the topbar */
                }
            </style>
            <div style="height: 1em;"><br/></div>
        <?php

        } // End hidenav test
    ?>

    <div class="container page-body">
        <a name="pagecontent"></a>
        <?php

            if (!empty($messages)) {
                foreach ($messages as $message) {

                    ?>

                    <div class="alert <?= $message['message_type'] ?> col-md-10 col-md-offset-1">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?= $message['message'] ?>
                    </div>

                <?php

                }
            }

        ?>
        <?= $this->draw('shell/beforecontent') ?>
        <?= $vars['body'] ?>
        <?= $this->draw('shell/aftercontent') ?>

    </div>
    <!-- /container -->

    <?= $this->draw('shell/contentfooter') ?>

</div>
<!-- Everything below this should be includes, not content -->

<?php if (!$_SERVER["HTTP_X_PJAX"]): ?>
<!-- Le javascript -->
<!-- Placed at the end of the document so the pages load faster -->
<script
    src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/jquery-timeago/' ?>jquery.timeago.js"></script>
<script src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/jquery-pjax/' ?>jquery.pjax.js"></script>
<script src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/underscore/underscore-min.js' ?>"
        type="text/javascript"></script>
<script src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/mention/bootstrap-typeahead.js' ?>"
        type="text/javascript"></script>
<script src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/mention/mention.js' ?>"
        type="text/javascript"></script>


<!-- Flexible media player -->
<script
    src="<?= \Idno\Core\site()->config()->getDisplayURL() ?>external/mediaelement/build/mediaelement-and-player.min.js"></script>
<link rel="stylesheet"
      href="<?= \Idno\Core\site()->config()->getDisplayURL() ?>external/mediaelement/build/mediaelementplayer.css"/>

<?php

    if (\Idno\Core\site()->session()->isLoggedIn()) {

        ?>
        <!-- WYSIWYG editor -->
        <script src="<?= \Idno\Core\site()->config()->getDisplayURL() ?>external/tinymce/js/tinymce/tinymce.min.js"
                type="text/javascript"></script>
        <script
            src="<?= \Idno\Core\site()->config()->getDisplayURL() ?>external/tinymce/js/tinymce/jquery.tinymce.min.js"
            type="text/javascript"></script>
    <?php

    }

?>

<!-- Mention styles -->
<link rel="stylesheet" type="text/css"
      href="<?= \Idno\Core\site()->config()->getDisplayURL() ?>external/mention/recommended-styles.css">

<?php
    if (\Idno\Core\site()->session()->isLoggedOn()) {
        echo $this->draw('js/mentions');
    }
?>

<!-- Video shim -->
<script src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/fitvids/jquery.fitvids.min.js' ?>"></script>

<?php
    // Load javascript assets
    if ((\Idno\Core\site()->currentPage()) && $scripts = \Idno\Core\site()->currentPage->getAssets('javascript')) {
        foreach ($scripts as $script) {
            ?>
            <script src="<?= $script ?>"></script>
        <?php
        }
    }
?>

<!-- HTML5 form element support for legacy browsers -->
<script src="<?= \Idno\Core\site()->config()->getDisplayURL() . 'external/h5f/h5f.min.js' ?>"></script>

<script>

    function annotateContent() {
        $(".h-entry").fitVids();
        $("time.dt-published").timeago();
    }

    // Shim so that JS functions can get the current site URL
    function wwwroot() {
        return '<?=\Idno\Core\site()->config()->getDisplayURL()?>';
    }

    $(document).ready(function () {
        annotateContent();
    });

    /**
     * Handle Twitter tweet embedding
     */
    $(document).ready(function () {
        $('div.twitter-embed').each(function (index) {
            var url = $(this).attr('data-url');
            var div = $(this);

            $.ajax({
                url: "https://api.twitter.com/1/statuses/oembed.json?url=" + url,
                dataType: "jsonp",
                success: function (data) {
                    div.html(data['html']);
                }
            });
        });

        $('body').on('click', function (event, el) {
            var clickTarget = event.target;

            if (clickTarget.href && clickTarget.href.indexOf(window.location.origin) === -1) {
                clickTarget.target = "_blank";
            }
        });
    });
    
    /**
     * Handle Soundcloud oEmbed code
     */
    $(document).ready(function() {
	$('div.soundcloud-embed').each(function(index) {
	    var url = $(this).attr('data-url');
	    var div = $(this);
	    
	    $.getJSON('https://soundcloud.com/oembed?callback=?',
		    {
			format: 'js',
			url: url,
			iframe: true
		    },
		function(data) {
		    div.html(data['html']);
		}
	    );
	});
    });

    /**
     * Better handle links in iOS web applications.
     * This code (from the discussion here: https://gist.github.com/kylebarrow/1042026)
     * will prevent internal links being opened up in safari when known is installed
     * on an ios home screen.
     */
    (function (document, navigator, standalone) {
        if ((standalone in navigator) && navigator[standalone]) {
            var curnode, location = document.location, stop = /^(a|html)$/i;
            document.addEventListener('click', function (e) {
                curnode = e.target;
                while (!(stop).test(curnode.nodeName)) {
                    curnode = curnode.parentNode;
                }
                if ('href' in curnode && ( curnode.href.indexOf('http') || ~curnode.href.indexOf(location.host) ) && (!curnode.classList.contains('contentTypeButton'))) {
                    e.preventDefault();
                    location.href = curnode.href;
                }
            }, false);
        }
    })(document, window.navigator, 'standalone');

</script>

<?= $this->draw('shell/footer', $vars) ?>

</body>


</html>
<?php endif; ?>
