<?php

namespace Oro\Bundle\ProductBundle\Action\Condition;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Helper\ProductHolderTrait;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class AtLeastOneAvailableProduct extends AbstractCondition implements ContextAccessorAwareInterface
{
    const NAME = 'at_least_one_available_product';

    use ContextAccessorAwareTrait, ProductHolderTrait;

    /** @var PropertyPathInterface */
    private $productIteratorPath;

    /** @var ProductManager */
    private $productManager;

    /** @var ProductRepository */
    private $productRepository;

    /**
     * @param ProductRepository $productRepository
     * @param ProductManager    $productManager
     */
    public function __construct(ProductRepository $productRepository, ProductManager $productManager)
    {
        $this->productManager = $productManager;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $propertyPath = reset($options);
        if ($propertyPath instanceof PropertyPathInterface) {
            $this->productIteratorPath = $propertyPath;
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $productHolderIterator = $this->resolveValue($context, $this->productIteratorPath);
        $products = $this->getProductIdsFromProductHolders($productHolderIterator);

        if (count($products) > 0) {
            $queryBuilder = $this->productRepository->getProductsQueryBuilder($products);
            $this->productManager->restrictQueryBuilder($queryBuilder, []);
            $products = $queryBuilder->getQuery()->getResult();
        }

        return count($products) > 0;
    }
}
