<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\ShoppingListLimitProvider;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;

class ShoppingListLimitProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $manager;

    /**
     * @var ShoppingListLimitProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->manager = $this->createMock(ShoppingListLimitManager::class);
        $this->provider = new ShoppingListLimitProvider($this->manager);
    }

    /**
     * @dataProvider isOnlyOneEnabledProvider
     * @param bool $result
     */
    public function testIsOnlyOneEnabled(bool $result)
    {
        $this->manager->expects($this->once())
            ->method('isOnlyOneEnabled')
            ->willReturn($result);
        self::assertEquals($result, $this->provider->isOnlyOneEnabled());
    }

    /**
     * @return array
     */
    public function isOnlyOneEnabledProvider()
    {
        return [
            [true],
            [false],
        ];
    }
}
