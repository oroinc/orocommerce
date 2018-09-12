<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Transforms id from request in 'guid-priceListId' format back to 'guid'
 * and save 'priceListId' to the context.
 */
class NormalizeInputProductPriceId implements ProcessorInterface
{
    private const REGEX_FOR_API_PRODUCT_PRICE_ID = '/(.+)\-(\d+)$/';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param ValidatorInterface $validator
     */
    public function __construct(DoctrineHelper $doctrineHelper, ValidatorInterface $validator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $productPriceId = $context->getId();
        if (!$productPriceId || !is_string($productPriceId)) {
            return;
        }

        $matched = preg_match(self::REGEX_FOR_API_PRODUCT_PRICE_ID, $productPriceId, $matches);
        if (!$matched || 3 !== count($matches)) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }

        $uuid = $matches[1];
        $priceListId = (int)$matches[2];
        if (!$this->isValidUuid($uuid) || !$this->isValidPriceListId($priceListId)) {
            throw new NotFoundHttpException('An entity with the requested identifier does not exist.');
        }

        $context->setId($uuid);
        PriceListIdContextUtil::storePriceListId($context, $priceListId);
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
