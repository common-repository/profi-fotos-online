<?php
/*
Plugin Name: PROFI-FOTOS-online.com | Online-Shop für Fotografen
Plugin URI: https://www.profi-fotos-online.com/homepage/wordpress-plugin/
Description: PROFI-FOTOS-online.com | Online-Shop für Fotografen
Version: 1.0.28
Author: PROFI-FOTOS-online.com | Stephan Heller <wordpress@profi-fotos-online.com> | daik.de UG
Author URI: https://daik.de/%C3%9Cber+daik.de/Stephan+Heller
License: GPLv2 or later
*/

add_filter( 'https_ssl_verify', '__return_false' );
add_filter( 'https_local_ssl_verify', '__return_false' );

/**
 * CONST Debug - if you want see which params are exchanced
 * AWARE! no Debug Message before Session is initialized (initPfoSession(); is called..)
 */
define( 'PFO_DEBUG', get_option( 'pfo_debug_mode' ) );

/**
 * CONST Demo - to use the Demo Album of PROFI-FOTOS-online.com
 **/
define( 'PFO_DEMO', get_option( 'pfo_show_demo' ) );

/**
 * CONST Main color - Defines the main color for Link, Icons etc
 **/
define( 'PFO_MAIN_COLOR', get_option( 'pfo_main_color' ) );

/**
 * CONST Dark Layout - to use with dark WP Themes
 **/
define( 'PFO_DARK_LAYOUT', get_option( 'pfo_dark_layout' ) );

/**
 * CONST JQUERY
 * most Themes have jQuery included - but not all.
 * if you need to aktivate jQuery, change the value from false to true
 * but don't worry, install first, if you have to activat, we give you a hint
 */
define( 'PFO_INCLUDE_JQUERY', get_option( 'pfo_include_jquery' ) );

/**
 * CONST PFO_INIT_SESSION_ALWAYS
 * for some reasons, when a theme is sending an output before the plugin is called
 * the Session for the Shop cannot be initialized
 * This flag is for the User to acativate Session in generall.
 * Just a workaround - but musst be offered because there are already User who use older versions of this plugin.
 */
define( 'PFO_INIT_SESSION_ALWAYS', get_option( 'pfo_init_session_hack' ) );

/**
 * if the User activated this Flag in the Wordpress Backend, Session will be initialize here...
 */
if (PFO_INIT_SESSION_ALWAYS) {
    session_start();
}

try {
    if (!function_exists( 'get_plugin_data' )) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $default_headers = get_plugin_data( __FILE__ );
    /**
     * PFO_PLUGIN_VERSION
     * fetch the Plugin-Version for Debug Purposes
     */
    define( 'PFO_PLUGIN_VERSION', $default_headers['Version'] );
} catch (Error $e) {
    define( 'PFO_PLUGIN_VERSION', 'undefined' );
    // no exit on Error...
}

/**
 * PROFI-FOTOS-online.com is checking if jQuery is included an gives advice to the user to activate
 * in the settings
 * if the user aktivated jQuery, it will be included here.
 */
if (PFO_INCLUDE_JQUERY) {
    wp_enqueue_script( 'jquery' );
}

/**
 * if you need to add your own CSS to the Plugin-Styels, activate the Flag in the Wordpress Settings
 * then a PFO-CSS-File is included in your WordPress installation
 * this is the appropriate CSS File to overwrite our PFO CSS
 */
if (get_option( 'pfo_plugin_css' )) {
    wp_register_style( 'pfo-styles', plugins_url( 'pfo.css', __FILE__ ) );
    wp_enqueue_style( 'pfo-styles' );
}

/**
 * Register a custom menu page.
 */
function wpdocs_register_my_custom_menu_page() {
    add_menu_page(
        __( 'PROFI-FOTOS-online.com | Online-Shop für Fotografen',
            'textdomain' ),
        'PROFI-FOTOS-online.com',
        'manage_options',
        'profi-fotos-online/pfo-admin.php',
        'pfo_page',
        plugins_url( 'profi-fotos-online/images/icon-bg-trans.png' ),
        10
    );
}


