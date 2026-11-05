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

use Modestox\ConfigProcessorWp\Registry\ConfigRegistry;

/**
 * Class Notices
 *
 * Shows configuration errors of registered plugins to administrators instead of stopping the request.
 */
final class Notices
{
    public function __construct(private readonly ConfigRegistry $registry) {}

    /**
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $errors = array_values(array_unique($this->registry->errors()));
        if ($errors === []) {
            return;
        }
        ?>
        <div class="notice notice-error">
            <p><strong><?php echo esc_html__('Modestox Config Processor: configuration errors', 'modestox-config-processor-wp'); ?></strong></p>
            <ul style="list-style: disc; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><code><?php echo esc_html($error); ?></code></li>
                <?php endforeach; ?>
            </ul>
            <p><?php echo esc_html__('The affected settings were skipped, everything else keeps working.', 'modestox-config-processor-wp'); ?></p>
        </div>
        <?php
    }
}