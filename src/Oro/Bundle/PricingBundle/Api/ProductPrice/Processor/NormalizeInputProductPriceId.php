<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Transforms id from request in 'guid-priceListId' format back to 'guid'
 * and save 'priceListId' to the Context.
 */
class NormalizeInputProductPriceId implements ProcessorInterface
{
    /**
     * @internal
     */
    const REGEX_FOR_API_PRODUCT_PRICE_ID = '/(.+)\-(\d+)$/';

    /**
     * @var PriceListIDContextStorageInterface
     */
    private $priceListIDContextStorage;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param PriceListIDContextStorageInterface $priceListIDContextStorage
     * @param DoctrineHelper                     $doctrineHelper
     * @param ValidatorInterface                 $validator
     */
    public function __construct(
        PriceListIDContextStorageInterface $priceListIDContextStorage,
        DoctrineHelper $doctrineHelper,
        ValidatorInterface $validator
    ) {
        $this->priceListIDContextStorage = $priceListIDContextStorage;
        $this->doctrineHelper = $doctrineHelper;
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof SingleItemContext) {
            return;
        }

        if (!$context->getId() || !is_string($context->getId())) {
            return;
        }

        $matched = preg_match(self::REGEX_FOR_API_PRODUCT_PRICE_ID, $context->getId(), $matches);

        if (!$matched || 3 !== count($matches)) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }

        $uuid = $matches[1];
        $priceListID = (int)$matches[2];

        if (false === $this->isValidUuid($uuid) || false === $this->isValidPriceListId($priceListID)) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }

        $context->setId($uuid);
        $this->priceListIDContextStorage->store($priceListID, $context);
    }

    /**
     * @param string $uuid
     *
     * @return bool
     */
    private function isValidUuid(string $uuid): bool
    {
        return 0 === $this->validator->validate($uuid, new Uuid())->count();
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
