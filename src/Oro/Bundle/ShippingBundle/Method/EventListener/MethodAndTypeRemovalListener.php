<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEvent;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeRemovalEvent;
use Psr\Log\LoggerInterface;

class MethodAndTypeRemovalListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(DoctrineHelper $doctrineHelper, LoggerInterface $logger)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function onMethodRemove(MethodRemovalEvent $event)
    {
        $methodId = $event->getMethodIdentifier();
        $connection = $this->getEntityManager()->getConnection();
        try {
            $connection->beginTransaction();
            $this->getShippingMethodConfigRepository()->deleteByMethod($methodId);
            $this->getShippingMethodsConfigsRuleRepository()->disableRulesWithoutShippingMethods();
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e->getMessage(), [
                'shipping_method_identifier' => $methodId
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    public function onMethodTypeRemove(MethodTypeRemovalEvent $event)
    {
        $methodId = $event->getMethodIdentifier();
        $typeId = $event->getTypeIdentifier();
        $connection = $this->getEntityManager()->getConnection();
        try {
            $connection->beginTransaction();
            $this->deleteMethodTypeConfigsByMethodIdAndTypeId($methodId, $typeId);
            $this->deleteMethodConfigsWithoutTypeConfigs();
            $this->getShippingMethodsConfigsRuleRepository()->disableRulesWithoutShippingMethods();
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->critical($e->getMessage(), [
                'shipping_method_identifier' => $methodId,
                'shipping_method_type_identifier' => $typeId,
            ]);
        }
    }

    /**
     * @param string $methodId
     * @param string $typeId
     */
    private function deleteMethodTypeConfigsByMethodIdAndTypeId($methodId, $typeId)
    {
        $shippingMethodTypeConfigRepository = $this->getShippingMethodTypeConfigRepository();
        $ids = $shippingMethodTypeConfigRepository
            ->findIdsByMethodAndType($methodId, $typeId);
        $shippingMethodTypeConfigRepository->deleteByIds($ids);
    }

    private function deleteMethodConfigsWithoutTypeConfigs()
    {
        $shippingMethodConfigRepository = $this->getShippingMethodConfigRepository();
        $ids = $shippingMethodConfigRepository->findIdsWithoutTypeConfigs();
        $shippingMethodConfigRepository->deleteByIds($ids);
    }

    /**
     * @return \Doctrine\ORM\EntityManager|null
     */
    private function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManagerForClass('OroShippingBundle:ShippingMethodsConfigsRule');
    }

    /**
     * @return ShippingMethodConfigRepository|\Doctrine\ORM\EntityRepository
     */
    private function getShippingMethodConfigRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroShippingBundle:ShippingMethodConfig');
    }

    /**
     * @return ShippingMethodTypeConfigRepository|\Doctrine\ORM\EntityRepository
     */
    private function getShippingMethodTypeConfigRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroShippingBundle:ShippingMethodTypeConfig');
    }

    /**
     * @return ShippingMethodsConfigsRuleRepository|\Doctrine\ORM\EntityRepository
     */
    private function getShippingMethodsConfigsRuleRepository()
    {
        return $this->doctrineHelper->getEntityRepository('OroShippingBundle:ShippingMethodsConfigsRule');
    }
}
