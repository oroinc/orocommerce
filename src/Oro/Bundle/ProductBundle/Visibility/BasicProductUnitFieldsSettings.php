<?php

namespace Oro\Bundle\ProductBundle\Visibility;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class BasicProductUnitFieldsSettings implements ProductUnitFieldsSettingsInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isProductUnitSelectionVisible(Product $product)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isProductPrimaryUnitVisible(Product $product = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAddingAdditionalUnitsToProductAvailable(Product $product = null)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePrimaryUnitChoices(Product $product = null)
    {
        return $this->doctrineHelper->getEntityRepository(ProductUnit::class)->findAll();
    }
}
