<?php
namespace AIOSEO\BrokenLinkChecker\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Error;

/** \WP_Upgrader class */
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

/** \Plugin_Upgrader class */
require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

/**
 * In WP 5.3 a PHP 5.6 splat operator (...$args) was added to \WP_Upgrader_Skin::feedback().
 * We need to remove all calls to *Skin::feedback() method, as we can't override it in own Skins
 * without breaking support for PHP 5.3-5.5.
 *
 * @internal Please do not use this class outside of core AIOSEO development.
 *
 * @since 1.0.0
 */
class PluginUpgraderSilentAjax extends \Plugin_Upgrader {
	/**
	 * An array of links to install the plugins from.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $pluginLinks = [
		'aioseo'               => 'https://downloads.wordpress.org/plugins/all-in-one-seo-pack.zip',
		'optinMonster'         => 'https://downloads.wordpress.org/plugin/optinmonster.zip',
		'wpForms'              => 'https://downloads.wordpress.org/plugin/wpforms-lite.zip',
		'miLite'               => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
		'emLite'               => 'https://downloads.wordpress.org/plugin/google-analytics-dashboard-for-wp.zip',
		'wpMail'               => 'https://downloads.wordpress.org/plugin/wp-mail-smtp.zip',
		'rafflePress'          => 'https://downloads.wordpress.org/plugin/rafflepress.zip',
		'seedProd'             => 'https://downloads.wordpress.org/plugin/coming-soon.zip',
		'trustPulse'           => 'https://downloads.wordpress.org/plugin/trustpulse-api.zip',
		'instagramFeed'        => 'https://downloads.wordpress.org/plugin/instagram-feed.zip',
		'facebookFeed'         => 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip',
		'twitterFeed'          => 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip',
		'youTubeFeed'          => 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip',
		'pushEngage'           => 'https://downloads.wordpress.org/plugins/pushengage.zip',
		'sugarCalendar'        => 'https://downloads.wordpress.org/plugins/sugar-calendar-lite.zip',
		'wpSimplePay'          => 'https://downloads.wordpress.org/plugins/stripe.zip',
		'easyDigitalDownloads' => 'https://downloads.wordpress.org/plugins/easy-digital-downloads.zip',
		'wpcode'               => 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip',
		'searchWp'             => '',
		'affiliateWp'          => '',
		'charitable'           => 'https://downloads.wordpress.org/plugin/charitable.zip',
		'duplicator'           => 'https://downloads.wordpress.org/plugin/duplicator.zip'
	];

	/**
	 * An array of links to install the plugins from wordpress.org.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $wpPluginLinks = [
		'aioseo'        => 'https://wordpress.org/plugins/all-in-one-seo-pack/',
		'optinMonster'  => 'https://wordpress.org/plugin/optinmonster/',
		'wpForms'       => 'https://wordpress.org/plugin/wpforms-lite/',
		'miLite'        => 'https://wordpress.org/plugin/google-analytics-for-wordpress/',
		'emLite'        => 'https://wordpress.org/plugin/google-analytics-dashboard-for-wp/',
		'wpMail'        => 'https://wordpress.org/plugin/wp-mail-smtp/',
		'rafflePress'   => 'https://wordpress.org/plugin/rafflepress/',
		'seedProd'      => 'https://wordpress.org/plugin/coming-soon/',
		'trustPulse'    => 'https://wordpress.org/plugin/trustpulse-api/',
		'instagramFeed' => 'https://wordpress.org/plugin/instagram-feed/',
		'facebookFeed'  => 'https://wordpress.org/plugin/custom-facebook-feed/',
		'twitterFeed'   => 'https://wordpress.org/plugin/custom-twitter-feeds/',
		'youTubeFeed'   => 'https://wordpress.org/plugin/feeds-for-youtube/',
		'pushEngage'    => 'https://wordpress.org/plugins/pushengage/',
		'sugarCalendar' => 'https://wordpress.org/plugins/sugar-calendar-lite/',
		'wpSimplePay'   => 'https://wordpress.org/plugins/stripe/',
		'searchWp'      => 'https://searchwp.com/',
		'affiliateWp'   => 'https://affiliatewp.com/',
		'wpcode'        => 'https://wordpress.org/plugins/insert-headers-and-footers/',
		'charitable'    => 'https://wordpress.org/plugins/charitable/',
		'duplicator'    => 'https://wordpress.org/plugins/duplicator/'
	];

	/**
	 * An array of slugs to check if plugins are activated.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $pluginSlugs = [
		'aioseo'                  => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
		'aioseoPro'               => 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php',
		'optinMonster'            => 'optinmonster/optin-monster-wp-api.php',
		'wpForms'                 => 'wpforms-lite/wpforms.php',
		'wpFormsPro'              => 'wpforms/wpforms.php',
		'miLite'                  => 'google-analytics-for-wordpress/googleanalytics.php',
		'miPro'                   => 'google-analytics-premium/googleanalytics-premium.php',
		'emLite'                  => 'google-analytics-dashboard-for-wp/gadwp.php',
		'emPro'                   => 'exactmetrics-premium/exactmetrics-premium.php',
		'wpMail'                  => 'wp-mail-smtp/wp_mail_smtp.php',
		'wpMailPro'               => 'wp-mail-smtp-pro/wp_mail_smtp.php',
		'rafflePress'             => 'rafflepress/rafflepress.php',
		'rafflePressPro'          => 'rafflepress-pro/rafflepress-pro.php',
		'seedProd'                => 'coming-soon/coming-soon.php',
		'seedProdPro'             => 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php',
		'trustPulse'              => 'trustpulse-api/trustpulse.php',
		'instagramFeed'           => 'instagram-feed/instagram-feed.php',
		'instagramFeedPro'        => 'instagram-feed-pro/instagram-feed.php',
		'facebookFeed'            => 'custom-facebook-feed/custom-facebook-feed.php',
		'facebookFeedPro'         => 'custom-facebook-feed-pro/custom-facebook-feed.php',
		'twitterFeed'             => 'custom-twitter-feeds/custom-twitter-feed.php',
		'twitterFeedPro'          => 'custom-twitter-feeds-pro/custom-twitter-feed.php',
		'youTubeFeed'             => 'feeds-for-youtube/youtube-feed.php',
		'youTubeFeedPro'          => 'youtube-feed-pro/youtube-feed.php',
		'pushEngage'              => 'pushengage/main.php',
		'sugarCalendar'           => 'sugar-calendar-lite/sugar-calendar-lite.php',
		'sugarCalendarPro'        => 'sugar-calendar/sugar-calendar.php',
		'wpSimplePay'             => 'stripe/stripe-checkout.php',
		'wpSimplePayPro'          => 'wp-simple-pay-pro-3/simple-pay.php',
		'easyDigitalDownloads'    => 'easy-digital-downloads/easy-digital-downloads.php',
		'easyDigitalDownloadsPro' => 'easy-digital-downloads-pro/easy-digital-downloads.php',
		'searchWp'                => 'searchwp/index.php',
		'affiliateWp'             => 'affiliate-wp/affiliate-wp.php',
		'wpcode'                  => 'insert-headers-and-footers/ihaf.php',
		'wpcodePro'               => 'wpcode-premium/wpcode.php',
		'charitable'              => 'charitable/charitable.php',
		'duplicator'              => 'duplicator/duplicator.php'
	];

	/**
	 * An array of links for admin settings.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $pluginAdminUrls = [
		'aioseo'                  => 'admin.php?page=aioseo-settings',
		'aioseoPro'               => 'admin.php?page=aioseo-settings',
		'optinMonster'            => 'admin.php?page=optin-monster-api-settings',
		'wpForms'                 => 'admin.php?page=wpforms-settings',
		'wpFormsPro'              => 'admin.php?page=wpforms-settings',
		'miLite'                  => 'admin.php?page=monsterinsights_settings#/',
		'miPro'                   => 'admin.php?page=monsterinsights_settings#/',
		'emLite'                  => 'admin.php?page=exactmetrics_settings#/',
		'emPro'                   => 'admin.php?page=exactmetrics_settings#/',
		'wpMail'                  => 'admin.php?page=wp-mail-smtp',
		'wpMailPro'               => 'admin.php?page=wp-mail-smtp',
		'seedProd'                => 'admin.php?page=seedprod_lite',
		'seedProdPro'             => 'admin.php?page=seedprod_pro',
		'rafflePress'             => 'admin.php?page=rafflepress_lite#/settings',
		'rafflePressPro'          => 'admin.php?page=rafflepress_pro#/settings',
		'trustPulse'              => 'admin.php?page=trustpulse',
		'instagramFeed'           => 'admin.php?page=sb-instagram-feed',
		'instagramFeedPro'        => 'admin.php?page=sb-instagram-feed',
		'facebookFeed'            => 'admin.php?page=cff-top',
		'facebookFeedPro'         => 'admin.php?page=cff-top',
		'twitterFeed'             => 'admin.php?page=ctf-settings',
		'twitterFeedPro'          => 'admin.php?page=ctf-settings',
		'youTubeFeed'             => 'admin.php?page=youtube-feed-settings',
		'youTubeFeedPro'          => 'admin.php?page=youtube-feed-settings',
		'pushEngage'              => 'admin.php?page=pushengage',
		'sugarCalendar'           => 'admin.php?page=sugar-calendar',
		'sugarCalendarPro'        => 'admin.php?page=sugar-calendar',
		'wpSimplePay'             => 'edit.php?post_type=simple-pay',
		'wpSimplePayPro'          => 'edit.php?post_type=simple-pay',
		'easyDigitalDownloads'    => 'edit.php?post_type=download&page=edd-settings',
		'easyDigitalDownloadsPro' => 'edit.php?post_type=download&page=edd-settings',
		'searchWp'                => 'options-general.php?page=searchwp',
		'affiliateWp'             => 'admin.php?page=affiliate-wp',
		'wpcode'                  => 'admin.php?page=wpcode',
		'wpcodePro'               => 'admin.php?page=wpcode',
		'charitable'              => 'admin.php?page=charitable-settings',
		'duplicator'              => 'admin.php?page=duplicator-settings'
	];
}