<?php
/**
 * Plugin Name:     FluentCRM Shortcodes
 * Plugin URI:      https://github.com/WeMakeGood/fluentcrm-shortcodes
 * Description:     Display FluentCRM contact information via shortcodes
 * Author:          WeMakeGood
 * Author URI:      https://wemakegood.com
 * Text Domain:     fluentcrm-shortcodes
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Fluentcrm_Shortcodes
 */

namespace FluentCRM_Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ============================================================================
 * FLUENTCRM CONTACT QUERY CLASS
 * ============================================================================
 * Handles all database queries to FluentCRM tables (_fc_subscribers, _fc_subscriber_meta)
 *
 * Public Methods:
 * - get_contact_by_email( $email ) : array|null
 * - get_contact_by_id( $contact_id ) : array|null
 * - get_contact_field( $contact_id, $field_name ) : mixed
 * - get_contact_meta( $contact_id, $meta_key ) : mixed
 */
class Contact_Query {

	/**
	 * Whitelist of safe fields from _fc_subscribers table that can be publicly displayed.
	 *
	 * @var array
	 */
	private static $safe_fields = array(
		'email',
		'first_name',
		'last_name',
		'phone',
		'city',
		'state',
		'postal_code',
		'country',
		'timezone',
	);

	/**
	 * Get a contact by email address.
	 *
	 * @param string $email The contact's email address.
	 * @return array|null The contact data or null if not found.
	 */
	public static function get_contact_by_email( $email ) {
		global $wpdb;

		$table = $wpdb->prefix . 'fc_subscribers';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$contact = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s LIMIT 1", $email ),
			ARRAY_A
		);

		return $contact ?: null;
	}

	/**
	 * Get a contact by FluentCRM contact ID.
	 *
	 * @param int $contact_id The FluentCRM contact ID.
	 * @return array|null The contact data or null if not found.
	 */
	public static function get_contact_by_id( $contact_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'fc_subscribers';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$contact = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $contact_id ),
			ARRAY_A
		);

		return $contact ?: null;
	}

	/**
	 * Get a specific field value from a contact.
	 *
	 * First checks the _fc_subscribers table for whitelisted fields,
	 * then checks _fc_subscriber_meta for custom fields.
	 *
	 * @param int    $contact_id The FluentCRM contact ID.
	 * @param string $field_name The field name to retrieve.
	 * @return mixed|null The field value or null if not found.
	 */
	public static function get_contact_field( $contact_id, $field_name ) {
		// Check if it's a safe whitelisted field from _fc_subscribers.
		if ( in_array( $field_name, self::$safe_fields, true ) ) {
			$contact = self::get_contact_by_id( $contact_id );
			if ( $contact && isset( $contact[ $field_name ] ) ) {
				return $contact[ $field_name ];
			}
			return null;
		}

		// Otherwise, treat it as a custom field in _fc_subscriber_meta.
		return self::get_contact_meta( $contact_id, $field_name );
	}

	/**
	 * Get a custom field value from _fc_subscriber_meta.
	 *
	 * Custom fields in FluentCRM are stored with object_type = 'custom_field'.
	 * This method queries only custom fields, not system options.
	 *
	 * @param int    $contact_id The FluentCRM contact ID.
	 * @param string $meta_key   The meta key to retrieve.
	 * @return mixed|null The meta value or null if not found.
	 */
	public static function get_contact_meta( $contact_id, $meta_key ) {
		global $wpdb;

		$table = $wpdb->prefix . 'fc_subscriber_meta';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_value = $wpdb->get_var(
			$wpdb->prepare( "SELECT `value` FROM {$table} WHERE subscriber_id = %d AND `key` = %s AND object_type = 'custom_field' LIMIT 1", $contact_id, $meta_key )
		);

		return $meta_value ?: null;
	}

	/**
	 * Check if a field is a whitelisted safe field.
	 *
	 * @param string $field_name The field name to check.
	 * @return bool True if the field is whitelisted.
	 */
	public static function is_safe_field( $field_name ) {
		return in_array( $field_name, self::$safe_fields, true );
	}

	/**
	 * Get all custom field meta keys for a contact (for debugging).
	 *
	 * Only returns custom_field object_type entries, not system options.
	 *
	 * @param int $contact_id The FluentCRM contact ID.
	 * @return array Array of custom field keys and values.
	 */
	public static function get_all_contact_meta( $contact_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'fc_subscriber_meta';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_data = $wpdb->get_results(
			$wpdb->prepare( "SELECT `key`, `value` FROM {$table} WHERE subscriber_id = %d AND object_type = 'custom_field' ORDER BY `key` ASC", $contact_id ),
			ARRAY_A
		);

		$result = array();
		if ( $meta_data ) {
			foreach ( $meta_data as $item ) {
				$result[ $item['key'] ] = $item['value'];
			}
		}

		return $result;
	}
}

