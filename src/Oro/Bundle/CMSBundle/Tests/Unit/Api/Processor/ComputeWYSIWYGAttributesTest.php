<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData\CustomizeLoadedDataProcessorTestCase;
use Oro\Bundle\CMSBundle\Api\Processor\ComputeWYSIWYGAttributes;
use Oro\Bundle\CMSBundle\Api\WYSIWYGValueRenderer;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class ComputeWYSIWYGAttributesTest extends CustomizeLoadedDataProcessorTestCase
{
    private const ATTRIBUTES_FIELD_NAME = 'testAttributes';

    /** @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygFieldsProvider;

    /** @var WYSIWYGValueRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygValueRenderer;

    /** @var ComputeWYSIWYGAttributes */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);
        $this->wysiwygValueRenderer = $this->createMock(WYSIWYGValueRenderer::class);

        $this->processor = new ComputeWYSIWYGAttributes(
            $this->wysiwygFieldsProvider,
            $this->wysiwygValueRenderer,
            self::ATTRIBUTES_FIELD_NAME
        );
    }

    public function testProcessWhenAttributesAreNotRequested()
    {
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();
        $config->addField(self::ATTRIBUTES_FIELD_NAME)->setExcluded();

        $data = [
            [
                self::ATTRIBUTES_FIELD_NAME => [],
                'wysiwygAttribute'          => [
                    'value' => '<div id="test">test</div>div>',
                    'style' => 'id {color: red;}'
                ]
            ]
        ];

        $this->wysiwygFieldsProvider->expects(self::never())
            ->method(self::anything());

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals($data, $this->context->getData());
    }

    public function testProcessWithoutWysiwygAttributes()
    {
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();
        $config->addField(self::ATTRIBUTES_FIELD_NAME);

        $data = [
            [
                self::ATTRIBUTES_FIELD_NAME => [],
                'wysiwygField'              => [
                    'value'      => '<div id="test">test</div>div>',
                    'style'      => 'id {color: red;}',
                    'properties' => [
                        ['name' => 'Row', 'content' => '']
                    ]
                ]
            ]
        ];

        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygAttributes')
            ->with($entityClass)
            ->willReturn([]);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setData($data);
        $this->processor->process($this->context);

        self::assertEquals($data, $this->context->getData());
    }

    public function testProcess()
    {
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();
        $config->addField(self::ATTRIBUTES_FIELD_NAME);

        $this->wysiwygFieldsProvider->expects(self::once())
            ->method('getWysiwygAttributes')
            ->with($entityClass)
            ->willReturn(['wysiwygAttribute']);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->context->setData([
            [
                self::ATTRIBUTES_FIELD_NAME => [],
                'wysiwygAttribute'          => [
                    'value' => '<div id="test">test</div>div>',
                    'style' => 'id {color: red;}'
                ]
            ]
        ]);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                [
                    self::ATTRIBUTES_FIELD_NAME => [
                        'wysiwygAttribute' => [
                            'value' => '<div id="test">test</div>div>',
                            'style' => 'id {color: red;}'
                        ]
                    ]
                ]
            ],
            $this->context->getData()
        );
    }
}
