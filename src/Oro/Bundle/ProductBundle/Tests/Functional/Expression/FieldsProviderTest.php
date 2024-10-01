<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Expression;

use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityFields;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Expression\FieldsProviderInterface;

class FieldsProviderTest extends WebTestCase
{
    private FieldsProviderInterface $provider;

    #[\Override]
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
        sort($expectedFields);
        sort($fields);
        $this->assertEquals($expectedFields, $fields);
    }

    public function getFieldsDataProvider(): array
    {
        return [
            'numerical only' => [
                TestEntityFields::class,
                true,
                false,
                [
                    'decimalField',
                    'floatField',
                    'id',
                    'integerField'
                ]
            ],
            'with relations' => [
                TestEntityFields::class,
                false,
                true,
                [
                    'bigintField',
                    'booleanField',
                    'dateField',
                    'datetimeField',
                    'decimalField',
                    'enum_field',
                    'floatField',
                    'htmlField',
                    'id',
                    'image_field',
                    'integerField',
                    'manyToOneRelation',
                    'smallintField',
                    'stringField',
                    'textField'
                ]
            ],
            'without relations' => [
                TestEntityFields::class,
                false,
                false,
                [
                    'bigintField',
                    'booleanField',
                    'dateField',
                    'datetimeField',
                    'decimalField',
                    'enum_field',
                    'floatField',
                    'htmlField',
                    'id',
                    'integerField',
                    'smallintField',
                    'stringField',
                    'textField'
                ]
            ],
        ];
    }
}
