<?php

namespace Oro\Bundle\CommerceBundle\Tests\Functional\ContentWidget\Provider;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\TotalOrdersScorecardProvider;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Tests\Functional\WebsiteTrait;

final class TotalOrdersScorecardProviderTest extends WebTestCase
{
    use WebsiteTrait;

    private TotalOrdersScorecardProvider $scorecardProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadOrders::class]);

        $this->scorecardProvider = self::getContainer()
            ->get('oro_commerce.content_widget.provider.scorecards_total_orders');
    }

    public function testGetName(): void
    {
        self::assertSame('total_orders', $this->scorecardProvider->getName());
    }

    public function testGetLabel(): void
    {
        self::assertSame(
            'oro.commerce.content_widget_type.scorecard.total_orders',
            $this->scorecardProvider->getLabel()
        );
    }

    public function testIsVisible(): void
    {
        self::assertTrue($this->scorecardProvider->isVisible());
    }

    public function testGetDataNoWebsite(): void
    {
        self::assertNull($this->scorecardProvider->getData());
    }

    public function testGetData(): void
    {
        self::getContainer()->get('oro_website.manager')->setCurrentWebsite($this->getDefaultWebsite());

        self::assertSame('$7,404.00', $this->scorecardProvider->getData());
    }

    public function testGetDataWithExcludedStatuses(): void
    {
        $this->scorecardProvider->setExcludedInternalStatuses(['cancelled', 'closed']);

        self::assertSame('$6,170.00', $this->scorecardProvider->getData());
    }
}
