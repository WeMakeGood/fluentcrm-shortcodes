# FluentCRM Shortcodes

Display FluentCRM contact information dynamically using simple, flexible WordPress shortcodes.

## Features

- **Display Contact Fields** - Show any standard FluentCRM field or custom field
- **Multiple Formatting Options** - Format output with sprintf, regex patterns, or template syntax
- **Conditional Display** - Show content only if conditions are met (isset, equals, greater_than, less_than, contains)
- **Admin Testing** - View specific contacts by ID (admin only)
- **Debug Mode** - Troubleshoot field lookups and see available custom fields
- **Security-Focused** - Field whitelisting, prepared statements, output escaping, permission checks

## Installation

1. Clone or download this repository to your WordPress plugins folder:
   ```bash
   git clone https://github.com/WeMakeGood/fluentcrm-shortcodes.git
   ```

2. Activate the plugin through the WordPress admin Plugins menu

3. Start using shortcodes on any page, post, or shortcode-enabled area

## Quick Start

### Basic Usage

Display the current user's email:
```
[fluentcrm_contact field="email"]
```

Display a custom field:
```
[fluentcrm_contact field="classy_id"]
```

### With Formatting

Format currency (2 decimal places):
```
[fluentcrm_contact field="legacy_total_order_value" format="$%.2f"]
```

Format a date (YYYY-MM-DD to MM/DD/YYYY):
```
[fluentcrm_contact field="legacy_first_order_date" format="/^(\d{4})-(\d{2})-(\d{2})/|$2/$3/$1"]
```

Embed value in text:
```
[fluentcrm_contact field="first_name" format="Welcome, {value}!"]
```

### Conditional Display

Show content only if a field exists:
```
[fluentcrm_contact field="classy_id" condition="isset"]
  <a href="https://classy.org/login?id={value}">Login to Classy</a>
[/fluentcrm_contact]
```

Show message if total orders > 10:
```
[fluentcrm_contact field="legacy_total_order_count" condition="greater_than" condition_value="10"]
  <p>Thank you for being a loyal supporter!</p>
[/fluentcrm_contact]
```

## Available Fields

### Standard Fields (Whitelisted)
- `email`
- `first_name`
- `last_name`
- `phone`
- `city`
- `state`
- `postal_code`
- `country`
- `timezone`

### Custom Fields
Any custom field name can be used. Examples:
- `classy_id`
- `givebutter_id`
- `legacy_total_order_value`
- `legacy_first_order_date`

## Shortcode Attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| `field` | Yes | Contact field or custom meta key |
| `format` | No | Format string (sprintf, regex, or template) |
| `condition` | No | Condition type: `isset`, `equals`, `greater_than`, `less_than`, `contains` |
| `condition_value` | No | Value to compare against for conditions |
| `user_id` | No | FluentCRM contact ID (admin only, for testing) |
| `not_found` | No | Text to display if contact/field not found |
| `debug` | No | Show debug info (admin only, use `debug="1"`) |

## Formatting Options

### Template Syntax
Embed the value in text using `{value}` placeholder:
```
[fluentcrm_contact field="first_name" format="Hello, {value}!"]
[fluentcrm_contact field="balance" format="Your balance: ${value}"]
```

### Sprintf Formatting
Professional formatting using PHP sprintf syntax:
```
[fluentcrm_contact field="amount" format="$%.2f"]        # Currency
[fluentcrm_contact field="count" format="%03d"]          # Padded integers
[fluentcrm_contact field="email" format="Email: %s"]     # Strings
```

### Regex Formatting
Pattern-based transformation with capture groups:
```
[fluentcrm_contact field="date" format="/^(d{4})-(d{2})-(d{2})/|$2/$3/$1"]  # Date reformatting
[fluentcrm_contact field="text" format="/(w+)/|[$1]"]   # Wrap words in brackets
```

**Note:** Due to WordPress attribute parsing, backslashes are automatically restored. You can write patterns with or without leading backslashes - both work:
- `/^(d{4})-(d{2})-(d{2})/|$2/$3/$1` ✓ (recommended in shortcodes)
- `/^(\d{4})-(\d{2})-(\d{2})/|$2/$3/$1` ✓ (also works)

## Conditional Logic

### Conditions

| Condition | Description | Example |
|-----------|-------------|---------|
| `isset` | Field exists and not empty | `condition="isset"` |
| `equals` | Field equals value | `condition="equals" condition_value="yes"` |
| `greater_than` | Field > value (numeric) | `condition="greater_than" condition_value="100"` |
| `less_than` | Field < value (numeric) | `condition="less_than" condition_value="50"` |
| `contains` | Field contains substring | `condition="contains" condition_value="test"` |

### Using {value} in Conditionals

When a condition is true, the nested content displays with `{value}` replaced. If a format is specified, the formatted value is used:

```
[fluentcrm_contact field="external_id" condition="isset"]
  <a href="https://external-system.com/user/{value}">
    View External Profile
  </a>
[/fluentcrm_contact]
```

Example with formatting (date reformatting in nested content):
```
[fluentcrm_contact field="legacy_first_order_date" format="/^(d{4})-(d{2})-(d{2}).*/|$2/$3/$1" condition="isset"]
  <p>Your first order was on {value}</p>
[/fluentcrm_contact]
```

