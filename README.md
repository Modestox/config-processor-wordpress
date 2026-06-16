# Modestox Config Processor WordPress

WordPress-адаптер для библиотеки Modestox Config Processor.

Плагин предоставляет декларативный API для создания административных страниц настроек WordPress на основе конфигурационных схем.

В основе адаптера используется библиотека Modestox Config Processor, которая отвечает за валидацию, нормализацию и обработку конфигурации.

---

## Возможности

* Поддержка многоуровневой структуры настроек
* Автоматическое создание административных страниц
* Валидация конфигурации перед рендерингом
* Автоматическая нормализация данных
* Сохранение настроек через WordPress Options API
* Поддержка зависимостей между полями
* Поддержка пользовательских типов полей
* Полная совместимость с Composer
* Архитектура на основе PSR-4
* PHP 8.3+

---

## Архитектура

Конфигурация строится по следующей структуре:

```text
Tabs
└── Sections
    └── Groups
        └── Fields
```

Пример:

```text
General
└── API Settings
    └── Authentication
        ├── Enable API
        └── API Key

    └── Security
        ├── Token
        └── Signature
```

---

## Требования

* PHP 8.3+
* WordPress 6.8+
* Composer

---

## Установка

### Установка библиотеки

```bash
composer require modestox/config-processor
```

### Установка адаптера

Склонируйте или скачайте проект:

```text
wp-content/plugins/modestox-config-processor-wordpress
```

Установите зависимости:

```bash
composer install
```

Активируйте плагин через административную панель WordPress.

---

## Быстрый старт

Регистрация конфигурации выполняется через WordPress hooks.

### Регистрация настроек плагина

```php
add_filter(
    'modestox_register_admin_plugin_config',
    function (array $config): array {

        $config['tabs']['general'] = [
            'label' => 'General'
        ];

        $config['sections']['main'] = [
            'tab'   => 'general',
            'label' => 'Main Settings',
            'groups' => [
                'base' => [
                    'label' => 'Configuration',
                    'fields' => [
                        'enabled' => [
                            'type'    => 'yes_no',
                            'label'   => 'Enable Module',
                            'default' => 0
                        ]
                    ]
                ]
            ]
        ];

        return $config;
    }
);
```

После регистрации конфигурации адаптер автоматически:

* валидирует структуру;
* нормализует значения;
* создаёт административные страницы;
* рендерит интерфейс;
* сохраняет значения.

---

## Глобальные настройки

Для регистрации общих настроек используется отдельный фильтр:

```php
add_filter(
    'modestox_register_admin_global_config',
    function (array $config): array {

        return $config;
    }
);
```

Глобальные настройки отображаются независимо от настроек отдельных плагинов.

---

## Поддерживаемые схемы

### SystemConfig

Полная структура:

```text
Tabs
└── Sections
    └── Groups
        └── Fields
```

Рекомендуется для:

* крупных плагинов;
* административных панелей;
* модульных систем.

---

### GroupedConfig

Упрощённая структура:

```text
Groups
└── Fields
```

Рекомендуется для:

* небольших плагинов;
* виджетов;
* независимых модулей.

---

## Поддерживаемые типы полей

### Text

```php
[
    'type' => 'text'
]
```

---

### Textarea

```php
[
    'type' => 'textarea'
]
```

---

### Password

```php
[
    'type' => 'password'
]
```

---

### Number

```php
[
    'type' => 'number',
    'min'  => 0,
    'max'  => 100
]
```

---

### Boolean

```php
[
    'type' => 'boolean'
]
```

---

### Yes / No

```php
[
    'type' => 'yes_no'
]
```

Автоматически создаёт варианты:

```php
[
    0 => 'No',
    1 => 'Yes'
]
```

---

### Select

```php
[
    'type' => 'select',
    'options' => [
        'a' => 'Option A',
        'b' => 'Option B'
    ]
]
```

---

### Multiselect

```php
[
    'type' => 'multiselect'
]
```

---

### Radio

```php
[
    'type' => 'radio'
]
```

---

### Checkbox

```php
[
    'type' => 'checkbox'
]
```

---

### Datetime

```php
[
    'type' => 'datetime'
]
```

---

### Image

```php
[
    'type' => 'image'
]
```

---

### File

```php
[
    'type' => 'file'
]
```

---

### Dynamic Rows

```php
[
    'type' => 'dynamic_rows'
]
```

Позволяет создавать динамические таблицы и повторяемые наборы данных.

---

### Infoblock

```php
[
    'type' => 'infoblock'
]
```

Используется для вывода информационных сообщений и справочной информации.

---

## Зависимости полей

Поддерживаются зависимости между полями внутри одной группы.

Пример:

```php
[
    'api_key' => [
        'type' => 'text',
        'depends' => [
            'enable_api' => 1
        ]
    ]
]
```

Поле будет отображаться только при выполнении условия.

---

## Структура проекта

```text
src/
├── Admin/
├── Builder/
├── Field/
├── Renderer/
├── Exception/
├── Ui/
└── Plugin.php
```

---

## Используемые компоненты

### Modestox Config Processor

Отвечает за:

* валидацию;
* нормализацию;
* сортировку;
* обработку зависимостей;
* проверку структуры конфигурации.

---

## Для разработчиков

Установка зависимостей:

```bash
composer install
```

Генерация автозагрузчика:

```bash
composer dump-autoload
```

Запуск тестов:

```bash
vendor/bin/phpunit
```

---

## Roadmap

Планируемые улучшения:

* расширенный JavaScript API для зависимостей;
* дополнительные типы полей;
* интеграция с WordPress Media Library;
* пользовательские валидаторы;
* экспорт и импорт настроек;
* REST API интеграция.

---
