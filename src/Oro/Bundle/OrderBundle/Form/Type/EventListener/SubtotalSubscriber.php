<?php

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Handler\OrderLineItemCurrencyHandler;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Calculates prices for order line items and totals for an order
 * and adds a validator to "discountsSum" form field to disallow submitting an order
 * when the sum of all discounts is exceeded the order grand total amount.
 */
class SubtotalSubscriber implements EventSubscriberInterface
{
    /** @var TotalHelper  */
    protected $totalHelper;

    /** @var PriceMatcher */
    protected $priceMatcher;

    /** @var OrderLineItemCurrencyHandler */
    protected $orderLineItemCurrencyHandler;

    public function __construct(
        TotalHelper $totalHelper,
        PriceMatcher $priceMatcher,
        OrderLineItemCurrencyHandler $orderLineItemCurrencyHandler
    ) {
        $this->totalHelper = $totalHelper;
        $this->priceMatcher = $priceMatcher;
        $this->orderLineItemCurrencyHandler = $orderLineItemCurrencyHandler;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetDataEventListener',
            FormEvents::SUBMIT => 'onSubmitEventListener',
        ];
    }

    public function onPreSetDataEventListener(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data instanceof Order) {
            $this->fillTotals($form, $data);
        }
    }

    private function fillTotals(FormInterface $form, Order $order): void
    {
        $serializedData = $order->getSerializedData();
        // As the order currency may change, need to reset all prices and recalculate it.
        $this->resetLineItems($form, $order);
        $this->priceMatcher->addMatchingPrices($order);
        $originalTotal = $order->getTotalObject();

        $this->totalHelper->fill($order);

        if (
            isset($serializedData['precalculatedTotal'])
            && $order->getTotalObject()->getValue() === (float)$serializedData['precalculatedTotal']
        ) {
            $order->setTotalObject($originalTotal);
        }
    }

    public function onSubmitEventListener(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data instanceof Order) {
            $serializedData = $data->getSerializedData();
            // As the order currency may change, need to reset all prices and recalculate it.
            $this->resetLineItems($form, $data);
            $this->priceMatcher->addMatchingPrices($data);
            $originalTotal = $data->getTotalObject();

            $this->totalHelper->fill($data);

            if (
                isset($serializedData['precalculatedTotal'])
                && $data->getTotalObject()->getValue() === (float)$serializedData['precalculatedTotal']
            ) {
                $data->setTotalObject($originalTotal);
            }

            $event->setData($data);

            if ($form->has('discountsSum')) {
                $form->remove('discountsSum');
                $form->add(
                    'discountsSum',
                    HiddenType::class,
                    [
                        'mapped' => false,
                        'constraints' => [new Range(
                            notInRangeMessage: 'oro.order.discounts.sum.error.not_in_range.label',
                            min: PHP_INT_MAX * (-1),
                            max: $data->getSubtotal()
                        )]
                    ]
                );
                //submit with new max range value for correct validation
                $form->get('discountsSum')->submit($data->getTotalDiscounts()->getValue());
            }
        }
    }

    private function resetLineItems(FormInterface $form, Order $order): void
    {
        if ($form->has('lineItems')) {
            $this->orderLineItemCurrencyHandler->resetLineItemsPrices($form->get('lineItems'), $order);
        }
    }
}
