<?php

namespace Wyue;

use Exception;

class Vite
{
    /**
     * @var string Use React Template
     */
    const TEMPLATE_REACT = 'react';

    /**
     * @var string Use Vite Template for production
     */
    const TEMPLATE_VITE = 'vite';
    
    /**
     * @var string Use Vite Template for development
     */
    const TEMPLATE_DEV = 'dev';

    protected static $str_tpl_name = null;
    protected static $str_template = null;
    protected static $str_manifest = null;
    protected static $arr_head = [];
    protected static $arr_body = [];
    protected static $arr_data = [];
    protected static $dir_src = null;
    protected static $dir_pub = null;
    protected static $dir_dis = null;

    /**
     * Return the dev template for react
     * @return string
     */
    protected static function template_react(): string
    {
        return <<<HTML
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            \${head}
        </head>
        <body>
            <div id="\${app_id}"></div>
            \${body}
            <script type="module">
                import RefreshRuntime from \'\${base_uri}/@react-refresh\'
                RefreshRuntime.injectIntoGlobalHook(window)
                window.\$RefreshReg\$ = () => { }
                window.\$RefreshSig\$ = () => (type) => type
                window.__vite_plugin_react_preamble_installed__ = true
            </script>
            <script type="module" src="\${base_uri}/@vite/client"></script>
            <script type="module" src="\${base_uri}/\${entry}"></script>
        </body>
        </html>
        HTML;
    }

    /**
     * Return the dev template for vite, this should work on all frameworks except react
     * @return string
     */
    protected static function template_dev()
    {
        return <<<HTML
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            \${head}
        </head>
        <body>
            <div id="\${app_id}"></div>
            \${body}
            <script type="module" src="\${base_uri}/@vite/client"></script>
            <script type="module" src="\${base_uri}/\${entry}"></script>
        </body>
        </html>
        HTML;
    }

    /**
     * Return the production template for vite, this should work on all frameworks
     * @return string
     */
    protected static function template_vite()
    {
        return <<<HTML
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            \${head}
        </head>
        <body>
            <div id="\${app_id}"></div>
            \${body}
            <script type="module" src="\${base_uri}/\${entry}"></script>
        </body>
        </html>
        HTML;
    }

    /**
     * Set the template to use
     * @param string $mode The template to use (vite, react, dev) vite for production, react for react dev, dev for vite dev
     * @return void
     */
    public static function template(string $mode = 'vite')
    {
        switch ($mode) {
            case 'react':
                static::$str_template = static::template_react();
                static::$str_tpl_name = 'react';
                break;
            case 'dev':
                static::$str_template = static::template_dev();
                static::$str_tpl_name = 'dev';
                break;
            case 'vite':
            default:
                static::$str_tpl_name = 'vite';
                static::$str_template = static::template_vite();
                break;
        }
    }

    /**
     * Set key value pair of data to be used in the template
     * @param string $key The key to be used in the template
     * @param string $value The value to be used in the template
     * @return void
     */
    public static function set(string $key, string $value)
    {
        static::$arr_data[$key] = $value;
    }

    /**
     * Add a raw html string to the head of the template
     * @param string $html The html string to be added to the head
     * @return void
     */
    public static function head(string $html)
    {
        static::$arr_head[] = $html;
    }

    /**
     * Add a raw html string to the body of the template
     * @param string $html The html string to be added to the body
     * @return void
     */
    public static function body(string $html)
    {
        static::$arr_body[] = $html;
    }

    /**
     * Set the path to the manifest.json file
     * @param string $path The path to the manifest.json file
     * @return void
     */
    public static function manifest(string $path)
    {
        static::$str_manifest = $path;
    }

    /**
     * Set the path to the src directory
     * @param string $path The path to the src directory
     * @return void
     */
    public static function src(string $path)
    {
        static::$dir_src = $path;
    }

    /**
     * Set the path to the public directory
     * @param string $path The path to the public directory
     * @return void
     */
    public static function public(string $path)
    {
        static::$dir_pub = $path;
    }

    /**
     * Set the path to the dist directory
     * @param string $path The path to the dist directory
     * @return void
     */
    public static function dist(string $path)
    {
        static::$dir_dis = $path;
    }

