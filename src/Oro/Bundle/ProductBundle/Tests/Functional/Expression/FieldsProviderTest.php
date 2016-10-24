<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Expression;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Expression\FieldsProviderInterface;

/**
 * @dbIsolation
 */
class FieldsProviderTest extends WebTestCase
{
    /**
     * @var FieldsProviderInterface
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);
        $this->provider = $this->getContainer()->get('oro_product.expression.fields_provider');
    }

    /**
     * @dataProvider getFieldsDataProvider
     * @param string $class
     * @param bool $onlyNumerical
     * @param bool $withRelations
     * @param array $expectedFields
     * @throws \Exception
     */
    public function testGetFiends($class, $onlyNumerical, $withRelations, array $expectedFields)
    {
        $fields = $this->provider->getFields($class, $onlyNumerical, $withRelations);
        $this->assertEquals(sort($expectedFields), sort($fields));
    }

    /**
     * @return array
     */
    public function getFieldsDataProvider()
    {
        return [
            [Product::class, true, false, ['id']],
            [
                Product::class,
                false,
                true,
                [
                    'createdAt',
                    'hasVariants',
                    'id',
                    'sku',
                    'status',
                    'updatedAt',
                    'variantFields',
                    'category',
                    'descriptions',
                    'images',
                    'inventory_status',
                    'metaDescriptions',
                    'metaKeywords',
                    'names',
                    'organization',
                    'owner',
                    'variantLinks',
                    'shortDescriptions',
                    'primaryUnitPrecision',
                    'unitPrecisions',
                ]
            ],
            [
                Product::class,
                false,
                false,
                [
                    'createdAt',
                    'hasVariants',
                    'id',
                    'sku',
                    'status',
                    'updatedAt',
                    'variantFields',
                ]
            ],
        ];
    }
}
