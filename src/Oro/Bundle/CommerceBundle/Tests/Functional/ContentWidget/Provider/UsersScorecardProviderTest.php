<?php

namespace Oro\Bundle\CommerceBundle\Tests\Functional\ContentWidget\Provider;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\UsersScorecardProvider;
use Oro\Bundle\CustomerBundle\Tests\Functional\ImportExport\Import\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class UsersScorecardProviderTest extends WebTestCase
{
    private UsersScorecardProvider $scorecardProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadCustomerUserData::class]);

        $this->scorecardProvider = self::getContainer()->get('oro_commerce.content_widget.provider.scorecards_users');
    }

    public function testGetName(): void
    {
        self::assertSame('users', $this->scorecardProvider->getName());
    }

    public function testGetLabel(): void
    {
        self::assertSame('oro.commerce.content_widget_type.scorecard.users', $this->scorecardProvider->getLabel());
    }

    public function testIsVisible(): void
    {
        self::assertTrue($this->scorecardProvider->isVisible());
    }

    public function testGetData(): void
    {
        self::assertSame('5', $this->scorecardProvider->getData());
    }
}
