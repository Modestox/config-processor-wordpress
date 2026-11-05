**English** | [Русский](CHANGELOG.ru.md)

# Changelog

All notable changes to this project are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow [Semantic Versioning](https://semver.org/).

## [1.0.0] - Unreleased

First public version.

### Added

- Settings pages from plain arrays through two filters:
    - `modestox_register_admin_plugin_config` — a settings page of a plugin (groups → fields);
    - `modestox_register_global_config` — the shared "Global Settings" page (tabs → sections → groups → fields).
- Validation of every schema with `modestox/config-processor`, once per request.
- Registration of every field in the Settings API with its default value and sanitizing of its type.
- 15 field types: `text`, `textarea`, `password`, `number`, `boolean`, `yes_no`, `select`, `radio`, `multiselect`, `checkbox`, `datetime`, `dynamic_rows`, `file`, `image`, `infoblock`.
- Field dependencies (`depends`) on the server and in the browser, including chains and every parent type.
- `modestox_config()` — typed values with defaults, in the admin and on the front end.
- Ownership rules of the shared page: tabs, section and group metadata and field paths belong to one plugin; other plugins may add groups and fields.
- Admin notices for configuration errors: a broken plugin is skipped, the others keep working.
- Strict registration entries: unknown keys with a "Did you mean" hint, identifier checks, the reserved `mtx` prefix, option name collisions and length.
- Custom field types through the `modestox_register_field_types` filter.
- `dynamic_rows`: moving rows up and down; protected default rows with `x_locked_column`.
- Public API for other plugins' own forms: `modestox_fields()`, `FormFields`, `FieldView`.
- Field styles and scripts under stable handles (`modestox-admin-settings`, `modestox-admin-fields`, `modestox-admin-depends`).

### Security

- A saved password is never printed into the page.
- Markup in text fields is kept as is only for users with `unfiltered_html`, otherwise it is filtered with `wp_kses_post`.
- Saving a page requires the capability of that page.