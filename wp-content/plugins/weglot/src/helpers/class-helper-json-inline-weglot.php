<?php

namespace WeglotWP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * @since 2.0
 */
abstract class Helper_Json_Inline_Weglot {

	/**
	 * @since 2.0
	 * @param string $string
	 * @return string
	 */
	public static function format_for_api( $string ) {
		$string = '"' . $string . '"';
		return json_decode( str_replace( '\\/', '/', str_replace( '\\\\', '\\', $string ) ) );
	}

	/**
	 * @since 2.0
	 * @param string $string
	 * @return string
	 */
	public static function unformat_from_api( $string ) {
		$string = str_replace( '"', '', str_replace( '/', '\\\\/', str_replace( '\\u', '\\\\u', json_encode( $string ) ) ) ); //phpcs:ignore
		return $string;
	}
}
