<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Symfony\Component\Form\FormInterface;

/**
 * This class adds to the events blocks, details about address blocks taking into account submission.
 */
class OrderAddressEventListener extends AbstractFormEventListener
{
    private OrderAddressManager $addressManager;

    public function setAddressManager(OrderAddressManager $addressManager): void
    {
        $this->addressManager = $addressManager;
    }

    public function onOrderEvent(OrderEvent $event)
    {
        if (null === $event->getSubmittedData()) {
            return;
        }

        $orderForm = $event->getForm();
        foreach ([AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING] as $type) {
            $fieldName = sprintf('%sAddress', $type);
            if ($orderForm->has($fieldName)) {
                $form = $this->createFieldWithSubmission($orderForm, $fieldName, $event->getSubmittedData());
                $formField = $form->get($fieldName);
                $formView = $formField->createView();
                $view = $this->renderForm(
                    $formView,
                    '@OroOrder/Form/customerAddressSelector.html.twig'
                );
                $event->getData()->offsetSet($fieldName, $view);
                if (($formView->vars['value'] ?? null) === null) {
                    $this->setOrderDefaultAddress($event, $fieldName, $formField);
                }
            }
        }
    }

    /**
     * Fill order addresses with default values if empty to get dependent totals, taxes and discounts
     * calculated correctly. Otherwise, form will be rendered with address pre-filled by default address
     * but dependent data will be incorrect.
     */
    private function setOrderDefaultAddress(
        OrderEvent $event,
        string $fieldName,
        FormInterface $formField
    ): void {
        $order = $event->getOrder();
        $getterMethod = 'get' . ucfirst($fieldName);
        $setterMethod = 'set' . ucfirst($fieldName);
        if ($order->{$getterMethod}()) {
            return;
        }

        /** @var TypedOrderAddressCollection $addresses */
        $addresses = $formField->get('customerAddress')->getConfig()->getOption('address_collection');
        $defaultAddress = $addresses->getDefaultAddress();
        if (!$defaultAddress) {
            return;
        }

        $orderAddress = $this->addressManager->updateFromAbstract($defaultAddress);
        if ($orderAddress) {
            $order->{$setterMethod}($orderAddress);
        }
    }
}
