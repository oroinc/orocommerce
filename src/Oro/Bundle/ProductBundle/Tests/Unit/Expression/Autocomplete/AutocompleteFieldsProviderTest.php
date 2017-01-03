<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AutocompleteFieldsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $expressionParser;

    /**
     * @var FieldsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var AutocompleteFieldsProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->expressionParser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new AutocompleteFieldsProvider(
            $this->expressionParser,
            $this->fieldsProvider,
            $this->translator
        );
    }

    public function testGetAutocompleteData()
    {
        $numericalOnly = true;
        $withRelations = true;

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($str) {
                    return $str . ' TRANS';
                }
            );
        $this->expressionParser->expects($this->once())
            ->method('getReverseNameMapping')
            ->willReturn(['ProductClass' => 'product']);
        $this->expressionParser->expects($this->once())
            ->method('getNamesMapping')
            ->willReturn(['product' => 'ProductClass']);

        $this->fieldsProvider->expects($this->any())
            ->method('getDetailedFieldsInformation')
            ->withConsecutive(
                ['ProductClass', $numericalOnly, $withRelations],
                ['UnitClass', $numericalOnly, $withRelations]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => [
                        'name' => 'id',
                        'label' => 'id.label',
                        'type' => 'integer'
                    ],
                    'unit' => [
                        'name' => 'unit',
                        'label' => 'unit.label',
                        'type' => 'manyToOne',
                        'relation_type' => 'manyToOne',
                        'related_entity_name' => 'UnitClass'
                    ],
                    'unknown_type' => [
                        'name' => 'unknown_type',
                        'label' => 'unknown_type.label',
                        'type' => 'unknown'
                    ]
                ],
                [
                    'code' => [
                        'name' => 'code',
                        'label' => 'code.label',
                        'type' => 'string'
                    ]
                ]
            );

        $expectedData = [
            AutocompleteFieldsProvider::ROOT_ENTITIES_KEY => [
                'ProductClass' => 'product'
            ],
            AutocompleteFieldsProvider::FIELDS_DATA_KEY => [
                'ProductClass' => [
                    'id' => [
                        'label' => 'id.label TRANS',
                        'type' => AutocompleteFieldsProvider::TYPE_INTEGER
                    ],
                    'unit' => [
                        'label' => 'unit.label TRANS',
                        'type' => AutocompleteFieldsProvider::TYPE_RELATION,
                        'relation_alias' => 'UnitClass'
                    ]
                ],
                'UnitClass' => [
                    'code' => [
                        'label' => 'code.label TRANS',
                        'type' => 'standalone'
                    ],
                    'special' => [
                        'label' => 'special TRANS',
                        'type' => 'money'
                    ]
                ]
            ]
        ];

        $this->provider->addSpecialFieldInformation('UnitClass', 'special', ['label' => 'special', 'type' => 'money']);
        $this->provider->addSpecialFieldInformation('UnitClass', 'code', ['type' => 'standalone']);
        $this->assertEquals($expectedData, $this->provider->getAutocompleteData($numericalOnly, $withRelations));
    }
}
