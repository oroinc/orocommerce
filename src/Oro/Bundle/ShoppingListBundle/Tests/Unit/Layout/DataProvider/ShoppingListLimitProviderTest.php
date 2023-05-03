<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListLimitProvider;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;

class ShoppingListLimitProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ShoppingListLimitProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ShoppingListLimitManager::class);
        $this->provider = new ShoppingListLimitProvider($this->manager);
    }

    /**
     * @dataProvider isOnlyOneEnabledProvider
     */
    public function testIsOnlyOneEnabled(bool $result)
    {
        $this->manager->expects($this->once())
            ->method('isOnlyOneEnabled')
            ->willReturn($result);
        self::assertEquals($result, $this->provider->isOnlyOneEnabled());
    }

    public function isOnlyOneEnabledProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }
}
