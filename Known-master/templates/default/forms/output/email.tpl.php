<?php

    $href = filter_var($vars['value'], FILTER_SANITIZE_EMAIL);
    if ($vars['label']) $vars['value'] = $vars['label'];
    
?>
<a href="mailto:<?php echo $href; ?>"><?php echo $this->__($vars)->draw('forms/output/text'); ?></a>