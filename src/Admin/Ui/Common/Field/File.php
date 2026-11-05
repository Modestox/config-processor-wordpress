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
 * Class File
 *
 * File picked from the WordPress Media Library. The value is the file URL ('' when empty).
 * 'extensions' (e.g. 'pdf,zip') restricts the allowed file types. 'upload_dir' is not used:
 * files are uploaded through the Media Library.
 */
class File extends AbstractField
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        $url = (string)$value;
        $hasFile = $url !== '';
        $id = $this->inputId($optionName);
        ?>
        <div class="mtx-media-field <?php echo esc_attr((string)($field['class'] ?? '')); ?>"
             data-kind="<?php echo esc_attr($this->mediaKind()); ?>"
             data-label-select="<?php echo esc_attr($this->selectLabel()); ?>"
             data-label-change="<?php echo esc_attr($this->changeLabel()); ?>">
            <input type="hidden"
                   id="<?php echo esc_attr($id); ?>"
                   name="<?php echo esc_attr($optionName); ?>"
                   value="<?php echo esc_attr($url); ?>"
                   class="mtx-media-value" />
            <div class="mtx-media-preview-box"<?php echo $hasFile ? '' : ' style="display: none;"'; ?>>
                <?php $this->renderPreview($url); ?>
            </div>
            <div class="mtx-media-actions-group">
                <button type="button" class="button button-secondary mtx-media-select">
                    <?php echo esc_html($hasFile ? $this->changeLabel() : $this->selectLabel()); ?>
                </button>
                <button type="button" class="button mtx-media-remove"<?php echo $hasFile ? '' : ' style="display: none;"'; ?>>
                    <?php echo esc_html__('Remove', 'modestox-config-processor-wp'); ?>
                </button>
            </div>
        </div>
        <?php
        $allowed = $this->allowedExtensions($field);
        if ($allowed !== [] && (string)($field['extensions'] ?? '') !== ''): ?>
            <p class="description mtx-sys-config-field-comment">
                <?php
                /* translators: %s: list of file extensions */
                echo esc_html(sprintf(__('Allowed file types: %s', 'modestox-config-processor-wp'), implode(', ', $allowed)));
                ?>
            </p>
        <?php endif;
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(array $field): mixed
    {
        return '';
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
    protected function normalize(mixed $value, array $field): string
    {
        $value = trim($this->scalarToString($value));
        if ($value === '') {
            return '';
        }

        $url = esc_url_raw($value, ['http', 'https']);
        if ($url === '') {
            $this->fail(__('The value must be a valid http(s) URL.', 'modestox-config-processor-wp'));
        }

        $allowed = $this->allowedExtensions($field);
        if ($allowed !== []) {
            $path = (string)wp_parse_url($url, PHP_URL_PATH);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowed, true)) {
                $this->fail(
                /* translators: %s: list of file extensions */
                    sprintf(__('This file type is not allowed. Allowed file types: %s.', 'modestox-config-processor-wp'), implode(', ', $allowed)),
                );
            }
        }

        return $url;
    }

    /**
     * Lower-case extensions from the 'extensions' key; an empty list allows any file.
     *
     * @param array<string, mixed> $field
     * @return list<string>
     */
    protected function allowedExtensions(array $field): array
    {
        $extensions = strtolower((string)($field['extensions'] ?? ''));

        return $extensions === '' ? [] : array_values(array_filter(explode(',', $extensions)));
    }

    /**
     * Media Library filter: 'image' or '' for any file.
     *
     * @return string
     */
    protected function mediaKind(): string
    {
        return '';
    }

    /**
     * @return string
     */
    protected function selectLabel(): string
    {
        return __('Select file', 'modestox-config-processor-wp');
    }

    /**
     * @return string
     */
    protected function changeLabel(): string
    {
        return __('Change file', 'modestox-config-processor-wp');
    }

    /**
     * @param string $url
     * @return void
     */
    protected function renderPreview(string $url): void
    {
        $name = $url !== '' ? wp_basename((string)wp_parse_url($url, PHP_URL_PATH)) : '';
        ?>
        <span class="dashicons dashicons-media-default"></span>
        <a class="mtx-media-file-link" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($name); ?></a>
        <?php
    }
}