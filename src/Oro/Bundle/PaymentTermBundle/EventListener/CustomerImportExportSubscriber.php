<?php

namespace Oro\Bundle\PaymentTermBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerImportExportSubscriber implements EventSubscriberInterface
{
    /**
     * @var PaymentTermAssociationProvider
     */
    private $associationProvider;

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

                if (!$customer instanceof Customer) {
                    continue;
                }

                $this->associationProvider->setPaymentTerm($customer, (new PaymentTerm())->setLabel('net 90'));
            }
        }
    }
}
