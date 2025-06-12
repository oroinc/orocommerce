<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Recalculate order total price and save it in serialized data if it's not correct calculated.
 */
class RecalculateOrderTotal extends AbstractFixture implements VersionedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const BATCH_SIZE = 100;

    #[\Override]
    public function getVersion(): string
    {
        return '1.0';
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $orders = $this->getOrderIterator($manager);
        $counter = 0;
        /** @var Order $order */
        foreach ($orders as $order) {
            $recalculatedOrderTotal = $this->getTotalHelper()->calculateTotal($order);

            if ((float)$order->getTotalObject()->getValue() === (float)$recalculatedOrderTotal->getValue()) {
                continue;
            }

            $serializedData = $order->getSerializedData();
            $serializedData['totals'] = [
                'not_relevant_price' => true,
                'total' => (float)$recalculatedOrderTotal->getValue(),
            ];

            $order->setSerializedData($serializedData);
            $manager->persist($order);
            $counter++;
            if (($counter % self::BATCH_SIZE) === 0) {
                $manager->flush();
                $manager->clear();
                $counter = 0;
            }
        }

        if ($counter > 0) {
            $manager->flush();
            $manager->clear();
        }
    }

    private function getOrderIterator(
        ObjectManager $manager,
    ): BufferedQueryResultIterator {
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(Order::class);
        $qb = $repository->createQueryBuilder('o');

        $iterator = new BufferedQueryResultIterator($qb->getQuery());
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }

    private function getTotalHelper(): TotalHelper
    {
        return $this->container
            ->get('oro_order.order.total.total_helper');
    }
}
