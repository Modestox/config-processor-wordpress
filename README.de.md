[English](README.md) | [Русский](README.ru.md) | **Deutsch**

# Modestox Config Processor für WordPress

Ein WordPress-Plugin, das Einstellungsseiten im Adminbereich aus einem einfachen PHP-Array erstellt.

Ihr Plugin hängt sich an einen Filter und übergibt eine Beschreibung seiner Einstellungen. Alles andere erledigt der Adapter:

- prüft die Beschreibung mit der Bibliothek [modestox/config-processor](https://github.com/Modestox/config-processor);
- legt den Menüeintrag und die Seite an;
- registriert jedes Feld in der WordPress Settings API (`register_setting`) mit Standardwert und Bereinigung;
- gibt das Formular aus, speichert die Werte, zeigt Fehler an;
- liefert typisierte Werte über `modestox_config()`.

`add_menu_page`, `register_setting`, `add_settings_field` sowie Ausgabe- und Bereinigungs-Callbacks für jedes Feld müssen nicht geschrieben werden.

Das Beschreibungsmodell ist dasselbe wie bei `system.xml` in Magento: **Tabs → Sektionen → Gruppen → Felder**.

---

## Inhalt

- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Schnellstart: Einstellungsseite eines Plugins](#schnellstart-einstellungsseite-eines-plugins)
- [Gemeinsame Seite Global Settings](#gemeinsame-seite-global-settings)
- [Werte lesen](#werte-lesen)
- [Feldtypen](#feldtypen)
- [Feldabhängigkeiten (depends)](#feldabhängigkeiten-depends)
- [Zeilentabelle (dynamic_rows)](#zeilentabelle-dynamic_rows)
- [Eigene Feldtypen](#eigene-feldtypen)
- [Felder in eigenen Formularen](#felder-in-eigenen-formularen)
- [Prüfung und Bereinigung der Werte](#prüfung-und-bereinigung-der-werte)
- [Konfigurationsfehler](#konfigurationsfehler)
- [Einschränkungen](#einschränkungen)
- [Projektstruktur](#projektstruktur)
- [Lizenz](#lizenz)

---

## Voraussetzungen

- PHP 8.3+
- WordPress 6.4+ (getestet mit 6.8)
- Composer

## Installation

```bash
cd wp-content/plugins
git clone https://github.com/Modestox/config-processor-wordpress modestox-config-processor-wp
cd modestox-config-processor-wp
composer install --no-dev --optimize-autoloader
```

Mit DDEV (aus dem Projektverzeichnis):

```bash
ddev exec -d /var/www/html/wp-content/plugins/modestox-config-processor-wp composer install --no-dev --optimize-autoloader
```

Danach das Plugin im Adminbereich aktivieren.

Fehlt der Ordner `vendor/`, stürzt das Plugin nicht ab, sondern zeigt dem Administrator einen Hinweis, dass Abhängigkeiten fehlen.

---

## Schnellstart: Einstellungsseite eines Plugins

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

Die Seite erscheint im Menü unter „Einstellungen → My Plugin“.

### Schlüssel des Seiteneintrags

| Schlüssel | Pflicht | Standard | Beschreibung |
|---|---|---|---|
| `plugin` | ja | — | Plugin-Kennung: nur `a-z`, `0-9`, `_` |
| `page_slug` | ja | — | Slug der Seite (`?page=`): `a-z`, `0-9`, `_`, `-` |
| `schema` | ja | — | Beschreibung: `['groups' => [...]]` |
| `parent_slug` | nein | `options-general.php` | Übergeordneter Menüeintrag |
| `menu_title` | nein | Wert von `plugin` | Titel im Menü |
| `page_title` | nein | Wert von `menu_title` | Überschrift der Seite |
| `capability` | nein | `manage_options` | Berechtigung zum Ansehen und Speichern |
| `option_prefix` | nein | Wert von `plugin` | Präfix der Optionsnamen in der Datenbank |

Ein unbekannter Schlüssel ist ein Fehler, bei Tippfehlern mit dem Hinweis „Did you mean ...“. Ein Plugin kann eine Seite registrieren.

### Schlüssel von Gruppe und Feld

| Ebene | Schlüssel |
|---|---|
| Gruppe | `label`, `sort_order`, `fields` |
| Feld (allgemein) | `type`, `label`, `sort_order`, `depends`, `comment`, `class`, `required`, `validation`, `provider` |

Die Schlüssel der einzelnen Typen stehen im Abschnitt [Feldtypen](#feldtypen).

Eigene Schlüssel können auf jeder Ebene hinzugefügt werden, wenn sie mit `x_` beginnen. Die Bibliothek gibt sie unverändert weiter.

---

## Gemeinsame Seite Global Settings

Mehrere Plugins können Einstellungen auf eine gemeinsame Seite „Global Settings“ im Hauptmenü bringen. Hier wird die vollständige Struktur mit Tabs und Sektionen verwendet:

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

Verwenden Sie `require __DIR__ . '/...'`, nicht `require 'config/...'`: Ein relativer Pfad hängt vom aktuellen Ordner von PHP ab.

| Ebene | Schlüssel |
|---|---|
| Tab | `label`, `sort_order`, `class`, `icon` |
| Sektion | `tab` (Pflicht), `label`, `sort_order`, `class`, `icon`, `groups` |

Das Icon kann als `cart` oder `dashicons-cart` angegeben werden, beide Varianten funktionieren.

Jede Sektion wird einzeln gespeichert: Die Schaltfläche „Speichern“ speichert nur die geöffnete Sektion.

### Wem was gehört

Damit sich Plugins nicht gegenseitig überschreiben, gelten auf der gemeinsamen Seite folgende Regeln:

- **Ein Tab wird nur von einem Plugin deklariert.** Deklariert ein anderes Plugin einen Tab mit derselben ID, wird seine Registrierung abgelehnt.
- **Die Metadaten einer Sektion und einer Gruppe** (`tab`, `label`, `icon`, ...) setzt nur ihr Besitzer. Andere Plugins dürfen einer fremden Sektion eigene Gruppen und einer fremden Gruppe eigene Felder hinzufügen:

  ```php
  // Ein anderes Plugin fügt der Sektion 'checkout' eine Gruppe hinzu, ohne ihr label und ihren tab anzufassen
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

- **Ein Feld mit demselben Pfad** wird nur von einem Plugin deklariert.
- **Abgelehnt wird das ganze Plugin**, die anderen werden ohne es registriert.

Besitzer wird das Plugin, dessen Filter zuerst ausgeführt wurde, und das hängt von der Ladereihenfolge der Plugins ab. Um eine fremde Sektion sicher zu erweitern, hängen Sie den Filter mit Priorität 20 ein.

---

## Werte lesen

```php
// Plugin-Seite: 'gruppe/feld'
$name = modestox_config('my_plugin', 'general/site_name');

// gemeinsame Seite: 'sektion/gruppe/feld'
$logging = modestox_config('my_plugin', 'checkout/payment/enable_logging');
```

`modestox_config()` funktioniert im Adminbereich und im Frontend, ab dem Hook `init`. Die Funktion liefert:

- den gespeicherten Wert, umgewandelt in den Feldtyp (`int`, `bool`, `array`, ...);
- den Standardwert aus dem Schema, wenn nichts gespeichert ist oder der gespeicherte Wert nicht mehr passt;
- `null`, wenn das Feld nicht gefunden wird oder der Adapter nicht aktiv ist.

Wenn der Adapter deaktiviert sein kann, prüfen Sie, ob die Funktion existiert: `function_exists('modestox_config')`.

### Optionsnamen

Jedes Feld wird als eigene WordPress-Option gespeichert:

| Seite | Optionsname |
|---|---|
| Plugin-Seite | `{option_prefix}_{gruppe}_{feld}`, z. B. `my_plugin_general_site_name` |
| Global Settings | `mtx_{sektion}_{gruppe}_{feld}`, z. B. `mtx_checkout_payment_enable_logging` |

Ein normales `get_option()` funktioniert ebenfalls und kennt auch den Standardwert, liefert aber das, was in der Datenbank steht: Zahlen werden dort als Strings gespeichert.

Das Präfix `mtx` ist für die gemeinsame Seite reserviert. Die Länge eines Optionsnamens ist auf 191 Zeichen begrenzt. Gleiche Namen verschiedener Felder sind ein Fehler.

---

## Feldtypen

| Typ | Wert | Eigene Schlüssel |
|---|---|---|
| `text` | `string` (eine Zeile, an den Rändern gekürzt) | `default`, `placeholder` |
| `textarea` | `string` (Zeilenumbrüche bleiben erhalten) | `default`, `placeholder` |
| `password` | `string` | `default` |
| `number` | `int` oder `float` | `default`, `min`, `max` |
| `boolean` | `bool` (Checkbox) | `default` (`true`/`false`) |
| `yes_no` | `int` `1`/`0` (Select Ja/Nein) | `default` (`1`/`0`) |
| `select` | Optionsschlüssel (`int` oder `string`) | `options`, `default` |
| `radio` | Optionsschlüssel (`int` oder `string`) | `options`, `default` |
| `multiselect` | Liste von Schlüsseln | `options`, `default` (Array) |
| `checkbox` | Liste von Schlüsseln (Checkbox-Gruppe) | `options`, `default` (Array) |
| `datetime` | `string` oder `''` | `view_mode`, `default` |
| `dynamic_rows` | Liste von Zeilen `[Spalte => String]` | `columns`, `default`, `x_locked_column` |
| `file` | URL der Datei oder `''` | `extensions`, `upload_dir` |
| `image` | URL des Bildes oder `''` | `extensions`, `upload_dir` |
| `infoblock` | wird nicht gespeichert | `text`, `format` |

Details zu den Typen:

- **number**: Dezimalzahlen sind nur erlaubt, wenn `default`, `min` oder `max` einen `float` enthält (z. B. `0.5`). Sonst werden nur ganze Zahlen akzeptiert.
- **password**: Das gespeicherte Passwort wird nie auf der Seite ausgegeben. Ein leeres Feld behält beim Speichern das bisherige Passwort.
- **datetime**: `view_mode` ist `datetime` (Format `Y-m-d H:i:s`), `date` (`Y-m-d`) oder `time` (`H:i:s`).
- **file / image**: Die Datei wird aus der WordPress-Mediathek gewählt. `extensions` schränkt die Dateitypen ein (`'pdf,zip'`); `image` ohne `extensions` erlaubt die üblichen Bildformate. `upload_dir` wird akzeptiert, aber nicht verwendet.
- **infoblock**: Mit `format => 'html'` ist sicheres Markup erlaubt, sonst wird der Text unverändert ausgegeben, mit Zeilenumbrüchen.
- **select, radio, multiselect, checkbox**: Schlüssel von `options` können `int` oder `string` sein, der Wert wird mit demselben Typ gespeichert.

---

## Feldabhängigkeiten (depends)

Ein Feld wird nur angezeigt, wenn ein Elternfeld in **derselben Gruppe** den benötigten Wert hat:

```php
'use_api' => ['type' => 'boolean', 'label' => 'Use API'],
'api_key' => ['type' => 'text', 'label' => 'API key', 'depends' => ['use_api' => true]],
```

- Es können mehrere Bedingungen gesetzt werden, dann müssen alle erfüllt sein: `'depends' => ['use_api' => true, 'mode' => 'live']`.
- Ketten funktionieren: Ist das Elternfeld ausgeblendet, ist auch das abhängige Feld ausgeblendet.
- Der Wert wird mit dem Typ des Elternfelds angegeben:
    - `boolean`: `true`/`false`;
    - `yes_no`: `1`/`0`;
    - `select`, `radio`: Optionsschlüssel;
    - `number`: eine Zahl;
    - `multiselect`, `checkbox`: eine Option (die Bedingung ist erfüllt, wenn sie ausgewählt ist).
- Abhängigkeiten von einem Feld einer anderen Gruppe, von einem `infoblock`, von sich selbst sowie Zyklen sind nicht erlaubt. All das sind Schemafehler.

Die Sichtbarkeit wird sowohl auf dem Server (beim Laden der Seite) als auch im Browser (bei Änderung der Felder) berechnet. Das Attribut `required` eines ausgeblendeten Felds blockiert das Absenden des Formulars nicht.

---

## Zeilentabelle (dynamic_rows)

```php
'headers' => [
    'type'    => 'dynamic_rows',
    'label'   => 'HTTP headers',
    'columns' => ['name' => 'Header', 'value' => 'Value'],
    'default' => [['name' => 'X-Shop', 'value' => 'demo']],
],
```

Zeilen können hinzugefügt, gelöscht und mit den Schaltflächen ▲ ▼ verschoben werden. Vollständig leere Zeilen werden beim Speichern verworfen.

### Geschützte Standardzeilen

Wenn Logik von den Codes aus `default` abhängt, können sie vor dem Löschen geschützt werden:

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

- Zeilen aus `default` können nicht gelöscht oder verschoben werden, ihr `code` kann nicht geändert werden. Die übrigen Zellen (z. B. `title`) sind bearbeitbar.
- Eigene Zeilen fügt der Benutzer wie gewohnt hinzu, löscht und verschiebt sie. Eine eigene Zeile tauscht den Platz nur mit einer benachbarten eigenen Zeile, daher bleiben die geschützten Zeilen an ihren Plätzen.
- `code` ist in jeder Zeile Pflicht und muss eindeutig sein.
- **Der Schutz greift auch auf dem Server.** Wird eine geschützte Zeile unter Umgehung der Oberfläche entfernt oder umsortiert, wird sie beim Speichern (und beim Lesen über `modestox_config()`) an ihrem Platz in der Reihenfolge aus `default` wiederhergestellt.

---

## Eigene Feldtypen

Ein eigener Typ besteht aus einem Validator (für die Bibliothek) und einem Renderer (für WordPress):

```php
add_filter('modestox_register_field_types', function (array $types): array {
    // Die Klassen werden hier geladen, nicht am Anfang der Datei:
    // Ihr Plugin kann vor dem Adapter geladen werden.
    require_once __DIR__ . '/src/ColorValidator.php'; // implements FieldValidatorInterface
    require_once __DIR__ . '/src/ColorField.php';     // extends AbstractField

    $types['color'] = [
        'validator' => new ColorValidator(),
        'renderer'  => new ColorField(),
    ];

    return $types;
});
```

- `validator` implementiert `Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface`. Am einfachsten erbt er von `AbstractFieldValidator` aus der Bibliothek.
- `renderer` erbt von `Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\AbstractField` und implementiert `render()` und `normalize()`.
- Einem eingebauten Typ kann nur ein `renderer` übergeben werden, um sein Markup zu ersetzen.

---

## Felder in eigenen Formularen

Die Felder des Adapters lassen sich auch außerhalb der Einstellungsseiten verwenden: in eigenen CRUD-Bildschirmen, Metaboxen, eigenen Seiten. Das Plugin übergibt eine Feldbeschreibung und erhält ein fertiges Feld mit korrektem `name` und `id`, geladenen Styles und Skripten, umgewandeltem Wert und Prüfung beim Speichern.

```php
$ui = modestox_fields(); // null, wenn der Adapter nicht aktiv ist
if ($ui === null) {
    return;
}

$status = $ui->field('select', 'note[status]', [
    'label'    => 'Status',
    'required' => true,
    'options'  => ['open' => 'Open', 'done' => 'Done'],
]);

// Ausgabe
echo '<table class="form-table" role="presentation"><tbody>';
$status->renderRow($note['status'] ?? null);   // <tr>: Label, Sternchen, Feld
echo '</tbody></table>';

// Speichern
try {
    $note['status'] = $status->sanitize(wp_unslash($_POST['note']['status'] ?? null), $note['status'] ?? null);
} catch (\Modestox\ConfigProcessorWp\Exception\InvalidFieldValueException $e) {
    $errors[] = $status->label() . ': ' . $e->getMessage();
}
```

Die Feldbeschreibung wird genauso streng geprüft wie in den Einstellungen, mit denselben Schlüsseln. Nur `depends` ist hier nicht verfügbar: Ein einzelnes Feld hat keine Gruppe.

**`FormFields`** — das Ergebnis von `modestox_fields()`:

| Methode | Beschreibung |
|---|---|
| `field($type, $name, $definition)` | Prüft die Beschreibung und liefert ein `FieldView`. Wirft bei einem Fehler `InvalidConfigException` |
| `render($type, $name, $definition, $value)` | Kurzform: gibt das Feld aus und zeigt bei einem Fehler dem Administrator eine Meldung. Liefert `bool` |
| `enqueueAssets()` | Lädt Styles und Skripte vorab (aus `admin_enqueue_scripts`). Optional |

**`FieldView`** — ein Feld:

| Methode | Beschreibung |
|---|---|
| `name()`, `id()` | HTML-`name` (wie übergeben) und `id` für `<label for>`: `note[status]` → `mtx-field-note_status_` |
| `label()`, `type()`, `isRequired()`, `hasLabelTarget()` | Felddaten |
| `render($value)`, `html($value)` | Feld ausgeben oder sein HTML als String erhalten |
| `renderRow($value)` | Eine `<tr>`-Zeile mit Label und Feld ausgeben |
| `cast($stored)` | Wert in den Feldtyp umwandeln (`null` und ein unpassender Wert werden zum Standardwert) |
| `sanitize($input, $previous)` | Gesendeten Wert bereinigen. Wirft bei einem Fehler `InvalidFieldValueException` mit einer Meldung für den Benutzer |
| `defaultValue()`, `definition()` | Standardwert und vollständige normalisierte Beschreibung |

Rufen Sie `modestox_fields()` nach dem Hook `init` auf.

---

## Prüfung und Bereinigung der Werte

Beim Speichern durchläuft jedes Feld die Bereinigung seines Typs:

- **Zahl** wird auf Typ und den Bereich `min`/`max` geprüft;
- **Auswahl** (`select`, `radio`, `multiselect`, `checkbox`) akzeptiert nur Schlüssel aus `options`;
- **Datum** wird auf das Format geprüft;
- **Datei** wird auf eine `http(s)`-URL und eine erlaubte Endung geprüft;
- **dynamic_rows** akzeptiert nur die deklarierten Spalten;
- **Text** wird an den Rändern gekürzt, bei `text` werden Zeilenumbrüche entfernt.

Ein ungültiger Wert wird nicht gespeichert: Der bisherige bleibt, und auf der Seite erscheint eine Meldung, z. B. „Timeout: The value cannot be greater than 120“.

HTML und Skripte in `text`, `textarea` und Zellen von `dynamic_rows` werden nur bei Benutzern mit der Berechtigung `unfiltered_html` unverändert gespeichert. Bei allen anderen wird nur sicheres Markup durchgelassen (`wp_kses_post`).

---

## Konfigurationsfehler

Ein Fehler in der Beschreibung macht die Website nicht kaputt und hält den Adminbereich nicht an. Das Plugin mit dem Fehler wird übersprungen, die anderen funktionieren, und der Administrator sieht einen Hinweis mit Plugin und Fehlerstelle:

```
Plugin 'my_plugin' (settings page): groups.general.fields.timeout:
Field 'default' for number field 'timeout' must be a strict integer or float.
```

```
Plugin 'my_addon' (global settings): Tab 'shop' is already declared by plugin 'my_plugin'.
```

Die Bibliothek bricht beim ersten Fehler ab, daher kann nach der Behebung eines Fehlers der nächste erscheinen.

---

## Einschränkungen

- `required` wird nur vom Browser geprüft, der Server prüft es nicht.
- Die Schlüssel `validation` und `provider` werden von der Bibliothek akzeptiert, vom Adapter aber noch nicht angewendet.
- Im Feld `datetime` zeigt der Browser keine Sekunden, daher werden sie beim erneuten Speichern auf null gesetzt.
- Der Besitzer eines Tabs oder einer Sektion auf der gemeinsamen Seite wird durch die Ladereihenfolge der Plugins bestimmt.

---

## Projektstruktur

```text
modestox-config-processor-wp.php   Einstiegspunkt des Plugins
src/
├── Plugin.php                     Anbindung der Hooks
├── functions.php                  modestox_config(), modestox_fields()
├── Registry/                      Sammeln, Prüfen und Speichern von Seiten und Optionen
├── Builder/ConfigMerger.php       Zusammenführen der gemeinsamen Seite, Besitzregeln
├── Field/FieldTypeRegistry.php    Feldtypen, einschließlich eigener
├── Api/                           FormFields, FieldView — Felder für eigene Formulare
├── Admin/                         Menü, Settings API, Assets, Hinweise
│   └── Ui/                        Ausgabe von Seiten, Gruppen und Feldern
│       └── Common/Field/          eine Klasse pro Feldtyp
└── Exception/
assets/
├── css/admin-settings.css
└── js/
    ├── admin-fields.js            dynamic_rows, file, image
    └── admin-depends.js           Feldabhängigkeiten
```

---

## Lizenz

MIT