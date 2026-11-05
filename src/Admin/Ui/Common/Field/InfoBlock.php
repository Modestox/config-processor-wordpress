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
 * Display-only notice. Nothing is stored. 'format' => 'html' allows post-safe markup.
 */
class InfoBlock extends AbstractField
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        $class = (string)($field['class'] ?? '');
        $text = (string)($field['text'] ?? '');
        ?>
        <div class="<?php echo esc_attr($class !== '' ? $class : 'notice notice-info inline'); ?> mtx-infoblock">
            <p><?php echo ($field['format'] ?? 'text') === 'html' ? wp_kses_post($text) : nl2br(esc_html($text)); ?></p>
        </div>
        <?php
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function storesValue(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function hasLabelTarget(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isFullWidth(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(array $field): mixed
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    protected function normalize(mixed $value, array $field): mixed
    {
        return null;
    }
}