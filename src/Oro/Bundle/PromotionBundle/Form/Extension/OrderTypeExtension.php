<?php

namespace Oro\Bundle\PromotionBundle\Form\Extension;

use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponCollectionType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionCollectionTableType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Extends order forms with applied promotions and coupons fields.
 */
class OrderTypeExtension extends AbstractTypeExtension
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('appliedPromotions', AppliedPromotionCollectionTableType::class);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
    }

    public function postSetData(FormEvent $event)
    {
        $event->getForm()->add('appliedCoupons', AppliedCouponCollectionType::class, ['entity' => $event->getData()]);
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
