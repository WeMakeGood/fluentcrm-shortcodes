=== FluentCRM Shortcodes ===
Contributors: WeMakeGood
Tags: fluentcrm, shortcode, contact, crm
Requires at least: 5.0
Tested up to: 6.8.3
Requires PHP: 7.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display FluentCRM contact information dynamically using simple shortcodes.

== Description ==

FluentCRM Shortcodes allows you to display FluentCRM contact information anywhere on your WordPress site using simple, flexible shortcodes. Retrieve standard contact fields, custom fields, and format the output with sprintf, regex patterns, or template syntax.

**Key Features:**

* Display any FluentCRM contact field or custom field
* Multiple formatting options (sprintf, regex, or template syntax)
* Conditional display based on field values
* Admin-only testing parameter for specific contacts
* Debug mode to troubleshoot field lookups
* Security-focused with field whitelisting and admin-only features
* Prepared SQL statements to prevent injection

== Installation ==

1. Upload the `fluentcrm-shortcodes` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin Plugins menu
3. Use the shortcodes in pages, posts, or any shortcode-enabled area

== Usage ==

=== Basic Shortcode ===

Display a contact's email address (for the currently logged-in user):

`[fluentcrm_contact field="email"]`

Display a custom field (like Classy ID):

`[fluentcrm_contact field="classy_id"]`

=== Available Fields ===

**Standard Fields (Whitelisted):**

* `email`
* `first_name`
* `last_name`
* `phone`
* `city`
* `state`
* `postal_code`
* `country`
* `timezone`

**Custom Fields:**

Any custom field name can be used (e.g., `classy_id`, `givebutter_id`, `legacy_total_order_value`, etc.)

=== Formatting Output ===

**Template Syntax** - Embed value in text:

`[fluentcrm_contact field="first_name" format="Welcome, {value}!"]`

Output: `Welcome, John!`

**Sprintf Formatting** - Professional formatting:

Currency (2 decimal places):
`[fluentcrm_contact field="legacy_total_order_value" format="$%.2f"]`
Output: `$87.12`

Integers with leading zeros:
`[fluentcrm_contact field="legacy_total_order_count" format="%03d"]`
Output: `016`

String formatting:
`[fluentcrm_contact field="email" format="Contact: %s"]`
Output: `Contact: user@example.com`

**Regex Formatting** - Pattern-based transformation:

Transform date format (YYYY-MM-DD to MM/DD/YYYY):
`[fluentcrm_contact field="legacy_first_order_date" format="/^(\d{4})-(\d{2})-(\d{2})/|$2/$3/$1"]`
Output: `02/13/2025`

=== Conditional Display ===

Display content only if a field exists (isset):

`[fluentcrm_contact field="classy_id" condition="isset"]
  <a href="https://classy.org/login?id={value}">Login to Classy</a>
[/fluentcrm_contact]`

The `{value}` placeholder is replaced with the field value inside nested content.

**Available Conditions:**

* `isset` - Field exists and is not empty
* `equals` - Field equals a specific value
* `greater_than` - Field is numerically greater than a value
* `less_than` - Field is numerically less than a value
* `contains` - Field contains a substring

**Examples:**

Show link if user has a Givebutter ID:

`[fluentcrm_contact field="givebutter_id" condition="isset"]
  <a href="https://givebutter.com/user/{value}">View Givebutter Profile</a>
[/fluentcrm_contact]`

Show message if total orders greater than 10:

`[fluentcrm_contact field="legacy_total_order_count" condition="greater_than" condition_value="10"]
  <p>Thank you for being a loyal supporter with over 10 orders!</p>
[/fluentcrm_contact]`

=== All Attributes ===

* `field` (required) - Contact field or custom meta key to display
* `format` (optional) - Format string for output (sprintf, regex, or template syntax)
* `condition` (optional) - Conditional logic type (isset, equals, greater_than, less_than, contains)
* `condition_value` (optional) - Value to compare against for conditions
* `user_id` (optional) - FluentCRM contact ID to display (admin only, for testing)
* `not_found` (optional) - Text to display if contact or field not found (default: empty string)
* `debug` (optional) - Show debug information (admin only, use `debug="1"`)

