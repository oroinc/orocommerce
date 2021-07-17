<?php

namespace Oro\Bundle\PromotionBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponCollectionType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionCollectionTableType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OrderTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('appliedPromotions', AppliedPromotionCollectionTableType::class);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
    }

    public function postSetData(FormEvent $event)
    {
        $event->getForm()->add('appliedCoupons', AppliedCouponCollectionType::class, ['entity' => $event->getData()]);
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
