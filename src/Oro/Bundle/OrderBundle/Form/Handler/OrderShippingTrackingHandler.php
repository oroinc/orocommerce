<?php

namespace Oro\Bundle\OrderBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class OrderShippingTrackingHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @param FormInterface $form
     * @param ObjectManager $manager
     * @param Request $request
     * @param Order $order
     */
    public function __construct(
        FormInterface $form,
        ObjectManager $manager,
        Request $request,
        Order $order
    ) {
        $this->form = $form;
        $this->manager = $manager;
        $this->request = $request;
        $this->order = $order;
    }

    /**
     * @throws AlreadySubmittedException
     * @return bool
     */
    public function process()
    {
        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $formData = $this->form->getData();

                if ($formData && count($formData)) {
                    $this->handleOrderShippingTrackings($formData);
                    $this->manager->flush();
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param $trackings OrderShippingTracking[]|ArrayCollection
     */
    protected function handleOrderShippingTrackings($trackings)
    {
        $old_trackings = $this->order->getShippingTrackings()->toArray();

        foreach ($trackings as $tracking) {
            $this->order->addShippingTracking($tracking);
        }

        foreach ($old_trackings as $removed_tracking) {
            if (!in_array($removed_tracking, $trackings->toArray())) {
                $this->order->removeShippingTracking($removed_tracking);
            }
        }
        $this->manager->persist($this->order);
    }
}
