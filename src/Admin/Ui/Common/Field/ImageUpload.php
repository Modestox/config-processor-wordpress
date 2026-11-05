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
 * Image picked from the WordPress Media Library, with a preview. The value is the image URL.
 * Without 'extensions' the usual image types are allowed.
 */
class ImageUpload extends File
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico', 'bmp'];

    /**
     * @inheritDoc
     */
    protected function allowedExtensions(array $field): array
    {
        $extensions = parent::allowedExtensions($field);

        return $extensions === [] ? self::IMAGE_EXTENSIONS : $extensions;
    }

    /**
     * @inheritDoc
     */
    protected function mediaKind(): string
    {
        return 'image';
    }

    /**
     * @inheritDoc
     */
    protected function selectLabel(): string
    {
        return __('Select image', 'modestox-config-processor-wp');
    }

    /**
     * @inheritDoc
     */
    protected function changeLabel(): string
    {
        return __('Change image', 'modestox-config-processor-wp');
    }

    /**
     * @inheritDoc
     */
    protected function renderPreview(string $url): void
    {
        ?>
        <img src="<?php echo esc_url($url); ?>" class="mtx-preview-image-node" alt=""/>
        <?php
    }
}