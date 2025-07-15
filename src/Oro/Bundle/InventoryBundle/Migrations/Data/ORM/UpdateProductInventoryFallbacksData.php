<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Preload fallback values for product inventory fields.
 */
final class UpdateProductInventoryFallbacksData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const int BATCH_SIZE = 100;
    private const array FIELDS = [
        'manageInventory',
        'highlightLowInventory',
        'inventoryThreshold',
        'lowInventoryThreshold',
        'backOrder',
        'decrementQuantity',
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
        UpcomingProductProvider::IS_UPCOMING,
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $propertyAccessor = $this->container->get('property_accessor');
        $repo = $manager->getRepository(Product::class);
        $qb = $repo->createQueryBuilder('p');
        $query = $qb->getQuery();

        $iterator = new BufferedQueryResultIterator($query);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $counter = 0;
        foreach ($iterator as $product) {
            $needsUpdate = false;
            foreach (self::FIELDS as $field) {
                if (null === $propertyAccessor->getValue($product, $field)) {
                    $fallback = new EntityFieldFallbackValue();
                    $fallback->setFallback(CategoryFallbackProvider::FALLBACK_ID);
                    $propertyAccessor->setValue($product, $field, $fallback);
                    $needsUpdate = true;
                }
            }
            if ($needsUpdate) {
                $manager->persist($product);
                $counter++;
                if (($counter % self::BATCH_SIZE) === 0) {
                    $manager->flush();
                    $manager->clear();
                    $counter = 0;
                }
            }
        }
        if ($counter > 0) {
            $manager->flush();
            $manager->clear();
        }
    }
}
