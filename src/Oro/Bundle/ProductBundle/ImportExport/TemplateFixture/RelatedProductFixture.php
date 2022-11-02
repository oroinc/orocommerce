<?php

namespace Oro\Bundle\ProductBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

/**
 * Fixture of Related Product entity used for generation of import-export template.
 */
class RelatedProductFixture implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass(): string
    {
        return RelatedProduct::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($key): RelatedProduct
    {
        return new RelatedProduct();
    }

    /**
     * {@inheritdoc}
     */
    public function fillEntityData($key, $entity): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): \Iterator
    {
        return new \ArrayIterator(
            [
                ['sku' => 'sku-1', 'relatedItem' => 'sku-2,sku-3']
            ]
        );
    }
}
