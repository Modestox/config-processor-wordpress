<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui\GlobalLayout;

use Modestox\ConfigProcessorWp\Admin\Ui\Common\FieldRender;
use Modestox\ConfigProcessorWp\Admin\OptionNameBuilder;

/**
 * Class GroupContainer
 *
 * Renders configuration group containers and their fields.
 */
class GroupContainer
{
    /**
     * GroupContainer constructor.
     *
     * @param array<string, array<string, mixed>> $groups
     * @param string $namespace
     */
    public function __construct(
            private readonly array $groups,
            private readonly string $namespace
    ) {}

    /**
     * Renders configuration group boxes with standard tables.
     *
     * @return void
     */
    public function render(): void
    {
        foreach ($this->groups as $groupKey => $groupData) {
            $fields = (array)($groupData['fields'] ?? []);

            // Dynamically generate unique database keys based on layout hierarchy
            foreach ($fields as $fKey => &$field) {
                if (!isset($field['_option_name'])) {
                    $field['_option_name'] = OptionNameBuilder::build(
                            $this->namespace,
                            (string)$groupKey,
                            (string)$fKey
                    );
                    $field['option_name'] = $field['_option_name'];
                }
            }
            unset($field);

            ?>
            <div class="mtx-global-group-table" style="margin-bottom: 30px;">
                <h3 style="border-bottom: 1px solid #ccd0d4; padding-bottom: 8px; margin-bottom: 15px; font-size: 16px; font-weight: 600;">
                    <?php echo esc_html($groupData['label'] ?? $groupKey); ?>
                </h3>

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