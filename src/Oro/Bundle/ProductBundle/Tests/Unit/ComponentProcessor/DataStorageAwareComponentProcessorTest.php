<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ComponentProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataStorageAwareComponentProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductDataStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var ProductMapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productMapper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var DataStorageAwareComponentProcessor */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->productMapper = $this->createMock(ProductMapperInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->processor = new DataStorageAwareComponentProcessor(
            $this->storage,
            $this->productMapper,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $requestStack,
            $this->translator,
            $this->urlGenerator,
        );
    }

    public function testProcessWithoutRedirectRoute(): void
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];
        $allowedData = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01', 'productId' => 1]]];

        $this->expectsFilterData($allowedData);

        $this->urlGenerator->expects(self::never())
            ->method(self::anything());

        $this->storage->expects(self::once())
            ->method('set')
            ->with($allowedData);

        self::assertNull($this->processor->process($data, new Request()));
    }

    public function testProcessWithRedirectRoute(): void
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];
        $allowedData = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01', 'productId' => 1]]];

        $this->expectsFilterData($allowedData);

        $this->storage->expects(self::once())
            ->method('set')
            ->with($allowedData);

        $redirectUrl = '/redirect/url';
        $this->expectsGenerateRedirectUrl('route', $redirectUrl);

        $response = $this->processor->process($data, new Request());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals($redirectUrl, $response->getTargetUrl());
    }

    /**
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed(string $acl, bool $isGranted, bool $hasLoggedUser, bool $expected): void
    {
        $this->tokenAccessor->expects(self::any())
            ->method('hasUser')
            ->willReturn($hasLoggedUser);
        $this->authorizationChecker->expects(self::any())
            ->method('isGranted')
            ->with($acl)
            ->willReturn($isGranted);

        $this->processor->setAcl($acl);
        self::assertSame($expected, $this->processor->isAllowed());
    }

    public function isAllowedDataProvider(): array
    {
        return [
            ['fail', false, false, false],
            ['fail', true, false, false],
            ['fail', false, true, false],
            ['success', true, true, true],
        ];
    }

    public function testIsAllowedWhenAclIsNotSet(): void
    {
        $this->tokenAccessor->expects(self::never())
            ->method('hasUser');
        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertTrue($this->processor->isAllowed());
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $data,
        array $allowedData,
        string $errorMessageSkus,
        bool $hasRedirectRoute = false
    ): void {
        $this->expectsFilterData($allowedData);

        $this->storage->expects(self::once())
            ->method('set')
            ->with($allowedData);

        $this->expectsAddFlashMessage($errorMessageSkus);

        if ($hasRedirectRoute) {
            $redirectUrl = 'url';
            $this->expectsGenerateRedirectUrl('route', $redirectUrl);

            $response = $this->processor->process($data, new Request());

            self::assertInstanceOf(RedirectResponse::class, $response);
            self::assertEquals($redirectUrl, $response->getTargetUrl());
        } else {
            self::assertNull($this->processor->process($data, new Request()));
        }
    }

    public function processDataProvider(): array
    {
        return [
            'restricted several with redirect' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item'],
                        ['productSku' => 'sku02', 'productUnit' => 'item'],
                        ['productSku' => 'sku02', 'productUnit' => 'set'],
                        ['productSku' => 'sku03', 'productUnit' => 'item'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item', 'productId' => 1],
                    ],
                ],
                'errorMessageSkus' => 'sku02, sku03',
                'hasRedirectRoute' => true
            ],
            'restricted one with redirect' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item'],
                        ['productSku' => 'sku01', 'productUnit' => 'set'],
                        ['productSku' => 'sku02', 'productUnit' => 'item'],
                        ['productSku' => 'sku03', 'productUnit' => 'item'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item', 'productId' => 1],
                        ['productSku' => 'sku01', 'productUnit' => 'set', 'productId' => 1],
                        ['productSku' => 'sku02', 'productUnit' => 'item', 'productId' => 2],
                    ],
                ],
                'errorMessageSkus' => 'sku03',
                'hasRedirectRoute' => true
            ],
            'restricted several without redirect' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item'],
                        ['productSku' => 'sku02', 'productUnit' => 'item'],
                        ['productSku' => 'sku02', 'productUnit' => 'set'],
                        ['productSku' => 'sku03', 'productUnit' => 'item'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item', 'productId' => 1],
                    ],
                ],
                'errorMessageSkus' => 'sku02, sku03',
                'hasRedirectRoute' => false
            ],
            'restricted one without redirect' => [
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item'],
                        ['productSku' => 'sku01', 'productUnit' => 'set'],
                        ['productSku' => 'sku02', 'productUnit' => 'item'],
                        ['productSku' => 'sku03', 'productUnit' => 'item'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01', 'productUnit' => 'item', 'productId' => 1],
                        ['productSku' => 'sku01', 'productUnit' => 'set', 'productId' => 1],
                        ['productSku' => 'sku02', 'productUnit' => 'item', 'productId' => 2],
                    ],
                ],
                'errorMessageSkus' => 'sku03',
                'hasRedirectRoute' => false
            ],
        ];
    }

    /**
     * @dataProvider hasRedirectRouteDataProvider
     */
    public function testProcessAllRestricted(bool $hasRedirectRoute): void
    {
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                ['productSku' => 'sku01', 'productUnit' => 'item'],
                ['productSku' => 'sku02', 'productUnit' => 'item'],
                ['productSku' => 'sku03', 'productUnit' => 'item'],
                ['productSku' => 'sku03', 'productUnit' => 'set'],
            ]
        ];
        $allowedData = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => []];
        $errorMessageSkus = 'sku01, sku02, sku03';

        $this->expectsFilterData($allowedData);

        $this->storage->expects(self::once())
            ->method('set')
            ->with($allowedData);

        $this->expectsAddFlashMessage($errorMessageSkus);

        if ($hasRedirectRoute) {
            $this->processor->setRedirectRouteName('route');
        }
        $this->urlGenerator->expects($this->never())
            ->method('generate');

        self::assertNull($this->processor->process($data, new Request()));
    }

    /**
     * @dataProvider hasRedirectRouteDataProvider
     */
    public function testProcessAllAllowed(bool $hasRedirectRoute): void
    {
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                ['productSku' => 'sku01', 'productUnit' => 'item'],
                ['productSku' => 'sku02', 'productUnit' => 'item'],
                ['productSku' => 'sku02', 'productUnit' => 'set'],
                ['productSku' => 'sku03', 'productUnit' => 'item'],
            ]
        ];
        $allowedData = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                ['productSku' => 'sku01', 'productUnit' => 'item', 'productId' => 1],
                ['productSku' => 'sku02', 'productUnit' => 'item', 'productId' => 2],
                ['productSku' => 'sku02', 'productUnit' => 'set', 'productId' => 2],
                ['productSku' => 'sku03', 'productUnit' => 'item', 'productId' => 3],
            ]
        ];

        $this->expectsFilterData($allowedData);

        $this->storage->expects(self::once())
            ->method('set')
            ->with($allowedData);

        $this->translator->expects(self::never())
            ->method('trans');

        $this->session->expects(self::never())
            ->method('getFlashBag');

        if ($hasRedirectRoute) {
            $redirectUrl = 'url';
            $this->expectsGenerateRedirectUrl('route', $redirectUrl);

            $response = $this->processor->process($data, new Request());

            self::assertInstanceOf(RedirectResponse::class, $response);
            self::assertEquals($redirectUrl, $response->getTargetUrl());
        } else {
            self::assertNull($this->processor->process($data, new Request()));
        }
    }

    public function hasRedirectRouteDataProvider(): array
    {
        return [[true], [false]];
    }

    private function expectsFilterData(array $allowedData): void
    {
        $this->productMapper->expects(self::once())
            ->method('mapProducts')
            ->willReturnCallback(function (ArrayCollection $collection) use ($allowedData) {
                $collection->clear();
                foreach ($allowedData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataItem) {
                    $collection->add(new \ArrayObject($dataItem));
                }
            });
    }

    private function expectsAddFlashMessage(string $errorMessageSkus): void
    {
        $translatedMessage = 'translated not_added_products message';
        $this->translator->expects(self::any())
            ->method('trans')
            ->with(
                'oro.product.frontend.quick_add.messages.not_added_products',
                ['%count%' => count(explode(', ', $errorMessageSkus)), '%sku%' => $errorMessageSkus]
            )
            ->willReturn($translatedMessage);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $flashBag->expects(self::once())
            ->method('add')
            ->with('warning', $translatedMessage);
    }

    private function expectsGenerateRedirectUrl(string $redirectRouteName, string $redirectUrl): void
    {
        $this->processor->setRedirectRouteName($redirectRouteName);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                $redirectRouteName,
                [ProductDataStorage::STORAGE_KEY => true],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($redirectUrl);
    }
}
