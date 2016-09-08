<?php

namespace Oro\Bundle\PayPalBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @param object $entity
     * @param array $keys
     * @return array
     */
    public function getShippingAddressOptions($classname, $entity, array $keys)
    {
        $event = new ExtractAddressOptionsEvent($entity, $keys);
        $entityAlias = $this->aliasResolver->getAlias($classname);
        $this->dispatcher->dispatch(
            sprintf('%s.%s', ExtractAddressOptionsEvent::NAME, $entityAlias),
            $event
        );

        return $event->getOptions();
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @param array $keys
     * @return array
     */
    public function getLineItemPaymentOptions(LineItemsAwareInterface $entity, array $keys)
    {
        $event = new ExtractLineItemPaymentOptionsEvent($entity, $keys);
        $this->dispatcher->dispatch(ExtractLineItemPaymentOptionsEvent::NAME, $event);

        return $event->getOptions();
    }
}
