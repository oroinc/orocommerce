<?php

namespace Oro\Bundle\CommerceBundle\Tests\Functional\ContentWidget\Provider;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\ShoppingListsScorecardProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class ShoppingListsScorecardProviderTest extends WebTestCase
{
    private ShoppingListsScorecardProvider $scorecardProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadShoppingLists::class]);

        $this->scorecardProvider = self::getContainer()
            ->get('oro_commerce.content_widget.provider.scorecards_shopping_lists');
    }

    public function testGetName(): void
    {
        self::assertSame('shopping_lists', $this->scorecardProvider->getName());
    }

    public function testGetLabel(): void
    {
        self::assertSame(
            'oro.commerce.content_widget_type.scorecard.shopping_lists',
            $this->scorecardProvider->getLabel()
        );
    }

    public function testIsVisible(): void
    {
        self::assertTrue($this->scorecardProvider->isVisible());
    }

    public function testGetData(): void
    {
        self::assertSame('10', $this->scorecardProvider->getData());
    }
}
