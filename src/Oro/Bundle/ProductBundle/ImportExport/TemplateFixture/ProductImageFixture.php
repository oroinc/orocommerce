<?php

namespace Oro\Bundle\ProductBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

/**
 * Provides template fixture data for product image import/export.
 *
 * This fixture generates sample product image data used in import/export templates,
 * helping users understand the expected format and structure for product image data.
 */
class ProductImageFixture implements TemplateFixtureInterface
{
    #[\Override]
    public function getEntityClass()
    {
        return ProductImage::class;
    }

    #[\Override]
    public function getData()
    {
        $productImage = new ProductImage();

        $product = new Product();
        $product->setSku('sku_001');

        $productImageType1 = new ProductImageType('main');
        $productImageType2 = new ProductImageType('additional');

        $file = new File();
        $file->setOriginalFilename('sku_001_1.jpg');

        $productImage->setImage($file);
        $productImage->addType($productImageType1);
        $productImage->addType($productImageType2);
        $productImage->setProduct($product);

        return new \ArrayIterator(array($productImage));
    }

    #[\Override]
    public function getEntity($key)
    {
        return new ProductImage();
    }

    #[\Override]
    public function fillEntityData($key, $entity)
    {
    }
}
