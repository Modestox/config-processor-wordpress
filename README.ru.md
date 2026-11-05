[English](README.md) | **Русский** | [Deutsch](README.de.md)

# Modestox Config Processor для WordPress

WordPress-плагин, который строит страницы настроек в админке из обычного PHP-массива.

Ваш плагин вешает один фильтр и отдаёт описание настроек. Всё остальное адаптер делает сам:

- проверяет описание через библиотеку [modestox/config-processor](https://github.com/Modestox/config-processor);
- создаёт пункт меню и страницу;
- регистрирует каждое поле в WordPress Settings API (`register_setting`) со значением по умолчанию и очисткой;
- выводит форму, сохраняет значения, показывает ошибки;
- отдаёт значения нужного типа через `modestox_config()`.

Писать `add_menu_page`, `register_setting`, `add_settings_field` и колбэки вывода и очистки для каждого поля не нужно.

Модель описания та же, что у `system.xml` в Magento: **вкладки → секции → группы → поля**.

---

## Содержание

- [Требования](#требования)
- [Установка](#установка)
- [Быстрый старт: страница настроек плагина](#быстрый-старт-страница-настроек-плагина)
- [Общая страница Global Settings](#общая-страница-global-settings)
- [Чтение значений](#чтение-значений)
- [Типы полей](#типы-полей)
- [Зависимости полей (depends)](#зависимости-полей-depends)
- [Таблица строк (dynamic_rows)](#таблица-строк-dynamic_rows)
- [Свои типы полей](#свои-типы-полей)
- [Поля в своих формах](#поля-в-своих-формах)
- [Проверка и очистка значений](#проверка-и-очистка-значений)
- [Ошибки конфигурации](#ошибки-конфигурации)
- [Ограничения](#ограничения)
- [Структура проекта](#структура-проекта)
- [Лицензия](#лицензия)

---

## Требования

- PHP 8.3+
- WordPress 6.4+ (проверено на 6.8)
- Composer

## Установка

```bash
cd wp-content/plugins
git clone https://github.com/Modestox/config-processor-wordpress modestox-config-processor-wp
cd modestox-config-processor-wp
composer install --no-dev --optimize-autoloader
```

С DDEV (из корня проекта):

```bash
ddev exec -d /var/www/html/wp-content/plugins/modestox-config-processor-wp composer install --no-dev --optimize-autoloader
```

После этого активируйте плагин в админке.

Если папки `vendor/` нет, плагин не падает, а показывает администратору уведомление о том, что не хватает зависимостей.

---

## Быстрый старт: страница настроек плагина

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

Страница появится в меню «Настройки → My Plugin».

### Ключи записи страницы

| Ключ | Обязательный | По умолчанию | Описание |
|---|---|---|---|
| `plugin` | да | — | Идентификатор плагина: только `a-z`, `0-9`, `_` |
| `page_slug` | да | — | Slug страницы (`?page=`): `a-z`, `0-9`, `_`, `-` |
| `schema` | да | — | Описание: `['groups' => [...]]` |
| `parent_slug` | нет | `options-general.php` | Родительский пункт меню |
| `menu_title` | нет | значение `plugin` | Название в меню |
| `page_title` | нет | значение `menu_title` | Заголовок страницы |
| `capability` | нет | `manage_options` | Право на просмотр и сохранение |
| `option_prefix` | нет | значение `plugin` | Префикс имён опций в базе |

Неизвестный ключ — это ошибка, с подсказкой «Did you mean ...» при опечатке. Один плагин может зарегистрировать одну страницу.

### Ключи группы и поля

| Уровень | Ключи |
|---|---|
| Группа | `label`, `sort_order`, `fields` |
| Поле (общие) | `type`, `label`, `sort_order`, `depends`, `comment`, `class`, `required`, `validation`, `provider` |

Ключи конкретных типов перечислены в разделе [Типы полей](#типы-полей).

Свои ключи можно добавлять на любом уровне, если они начинаются с `x_`. Библиотека пропускает их без изменений.

---

## Общая страница Global Settings

Несколько плагинов могут добавлять настройки на одну общую страницу «Global Settings» в главном меню. Здесь используется полная структура со вкладками и секциями:

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

Используйте `require __DIR__ . '/...'`, а не `require 'config/...'`: относительный путь зависит от текущей папки PHP.

| Уровень | Ключи |
|---|---|
| Вкладка | `label`, `sort_order`, `class`, `icon` |
| Секция | `tab` (обязательно), `label`, `sort_order`, `class`, `icon`, `groups` |

Иконка указывается как `cart` или `dashicons-cart`, оба варианта работают.

Каждая секция сохраняется отдельно: кнопка «Сохранить» сохраняет только открытую секцию.

### Кто чем владеет

Чтобы плагины не перезаписывали друг друга, на общей странице действуют правила:

- **Вкладку объявляет только один плагин.** Если другой плагин объявит вкладку с тем же id, его регистрация будет отклонена.
- **Метаданные секции и группы** (`tab`, `label`, `icon`, ...) задаёт только их владелец. Другие плагины могут добавлять в чужую секцию свои группы, а в чужую группу — свои поля:

  ```php
  // Другой плагин добавляет группу в секцию 'checkout', не трогая её label и tab
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

- **Поле с одним и тем же путём** объявляет только один плагин.
- **Отклоняется весь плагин целиком**, остальные регистрируются без него.

Владельцем становится тот, чей фильтр сработал первым, а это зависит от порядка загрузки плагинов. Чтобы гарантированно расширять чужую секцию, вешайте фильтр с приоритетом 20.

---

## Чтение значений

```php
// страница плагина: 'группа/поле'
$name = modestox_config('my_plugin', 'general/site_name');

// общая страница: 'секция/группа/поле'
$logging = modestox_config('my_plugin', 'checkout/payment/enable_logging');
```

`modestox_config()` работает и в админке, и на фронте, начиная с хука `init`. Функция возвращает:

- сохранённое значение, приведённое к типу поля (`int`, `bool`, `array`, ...);
- значение по умолчанию из схемы, если ничего не сохранено или сохранённое больше не подходит;
- `null`, если поле не найдено или адаптер не активен.

Если адаптер может быть выключен, проверяйте наличие функции: `function_exists('modestox_config')`.

### Имена опций

Каждое поле хранится как отдельная опция WordPress:

| Страница | Имя опции |
|---|---|
| Страница плагина | `{option_prefix}_{группа}_{поле}`, например `my_plugin_general_site_name` |
| Global Settings | `mtx_{секция}_{группа}_{поле}`, например `mtx_checkout_payment_enable_logging` |

Обычный `get_option()` тоже работает и тоже знает значение по умолчанию, но возвращает то, что лежит в базе: числа там хранятся строками.

Префикс `mtx` зарезервирован для общей страницы. Длина имени опции ограничена 191 символом. Совпадение имён у разных полей считается ошибкой.

---

## Типы полей

| Тип | Значение | Свои ключи |
|---|---|---|
| `text` | `string` (одна строка, обрезан по краям) | `default`, `placeholder` |
| `textarea` | `string` (переносы строк сохраняются) | `default`, `placeholder` |
| `password` | `string` | `default` |
| `number` | `int` или `float` | `default`, `min`, `max` |
| `boolean` | `bool` (чекбокс) | `default` (`true`/`false`) |
| `yes_no` | `int` `1`/`0` (select Да/Нет) | `default` (`1`/`0`) |
| `select` | ключ опции (`int` или `string`) | `options`, `default` |
| `radio` | ключ опции (`int` или `string`) | `options`, `default` |
| `multiselect` | список ключей | `options`, `default` (массив) |
| `checkbox` | список ключей (группа чекбоксов) | `options`, `default` (массив) |
| `datetime` | `string` или `''` | `view_mode`, `default` |
| `dynamic_rows` | список строк `[колонка => строка]` | `columns`, `default`, `x_locked_column` |
| `file` | URL файла или `''` | `extensions`, `upload_dir` |
| `image` | URL картинки или `''` | `extensions`, `upload_dir` |
| `infoblock` | не хранится | `text`, `format` |

Подробности по типам:

- **number**: дробные значения разрешены, только если в `default`, `min` или `max` есть `float` (например `0.5`). Иначе принимаются только целые.
- **password**: сохранённый пароль не выводится на страницу. Пустое поле при сохранении оставляет прежний пароль.
- **datetime**: `view_mode` — это `datetime` (формат `Y-m-d H:i:s`), `date` (`Y-m-d`) или `time` (`H:i:s`).
- **file / image**: файл выбирается из медиатеки WordPress. `extensions` ограничивает типы файлов (`'pdf,zip'`); у `image` без `extensions` разрешены обычные форматы картинок. `upload_dir` принимается, но не используется.
- **infoblock**: при `format => 'html'` разрешена безопасная разметка, иначе текст выводится как есть, с переносами строк.
- **select, radio, multiselect, checkbox**: ключи `options` могут быть `int` или `string`, значение хранится с тем же типом.

---

## Зависимости полей (depends)

Поле показывается только тогда, когда родитель в **той же группе** имеет нужное значение:

```php
'use_api' => ['type' => 'boolean', 'label' => 'Use API'],
'api_key' => ['type' => 'text', 'label' => 'API key', 'depends' => ['use_api' => true]],
```

- Можно задать несколько условий, тогда должны выполниться все: `'depends' => ['use_api' => true, 'mode' => 'live']`.
- Работают цепочки: если родитель скрыт, скрыт и зависимый.
- Значение указывается с типом родителя:
    - `boolean`: `true`/`false`;
    - `yes_no`: `1`/`0`;
    - `select`, `radio`: ключ опции;
    - `number`: число;
    - `multiselect`, `checkbox`: одна опция (условие выполнено, если она выбрана).
- Нельзя зависеть от поля другой группы, от `infoblock`, от самого себя, и нельзя создавать циклы. Всё это ошибки схемы.

Видимость считается и на сервере (при загрузке страницы), и в браузере (при изменении полей). Атрибут `required` у скрытого поля не мешает отправке формы.

---

## Таблица строк (dynamic_rows)

```php
'headers' => [
    'type'    => 'dynamic_rows',
    'label'   => 'HTTP headers',
    'columns' => ['name' => 'Header', 'value' => 'Value'],
    'default' => [['name' => 'X-Shop', 'value' => 'demo']],
],
```

Строки можно добавлять, удалять и перемещать кнопками ▲ ▼. Полностью пустые строки при сохранении отбрасываются.

### Защищённые строки по умолчанию

Если на коды из `default` завязана логика, их можно защитить от удаления:

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

- Строки из `default` нельзя удалить и переместить, их `code` нельзя изменить. Остальные ячейки (например `title`) редактируются.
- Свои строки пользователь добавляет, удаляет и перемещает как обычно. Своя строка меняется местами только с соседней своей строкой, поэтому защищённые строки остаются на своих местах.
- В каждой строке `code` обязателен и должен быть уникальным.
- **Защита работает и на сервере.** Если защищённую строку убрать или переставить в обход интерфейса, при сохранении (и при чтении через `modestox_config()`) она вернётся на своё место в порядке из `default`.

---

## Свои типы полей

Свой тип состоит из валидатора (для библиотеки) и рендерера (для WordPress):

```php
add_filter('modestox_register_field_types', function (array $types): array {
    // Классы подключаются здесь, а не в начале файла:
    // ваш плагин может загрузиться раньше адаптера.
    require_once __DIR__ . '/src/ColorValidator.php'; // implements FieldValidatorInterface
    require_once __DIR__ . '/src/ColorField.php';     // extends AbstractField

    $types['color'] = [
        'validator' => new ColorValidator(),
        'renderer'  => new ColorField(),
    ];

    return $types;
});
```

- `validator` реализует `Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface`. Проще всего унаследовать `AbstractFieldValidator` из библиотеки.
- `renderer` наследует `Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\AbstractField` и реализует `render()` и `normalize()`.
- Встроенному типу можно передать только `renderer`, чтобы заменить его разметку.

---

## Поля в своих формах

Поля адаптера можно использовать и вне страниц настроек: в своих CRUD-экранах, метабоксах, своих страницах. Плагин передаёт описание поля и получает готовое поле с правильными `name` и `id`, подключёнными стилями и скриптами, приведённым значением и проверкой при сохранении.

```php
$ui = modestox_fields(); // null, если адаптер не активен
if ($ui === null) {
    return;
}

$status = $ui->field('select', 'note[status]', [
    'label'    => 'Status',
    'required' => true,
    'options'  => ['open' => 'Open', 'done' => 'Done'],
]);

// вывод
echo '<table class="form-table" role="presentation"><tbody>';
$status->renderRow($note['status'] ?? null);   // <tr>: label, звёздочка, поле
echo '</tbody></table>';

// сохранение
try {
    $note['status'] = $status->sanitize(wp_unslash($_POST['note']['status'] ?? null), $note['status'] ?? null);
} catch (\Modestox\ConfigProcessorWp\Exception\InvalidFieldValueException $e) {
    $errors[] = $status->label() . ': ' . $e->getMessage();
}
```

Описание поля проверяется так же строго, как в настройках, с теми же ключами. Только `depends` здесь недоступен: у одиночного поля нет группы.

**`FormFields`** — результат `modestox_fields()`:

| Метод | Описание |
|---|---|
| `field($type, $name, $definition)` | Проверяет описание и возвращает `FieldView`. При ошибке выбрасывает `InvalidConfigException` |
| `render($type, $name, $definition, $value)` | Короткий вариант: выводит поле, а при ошибке показывает сообщение администратору. Возвращает `bool` |
| `enqueueAssets()` | Подключает стили и скрипты заранее (из `admin_enqueue_scripts`). Необязательно |

**`FieldView`** — одно поле:

| Метод | Описание |
|---|---|
| `name()`, `id()` | HTML `name` (как передали) и `id` для `<label for>`: `note[status]` → `mtx-field-note_status_` |
| `label()`, `type()`, `isRequired()`, `hasLabelTarget()` | Данные поля |
| `render($value)`, `html($value)` | Вывести поле или получить его HTML-строкой |
| `renderRow($value)` | Вывести строку `<tr>` с label и полем |
| `cast($stored)` | Привести значение к типу поля (`null` и неподходящее значение превращаются в default) |
| `sanitize($input, $previous)` | Очистить отправленное значение. При ошибке выбрасывает `InvalidFieldValueException` с текстом для пользователя |
| `defaultValue()`, `definition()` | Значение по умолчанию и полное нормализованное описание |

`modestox_fields()` вызывайте после хука `init`.

---

## Проверка и очистка значений

При сохранении каждое поле проходит очистку своего типа:

- **число** проверяется на тип и диапазон `min`/`max`;
- **выбор** (`select`, `radio`, `multiselect`, `checkbox`) принимает только ключи из `options`;
- **дата** проверяется на формат;
- **файл** проверяется на `http(s)` URL и разрешённое расширение;
- **dynamic_rows** принимает только объявленные колонки;
- **текст** обрезается по краям, у `text` убираются переносы строк.

Неверное значение не сохраняется: остаётся прежнее, а на странице появляется сообщение, например «Timeout: The value cannot be greater than 120».

HTML и скрипты в `text`, `textarea` и ячейках `dynamic_rows` сохраняются как есть только у пользователей с правом `unfiltered_html`. У остальных пропускается только безопасная разметка (`wp_kses_post`).

---

## Ошибки конфигурации

Ошибка в описании не ломает сайт и не останавливает админку. Плагин с ошибкой пропускается, остальные работают, а администратор видит уведомление с указанием плагина и места ошибки:

```
Plugin 'my_plugin' (settings page): groups.general.fields.timeout:
Field 'default' for number field 'timeout' must be a strict integer or float.
```

```
Plugin 'my_addon' (global settings): Tab 'shop' is already declared by plugin 'my_plugin'.
```

Библиотека останавливается на первой ошибке, поэтому после исправления одной ошибки может появиться следующая.

---

## Ограничения

- `required` проверяется только браузером, сервер не проверяет обязательность.
- Ключи `validation` и `provider` принимаются библиотекой, но адаптер их пока не применяет.
- В поле `datetime` браузер не показывает секунды, поэтому при повторном сохранении они обнуляются.
- Владелец вкладки или секции на общей странице определяется порядком загрузки плагинов.

---

## Структура проекта

```text
modestox-config-processor-wp.php   точка входа плагина
src/
├── Plugin.php                     подключение хуков
├── functions.php                  modestox_config(), modestox_fields()
├── Registry/                      сбор, проверка и хранение страниц и опций
├── Builder/ConfigMerger.php       объединение общей страницы, правила владения
├── Field/FieldTypeRegistry.php    типы полей, включая свои
├── Api/                           FormFields, FieldView — поля для своих форм
├── Admin/                         меню, Settings API, ассеты, уведомления
│   └── Ui/                        вывод страниц, групп и полей
│       └── Common/Field/          по классу на тип поля
└── Exception/
assets/
├── css/admin-settings.css
└── js/
    ├── admin-fields.js            dynamic_rows, file, image
    └── admin-depends.js           зависимости полей
```

---

## Лицензия

MIT