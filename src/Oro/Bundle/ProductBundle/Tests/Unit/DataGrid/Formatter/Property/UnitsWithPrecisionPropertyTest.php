<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\TwigTemplateProperty;
use Oro\Bundle\ProductBundle\DataGrid\Formatter\Property\UnitsWithPrecisionProperty;
use Oro\Bundle\UIBundle\Twig\Environment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnitsWithPrecisionPropertyTest extends TestCase
{
    private const TEMPLATE = 'sample_template.html.twig';

    private Environment|MockObject $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);

        $twigTemplateProperty = new TwigTemplateProperty($this->twig);
        $this->property = new UnitsWithPrecisionProperty($twigTemplateProperty);
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testGetRawValue(array $params, array $expected): void
    {
        $this->property->init(PropertyConfiguration::create($params));

        $data = new \stdClass();
        $data->unitsWithPrecision = 'item|item,each|0,1';
        $data->unitsWithPrecisionData = 'set|set,each|1,0';
        $data->unitsWithPrecisionInvalidData = 'invalid';

        $record = new ResultRecord($data);

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with(self::TEMPLATE, ['record' => $record] + $expected);

        $this->property->getValue($record);
    }

    public function valueDataProvider(): array
    {
        return [
            'regular name' => [
                [
                    TwigTemplateProperty::TEMPLATE_KEY => self::TEMPLATE,
                    TwigTemplateProperty::NAME_KEY => 'unitsWithPrecision',
                ],
                [
                    'value' => [
                        ['code' => 'item', 'precision' => 0, 'isPrimary' => true],
                        ['code' => 'each', 'precision' => 1, 'isPrimary' => false]
                    ],
                ],
            ],
            'data_name with invalid data' => [
                [
                    TwigTemplateProperty::TEMPLATE_KEY => self::TEMPLATE,
                    TwigTemplateProperty::DATA_NAME_KEY => 'unitsWithPrecisionInvalidData',
                ],
                [
                    'value' => [],
                ],
            ],
            'data_name with valid data' => [
                [
                    TwigTemplateProperty::TEMPLATE_KEY => self::TEMPLATE,
                    TwigTemplateProperty::DATA_NAME_KEY => 'unitsWithPrecisionData',
                ],
                [
                    'value' => [
                        ['code' => 'set', 'precision' => 1, 'isPrimary' => true],
                        ['code' => 'each', 'precision' => 0, 'isPrimary' => false]
                    ],
                ],
            ],
            'data_name with valid data and context' => [
                [
                    TwigTemplateProperty::TEMPLATE_KEY => self::TEMPLATE,
                    TwigTemplateProperty::DATA_NAME_KEY => 'unitsWithPrecisionData',
                    TwigTemplateProperty::CONTEXT_KEY => ['sampleContextKey' => 'sampleContextValue'],

                ],
                [
                    'value' => [
                        ['code' => 'set', 'precision' => 1, 'isPrimary' => true],
                        ['code' => 'each', 'precision' => 0, 'isPrimary' => false]
                    ],
                    'sampleContextKey' => 'sampleContextValue',
                ],
            ],
        ];
    }

    public function testInitWhenReservedKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Context of template "sample_template" includes reserved key(s) - (record, value)'
        );

        $params = [
            TwigTemplateProperty::TEMPLATE_KEY => 'sample_template',
            TwigTemplateProperty::CONTEXT_KEY => [
                'record' => 'sample_record',
                'value' => 'sample_value',
            ],
        ];
        $this->property->init(PropertyConfiguration::create($params));
    }
}
