<?php

if (!function_exists('scan_directory')) {
    /**
     * Scan directory abd return files and folders in the directory
     *
     * @param string $path
     * @param array $sub
     * @return array
     */
    function scan_directory(string $path, array $sub = [])
    {
        $result = [];
        $dirfiles = scandir($path);
        $files = array_diff($dirfiles, ['.', '..']);

        every($files, function ($filename) use (&$result, $sub, $path) {
            $filedir = $path . '/' . $filename;

            if (is_file($filedir)) {
                $result[] = (object) array(
                    'file' => $filedir,
                    'path' => $path,
                    'filename' => $filename,
                    'subdirs' => $sub
                );
            }

            if (is_dir($filedir)) {
                $sub[] = $filename;

                $result = array_merge(
                    $result,
                    scan_directory(
                        $path . '/' . $filename,
                        $sub
                    )
                );
            }
        });

        return $result;
    }
}
