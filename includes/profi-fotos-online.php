<?php
/**
 * runPFO() - the controller
 *
 * - checking if cURL is supported
 * - set the SESSION
 * - calling the content (via PFO-Webservice)
 *
 * @param $atts
 *
 * @return bool|string
 */
function runPFO( $atts ) {

    // first init PFO Session because without the cart will not work...
    initPfoSession();
    define( 'WP_SESSION', session_id() );
    $sMessage = false;
    if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
        echo "<p><strong>WP \$atts</strong></p>";
        echo "<pre>";
        print_r( $atts );
        echo "</pre>";
    }
    if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
        echo "<p>PFO_INCLUDE_JQUERY: " . PFO_INCLUDE_JQUERY . " | " . gettype( PFO_INCLUDE_JQUERY ) . '</p>';
        echo "<p>PFO_DEBUG: " . PFO_DEBUG . " | " . gettype( PFO_DEBUG ) . '</p>';
    }
    if (!SESSION_ENABLED) {
        return;
    }

    $oPFO = PFO::singleton();
    $aReq = $oPFO->mergeRequest( $_GET, $_POST );

    if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
        echo "<p><strong>WP \$aReq </strong></p>";
        echo "<pre style='color: purple'>";
        print_r( $aReq );
        echo "</pre>";
    }

    /**
     * Set Cookie for re-Authentication
     */
    $sAddCookie = false;

    /**
     * @todo: remove at next two Updates (1.1.16)
     */
    if (isset( $aReq['pfo-vals'] ) && isset( $aReq['pfo-vals']['val-1'] ) && $aReq['pfo-vals']['val-1'] == 'album' && isset( $aReq['pfo-vals']['val-2'] ) && is_numeric( $aReq['pfo-vals']['val-2'] ) && isset( $aReq['pfo-vals']['coac'] ) && $aReq['pfo-vals']['coac']) {
        $sExpire = false;
        if ($aReq['pfo-vals']['exp']) {
            echo "<script>var d = new Date();  d.setTime(" . ($aReq['pfo-vals']['exp'] * 1000) . ");  var expires = 'expires='+ d.toUTCString()+';';</script>";
        } else {
            echo "<script>var expires = ''</script>";
        }
        $sAddCookie = "document.cookie='pfo_access_" . $aReq['pfo-vals']['val-2'] . "=" . $aReq['pfo-vals']['coac'] . ";'+expires+'path=/';";
        echo "<script>" . $sAddCookie . "</script>";
    }

    /**
     * Set generic Cookie from PFO Data
     */
    if (isset( $aReq['pfo-vals']['cookie'] ) && $aReq['pfo-vals']['cookie']) {
        $aOneCookie = explode( ';', $aReq['pfo-vals']['cookie'] );
        foreach ($aOneCookie as $sOneCookie) {
            if ($sOneCookie) {
                $sSeperator = false;
                if (strpos( $sOneCookie, '|' )) {
                    $sSeperator = '|';
                } elseif (strpos( $sOneCookie, '__' )) {
                    $sSeperator = '__';
                }
                if ($sSeperator) {
                    $aSingleCookie = explode( $sSeperator, $sOneCookie );
                    if ($aSingleCookie[0] && $aSingleCookie[1]) {
                        $sExpire = '';
                        if ($aSingleCookie[2]) {
                            $sExpire = urldecode( $aSingleCookie[2] );
                        }
                        if ($aSingleCookie[1] != 'remove') {
                            $sAddCookie = "document.cookie='" . $aSingleCookie[0] . "=" . $aSingleCookie[1] . "; expires=" . $sExpire . "; path=/';";
                        } else {
                            $sAddCookie = "document.cookie='" . $aSingleCookie[0] . "=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';";
                        }
                        echo "<script>" . $sAddCookie . "</script>";
                        // echo "<script>alert('" . str_replace( "'", "\'", $sAddCookie ) . "')</script>";
                    }
                }
            }
        }
    }

    /**
     * Set Device Information
     */
    if (!isset( $_COOKIE['is_touch'] )) {
        echo "<script>document.cookie='pfo_is_touch=' + ('ontouchstart' in document.documentElement)+ ';path=/';</script>";
    }


    if (!isset( $_SESSION['WP_SESSION'] ) || !defined( 'WP_SESSION' ) || !$_SESSION['WP_SESSION']) {
        if (defined( 'WP_SESSION' ) && WP_SESSION && WP_SESSION != 'WP_SESSION') {
            $_SESSION['WP_SESSION'] = WP_SESSION;
        } else {
            return '<p class="pfo pfo-wp">Init PFO fehlgeschlagen.</p>';
        }
    }
    if (!isset( $_SESSION['PFO_SESSION'] ) || !defined( 'PFO_SESSION' ) || !$_SESSION['PFO_SESSION'] ||
        !isset( $_SESSION['WP_SESSION'] ) || !defined( 'WP_SESSION' ) || !$_SESSION['WP_SESSION'] ||
        (isset( $_SESSION['WP_SESSION'] ) && defined( 'WP_SESSION' ) && $_SESSION['WP_SESSION'] == 'WP_SESSION')
    ) {
        $bRegisterSuccess = $oPFO->registerSession( $aReq );
        if (!$bRegisterSuccess) {
            return '<p class="pfo pfo-wp">Anmeldung an PFO fehlgeschlagen.</p>';
        }
    }
    if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
        echo "<p><strong>\$_SESSION nach registerSession()</strong></p>";
        echo "<pre>";
        print_r( $_SESSION );
        echo "</pre>";
    }
    if (defined( 'PFO_DEBUG' ) && PFO_DEBUG) {
        $sMessage .= '<p  class="pfo pfo-wp" style="color:red;font-size: 1.5em; border: 1px solid;background:#fff;padding: 10px;margin: 0 auto 10px;">PFO-Debug-Modus ist aktiviert</a>.</p>';
    }
    $sContent = $oPFO->getContent( $aReq, $atts );

    define( 'PFO_RUN_DONE', 1 );
    $done = true;

    return '<div id="pfo-plugin" class="pfo pfo-wp">' . $sMessage . $sContent . '</div>';
}

