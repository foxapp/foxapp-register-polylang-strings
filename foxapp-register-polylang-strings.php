<?php
/*
@link https://plugins.foxapp.net/
Plugin Name: FoxApp - Register Polylang Strings
Plugin URI: https://plugins.foxapp.net/foxapp-register-polylang-strings
Description: Plugin adds functionality to save strings to have possibility to translate on Polylang Strings option.
Version: 1.0.6
Author: FoxApp
Author URI: https://plugins.foxapp.net/
Requires at least: 6.1
Requires PHP: >= 7.4
Text Domain: foxapp-register-polylang-strings
Domain Path: /languages
*/

namespace FoxApp;

class RegisterPllStrings {
	public array $plugin;
	public string $plugin_slug;
	/**
	 * @var mixed
	 */
	public $plugin_text_domain;
	public string $plugin_identifier;

	public function __construct() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$this->plugin             = get_plugin_data( __FILE__ );
		$this->plugin_slug        = basename( __FILE__, '.php' );
		$this->plugin_text_domain = $this->plugin['TextDomain'];
		$this->plugin_identifier  = md5( $this->plugin_text_domain );

		$this->strings = get_option( 'pll_strings' . $this->plugin_identifier );

		if ( get_option( 'enabled' . $this->plugin_identifier ) ) {
			add_action( 'init', [ $this, 'init' ] );
		}

