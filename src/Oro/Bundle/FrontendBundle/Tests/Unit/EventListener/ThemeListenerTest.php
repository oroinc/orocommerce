<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\ThemeBundle\Model\ThemeRegistry;
use Oro\Bundle\FrontendBundle\EventListener\ThemeListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    protected function setUp()
    {
        $this->helper = $this->getMockBuilder('Oro\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->themeRegistry = new ThemeRegistry(
            [
                'oro' => [],
                'demo' => [],
            ]
        );

        $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param boolean $installed
     * @param int $requestType
     * @param boolean $isFrontendRequest
     * @param string $expectedOroTheme
     * @param string $expectedLayoutTheme
     * @param boolean $hashNavigation
     * @param boolean $fullRedirect
     *
     * @dataProvider onKernelRequestProvider
     */
    public function testOnKernelRequest(
        $installed,
        $requestType,
        $isFrontendRequest,
        $expectedOroTheme,
        $expectedLayoutTheme,
        $hashNavigation,
        $fullRedirect
    ) {
        $this->themeRegistry->setActiveTheme('oro');

        $request = new Request();
        if ($hashNavigation) {
            $request->headers->set(ResponseHashnavListener::HASH_NAVIGATION_HEADER, true);
        }
        $event = new GetResponseEvent($this->kernel, $request, $requestType);

        $this->helper->expects($this->any())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($isFrontendRequest);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(ThemeListener::DEFAULT_LAYOUT_THEME_CONFIG_VALUE_KEY)
            ->willReturn('test_layout_theme');

        $listener = new ThemeListener($this->themeRegistry, $this->helper, $this->configManager, $installed);
        $listener->onKernelRequest($event);

        $this->assertEquals($expectedOroTheme, $this->themeRegistry->getActiveTheme()->getName());
        $this->assertEquals($expectedLayoutTheme, $request->attributes->get('_theme'));
        $this->assertEquals($fullRedirect, $request->attributes->has('_fullRedirect'));
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
                'expectedOroTheme' => 'oro',
                'expectedLayoutTheme' => null,
                'hashNavigation' => false,
                'fullRedirect' => false,
            ],
            'not master request' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'isFrontendRequest' => true,
                'expectedOroTheme' => 'oro',
                'expectedLayoutTheme' => null,
                'hashNavigation' => false,
                'fullRedirect' => false,
            ],
            'frontend' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => true,
                'expectedOroTheme' => 'demo',
                'expectedLayoutTheme' => 'test_layout_theme',
                'hashNavigation' => true,
                'fullRedirect' => true,
            ],
            'backend' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => false,
                'expectedOroTheme' => 'oro',
                'expectedLayoutTheme' => null,
                'hashNavigation' => false,
                'fullRedirect' => false,
            ],
        ];
    }

    /**
     * @dataProvider onKernelViewProvider
     *
     * @param bool $installed
     * @param string $requestType
     * @param bool $isFrontendRequest
     * @param bool $hasTheme
     * @param bool|string $deletedAnnotation
     */
    public function testOnKernelView($installed, $requestType, $isFrontendRequest, $hasTheme, $deletedAnnotation)
    {
        $this->themeRegistry->setActiveTheme('oro');

        $request = new Request();
        $request->attributes->set('_template', true);
        $request->attributes->set('_layout', true);
        if ($hasTheme) {
            $request->attributes->set('_theme', 'test');
        }
        $event = new GetResponseForControllerResultEvent(
            $this->kernel,
            $request,
            $requestType,
            []
        );

        $this->helper->expects($this->any())
            ->method('isFrontendRequest')
            ->with($request)
            ->willReturn($isFrontendRequest);

        $listener = new ThemeListener($this->themeRegistry, $this->helper, $this->configManager, $installed);

        $listener->onKernelView($event);

        if ($deletedAnnotation && $requestType === HttpKernelInterface::MASTER_REQUEST) {
            $this->assertFalse($request->attributes->has($deletedAnnotation));
        }
    }

    /**
     * @return array
     */
    public function onKernelViewProvider()
    {
        return [
            'not installed application' => [
                'installed' => false,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => false,
                'hasTheme' => false,
                'deletedAnnotation' => false
            ],
            'backend sub-request' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'isFrontendRequest' => false,
                'hasTheme' => false,
                'deletedAnnotation' => false
            ],
            'backend master request' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => false,
                'hasTheme' => false,
                'deletedAnnotation' => false
            ],
            'frontend master request without layout theme' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => true,
                'hasTheme' => false,
                'deletedAnnotations' => '_layout'
            ],
            'frontend sub-request without layout theme' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'isFrontendRequest' => true,
                'hasTheme' => false,
                'deletedAnnotations' => '_layout'
            ],
            'frontend master request with layout theme' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::MASTER_REQUEST,
                'isFrontendRequest' => true,
                'hasTheme' => true,
                'deletedAnnotations' => '_template'
            ],
            'frontend sub-request with layout theme' => [
                'installed' => true,
                'requestType' => HttpKernelInterface::SUB_REQUEST,
                'isFrontendRequest' => true,
                'hasTheme' => true,
                'deletedAnnotations' => '_template'
            ],
        ];
    }
}
