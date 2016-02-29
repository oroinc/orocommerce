<?php

namespace OroB2B\Bundle\OrderBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

use OroB2B\Bundle\OrderBundle\Provider\SubtotalProvider;
use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderHandler
{
    /**
     * @var FormInterface
     */
    protected $form;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var ObjectManager
     */
    protected $manager;
    /**
     * @var SubtotalProvider
     */
    protected $subtotalProvider;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param SubtotalProvider $subtotalProvider
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        SubtotalProvider $subtotalProvider
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->subtotalProvider = $subtotalProvider;
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
        $subtotal = $this->subtotalProvider->getSubtotal($order);

        if ($subtotal) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            try {
                $propertyAccessor->setValue($order, $subtotal->getType(), $subtotal->getAmount());
            } catch (NoSuchPropertyException $e) {
            }
        }
// todo: refactor to use total BB-2093
//        $subtotals = $this->subtotalsProvider->getSubtotals($order);
//
//        $propertyAccessor = PropertyAccess::createPropertyAccessor();
//        foreach ($subtotals as $subtotal) {
//            try {
//                $propertyAccessor->setValue($order, $subtotal->getType(), $subtotal->getAmount());
//            } catch (NoSuchPropertyException $e) {
//            }
//        }
    }
}