add_action( 'admin_menu', 'wpdocs_register_my_custom_menu_page' );

/*ADDS STYLESHEET ON WP-ADMIN*/
add_action( 'admin_enqueue_scripts', 'safely_add_stylesheet_to_admin' );
function safely_add_stylesheet_to_admin() {
    wp_enqueue_style( 'prefix-style', plugins_url( 'css/wp-pfo-admin.css', __FILE__ ) );
}

/**
 * Settings Page for PROFI-FOTOS-online.com in WordPress
 */
function pfo_page() {
    ?>
    <div class="wrap" id="pfo-admin-wrap">
        <h1><?= esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/icon-128x128.png'; ?>"
                 alt="PROFI-FOTOS-online.com | Online-Shop für Fotografen">
            <?php
            settings_fields( "section" );
            do_settings_sections( "profifotosonline" );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Setup Settings Form
 */
function pfo_settings_page() {


    add_settings_section( "section", "Plugin-Einstellungen", 'pfo_intro', "profifotosonline" );

    add_settings_field( "pfo_include_jquery", "jQuery für Plugin aktivieren <small>Je nach Theme ist jQuery aktiviert oder nicht. Beim Aufruf der Seite überprüfen wir, ob jQuery eingebunden ist und weisen Dich gegebenenfalls darauf hin, hier jQuery zu aktivieren.</small>", "jquery_checkbox_display", "profifotosonline", "section", array('label_for' => 'pfo_include_jquery') );
    add_settings_field( "pfo_init_session_hack", "Sessions generell aktivieren <small>Je nach Theme werden Header bereits vor der Einbindung der Plugins gesendet. Dies verhindert, eine PROFI-FOTOS-online.com-Session für die Shop-Funktionalität  zu initialisieren.<br>Falls Sie diese Checkbox aktivieren, wird generell eine Session initialisiert, bevor andere Themes oder Plugins einen Output und damit einen Header senden.</small>", "session_checkbox_display", "profifotosonline", "section", array('label_for' => 'pfo_init_session_hack') );
    add_settings_field( "pfo_show_demo", "Demo-Album verwenden <small>Falls Du unser Plugin ohne Nutzer-Konto bei <span style='font-family: Open Sans, sans-serif;color: #0099e6;font-size: 110%;font-weight: 600;'>PROFI-FOTOS-online.com</span> testen möchtest, kannst Du auch einfach ein Demo-Album von uns zum Testen verwenden.<br>Oder verwende den Short Code <input class=\"input-code\" value=\"[pfo album=demo]\" style='width: 200px' onclick=\"this.select()\">, um das Demo-Album aufzurufen</small>", "pfo_show_demo_display", "profifotosonline", "section", ['label_for' => 'pfo_show_demo'] );
    add_settings_field( "pfo_plugin_css", "Plugin-CSS aktivieren <small>Falls sich die Styles Deines Themes mit unseren Styles beißen, kannst Du die CSS-Datei im Plugin-Verzeichnis bearbeiten und hier aktivieren.</small>", "plugin_css_checkbox_display", "profifotosonline", "section", array('label_for' => 'pfo_plugin_css') );
    add_settings_field( "pfo_debug_mode", "Debug-Modus aktivieren <small>Wird gegebenenfalls beim Support bei der Fehlersuche benötigt. <br><span class='alert'>Bitte nicht aktivieren. Diese Option wird nur im Support-Fall benötigt.</span> </small>", "debug_checkbox_display", "profifotosonline", "section", array('label_for' => 'pfo_debug_mode') );
    add_settings_field( "pfo_api_url", "Alternative API URL <small>Bitte leer lassen, die Eingabe ist nur für den Support-Fall. Hier kann unsere Entwicklungs-API konfiguriert werden. Diese teilen wir nur persönlich mit. <br><span class='alert'>Bitte nichts eintragen. Das Feld wird nur im Support-Fall benötigt.</span></small>", "pfo_api_host_input_display", "profifotosonline", "section", array('label_for' => 'pfo_api_url') );


    register_setting( "section", "pfo_include_jquery" );
    register_setting( "section", "pfo_init_session_hack" );
    register_setting( "section", "pfo_show_demo" );
    register_setting( "section", "pfo_plugin_css" );
    register_setting( "section", "pfo_debug_mode" );
    register_setting( "section", "pfo_api_url" );

    add_settings_field( "pfo_dark_layout", "Dunkles Shop-Template versenden<small>Für WordPress-Seiten, die ein dunkles Theme verwenden.</small>", "pfo_dark_layout", "profifotosonline", "section", array('label_for' => 'pfo_dark_layout') );
    register_setting( "section", "pfo_dark_layout" );


    // add_settings_field("pfo_main_color", "Hauptfarbe für Links und Icons<small>Ein Teil der Links, Icons und Bedienelemente werden mit dieser Farbe angezeigt. Damit passt sich der Shop in Dein Layout an.</small>", "pfo_main_color", "profifotosonline", "section", array('label_for' => 'pfo_main_color'));
    // register_setting("section", "pfo_main_color");

}

function pfo_intro( $arg ) {


    /**
     * Check if Super-Cache isset
     */
    if (is_plugin_active( 'wp-super-cache/wp-cache.php' )) {
        echo '<div style="color: red;font-weight: 400"><p><strong>Das Plugin "WP Super Cache" ist aktiviert.</strong><br>Bitte deaktivieren Sie dieses Plugin bzw. deaktivieren  Sie das Caching auf der Seite, auf der Sie das Plugin einsetzten.</p></div>';
    }

    echo '<p>Du musst ein gültiges Konto auf <a href="https://www.profi-fotos-online.com/" target="_blank">www.profi-fotos-online.com</a> haben und dort mindestens ein Album angelegt haben.</p>';
    echo '<p>Deine WordPress-Seite muss unter <a href="https://www.profi-fotos-online.com/layout/wordpress-plugin/domain/" target="_blank">www.profi-fotos-online.com/layout/wordpress-plugin/domain/</a> eingetragen werden.</p>';
    echo '<p>Danach kann der vollständige Shop über den Short Code <input class="input-code" value="[pfo]" onclick="this.select()"> hier eingebunden werden.</p>';
    echo '<p><span class=\'alert-install\'>Nach der Einbindung des Short Codes und Aufruf der Webseite werden etwaige Probleme automatisch erkannt und entsprechende Meldungen angezeigt. Je nach Hinweis muss hier ein Häkchen gesetzt oder entfernt werden.</p>';
    echo '<p><strong>Support-Hinweis</strong>: Jede WordPress-Installation ist anders. Falls Du Unterstützung bei der Installation benötigst, melde Dich gerne: <a href="mailto:wordpress@profi-fotos-online.com">wordpress@profi-fotos-online.com</a>.</p>';
}

function pfo_colors( $arg ) {
    echo '<p>Du hast hier die Möglichkeit, ein dunkles Farbschema zu aktivieren und die Hauptfarbe einzustellen.</p>';
}

/**
 * Single Checkbox
 */
function jquery_checkbox_display() {
    ?>
    <!-- Here we are comparing stored value with 1. Stored value is 1 if user checks the checkbox otherwise empty string. -->
    <input type="checkbox" name="pfo_include_jquery" id="pfo_include_jquery"
           value="1" <?php checked( 1, get_option( 'pfo_include_jquery' ), true ); ?> />
    <?php
}

/**
 * Single Checkbox
 */
function session_checkbox_display() {
    ?>
    <!-- Here we are comparing stored value with 1. Stored value is 1 if user checks the checkbox otherwise empty string. -->
    <input type="checkbox" name="pfo_init_session_hack" id="pfo_init_session_hack"
           value="1" <?php checked( 1, get_option( 'pfo_init_session_hack' ), true ); ?> />
    <?php
}

/**
 * Single Checkbox
 */
function pfo_show_demo_display() {
    ?>
    <input type="checkbox" name="pfo_show_demo" id="pfo_show_demo"
           value="1" <?php checked( 1, get_option( 'pfo_show_demo' ), true ); ?> />
    <?php
}

/**
 * Single Checkbox
 */
function plugin_css_checkbox_display() {
    ?>
    <input type="checkbox" name="pfo_plugin_css" id="pfo_plugin_css"
           value="1" <?php checked( 1, get_option( 'pfo_plugin_css' ), true ); ?> />
    <?php
}

/**
 * Single Checkbox
 */
function debug_checkbox_display() {
    ?>
    <input type="checkbox" name="pfo_debug_mode" id="pfo_debug_mode"
           value="1" <?php checked( 1, get_option( 'pfo_debug_mode' ), true ); ?> />
    <?php
}

/**
 * Single Checkbox
 */
function pfo_dark_layout() {
    ?>
    <input type="checkbox" name="pfo_dark_layout" id="pfo_dark_layout"
           value="1" <?php checked( 1, get_option( 'pfo_dark_layout' ), true ); ?> />
    <?php
}

/**
 * Single Input Fields
 */
function pfo_api_host_input_display() {
    ?>
    <input type="text" name="pfo_api_url" id="pfo_api_url" value="<?php echo get_option( 'pfo_api_url' ) ?>"
           style="min-width: 250px;"/>
    <?php
}

/**
 * Single Input Fields
 */
function pfo_main_color() {
    ?>
    <input type="color" name="pfo_main_color" id="pfo_main_color" value="<?php echo get_option( 'pfo_main_color' ) ?>"
    />
    <?php
}

// add_action('admin_menu', 'pfo_options_page');
add_action( "admin_init", "pfo_settings_page" );

function general_admin_notice() {

    if (is_plugin_active( 'wp-super-cache/wp-cache.php' )) {
        echo '<div class="notice notice-error is-dismissible">
             <p><strong>PROFI-FOTOS-online.com nicht kompatibel  mit WP Super Cache</strong>: Das Plugin "WP Super Cache" ist aktiviert.<br>Bitte deaktivieren Sie dieses Plugin bzw. deaktivieren  Sie das Caching auf der Seite, auf der Sie das Plugin einsetzten.</p>
         </div>';
    }
}

add_action( 'admin_notices', 'general_admin_notice' );


try {

    /**
     * Run PFO Plugin NOT in WordPress Backend
     */
    if (!is_admin() && (!isset( $_SERVER['CONTENT_TYPE'] ) || $_SERVER['CONTENT_TYPE'] != 'application/json')) {
        include_once 'includes/profi-fotos-online.class.php';
        include_once 'includes/profi-fotos-online.php';
        $sAltApiHost = get_option( 'pfo_api_url' );

        // define API Host
        define( 'PFO_HOST', $sAltApiHost ? $sAltApiHost . (substr( $sAltApiHost, -1 ) != '/' ? '/' : '') : 'https://www.profi-fotos-online.com/' );

        // define API Url
        define( 'PFO_API_URL', PFO_HOST . 'wp/index.php' );
        add_shortcode( "pfo", "runPFO" );
    }

} catch (Error  $e) {
    /**
     * Collect Error-Information
     */
    $sErrorString .= $e->getMessage();                 // Exception message
    $sErrorString .= "\n";
    $sErrorString .= $e->getCode();                    // User-defined Exception code
    $sErrorString .= "\n";
    $sErrorString .= $e->getFile();                    // Source filename
    $sErrorString .= "\n";
    $sErrorString .= $e->getLine();                    // Source line
    $sErrorString .= "\n";
    $sErrorString .= $e->getTrace();                   // An array of the backtrace()
    $sErrorString .= "\n";
    $sErrorString .= $e->getTraceAsString();           // Formated string of trace

    echo "<h1>Fehler</h1>";
    echo "<p>Es ist ein Fehler aufgetreten (<small>C " . __LINE__ . "</small>) | WP_DEBUG: " . WP_DEBUG . "</p>";

    if (defined( 'WP_DEBUG' ) && WP_DEBUG) {
        echo "<pre>";
        print_r( $sErrorString );
        echo "<pre>";
    }
}