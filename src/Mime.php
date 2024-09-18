<?php

namespace Wyue;

class Mime
{
    const MIME_CSS = 'text/css';
    const MIME_JS = 'text/javascript';
    const MIME_JSON = 'application/json';
    const MIME_XML = 'application/xml';
    const MIME_XSL = 'application/xml';
    const MIME_XSLT = 'application/xml';
    const MIME_XHTML = 'application/xhtml+xml';
    const MIME_XHT = 'application/xhtml+xml';
    const MIME_HTML = 'text/html';
    const MIME_HTM = 'text/html';
    const MIME_TXT = 'text/plain';
    const MIME_CSV = 'text/csv';
    const MIME_TSV = 'text/tab-separated-values';
    const MIME_GIF = 'image/gif';
    const MIME_JPG = 'image/jpeg';
    const MIME_JPEG = 'image/jpeg';
    const MIME_JPE = 'image/jpeg';
    const MIME_PNG = 'image/png';
    const MIME_BMP = 'image/bmp';
    const MIME_ICO = 'image/x-icon';
    const MIME_TIF = 'image/tiff';
    const MIME_TIFF = 'image/tiff';
    const MIME_SVG = 'image/svg+xml';
    const MIME_WOFF = 'application/font-woff';
    const MIME_WOFF2 = 'application/font-woff2';
    const MIME_TTF = 'application/font-sfnt';
    const MIME_OTF = 'application/font-sfnt';
    const MIME_EOT = 'application/vnd.ms-fontobject';

    /**
     * Convert ext to Mime type
     * @param string $ext File extension
     * @return string Mime type
     */
    public static function ext2mime(string $ext, string $default = 'application/octet-stream'): string
    {
        return match (strtolower($ext)) {
            'css' => self::MIME_CSS,
            'js' => self::MIME_JS,
            'json' => self::MIME_JSON,
            'xml' => self::MIME_XML,
            'xsl' => self::MIME_XSL,
            'xslt' => self::MIME_XSLT,
            'xhtml' => self::MIME_XHTML,
            'xht' => self::MIME_XHT,
            'html' => self::MIME_HTML,
            'htm' => self::MIME_HTM,
            'txt' => self::MIME_TXT,
            'csv' => self::MIME_CSV,
            'tsv' => self::MIME_TSV,
            'gif' => self::MIME_GIF,
            'jpg' => self::MIME_JPG,
            'jpeg' => self::MIME_JPEG,
            'jpe' => self::MIME_JPE,
            'png' => self::MIME_PNG,
            'bmp' => self::MIME_BMP,
            'ico' => self::MIME_ICO,
            'tif' => self::MIME_TIF,
            'tiff' => self::MIME_TIFF,
            'svg' => self::MIME_SVG,
            'woff' => self::MIME_WOFF,
            'woff2' => self::MIME_WOFF2,
            'ttf' => self::MIME_TTF,
            'otf' => self::MIME_OTF,
            'eot' => self::MIME_EOT,
            default => $default,
        };
    }

    /**
     * Convert a MIME type to an extension.
     * @param string $mime MIME type
     * @return string Extension
     */
    public static function mime2ext(string $mime): string
    {
        return match (strtolower($mime)) {
            self::MIME_CSS => 'css',
            self::MIME_JS => 'js',
            self::MIME_JSON => 'json',
            self::MIME_XML => 'xml',
            self::MIME_XSL => 'xsl',
            self::MIME_XSLT => 'xslt',
            self::MIME_XHTML => 'xhtml',
            self::MIME_XHT => 'xht',
            self::MIME_HTML => 'html',
            self::MIME_HTM => 'htm',
            self::MIME_TXT => 'txt',
            self::MIME_CSV => 'csv',
            self::MIME_TSV => 'tsv',
            self::MIME_GIF => 'gif',
            self::MIME_JPG => 'jpg',
            self::MIME_JPEG => 'jpeg',
            self::MIME_JPE => 'jpe',
            self::MIME_PNG => 'png',
            self::MIME_BMP => 'bmp',
            self::MIME_ICO => 'ico',
            self::MIME_TIF => 'tif',
            self::MIME_TIFF => 'tiff',
            self::MIME_SVG => 'svg',
            self::MIME_WOFF => 'woff',
            self::MIME_WOFF2 => 'woff2',
            self::MIME_TTF => 'ttf',
            self::MIME_OTF => 'otf',
            self::MIME_EOT => 'eot',
            default => '',
        };
    }
}
