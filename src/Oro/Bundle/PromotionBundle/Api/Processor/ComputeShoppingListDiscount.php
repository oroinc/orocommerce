<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Api\Processor\ComputeShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "discount" field for ShoppingList entity.
 */
class ComputeShoppingListDiscount implements ProcessorInterface
{
    private const string DISCOUNT_FIELD_NAME = 'discount';

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly ValueTransformer $valueTransformer,
        private readonly TotalProcessorProvider $totalProvider
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequested(self::DISCOUNT_FIELD_NAME, $data)) {
            return;
        }

        $computedSubtotals = $this->getSubtotals(
            $data[$context->getConfig()->findFieldNameByPropertyPath('id')],
            $context
        );
        $discount = null;
        foreach ($computedSubtotals as $computedValue) {
            if ('discount' === $computedValue->getType() && $computedValue->isVisible()) {
                $discount = $computedValue->getAmount();
                break;
            }
        }
        if (null !== $discount) {
            $discount = $this->valueTransformer->transformValue(
                BigDecimal::of(0)->minus($discount)->toFloat(),
                DataType::MONEY,
                $context->getNormalizationContext()
            );
        }
        $data[self::DISCOUNT_FIELD_NAME] = $discount;
        $context->setData($data);
    }

    private function getSubtotals(int $shoppingListId, CustomizeLoadedDataContext $context): ArrayCollection
    {
        $subtotals = $context->get(ComputeShoppingListTotal::SHOPPING_LIST_SUB_TOTALS);
        if (null === $subtotals) {
            $em = $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class);
            $shoppingList = $em->getReference(ShoppingList::class, $shoppingListId);
            $em->refresh($shoppingList);
            $subtotals = $this->totalProvider->getSubtotals($shoppingList);
        }

        return $subtotals;
    }
}
