<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\ShoppingListBundle\Layout\Extension\ShoppingListLimitContextConfigurator;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShoppingListLimitContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $limitManager;

    /** @var ShoppingListLimitContextConfigurator */
    private $configurator;

    protected function setUp(): void
    {
        $this->limitManager = $this->createMock(ShoppingListLimitManager::class);

        $this->configurator = new ShoppingListLimitContextConfigurator($this->limitManager);
    }

    /**
     * @dataProvider configureContextDataProvider
     */
    public function testConfigureContext(bool $isOnlyOneEnabled): void
    {
        $this->limitManager->expects($this->once())
            ->method('isOnlyOneEnabled')
            ->willReturn($isOnlyOneEnabled);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getResolver')
            ->willReturn(new OptionsResolver());
        $context->expects($this->once())
            ->method('set')
            ->with('isSingleShoppingList', $isOnlyOneEnabled);

        $this->configurator->configureContext($context);
    }

    public function configureContextDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }
}
