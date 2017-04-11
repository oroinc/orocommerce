<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ProductBundle\Entity\Product;

class DisableWrongConfigurableProducts extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->disableWithDifferentAttributeFamilies($manager);

        $qb = $this->createQueryBuilder($manager);
        $qb->select(['c_product.id', 'c_product.sku', 'n.string AS name', 'c_product.type']);
        $qb->leftJoin('c_product.names', 'n');
        $qb->where($qb->expr()->isNull('n.localization'));

        $result = $qb->getQuery()->getArrayResult();
        if ($result) {
            $file = fopen($this->container->get('kernel')->getLogDir() . '/disabled_products.log', 'w');
            fwrite($file, implode(', ', ['id', 'sku', 'name', 'type']) . PHP_EOL);
            foreach ($result as $data) {
                fwrite($file, implode(', ', $data) . PHP_EOL);
            }
            fclose($file);
        }
    }

    /**
     * @param EntityManagerInterface|ObjectManager $manager
     */
    private function disableWithDifferentAttributeFamilies(EntityManagerInterface $manager)
    {
        $qb = $this->createQueryBuilder($manager);
        $qb->select('c_product.id');
        $qb->groupBy('c_product.id');

        $query = sprintf(
            'UPDATE %s SET status = \'%s\' WHERE id IN (SELECT * FROM (%s) AS product)',
            $manager->getClassMetadata(Product::class)->getTableName(),
            Product::STATUS_DISABLED,
            $qb->getQuery()->getSQL()
        );

        $connection = $manager->getConnection();
        try {
            $connection->beginTransaction();
            $connection->executeUpdate($query);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
        }
    }

    /**
     * @param ObjectManager $manager
     *
     * @return QueryBuilder
     */
    private function createQueryBuilder(ObjectManager $manager)
    {
        $repository = $manager->getRepository(Product::class);

        $qb = $repository->createQueryBuilder('c_product');
        $qb->innerJoin('c_product.variantLinks', 'vl');
        $qb->innerJoin('vl.product', 's_product');
        $qb->where(sprintf('c_product.type = \'%s\'', Product::TYPE_CONFIGURABLE));
        $qb->andWhere(sprintf('s_product.type = \'%s\'', Product::TYPE_SIMPLE));
        $qb->andWhere($qb->expr()->neq('c_product.attributeFamily', 's_product.attributeFamily'));

        return $qb;
    }
}
