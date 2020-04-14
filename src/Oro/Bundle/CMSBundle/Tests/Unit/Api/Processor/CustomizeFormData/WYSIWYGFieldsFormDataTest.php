<?php

namespace commerce\src\Oro\Bundle\CMSBundle\Tests\Unit\Api\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CMSBundle\Api\Processor\CustomizeFormData\WYSIWYGFieldsFormData;
use Oro\Bundle\CMSBundle\Provider\WYSIWYGFieldsProvider;

class WYSIWYGFieldsFormDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WYSIWYGFieldsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $wysiwygFieldsProvider;

    /**
     * @var WYSIWYGFieldsFormData
     */
    private $processor;

    protected function setUp(): void
    {
        $this->wysiwygFieldsProvider = $this->createMock(WYSIWYGFieldsProvider::class);

        $this->processor = new WYSIWYGFieldsFormData($this->wysiwygFieldsProvider);
    }

    public function testProcess()
    {
        $context = new CustomizeFormDataContext();
        $context->setClassName('\stdClass');
        $context->setData([
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
        ]);

        $this->wysiwygFieldsProvider->expects($this->once())
            ->method('getWysiwygFields')
            ->with('\stdClass')
            ->willReturn(['wysiwygField']);

        $this->processor->process($context);

        $this->assertEquals(
            [
                'wysiwygField' => '<div id="test">test</div>div>',
                'wysiwygField_style' => "id  {color: red;}",
                'wysiwygField_properties' => [
                    [
                        'name' => 'Row',
                        'content' => ''
                    ]
                ]
            ],
            $context->getData()
        );
    }
}
