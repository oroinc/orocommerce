<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
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

    private const BATCH_SIZE = 20;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    private $nameGenerator;

    /**
     * @var ExtendExtension
     */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();

        /** @var EntityManager $manager */
        $connection = $manager->getConnection();

        $metaTitleProductIds = $this->getAffectedProductIds($connection, 'metaTitles');
        $metaDescriptionProductIds = $this->getAffectedProductIds($connection, 'metaDescriptions');
        $metaKeywordProductIds = $this->getAffectedProductIds($connection, 'metaKeywords');

        $productIds = array_unique(
            array_merge($metaTitleProductIds, $metaDescriptionProductIds, $metaKeywordProductIds)
        );

        $duplicateListener = $this->container->get('oro_seo.event_listener.product_duplicate');

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

    private function getAffectedProductIds(Connection $connection, string $associationName): array
    {
        $tableName = $this->getAssociationTableName($associationName);

        if ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $query = sprintf(
                <<<SQL
SELECT DISTINCT STRING_AGG(CAST(relation.product_id AS TEXT), '-') AS ids
FROM %s relation
GROUP BY relation.localizedfallbackvalue_id
HAVING COUNT(relation.localizedfallbackvalue_id) > 1;
SQL,
                $tableName
            );

            $productIds = $this->prepareProductIds($connection, $query, '-');
        } else {
            $query = sprintf(
                <<<SQL
SELECT DISTINCT GROUP_CONCAT(CAST(relation.product_id AS CHAR), '') AS ids
FROM %s relation
GROUP BY relation.localizedfallbackvalue_id
HAVING COUNT(relation.localizedfallbackvalue_id) > 1;
SQL,
                $tableName
            );

            $productIds = $this->prepareProductIds($connection, $query, ',');
        }

        return $productIds;
    }

    private function prepareProductIds(Connection $connection, string $query, string $delimiter): array
    {
        $queryResult = $connection->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);

        $productIds = [];
        if ($queryResult) {
            $result = array_column($queryResult, 'ids');

            foreach ($result as $item) {
                $productIds = array_merge($productIds, explode($delimiter, $item));
            }
        }

        return $productIds;
    }

    private function getAssociationTableName(string $associationName): string
    {
        return $this->nameGenerator->generateManyToManyJoinTableName(
            Product::class,
            $associationName,
            LocalizedFallbackValue::class
        );
    }
}
