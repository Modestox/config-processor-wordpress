**English** | [Русский](README.ru.md) | [Deutsch](README.de.md)

# Modestox Config Processor for WordPress

A WordPress plugin that builds admin settings pages from a plain PHP array.

Your plugin hooks one filter and returns a description of its settings. The adapter does everything else:

- validates the description with the [modestox/config-processor](https://github.com/Modestox/config-processor) library;
- creates the menu entry and the page;
- registers every field in the WordPress Settings API (`register_setting`) with a default value and sanitizing;
- renders the form, saves the values, shows errors;
- returns typed values through `modestox_config()`.

There is no need to write `add_menu_page`, `register_setting`, `add_settings_field` and render and sanitize callbacks for every field.

The description model is the same as `system.xml` in Magento: **tabs → sections → groups → fields**.

---

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start: plugin settings page](#quick-start-plugin-settings-page)
- [Shared Global Settings page](#shared-global-settings-page)
- [Reading values](#reading-values)
- [Field types](#field-types)
- [Field dependencies (depends)](#field-dependencies-depends)
- [Row table (dynamic_rows)](#row-table-dynamic_rows)
- [Custom field types](#custom-field-types)
- [Fields in your own forms](#fields-in-your-own-forms)
- [Value validation and sanitizing](#value-validation-and-sanitizing)
- [Configuration errors](#configuration-errors)
- [Limitations](#limitations)
- [Project structure](#project-structure)
- [License](#license)

---

## Requirements

- PHP 8.3+
- WordPress 6.4+ (tested on 6.8)
- Composer

## Installation

```bash
cd wp-content/plugins
git clone https://github.com/Modestox/config-processor-wordpress modestox-config-processor-wp
cd modestox-config-processor-wp
composer install --no-dev --optimize-autoloader
```

With DDEV (from the project root):

```bash
ddev exec -d /var/www/html/wp-content/plugins/modestox-config-processor-wp composer install --no-dev --optimize-autoloader
```

Then activate the plugin in the admin.

If the `vendor/` folder is missing, the plugin does not crash; it shows the administrator a notice that dependencies are missing.

---

## Quick start: plugin settings page

```php
add_filter('modestox_register_admin_plugin_config', function (array $pages): array {
    $pages[] = [
        'plugin'     => 'my_plugin',
        'page_slug'  => 'my_plugin_settings',
        'menu_title' => 'My Plugin',
        'schema'     => [
            'groups' => [
                'general' => [
                    'label'  => 'General',
                    'fields' => [
                        'site_name' => [
                            'type'        => 'text',
                            'label'       => 'Site name',
                            'default'     => 'My Shop',
                            'placeholder' => 'Enter site name',
                        ],
                        'enable_logging' => [
                            'type'    => 'yes_no',
                            'label'   => 'Logging',
                            'default' => 1,
                        ],
                        'custom_scripts' => [
                            'type'    => 'textarea',
                            'label'   => 'Custom scripts',
                            'depends' => ['enable_logging' => 1],
                        ],
                    ],
                ],
            ],
        ],
    ];

    return $pages;
});
```

The page appears in the menu under "Settings → My Plugin".

### Page entry keys

| Key | Required | Default | Description |
|---|---|---|---|
| `plugin` | yes | — | Plugin identifier: only `a-z`, `0-9`, `_` |
| `page_slug` | yes | — | Page slug (`?page=`): `a-z`, `0-9`, `_`, `-` |
| `schema` | yes | — | Description: `['groups' => [...]]` |
| `parent_slug` | no | `options-general.php` | Parent menu entry |
| `menu_title` | no | value of `plugin` | Menu title |
| `page_title` | no | value of `menu_title` | Page heading |
| `capability` | no | `manage_options` | Capability to view and save |
| `option_prefix` | no | value of `plugin` | Prefix of the option names in the database |

An unknown key is an error, with a "Did you mean ..." hint for typos. A plugin can register one page.

### Group and field keys

| Level | Keys |
|---|---|
| Group | `label`, `sort_order`, `fields` |
| Field (common) | `type`, `label`, `sort_order`, `depends`, `comment`, `class`, `required`, `validation`, `provider` |

The keys of specific types are listed in the [Field types](#field-types) section.

Custom keys can be added at any level if they start with `x_`. The library passes them through unchanged.

---

## Shared Global Settings page

Several plugins can add settings to one shared "Global Settings" page in the main menu. It uses the full structure with tabs and sections:

```php
add_filter('modestox_register_global_config', function (array $configs): array {
    $configs[] = [
        'plugin' => 'my_plugin',
        'schema' => require __DIR__ . '/config/global-settings.php',
    ];

    return $configs;
});
```

```php
// config/global-settings.php
return [
    'tabs' => [
        'shop' => ['label' => 'Shop', 'icon' => 'cart', 'sort_order' => 10],
    ],
    'sections' => [
        'checkout' => [
            'tab'    => 'shop',
            'label'  => 'Checkout',
            'icon'   => 'dashicons-money-alt',
            'groups' => [
                'payment' => [
                    'label'  => 'Payment',
                    'fields' => [
                        'enable_logging' => ['type' => 'yes_no', 'label' => 'Logging', 'default' => 1],
                    ],
                ],
            ],
        ],
    ],
];
```

Use `require __DIR__ . '/...'`, not `require 'config/...'`: a relative path depends on PHP's current folder.

| Level | Keys |
|---|---|
| Tab | `label`, `sort_order`, `class`, `icon` |
| Section | `tab` (required), `label`, `sort_order`, `class`, `icon`, `groups` |

The icon can be given as `cart` or `dashicons-cart`; both work.

Each section is saved separately: the "Save" button saves only the open section.

### Who owns what

To keep plugins from overwriting each other, the shared page follows these rules:

- **A tab is declared by one plugin only.** If another plugin declares a tab with the same id, its registration is rejected.
- **The metadata of a section and a group** (`tab`, `label`, `icon`, ...) is set by its owner only. Other plugins may add their groups to someone else's section and their fields to someone else's group:

  ```php
  // Another plugin adds a group to the 'checkout' section without touching its label and tab
  $configs[] = [
      'plugin' => 'my_addon',
      'schema' => [
          'sections' => [
              'checkout' => [
                  'groups' => [
                      'addon' => ['label' => 'Addon', 'fields' => [/* ... */]],
                  ],
              ],
          ],
      ],
  ];
  ```

- **A field with the same path** is declared by one plugin only.
- **The whole plugin is rejected**; the others are registered without it.

The owner is the plugin whose filter ran first, and that depends on the plugin load order. To reliably extend someone else's section, hook your filter with priority 20.

---

## Reading values

```php
// plugin page: 'group/field'
$name = modestox_config('my_plugin', 'general/site_name');

// shared page: 'section/group/field'
$logging = modestox_config('my_plugin', 'checkout/payment/enable_logging');
```

`modestox_config()` works both in the admin and on the front end, from the `init` hook on. The function returns:

- the saved value, cast to the field type (`int`, `bool`, `array`, ...);
- the default value from the schema, if nothing is saved or the saved value no longer fits;
- `null`, if the field is not found or the adapter is not active.

If the adapter may be deactivated, check that the function exists: `function_exists('modestox_config')`.

### Option names

Each field is stored as a separate WordPress option:

| Page | Option name |
|---|---|
| Plugin page | `{option_prefix}_{group}_{field}`, e.g. `my_plugin_general_site_name` |
| Global Settings | `mtx_{section}_{group}_{field}`, e.g. `mtx_checkout_payment_enable_logging` |

A plain `get_option()` works too and also knows the default value, but it returns what is stored in the database: numbers are stored there as strings.

The `mtx` prefix is reserved for the shared page. The option name length is limited to 191 characters. Matching names of different fields are an error.

---

## Field types

| Type | Value | Own keys |
|---|---|---|
| `text` | `string` (one line, trimmed) | `default`, `placeholder` |
| `textarea` | `string` (line breaks are kept) | `default`, `placeholder` |
| `password` | `string` | `default` |
| `number` | `int` or `float` | `default`, `min`, `max` |
| `boolean` | `bool` (checkbox) | `default` (`true`/`false`) |
| `yes_no` | `int` `1`/`0` (Yes/No select) | `default` (`1`/`0`) |
| `select` | option key (`int` or `string`) | `options`, `default` |
| `radio` | option key (`int` or `string`) | `options`, `default` |
| `multiselect` | list of keys | `options`, `default` (array) |
| `checkbox` | list of keys (checkbox group) | `options`, `default` (array) |
| `datetime` | `string` or `''` | `view_mode`, `default` |
| `dynamic_rows` | list of rows `[column => string]` | `columns`, `default`, `x_locked_column` |
| `file` | file URL or `''` | `extensions`, `upload_dir` |
| `image` | image URL or `''` | `extensions`, `upload_dir` |
| `infoblock` | not stored | `text`, `format` |

Details by type:

- **number**: fractions are allowed only if `default`, `min` or `max` contains a `float` (e.g. `0.5`). Otherwise only whole numbers are accepted.
- **password**: the saved password is never printed on the page. An empty field on save keeps the previous password.
- **datetime**: `view_mode` is `datetime` (format `Y-m-d H:i:s`), `date` (`Y-m-d`) or `time` (`H:i:s`).
- **file / image**: the file is picked from the WordPress Media Library. `extensions` restricts the file types (`'pdf,zip'`); `image` without `extensions` allows the usual image formats. `upload_dir` is accepted but not used.
- **infoblock**: with `format => 'html'` safe markup is allowed, otherwise the text is printed as is, with line breaks.
- **select, radio, multiselect, checkbox**: `options` keys can be `int` or `string`; the value is stored with the same type.

---

## Field dependencies (depends)

A field is shown only when a parent in **the same group** has the required value:

```php
'use_api' => ['type' => 'boolean', 'label' => 'Use API'],
'api_key' => ['type' => 'text', 'label' => 'API key', 'depends' => ['use_api' => true]],
```

- Several conditions can be set; then all of them must be met: `'depends' => ['use_api' => true, 'mode' => 'live']`.
- Chains work: if the parent is hidden, the dependent field is hidden too.
- The value is given with the parent's type:
    - `boolean`: `true`/`false`;
    - `yes_no`: `1`/`0`;
    - `select`, `radio`: option key;
    - `number`: a number;
    - `multiselect`, `checkbox`: one option (the condition is met if it is selected).
- Depending on a field of another group, on an `infoblock`, on itself, and creating cycles is not allowed. All of these are schema errors.

Visibility is computed both on the server (when the page loads) and in the browser (when fields change). The `required` attribute of a hidden field does not block the form submission.

---

## Row table (dynamic_rows)

```php
'headers' => [
    'type'    => 'dynamic_rows',
    'label'   => 'HTTP headers',
    'columns' => ['name' => 'Header', 'value' => 'Value'],
    'default' => [['name' => 'X-Shop', 'value' => 'demo']],
],
```

Rows can be added, removed and moved with the ▲ ▼ buttons. Completely empty rows are dropped on save.

### Protected default rows

If logic depends on the codes from `default`, they can be protected from removal:

```php
'priority' => [
    'type'            => 'dynamic_rows',
    'label'           => 'Priority',
    'columns'         => ['code' => 'Code', 'title' => 'Title'],
    'x_locked_column' => 'code',
    'default'         => [
        ['code' => 'low',    'title' => 'Low'],
        ['code' => 'normal', 'title' => 'Normal'],
        ['code' => 'high',   'title' => 'High'],
    ],
],
```

- Rows from `default` cannot be removed or moved, and their `code` cannot be changed. The other cells (e.g. `title`) are editable.
- The user adds, removes and moves their own rows as usual. An own row swaps places only with a neighbouring own row, so the protected rows stay in their places.
- `code` is required in every row and must be unique.
- **The protection also works on the server.** If a protected row is removed or reordered by bypassing the interface, it is put back in its place in the order from `default` on save (and on read through `modestox_config()`).

---

## Custom field types

A custom type consists of a validator (for the library) and a renderer (for WordPress):

```php
add_filter('modestox_register_field_types', function (array $types): array {
    // The classes are loaded here, not at the top of the file:
    // your plugin may load before the adapter.
    require_once __DIR__ . '/src/ColorValidator.php'; // implements FieldValidatorInterface
    require_once __DIR__ . '/src/ColorField.php';     // extends AbstractField

    $types['color'] = [
        'validator' => new ColorValidator(),
        'renderer'  => new ColorField(),
    ];

    return $types;
});
```

- `validator` implements `Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface`. The easiest way is to extend `AbstractFieldValidator` from the library.
- `renderer` extends `Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\AbstractField` and implements `render()` and `normalize()`.
- A built-in type can be given only a `renderer` to replace its markup.

---

## Fields in your own forms

The adapter's fields can also be used outside the settings pages: in your own CRUD screens, meta boxes, custom pages. The plugin passes a field description and gets a ready field with the correct `name` and `id`, loaded styles and scripts, a cast value and validation on save.

```php
$ui = modestox_fields(); // null if the adapter is not active
if ($ui === null) {
    return;
}

$status = $ui->field('select', 'note[status]', [
    'label'    => 'Status',
    'required' => true,
    'options'  => ['open' => 'Open', 'done' => 'Done'],
]);

// output
echo '<table class="form-table" role="presentation"><tbody>';
$status->renderRow($note['status'] ?? null);   // <tr>: label, asterisk, field
echo '</tbody></table>';

// saving
try {
    $note['status'] = $status->sanitize(wp_unslash($_POST['note']['status'] ?? null), $note['status'] ?? null);
} catch (\Modestox\ConfigProcessorWp\Exception\InvalidFieldValueException $e) {
    $errors[] = $status->label() . ': ' . $e->getMessage();
}
```

The field description is validated as strictly as in the settings, with the same keys. Only `depends` is not available here: a single field has no group.

**`FormFields`** — the result of `modestox_fields()`:

| Method | Description |
|---|---|
| `field($type, $name, $definition)` | Validates the description and returns a `FieldView`. Throws `InvalidConfigException` on error |
| `render($type, $name, $definition, $value)` | Short form: prints the field, and on error shows a message to the administrator. Returns `bool` |
| `enqueueAssets()` | Loads the styles and scripts in advance (from `admin_enqueue_scripts`). Optional |

**`FieldView`** — one field:

| Method | Description |
|---|---|
| `name()`, `id()` | HTML `name` (as passed) and `id` for `<label for>`: `note[status]` → `mtx-field-note_status_` |
| `label()`, `type()`, `isRequired()`, `hasLabelTarget()` | Field data |
| `render($value)`, `html($value)` | Print the field or get its HTML as a string |
| `renderRow($value)` | Print a `<tr>` row with the label and the field |
| `cast($stored)` | Cast a value to the field type (`null` and an unfitting value become the default) |
| `sanitize($input, $previous)` | Clean a submitted value. Throws `InvalidFieldValueException` with a message for the user on error |
| `defaultValue()`, `definition()` | Default value and the full normalized description |

Call `modestox_fields()` after the `init` hook.

---

## Value validation and sanitizing

On save, every field goes through the sanitizing of its type:

- **number** is checked for type and the `min`/`max` range;
- **choice** (`select`, `radio`, `multiselect`, `checkbox`) accepts only keys from `options`;
- **date** is checked for format;
- **file** is checked for an `http(s)` URL and an allowed extension;
- **dynamic_rows** accepts only the declared columns;
- **text** is trimmed; `text` also loses line breaks.

An invalid value is not saved: the previous one stays, and a message appears on the page, e.g. "Timeout: The value cannot be greater than 120".

HTML and scripts in `text`, `textarea` and `dynamic_rows` cells are saved as is only for users with the `unfiltered_html` capability. For everyone else only safe markup passes (`wp_kses_post`).

---

## Configuration errors

An error in the description does not break the site or stop the admin. The plugin with the error is skipped, the others work, and the administrator sees a notice naming the plugin and the place of the error:

```
Plugin 'my_plugin' (settings page): groups.general.fields.timeout:
Field 'default' for number field 'timeout' must be a strict integer or float.
```

```
Plugin 'my_addon' (global settings): Tab 'shop' is already declared by plugin 'my_plugin'.
```

The library stops at the first error, so after fixing one error the next one may appear.

---

## Limitations

- `required` is checked only by the browser; the server does not check it.
- The `validation` and `provider` keys are accepted by the library, but the adapter does not apply them yet.
- In a `datetime` field the browser does not show seconds, so they are reset to zero when the value is saved again.
- The owner of a tab or section on the shared page is determined by the plugin load order.

---

## Project structure

```text
modestox-config-processor-wp.php   plugin entry point
src/
├── Plugin.php                     hook wiring
├── functions.php                  modestox_config(), modestox_fields()
├── Registry/                      collecting, validating and storing pages and options
├── Builder/ConfigMerger.php       merging the shared page, ownership rules
├── Field/FieldTypeRegistry.php    field types, including custom ones
├── Api/                           FormFields, FieldView — fields for your own forms
├── Admin/                         menu, Settings API, assets, notices
│   └── Ui/                        rendering of pages, groups and fields
│       └── Common/Field/          one class per field type
└── Exception/
assets/
├── css/admin-settings.css
└── js/
    ├── admin-fields.js            dynamic_rows, file, image
    └── admin-depends.js           field dependencies
```

---

## License

MIT