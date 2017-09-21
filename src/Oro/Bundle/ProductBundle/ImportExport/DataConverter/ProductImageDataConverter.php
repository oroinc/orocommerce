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
        foreach ($this->imageTypeProvider->getImageTypes() as $imageTypeName => $imageType) {
            $typesHeader[ucfirst($imageTypeName)] = sprintf('types:%s', $imageTypeName);
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
