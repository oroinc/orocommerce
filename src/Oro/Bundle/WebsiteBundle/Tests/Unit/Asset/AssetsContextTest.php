<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Asset;

use Oro\Bundle\WebsiteBundle\Asset\AssetsContext;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AssetsContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsContext
     */
    protected $context;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = new AssetsContext($this->requestStack);
    }

    public function testGetBasePathNoMasterRequest()
    {
        $this->requestStack->expects($this->atLeastOnce())
            ->method('getMasterRequest');

        $this->assertEquals('', $this->context->getBasePath());
    }

    public function testGetBasePath()
    {
        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->atLeastOnce())
            ->method('getBasePath')
            ->willReturn('/base/path');
        $request->server = new ParameterBag(['WEBSITE_PATH' => '/path']);
        
        $this->requestStack->expects($this->atLeastOnce())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->assertEquals('/base', $this->context->getBasePath());
    }

    public function testGetBasePathNoConfiguration()
    {
        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->atLeastOnce())
            ->method('getBasePath')
            ->willReturn('/base/path');
        $request->server = new ParameterBag([]);

        $this->requestStack->expects($this->atLeastOnce())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->assertEquals('/base/path', $this->context->getBasePath());
    }
}
