<?php

    $username = \Idno\Core\site()->session()->currentUser()->getHandle();
    $url = \Idno\Core\site()->session()->currentUser()->getDisplayURL();

    $facebookurl = "https://www.facebook.com/sharer/sharer.php?u=".urlencode(\Idno\Core\site()->config()->getDisplayURL());
    $twitterurl = "https://twitter.com/intent/tweet?text=".urlencode("Check out my new @withknown site!")."&url=".urlencode(\Idno\Core\site()->config()->getDisplayURL())."&source=webclient";

    switch (\Idno\Core\site()->session()->currentUser()->robot_state) {

        case '1':
            echo $this->__(array(
                'body' =>
                    "Welcome to your new Known site! " .
                    "I'm Aleph, your very own welcome robot. Let's get started by <a href=\"#\" onclick=\"contentCreateForm('status'); return false;\">adding your first status update</a>! "

                    //"about what you did today. Just select the icon above."
            ))->draw('robot/post');
            break;
        case '2a':
            echo $this->__(array(
                'body' => "Beep! That was a great update. Why not <a href=\"{$facebookurl}\" target=\"blank\" onclick=\"window.open('{$facebookurl}', 'newwindow', 'width=600, height=350'); return false;\">share your new website on Facebook</a> and <a href=\"{$twitterurl}\">Twitter</a> so your friends know about it?</a>\n\nI bet you've got some great photos. <a href=\"#\" onclick=\"contentCreateForm('photo'); return false;\">Try posting one</a>!"
            ))->draw('robot/post');
            break;
        case '2b':
            echo $this->__(array(
                'body' => "Zeep! That was a great update. Why not <a href=\"{$facebookurl}\" target=\"blank\" onclick=\"window.open('{$facebookurl}', 'newwindow', 'width=600, height=350'); return false;\">share your new website on Facebook</a> and <a href=\"{$twitterurl}\">Twitter</a> so your friends know about it?</a>\n\nI bet you've got some great photos. <a href=\"#\" onclick=\"contentCreateForm('photo'); return false;\">Try posting one</a>!"
            ))->draw('robot/post');
            break;
        case '2c':
            echo $this->__(array(
                'body' => "Beep boop! That was a great update. Why not <a href=\"{$facebookurl}\" target=\"blank\" onclick=\"window.open('{$facebookurl}', 'newwindow', 'width=600, height=350'); return false;\">share your new website on Facebook</a> and <a href=\"{$twitterurl}\">Twitter</a> so your friends know about it?</a>\n\nI bet you've got some great photos. <a href=\"#\" onclick=\"contentCreateForm('photo'); return false;\">Try posting one</a>!"
            ))->draw('robot/post');
            break;
        case "3a":
            echo $this->__(array(
                'body' => "Beepity boop! That was a great picture. Did you see that you can also <a href=\"".\Idno\Core\site()->session()->currentUser()->getDisplayURL()."/edit\">update your profile</a>?"
            ))->draw('robot/post');
            break;
        case "3b":
            echo $this->__(array(
                'body' => "Boopity beep! Did you see that you can also <a href=\"".\Idno\Core\site()->session()->currentUser()->getDisplayURL()."/edit\">update your profile</a>?"
            ))->draw('robot/post');
            break;
        case '4':
            echo $this->__(array(
                'body' => "01011001 01101111 00100000 01111001 01101111 00100000 01111001 01101111 \n\nThat's how you say hello where I come from. I wanted to remind you that you can also <a href=\"".\Idno\Core\site()->config()->getDisplayURL()."admin/themes/\">change the theme of your site</a>. If you ever have feedback, you can <a href=\"".\Idno\Core\site()->config()->getDisplayURL()."account/settings/feedback/\">send a message to my human creators</a>."
            ))->draw('robot/post');
            break;

    }
    if (\Idno\Core\site()->currentPage() instanceof \Idno\Pages\Homepage) {
        if (!empty(\Idno\Core\site()->session()->currentUser()->robot_state)) {
            if (in_array(\Idno\Core\site()->session()->currentUser()->robot_state,['3a','3b','2c','4'])) {
                $user = \Idno\Core\site()->session()->currentUser();
                switch($user->robot_state) {
                    case '3a':
                    case '3b':
                        $user->robot_state = '4';
                        break;
                    case '2c':
                        $user->robot_state = '3b';
                        break;
                    case '4':
                        $user->robot_state = 0;
                        break;
                }
                $user->save();
            }
        }
    }