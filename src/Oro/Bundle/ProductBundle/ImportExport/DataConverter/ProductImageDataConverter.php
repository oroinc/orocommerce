<?php

namespace Oro\Bundle\ProductBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

class ProductImageDataConverter extends AbstractTableDataConverter
{
    /** @var  ImageTypeProvider */
    protected $imageTypeProvider;


    public function setImageTypeProvider(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function getHeaderConversionRules()
    {
        foreach (array_keys($this->imageTypeProvider->getImageTypes()) as $key => $imageType) {
            $typesHeader[ucfirst($imageType)] = sprintf('types:%s', $imageType);
        }

        return array_merge(
            [
                'SKU' => 'product:sku',
                'Name' => 'image:name',
            ],
            $typesHeader
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->getHeaderConversionRules());
    }
}
