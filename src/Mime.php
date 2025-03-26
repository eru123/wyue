<?php

namespace Wyue;

class Mime
{
    public const MIME_CSS = 'text/css';
    public const MIME_JS = 'text/javascript';
    public const MIME_JSON = 'application/json';
    public const MIME_XML = 'application/xml';
    public const MIME_XSL = 'application/xml';
    public const MIME_XSLT = 'application/xml';
    public const MIME_XHTML = 'application/xhtml+xml';
    public const MIME_XHT = 'application/xhtml+xml';
    public const MIME_HTML = 'text/html';
    public const MIME_HTM = 'text/html';
    public const MIME_TXT = 'text/plain';
    public const MIME_CSV = 'text/csv';
    public const MIME_TSV = 'text/tab-separated-values';
    public const MIME_GIF = 'image/gif';
    public const MIME_JPG = 'image/jpeg';
    public const MIME_JPEG = 'image/jpeg';
    public const MIME_JPE = 'image/jpeg';
    public const MIME_PNG = 'image/png';
    public const MIME_BMP = 'image/bmp';
    public const MIME_ICO = 'image/x-icon';
    public const MIME_TIF = 'image/tiff';
    public const MIME_TIFF = 'image/tiff';
    public const MIME_SVG = 'image/svg+xml';
    public const MIME_WOFF = 'application/font-woff';
    public const MIME_WOFF2 = 'application/font-woff2';
    public const MIME_TTF = 'application/font-sfnt';
    public const MIME_OTF = 'application/font-sfnt';
    public const MIME_EOT = 'application/vnd.ms-fontobject';

    /**
     * Convert ext to Mime type.
     *
     * @param string $ext File extension
     *
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
     *
     * @param string $mime MIME type
     *
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
