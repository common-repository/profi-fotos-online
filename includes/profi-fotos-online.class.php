<?php

class PFO {
    private static $instance;

    private function __construct() {
    }

    private static $done = false;

    /**
     * the singleton method
     *
     * @return mixed
     */
    public static function singleton() {
        if (!isset( self::$instance )) {
            $c              = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     * registerSession()
     *
     * send first request to PFO and save the SESSION-ID
     * this will be secure the save and unique connection, save the basket etc.
     *
     * @param $aReq
     * @return bool|void
     */
    public function registerSession( $aReq ) {
        $mContent = $this->callPFOApi( $url = PFO_API_URL . '?register=1' );
        $oData    = json_decode( $mContent );
        $aData    = @get_object_vars( $oData );
        if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
            echo "<p><strong>\$aData form registerSession() CURL</strong></p>";
            echo "<pre>";
            print_r( $aData );
            echo "</pre>";
        }
        if (isset( $_SESSION['PFO_SESSION'] ) && $_SESSION['PFO_SESSION'] && $aData['PFO_SESSION'] != $_SESSION['PFO_SESSION']) {
            echo "<h3>PFO_SESSION stimmt nicht mit der gespeicherten ueberein</h3>";
            if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
                echo "<p>Raus in: <strong>" . __FILE__ . "</strong> / <strong>" . __LINE__ . "</strong></p>";
            }
            return;
        }
        if ($aData['WP_SESSION'] && $aData['WP_SESSION'] != $_SESSION['WP_SESSION']) {
            echo "<h1>WP_SESSION stimmt nicht mit der gespeicherten ueberein<br>DATA: " . $aData['WP_SESSION'] . "<br>SESS: " . $_SESSION['WP_SESSION'] . "</h1>";
            if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
                echo "<p>Raus in: <strong>" . __FILE__ . "</strong> / <strong>" . __LINE__ . "</strong></p>";
            }
            return;
        }
        $_SESSION['PFO_SESSION'] = $aData['PFO_SESSION'];
        if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
            echo "<p><strong>WP_SESSION: " . WP_SESSION . "</strong></p>";
            echo "<p><strong>\$_SESSION['PFO_SESSION']: " . $_SESSION['PFO_SESSION'] . "</strong></p>";
        }
        return true;
    }

    /**
     * getContent()
     *
     * invoking the cURL-Call
     *
     * @param $aReq
     * @param $atts
     * @return mixed
     */
    public function getContent( $aReq, $atts ) {
        if (isset( $this->done ) && $this->done) {
            echo "\n<!-- the PFO Shortcode was already called - will only fires once -->\n";
            return;
        }
        $mContent   = $this->callPFOApi( PFO_API_URL, $aReq, $atts );
        $this->done = 1;
        return $mContent;
    }

    /**
     * callPFOApi()
     *
     * calling the PFO-Webservice and providing the data
     * for the output in the recent WordPress page
     *
     * @param $sUrl
     * @param array $aReq
     * @param bool $bDecode
     * @param bool $atts
     * @return mixed
     */
    private function callPFOApi( $sUrl, $aReq = array(), $atts = array() ) {
        if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
            echo "<p><strong>\$aReq in callPFOApi()</strong></p>";
            echo "<pre style='color: blue;'>";
            print_r( $aReq );
            echo "</pre>";
            echo "<pre style='color: purple;'>";
            print_r( $atts );
            echo "</pre>";
        }
        $body            = array();
        $sAddError       = false;
        $body['wp-host'] = $_SERVER['HTTP_HOST'];
        $current_rel_uri = add_query_arg( NULL, NULL );
        if ($current_rel_uri) {
            $aReDirect = explode( '?', $current_rel_uri );
        } elseif ($_SERVER['REDIRECT_URL']) {
            $aReDirect = explode( '?', $_SERVER['REDIRECT_URL'] );
        } elseif ($_SERVER['PATH_INFO']) {
            $aReDirect = explode( '?', $_SERVER['PATH_INFO'] );
        }
        $body['wp-path'] = $aReDirect[0];
        ob_start();
        bloginfo( 'version' );
        $sBlogVersion = ob_get_contents();
        ob_end_clean();
        $body['wp-version'] = trim( $sBlogVersion );
        $oTheme             = wp_get_theme();
        if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
            $body['wp-theme'] = serialize( $oTheme );
        }
        $body['wp-theme-name'] = $oTheme->get( 'Name' );;
        $body['wp-theme-template'] = $oTheme->get( 'Template' );;
        $body['wp-theme-url'] = $oTheme->get( 'ThemeURI' );;
        $body['wp-theme-version'] = $oTheme->get( 'Version' );;
        $body['wp-theme-author'] = $oTheme->get( 'Author' );;
        $body['wp-theme-author-url'] = $oTheme->get( 'AuthorURI' );;

        $body['wp-request-uri']        = $_SERVER['REQUEST_URI'];
        $body['wp-server-remote-addr'] = $_SERVER['REMOTE_ADDR'];

        if (isset( $_SERVER['HTTP_USER_AGENT'] )) {
            $body['wp-user-agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        $body['WP_SESSION'] = WP_SESSION;
        if (isset( $_SESSION['PFO_SESSION'] ) && $_SESSION['PFO_SESSION']) {
            $body['PFO_SESSION'] = $_SESSION['PFO_SESSION'];
        }
        if (isset( $_COOKIE['screen_width'] )) {
            $body['screen_width'] = $_COOKIE['screen_width'];
        }
        if (isset( $_COOKIE['is_touch'] )) {
            $body['is_touch'] = $_COOKIE['is_touch'];
        }

        if (sizeof( $_COOKIE ) > 0) {
            foreach ($_COOKIE as $sKey => $sVal) {
                if (strpos( $sKey, 'pfo_' ) > -1) {
                    $body['PFO_COOKIE'][$sKey] = $sVal;
                }
            }
        }

        $body['wp-pfo-plugin-version'] = PFO_PLUGIN_VERSION;
        if ($_SERVER['REQUEST_SCHEME']) {
            $body['wp-scheme'] = $_SERVER['REQUEST_SCHEME'];
        }
        if ($_SERVER['SERVER_PORT']) {
            $body['wp-port'] = $_SERVER['SERVER_PORT'];
        }
        if (!$_SERVER['REQUEST_SCHEME'] && !$_SERVER['SERVER_PORT']) {
            $body['wp-server'] = $_SERVER;
        }
        if (PFO_DEBUG) {
            $body['PFO_DEBUG'] = PFO_DEBUG;
        } else {
            $body['PFO_DEBUG'] = '-1';
        }

        if (PFO_DEMO) {
            $body['PFO_DEMO_ALBUM'] = PFO_DEMO;
        } else {
            $body['PFO_DEMO_ALBUM'] = '-1';
        }

        if (PFO_MAIN_COLOR) {
            $body['PFO_MAIN_COLOR'] = PFO_MAIN_COLOR;
        } else {
            $body['PFO_MAIN_COLOR'] = '-1';
        }

        if (PFO_DARK_LAYOUT) {
            $body['PFO_DARK_LAYOUT'] = PFO_DARK_LAYOUT;
        } else {
            $body['PFO_DARK_LAYOUT'] = '-1';
        }

        if (PFO_INCLUDE_JQUERY) {
            $body['JQUERY_INCLUDE'] = PFO_INCLUDE_JQUERY;
        } else {
            $body['JQUERY_INCLUDE'] = '-1';
        }

        if (isset( $aReq['p'] ) && $aReq['p']) {
            $body['wp-p'] = $aReq['p'];
        }
        if (isset( $aReq['page_id'] ) && $aReq['page_id']) {
            if (!is_numeric( $aReq['page_id'] ) && strpos( $aReq['page_id'], '/?' )) {
                $aPageId = explode( '/?', $aReq['page_id'] );
                if (is_numeric( $aPageId [0] )) {
                    $body['wp-page_id'] = $aPageId [0];
                }
                if (strpos( $aPageId [1], 'vals[val-1]' )) {
                    //  $sPost .= '&' . $aPageId [1]; //
                }
            } else {
                $body['wp-page_id'] = $aReq['page_id'];
            }
        }
        if ($atts && sizeof( $atts ) > 0) {
            foreach ($atts as $k => $v) {
                $body['wp-params[' . $k . ']'] = $v;
            }
        }
        if (isset( $aReq['pfo-vals'] ) && is_array( $aReq['pfo-vals'] )) {
            foreach ($aReq['pfo-vals'] AS $k => $v) {
                $body[$k] = $v;
            }
        }
        if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
            echo "<pre>\$body: <br>";
            print_r( $body );
            echo "</pre>";
        }
        $args     = array(
            'body' => $body,
            'timeout' => '15',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'cookies' => array()
        );
        $response = wp_remote_post( $sUrl, $args );
        if (gettype( $response ) == 'object') {
            $http_request_failed = false;
            if ($response->errors) {
                $aResponse = @get_object_vars( $response );

                $sError = '<br><br><br><br><br><br><br><br><h1>Es ist ein Fehler aufgetreten!</h1>';
                $sError .= '<p>Error-Code: ' . __LINE__ . '</p>';
                $sError .= '<p style="border: 1px solid #aaa;background: white;color: #0099e5;padding: 12px 20px;border-radius: 5px;font-size: 20px;">Bitte laden Sie die Seite neu (Drücken Sie die F5-Taste oder STR + R bzw. CTRL + R).<br>In der Regel wird die Seite nach einem Neu-Laden korrekt angezeigt.</p>';
                $sError .= '<ul style="color:red">';
                $sGet   = '?';
                foreach ($aResponse ['errors'] as $sKey => $aOne) {
                    if ($sKey == 'http_request_failed') {
                        $http_request_failed = true;
                    }
                    $sGet .= strval( $sKey ) . '=' . strval( $aOne ) . '&';
                    foreach ($aOne as $sOneKey => $sOne) {
                        $sGet   .= strval( $sOneKey ) . '=' . strval( $sOne ) . '&';
                        $sError .= '<li>' . $sOne;
                    }
                }
                $sError .= '</ul >';
                $sError .= '<p>Bitte mache einen Screenshot uns sende diesen an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a> oder per WhatsApp an 0171 - 6 871 851.</p>';
                $sError .= '<p>Für Support wende Dich bitte ebenfalls an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
                if ($http_request_failed) {
                    die( $sError );
                }
                $sUrlComplete = PFO_HOST . 'json/wp-error.php' . $sGet;
                echo '<script>var jqxhr = jQuery.ajax( {
                            url: "' . $sUrlComplete . '",
                            dataType: "json"}  )
                            .done(function() {
                                console.log("pfo success");
                            })
                            .fail(function() {
                                console.log("pfo error");
                            })
                            .always(function(response) {    
                                console.log("pfo done");
                                console.log(response.html);
                            });                                               
                        </script>';
                return $sError;
            }
        }
        if ($response['response']['code'] == 200) {
            if (strpos( $response['body'], 'NO_CONFIG' )) {
                $oJson = json_decode( $response['body'] );
                $aJson = @get_object_vars( $oJson );
                if ($aJson['OUT'] == 'NO_CONFIG') {
                    $sHtml = '<p  style="color:red;background:#fff;padding: 5px;margin: 0 auto 10px;">Fehlende Konfiguration auf PROFI-FOTOS-online.com. <br>
					Bitte logge Dich auf <a href="https://www.profi-fotos-online.com/?mail-param=/layout/wordpress-plugin/domain/" target="_blank">www.profi-fotos-online.com</a> ein und trage Deine WordPress-Seite ein.</p>';
                    $sHtml .= '<p  style="color:red;background:#fff;padding: 5px;margin: 0 auto 10px;">Alternativ kannst Du ein Demo-Album aufrufen.<br>';
                    $sHtml .= 'Dazu verwende bitte den Short Code <input class="input-code" value="[pfo album=demo]" style="text-align: center;width: 175px;font-family: monospace;font-size: 120%;" onclick="this.select()">.</p>';
                    return $sHtml;
                }
            }
            if (strpos( $response['body'], 'NO_JQUERY' )) {
                $oJson = json_decode( $response['body'] );
                $aJson = @get_object_vars( $oJson );
                if ($aJson['OUT'] == 'NO_JQUERY') {
                    $sHtml = '<p  style="color:red;background:#fff;padding: 5px;margin: 0 auto 10px;"><strong>jQuery ist nicht aktiviert!</strong><br>
                    PROFI-FOTOS-online.com benötigt dies jedoch.<br>';
                    if (is_user_logged_in()) {
                        $sHtml .= 'Bitte wechsle zu den <a href="' . get_site_url( null, '/wp-admin/options-general.php?page=profifotosonline' ) . '">PROFI-FOTOS-online.com Einstellungen</a> und aktiviere  jQuery.';
                    } else {
                        $sHtml .= 'Bitte aktiviere  jQuery in den WordPress Einstellungen.';
                    }
                    $sHtml .= '</p>';
                    return $sHtml;
                }
            }
            return $response['body'];
        }
        switch ($response['response']['code']) {
            case 401:
                return '<h1>Authentication required!</h1><p>Für Support wende Dich bitte an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
            case 404:
                if (!strpos( $sUrl, 'www.profi-fotos-online.com' )) {
                    $sAddError = '<p style="color: red">Möglicherweise hast Du versehentlich  eine falsche API-URL eingetragen:<br><code style="font-size: 18px">' . $sUrl . '</code></p>';
                }
                return '<h1>Object not found!</h1>' . $sAddError . '<p>Für Support wende Dich bitte an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
                break;
            case 500:
                return '<h1>API nicht erreichbar!</h1><p>Für Support wende Dich bitte an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
                break;
            default:
                return '<h1>Es ist ein Fehler aufgetreten (<small>' . __LINE__ . ' | ' . $response['response']['code'] . '</small>)!</h1><p>Für Support wende Dich bitte an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
        }
    }

    /**
     * mergeRequest()
     *
     * joining POST and GET vars
     *
     * @param array $aGet
     * @param array $aPost
     * @return array
     */
    public function mergeRequest( $aGet = array(), $aPost = array() ) {
        if (is_array( $aGet ) && is_array( $aPost )) {
            $ret = array_merge( $aGet, $aPost );
        } elseif (is_array( $aGet )) {
            $ret = $aGet;
        } elseif (is_array( $aPost )) {
            $ret = $aPost;
        } else {
            return array();
        }
        $aIn  = array('/</', '/>/');
        $aOut = array('-', '-');
        if (isset( $ret ) && !empty( $ret )) {
            foreach ($ret as $k => $o) {
                $ret [$k] = preg_replace( $aIn, $aOut, $o );
            }
        }
        return $ret;
    }
}