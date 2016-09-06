<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;
use Oro\Bundle\ShippingBundle\QueryDesigner\Converter;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class Factory
{
    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param EntityFieldProvider $entityFieldProvider
     * @param FunctionProviderInterface $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry $doctrine
     * @param RestrictionBuilderInterface $restrictionBuilder
     */
    public function __construct(
        EntityFieldProvider $entityFieldProvider,
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        RestrictionBuilderInterface $restrictionBuilder
    ) {
        $this->converter = new Converter(
            $functionProvider,
            $virtualFieldProvider,
            $doctrine,
            $restrictionBuilder
        );

        $this->doctrine = $doctrine;
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     * @return $this
     */
    public function setVirtualRelationProvider(VirtualRelationProviderInterface $virtualRelationProvider)
    {
        $this->converter->setVirtualRelationProvider($virtualRelationProvider);
        return $this;
    }

    /**
     * @param Collection $lineItems
     * @param OrderLineItem $lineItem
     * @return OrderLineItemDecorator
     */
//TODO: remove dependency on OrderBundle
    public function createOrderLineItemDecorator(Collection $lineItems, OrderLineItem $lineItem)
    {
        return new OrderLineItemDecorator($this, $lineItems, $lineItem);
    }

    /**\
     * @param Collection $lineItems
     * @param Product $product
     * @return ProductDecorator
     */
    public function createProductDecorator(Collection $lineItems, Product $product)
    {
        return new ProductDecorator(
            $this->entityFieldProvider,
            $this->converter,
            $this->doctrine,
            $lineItems,
            $product
        );
    }
}
