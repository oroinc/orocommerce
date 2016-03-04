<?php

namespace OroB2B\Bundle\OrderBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use OroB2B\Bundle\OrderBundle\SubtotalProcessor\TotalProcessorProvider;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalLineItemProvider;
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

    /** @var SubtotalLineItemProvider */
    protected $subTotalLineItemProvider;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param TotalProcessorProvider $totalProvider
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        TotalProcessorProvider $totalProvider,
        SubtotalLineItemProvider $subTotalLineItemProvider
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->totalProvider = $totalProvider;
        $this->subTotalLineItemProvider = $subTotalLineItemProvider;
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
        $subtotal = $this->subTotalLineItemProvider->getSubtotal($order);
        $total = $this->totalProvider->getTotal($order);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if ($subtotal) {
            try {
                $propertyAccessor->setValue($order, $subtotal->getType(), $subtotal->getAmount());
            } catch (NoSuchPropertyException $e) {
            }
        }

        if ($total) {
            try {
                $propertyAccessor->setValue($order, $total->getType(), $total->getAmount());
            } catch (NoSuchPropertyException $e) {
            }
        }
    }
}
