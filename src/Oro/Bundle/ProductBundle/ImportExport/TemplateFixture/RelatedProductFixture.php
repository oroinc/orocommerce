<?php

namespace Oro\Bundle\ProductBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

/**
 * Fixture of Related Product entity used for generation of import-export template.
 */
class RelatedProductFixture implements TemplateFixtureInterface
{
    #[\Override]
    public function getEntityClass(): string
    {
        return RelatedProduct::class;
    }

    #[\Override]
    public function getEntity($key): RelatedProduct
    {
        return new RelatedProduct();
    }

    #[\Override]
    public function fillEntityData($key, $entity): void
    {
    }

    #[\Override]
    public function getData(): \Iterator
    {
        return new \ArrayIterator(
            [
                ['sku' => 'sku-1', 'relatedItem' => 'sku-2,sku-3']
            ]
        );
    }
}
