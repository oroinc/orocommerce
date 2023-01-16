<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Expression;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Expression\FieldsProviderInterface;

class FieldsProviderTest extends WebTestCase
{
    private FieldsProviderInterface $provider;

    protected function setUp(): void
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);
        $this->provider = $this->getContainer()->get('oro_product.expression.fields_provider');
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGetFiends(string $class, bool $onlyNumerical, bool $withRelations, array $expectedFields)
    {
        $fields = $this->provider->getFields($class, $onlyNumerical, $withRelations);
        $this->assertEquals(sort($expectedFields), sort($fields));
    }

    public function getFieldsDataProvider(): array
    {
        return [
            [Product::class, true, false, ['id']],
            [
                Product::class,
                false,
                true,
                [
                    'createdAt',
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
                    'metaTitles',
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
