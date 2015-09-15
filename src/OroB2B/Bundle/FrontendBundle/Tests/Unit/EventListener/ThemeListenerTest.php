<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;

use OroB2B\Bundle\FrontendBundle\EventListener\ThemeListener;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;

class ThemeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ThemeRegistry
     */
    protected $themeRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendHelper
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|HttpKernelInterface
     */
    protected $kernel;

    protected function setUp()
    {
        $this->helper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->themeRegistry = new ThemeRegistry([
            'oro' => [],
            'demo' => [],
        ]);

        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    }

    /**
     * @param boolean $installed
     * @param int $requestType
     * @param boolean $isFrontendRequest
     * @param string $expectedTheme
     *
     * @dataProvider onKernelRequestProvider
     */
    public function testOnKernelRequest(
        $installed,
        $requestType,
        $isFrontendRequest,
        $expectedTheme
    ) {
        $this->themeRegistry->setActiveTheme('oro');

        $request = new Request();
        $event = new GetResponseEvent($this->kernel, $request, $requestType);

        $this->helper->expects($this->any())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($isFrontendRequest);

        $listener = new ThemeListener($this->themeRegistry, $this->helper, $installed);
        $listener->onKernelRequest($event);

        $this->assertEquals($expectedTheme, $this->themeRegistry->getActiveTheme()->getName());
    }

    /**
     * @return array
     */
    public function onKernelRequestProvider()
    {
        return [
            'not installed application' => [
                'installed' => false,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => true,
                'expectedTheme' => 'oro'
            ],
            'not master request' => [
                'installed' => false,
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'isFrontendRequest' => true,
                'expectedTheme' => 'oro'
            ],
            'frontend' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => true,
                'expectedTheme' => 'demo'
            ],
            'backend' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => false,
                'expectedTheme' => 'oro'
            ],
        ];
    }
}
