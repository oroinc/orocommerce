<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class ExtractOptionsProvider
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var EntityAliasResolver */
    protected $aliasResolver;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param EntityAliasResolver $aliasResolver
     */
    public function __construct(EventDispatcherInterface $dispatcher, EntityAliasResolver $aliasResolver)
    {
        $this->dispatcher = $dispatcher;
        $this->aliasResolver = $aliasResolver;
    }

    /**
     * @param string $classname
     * @param AbstractAddress $entity
     * @return AddressOptionModel
     */
    public function getShippingAddressOptions($classname, AbstractAddress $entity)
    {
        $event = new ExtractAddressOptionsEvent($entity);
        $entityAlias = $this->aliasResolver->getAlias($classname);
        $this->dispatcher->dispatch(
            sprintf('%s.%s', ExtractAddressOptionsEvent::NAME, $entityAlias),
            $event
        );

        return $event->getModel();
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @return LineItemOptionModel[]
     */
    public function getLineItemPaymentOptions(LineItemsAwareInterface $entity)
    {
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $this->dispatcher->dispatch(ExtractLineItemPaymentOptionsEvent::NAME, $event);

        return $event->getModels();
    }
}
