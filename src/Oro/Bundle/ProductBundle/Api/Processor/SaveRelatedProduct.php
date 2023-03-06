<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Create\SaveEntity;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves a relation between two products to the database.
 */
class SaveRelatedProduct implements ProcessorInterface
{
    private AssignerStrategyInterface $assignerStrategy;
    private DoctrineHelper $doctrineHelper;

    public function __construct(AssignerStrategyInterface $assignerStrategy, DoctrineHelper $doctrineHelper)
    {
        $this->assignerStrategy = $assignerStrategy;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->isProcessed(SaveEntity::OPERATION_NAME)) {
            return;
        }

        /** @var RelatedProduct $relatedProduct */
        $relatedProduct = $context->getResult();
        $productFrom = $relatedProduct->getProduct();
        $productTo = $relatedProduct->getRelatedItem();
        if ($this->addRelation($productFrom, $productTo, $context)) {
            $relatedProduct = $this->getRelatedProductsRepository()->findOneBy([
                'product'     => $productFrom,
                'relatedItem' => $productTo
            ]);
            $context->setResult($relatedProduct);
            $context->setId($relatedProduct->getId());
        }
        $context->setProcessed(SaveEntity::OPERATION_NAME);
    }

    private function getRelatedProductsRepository(): RelatedProductRepository
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(RelatedProduct::class);
    }

    private function relationAlreadyExists(Product $productFrom, Product $productTo): bool
    {
        return $this->getRelatedProductsRepository()->exists($productFrom, $productTo);
    }

    private function addRelation(Product $productFrom, Product $productTo, SingleItemContext $context): bool
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

    private function createValueValidationError(Label|string $detail): Error
    {
        return Error::createValidationError(Constraint::VALUE, $detail);
    }
}
