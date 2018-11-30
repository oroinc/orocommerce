<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Fix meta fields for already duplicated products
 */
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
        $qb->leftJoin('p.metaTitles', 'pmt');
        $qb->leftJoin('p.metaDescriptions', 'pmd');
        $qb->leftJoin('p.metaKeywords', 'pmk');
        $qb->where($qb->expr()->orX(
            $qb->expr()->in('pmt.id', $this->getSubQuery($manager, 'metaTitles')),
            $qb->expr()->in('pmd.id', $this->getSubQuery($manager, 'metaDescriptions')),
            $qb->expr()->in('pmk.id', $this->getSubQuery($manager, 'metaKeywords'))
        ));
        $qb->orderBy('p.id');
        $qb->groupBy('p.id');
        $qb->setParameter('count', 1);

        /** @var Product[] $products */
        $products = new BufferedQueryResultIterator($qb->getQuery());

        $duplicateListener = $this->container->get('oro_seo.event_listener.product_duplicate');
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
        $qb->select(sprintf('sub_%s_flv.id', $key));
        $qb->leftJoin(sprintf('sub_%s_p.%s', $key, $key), sprintf('sub_%s_flv', $key));
        $qb->orderBy(sprintf('sub_%s_flv.id', $key));
        $qb->groupBy(sprintf('sub_%s_flv.id', $key));
        $qb->having($qb->expr()->gt($qb->expr()->count(sprintf('sub_%s_flv.id', $key)), ':count'));

        return $qb->getDQL();
    }
}
