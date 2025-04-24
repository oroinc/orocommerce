<?php

namespace Oro\Bundle\CommerceBundle\Tests\Functional\ContentWidget\Provider;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\OpenRfqsScorecardProvider;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class OpenRfqsScorecardProviderTest extends WebTestCase
{
    private OpenRfqsScorecardProvider $scorecardProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadRequestData::class]);

        $this->scorecardProvider = self::getContainer()
            ->get('oro_commerce.content_widget.provider.scorecards_open_rfqs');
    }

    public function testGetName(): void
    {
        self::assertSame('open_rfqs', $this->scorecardProvider->getName());
    }

    public function testGetLabel(): void
    {
        self::assertSame('oro.commerce.content_widget_type.scorecard.open_rfqs', $this->scorecardProvider->getLabel());
    }

    public function testIsVisible(): void
    {
        self::assertTrue($this->scorecardProvider->isVisible());
    }

    public function testGetData(): void
    {
        self::assertSame('14', $this->scorecardProvider->getData());
    }

    public function testGetDataWithExcludedStatuses(): void
    {
        $this->scorecardProvider->setExcludedCustomerStatuses(['submitted']);

        self::assertSame('0', $this->scorecardProvider->getData());
    }
}
