<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;

/**
 * Preload fallback values for product page template field.
 */
final class UpdateProductPageTemplateFallbackData extends AbstractFixture
{
    private const int BATCH_SIZE = 100;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(Product::class);
        $qb = $repo->createQueryBuilder('p');
        $qb->where('p.pageTemplate IS NULL');
        $query = $qb->getQuery();

        $iterator = new BufferedQueryResultIterator($query);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $counter = 0;
        foreach ($iterator as $product) {
            $fallback = new EntityFieldFallbackValue();
            $fallback->setFallback(ThemeConfigurationFallbackProvider::FALLBACK_ID);
            $product->setPageTemplate($fallback);
            $manager->persist($product);
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
}