=== Admin-Only Testing ===

Test a specific contact by ID (requires admin privileges):

`[fluentcrm_contact field="classy_id" user_id="42"]`

Combined with formatting:

`[fluentcrm_contact field="legacy_total_order_value" user_id="42" format="$%.2f"]`

=== Debug Mode ===

Enable debug output to troubleshoot field lookups (admin only):

`[fluentcrm_contact field="classy_id" debug="1"]`

This displays:
* Contact ID found
* Field name requested
* Whether it's a safe field or custom field
* All available custom fields for this contact
* Whether the requested field was found

=== Not Found Handling ===

Specify fallback text if contact or field not found:

`[fluentcrm_contact field="classy_id" not_found="No Classy ID found"]`

== Examples ==

**Display user's name:**

`Hello, [fluentcrm_contact field="first_name"]!`

**Format donor's lifetime value:**

`Your lifetime giving: [fluentcrm_contact field="legacy_total_order_value" format="$%.2f"]`

**Show external login link (if account exists):**

`[fluentcrm_contact field="classy_id" condition="isset"]
  <a href="https://classy.org/dashboard?uid={value}" class="btn btn-primary">
    Login to Classy Dashboard
  </a>
[/fluentcrm_contact]`

**Display formatted order date:**

`Your first order was on: [fluentcrm_contact field="legacy_first_order_date" format="/^(\d{4})-(\d{2})-(\d{2})/|$2/$3/$1"]`

**Show VIP message for high-value donors:**

`[fluentcrm_contact field="legacy_total_order_value" condition="greater_than" condition_value="500"]
  <p class="vip-badge">You're a VIP supporter!</p>
[/fluentcrm_contact]`

== FAQ ==

= Why doesn't my custom field show up? =

Use debug mode to see available fields:

`[fluentcrm_contact field="my_field" debug="1"]`

Check that your field name matches exactly (case-sensitive). Common issues:
* Field name has spaces or special characters
* Field hasn't been saved for this contact yet
* Field uses a different storage location

= Can I use this shortcode in widgets? =

Yes, if your theme/page builder supports shortcodes in widgets. Some page builders like Elementor have special support for this.

= Does this shortcode work for non-logged-in users? =

No. The shortcode requires a logged-in user (it uses the current user's email to look up their FluentCRM contact). Use the `user_id` parameter with an admin account to display specific contacts for testing.

= Can I nest multiple shortcodes? =

Yes. The plugin uses WordPress's `do_shortcode()` function inside conditional blocks, so you can nest shortcodes with `[fluentcrm_contact]...[other-shortcode]...[/fluentcrm_contact]`.

= What happens if a user isn't in FluentCRM? =

The `not_found` text will display. No error is shown to regular users.

= Can I format dates in any format? =

Yes, using regex. Any strtotime-compatible date can be reformatted with regex capture groups:

`[fluentcrm_contact field="order_date" format="/^(\d{4})-(\d{2})-(\d{2})/|$3/$2/$1"]` converts 2025-11-14 to 14/11/2025

== Security ==

* Field access is restricted to a whitelist of safe standard fields
* Custom fields are queried from `_fc_subscriber_meta` only
* All database queries use prepared statements
* Output is escaped with `esc_html()`
* Admin-only features (`user_id`, `debug`) are permission-checked
* No sensitive fields exposed in debug output

== Changelog ==

= 0.1.0 =
* Initial release
* Display FluentCRM contact fields and custom fields
* Support for sprintf, regex, and template formatting
* Conditional display with multiple comparison types
* Debug mode for troubleshooting
* Admin-only testing with user_id parameter
* Comprehensive security implementation

== Support ==

For issues, questions, or contributions, visit:
https://github.com/WeMakeGood/fluentcrm-shortcodes

== License ==

This plugin is licensed under the GPLv2 or later. See LICENSE for details.
