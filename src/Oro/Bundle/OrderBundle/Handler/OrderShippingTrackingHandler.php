<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;

/**
 * Processes and persists shipping tracking information for orders.
 *
 * Handles the submission of shipping tracking form data, synchronizing the submitted tracking entries
 * with the order's existing tracking collection.
 * Removes tracking entries that are no longer present and adds new ones, then persists the changes to the database.
 */
class OrderShippingTrackingHandler
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->manager = $managerRegistry->getManagerForClass(Order::class);
    }

    /**
     * @throws AlreadySubmittedException
     * @throws \OutOfBoundsException
     */
    public function process(Order $order, FormInterface $form)
    {
        $formData = $form->get('shippingTrackings')->getData();
        if ($formData) {
            $this->handleOrderShippingTrackings($order, $formData);
            $this->manager->flush();
        }
    }

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
