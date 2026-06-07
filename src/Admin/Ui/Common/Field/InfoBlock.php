<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui\Common\Field;

/**
 * Class InfoBlock
 *
 * Structural presentation container that bypasses interactive values collection.
 */
class InfoBlock extends AbstractField
{
    /**
     * Renders read-only messaging structural layouts.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $customClass = (string)($fieldData['class'] ?? 'notice notice-info');
        $rawText = (string)($fieldData['text'] ?? '');
        $format = (string)($fieldData['format'] ?? 'plain');
        ?>
        <div class="<?php echo esc_attr(trim($customClass)); ?>" style="margin: 5px 0; padding: 10px;">
            <p>
                <?php
                if ($format === 'html') {
                    echo wp_kses_post(trim($rawText));
                } else {
                    echo esc_html(trim($rawText));
                }
                ?>
            </p>
        </div>
        <?php
    }
}