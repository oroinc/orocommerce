<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Provider;

use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ShoppingListUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLimitManager;

    /** @var ShoppingListUrlProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->urlGenerator->expects($this->any())
            ->method('generate')
            ->willReturnMap(
                [
                    ['oro_shopping_list_frontend_view', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/view/url'],
                    [
                        'oro_shopping_list_frontend_view',
                        ['id' => 42],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                        '/view/url/id'
                    ],
                    ['oro_shopping_list_frontend_update', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/update/url'],
                    [
                        'oro_shopping_list_frontend_update',
                        ['id' => 42],
                        UrlGeneratorInterface::ABSOLUTE_PATH,
                        '/update/url/id'
                    ],
                ]
            );

        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);

        $this->provider = new ShoppingListUrlProvider(
            $this->authorizationChecker,
            $this->urlGenerator,
            $this->shoppingListLimitManager
        );
    }

    /**
     * @dataProvider getFrontendUrlDataProvider
     */
    public function testGetFrontendUrl(
        ?ShoppingList $shoppingList,
        bool $isOnlyOneEnabled,
        bool $isGranted,
        string $expected
    ): void {
        $this->shoppingListLimitManager->expects($this->any())
            ->method('isOnlyOneEnabled')
            ->willReturn($isOnlyOneEnabled);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update')
            ->willReturn($isGranted);

        $this->assertEquals($expected, $this->provider->getFrontendUrl($shoppingList));
    }

    public function getFrontendUrlDataProvider(): array
    {
        $shoppingList = new ShoppingListStub();
        $shoppingList->setId(42);

        return [
            [
                'shoppingList' => null,
                'isOnlyOneEnabled' => false,
                'isGranted' => false,
                'expected' => '/view/url',
            ],
            [
                'shoppingList' => null,
                'isOnlyOneEnabled' => false,
                'isGranted' => true,
                'expected' => '/update/url',
            ],
            [
                'shoppingList' => null,
                'isOnlyOneEnabled' => true,
                'isGranted' => false,
                'expected' => '/view/url',
            ],
            [
                'shoppingList' => null,
                'isOnlyOneEnabled' => true,
                'isGranted' => true,
                'expected' => '/update/url',
            ],
            [
                'shoppingList' => $shoppingList,
                'isOnlyOneEnabled' => false,
                'isGranted' => false,
                'expected' => '/view/url/id',
            ],
            [
                'shoppingList' => $shoppingList,
                'isOnlyOneEnabled' => false,
                'isGranted' => true,
                'expected' => '/update/url/id',
            ],
            [
                'shoppingList' => $shoppingList,
                'isOnlyOneEnabled' => true,
                'isGranted' => false,
                'expected' => '/view/url',
            ],
            [
                'shoppingList' => $shoppingList,
                'isOnlyOneEnabled' => true,
                'isGranted' => true,
                'expected' => '/update/url',
            ],
        ];
    }
}
