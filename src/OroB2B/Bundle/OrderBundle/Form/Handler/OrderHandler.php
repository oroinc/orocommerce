<?php

namespace OroB2B\Bundle\OrderBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var TotalProcessorProvider */
    protected $totalProvider;

    /** @var LineItemSubtotalProvider */
    protected $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider */
    protected $discountSubtotalProvider;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param TotalProcessorProvider $totalProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     * @param DiscountSubtotalProvider $discountSubtotalProvider
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        TotalProcessorProvider $totalProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider,
        DiscountSubtotalProvider $discountSubtotalProvider
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->totalProvider = $totalProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
        $this->discountSubtotalProvider = $discountSubtotalProvider;
    }

    /**
     * Process form
     *
     * @param Order $entity
     * @return bool True on successful processing, false otherwise
     */
    public function process(Order $entity)
    {
        $this->form->setData($entity);

        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $this->fillSubtotals($entity);

                $this->manager->persist($entity);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * @param Order $order
     */
    protected function fillSubtotals(Order $order)
    {
        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($order);
        $discountSubtotals = $this->discountSubtotalProvider->getSubtotal($order);
        $total = $this->totalProvider->getTotal($order);

        if ($subtotal) {
            $order->setSubtotal($subtotal->getAmount());
        }
        if (count($discountSubtotals) > 0) {
            $discountSubtotalAmount = new Price();
            foreach ($discountSubtotals as $discount) {
                $newAmount = $discount->getAmount() + (float) $discountSubtotalAmount->getValue();
                $discountSubtotalAmount->setValue($newAmount);
            }
            $order->setTotalDiscounts($discountSubtotalAmount);
        }
        if ($total) {
            $order->setTotal($total->getAmount());
        }
    }
}
