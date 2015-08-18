<?php

    namespace Idno\Pages\Admin\Export {

        use Idno\Common\Page;
        use Idno\Core\Email;
        use Idno\Core\Migration;
        use Idno\Entities\File;

        class Generate extends Page
        {

            function getContent()
            {
                $this->forward(\Idno\Core\site()->config()->getURL() . 'admin/export/');
            }

            function postContent()
            {

                $this->adminGatekeeper();

                // Flag that a site export has been requested
                \Idno\Core\site()->config()->export_last_requested = time();
                \Idno\Core\site()->config()->export_in_progress    = 1;
                \Idno\Core\site()->config()->save();

                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/export/', false);

                ignore_user_abort(true);    // This is dangerous, but we need export to continue

                session_write_close();

                header('Connection: close');
                header('Content-length: ' . (string)ob_get_length());

                @ob_end_flush();            // Return output to the browser
                @ob_end_clean();
                @flush();

                sleep(10);                  // Pause

                set_time_limit(0);          // Eliminate time limit - this could take a while

                // Remove the previous export file
                if (!empty(\Idno\Core\site()->config()->export_file_id)) {
                    if ($file = File::getByID(\Idno\Core\site()->config()->export_file_id)) {
                        $file->remove();
                        \Idno\Core\site()->config()->export_file_id  = false;
                        \Idno\Core\site()->config()->export_filename = false;
                        \Idno\Core\site()->config()->save();
                    }
                }

                if ($path = Migration::createCompressedArchive()) {

                    $filename = \Idno\Core\site()->config()->host . '.zip';
                    /*                    header('Content-disposition: attachment;filename=' . $filename);
                                        if ($fp = fopen($path, 'r')) {
                                            while ($content = fread($fp, 4096)) {
                                                echo $content;
                                            }
                                        }
                                        fclose($fp);*/

                    if ($file = File::createFromFile($path, $filename)) {
                        @unlink($path);
                        \Idno\Core\site()->config()->export_filename    = $filename;
                        \Idno\Core\site()->config()->export_file_id     = $file;
                        \Idno\Core\site()->config()->export_in_progress = 0;
                        \Idno\Core\site()->config()->save();

                        $mail = new Email();
                        $mail->setHTMLBodyFromTemplate('admin/export');
                        $mail->setTextBodyFromTemplate('admin/export');
                        $mail->addTo(\Idno\Core\site()->session()->currentUser()->email);
                        $mail->setSubject("Your data export is ready");
                        $mail->send();
                    }

                    exit;

                }

            }

        }

    }