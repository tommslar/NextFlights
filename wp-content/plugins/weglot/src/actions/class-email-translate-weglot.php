<?php

namespace WeglotWP\Actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WeglotWP\Models\Hooks_Interface_Weglot;

use Weglot\Client\Client;
use Weglot\Parser\Parser;
use Weglot\Parser\ConfigProvider\ServerConfigProvider;


/**
 * Translate Emails who use wp_mail
 *
 * @since 2.0
 *
 */
class Email_Translate_Weglot implements Hooks_Interface_Weglot {

	/**
	 * @since 2.0
	 */
	public function __construct() {
		$this->option_services           = weglot_get_service( 'Option_Service_Weglot' );
		$this->request_url_services      = weglot_get_service( 'Request_Url_Service_Weglot' );
	}

	/**
	 * @see Hooks_Interface_Weglot
	 *
	 * @since 2.0
	 * @return void
	 */
	public function hooks() {
		add_filter( 'wp_mail', [ $this, 'weglot_translate_emails' ], 10, 1 );
	}

	/**
	 * Translate emails
	 *
	 * @since 2.0
	 * @param array $args
	 * @return array
	 */
	public function weglot_translate_emails( $args ) {
		$translate_email = $this->option_services->get_option( 'email_translate' );
		if ( ! $translate_email ) {
			return $args;
		}

		$current_and_original_language   = weglot_get_current_and_original_language();

		$message_and_subject = [
			'subject' => $args['subject'],
			'message' => $args['message'],
		];

		if ( $current_and_original_language['current'] !== $current_and_original_language['original'] ) {
			$message_and_subject_translated = $this->translate_email( $message_and_subject, $current_and_original_language['current'] );
		} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) { //phpcs:ignore
			$url                     = $this->request_url_services
											->create_url_object( $_SERVER['HTTP_REFERER'] ); //phpcs:ignore

			$choose_current_language = $url->detectCurrentLanguage();
			if ( $choose_current_language !== $current_and_original_language['original'] ) { //If language in referer
				$message_and_subject_translated = $this->translate_email( $message_and_subject, $choose_current_language );
			} elseif ( strpos( $_SERVER['HTTP_REFERER'], 'wg_language=' ) !== false ) { //phpcs:ignore
				//If language in parameter

				$pos                         = strpos( $_SERVER['HTTP_REFERER'], 'wg_language=' ); //phpcs:ignore
				$start                       = $pos + strlen( 'wg_language=' );
				$choose_current_language     = substr( $_SERVER['HTTP_REFERER'], $start, 2 ); //phpcs:ignore
				if ( $choose_current_language && $choose_current_language !== $current_and_original_language['original'] ) {
					$message_and_subject_translated = $this->translate_email( $message_and_subject, $choose_current_language );
				}
			}
		}

		if ( strpos( $message_and_subject_translated['subject'], '</p>' ) !== false ) {
			$pos             = strpos( $message_and_subject_translated['subject'], '</p>' ) + 4;
			$args['subject'] = substr( $message_and_subject_translated['subject'], 3, $pos - 7 );
			$args['message'] = $message_and_subject_translated['message'];
		}

		return $args;
	}

	/**
	 * Translate email with parser
	 *
	 * @param string $body
	 * @param string $language
	 * @param mixed $args
	 * @return mixed
	 */
	protected function translate_email( $args, $language ) {
		$api_key            = $this->option_services->get_option( 'api_key' );

		if ( ! $api_key ) {
			return $args;
		}

		$current_and_original_language   = weglot_get_current_and_original_language();
		$exclude_blocks                  = $this->option_services->get_exclude_blocks();

		$config             = new ServerConfigProvider();
		$client             = new Client( $api_key );
		$parser             = new Parser( $client, $config, $exclude_blocks );
		$translated_subject = $parser->translate( '<p>' . $args['subject'] . '</p>', $current_and_original_language['original'], $current_and_original_language['current'] ); //phpcs:ignore

		$config             = new ServerConfigProvider();
		$client             = new Client( $api_key );
		$parser             = new Parser( $client, $config, $exclude_blocks );
		$translated_message = $parser->translate( $args['message'], $current_and_original_language['original'], $current_and_original_language['current'] ); //phpcs:ignore

		return [
			'subject' => $translated_subject,
			'message' => $translated_message,
		];
	}
}
