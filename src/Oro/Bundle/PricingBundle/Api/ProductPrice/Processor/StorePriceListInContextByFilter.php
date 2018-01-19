<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves priceListId from filter to context for later use
 */
class StorePriceListInContextByFilter implements ProcessorInterface
{
    const FILTER_PARAM_PRICE_LIST = 'filter[priceList]';

    const VALIDATION_MESSAGE_PRICE_LIST_REQUIRED = 'priceList filter is required';
    const VALIDATION_MESSAGE_PRICE_LIST_NOT_FOUND = 'specified priceList does not exist';

    /**
     * @var PriceListIDContextStorageInterface
     */
    private $priceListIDContextStorage;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param PriceListIDContextStorageInterface $priceListIDContextStorage
     * @param DoctrineHelper                     $doctrineHelper
     */
    public function __construct(
        PriceListIDContextStorageInterface $priceListIDContextStorage,
        DoctrineHelper $doctrineHelper
    ) {
        $this->priceListIDContextStorage = $priceListIDContextStorage;
        $this->doctrineHelper = $doctrineHelper;
    }
    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof Context) {
            return;
        }

        if (false === $context->getFilterValues()->has(self::FILTER_PARAM_PRICE_LIST)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    self::VALIDATION_MESSAGE_PRICE_LIST_REQUIRED
                )
            );

            return;
        }

        $priceListID = $context->getFilterValues()->get(self::FILTER_PARAM_PRICE_LIST)->getValue();

        if (false === $this->isValidPriceListId($priceListID)) {
            $context->addError(
                Error::createValidationError(
                    Constraint::FILTER,
                    self::VALIDATION_MESSAGE_PRICE_LIST_NOT_FOUND
                )
            );
        }

        $this->priceListIDContextStorage->store($priceListID, $context);
    }
    /**
     * @param int $priceListID
     *
     * @return bool
     */
    private function isValidPriceListId(int $priceListID): bool
    {
        $priceListRepository = $this->doctrineHelper->getEntityRepositoryForClass(PriceList::class);

        return null !== $priceListRepository->find($priceListID);
    }
}