    /**
     * Render the template into html
     * @param array $data A key value pair of data to be used in the template
     * @param bool $minify Whether to minify the html or not
     * @return string The rendered html
     */
    public static function render(array $data = [], bool $minify = true): string
    {
        if (is_null(static::$str_template)) {
            static::template();
        }

        $tpl_data = $data + static::$arr_data + ['body' => implode('', static::$arr_body), 'head' => implode('', static::$arr_head)];
        $dev = in_array(static::$str_tpl_name, ['react', 'dev']);

        if (!isset($tpl_data['app_id'])) {
            switch (static::$str_tpl_name) {
                case 'react':
                    $tpl_data['app_id'] = 'root';
                    break;
                case 'dev':
                    $tpl_data['app_id'] = 'app';
                    break;
                default:
                    $tpl_data['app_id'] = 'app';
                    break;
            }
        }

        if (!isset($tpl_data['base_uri'])) {
            $tpl_data['base_uri'] = $dev ? 'http://localhost:5173' : '';
        }

        if (!isset($tpl_data['entry'])) {
            $tpl_data['entry'] = 'src/main.js';
        }

        if (!$dev) {
            $manifest_path = realpath(static::$str_manifest);
            if (!$manifest_path || !file_exists($manifest_path)) {
                // throw new Exception('Manifest file not found');
                return static::hotReloadHtml();
            }

            $manifest = json_decode(file_get_contents($manifest_path), true);
            $entry = $tpl_data['entry'];
            if (!isset($manifest[$entry]) || !isset($manifest[$entry]['isEntry']) || !$manifest[$entry]['isEntry']) {
                throw new Exception('Invalid manifest file or entry file path');
            }

            $css = isset($manifest[$entry]['css']) ? $manifest[$entry]['css'] : [];
            $tpl_data['entry'] = $manifest[$entry]['file'];

            foreach ($css as $css_file) {
                $tpl_data['head'] .= '<link rel="stylesheet" href="' . $tpl_data['base_uri'] . '/' . $css_file . '">';
            }
        }

        $html = Format::template(
            static::$str_template,
            $tpl_data,
            FORMAT_TEMPLATE_DOLLAR_CURLY
        );

        $html = Format::template(
            $html,
            $tpl_data,
            FORMAT_TEMPLATE_DOLLAR_CURLY
        );

        return $minify ? preg_replace(['/ {2,}/', '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'], [' ', ''], $html) : $html;
    }

