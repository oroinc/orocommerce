<?php

namespace Oro\Bundle\ProductBundle\Api\Processor\Create;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves a relation between two products into the database.
 */
class AddRelatedProduct implements ProcessorInterface
{
    /** @var AssignerStrategyInterface */
    private $assignerStrategy;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AssignerStrategyInterface $assignerStrategy
     * @param DoctrineHelper            $doctrineHelper
     */
    public function __construct(AssignerStrategyInterface $assignerStrategy, DoctrineHelper $doctrineHelper)
    {
        $this->assignerStrategy = $assignerStrategy;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     * @param SingleItemContext $context
     */
    public function process(ContextInterface $context)
    {
        /** @var RelatedProduct $relatedProduct */
        $relatedProduct = $context->getResult();
        $productFrom = $relatedProduct->getProduct();
        $productTo = $relatedProduct->getRelatedItem();

        if ($this->addRelation($productFrom, $productTo, $context)) {
            $relatedProduct = $this->getRelatedProductsRepository()->findOneBy([
                'product' => $productFrom,
                'relatedItem' => $productTo
            ]);

            $context->setResult($relatedProduct);
            $context->setId($relatedProduct->getId());
            $context->skipGroup(ApiActionGroup::SAVE_DATA);
        }
    }

    /**
     * @return RelatedProductRepository|EntityRepository
     */
    private function getRelatedProductsRepository()
    {
        return $this->doctrineHelper->getEntityRepository(RelatedProduct::class);
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @return bool
     */
    private function relationAlreadyExists(Product $productFrom, Product $productTo)
    {
        return $this->getRelatedProductsRepository()->exists($productFrom, $productTo);
    }

    /**
     * @param Product $productFrom
     * @param Product $productTo
     * @param SingleItemContext $context
     * @return bool
     */
    private function addRelation(Product $productFrom, Product $productTo, SingleItemContext $context)
    {
        if ($this->relationAlreadyExists($productFrom, $productTo)) {
            $context->addError($this->createValueValidationError(
                new Label('oro.product.related_items.related_product.relation_already_exists', 'validators')
            ));
        } else {
            try {
                $this->assignerStrategy->addRelations($productFrom, [$productTo]);
            } catch (\InvalidArgumentException $e) {
                $context->addError($this->createValueValidationError($e->getMessage()));
            } catch (\LogicException $e) {
                $context->addError($this->createValueValidationError($e->getMessage()));
            } catch (\OverflowException $e) {
                $context->addError($this->createValueValidationError($e->getMessage()));
            }
        }

        return !$context->hasErrors();
    }

    /**
     * @param Label|string $detail
     *
     * @return Error
     */
    private function createValueValidationError($detail)
    {
        return Error::createValidationError(Constraint::VALUE, $detail);
    }
}
