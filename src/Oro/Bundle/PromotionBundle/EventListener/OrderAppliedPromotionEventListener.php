<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\AbstractFormEventListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

/**
 * Listener renders applied promotion collection form by given data on entry point call
 */
class OrderAppliedPromotionEventListener extends AbstractFormEventListener
{
    /**
     * @var AppliedPromotionManager
     */
    private $appliedPromotionManager;

    public function __construct(
        Environment $twig,
        FormFactoryInterface $formFactory,
        AppliedPromotionManager $appliedPromotionManager
    ) {
        parent::__construct($twig, $formFactory);

        $this->appliedPromotionManager = $appliedPromotionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $orderForm = $event->getForm();
        if ($orderForm->has('appliedPromotions') && $event->getSubmittedData()) {
            $this->appliedPromotionManager->createAppliedPromotions($event->getOrder());

            $form = $this->formFactory->create(
                \get_class($orderForm->getConfig()->getType()->getInnerType()),
                $event->getOrder()
            );

            $view = $this->renderForm(
                $form->createView(),
                '@OroPromotion/Order/applied_promotions.html.twig'
            );
            $event->getData()->offsetSet('appliedPromotions', $view);
        }
    }
}
