<?php

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\Range;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Total\TotalHelper;

class SubtotalSubscriber implements EventSubscriberInterface
{
    /** @var TotalHelper  */
    protected $totalHelper;

    /** @var PriceMatcher */
    protected $priceMatcher;

    /**
     * @param TotalHelper $totalHelper
     * @param PriceMatcher $priceMatcher
     */
    public function __construct(TotalHelper $totalHelper, PriceMatcher $priceMatcher)
    {
        $this->totalHelper = $totalHelper;
        $this->priceMatcher = $priceMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'onSubmitEventListener',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmitEventListener(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data instanceof Order) {
            $this->priceMatcher->addMatchingPrices($data);
            $this->totalHelper->fillSubtotals($data);
            $this->totalHelper->fillDiscounts($data);
            $this->totalHelper->fillTotal($data);
            $event->setData($data);
            
            if ($form->has('discountsSum')) {
                $form->remove('discountsSum');
                $form->add(
                    'discountsSum',
                    'hidden',
                    [
                        'mapped' => false,
                        'constraints' => [new Range(
                            [
                                'min' => PHP_INT_MAX * (-1), //use some big negative number
                                'max' => $data->getSubtotal(),
                                'maxMessage' => 'oro.order.discounts.sum.error.label'
                            ]
                        )]
                    ]
                );
                //submit with new max range value for correct validation
                $form->get('discountsSum')->submit($data->getTotalDiscounts()->getValue());
            }
        }
    }
}
