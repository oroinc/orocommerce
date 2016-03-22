<?php

namespace OroB2B\Bundle\OrderBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\OrderTotalsHandler;

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

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param OrderTotalsHandler $orderTotalsHandler
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        OrderTotalsHandler $orderTotalsHandler
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->orderTotalsHandler = $orderTotalsHandler;
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
                $this->orderTotalsHandler->fillSubtotals($entity);

                $this->manager->persist($entity);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
