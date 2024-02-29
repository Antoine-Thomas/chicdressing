<?php
/*
Plugin Name: Ashe Extra
Plugin URI: http://wordpress.org/plugins/ashe-extra/
Description: Adds One Click Demo Import functionality for Ashe theme.
Author: WP Royal
Version: 1.2.9
License: GPLv2 or later
Author URI: https://wp-royal.com/
Text Domain: ashe-extra
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Ashextra_Options' ) ) {

	class Ashextra_Options {

		public function __construct() {

			add_action( 'admin_init', [ $this, 'init' ] );

			add_action( 'admin_menu', [ $this, 'ashextra_options_page' ] );

			add_action( 'wp_ajax_ashextra_contact_from_7_activation', [ $this, 'ashextra_contact_from_7_activation' ] );
			add_action( 'wp_ajax_ashextra_instagram_feed_activation', [ $this, 'ashextra_instagram_feed_activation' ] );
			add_action( 'wp_ajax_ashextra_mailchimp_newsletter_activation', [ $this, 'ashextra_mailchimp_newsletter_activation' ] );
			add_action( 'wp_ajax_ashextra_recent_posts_activation', [ $this, 'ashextra_recent_posts_activation' ] );
			// add_action( 'wp_ajax_ashextra_elementor_activation', [ $this, 'ashextra_elementor_activation' ] );
			add_action( 'wp_ajax_ashextra_royal_elementor_addons_activation', [ $this, 'ashextra_royal_elementor_addons_activation' ] );

			add_action( 'admin_enqueue_scripts', [ $this, 'ashextra_widget_enqueue_scripts' ] );

		}

		public function init() {
			add_action( 'load-importer-wordpress', [ $this, 'on_load' ] );
			add_filter( 'wp_import_post_meta', [ $this, 'on_wp_import_post_meta' ] );
			add_filter( 'wxr_importer.pre_process.post_meta', [$this, 'on_wxr_importer_pre_process_post_meta'] );

			// Change GUID image URL.
			add_filter( 'wp_import_post_data_processed', array( $this, 'pre_post_data' ), 10, 2 );

			add_filter( 'wxr_importer.pre_process.post', array( $this, 'pre_process_post' ), 10, 4 );
			add_filter( 'wxr_importer.pre_process.post', array( $this, 'fix_image_duplicate_issue' ), 10, 4 );

			// Import XML file
			add_action( 'wp_ajax_ashextra_import_xml', [ $this, 'ashextra_import_xml' ] );
		}

		public function pre_process_post( $data, $meta, $comments, $terms ) {

			if ( isset( $data['post_content'] ) ) {

				$meta_data = wp_list_pluck( $meta, 'key' );

				$is_attachment = ( 'attachment' === $data['post_type'] ) ? true : false;
				$is_elementor_page = in_array( '_elementor_version', $meta_data, true );

				if ( $is_attachment || $is_elementor_page ) {
					$data['post_content'] = '';
				}
			}

			return $data;
		}

		public function on_load() {
			$_GET['noheader'] = true;
		}
		public function on_wp_import_post_meta( $post_meta ) {
			foreach ( $post_meta as &$meta ) {
				if ( '_elementor_data' === $meta['key'] ) {
					$meta['value'] = wp_slash( $meta['value'] );
					break;
				}
			}

			return $post_meta;
		}

		public function on_wxr_importer_pre_process_post_meta( $post_meta ) {
			if ( '_elementor_data' === $post_meta['key'] ) {
				$post_meta['value'] = wp_slash( $post_meta['value'] );
			}

			return $post_meta;
		}

		public function pre_post_data( $postdata, $data ) {

			// Skip GUID field which point to the https://websitedemos.net.
			$postdata['guid'] = '';

			return $postdata;
		}

		public function fix_image_duplicate_issue( $data, $meta, $comments, $terms ) {

			$remote_url   = ! empty( $data['attachment_url'] ) ? $data['attachment_url'] : $data['guid'];
			$data['guid'] = $remote_url;

			return $data;
		}

		public function ashe_extra_subpage_content() { ?>

			<div class="extra-options-page-wrap">
				<div class="wrap extra-options">
					<h1>Demo Import Completed !</h1>
					<p class="after-import-notice">
						<?php esc_html_e( 'Please visit', 'ashe-extra' ); ?> <a href="<?php echo esc_url( admin_url('themes.php?page=about-ashe') ); ?>" class="visit-website"><?php esc_html_e( 'About Ashe Page', 'ashe-extra' ); ?></a>
						&nbsp;<?php esc_html_e( 'or', 'ashe-extra' ); ?>&nbsp;
						<a href="<?php echo esc_url( home_url() ); ?>" class="visit-website" target="_blank"><?php esc_html_e( 'Check out your new website.', 'ashe-extra' ); ?></a>
					</p>
				</div>
			</div>

		<?php }

		// Add Admin Menu
		public function ashextra_options_page() {
			add_menu_page(
				esc_html__( 'Ashe Extra', 'ashe-extra' ),
				esc_html__( 'Ashe Extra', 'ashe-extra' ),
				'manage_options',
				'ashe-extra',
				[ $this, 'ashextra_options_page_html' ],
				'dashicons-star-filled',
				80
			);

			add_submenu_page( 
				'ashe-extra-xxxxx', 
				esc_html__('Ashe Extra Sub Page', 'ashe-extra'),
				esc_html__('Ashe Extra Sub', 'ashe-extra'),
				'manage_options',
				'ashe-extra-sub',
				[$this, 'ashe_extra_subpage_content']
			);

			// add_action('admin_menu', [$this, 'remove_ashe_extra_sub_from_menu'], 999);
		}

		// Now remove it from the menu.
		public function remove_ashe_extra_sub_from_menu() {
			remove_submenu_page('ashe-extra', 'ashe-extra-sub');
		}

		// Render Admin Page HTML
		public function ashextra_options_page_html() {

			?>

			<div class="extra-options-page-wrap">

				<div class="wrap extra-options">
					<h1><?php esc_html_e( 'One Click Demo Import', 'ashe-extra' ); ?></h1>
					<p>
						<?php esc_html_e( 'Importing demo data (post, pages, images, theme settings, ...) is the easiest way to setup your theme.', 'ashe-extra' ); ?>
						<br>
						<?php esc_html_e( 'It will allow you to quickly edit everything instead of creating content from scratch.', 'ashe-extra' ); ?>
					</p>

					<p>
					<?php
					// ! is_plugin_active( 'elementor/elementor.php' ) || 
						if ( ! is_plugin_active( 'royal-elementor-addons/wpr-addons.php' ) || ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) || ! is_plugin_active( 'instagram-feed/instagram-feed.php' ) || ! is_plugin_active( 'mailchimp-for-wp/mailchimp-for-wp.php' ) || ! is_plugin_active( 'recent-posts-widget-with-thumbnails/recent-posts-widget-with-thumbnails.php' ) ) {
							esc_html_e( 'All recommended plugins need to be installed and activated for this step.', 'ashe-extra' );
						}
					?>
					</p>

					<div class="ashextra-plugin-activation">
						<?php if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) : ?>
						<div class="plugin-box">
							<img src="<?php echo plugin_dir_url( __FILE__ ) .'assets/images/cf7.png'; ?>">
							<span><?php esc_html_e( 'Contact Form 7', 'ashe-extra' ); ?></span>
							<input type="checkbox" id="contact_from_7" name="contact_from_7" value="yes" checked>
							<label for="contact_from_7"></label>
						</div>
						<?php endif; ?>

						<?php if ( ! is_plugin_active( 'instagram-feed/instagram-feed.php' ) ) : ?>
						<div class="plugin-box">
							<img src="<?php echo plugin_dir_url( __FILE__ ) .'assets/images/instagram-feed.png'; ?>">
							<span><?php esc_html_e( 'Instagram Feed', 'ashe-extra' ); ?></span>
							<input type="checkbox" id="instagram_feed" name="instagram_feed" value="yes" checked>
							<label for="instagram_feed"></label>
						</div>
						<?php endif; ?>

						<?php if ( ! is_plugin_active( 'mailchimp-for-wp/mailchimp-for-wp.php' ) ) : ?>
						<div class="plugin-box">
							<img src="<?php echo plugin_dir_url( __FILE__ ) .'assets/images/mailchimp.png'; ?>">
							<span><?php esc_html_e( 'Mailchimp', 'ashe-extra' ); ?></span>
							<input type="checkbox" id="mailchimp_newsletter" name="mailchimp_newsletter" value="yes" checked>
							<label for="mailchimp_newsletter"></label>
						</div>
						<?php endif; ?>

						<?php if ( ! is_plugin_active( 'recent-posts-widget-with-thumbnails/recent-posts-widget-with-thumbnails.php' ) ) : ?>
						<div class="plugin-box">
							<img src="<?php echo plugin_dir_url( __FILE__ ) .'assets/images/recent-posts.png'; ?>">
							<span><?php esc_html_e( 'Recent Posts', 'ashe-extra' ); ?></span>
							<input type="checkbox" id="recent_posts" name="recent_posts" value="yes" checked>
							<label for="recent_posts"></label>
						</div>
						<?php endif; ?>

						<?php if ( ! is_plugin_active( 'elementor/elementor.php' ) && 1 == 2 ) : ?>
						<div class="plugin-box">
							<img src="<?php echo plugin_dir_url( __FILE__ ) .'assets/images/elementor.png'; ?>">
							<span><?php esc_html_e( 'Elementor', 'ashe-extra' ); ?></span>
							<input type="checkbox" id="elementor" name="elementor" value="yes" checked>
							<label for="elementor"></label>
						</div>
						<?php endif; ?>

						<?php if ( 2 < 1 && ! is_plugin_active( 'royal-elementor-addons/wpr-addons.php' ) ) : //temporary-change ?>
						<div class="plugin-box">
							<img src="<?php echo plugin_dir_url( __FILE__ ) .'assets/images/royal-addons.png'; ?>">
							<span><?php esc_html_e( 'Royal Elementor Addons', 'ashe-extra' ); ?></span>
							<input type="checkbox" id="royal_elementor_addons" name="royal_elementor_addons" value="yes" checked>
							<label for="royal_elementor_addons"></label>
						</div>
						<?php endif; ?>
					</div>
				
					<br>
					<button class="button button-primary" id="ashe-demo-import"><?php esc_html_e( 'Import Demo Content', 'ashe-extra' ); ?></button>
					<br><br>
					<em><?php esc_html_e( 'Import may take 1-2 minutes, please don\'t refresh this page until it\'s done!', 'ashe-extra' ); ?></em>

				</div>
				
			</div>

			<?php
		}

		// Install/Activate CF7 Plugin 
		public function ashextra_contact_from_7_activation() {

            if ( !current_user_can('activate_plugins') ) {
                return;
            }

			// Get the list of currently active plugins (Most likely an empty array)
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( true == $_POST['ashextra_plugin_checked'] ) {
				array_push( $active_plugins, 'contact-form-7/wp-contact-form-7.php' );
			}

			// Set the new plugin list in WordPress
			update_option( 'active_plugins', $active_plugins );

		}

		// Install/Activate Instagram Feed Plugin 
		public function ashextra_instagram_feed_activation() {

            if ( !current_user_can('activate_plugins') ) {
                return;
            }

			// Get the list of currently active plugins (Most likely an empty array)
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( true == $_POST['ashextra_plugin_checked'] ) {
				array_push( $active_plugins, 'instagram-feed/instagram-feed.php' );
			}

			// Set the new plugin list in WordPress
			update_option( 'active_plugins', $active_plugins );

			// Get Instagram Options
			$instagram_options = get_option( 'sb_instagram_settings' );

			// Set Instagram Options
			$instagram_options['sb_instagram_num'] = '9';
			$instagram_options['sb_instagram_cols'] = '3';
			$instagram_options['sb_instagram_image_padding'] = '0';
			$instagram_options['sb_instagram_show_header'] = false;
			$instagram_options['sb_instagram_show_btn'] = false;
			$instagram_options['sb_instagram_show_follow_btn'] = false;

			// Update Instagram Options
			update_option( 'sb_instagram_settings', $instagram_options );

		}

		// Install/Activate Mailpoet Plugin 
		public function ashextra_mailchimp_newsletter_activation() {

            if ( !current_user_can('activate_plugins') ) {
                return;
            }

			// Get the list of currently active plugins (Most likely an empty array)
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( true == $_POST['ashextra_plugin_checked'] ) {
				array_push( $active_plugins, 'mailchimp-for-wp/mailchimp-for-wp.php' );
			}

			// Set the new plugin list in WordPress
			update_option( 'active_plugins', $active_plugins );

		}

		// Install/Activate Recent Posts Widget Plugin 
		public function ashextra_recent_posts_activation() {

            if ( !current_user_can('activate_plugins') ) {
                return;
            }

			// Get the list of currently active plugins (Most likely an empty array)
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( true == $_POST['ashextra_plugin_checked'] ) {
				array_push( $active_plugins, 'recent-posts-widget-with-thumbnails/recent-posts-widget-with-thumbnails.php' );
			}

			// Set the new plugin list in WordPress
			update_option( 'active_plugins', $active_plugins );

		}

		// Install/Activate Elementor Plugin 
		public function ashextra_elementor_activation() {

			// Get the list of currently active plugins (Most likely an empty array)
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( true == $_POST['ashextra_plugin_checked'] ) {
				array_push( $active_plugins, 'elementor/elementor.php' );
			}

			// Set the new plugin list in WordPress
			update_option( 'active_plugins', $active_plugins );

		}

		// Install/Activate Royal Elementor Addons Plugin 
		public function ashextra_royal_elementor_addons_activation() {

            if ( !current_user_can('activate_plugins') ) {
                return;
            }
            
			// Get the list of currently active plugins (Most likely an empty array)
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( true == $_POST['ashextra_plugin_checked'] ) {
				array_push( $active_plugins, 'royal-elementor-addons/wpr-addons.php' );
			}

			// Set the new plugin list in WordPress
			update_option( 'active_plugins', $active_plugins );

		}
		
		// Import
		public function ashextra_import_xml() {
			require ABSPATH . 'wp-admin/includes/class-wp-importer.php';
			require plugin_dir_path( __FILE__ ) . 'includes/importers/logger.php';
			require plugin_dir_path( __FILE__ ) . 'includes/importers/wxr-importer.php';

			// Turn off PHP output compression
			$previous = error_reporting( error_reporting() ^ E_WARNING );
			ini_set( 'output_buffering', 'off' );
			ini_set( 'zlib.output_compression', false );
			error_reporting( $previous );

			if ( $GLOBALS['is_nginx'] ) {
				// Setting this header instructs Nginx to disable fastcgi_buffering
				// and disable gzip for this request.
				header( 'X-Accel-Buffering: no' );
				header( 'Content-Encoding: none' );
			}

			// Start the event stream.
			header( 'Content-Type: text/event-stream' );


			// 2KB padding for IE
			echo ':' . str_repeat( ' ', 2048 ) . "\n\n";

			// Time to run the import!
			set_time_limit( 0 );

			// Ensure we're not buffered.
			wp_ob_end_flush_all();
			flush();


			$importer = new WXR_Importer([
				'fetch_attachments' => true,
				'default_author'    => get_current_user_id(),
			]);
			$logger = new WP_Importer_Logger_ServerSentEvents();
			$importer->set_logger( $logger );

			// Flush once more.
			flush();

			$err = $importer->import( plugin_dir_path( __FILE__ ) . 'includes/importers/data/demo-content.xml' );

			// Let the browser know we're done.
			$complete = array(
				'action' => 'complete',
				'error' => false,
			);
			if ( is_wp_error( $err ) ) {
				$complete['error'] = $err->get_error_message();
			}

	        // Import Widgets
	        $this->ashextra_widgets_import( plugin_dir_path( __FILE__ ) . 'includes/importers/data/demo-widgets.wie' );

			// Install Menus after Import
			$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );
			$top_menu = get_term_by( 'name', 'Top Menu', 'nav_menu' );

			set_theme_mod( 'nav_menu_locations', array(
					'main' => $main_menu->term_id,
					'top'  => $top_menu->term_id,
				)
			);

		    // Set Theme Customzie Options
		    $custom_theme_options = array(
				'featured_slider_label' => true,
		        'featured_links_label' => true,
		        'featured_links_sec_title' => '',
		        'featured_links_window' => true,
		        'featured_links_gutter_horz' => true,
		        'featured_links_columns' => '3',
		        'featured_links_title_1' => 'Features',
		        'featured_links_url_1' => 'https://wp-royal.com/themes/item-ashe-pro/?ref=ashe-demo-import-xml#!/features',
		        'featured_links_image_1' => '43',
		        'featured_links_title_2' => 'Try Ashe Pro',
		        'featured_links_url_2' => 'https://wp-royal.com/themes/ashe-pro/wp-content/plugins/open-house-theme-options/redirect.php?multisite=demo',
		        'featured_links_image_2' => '37',
		        'featured_links_title_3' => 'Buy Ashe Pro',
		        'featured_links_url_3' => 'https://wp-royal.com/themes/item-ashe-pro/?ref=ashe-demo-import-xml#!/download',
		        'featured_links_image_3' => '40',
		        'featured_links_title_4' => '',
		        'featured_links_url_4' => '',
		        'featured_links_image_4' => '',
		        'featured_links_title_5' => '',
		        'featured_links_url_5' => '',
		        'featured_links_image_5' => '',
		        'featured_links_title_6' => '',
		        'featured_links_url_6' => '',
		        'featured_links_image_6' => '',
				'social_media_icon_1' => 'facebook',
				'social_media_url_1' => '#',
				'social_media_icon_2' => 'twitter',
				'social_media_url_2' => '#',
				'social_media_icon_3' => 'instagram',
				'social_media_url_3' => '#',
				'social_media_icon_4' => 'pinterest',
				'social_media_url_4' => '#',
				'page_footer_copyright' => '© 2024 - All Rights Reserved.',
		    );
		    update_option( 'ashe_options', $custom_theme_options );

		    // Set Logo
			set_theme_mod( 'custom_logo', '7' );

			// Remove Tagline
			update_option( 'blogdescription', '' );

		    // Delete "Hello World" Post
		    $hello_world_post = get_page_by_path( 'hello-world', OBJECT, 'post' );

			if ( ! is_null( $hello_world_post ) ) {
				wp_delete_post( $hello_world_post->ID, true );
			}

			// Fix Elementor Images
			$this->fix_elementor_bg_images();

			exit;
		}

		// Widget Import Function
		public function ashextra_widgets_import( $file_path ) {

		    if ( ! file_exists($file_path) ) {
		        return;
		    }

		    // get import file and convert to array
		    $widgets_wie  = file_get_contents( $file_path );
		    $widgets_json = json_decode($widgets_wie, true);

		    // get active widgets
		    $active_widgets = get_option('sidebars_widgets');
		    $active_widgets['sidebar-left'] = array();
		    $active_widgets['sidebar-right'] = array();
		    $active_widgets['sidebar-alt'] = array();
		    $active_widgets['footer-widgets'] = array();
		    $active_widgets['instagram-widget'] = array();

		    // Sidebar Right
		    $counter = 0;
		    if ( isset($widgets_json['sidebar-right']) ) {
		        foreach( $widgets_json['sidebar-right'] as $widget_id => $widget_data ) {

		            // separate widget id/number
		            $instance_id     = preg_replace( '/-[0-9]+$/', '', $widget_id );
		            $instance_number = str_replace( $instance_id .'-', '', $widget_id );

		            if ( ! get_option('widget_'. $instance_id) ) {

		                // if is a single widget
		                $update_arr = array(
		                    $instance_number => $widget_data,
		                    '_multiwidget' => 1
		                );

		            } else {

		                // if there are multiple widgets
		                $update_arr = get_option('widget_'. $instance_id);
		                $update_arr[$instance_number] = $widget_data;

		            }

		            // update widget data
		            update_option( 'widget_' . $instance_id, $update_arr );
		            $active_widgets['sidebar-right'][$counter] = $widget_id;
		            $counter++;

		        }
		    }

		    // Sidebar Alt
		    $counter = 0;
		    if ( isset($widgets_json['sidebar-alt']) ) {
		        foreach( $widgets_json['sidebar-alt'] as $widget_id => $widget_data ) {

		            // separate widget id/number
		            $instance_id     = preg_replace( '/-[0-9]+$/', '', $widget_id );
		            $instance_number = str_replace( $instance_id .'-', '', $widget_id );

		            if ( ! get_option('widget_'. $instance_id) ) {

		                // if is a single widget
		                $update_arr = array(
		                    $instance_number => $widget_data,
		                    '_multiwidget' => 1
		                );

		            } else {

		                // if there are multiple widgets
		                $update_arr = get_option('widget_'. $instance_id);
		                $update_arr[$instance_number] = $widget_data;

		            }

		            // update widget data
		            update_option( 'widget_' . $instance_id, $update_arr );
		            $active_widgets['sidebar-alt'][$counter] = $widget_id;
		            $counter++;

		        }
		    }

		    // Footer Widgets
		    $counter = 0;
		    if ( isset($widgets_json['footer-widgets']) ) {
		        foreach( $widgets_json['footer-widgets'] as $widget_id => $widget_data ) {

		            // separate widget id/number
		            $instance_id     = preg_replace( '/-[0-9]+$/', '', $widget_id );
		            $instance_number = str_replace( $instance_id .'-', '', $widget_id );

		            if ( ! get_option('widget_'. $instance_id) ) {

		                // if is a single widget
		                $update_arr = array(
		                    $instance_number => $widget_data,
		                    '_multiwidget' => 1
		                );

		            } else {

		                // if there are multiple widgets
		                $update_arr = get_option('widget_'. $instance_id);
		                $update_arr[$instance_number] = $widget_data;

		            }

		            // update widget data
		            update_option( 'widget_' . $instance_id, $update_arr );
		            $active_widgets['footer-widgets'][$counter] = $widget_id;
		            $counter++;

		        }
		    }
		    
		    update_option( 'sidebars_widgets', $active_widgets );

		}

		// Fix Elementor Background Images
		public function fix_elementor_bg_images() {
			foreach( get_pages() as $page ) {
				$page_meta = get_post_meta( $page->ID, '_elementor_data', true );
			    
			    if ( ! empty($page_meta) && 'null' !== $page_meta ) {

					$elementor_data = json_decode($page_meta);

					foreach ( $elementor_data as $key => $value ) {
						if ( 'section' === $value->elType ) {
							if ( isset($value->settings->background_image) && '' !== $value->settings->background_image ) {
								$upload_dir = wp_upload_dir();
								$img_id = $value->settings->background_image->id;
								$img_url = $value->settings->background_image->url;
								$new_url = $upload_dir['baseurl'] . substr($img_url, (strpos($img_url, 'uploads') + 7));

								// Fix URL
								$value->settings->background_image->url = $new_url;
							}
							
						}
					}

					update_post_meta( $page->ID, '_elementor_data', wp_slash(json_encode($elementor_data)) );

			    }
			}
		}

		// Enqueue Scripts
		public function ashextra_widget_enqueue_scripts($hook) {
			// Disable Notifications
			wp_enqueue_style( 'plugin-notices-css', plugin_dir_url( __FILE__ ) . 'assets/css/notices.css' );

			if ( 'toplevel_page_ashe-extra' != $hook && 'admin_page_ashe-extra-sub' != $hook ) {
				return;
			}

			wp_enqueue_script( 'plugin-install' );
			wp_enqueue_script( 'updates' );
			wp_enqueue_script( 'plugin-options-js', plugin_dir_url( __FILE__ ) . 'assets/js/plugin-options.js', array(), '1.2.6' );

			// Enqueue Styles
			wp_enqueue_style( 'plugin-options-css', plugin_dir_url( __FILE__ ) . 'assets/css/plugin-options.css', array(), '1.2.6' );
		}

	} // end Ashextra_Options

}
new Ashextra_Options();