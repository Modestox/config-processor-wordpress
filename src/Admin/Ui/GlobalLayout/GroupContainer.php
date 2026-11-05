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
use Modestox\ConfigProcessorWp\Field\FieldTypeRegistry;

/**
 * Class GroupContainer
 *
 * Groups of the active section of the global settings page.
 */
final class GroupContainer
{
    /**
     * @param array<string, array<string, mixed>> $groups Validated groups.
     * @param FieldTypeRegistry                   $types
     */
    public function __construct(
        private readonly array $groups,
        private readonly FieldTypeRegistry $types,
    ) {}

    /**
     * @return void
     */
    public function render(): void
    {
        foreach ($this->groups as $groupKey => $group) {
            ?>
            <div class="mtx-global-group-table" data-mtx-group="<?php echo esc_attr((string)$groupKey); ?>">
                <h3 class="mtx-global-group-title"><?php echo esc_html((string)$group['label']); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
                    <?php (new FieldRender((array)$group['fields'], $this->types))->render(); ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }
}