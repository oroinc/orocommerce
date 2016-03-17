<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Placeholder;

use OroB2B\Bundle\FrontendBundle\Request\LayoutHelper;
use OroB2B\Bundle\FrontendBundle\Placeholder\LayoutFilter;

class LayoutFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LayoutHelper
     */
    protected $helper;

    /**
     * @var LayoutFilter
     */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->helper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\LayoutHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param bool $isLayout
     * @dataProvider isBackendIsFrontendDataProvider
     */
    public function testFilter($isLayout)
    {
        $this->filter = new LayoutFilter($this->helper);

        $this->helper->expects($this->any())
            ->method('isLayoutRequest')
            ->willReturn($isLayout);

        $this->assertSame($isLayout, $this->filter->isLayoutRoute());
        $this->assertSame(!$isLayout, $this->filter->isSPARoute());
    }

    /**
     * @return array
     */
    public function isBackendIsFrontendDataProvider()
    {
        return [
            'backend request' => [
                'isLayout' => false,
            ],
            'frontend request' => [
                'isLayout' => true,
            ],
        ];
    }
}
