<?php

namespace WeglotWP\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Weglot\Util\Url;
use Weglot\Util\Server;


/**
 * Redirect URL
 *
 * @since 2.0
 */
class Redirect_Service_Weglot {
	/**
	 * @since 2.0
	 *
	 * @var string
	 */
	protected $weglot_url = null;

	/**
	 *
	 * @var boolean
	 */
	protected $no_redirect = false;

	/**
	 * @since 2.0
	 */
	public function __construct() {
		$this->option_services           = weglot_get_service( 'Option_Service_Weglot' );
		$this->request_url_services      = weglot_get_service( 'Request_Url_Service_Weglot' );
	}

	/**
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function get_no_redirect() {
		return $this->no_redirect;
	}

	/**
	 * @since 2.0
	 *
	 * @return string
	 */
	public function auto_redirect() {
		if ( ! isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) { //phpcs:ignore
			return;
		}

		$server_lang           = substr( $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2 ); //phpcs:ignore
		$destination_languages = weglot_get_destination_languages();

		if (
			in_array( $server_lang, $destination_languages ) && // phpcs:ignore
			weglot_get_original_language() === $this->request_url_services->get_current_language()
		) {
			$url_auto_redirect = apply_filters( 'weglot_url_auto_redirect', $this->request_url_services->get_weglot_url()->getForLanguage( $server_lang ) );
			wp_safe_redirect( $url_auto_redirect );
			exit();
		}
	}

	/**
	 * @since 2.0
	 *
	 * @return void
	 */
	public function verify_no_redirect() {
		if ( strpos( $this->request_url_services->get_weglot_url()->getUrl(), '?no_lredirect=true' ) === false ) {
			return;
		}

		$this->no_redirect = true;

		if ( isset( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore
			$_SERVER['REQUEST_URI'] = str_replace(
				'?no_lredirect=true',
				'',
				$_SERVER['REQUEST_URI'] //phpcs:ignore
			);
		}
	}
}


