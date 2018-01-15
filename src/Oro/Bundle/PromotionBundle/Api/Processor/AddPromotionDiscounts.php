<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class AddPromotionDiscounts implements ProcessorInterface
{
    /**
     * @var AppliedDiscountsProvider
     */
    private $appliedDiscountsProvider;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param AppliedDiscountsProvider $appliedDiscountsProvider
     * @param DoctrineHelper           $doctrineHelper
     */
    public function __construct(
        AppliedDiscountsProvider $appliedDiscountsProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->appliedDiscountsProvider = $appliedDiscountsProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $orderData = $context->getResult();
        if (!is_array($orderData)) {
            return;
        }

        $config = $context->getConfig();
        if (!$config) {
            return;
        }

        $order = $this->getOrder($orderData, $config);
        if (!$order) {
            return;
        }

        $orderData = $this->processDiscountField($orderData, $config, $order);
        $orderData = $this->processShippingDiscountField($orderData, $config, $order);

        $context->setResult($orderData);
    }

    /**
     * @param array                  $orderData
     * @param EntityDefinitionConfig $config
     *
     * @return null|Order
     */
    private function getOrder(array $orderData, EntityDefinitionConfig $config)
    {
        $idField = $config->findFieldNameByPropertyPath('id');
        if ($idField && array_key_exists($idField, $orderData)) {
            return $this->getOrderRepository()->find($orderData[$idField]);
        }

        return null;
    }

    /**
     * @return OrderRepository
     */
    private function getOrderRepository(): OrderRepository
    {
        return $this->doctrineHelper->getEntityRepository(Order::class);
    }

    /**
     * @param array                  $orderData
     * @param EntityDefinitionConfig $config
     * @param Order                  $order
     *
     * @return array
     */
    private function processDiscountField(array $orderData, EntityDefinitionConfig $config, Order $order): array
    {
        $discountFieldName = $config->findFieldNameByPropertyPath('discount');
        if ($discountFieldName &&
            !array_key_exists($discountFieldName, $orderData) &&
            !$config->getField($discountFieldName)->isExcluded()
        ) {
            $orderData[$discountFieldName] = $this->appliedDiscountsProvider->getDiscountsAmountByOrder($order);
        }

        return $orderData;
    }

    /**
     * @param array                  $orderData
     * @param EntityDefinitionConfig $config
     * @param Order                  $order
     *
     * @return array
     */
    private function processShippingDiscountField(array $orderData, EntityDefinitionConfig $config, Order $order): array
    {
        $shippingDiscountFieldName = $config->findFieldNameByPropertyPath('shippingDiscount');
        if ($shippingDiscountFieldName &&
            !array_key_exists($shippingDiscountFieldName, $orderData) &&
            !$config->getField($shippingDiscountFieldName)->isExcluded()
        ) {
            $orderData[$shippingDiscountFieldName] = $this->appliedDiscountsProvider
                ->getShippingDiscountsAmountByOrder($order);
        }

        return $orderData;
    }
}