/**
 * ============================================================================
 * CONTACT FORMATTER CLASS
 * ============================================================================
 * Handles formatting of contact field values using sprintf, regex, and template syntax.
 *
 * Public Methods:
 * - format_value( $value, $format ) : string
 * - format_sprintf( $value, $format_string ) : string
 * - format_template( $value, $template ) : string
 * - format_regex( $value, $pattern, $replacement ) : string
 */
class Contact_Formatter {

	/**
	 * Format a value using sprintf, regex, or curly brace template syntax.
	 *
	 * Sprintf format: "%.2f" for currency, "%s" for strings, "%d" for integers, etc.
	 * Regex format: Use pipe-separated pattern|replacement (e.g., "/^\d{4}/|YEAR")
	 * Template format: Use {value} to embed the field value (e.g., "Welcome, {value}!")
	 *
	 * @param mixed  $value  The value to format.
	 * @param string $format The format string (sprintf, regex, or template pattern).
	 * @return string The formatted value.
	 */
	public static function format_value( $value, $format ) {
		if ( empty( $format ) ) {
			return (string) $value;
		}

		// Try curly brace template syntax first.
		if ( strpos( $format, '{value}' ) !== false ) {
			return self::format_template( $value, $format );
		}

		// Try sprintf formatting.
		if ( strpos( $format, '%' ) !== false ) {
			return self::format_sprintf( $value, $format );
		}

		// Try regex formatting if it looks like a regex pattern.
		if ( strpos( $format, '/' ) !== false && strpos( $format, '|' ) !== false ) {
			return self::format_regex( $value, $format );
		}

		// Fallback: return value as-is.
		return (string) $value;
	}

	/**
	 * Format a value using sprintf-style formatting.
	 *
	 * Examples:
	 * - "%.2f" for currency: sprintf("$%.2f", 42.5) => "$42.50"
	 * - "%s" for strings: sprintf("Hello %s", "World") => "Hello World"
	 * - "%d" for integers: sprintf("%03d", 5) => "005"
	 *
	 * @param mixed  $value          The value to format.
	 * @param string $format_string  The sprintf format string.
	 * @return string The formatted value.
	 */
	public static function format_sprintf( $value, $format_string ) {
		try {
			// Use sprintf to format the value.
			return sprintf( $format_string, $value );
		} catch ( \Exception $e ) {
			// If sprintf fails, return the value as-is.
			return (string) $value;
		}
	}

	/**
	 * Format a value using curly brace template syntax.
	 *
	 * The {value} placeholder will be replaced with the actual field value.
	 *
	 * Examples:
	 * - "Welcome, {value}!" with "John" => "Welcome, John!"
	 * - "Your balance is ${value}" with "42.50" => "Your balance is $42.50"
	 * - "Contact: {value}" with "user@example.com" => "Contact: user@example.com"
	 *
	 * @param mixed  $value    The value to embed.
	 * @param string $template The template string with {value} placeholder.
	 * @return string The formatted value.
	 */
	public static function format_template( $value, $template ) {
		return str_replace( '{value}', (string) $value, $template );
	}