In this case, `{value}` will be replaced with the formatted date (e.g., `02/13/2025`), not the raw timestamp.

## Advanced Examples

### VIP Donor Badge
```
[fluentcrm_contact field="legacy_total_order_value" condition="greater_than" condition_value="1000"]
  <span class="badge badge-gold">VIP Donor</span>
[/fluentcrm_contact]
```

### Personalized Greeting with Formatting
```
Hello, [fluentcrm_contact field="first_name" format="{value}"]!

You've donated: [fluentcrm_contact field="legacy_total_order_value" format="$%.2f"]
```

### External System Integration
```
[fluentcrm_contact field="givebutter_id" condition="isset"]
  <a href="https://givebutter.com/supporter/{value}" target="_blank">
    View your Givebutter profile
  </a>
[/fluentcrm_contact]

[fluentcrm_contact field="givebutter_id" condition="isset" not_found="Connect your Givebutter account"]
```

## Admin Features

### Debug Mode
View available custom fields for the current user:
```
[fluentcrm_contact field="any_field" debug="1"]
```

Shows:
- Contact ID
- Field name requested
- Whether it's a safe field
- List of all available custom fields

### Testing Specific Contacts
View a specific contact's data by ID (admin only):
```
[fluentcrm_contact field="classy_id" user_id="42"]
[fluentcrm_contact field="legacy_total_order_value" user_id="42" format="$%.2f"]
```

## Architecture

The plugin is built as a single, well-organized file with four main classes:

### Contact_Query
Handles all database queries to FluentCRM tables:
- `get_contact_by_email()` - Look up by email
- `get_contact_by_id()` - Look up by contact ID
- `get_contact_field()` - Get field value (auto-routes to standard or custom)
- `get_contact_meta()` - Query custom fields

**Key Features:**
- Field whitelisting for security
- Proper handling of MySQL reserved words (`key`, `value`)
- Filters custom fields by `object_type = 'custom_field'`

### Contact_Formatter
Formats output values in multiple ways:
- `format_value()` - Auto-detects format type and routes appropriately
- `format_sprintf()` - PHP sprintf formatting
- `format_template()` - Simple `{value}` placeholder replacement
- `format_regex()` - Pattern-based transformations

### Contact_Conditional
Evaluates conditional expressions:
- `evaluate()` - Supports isset, equals, greater_than, less_than, contains

### FluentCRM_Contact_Shortcode
Main shortcode handler:
- `register()` - Register shortcode with WordPress
- `handle_shortcode()` - Process shortcode with all features
- `get_contact_id()` - Smart contact lookup (current user or admin-specified)

## Security

- **Field Whitelisting**: Only safe standard fields are accessible by default
- **Custom Fields Isolated**: Queried from dedicated `_fc_subscriber_meta` table with `object_type` filtering
- **Prepared Statements**: All database queries use `$wpdb->prepare()`
- **Output Escaping**: All output escaped with `esc_html()`
- **Permission Checks**: Admin-only features (`user_id`, `debug`) verified with `current_user_can()`
- **Input Sanitization**: All shortcode attributes sanitized with `sanitize_text_field()`

## Troubleshooting

### Custom field not showing up?

1. Use debug mode to see available fields:
   ```
   [fluentcrm_contact field="test" debug="1"]
   ```

2. Check field name matches exactly (case-sensitive)

3. Verify field has been saved for the contact

### Formatting not working?

- **Template syntax** uses `{value}` placeholder (e.g., `format="Hello {value}!"`)
- **Sprintf** requires `%` in format string (e.g., `format="$%.2f"`)
- **Regex** requires `/pattern/|replacement` format (e.g., `format="/^(d{4})-(d{2})-(d{2})/|$2/$3/$1"`)
- **WordPress parsing**: WordPress may strip backslashes from patterns - write patterns with or without `\` (both work)
- **Debug mode**: Use `debug="1"` (admin only) to see what format string is being received and the before/after values

### Not seeing any output?

- User must be logged in (uses current user's email)
- Contact must exist in FluentCRM
- Use `not_found="message"` to display when contact not found

## Database Requirements

This plugin requires FluentCRM to be installed and activated. It queries:
- `wp_fc_subscribers` - Contact records
- `wp_fc_subscriber_meta` - Custom field values

## Requirements

- WordPress 5.0+
- PHP 7.2+
- FluentCRM plugin installed and activated

## License

GPLv2 or later. See LICENSE for details.

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request

## Support

For issues, questions, or feature requests, please open an issue on GitHub:
https://github.com/WeMakeGood/fluentcrm-shortcodes/issues

## Changelog

### 0.1.1
- Fixed regex formatting by restoring backslashes stripped by WordPress attribute parsing
- Fixed formatting being applied before conditionals so `{value}` in conditional blocks gets formatted values
- Added comprehensive debug output showing format string, values before/after formatting
- Improved regex pattern auto-detection to handle patterns without leading backslashes
- Enhanced documentation with regex formatting examples and troubleshooting tips

### 0.1.0
- Initial release
- Display FluentCRM contact fields and custom fields
- Support for sprintf, regex, and template formatting
- Conditional display with multiple comparison types
- Debug mode for troubleshooting
- Admin-only testing with user_id parameter
- Comprehensive security implementation

---

Made with ❤️ by [WeMakeGood](https://wemakegood.com)
