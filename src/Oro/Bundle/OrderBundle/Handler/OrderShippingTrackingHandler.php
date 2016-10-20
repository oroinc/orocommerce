<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;

class OrderShippingTrackingHandler
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->manager = $managerRegistry->getManagerForClass(Order::class);
    }

    /**
     * @param Order $order
     * @param FormInterface $form
     * @throws AlreadySubmittedException
     * @throws \OutOfBoundsException
     * @return bool
     */
    public function process(Order $order, FormInterface $form)
    {
        $formData = $form->get('shippingTrackings')->getData();
        if ($formData) {
            $this->handleOrderShippingTrackings($order, $formData);
            $this->manager->flush();
        }
    }

    /**
     * @param Order $order
     * @param $trackings OrderShippingTracking[]|ArrayCollection
     */
    protected function handleOrderShippingTrackings(Order $order, $trackings)
    {
        $old_trackings = $order->getShippingTrackings()->toArray();

        foreach ($trackings as $tracking) {
            $order->addShippingTracking($tracking);
        }

        foreach ($old_trackings as $removed_tracking) {
            if (!in_array($removed_tracking, $trackings->toArray(), false)) {
                $order->removeShippingTracking($removed_tracking);
            }
        }
        $this->manager->persist($order);
    }
}
