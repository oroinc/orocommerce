<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Disables configurable products which have extend fields as variants selected or simple products from other families
 */
class DisableWrongConfigurableProducts extends AbstractFixture implements ContainerAwareInterface
{
    const BATCH_SIZE = 200;

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
        $hasResult = false;
        $filepath = $this->container->get('kernel')->getLogDir() . '/disabled_products.log';

        $file = fopen($filepath, 'w');
        fwrite($file, implode(', ', ['id', 'sku', 'name', 'type']) . PHP_EOL);

        $qb = $this->createQueryBuilder($manager);
        $qb->select(['c_product.id', 'c_product.sku', 'n.string AS name', 'c_product.type']);
        $qb->leftJoin('c_product.names', 'n');
        $qb->andWhere($qb->expr()->isNull('n.localization'));

        $result = $qb->getQuery()->getArrayResult();
        foreach ($result as $data) {
            $hasResult = true;
            fwrite($file, sprintf('"%s"', implode('", "', $data)) . PHP_EOL);
        }

        $this->disableWithDifferentAttributeFamilies($manager);

        $disableProductIds = [];

        $provider = $this->container->get('oro_product.provider.variant_field_provider');
        foreach ($this->getAttributeFamilyIterator($manager) as $attributeFamily) {
            $variantFields = $provider->getVariantFields($attributeFamily);

            foreach ($this->getProductsByAttributeFamilyIterator($manager, $attributeFamily) as $productData) {
                foreach ($productData['variantFields'] as $variantFieldName) {
                    if (!array_key_exists($variantFieldName, $variantFields)) {
                        $hasResult = true;
                        unset($productData['variantFields']);
                        fwrite($file, sprintf('"%s"', implode('", "', $productData)) . PHP_EOL);

                        $disableProductIds[$productData['id']] = $productData['id'];
                        if (count($disableProductIds) >= self::BATCH_SIZE) {
                            $this->disableProductsByIds($manager, $disableProductIds);
                            $disableProductIds = [];
                        }
                        break;
                    }
                }
            }
        }

        if (count($disableProductIds) > 0) {
            $this->disableProductsByIds($manager, $disableProductIds);
        }

        fclose($file);

        if (!$hasResult) {
            unlink($filepath);
        }
    }

    /**
     * @param EntityManagerInterface|ObjectManager $manager
     *
     * @throws \Exception
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
            $connection->executeStatement($query);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
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
        $qb->andWhere(sprintf('c_product.status = \'%s\'', Product::STATUS_ENABLED));
        $qb->andWhere($qb->expr()->neq('c_product.attributeFamily', 's_product.attributeFamily'));

        return $qb;
    }

    /**
     * @param ObjectManager|EntityManagerInterface $manager
     *
     * @return BufferedQueryResultIterator
     */
    private function getAttributeFamilyIterator(EntityManagerInterface $manager)
    {
        $repository = $manager->getRepository(AttributeFamily::class);
        $qb = $repository->createQueryBuilder('af');

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param ObjectManager|EntityManagerInterface $manager
     * @param AttributeFamily $attributeFamily
     *
     * @return BufferedQueryResultIterator
     */
    private function getProductsByAttributeFamilyIterator(
        EntityManagerInterface $manager,
        AttributeFamily $attributeFamily
    ) {
        $repository = $manager->getRepository(Product::class);
        $qb = $repository->createQueryBuilder('p');
        $qb->select(['p.id', 'p.sku', 'n.string AS name', 'p.type', 'p.variantFields']);
        $qb->leftJoin('p.names', 'n');
        $qb->where($qb->expr()->eq('p.type', ':type'));
        $qb->andWhere($qb->expr()->eq('p.status', ':status'));
        $qb->andWhere($qb->expr()->eq('p.attributeFamily', ':attributeFamily'));
        $qb->andWhere($qb->expr()->isNull('n.localization'));
        $qb->setParameters([
            'type' => Product::TYPE_CONFIGURABLE,
            'status' => Product::STATUS_ENABLED,
            'attributeFamily' => $attributeFamily,
        ]);

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param EntityManagerInterface|ObjectManager $manager
     * @param array $productIds
     *
     * @throws \Exception
     */
    private function disableProductsByIds(EntityManagerInterface $manager, array $productIds)
    {
        $query = sprintf(
            'UPDATE %s SET status = \'%s\' WHERE id IN (%s)',
            $manager->getClassMetadata(Product::class)->getTableName(),
            Product::STATUS_DISABLED,
            implode(', ', $productIds)
        );

        $connection = $manager->getConnection();
        try {
            $connection->beginTransaction();
            $connection->executeStatement($query);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
