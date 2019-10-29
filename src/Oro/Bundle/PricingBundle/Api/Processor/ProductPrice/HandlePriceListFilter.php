<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Gets a value of the "priceList" filter and saves the price list ID to context.
 */
class HandlePriceListFilter implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $priceListFilterValue = $context->getFilterValues()->get('priceList');
        if (null !== $priceListFilterValue) {
            $priceListId = $priceListFilterValue->getValue();
            if ($this->isValidPriceListId($priceListId)) {
                PriceListIdContextUtil::storePriceListId($context, $priceListId);
            } else {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER, 'The specified price list does not exist.')
                );
            }
        } else {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The "priceList" filter is required.')
            );
        }
    }

    /**
     * @param int $priceListId
     *
     * @return bool
     */
    private function isValidPriceListId(int $priceListId): bool
    {
        $priceListRepository = $this->doctrineHelper->getEntityRepositoryForClass(PriceList::class);

        return null !== $priceListRepository->find($priceListId);
    }
}
