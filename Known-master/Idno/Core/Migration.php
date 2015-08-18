<?php

    namespace Idno\Core {

        use Idno\Common\Entity;
        use Idno\Entities\File;

        class Migration extends \Idno\Common\Component
        {

            /**
             * Prepares an archive containing all of this site's data.
             * @return string
             */
            static function exportToFolder($dir = false)
            {

                set_time_limit(0);  // Switch off the time limit for PHP
                site()->currentPage()->setPermalink(true);

                // Prepare a unique name for the archive
                $name = md5(time() . rand(0, 9999) . site()->config()->getURL());

                // If $folder is false or doesn't exist, use the temporary directory and ensure it has a slash on the end of it
                if (!is_dir($dir)) {
                    $dir = site()->config()->getTempDir();
                }

                // Make the temporary directory, or fail out
                if (!@mkdir($dir . $name)) {
                    return false;
                }
                $json_path = $dir . $name . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR;
                if (!@mkdir($json_path)) {
                    return false;
                }
                $html_path = $dir . $name . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR;
                if (!@mkdir($html_path)) {
                    return false;
                }
                $file_path = $dir . $name . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
                if (!@mkdir($file_path)) {
                    return false;
                }

                if (!@mkdir($file_path . 'readable', 0777, true)) {
                    return false;
                }
                if (!@mkdir($file_path . 'uploads', 0777, true)) {
                    return false;
                }

                // If we've made it here, we've created a temporary directory with the hash name

                $config = array(
                    'url'   => site()->config()->getURL(),
                    'title' => site()->config()->getTitle()
                );

                file_put_contents($dir . $name . DIRECTORY_SEPARATOR . 'known.json', json_encode($config));
                $all_in_one_json = '';

                // Let's export everything.
                $fields           = array();
                $query_parameters = array('entity_subtype' => array('$not' => array('$in' => array('Idno\Entities\ActivityStreamPost'))));
                $collection       = 'entities';
                if ($results = site()->db()->getRecords($fields, $query_parameters, 99999, 0, $collection)) {
                    foreach ($results as $id => $row) {
                        $object = site()->db()->rowToEntity($row);
                        if (!empty($object->_id) && $object instanceof Entity) {
                            $object_name = $object->_id;
                            $attachments = $object->attachments;
                            if (empty($attachments)) {
                                $attachments = [];
                            }
                            foreach(['thumbnail','thumbnail_large'] as $thumbnail)
                            if (!empty($object->$thumbnail)) {
                                if (preg_match('/file\/([a-zA-Z0-9]+)\//', $object->$thumbnail, $matches)) {
                                    $attachments[] = [
                                        'url' => $object->$thumbnail,
                                        '_id' => $matches[1]
                                    ];
                                }
                            }
                            if (!empty($attachments)) {
                                foreach ($attachments as $key => $attachment) {
                                    if ($data = File::getFileDataFromAttachment($attachment)) {
                                        $filename = $attachment['_id'];
                                        $id       = $attachment['_id'];
                                        if ($ext = pathinfo($attachment['url'], PATHINFO_EXTENSION)) {
                                            $filename .= '.' . $ext;
                                        }
                                        if (!empty($attachment['mime-type'])) {
                                            $mime_type = $attachment['mime-type'];
                                        } else {
                                            $mime_type = 'application/octet-stream';
                                        }
                                        file_put_contents($file_path . 'readable/' . $filename, $data);
                                        $attachments[$key]['url'] = '../files/' . site()->config()->pathHost() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3] . '/' . $id . '.file'; //$filename;
                                        $data_file                = $file_path . 'uploads/' . \Idno\Core\site()->config()->pathHost() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3] . '/' . $id . '.data';
                                        foreach (array($file_path . 'uploads/' . \Idno\Core\site()->config()->pathHost(), $file_path . \Idno\Core\site()->config()->pathHost() . '/' . $id[0], $file_path . 'uploads/' . \Idno\Core\site()->config()->pathHost() . '/' . $id[0] . '/' . $id[1], $file_path . 'uploads/' . \Idno\Core\site()->config()->pathHost() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2], $file_path . 'uploads/' . \Idno\Core\site()->config()->pathHost() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3]) as $up_path) {
                                            if (!is_dir($up_path)) {
                                                $result = mkdir($up_path, 0777, true);
                                            }
                                        }
                                        file_put_contents($file_path . 'uploads/' . site()->config()->pathHost() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3] . '/' . $id . '.file', $data);
                                        file_put_contents($data_file, json_encode(['filename' => $filename, 'mime_type' => $mime_type]));
                                    }
                                }
                                $object->attachments = $attachments;
                            }
                            $activityStreamPost = new \Idno\Entities\ActivityStreamPost();
                            if ($owner = $object->getOwner()) {
                                $activityStreamPost->setOwner($owner);
                                $activityStreamPost->setActor($owner);
                                $activityStreamPost->setTitle(sprintf("%s posted %s", $owner->getTitle(), $object->getTitle()));
                            }
                            $activityStreamPost->setVerb('post');
                            $activityStreamPost->setObject($object);
                            $json_object = json_encode($object);
                            file_put_contents($json_path . $object_name . '.json', $json_object);
                            $all_in_one_json[] = json_decode($json_object);
                            if (is_callable(array($object, 'draw'))) {
                                //file_put_contents($html_path . $object_name . '.html', $activityStreamPost->draw());
                                file_put_contents($html_path . $object_name . '.html', $object->draw());
                            }
                            //unset($results[$id]);
                            //unset($object);
                            gc_collect_cycles();    // Clean memory
                        }
                    }
                }

                if ($exported_records = \Idno\Core\site()->db()->exportRecords()) {
                    if (site()->config()->database == 'mysql' || site()->config()->database == 'postgres') {
                        $export_ext = 'sql';
                    } else {
                        $export_ext = 'json';
                    }
                    file_put_contents($dir . $name . DIRECTORY_SEPARATOR . 'exported_data.' . $export_ext, $exported_records);
                }

                file_put_contents($dir . $name . DIRECTORY_SEPARATOR . 'entities.json', json_encode($all_in_one_json));

                // As we're successful, return the unique name of the archive
                return $dir . $name;

            }

            /**
             * Given the path to a Known export, creates a complete .tar.gz file and returns the path to that.
             * If $save_path is false, will save to the temporary folder.
             *
             * @param $path
             * @param $save_path
             * @return string
             */
            static function archiveExportFolder($path, $save_path = false)
            {

                if (!is_dir($path)) {
                    return false;
                }
                if (substr($path, -1) != DIRECTORY_SEPARATOR) {
                    $path .= DIRECTORY_SEPARATOR;
                }
                if (!file_exists($path . 'known.json')) {
                    return false;
                }
                if (!class_exists('PharData')) {
                    return false;
                }

                $filename = str_replace('.', '_', site()->config()->host);

                if (file_exists(site()->config()->getTempDir() . $filename . '.tar')) {
                    @unlink(site()->config()->getTempDir() . $filename . '.tar');
                    @unlink(site()->config()->getTempDir() . $filename . '.tar.gz');
                }

                $archive = new \PharData(site()->config()->getTempDir() . $filename . '.zip');
                $archive->buildFromDirectory($path);

                //$archive->compress(\Phar::GZ);

                return $archive->getPath();

            }

            /**
             * Wrapper function that exports Known data and returns the path to the archive of it.
             * @return bool|string
             */
            static function createCompressedArchive()
            {
                if ($path = self::exportToFolder()) {
                    if ($archive = self::archiveExportFolder($path)) {
                        self::cleanUpFolder($path);

                        return $archive;
                    }
                }

                return false;
            }

            /**
             * Given the path to an archive folder, recursively removes it
             * @param $path
             */
            static function cleanUpFolder($path)
            {
                foreach (glob("{path}/*") as $file) {
                    if (is_dir($file)) {
                        self::cleanUpFolder($file);
                    } else {
                        @unlink($file);
                    }
                }
                @rmdir($path);
            }

            /**
             * Given the XML source of an export, imports each post into Known.
             * @param $xml
             */
            static function importFeedXML($xml)
            {

                // Blogger will be imported as blog posts, so make sure we can import those ...
                if (!($text = site()->plugins()->get('Text'))) {
                    return false;
                }

                $xml_parser = new \SimplePie();
                $xml_parser->set_raw_data($xml);
                $xml_parser->init();

                if ($items = $xml_parser->get_items()) {

                    foreach ($items as $item) {
                        /* @var \SimplePie_Item $item */

                        $post_type = 'post';
                        if ($categories = $item->get_categories()) {
                            foreach ($categories as $category) {
                                if (!empty($category->term) && !empty($category->scheme)) {
                                    if ($category->scheme == 'http://schemas.google.com/g/2005#kind') {
                                        foreach (['settings', 'template', 'comment'] as $term) {
                                            if (substr_count($category->term, $term)) {
                                                $post_type = $term;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($post_type == 'post') {
                            $body = $item->get_content();
                            $tags = [];
                            if ($categories = $item->get_categories()) {
                                foreach ($categories as $category) {
                                    if (empty($category->scheme) || $category->scheme != 'http://schemas.google.com/g/2005#kind') {
                                        $tags[] = '#' . preg_replace('/\W+/', '', $category->term);
                                    }
                                }
                            }
                            if (!empty($tags)) {
                                $body .= '<p>' . implode(' ', $tags) . '</p>';
                            }

                            self::importImagesFromBodyHTML($body, 'blogspot.com');

                            $object = new \IdnoPlugins\Text\Entry();
                            $object->setTitle(html_entity_decode($item->get_title()));
                            $object->created = strtotime(($item->get_date("c")));
                            $object->body    = ($body);
                            $object->save(true);
                        }

                    }

                }

            }

            static function importImagesFromBodyHTML($body, $src_url)
            {

                $doc = new \DOMDocument();
                if (@$doc->loadHTML($body)) {
                    if ($images = $doc->getElementsByTagName('img')) {
                        foreach ($images as $image) {
                            $src = $image->getAttribute('src');
                            if (substr_count($src, $src_url)) {
                                $dir     = site()->config()->getTempDir();
                                $name    = md5($src);
                                $newname = $dir . $name . basename($src);
                                if (@file_put_contents($newname, fopen($src, 'r'))) {
                                    switch (strtolower(pathinfo($src, PATHINFO_EXTENSION))) {
                                        case 'jpg':
                                        case 'jpeg':
                                            $mime = 'image/jpg';
                                            break;
                                        case 'gif':
                                            $mime = 'image/gif';
                                            break;
                                        case 'png':
                                            $mime = 'image/png';
                                            break;
                                        default:
                                            $mime = 'application/octet-stream';
                                    }
                                    if ($file = File::createFromFile($newname, basename($src), $mime, true)) {
                                        $newsrc = \Idno\Core\site()->config()->getURL() . 'file/' . $file->file['_id'];
                                        $body   = str_replace($src, $newsrc, $body);
                                        @unlink($newname);
                                    }

                                }
                            }
                        }
                    }
                }

            }

            /**
             * Given the XML source of a Blogger export, imports each post into Known.
             * @param $xml
             */
            static function importBloggerXML($xml)
            {

                return self::importFeedXML($xml);

            }

            /**
             * Given the XML source of a WordPress export, imports each post into Known.
             * @param $xml
             */
            static function importWordPressXML($xml)
            {

                // XML will be imported as blog posts, so make sure we can import those ...
                if (!($text = site()->plugins()->get('Text'))) {
                    return false;
                }

                if ($data = simplexml_load_string($xml, null, LIBXML_NOCDATA)) {

                    $namespaces   = $data->getDocNamespaces(false);
                    $namespaces[] = null;

                    unset($namespace_data);
                    unset($xml);

                    if (!empty($data->channel->item)) {
                        foreach ($data->channel->item as $item_structure) {
                            $item = [];
                            foreach ($namespaces as $ns => $namespace) {
                                if ($properties = (array)$item_structure->children($namespace)) {
                                    foreach ($properties as $name => $val) {
                                        if (!empty($ns)) {
                                            $name = $ns . ':' . $name;
                                        }
                                        $item[$name] = $val;
                                    }
                                }
                            }

                            if ($item['wp:post_type'] == 'post' && $item['wp:status'] == 'publish') {

                                $title = $item['title'];
                                if (!empty($item['content:encoded'])) {
                                    $body = $item['content:encoded'];
                                } else if (!empty($item['description'])) {
                                    $body = $item['description'];
                                } else {
                                    $body = '';
                                }
                                if (!empty($item['wp:post_date'])) {
                                    $published = strtotime($item['wp:post_date']);
                                } else if (!empty($item['pubDate'])) {
                                    $published = strtotime($item['pubDate']);
                                } else {
                                    $published = time();
                                }
                                if (!empty($item['category'])) {
                                    $tags = [];
                                    if (!is_array($item['category'])) {
                                        $item['category'] = [$item['category']];
                                    }
                                    foreach ($item['category'] as $category) {
                                        $category = strtolower(trim($category));
                                        if ($category != 'general') {
                                            $tags[] = '#' . preg_replace('/\W+/', '', $category);
                                        }
                                    }
                                    if (!empty($tags)) {
                                        $body .= '<p>' . implode(' ', $tags) . '</p>';
                                    }
                                }

                                self::importImagesFromBodyHTML($body, parse_url($item['link'], PHP_URL_HOST));

                                $object = new \IdnoPlugins\Text\Entry();
                                $object->setTitle(html_entity_decode($title));
                                $object->created = $published;
                                $object->body    = ($body);
                                $object->save(true);

                            }

                        }
                    }

                }

            }


        }

    }