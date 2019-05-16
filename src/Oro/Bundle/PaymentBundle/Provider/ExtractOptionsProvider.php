<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides additional information for payment systems like line item models or shipping address model
 */
class ExtractOptionsProvider
{
    public const CONTEXT_PAYMENT_METHOD_TYPE = 'payment_method_type';

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
        return $this->getLineItemPaymentOptionsWithContext($entity, []);
    }

    /**
     * @param LineItemsAwareInterface $entity
     * @param array $context
     * @return LineItemOptionModel[]
     */
    public function getLineItemPaymentOptionsWithContext(LineItemsAwareInterface $entity, array $context): array
    {
        $event = new ExtractLineItemPaymentOptionsEvent($entity);
        $event->setContext($context);
        $this->dispatcher->dispatch(ExtractLineItemPaymentOptionsEvent::NAME, $event);

        return $event->getModels();
    }
}
