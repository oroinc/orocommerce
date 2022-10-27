<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\UIBundle\Tools\UrlHelper;

/**
 * Adds imageWebp column.
 */
class WebpAwareFrontendProductDatagridListener
{
    private const COLUMN_IMAGE_WEBP = 'imageWebp';

    private DataGridThemeHelper $themeHelper;

    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    private WebpConfiguration $webpConfiguration;

    private UrlHelper $urlHelper;

    public function __construct(
        DataGridThemeHelper $themeHelper,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        WebpConfiguration $webpConfiguration,
        UrlHelper $urlHelper
    ) {
        $this->themeHelper = $themeHelper;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->webpConfiguration = $webpConfiguration;
        $this->urlHelper = $urlHelper;
    }

    public function onPreBuild(PreBuild $event): void
    {
        if (!$this->webpConfiguration->isEnabledIfSupported()) {
            return;
        }

        $config = $event->getConfig();

        $columns[self::COLUMN_IMAGE_WEBP] = [
            'label' => 'oro.product.webp_image.label'
        ];

        $config->offsetAddToArrayByPath('[columns]', $columns);
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        if (!$this->webpConfiguration->isEnabledIfSupported()) {
            return;
        }

        $this->addProductImages($event, $event->getRecords());
    }

    /**
     * @param SearchResultAfter $event
     * @param ResultRecord[] $records
     */
    private function addProductImages(SearchResultAfter $event, array $records): void
    {
        $gridName = $event->getDatagrid()->getName();
        $theme = $this->themeHelper->getTheme($gridName);
        switch ($theme) {
            case DataGridThemeHelper::VIEW_GRID:
                $imageFilter = 'product_large';
                break;
            case DataGridThemeHelper::VIEW_LIST:
            case DataGridThemeHelper::VIEW_TILES:
                $imageFilter = 'product_medium';
                break;
            default:
                return;
        }

        foreach ($records as $record) {
            $imageData[self::COLUMN_IMAGE_WEBP] = $this
                ->getProductImageUrl((string) $record->getValue('image_' . $imageFilter . '_webp'), $imageFilter);

            $record->addData($imageData);
        }
    }

    private function getProductImageUrl(string $path, string $placeholderFilter)
    {
        if ($path !== '') {
            // The image URL obtained from the search index does not contain a base url
            // so may not represent an absolute path.
            $imageUrl = $this->urlHelper->getAbsolutePath($path);
        } else {
            $imageUrl = $this->imagePlaceholderProvider->getPath($placeholderFilter, 'webp');
        }

        return $imageUrl;
    }
}