		add_shortcode( '_text', [ $this, 'translated_text_with_polylang' ] );
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		add_action( 'admin_notices_saved_options', [ $this, 'admin_notices_saved_options_method' ] );
	}

	public function admin_notices_saved_options_method(): void {
		$class   = 'notice notice-success';
		$notify  = __( 'Imported!', $this->plugin_text_domain );
		$message = __( 'Strings are imported with success.', $this->plugin_text_domain );

		printf( '<div class="%1$s"><p><strong>%2$s</strong> %3$s</p></div>', esc_attr( $class ), esc_html( $notify ), esc_html( $message ) );
	}

	public function adminMenu() {
		add_menu_page(
			__( 'Register Strings to Polylang', $this->plugin_text_domain ),
			__( 'Register Strings', $this->plugin_text_domain ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'adminPage' ),
			'dashicons-translation',
			100
		);
	}

	public function adminPage(): void {

		$currentClass = $this->slugify( $this->plugin_text_domain );

		load_theme_textdomain( $this->plugin_text_domain, __DIR__ . '/languages' );
		?>
        <!-- Our admin page content should all be inside .wrap -->
        <div class="wrap <?php echo $currentClass; ?>">
            <style>


                .<?php echo $currentClass; ?> .nav-tab-wrapper,
                .<?php echo $currentClass; ?>.wrap h2.nav-tab-wrapper,
                .<?php echo $currentClass; ?> h1.nav-tab-wrapper {
                    border-bottom: 1px solid #c3c4c7;
                }

                .<?php echo $currentClass; ?> .tab-content {
                    padding: 10px;
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-top: 0;
                }

                .<?php echo $currentClass; ?> .nav-tab-active,
                .<?php echo $currentClass; ?> .nav-tab-active:focus,
                .<?php echo $currentClass; ?> .nav-tab-active:focus:active,
                .<?php echo $currentClass; ?> .nav-tab-active:hover {
                    border-bottom: 1px solid #fff;
                    background: #fff;
                    color: #000;
                }

                .<?php echo $currentClass; ?> .preview-list td,
                .<?php echo $currentClass; ?> .examples td {
                    padding: 2px 12px;
                }

                .<?php echo $currentClass; ?> .examples td {
                    border: 1px solid #c3c4c7;
                }

                .<?php echo $currentClass; ?> h1 {
                    display: inline-flex !important;
                    justify-content: space-between;
                    gap: 10px;
                }

                .<?php echo $currentClass; ?> h1 {
                    font-weight: bold;
                }

                .<?php echo $currentClass; ?> sup {
                    font-weight: normal;
                    color: red;
                }

                .<?php echo $currentClass; ?> .alert {
                    padding: 7px 10px;
                    background: #ffdcdc;
                    border-radius: 3px;
                }

                .<?php echo $currentClass; ?> .nav-tab {
                    display: flex;
                    align-items: center;
                }

                .<?php echo $currentClass; ?> .nav-tab i {
                    margin-right: 5px;
                }

                .<?php echo $currentClass; ?> .input-translatable {
                    border: 2px solid green !important;
                }

                .<?php echo $currentClass; ?> .strings_import,
                .<?php echo $currentClass; ?> .strings_export {
                   width: 100%;
                   min-height: 200px;
                }
            </style>

            <h1><?php echo esc_html( get_admin_page_title() ); ?><sup>ver. <?php echo $this->plugin['Version'] ?></sup>
            </h1>

            <nav class="nav-tab-wrapper">

				<?php
				$tabs = [
					[
						'tab_icon'  => '<i class="dashicons dashicons-admin-settings"></i>',
						'tab_title' => __( 'Registered Strings', $this->plugin_text_domain ),
						'page'      => $this->plugin_slug,
						'tab_slug'  => 'register_settings'
					],
					[
						'tab_icon'  => '<i class="dashicons dashicons-backup"></i>',
						'tab_title' => __( 'Import/Export Strings', $this->plugin_text_domain ),
						'page'      => $this->plugin_slug,
						'tab_slug'  => 'import_export_settings'
					]
				];

				$default_tab = $tabs[0]['tab_slug'];
				if ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ) {
					$current_tab = sanitize_text_field( $_GET['tab'] );
				} else {
					$current_tab = null;
				}
				$current_tab = ! empty( $current_tab ) ? $current_tab : $default_tab;

				$current_tab_exists = false;
				foreach ( $tabs as $tab ) {
					if ( $current_tab === $tab['tab_slug'] ) {
						$current_tab_exists = true;
					}
				}

				if ( ! $current_tab_exists ) {
					$current_tab = $tabs[0]['tab_slug'];
				}

				foreach ( $tabs as $tab_index => $tab ) {
					$tab_url = '?' . http_build_query( [ 'page' => $tab['page'], 'tab' => $tab['tab_slug'] ] );
					?>
                    <a style="<?php if ( $tab_index === 0 ) { ?>margin-left:0;<?php } ?>" href="<?php echo $tab_url ?>"
                       class="nav-tab <?php if ( $current_tab === $tab['tab_slug'] ): ?>nav-tab-active<?php endif; ?>">
						<?php echo $tab['tab_icon'] ?>
						<?php echo $tab['tab_title'] ?>
                    </a>
					<?php
				}
				?>
            </nav>

            <div class="tab-content">
				<?php switch ( $current_tab ) :

					case 'import_export_settings':
						$this->getRegisteredStringsImportExport();
						break;

					default:
						$this->getRegisteredStringsUi();
						break;

				endswitch; ?>
            </div>
        </div>
		<?php
	}

	public function getRegisteredStringsImportExport() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$action = 'get_import_export_strings_action';

		if (
			isset( $_POST['action'] ) && $_POST['action'] === $action &&
			isset( $_POST[ $action . '_nonce_field' ] ) &&
			wp_verify_nonce( $_POST[ $action . '_nonce_field' ], $action . '_nonce_action' )
		) {
			//Save Settings
            echo 'Current Action for Import/Export'.PHP_EOL;
            $import_strings = json_decode(stripslashes($_POST['strings_import']), true);

			if(isset($_POST['strings_import']) && is_array($import_strings)){
				$registered_strings  = array_values($import_strings);
				$tmp_array = [];
				foreach($registered_strings as $tmp){
					if( empty(trim($tmp['string']))){
						continue;
					}
					$tmp_array[] = $tmp;
				}
				$registered_strings  = $tmp_array;
			}
			update_option( 'registered_strings' . $this->plugin_identifier, json_encode( $registered_strings ), 'yes' );
			do_action( 'admin_notices_saved_options' );
		}
		$registered_strings = get_option( 'registered_strings' . $this->plugin_identifier, "[]" );
		$registered_strings = json_decode( $registered_strings, true );
		?>

        <table class="form-table register_strings">
            <tbody>
            <tr>
                <td class="outer" style="vertical-align: top">
                    <h2><label for="strings_import"><?php _e( 'Import Strings', $this->plugin_text_domain ) ?></label></h2>

                    <form method="post">
                        <input type="hidden" name="action" value="<?php echo $action; ?>">
                        <textarea class="strings_import" placeholder="Paste content here." id="strings_import" name="strings_import"></textarea>

                        <p class="wp-ui-highlight">
	                        <?php _e( '<strong>Note:</strong> Importing will overwrite previous registered strings.', $this->plugin_text_domain ) ?>
                        </p>
                        <p>
                            <strong><?php _e( 'To import strings from a different WordPress site, paste the exported content from that site and click the "Import" button.', $this->plugin_text_domain ) ?></strong>
                        </p>

                        <p>
                            <input class="button button-primary" type="submit" value="<?php _e( "Import", $this->plugin_text_domain ) ?>">
                        </p>
	                    <?php wp_nonce_field( $action . '_nonce_action', $action . '_nonce_field' ); ?>
                    </form>
                </td>
                <td class="outer" style="vertical-align: top">
                    <h2><label for="strings_export"><?php _e( 'Export Strings', $this->plugin_text_domain ) ?></label></h2>
                    <textarea title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)." onclick="this.focus();this.select();" onfocus="this.focus();this.select();" readonly="readonly" aria-readonly="true" class="strings_export" id="strings_export" name="strings_export"><?php echo json_encode($registered_strings, JSON_FORCE_OBJECT)?></textarea>

                    <p>
                        <strong><?php _e( 'Use the content above to import current strings into a different WordPress site. You can also use this to simply back up your strings.', $this->plugin_text_domain ) ?></strong>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}

	public function getRegisteredStringsUi() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$action = 'get_registered_strings_action';

		if (
			isset( $_POST['action'] ) && $_POST['action'] === $action &&
			isset( $_POST[ $action . '_nonce_field' ] ) &&
			wp_verify_nonce( $_POST[ $action . '_nonce_field' ], $action . '_nonce_action' )
		) {
			//Save Settings
			update_option( 'enabled' . $this->plugin_identifier, sanitize_text_field( $_POST['enabled'] ?? 0 ), 'yes' );
            $registered_strings = $_POST['registered_strings'] ?? "[]";

            if(isset($_POST['registered_strings']) && is_array($_POST['registered_strings'])){
	            $registered_strings  = array_values($_POST['registered_strings']);
                $tmp_array = [];
                foreach($registered_strings as $tmp){
	                if( empty(trim($tmp['string']))){
                        continue;
                    }
	                $tmp_array[] = $tmp;
                }
	            $registered_strings  = $tmp_array;
            }
            if( isset($_POST['registered_new_strings']) && !empty($_POST['registered_new_strings'][0]['string']) ){
                if(is_array($registered_strings)){
	                $registered_strings = array_merge($registered_strings, $_POST['registered_new_strings']);
                }else{
	                $registered_strings = $_POST['registered_new_strings'];
                }
            }
			update_option( 'registered_strings' . $this->plugin_identifier, json_encode( $registered_strings ), 'yes' );
		}

		$enabled            = get_option( 'enabled' . $this->plugin_identifier );
		$registered_strings = get_option( 'registered_strings' . $this->plugin_identifier, "[]" );
		$registered_strings = json_decode( $registered_strings, true );

		?>
        <form method="post">
			<?php wp_nonce_field( $action . '_nonce_action', $action . '_nonce_field' ); ?>
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <div style="display:flex">
                <table class="form-table register_strings" style="float:left;width:50%">
                    <tbody>
                    <tr class="enabled">
                        <td scope="row" style="padding-left: 10px;width:100px"><label
                                    for="enabled"
                                    style="font-weight: bold"><?php _e( 'Enable?', $this->plugin_text_domain ) ?></label>
                        </td>
                        <td style="display: flex;align-items: center;margin:0;"><input type="checkbox"
                                                                                       id="enabled"
                                                                                       name="enabled"
                                                                                       value="1" <?php checked( $enabled, 1 ) ?>
                                                                                       class="regular-text">
                            <p style="padding-top: 0;padding-left:10px" class="description">To use this text already translated you need to use it in shortcode <strong>[_text text='Name']</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <table style="width:100%;padding:10px;background-color: #f4f4f4">
                    <tbody>
                    <tr>
	                    <td><strong><?php _e( 'Name', $this->plugin_text_domain ) ?></strong></td>
                        <td><strong><?php _e( 'String', $this->plugin_text_domain ) ?></strong></td>
                        <td><strong><?php _e( 'Group(read-only)', $this->plugin_text_domain ) ?></strong></td>
                    </tr>
					<?php
					$count_strings = 0;
                    if(is_array($registered_strings)){
					foreach ( $registered_strings as $index => $string ) {
						$count_strings ++;
						?>
                        <tr>
                            <td>
                                <input style="width: 100%" type="text"
                                       name="registered_strings[<?php echo $index ?>][string]"
                                       value="<?php echo $string['string'] ?? ''; ?>">
                            </td>
                            <td>
                                <input style="width: 100%" type="text"
                                       name="registered_strings[<?php echo $index ?>][name]"
                                       value="<?php echo $string['name'] ?? ''; ?>">
                            </td>
                            <td>
                                <input style="width: 100%" type="text" readonly
                                       name="registered_strings[<?php echo $index ?>][group]"
                                       value="<?php echo $string['group'] ?? 'FoxApp'; ?>">
                            </td>
                            <td class="remove_strings_button">
                                <i class="dashicons dashicons-trash" style="cursor:pointer" title="<?php _e( 'Delete current string', $this->plugin_text_domain ) ?>"></i>
                            </td>
                        </tr>
					<?php } } ?>

                    <tr>
                        <td>
                            <input style="width: 100%;border-color: #ff7900" type="text" placeholder="New String"
                                   name="registered_new_strings[0][string]">
                        </td>
                        <td>
                            <input style="width: 100%;border-color: #ff7900" type="text" placeholder="New Name"
                                   name="registered_new_strings[0][name]">
                        </td>
                        <td>
                            <input style="width: 100%;border-color: #ff7900" type="text" placeholder="New Group"
                                   name="registered_new_strings[0][group]" readonly
                                   value="FoxApp">
                        </td>
                        <td>
                            <i class="dashicons dashicons-plus" style="cursor:pointer;color: #ff7900"
                               title="<?php _e( 'Complete inputs for adding new string', $this->plugin_text_domain ) ?>"></i>
                        </td>
                    </tr>
                    </tbody>
                </table>
                </td>
                </tr>
                </tbody>
                </table>
                <script>
                    jQuery(".remove_strings_button").click(function () {
                        jQuery(this).parent().animate({
                            height: "-=50",
                            opacity: "toggle",
                        }, 1000, function () {
                            jQuery(this).remove();
                        });
                    });
                </script>
            </div>
            <div style="clear:both"></div>
            <input type="submit"
                   class="button-primary"
                   style="margin-top:40px"
                   value="<?php _e( "Save settings", $this->plugin_text_domain ) ?>"/>
        </form>
		<?php
	}

	public function slugify( $text ) {
		$text = str_replace( '-', '_', $text );

		return sanitize_title( $text );
	}

	public function init() {
		if ( function_exists( 'pll_register_string' ) ) {
			$registered_strings = get_option( 'registered_strings' . $this->plugin_identifier, "[]" );
			$registered_strings = json_decode( $registered_strings, true );

            foreach ($registered_strings as $string){
	            pll_register_string( $string['string'], $string['name'], $string['group'] );
            }
		}
	}

	public function translated_text_with_polylang( $atts ) {
		$atts = shortcode_atts( array(
			'text' => ''
		), $atts );

		if ( function_exists( 'pll_e' ) ) {
			return pll_e( $atts['text'] );
		} else {
			return $atts['text'];
		}
	}
}

new RegisterPllStrings();

// Init Fox App GitHub Updater for current Plugin File
add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'FoxApp\GitHub\Init' ) ) {
			new \FoxApp\GitHub\Init( __FILE__ );
		}
	}
);