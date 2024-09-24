<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_33;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;

class UpdateProductKitImportExportConfigFields implements Migration
{
    private static array $productConfigFields = [
        'kitItems' => [
            'excluded' => false,
            'immutable' => true,
            'full' => true,
            'process_as_scalar' => true,
        ]
    ];

    private static array $productKitConfigFields = [
        'id' => ['immutable' => true],
        'labels' => ['immutable' => true, 'full' => true, 'fallback_field' => 'string'],
        'sortOrder' => ['immutable' => true],
        'productKit' => ['immutable' => true],
        'kitItemProducts' => ['immutable' => true, 'full' => true],
        'optional' => ['immutable' => true],
        'minimumQuantity' => ['immutable' => true],
        'maximumQuantity' => ['immutable' => true],
        'productUnit' => ['immutable' => true],
    ];

    private static array $productKitItemProductConfigFields = [
        'kitItem' => ['immutable' => true, 'identity' => true],
        'product' => ['immutable' => true, 'identity' => true],
    ];

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->updateConfigFields($queries, Product::class, self::$productConfigFields);
        $this->updateConfigFields($queries, ProductKitItem::class, self::$productKitConfigFields);
        $this->updateConfigFields($queries, ProductKitItemProduct::class, self::$productKitItemProductConfigFields);
    }

    private function updateConfigFields(QueryBag $queries, string $class, array $configFields): void
    {
        foreach ($configFields as $fieldName => $configField) {
            foreach ($configField as $code => $value) {
                $queries->addQuery(
                    new UpdateEntityConfigFieldValueQuery(
                        $class,
                        $fieldName,
                        'importexport',
                        $code,
                        $value
                    )
                );
            }
        }
    }
}
