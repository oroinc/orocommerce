<?php

namespace Oro\Bundle\ProductBundle\Processor\Create;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\RelatedItem\RelatedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AssignerStrategyInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class AddRelatedProduct implements ProcessorInterface
{
    /**
     * @var AssignerStrategyInterface
     */
    private $assignerStrategy;

    /**
     * @var DoctrineHelper
     */
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

        if ($this->relationAlreadyExists($productFrom, $productTo)) {
            $context->addError(Error::createValidationError(
                Constraint::VALUE,
                new Label('oro.product.related_items.related_product.relation_already_exists', 'validators')
            ));

            return;
        }

        try {
            $this->assignerStrategy->addRelations($productFrom, [$productTo]);

            $relatedProduct = $this->getRelatedProductsRepository()->findOneBy([
                'product' => $productFrom,
                'relatedItem' => $productTo
            ]);

            $context->setResult($relatedProduct);
            $context->setId($relatedProduct->getId());
            $context->skipGroup('save_data');
        } catch (\InvalidArgumentException $e) {
            $errorDetail = (new Label($e->getMessage(), 'validators'))->setTranslateDirectly(true);
            $context->addError(Error::createValidationError(Constraint::VALUE, $errorDetail));
        } catch (\LogicException $e) {
            $errorDetail = (new Label($e->getMessage(), 'validators'))->setTranslateDirectly(true);
            $context->addError(Error::createValidationError(Constraint::VALUE, $errorDetail));
        } catch (\OverflowException $e) {
            $errorDetail = (new Label($e->getMessage(), 'validators'))->setTranslateDirectly(true);
            $context->addError(Error::createValidationError(Constraint::VALUE, $errorDetail));
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
}