    public static function seo(array $data)
    {
        if (isset($data['url'])) {
            static::head('<meta property="og:url" content="' . $data['url'] . '">');
        }

        if (isset($data['type'])) {
            static::head('<meta property="og:type" content="' . $data['type'] . '">');
            static::head('<meta name="twitter:card" content="' . $data['type'] . '">');
        }

        if (isset($data['title'])) {
            static::head('<meta property="og:title" content="' . $data['title'] . '">');
            static::head('<meta name="title" content="' . $data['title'] . '">');
            static::head('<title>' . $data['title'] . '</title>');
        }

        if (isset($data['description'])) {
            static::head('<meta property="og:description" content="' . $data['description'] . '">');
            static::head('<meta name="description" content="' . $data['description'] . '">');
        }

        if (isset($data['image'])) {
            static::head('<meta property="og:image" content="' . $data['image'] . '">');
            static::head('<meta name="image" content="' . $data['image'] . '">');
        }

        if (isset($data['app_id'])) {
            static::head('<meta property="fb:app_id" content="' . $data['app_id'] . '">');
        }

        if (isset($data['locale'])) {
            static::head('<meta property="og:locale" content="' . $data['locale'] . '">');
            static::head('<meta name="locale" content="' . $data['locale'] . '">');
        }

        if (isset($data['keywords'])) {
            static::head('<meta name="keywords" content="' . (is_array($data['keywords']) ? implode(',', $data['keywords']) : '') . '">');
        }

        if (isset($data['author'])) {
            static::head('<meta name="author" content="' . $data['author'] . '">');
        }

        if (isset($data['publisher'])) {
            static::head('<meta name="publisher" content="' . $data['publisher'] . '">');
        }

        if (isset($data['robots'])) {
            static::head('<meta name="robots" content="' . $data['robots'] . '">');
        }

        if (isset($data['canonical'])) {
            static::head('<link rel="canonical" href="' . $data['canonical'] . '">');
        }

        if (isset($data['prev'])) {
            static::head('<link rel="prev" href="' . $data['prev'] . '">');
        }

        if (isset($data['next'])) {
            static::head('<link rel="next" href="' . $data['next'] . '">');
        }

        if (isset($data['alternate'])) {
            static::head('<link rel="alternate" href="' . $data['alternate'] . '">');
        }

        if (isset($data['amphtml'])) {
            static::head('<link rel="amphtml" href="' . $data['amphtml'] . '">');
        }

        if (isset($data['manifest'])) {
            static::head('<link rel="manifest" href="' . $data['manifest'] . '">');
        }

        if (isset($data['mask-icon'])) {
            static::head('<link rel="mask-icon" href="' . $data['mask-icon'] . '">');
        }

        if (isset($data['theme-color'])) {
            static::head('<meta name="theme-color" content="' . $data['theme-color'] . '">');
        }

        if (isset($data['apple-mobile-web-app-capable'])) {
            static::head('<meta name="apple-mobile-web-app-capable" content="' . $data['apple-mobile-web-app-capable'] . '">');
        }

        if (isset($data['apple-mobile-web-app-status-bar-style'])) {
            static::head('<meta name="apple-mobile-web-app-status-bar-style" content="' . $data['apple-mobile-web-app-status-bar-style'] . '">');
        }

        if (isset($data['apple-mobile-web-app-title'])) {
            static::head('<meta name="apple-mobile-web-app-title" content="' . $data['apple-mobile-web-app-title'] . '">');
        }

        if (isset($data['msapplication-TileColor'])) {
            static::head('<meta name="msapplication-TileColor" content="' . $data['msapplication-TileColor'] . '">');
        }

        if (isset($data['msapplication-TileImage'])) {
            static::head('<meta name="msapplication-TileImage" content="' . $data['msapplication-TileImage'] . '">');
        }

        if (isset($data['msapplication-config'])) {
            static::head('<meta name="msapplication-config" content="' . $data['msapplication-config'] . '">');
        }

        if (isset($data['application-name'])) {
            static::head('<meta name="application-name" content="' . $data['application-name'] . '">');
        }

        if (isset($data['full-screen'])) {
            static::head('<meta name="full-screen" content="' . $data['full-screen'] . '">');
        }

        if (isset($data['browser-mode'])) {
            static::head('<meta name="browser-mode" content="' . $data['browser-mode'] . '">');
        }

        if (isset($data['night-mode'])) {
            static::head('<meta name="night-mode" content="' . $data['night-mode'] . '">');
        }

        if (isset($data['layout-mode'])) {
            static::head('<meta name="layout-mode" content="' . $data['layout-mode'] . '">');
        }

        if (isset($data['screen-orientation'])) {
            static::head('<meta name="screen-orientation" content="' . $data['screen-orientation'] . '">');
        }

        if (isset($data['color-scheme'])) {
            static::head('<meta name="color-scheme" content="' . $data['color-scheme'] . '">');
        }

        if (isset($data['viewport-fit'])) {
            static::head('<meta name="viewport-fit" content="' . $data['viewport-fit'] . '">');
        }

        if (isset($data['google-site-verification'])) {
            static::head('<meta name="google-site-verification" content="' . $data['google-site-verification'] . '">');
        }

        if (isset($data['yandex-verification'])) {
            static::head('<meta name="yandex-verification" content="' . $data['yandex-verification'] . '">');
        }

        if (isset($data['msvalidate.01'])) {
            static::head('<meta name="msvalidate.01" content="' . $data['msvalidate.01'] . '">');
        }

        if (isset($data['alexaVerifyID'])) {
            static::head('<meta name="alexaVerifyID" content="' . $data['alexaVerifyID'] . '">');
        }

        if (isset($data['p:domain_verify'])) {
            static::head('<meta name="p:domain_verify" content="' . $data['p:domain_verify'] . '">');
        }

        if (isset($data['norton-safeweb-site-verification'])) {
            static::head('<meta name="norton-safeweb-site-verification" content="' . $data['norton-safeweb-site-verification'] . '">');
        }

        if (isset($data['csrf-token'])) {
            static::head('<meta name="csrf-token" content="' . $data['csrf-token'] . '">');
        }

        if (isset($data['csrf-param'])) {
            static::head('<meta name="csrf-param" content="' . $data['csrf-param'] . '">');
        }

        if (isset($data['referrer'])) {
            static::head('<meta name="referrer" content="' . $data['referrer'] . '">');
        }
    }

    public static function data(array $data)
    {
        Vite::body('<script type="module">window.__SERVER_DATA__ = {...(window?.__SERVER_DATA__ || {}), ...' . json_encode($data) . '};</script>');
    }

    /**
     * Inject the Vite handler into the app
     * @param Router $router The reference to the router object
     * @param array $data A key value pair of data to be used in the template
     * @return void
     */
    // public static function inject(Router &$router, array $data = [], $minify = true)
    // {
    //     $prod = !in_array(static::$str_tpl_name, ['dev', 'react']);
    //     $router->bootstrap(function (Context $ctx) use ($data, $minify) {
    //         $ctx->vite = function (array $data2 = []) use ($data, $minify) {
    //             return static::render($data2 + $data, $minify);
    //         };
    //     });

    //     if ($prod) {
    //         $router->static('/', static::$dir_dis);
    //     } else {
    //         $router->static('/', static::$dir_pub);
    //         $router->static('/src/', static::$dir_src);
    //     }
    // }

    public static function hotReloadHtml(): string 
    {
        return preg_replace(['/ {2,}/', '/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'], [' ', ''], <<<HTML
            <html>
                <head>
                    <meta charset="UTF-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                </head>
                <body>
                    Please wait while the app is building
                    <script>
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    </script>
                </body>
            </html>
        HTML);
    }
}
