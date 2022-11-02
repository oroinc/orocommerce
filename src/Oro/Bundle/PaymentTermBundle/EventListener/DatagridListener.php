<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;

/**
 * Adds appropriate payment term data to the grid.
 */
class DatagridListener
{
    /** @var PaymentTermAssociationProvider */
    private $paymentTermAssociationProvider;

    /** @var PaymentTermProviderInterface */
    private $paymentTermProvider;

    public function __construct(
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        PaymentTermProviderInterface $paymentTermProvider
    ) {
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->paymentTermProvider = $paymentTermProvider;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        $className = $config->getExtendedEntityClassName();
        if (!$className) {
            return;
        }

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames($className);
        if (!$associationNames) {
            return;
        }

        foreach ($associationNames as $associationName) {
            $config->offsetSetByPath(sprintf('[columns][%s][type]', $associationName), 'twig');
            $config->offsetSetByPath(sprintf('[columns][%s][frontend_type]', $associationName), 'html');
            $config->offsetSetByPath(
                sprintf('[columns][%s][template]', $associationName),
                '@OroPaymentTerm/PaymentTerm/column.html.twig'
            );
        }
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        $config = $event->getDatagrid()->getConfig();
        $className = $config->getExtendedEntityClassName();
        if (!$className) {
            return;
        }

        $associationNames = $this->paymentTermAssociationProvider->getAssociationNames($className);
        if (!$associationNames) {
            return;
        }

        foreach ($event->getRecords() as $record) {
            if (!$record instanceof ResultRecord) {
                return;
            }

            $entity = $record->getRootEntity();
            if (!$entity instanceof CustomerOwnerAwareInterface) {
                return;
            }

            $customerGroupPaymentTerm = $this->paymentTermProvider->getCustomerGroupPaymentTermByOwner($entity);
            if ($customerGroupPaymentTerm) {
                $record->setValue('customer_group_payment_term', $customerGroupPaymentTerm->getLabel());
            }
        }
    }
}
