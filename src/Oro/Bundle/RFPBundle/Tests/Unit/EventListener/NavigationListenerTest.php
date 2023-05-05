<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\EventListener;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\RFPBundle\EventListener\NavigationListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NavigationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var NavigationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new NavigationListener($this->authorizationChecker, $this->featureChecker);
    }

    /**
     * @dataProvider navigationConfigureDataProvider
     */
    public function testOnNavigationConfigure(bool $isGranted, bool $isResourceEnabled, bool $expectedIsDisplayed)
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_rfp_frontend_request_view')
            ->willReturn($isGranted);
        $this->featureChecker->expects($this->exactly((int)!$isGranted))
            ->method('isResourceEnabled')
            ->with('oro_rfp_frontend_request_index', 'routes')
            ->willReturn($isResourceEnabled);

        $factory = new MenuFactory();
        $menu = new MenuItem('oro_customer_menu', $factory);
        $rfpMenuItem = new MenuItem('oro_rfp_frontend_request_index', $factory);
        $menu->addChild($rfpMenuItem);

        $eventData = new ConfigureMenuEvent($factory, $menu);
        $this->listener->onNavigationConfigure($eventData);

        $this->assertEquals($expectedIsDisplayed, $rfpMenuItem->isDisplayed());
    }

    public function navigationConfigureDataProvider(): array
    {
        return [
            'access granted and resource enabled' => [
                true,
                true,
                true
            ],
            'access not granted and resource enabled' => [
                false,
                true,
                true
            ],
            'access granted and resource not enabled' => [
                true,
                false,
                true
            ],
            'access not granted and resource not enabled' => [
                false,
                false,
                false
            ]
        ];
    }
}
