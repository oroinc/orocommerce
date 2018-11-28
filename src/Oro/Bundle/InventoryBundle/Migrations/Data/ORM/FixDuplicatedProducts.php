<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class FixDuplicatedProducts extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(Product::class);
        $qb = $repository->createQueryBuilder('p');
        $qb->select('p');
        $qb->where($qb->expr()->orX(
            $qb->expr()->in('p.manageInventory', $this->getSubQuery($manager, 'manageInventory')),
            $qb->expr()->in('p.highlightLowInventory', $this->getSubQuery($manager, 'highlightLowInventory')),
            $qb->expr()->in('p.inventoryThreshold', $this->getSubQuery($manager, 'inventoryThreshold')),
            $qb->expr()->in('p.lowInventoryThreshold', $this->getSubQuery($manager, 'lowInventoryThreshold')),
            $qb->expr()->in('p.minimumQuantityToOrder', $this->getSubQuery($manager, 'minimumQuantityToOrder')),
            $qb->expr()->in('p.maximumQuantityToOrder', $this->getSubQuery($manager, 'maximumQuantityToOrder')),
            $qb->expr()->in('p.decrementQuantity', $this->getSubQuery($manager, 'decrementQuantity')),
            $qb->expr()->in('p.backOrder', $this->getSubQuery($manager, 'backOrder')),
            $qb->expr()->in('p.isUpcoming', $this->getSubQuery($manager, 'isUpcoming'))
        ));
        $qb->orderBy('p.id');
        $qb->groupBy('p.id');
        $qb->setParameter('count', 1);

        /** @var Product[] $products */
        $products = new BufferedQueryResultIterator($qb->getQuery());

        $duplicateListener = $this->container->get('oro_inventory.event_listener.product_duplicate');
        foreach ($products as $product) {
            $event = new ProductDuplicateAfterEvent($product, $product);
            $duplicateListener->onDuplicateAfter($event);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param string $key
     *
     * @return string
     */
    private function getSubQuery(ObjectManager $manager, $key)
    {
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(Product::class);
        $qb = $repository->createQueryBuilder(sprintf('sub_%s_p', $key));
        $qb->select(sprintf('sub_%s_f.id', $key));
        $qb->leftJoin(sprintf('sub_%s_p.%s', $key, $key), sprintf('sub_%s_f', $key));
        $qb->orderBy(sprintf('sub_%s_f.id', $key));
        $qb->groupBy(sprintf('sub_%s_f.id', $key));
        $qb->having($qb->expr()->gt($qb->expr()->count(sprintf('sub_%s_f.id', $key)), ':count'));

        return $qb->getDQL();
    }
}
