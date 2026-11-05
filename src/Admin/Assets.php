<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin;

use Modestox\ConfigProcessorWp\Plugin;

/**
 * Class Assets
 *
 * Styles and scripts of the fields, registered under stable handles so any admin page can load them:
 *
 *  - style  'modestox-admin-settings'
 *  - script 'modestox-admin-fields'   (dynamic rows, file and image fields)
 *  - script 'modestox-admin-depends'  (field dependencies)
 */
final class Assets
{
    public const STYLE = 'modestox-admin-settings';
    public const SCRIPT_FIELDS = 'modestox-admin-fields';
    public const SCRIPT_DEPENDS = 'modestox-admin-depends';

    private bool $enqueued = false;

    public function __construct(private readonly string $pluginFile) {}

    /**
     * Registers the handles. Safe to call more than once.
     *
     * @return void
     */
    public function register(): void
    {
        if (wp_style_is(self::STYLE, 'registered')) {
            return;
        }

        $baseUrl = plugin_dir_url($this->pluginFile);

        wp_register_style(self::STYLE, $baseUrl . 'assets/css/admin-settings.css', [], Plugin::VERSION);
        wp_register_script(self::SCRIPT_FIELDS, $baseUrl . 'assets/js/admin-fields.js', [], Plugin::VERSION, true);
        wp_register_script(self::SCRIPT_DEPENDS, $baseUrl . 'assets/js/admin-depends.js', [], Plugin::VERSION, true);
    }

    /**
     * Loads everything the fields need, including the Media Library. Safe to call more than once.
     *
     * Best called from 'admin_enqueue_scripts'. A call while the page is being printed still works:
     * WordPress prints the late style and the scripts in the footer.
     *
     * @return void
     */
    public function enqueue(): void
    {
        if ($this->enqueued) {
            return;
        }
        $this->enqueued = true;

        $this->register();

        wp_enqueue_media();
        wp_enqueue_style(self::STYLE);
        wp_enqueue_script(self::SCRIPT_FIELDS);
        wp_enqueue_script(self::SCRIPT_DEPENDS);
    }
}