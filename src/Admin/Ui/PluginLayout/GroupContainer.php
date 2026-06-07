<?php
/**
 * Modestox Config Processor Integration Wordpress 
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui\PluginLayout;

use Modestox\ConfigProcessorWp\Admin\Ui\Common\FieldRender;
use Modestox\ConfigProcessorWp\Admin\OptionNameBuilder;

/**
 * Class GroupContainer
 *
 * Handles rendering of visual layout group boxes inside the active section viewport.
 */
class GroupContainer
{
    /**
     * GroupContainer constructor.
     *
     * @param array<string, array<string, mixed>> $groups
     * @param string $prefix Plugin standalone option prefix mapping value.
     */
    public function __construct(
            private readonly array $groups,
            private readonly string $prefix = ''
    ) {}

    /**
     * Renders group box components block.
     *
     * @return void
     */
    public function render(): void
    {
        foreach ($this->groups as $groupKey => $groupData) {
            $fields = (array)($groupData['fields'] ?? []);

            foreach ($fields as $fKey => &$field) {
                if (!isset($field['_option_name'])) {
                    $field['_option_name'] = OptionNameBuilder::build(
                            $this->prefix,
                            (string)$groupKey,
                            (string)$fKey
                    );
                    $field['option_name'] = $field['_option_name'];
                }
            }
            unset($field);

            ?>
            <div class="mtx-plugin-table">
                <h2><?php echo esc_html($groupData['label'] ?? $groupKey); ?></h2>

                <table class="form-table" role="presentation">
                    <tbody>
                    <?php (new FieldRender($fields, 'table'))->render(); ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }
}