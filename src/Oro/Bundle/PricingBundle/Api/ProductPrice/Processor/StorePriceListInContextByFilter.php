<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves priceListId from filter to context for later use
 */
class StorePriceListInContextByFilter implements ProcessorInterface
{
    private const FILTER_PARAM_PRICE_LIST = 'filter[priceList]';

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

        $filterValues = $context->getFilterValues();

        if (!$filterValues->has(self::FILTER_PARAM_PRICE_LIST)) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'priceList filter is required')
            );

            return;
        }

        $priceListId = $filterValues->get(self::FILTER_PARAM_PRICE_LIST)->getValue();

        if (!$this->isValidPriceListId($priceListId)) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'specified priceList does not exist')
            );
        }

        PriceListIdContextUtil::storePriceListId($context, $priceListId);
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
