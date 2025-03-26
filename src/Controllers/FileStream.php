<?php

namespace Wyue\Controllers;

use Wyue\Mime;

trait FileStream
{
    /**
     * Manually handling file streaming.
     *
     * @param string  $file File path
     * @param ?string $name (Optional) File name
     */
    public function streamFile(string $file, ?string $name = null): void
    {
        if (file_exists($file)) {
            if (headers_sent()) {
                exit;
            }

            $disposition = 'inline';
            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($file);
                $mime = $mime ?: 'application/octet-stream';
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file);
                $mime = $mime ?: 'application/octet-stream';
                finfo_close($finfo);
            } else {
                $mime = 'application/octet-stream';
            }

            if (function_exists('pathinfo')) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mime = Mime::ext2mime($ext, $mime);
            }

            if (empty($name)) {
                $name = basename($file);
            }

            $filesize = filesize($file);
            $etag = hash_file('sha256', $file);
            $tz = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $last_modified = date('D, d M Y H:i:s T', filemtime($file));
            date_default_timezone_set($tz);

            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
                header('HTTP/1.1 304 Not Modified');

                exit;
            }

            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $last_modified) {
                header('HTTP/1.1 304 Not Modified');

                exit;
            }

            if (
                isset($_SERVER['HTTP_IF_MODIFIED_SINCE'], $_SERVER['HTTP_IF_NONE_MATCH'])
                && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $last_modified
                && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag
            ) {
                header('HTTP/1.1 304 Not Modified');

                exit;
            }

            header('Content-Type: '.$mime);
            header('Content-Disposition: '.$disposition.'; filename="'.$name.'"');
            header('Content-Length: '.$filesize);
            header('Last-Modified: '.$last_modified);
            header('ETag: '.$etag);
            header('Cache-Control: public, max-age=1800');
            header('Expires: '.gmdate('D, d M Y H:i:s T', time() + 1800));
            header('Pragma: public');
            readfile($file);
        }

        exit;
    }
}