	/**
	 * Format a value using regex pattern matching and replacement.
	 *
	 * Format: "pattern|replacement" where pattern uses regex syntax with capture groups.
	 * Examples:
	 * - "/^(\d{4})-(\d{2})-(\d{2})/|$2/$3/$1" transforms "2023-11-14" to "11/14/2023"
	 * - "/(\w+)/|[$1]" wraps words in brackets
	 *
	 * @param mixed  $value       The value to format.
	 * @param string $format_spec The format specification (pattern|replacement).
	 * @return string The formatted value.
	 */
	public static function format_regex( $value, $format_spec ) {
		$parts = explode( '|', $format_spec, 2 );
		if ( count( $parts ) !== 2 ) {
			return (string) $value;
		}

		$pattern     = $parts[0];
		$replacement = $parts[1];

		try {
			return preg_replace( $pattern, $replacement, (string) $value );
		} catch ( \Exception $e ) {
			// If regex fails, return the value as-is.
			return (string) $value;
		}
	}
}

/**
 * ============================================================================
 * CONTACT CONDITIONAL CLASS
 * ============================================================================
 * Handles conditional logic for displaying content based on contact field values.
 *
 * Supported Conditions:
 * - isset: Check if field is set and not empty
 * - equals: Check if field equals a specific value
 * - greater_than: Check if field is greater than a value (numeric)
 * - less_than: Check if field is less than a value (numeric)
 * - contains: Check if field contains a substring or value
 *
 * Public Methods:
 * - evaluate( $value, $condition, $condition_value ) : bool
 */
class Contact_Conditional {

	/**
	 * Evaluate a conditional expression against a field value.
	 *
	 * @param mixed  $value            The field value to evaluate.
	 * @param string $condition        The condition type (isset, equals, greater_than, less_than, contains).
	 * @param mixed  $condition_value  The value to compare against (not used for isset).
	 * @return bool True if the condition is met, false otherwise.
	 */
	public static function evaluate( $value, $condition, $condition_value = null ) {
		switch ( $condition ) {
			case 'isset':
				return ! empty( $value );

			case 'equals':
				return (string) $value === (string) $condition_value;

			case 'greater_than':
				return is_numeric( $value ) && is_numeric( $condition_value ) && (float) $value > (float) $condition_value;

			case 'less_than':
				return is_numeric( $value ) && is_numeric( $condition_value ) && (float) $value < (float) $condition_value;

			case 'contains':
				return strpos( (string) $value, (string) $condition_value ) !== false;

			default:
				return false;
		}
	}
}

/**
 * ============================================================================
 * FLUENTCRM SHORTCODE HANDLER CLASS
 * ============================================================================
 * Main shortcode handler that orchestrates queries, formatting, and conditionals.
 *
 * Shortcode Usage:
 * [fluentcrm_contact field="email" format="%.2f" user_id="5" not_found="N/A"]
 * [fluentcrm_contact field="email" condition="isset"]Show if email exists[/fluentcrm_contact]
 *
 * Attributes:
 * - field: (required) Contact field or custom meta key to display
 * - format: (optional) sprintf or regex format string for output
 * - user_id: (optional) FluentCRM contact ID (admin only; for testing)
 * - not_found: (optional) Text to display if contact not found (default: empty string)
 * - condition: (optional) Conditional logic (isset, equals, greater_than, less_than, contains)
 * - condition_value: (optional) Value to compare for conditions
 *
 * Public Methods:
 * - register() : void - Register the shortcode
 * - handle_shortcode( $atts, $content ) : string - Process the shortcode
 */
class FluentCRM_Contact_Shortcode {

	/**
	 * Register the shortcode with WordPress.
	 */
	public static function register() {
		add_shortcode( 'fluentcrm_contact', array( __CLASS__, 'handle_shortcode' ) );
	}

