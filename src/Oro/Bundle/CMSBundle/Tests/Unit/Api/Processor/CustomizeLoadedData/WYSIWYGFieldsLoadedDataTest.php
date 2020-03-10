<?php

namespace commerce\src\Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CMSBundle\Api\Processor\CustomizeLoadedData\WYSIWYGFieldsLoadedData;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class WYSIWYGFieldsLoadedDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $wysiwygFieldsProvider;

    /**
     * @var WYSIWYGFieldsLoadedData
     */
    private $processor;

    protected function setUp()
    {
        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new WYSIWYGFieldsLoadedData($this->wysiwygFieldsProvider);
    }

    public function testProcessWithoutWYSIWYGFields()
    {
        $context = new CustomizeLoadedDataContext();
        $context->setClassName('\stdClass');
        $context->setData([
            'field' => '<div id="test">test</div>div>',
            'field_style' => "id  {color: red;}",
            'field_properties' => [
                [
                    'name' => 'Row',
                    'content' => ''
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
                'field' => '<div id="test">test</div>div>',
                'field_style' => "id  {color: red;}",
                'field_properties' => [
                    [
                        'name' => 'Row',
                        'content' => ''
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
            'wysiwygField' => '<div id="test">test</div>div>',
            'wysiwygField_style' => "id  {color: red;}",
            'wysiwygField_properties' => [
                [
                    'name' => 'Row',
                    'content' => ''
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
            ],
            $context->getData()
        );
    }
}
