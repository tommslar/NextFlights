<?php

namespace WeglotWP\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Replace URL
 *
 * @since 2.0
 */
class Replace_Url_Service_Weglot {

	/**
	 * @since 2.0
	 */
	public function __construct() {
		$this->request_url_services      = weglot_get_service( 'Request_Url_Service_Weglot' );
		$this->replace_link_service      = weglot_get_service( 'Replace_Link_Service_Weglot' );
	}

	/**
	 * Replace link
	 *
	 * @param string $pattern
	 * @param string $translated_page
	 * @param string $type
	 * @return string
	 */
	public function modify_link( $pattern, $translated_page, $type ) {
		$current_language = weglot_get_current_language();

		preg_match_all( $pattern, $translated_page, $out, PREG_PATTERN_ORDER );
		$count_out_0 = count( $out[0] );
		for ( $i = 0;$i < $count_out_0; $i++ ) {
			$sometags    = ( isset( $out[1] ) ) ? $out[1][ $i ] : null;
			$quote1      = ( isset( $out[2] ) ) ? $out[2][ $i ] : null;
			$current_url = ( isset( $out[3] ) ) ? $out[3][ $i ] : null;
			$quote2      = ( isset( $out[4] ) ) ? $out[4][ $i ] : null;
			$sometags2   = ( isset( $out[5] ) ) ? $out[5][ $i ] : null;

			$length_link = apply_filters( 'weglot_length_replace_a', 1500 ); // Prevent error on long URL (preg_match_all Compilation failed: regular expression is too large at offset)
			if ( strlen( $current_url ) >= $length_link ) {
				continue;
			}

			if ( self::check_link( $current_url, $sometags, $sometags2 ) ) {
				$function_name = 'replace_' . $type;

				$translated_page = $this->replace_link_service->$function_name(
					$translated_page,
					$current_url,
					$quote1,
					$quote2,
					$sometags,
					$sometags2
				);
			}
		}

		return $translated_page;
	}

	/**
	 * @since 2.0
	 * @param string $current_url
	 * @param string $sometags
	 * @param string $sometags2
	 * @return string
	 */
	public function check_link( $current_url, $sometags = null, $sometags2 = null ) {
		$admin_url   = admin_url();
		$parsed_url  = wp_parse_url( $current_url );

		return (
			(
				( $current_url[0] === 'h' && $parsed_url['host'] === $_SERVER['HTTP_HOST'] ) || //phpcs:ignore
				( isset( $current_url[0] ) && $current_url[0] === '/' && ( isset( $current_url[1] ) ) && '/' !== $current_url[1] ) //phpcs:ignore
			) &&
			strpos( $current_url, $admin_url ) === false
			&& strpos( $current_url, 'wp-login' ) === false
			&& ! $this->is_link_a_file( $current_url )
			&& $this->request_url_services->is_eligible_url( $current_url )
			&& strpos( $sometags, 'data-wg-notranslate' ) === false
			&& strpos( $sometags2, 'data-wg-notranslate' ) === false
		);
	}

	/**
	 * @since 2.0
	 *
	 * @param string $current_url
	 * @return boolean
	 */
	public function is_link_a_file( $current_url ) {
		$files = [
			'pdf',
			'rar',
			'doc',
			'docx',
			'jpg',
			'jpeg',
			'png',
			'ppt',
			'pptx',
			'xls',
			'zip',
			'mp4',
			'xlsx',
		];

		foreach ( $files as $file ) {
			if ( self::ends_with( $current_url, '.' . $file ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * search forward starting from end minus needle length characters
	 * @since 2.0
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	public function ends_with( $haystack, $needle ) {
		$temp = strlen( $haystack );

		return '' === $needle ||
		(
			(  $temp - strlen( $needle ) ) >= 0 && strpos( $haystack, $needle, $temp ) !== false
		);
	}
}


