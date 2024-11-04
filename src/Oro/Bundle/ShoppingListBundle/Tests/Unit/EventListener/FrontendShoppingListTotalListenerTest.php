<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\FrontendShoppingListTotalListener;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FrontendShoppingListTotalListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomerUserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUserProvider;

    /** @var ShoppingListTotalManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListTotalManager;

    /** @var FrontendShoppingListTotalListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->customerUserProvider = $this->createMock(CustomerUserProvider::class);
        $this->shoppingListTotalManager = $this->createMock(ShoppingListTotalManager::class);

        $container = TestContainerBuilder::create()
            ->add(ShoppingListTotalManager::class, $this->shoppingListTotalManager)
            ->getContainer($this);

        $this->listener = new FrontendShoppingListTotalListener($this->customerUserProvider, $container);
    }

    /**
     * @dataProvider routeDataProvider
     */
    public function testOnKernelController(string $route): void
    {
        $customerUser = new CustomerUser();
        $source = new ShoppingList();
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $request = new Request();
        $request->attributes->set('_route', $route);

        $controller = static fn () => null;

        $event = new ControllerArgumentsEvent(
            $httpKernel,
            $controller,
            [$source],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->with(false)
            ->willReturn($customerUser);

        $this->shoppingListTotalManager->expects(self::once())
            ->method('setSubtotalsForCustomerUser')
            ->with($source, $customerUser);

        $this->listener->onKernelController($event);
    }

    /**
     * @dataProvider routeDataProvider
     */
    public function testOnKernelControllerWithAssignedCustomerUser(string $route): void
    {
        $customerUser = new CustomerUser();
        $source = new ShoppingList();
        $source->setCustomerUser($customerUser);

        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $request = new Request();
        $request->attributes->set('_route', $route);

        $controller = static fn () => null;

        $event = new ControllerArgumentsEvent(
            $httpKernel,
            $controller,
            [$source],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->with(false)
            ->willReturn($customerUser);

        $this->shoppingListTotalManager->expects(self::never())
            ->method('setSubtotalsForCustomerUser');

        $this->listener->onKernelController($event);
    }

    public function routeDataProvider(): array
    {
        return [
            'View page' => ['oro_shopping_list_frontend_view'],
            'Edit page' => ['oro_shopping_list_frontend_update']
        ];
    }

    public function testOnKernelControllerWithInvalidRoute(): void
    {
        $customerUser = new CustomerUser();
        $source = new ShoppingList();
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $request = new Request();
        $request->attributes->set('_route', 'invalid url');

        $controller = static fn () => null;

        $event = new ControllerArgumentsEvent(
            $httpKernel,
            $controller,
            [$source],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->customerUserProvider->expects(self::once())
            ->method('getLoggedUser')
            ->with(false)
            ->willReturn($customerUser);

        $this->shoppingListTotalManager->expects(self::never())
            ->method('setSubtotalsForCustomerUser');

        $this->listener->onKernelController($event);
    }
}
