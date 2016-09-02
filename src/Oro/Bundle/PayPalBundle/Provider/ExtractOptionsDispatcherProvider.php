<?php

namespace Oro\Bundle\PayPalBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Event\ExtractShippingAddressOptionsEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExtractOptionsDispatcherProvider
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var EntityAliasProviderInterface */
    protected $aliasProvider;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param EntityAliasProviderInterface $aliasProvider
     */
    public function __construct(EventDispatcherInterface $dispatcher, EntityAliasProviderInterface $aliasProvider)
    {
        $this->dispatcher = $dispatcher;
        $this->aliasProvider = $aliasProvider;
    }

    /**
     * @param string $classname
     * @param object $entity
     * @param array $keys
     * @return array
     */
    public function getShippingAddressOptions($classname, $entity, array $keys)
    {
        $event = new ExtractShippingAddressOptionsEvent($entity, $keys);
        $entityAlias = $this->aliasProvider->getEntityAlias($classname);
        $this->dispatcher->dispatch(
            sprintf('%s.%s', ExtractShippingAddressOptionsEvent::NAME, $entityAlias->getAlias()),
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
