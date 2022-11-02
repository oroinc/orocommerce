<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Fix inventory fields for already duplicated products
 */
class FixDuplicatedProducts extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const BATCH_SIZE = 20;
    private const RELATIONS = [
        'manageInventory',
        'highlightLowInventory',
        'inventoryThreshold',
        'lowInventoryThreshold',
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
        'decrementQuantity',
        'backOrder',
        'isUpcoming'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $duplicateListener = $this->container->get('oro_inventory.event_listener.product_duplicate');

        $productIds = $this->getAffectedProductIds($manager);

        $counter = 0;
        foreach ($productIds as $productId) {
            /** @var Product $product */
            $product = $manager->getReference(Product::class, $productId);
            $event = new ProductDuplicateAfterEvent($product, $product);
            $duplicateListener->onDuplicateAfter($event);

            ++ $counter;

            if ($counter > self::BATCH_SIZE) {
                $manager->clear();
            }
        }
    }

    private function getAffectedProductIds(ObjectManager $manager): array
    {
        $productIds = [];

        foreach (self::RELATIONS as $relation) {
            $dql = $this->getDQL($relation);

            /** @var EntityManager $manager */
            $query = $manager->createQuery($dql);
            $queryResult = $query->getResult(AbstractQuery::HYDRATE_ARRAY);

            if ($queryResult) {
                $result = array_column($queryResult, 'ids');

                foreach ($result as $item) {
                    $productIds = array_merge($productIds, explode(',', $item));
                }
            }
        }

        return array_unique($productIds);
    }

    private function getDQL(string $relation)
    {
        $dql = sprintf(
            <<<DQL
SELECT DISTINCT GROUP_CONCAT(CAST(p.id AS TEXT), '') AS ids
FROM Oro\Bundle\ProductBundle\Entity\Product p
INNER JOIN Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue efv WITH p.%s = efv
GROUP BY efv.id
HAVING COUNT(efv.id) > 1
DQL,
            $relation
        );

        return $dql;
    }
}
