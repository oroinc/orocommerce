<?php

namespace commerce\src\Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\CMSBundle\Api\Processor\ComputeWYSIWYGAttributes;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class ComputeWYSIWYGAttributesTest extends CustomizeLoadedDataProcessorTestCase
{
    private const ATTRIBUTES_FIELD_NAME = 'testAttributes';

    /** @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygFieldsProvider;

    /** @var ComputeWYSIWYGAttributes */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new ComputeWYSIWYGAttributes(
            $this->wysiwygFieldsProvider,
            self::ATTRIBUTES_FIELD_NAME
        );
    }

    public function testProcessWithoutWysiwygFields()
    {
        $entityClass = 'Test\Entity';

        $data = [
            [
                self::ATTRIBUTES_FIELD_NAME => [],
                'field'                     => [
                    'value'      => '<div id="test">test</div>div>',
                    'style'      => 'id {color: red;}',
                    'properties' => [
                        ['name' => 'Row', 'content' => '']
                    ]
                ]
            ]
        ];

        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with($entityClass)
            ->willReturn([]);

        $this->context->setClassName($entityClass);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals($data, $this->context->getData());
    }

    public function testProcess()
    {
        $entityClass = 'Test\Entity';

        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygFields')
            ->with($entityClass)
            ->willReturn(['wysiwygAttribute']);
        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygAttributes')
            ->with($entityClass)
            ->willReturn(['wysiwygAttribute']);

        $this->context->setClassName($entityClass);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setData([
            [
                self::ATTRIBUTES_FIELD_NAME => [],
                'wysiwygAttribute'          => [
                    'value'      => '<div id="test">test</div>div>',
                    'style'      => 'id {color: red;}',
                    'properties' => [
                        ['name' => 'Row', 'content' => '']
                    ]
                ]
            ]
        ]);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                [
                    self::ATTRIBUTES_FIELD_NAME => [
                        'wysiwygAttribute' => [
                            'value'      => '<div id="test">test</div>div>',
                            'style'      => 'id {color: red;}',
                            'properties' => [
                                ['name' => 'Row', 'content' => '']
                            ]
                        ]
                    ]
                ]
            ],
            $this->context->getData()
        );
    }
}
