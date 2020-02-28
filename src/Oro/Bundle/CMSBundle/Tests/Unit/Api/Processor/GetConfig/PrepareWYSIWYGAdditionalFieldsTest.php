<?php

namespace commerce\src\Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\CMSBundle\Api\Processor\GetConfig\PrepareWYSIWYGAdditionalFields;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class PrepareWYSIWYGAdditionalFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $wysiwygFieldsProvider;

    /**
     * @var PrepareWYSIWYGAdditionalFields
     */
    private $processor;

    protected function setUp()
    {
        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new PrepareWYSIWYGAdditionalFields($this->wysiwygFieldsProvider);
    }

    public function testProcessWithoutWYSIWYGFields()
    {
        $context = new ConfigContext();
        $context->setResult([]);
        $context->setClassName('\stdClass');

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygFields')
            ->with('\stdClass')
            ->willReturn([]);

        $this->wysiwygFieldsProvider->expects($this->never())
            ->method('getWysiwygAttributes');

        $this->processor->process($context);
    }

    public function testProcess()
    {
        $context = new ConfigContext();
        $context->setResult($this->getEntityDefinitionConfig([
            'firstField'
        ]));
        $context->setClassName('\stdClass');

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygFields')
            ->with('\stdClass')
            ->willReturn(['firstField', 'secondField']);

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygAttributes')
            ->with('\stdClass')
            ->willReturn(['secondField']);

        $this->processor->process($context);

        $this->assertEquals(
            [
                'fields' => [
                    'firstField' => [
                        'depends_on' => ['firstField_style', 'firstField_properties'],
                    ],
                    'firstField_style' => [
                        'depends_on' => ['serialized_data'],
                        'exclude' => true,
                        'data_type' => 'string'
                    ],
                    'firstField_properties' => [
                        'depends_on' => ['serialized_data'],
                        'exclude' => true,
                        'data_type' => 'array'
                    ],
                    'secondField' => [
                        'depends_on' => ['secondField_style', 'secondField_properties'],
                        'data_type' => 'string'
                    ],
                    'secondField_style' => [
                        'depends_on' => ['serialized_data'],
                        'exclude' => true,
                        'data_type' => 'string'
                    ],
                    'secondField_properties' => [
                        'depends_on' => ['serialized_data'],
                        'exclude' => true,
                        'data_type' => 'array'
                    ]
                ]
            ],
            $context->getResult()->toArray()
        );
    }

    /**
     * @param string[] $fields
     *
     * @return EntityDefinitionConfig
     */
    private function getEntityDefinitionConfig(array $fields = [])
    {
        $config = new EntityDefinitionConfig();
        foreach ($fields as $field) {
            $config->addField($field);
        }

        return $config;
    }
}
