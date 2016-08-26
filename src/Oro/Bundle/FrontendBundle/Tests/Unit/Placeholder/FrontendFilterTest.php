<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Placeholder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendBundle\Placeholder\FrontendFilter;

class FrontendFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendHelper
     */
    protected $helper;

    /**
     * @var FrontendFilter
     */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->helper = $this->getMockBuilder('Oro\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testNoRequestBehaviour()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn(null);
        $this->filter = new FrontendFilter($this->helper, $requestStack);
        $this->assertTrue($this->filter->isBackendRoute());
        $this->assertFalse($this->filter->isFrontendRoute());
    }

    /**
     * @param bool $isFrontend
     * @dataProvider isBackendIsFrontendDataProvider
     */
    public function testIsBackendIsFrontend($isFrontend)
    {
        $request = new Request();
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);
        $this->filter = new FrontendFilter($this->helper, $requestStack);

        $this->helper->expects($this->any())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($isFrontend);

        $this->assertSame(!$isFrontend, $this->filter->isBackendRoute());
        $this->assertSame($isFrontend, $this->filter->isFrontendRoute());
    }

    /**
     * @return array
     */
    public function isBackendIsFrontendDataProvider()
    {
        return [
            'backend request' => [
                'isFrontend' => false,
            ],
            'frontend request' => [
                'isFrontend' => true,
            ],
        ];
    }
}
