<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\Placeholder;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
use OroB2B\Bundle\FrontendBundle\Placeholder\FrontendFilter;

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
        $this->helper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new FrontendFilter($this->helper);
    }

    public function testNoRequestBehaviour()
    {
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

        $this->helper->expects($this->any())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($isFrontend);

        $this->filter->setRequest($request);

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
