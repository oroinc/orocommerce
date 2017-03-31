<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;

class CustomerImportExportSubscriber implements EventSubscriberInterface
{
    /**
     * @var PaymentTermAssociationProvider
     */
    private $associationProvider;

    /**
     * @param PaymentTermAssociationProvider $associationProvider
     */
    public function __construct(PaymentTermAssociationProvider $associationProvider)
    {
        $this->associationProvider = $associationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::AFTER_LOAD_TEMPLATE_FIXTURES => 'addPaymentTermToCustomers',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function addPaymentTermToCustomers(LoadTemplateFixturesEvent $event)
    {
        foreach ($event->getEntities() as $customerData) {
            foreach ($customerData as $customer) {
                /** @var Customer $customer */
                $customer = $customer['entity'];

                $this->associationProvider->setPaymentTerm($customer, (new PaymentTerm())->setLabel('net 90'));
            }
        }
    }
}
