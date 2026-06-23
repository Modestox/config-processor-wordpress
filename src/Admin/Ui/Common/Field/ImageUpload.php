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
 * Renders file upload inputs with media library integration and preview blocks.
 */
class ImageUpload extends AbstractField
{
    /**
     * Renders media upload element markup.
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
                   class="mtx-image-value-target"/>

            <?php if ($attr['required_attr'] !== ''): ?>
                <input type="text"
                       class="mtx-image-value-target"
                       value="<?php echo esc_attr($imageUrl); ?>"
                       style="position:absolute; width:0; height:0; opacity:0; pointer-events:none; padding:0; border:none;"
                        <?php echo $attr['required_attr']; ?>
                       tabindex="-1" />
            <?php endif; ?>

            <div class="mtx-media-preview-box" style="<?php echo $hasImage ? '' : 'display: none;'; ?>">
                <div class="mtx-preview-browser-mockup">
                    <img src="<?php echo esc_url($imageUrl); ?>" class="mtx-preview-image-node"
                         alt="<?php echo esc_attr__('Site asset preview', 'modestox-config-processor-wp'); ?>"/>
                    <div class="mtx-preview-browser-tab">
                        <img src="<?php echo esc_url($imageUrl); ?>" class="mtx-preview-favicon-node" alt=""/>
                        <span><?php echo esc_html(get_bloginfo('name')); ?></span>
                        <span class="dashicons dashicons-no-alt"></span>
                    </div>
                </div>
            </div>

            <div class="mtx-media-actions-group">
                <button type="button"
                        class="button button-secondary mtx-media-upload-trigger"
                        data-target="<?php echo esc_attr($attr['id']); ?>"
                        data-label-upload="<?php echo esc_attr__('Upload Logo', 'modestox-config-processor-wp'); ?>"
                        data-label-change="<?php echo esc_attr__('Change Site Icon', 'modestox-config-processor-wp'); ?>">
                    <?php echo $hasImage ? esc_html__('Change', 'modestox-config-processor-wp') : esc_html__(
                            'Upload Logo',
                            'modestox-config-processor-wp',
                    ); ?>
                </button>

                <button type="button"
                        class="button mtx-media-remove-trigger"
                        data-target="<?php echo esc_attr($attr['id']); ?>"
                        style="<?php echo $hasImage ? '' : 'display: none;'; ?>">
                    <?php echo esc_html__('Remove', 'modestox-config-processor-wp'); ?>
                </button>
            </div>
        </div>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment mtx-media-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}