/**
 * Check and start SESSION.
 * the Shop need to have an SESSION
 * if your Server doesn't support SESSIONs or they are de-activated,
 * you sadly cannot use our Plugin
 */
function initPfoSession() {
    $status = session_status();
    define( 'SESSION_STATUS', $status );
    if (PHP_SESSION_DISABLED === $status) {
        echo '<p style="color:red;background:#fff;padding: 5px;">SESSION\'s sind deaktiviert. Bitte aktivieren Sie SESSION\'s auf Ihrem Webserser.</p>';
        echo '<p  class="pfo pfo-wp" style="color:red;background:#fff;padding: 5px;margin: 0 auto 10px;">Bei Fragen wende Dich  bitte an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
        define( 'SESSION_ENABLED', false );

        return;
    }
    define( 'SESSION_ENABLED', true );

    if (!PFO_INIT_SESSION_ALWAYS && headers_sent( $filename, $linenum )) {
        $sMessage = '<p  class="pfo pfo-wp" style="color:red;font-size: 20px; border: 1px solid;background:#fff;padding: 10px;margin: 0 auto 10px;"><strong>Es ist ein Fehler aufgetreten (<small>' . __LINE__ . '</small>)!</strong><br>';
        $sImgUrl  = str_replace( $_SERVER['DOCUMENT_ROOT'], '', __DIR__ );
        $sImgUrl  = str_replace( 'profi-fotos-online/includes', 'profi-fotos-online', $sImgUrl );
        $sMessage .= 'Bitte überprüfe  im WordPress-Admin-Bereich in den PROFI-FOTOS-online.com-Einstellungen, ob der Eintrag »Sessions generell aktivieren« aktiviert ist.<br>';
        $sMessage .= str_replace( '//', '/', '<img src="/' . $sImgUrl . '/images/hilfe-session-screen.png" alt="Hilfe-Screen Session-Fehler" style="margin: 20px auto 0;display: block;max-width: 100%"> <br>' );
        if ((defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'PFO_DEBUG' ) && PFO_DEBUG)) {
            $sMessage .= 'Es wurde bereits ein Header gesendet.<br>';
            $sMessage .= 'Die Session für die Shop-Funktion kann nicht initialisiert werden.<br>';
            $sMessage .= '<small>Zeile: ' . $linenum . ' in Datei: ' . $filename . '</small>.<br>';
        }
        $sMessage .= 'Bei Fragen wende Dich  bitte an <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
        echo $sMessage;
    }

    if (PHP_SESSION_NONE === $status) {
        session_start();
    }
    if (defined( 'PFO_DEBUG' ) && PFO_DEBUG) {
        echo '<p>SESSION_STATUS: ' . SESSION_STATUS . '</p>';
        echo '<p>SESSION_ENABLED: ' . SESSION_ENABLED . '</p>';
        echo '<p>session_id(): ' . session_id() . '</p>';
    }
}