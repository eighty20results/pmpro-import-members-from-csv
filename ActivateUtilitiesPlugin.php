<?php


namespace E20R\Utilities;

use function activate_plugin;
use function add_action;
use function is_plugin_active;
use function is_wp_error;

if ( ! class_exists( 'E20R\Utilities\ActivateUtilitiesPlugin' ) ) {

	class ActivateUtilitiesPlugin {

		private static $plugin_name = 'E20R Utilities Module';

		/**
		 * Error message to show when the E20R Utilities Module plugin is not installed and active
		 *
		 * @param $dependent_plugin_name
		 */
		public static function plugin_not_installed( $dependent_plugin_name ) {

			printf(
				'<div src="notice notice-error"><p>%1$s</p></div>',
				sprintf(
					'Please download and install the <strong>%1$s</strong> plugin. It is required for the %2$s plugin to function.',
					sprintf(
						'<a href="%1$s">%2$s</a>',
						'https://eighty20results.com/product/e20r-utilities-module-for-other-plugins/',
						self::$plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					),
					$dependent_plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				)
			);
		}

		/**
		 * Attempt to activate the E20R Utilities Module plugin when the dependent plugin is activated
		 *
		 * @param string|null $path
		 *
		 * @return bool
		 */
		public static function attempt_activation( $path = null ) {

			if ( empty( $path ) ) {
				$path = sprintf(
					'%1$s/00-e20r-utilities/class-loader.php',
					ABSPATH . 'wp-content/plugins'
				);
			}

			if ( ! file_exists( $path ) ) {
				add_action(
					'admin_notices',
					function() use ( $path ) {
						printf(
							'<div src="notice notice-error"><p>%1$s</p></div>',
							sprintf(
								'The <strong>%1$s</strong> plugin was not found at %2$s. Please install it!',
								self::$plugin_name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$path // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							)
						);
					}
				);
				return false;
			}

			if (
				! function_exists( 'is_plugin_active' ) ||
				! is_plugin_active( '00-e20r-utilities/class-loader.php' )
			) {
				add_action(
					'update_option_active_plugins',
					function() {
						$result = activate_plugin( '00-e20r-utilities/class-utility-loader.php' );

						if ( ! is_wp_error( $result ) ) {
							add_action(
								'admin_notices',
								function () {
									printf(
										'<div src="notice notice-success"><p>%s</p></div>',
										sprintf(
											'The <strong>%s</strong> plugin is required & was auto-activated.',
											self::$plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										)
									);
								}
							);

							return true;
						} else {
							add_action(
								'admin_notices',
								function () {
									printf(
										'<div src="notice notice-error"><p>%s</p></div>',
										sprintf(
											'The <strong>%s</strong> plugin can\'t be auto-activated. Please install it!',
											self::$plugin_name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										)
									);
								}
							);
							return false;
						}
					}
				);
			}
			return true;
		}
	}
}

add_action( 'admin_init', '\E20R\Utilities\ActivateUtilitiesPlugin::attempt_activation', 9999, 1 );
