<?php

namespace commerce\src\Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CMSBundle\Api\Processor\CustomizeLoadedData\FrontendWYSIWYGFieldsLoadedData;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class FrontendWYSIWYGFieldsLoadedDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $wysiwygFieldsProvider;

    /**
     * @var FrontendWYSIWYGFieldsLoadedData
     */
    private $processor;

    protected function setUp(): void
    {
        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new FrontendWYSIWYGFieldsLoadedData($this->wysiwygFieldsProvider);
    }

    public function testProcessWithoutWYSIWYGFields()
    {
        $context = new CustomizeLoadedDataContext();
        $context->setClassName('\stdClass');
        $context->setData([
            [
                'productAttributes' => [],
                'field' => [
                    'value' => '<div id="test">test</div>div>',
                    'style' => 'id  {color: red;}',
                    'properties' => [
                        [
                            'name' => 'Row',
                            'content' => ''
                        ]
                    ]
                ]
            ]
        ]);

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygFields')
            ->with('\stdClass')
            ->willReturn([]);

        $this->processor->process($context);

        $this->assertEquals(
            [
                [
                    'productAttributes' => [],
                    'field' => [
                        'value' => '<div id="test">test</div>div>',
                        'style' => 'id  {color: red;}',
                        'properties' => [
                            [
                                'name' => 'Row',
                                'content' => ''
                            ]
                        ]
                    ]
                ]
            ],
            $context->getData()
        );
    }

    public function testProcess()
    {
        $context = new CustomizeLoadedDataContext();
        $context->setClassName('\stdClass');
        $context->setData([
            [
                'productAttributes' => [],
                'wysiwygField' => [
                    'value' => '<div id="test">test</div>div>',
                    'style' => 'id  {color: red;}',
                    'properties' => [
                        [
                            'name' => 'Row',
                            'content' => ''
                        ]
                    ],
                ]
            ]
        ]);

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygFields')
            ->with('\stdClass')
            ->willReturn(['wysiwygField']);

        $this->processor->process($context);

        $this->assertEquals(
            [
                [
                    'productAttributes' => [],
                    'wysiwygField' => [
                        'value' => '<div id="test">test</div>div>',
                        'style' => 'id  {color: red;}'
                    ]
                ]
            ],
            $context->getData()
        );
    }

    public function testProcessMoveAttribute()
    {
        $context = new CustomizeLoadedDataContext();
        $context->setClassName('\stdClass');
        /** @var EntityDefinitionConfig|\PHPUnit\Framework\MockObject\MockObject $config */
        $config = $this->createMock(EntityDefinitionConfig::class);
        $context->setConfig($config);
        $context->setData([
            [
                'productAttributes' => [],
                'wysiwygAttribute' => [
                    'value' => '<div id="test">test</div>div>',
                    'style' => 'id  {color: red;}',
                    'properties' => [
                        [
                            'name' => 'Row',
                            'content' => ''
                        ]
                    ],
                ]
            ]
        ]);

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygFields')
            ->with('\stdClass')
            ->willReturn(['wysiwygAttribute']);
        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygAttributes')
            ->with('\stdClass')
            ->willReturn(['wysiwygAttribute']);

        $this->processor->process($context);

        $this->assertEquals(
            [
                [
                    'productAttributes' => [
                        'wysiwygAttribute' => [
                            'value' => '<div id="test">test</div>div>',
                            'style' => 'id  {color: red;}'
                        ]
                    ],
                ]
            ],
            $context->getData()
        );
    }
}
