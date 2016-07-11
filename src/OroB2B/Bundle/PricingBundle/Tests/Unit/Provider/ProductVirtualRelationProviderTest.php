<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use OroB2B\Bundle\PricingBundle\Provider\ProductVirtualRelationProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVirtualRelationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVirtualRelationProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    public function setUp()
    {
        $repository = $this->getMockBuilder(PriceAttributePriceListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getFieldNames')
            ->willReturn([
                'MSRP' => 'msrp',
                'MAP' => 'map',
            ]);

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(PriceAttributePriceList::class)
            ->willReturn($repository);

        $this->provider = new ProductVirtualRelationProvider($this->doctrineHelper);
    }

    /**
     * @dataProvider isVirtualRelationProvider
     * @param string $class
     * @param string $field
     * @param bool $expected
     */
    public function testIsVirtualRelation($class, $field, $expected)
    {
        $this->assertEquals($expected, $this->provider->isVirtualRelation($class, $field));
    }

    /**
     * @return array
     */
    public function isVirtualRelationProvider()
    {
        return [
            [Product::class, 'msrp', true],
            [Product::class, 'wrong_test_attribute', false],
            ['stdClass', 'relation', false],
        ];
    }

    /**
     * @dataProvider getVirtualRelationQueryProvider
     * @param string $class
     * @param string $field
     * @param array $expected
     */
    public function testGetVirtualRelationQuery($class, $field, array $expected)
    {
        $this->assertEquals($expected, $this->provider->getVirtualRelationQuery($class, $field));
    }

    /**
     * @return array
     */
    public function getVirtualRelationQueryProvider()
    {
        return [
            [
                Product::class,
                'msrp',
                [
                    'join' => [
                        'left' => [
                            [
                                'join' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                                'alias' => 'msrpPrice',
                                'conditionType' => 'WITH',
                                'condition' => '(msrpPrice.product = entity)',
                            ],
                            [
                                'join' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList',
                                'alias' => 'msrpPriceAttribute',
                                'conditionType' => 'WITH',
                                'condition' => '(msrpPrice.priceList = msrpPriceAttribute)',
                            ],
                        ],
                    ],
                ],
            ],
            ['stdClass', 'msrp', []],
        ];
    }

    /**
     * @dataProvider getVirtualRelationsProvider
     * @param string $class
     * @param array $expected
     */
    public function testGetVirtualRelations($class, array $expected)
    {
        $this->assertEquals($expected, $this->provider->getVirtualRelations($class));
    }

    /**
     * @return array
     */
    public function getVirtualRelationsProvider()
    {
        return [
            [
                Product::class,
                [
                    'msrp' => [
                        'label' => 'MSRP',
                        'relation_type' => 'manyToOne',
                        'related_entity_name' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                        'target_join_alias' => 'msrpPrice',
                        'query' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                                        'alias' => 'msrpPrice',
                                        'conditionType' => 'WITH',
                                        'condition' => '(msrpPrice.product = entity)',
                                    ],
                                    [
                                        'join' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList',
                                        'alias' => 'msrpPriceAttribute',
                                        'conditionType' => 'WITH',
                                        'condition' => '(msrpPrice.priceList = msrpPriceAttribute)',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'map' => [
                        'label' => 'MAP',
                        'relation_type' => 'manyToOne',
                        'related_entity_name' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                        'target_join_alias' => 'mapPrice',
                        'query' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                                        'alias' => 'mapPrice',
                                        'conditionType' => 'WITH',
                                        'condition' => '(mapPrice.product = entity)',
                                    ],
                                    [
                                        'join' => 'OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList',
                                        'alias' => 'mapPriceAttribute',
                                        'conditionType' => 'WITH',
                                        'condition' => '(mapPrice.priceList = mapPriceAttribute)',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['stdClass', []],
        ];
    }

    /**
     * @dataProvider getTargetJoinAliasDataProvider
     * @param string $fieldName
     * @param string $joinAlias
     */
    public function testGetTargetJoinAlias($fieldName, $joinAlias)
    {
        $this->assertEquals($joinAlias, $this->provider->getTargetJoinAlias(Product::class, $fieldName));
    }

    /**
     * @return array
     */
    public function getTargetJoinAliasDataProvider()
    {
        return [
            [
                'fieldName' => 'msrp',
                'joinAlias' => 'msrpPrice',
            ],
            [
                'fieldName' => 'map',
                'joinAlias' => 'mapPrice',
            ],
        ];
    }
}
