<?php

namespace Oro\Bundle\PromotionBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedDiscountCollectionTableType;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\PromotionBundle\Provider\DiscountsProvider;

class OrderTypeExtension extends AbstractTypeExtension
{
    const ON_SUBMIT_LISTENER_PRIORITY = 10;

    /**
     * @var AppliedDiscountManager
     */
    protected $appliedDiscountManager;

    /**
     * @var DiscountsProvider
     */
    protected $discountsProvider;

    /**
     * @param AppliedDiscountManager $appliedDiscountManager
     * @param DiscountsProvider $discountsProvider
     */
    public function __construct(AppliedDiscountManager $appliedDiscountManager, DiscountsProvider $discountsProvider)
    {
        $this->appliedDiscountManager = $appliedDiscountManager;
        $this->discountsProvider = $discountsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('appliedCoupons', EntityIdentifierType::class, ['class' => Coupon::class]);
        $builder->add('appliedDiscounts', AppliedDiscountCollectionTableType::class);

        // Should be called before OrderBundle\Form\Type\EventListener\SubtotalSubscriber::onSubmitEventListener
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit'], self::ON_SUBMIT_LISTENER_PRIORITY);
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $order = $event->getData();
        if (!$order instanceof Order || !$order->getId()) {
            return;
        }

        $this->discountsProvider->enableRecalculation();
        $this->appliedDiscountManager->removeAppliedDiscountByOrder($order);
        $this->appliedDiscountManager->saveAppliedDiscounts($order);
    }

    /**
     * {@inheritDoc}
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }
}
