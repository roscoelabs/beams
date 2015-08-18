<?php

    namespace Idno\Files {

        /*
         * Class LocalFileSystem
         * A file system capable of storing files on the local disk
         * @package Idno\Files
         */

        class LocalFileSystem extends FileSystem
        {

            /**
             * Find a file.
             * @param $id
             * @return mixed
             */
            public function findOne($id)
            {
                // Get path to load from
                $path = rtrim(\Idno\Core\site()->config()->uploadpath, ' /') . '/';

                if (is_array($id)) {
                    if (!empty($id['_id'])) {
                        $id = $id['_id'];
                    }
                }

                $upload_file = $path . \Idno\Core\site()->config()->getFileBaseDirName() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3] . '/' . $id . '.file';
                $data_file   = $path . \Idno\Core\site()->config()->getFileBaseDirName() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3] . '/' . $id . '.data';

                if (file_exists($upload_file)) {
                    $file                    = new \Idno\Files\LocalFile();
                    $file->_id               = $id;
                    $file->internal_filename = $upload_file;
                    if ($metadata = file_get_contents($data_file)) {
                        if ($metadata = json_decode($metadata, true)) {
                            $file->metadata       = $metadata;
                            $file->file           = $metadata;
                            $file->file['_id']    = $id;
                            $file->file['length'] = filesize($upload_file);
                        }
                    }

                    return $file;
                }

                return false;
            }

            /**
             * Store the file at $file_path with $metadata and $options
             * @param $file_path
             * @param $metadata
             * @param $options
             * @return \Idno\Files\File
             */
            public function storeFile($file_path, $metadata, $options)
            {
                if (file_exists($file_path) && $path = \Idno\Core\site()->config()->uploadpath) {

                    // Encode metadata for saving
                    $metadata = json_encode($metadata);

                    // Generate a random ID
                    $id = md5(time() . $metadata);

                    // Generate save path
                    if ($path[sizeof($path) - 1] != '/') {
                        $path .= '/';
                    }
                    $upload_file = $path . \Idno\Core\site()->config()->getFileBaseDirName() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3] . '/' . $id . '.file';
                    $data_file   = $path . \Idno\Core\site()->config()->getFileBaseDirName() . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3] . '/' . $id . '.data';

                    foreach (array($path . \Idno\Core\site()->config()->getFileBaseDirName(), $path . \Idno\Core\site()->config()->host . '/' . $id[0], $path . \Idno\Core\site()->config()->host . '/' . $id[0] . '/' . $id[1], $path . \Idno\Core\site()->config()->host . '/' . $id[0] . '/' . $id[1] . '/' . $id[2], $path . \Idno\Core\site()->config()->host . '/' . $id[0] . '/' . $id[1] . '/' . $id[2] . '/' . $id[3]) as $up_path) {
                        if (!is_dir($up_path)) {
                            $result = mkdir($up_path, 0777, true);
                        }
                    }

                    copy($file_path, $upload_file);
                    file_put_contents($data_file, $metadata);

                    return $id;

                }

                return false;
            }

        }

    }