	/**
	 * Handle the [fluentcrm_contact] shortcode.
	 *
	 * @param array  $atts    The shortcode attributes.
	 * @param string $content The shortcode content (for nested usage).
	 * @return string The shortcode output.
	 */
	public static function handle_shortcode( $atts, $content = '' ) {
		// Parse and sanitize attributes.
		$atts = shortcode_atts(
			array(
				'field'            => '',
				'format'           => '',
				'user_id'          => '',
				'not_found'        => '',
				'condition'        => '',
				'condition_value'  => '',
				'debug'            => '',
			),
			$atts,
			'fluentcrm_contact'
		);

		// Check if debug mode is enabled (admin only).
		$debug = ! empty( $atts['debug'] ) && current_user_can( 'manage_options' );
		$debug_output = array();

		// Validate that field is provided.
		if ( empty( $atts['field'] ) ) {
			return '';
		}

		$field = sanitize_text_field( $atts['field'] );

		// Get the contact ID.
		$contact_id = self::get_contact_id( $atts );
		if ( ! $contact_id ) {
			if ( $debug ) {
				return '<pre style="background:#f5f5f5;padding:10px;border:1px solid #ddd;">DEBUG: Contact not found</pre>';
			}
			return esc_html( $atts['not_found'] );
		}

		if ( $debug ) {
			$debug_output[] = "Contact ID: {$contact_id}";
			$debug_output[] = "Field requested: {$field}";
			$debug_output[] = "Is safe field: " . ( Contact_Query::is_safe_field( $field ) ? 'yes' : 'no' );
			$debug_output[] = "\nAll available meta keys:";
			$all_meta = Contact_Query::get_all_contact_meta( $contact_id );
			foreach ( $all_meta as $key => $val ) {
				$debug_output[] = "  - {$key}: " . substr( (string) $val, 0, 50 );
			}
		}

		// Get the field value.
		$value = Contact_Query::get_contact_field( $contact_id, $field );
		if ( $value === null ) {
			if ( $debug ) {
				$debug_msg = implode( "\n", $debug_output );
				$debug_msg .= "\n\nField not found or returned null";
				return '<pre style="background:#f5f5f5;padding:10px;border:1px solid #ddd;">' . esc_html( $debug_msg ) . '</pre>';
			}
			return esc_html( $atts['not_found'] );
		}

		// If a condition is specified, evaluate it.
		if ( ! empty( $atts['condition'] ) ) {
			$condition       = sanitize_text_field( $atts['condition'] );
			$condition_value = sanitize_text_field( $atts['condition_value'] );

			if ( Contact_Conditional::evaluate( $value, $condition, $condition_value ) ) {
				// Condition is true; return the shortcode content (nested usage).
				// Replace {value} placeholder in content if present.
				$content = str_replace( '{value}', esc_html( (string) $value ), $content );
				return do_shortcode( $content );
			} else {
				// Condition is false; return empty.
				return '';
			}
		}

		// Format the value if a format string is provided.
		if ( ! empty( $atts['format'] ) ) {
			$format = sanitize_text_field( $atts['format'] );
			$value  = Contact_Formatter::format_value( $value, $format );
		}

		// Return the formatted value, escaped for HTML.
		return esc_html( (string) $value );
	}

	/**
	 * Get the contact ID based on shortcode attributes.
	 *
	 * Priority:
	 * 1. If user_id is provided and user is admin, use it.
	 * 2. Otherwise, use the current logged-in user's email to look up the contact.
	 *
	 * @param array $atts The shortcode attributes.
	 * @return int|null The contact ID or null if not found.
	 */
	private static function get_contact_id( $atts ) {
		// If user_id is provided, only allow admins to use it.
		if ( ! empty( $atts['user_id'] ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$user_id = intval( $atts['user_id'] );
				$contact = Contact_Query::get_contact_by_id( $user_id );
				if ( $contact ) {
					return $contact['id'];
				}
			}
			return null;
		}

		// Get the current user's email.
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			return null;
		}

		// Look up the contact by email.
		$contact = Contact_Query::get_contact_by_email( $current_user->user_email );
		if ( $contact ) {
			return $contact['id'];
		}

		return null;
	}
}

// Register the shortcode when WordPress initializes.
add_action( 'init', array( 'FluentCRM_Shortcodes\FluentCRM_Contact_Shortcode', 'register' ) );
