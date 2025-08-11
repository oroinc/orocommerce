<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

/**
 * Adds order paymentStatus field to the search index.
 */
class OrderPaymentStatusSearchListener
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private PaymentStatusLabelFormatter $paymentStatusLabelFormatter
    ) {
    }

    public function collectEntityMapEvent(SearchMappingCollectEvent $event): void
    {
        $mapConfig = $event->getMappingConfig();
        $mapConfig[Order::class]['fields'][] = [
            'name' => 'paymentStatus',
            'target_type' => 'text',
            'target_fields' => ['paymentStatus'],
        ];
        $event->setMappingConfig($mapConfig);
    }

    public function prepareEntityMapEvent(PrepareEntityMapEvent $event): void
    {
        $className = $event->getClassName();
        if ($className !== Order::class) {
            return;
        }

        /** @var $entity Order */
        $entity = $event->getEntity();

        $status = $this->doctrineHelper->getEntityRepository(PaymentStatus::class)
            ->createQueryBuilder('ps')
            ->where('ps.entityIdentifier = :orderId')
            ->andWhere('ps.entityClass = :entityType')
            ->setParameter('orderId', $entity->getId())
            ->setParameter('entityType', Order::class)
            ->getQuery()
            ->getOneOrNullResult();

        if ($status) {
            $data = $event->getData();
            $data['text']['paymentStatus'] = $this->paymentStatusLabelFormatter->formatPaymentStatusLabel(
                $status->getPaymentStatus()
            );

            $event->setData($data);
        }
    }
}
