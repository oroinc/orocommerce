<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\Range;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Total\TotalHelper;

class SubtotalSubscriber implements EventSubscriberInterface
{
    /** @var TotalHelper  */
    protected $totalHelper;

    /**
     * SubtotalSubscriber constructor.
     * @param TotalHelper $totalHelper
     */
    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
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
                                'maxMessage' => 'orob2b.order.discounts.sum.error.label'
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
