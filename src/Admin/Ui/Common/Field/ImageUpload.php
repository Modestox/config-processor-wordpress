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
 * Class ImageUpload
 *
 * Generates resource links matching native media management file assets frameworks with live preview state.
 */
class ImageUpload extends AbstractField
{
    /**
     * Renders native file management paths layout blocks with live asset preview cards.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'mtx-upload-path-hidden');
        $imageUrl = (string)$attr['value'];
        $hasImage = $imageUrl !== '';
        ?>
        <div class="mtx-media-uploader-component" id="container_<?php echo esc_attr($attr['id']); ?>">
            <input type="hidden"
                   id="<?php echo esc_attr($attr['id']); ?>"
                   name="<?php echo esc_attr($attr['option_name']); ?>"
                   value="<?php echo esc_attr($imageUrl); ?>"
                   class="mtx-image-value-target" />

            <div class="mtx-media-preview-box" style="<?php echo $hasImage ? '' : 'display: none;'; ?>">
                <div class="mtx-preview-browser-mockup">
                    <img src="<?php echo esc_url($imageUrl); ?>" class="mtx-preview-image-node" alt="Favicon preview" />
                    <div class="mtx-preview-browser-tab">
                        <img src="<?php echo esc_url($imageUrl); ?>" class="mtx-preview-favicon-node" alt="" />
                        <span>Config Test</span>
                        <span class="dashicons dashicons-no-alt"></span>
                    </div>
                </div>
            </div>

            <div class="mtx-media-actions-group">
                <button type="button"
                        class="button button-secondary mtx-media-upload-trigger"
                        data-target="<?php echo esc_attr($attr['id']); ?>"
                        data-label-upload="<?php esc_attr_e('Upload Logo'); ?>"
                        data-label-change="<?php esc_attr_e('Change Site Icon'); ?>">
                    <?php echo $hasImage ? esc_html__('Change') : esc_html__('Upload Logo'); ?>
                </button>

                <button type="button"
                        class="button mtx-media-remove-trigger"
                        data-target="<?php echo esc_attr($attr['id']); ?>"
                        style="<?php echo $hasImage ? '' : 'display: none;'; ?>">
                    <?php esc_html_e('Remove'); ?>
                </button>
            </div>
        </div>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment mtx-media-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}