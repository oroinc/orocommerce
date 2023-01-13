<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ComponentProcessor;

use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilter;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorFilterInterface;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataStorageAwareComponentProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var ProductDataStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ComponentProcessorFilter|\PHPUnit\Framework\MockObject\MockObject */
    private $componentProcessorFilter;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DataStorageAwareComponentProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->componentProcessorFilter = $this->createMock(ComponentProcessorFilterInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->processor = new DataStorageAwareComponentProcessor(
            $this->router,
            $this->storage,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->session,
            $this->translator
        );
        $this->processor->setComponentProcessorFilter($this->componentProcessorFilter);
    }

    public function testProcessWithoutRedirectRoute()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => ['param' => 42]];
        $this->componentProcessorFilter->expects($this->any())
            ->method('filterData')
            ->willReturnArgument(0);

        $this->router->expects($this->never())
            ->method($this->anything());

        $this->storage->expects($this->once())
            ->method('set')
            ->with($data);

        $this->assertNull($this->processor->process($data, new Request()));
    }

    public function testProcessWithRedirectRoute()
    {
        $this->componentProcessorFilter->expects($this->any())
            ->method('filterData')
            ->willReturnArgument(0);
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => ['param' => 42]];
        $redirectRouteName = 'redirect_route';
        $redirectUrl = '/redirect/url';
        $expectedResponseContent = (new RedirectResponse($redirectUrl))->getContent();

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $redirectRouteName,
                [ProductDataStorage::STORAGE_KEY => true],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn($redirectUrl);

        $this->storage->expects($this->once())
            ->method('set')
            ->with($data);

        $this->processor->setRedirectRouteName($redirectRouteName);

        $this->assertEquals(
            $expectedResponseContent,
            $this->processor->process($data, new Request())->getContent()
        );
    }

    public function testProcessorName()
    {
        $name = 'test_name';

        $this->assertNull($this->processor->getName());

        $this->processor->setName($name);

        $this->assertEquals($name, $this->processor->getName());
    }

    /**
     * @dataProvider processorIsAllowedProvider
     */
    public function testProcessorIsAllowed(?string $acl, bool $isGranted, bool $hasLoggedUser, bool $expected)
    {
        if (null !== $acl) {
            $this->tokenAccessor->expects($this->any())
                ->method('hasUser')
                ->willReturn($hasLoggedUser);
            $this->authorizationChecker->expects($this->any())
                ->method('isGranted')
                ->with($acl)->willReturn($isGranted);
        }

        $this->processor->setAcl($acl);
        $this->assertEquals($expected, $this->processor->isAllowed());
    }

    public function processorIsAllowedProvider(): array
    {
        return [
            [null, true, false, true],
            ['fail', false, false, false],
            ['fail', true, false, false],
            ['fail', false, true, false],
            ['success', true, true, true],
        ];
    }

    public function testValidationRequired()
    {
        $this->assertTrue($this->processor->isValidationRequired());

        $this->processor->setValidationRequired(false);

        $this->assertFalse($this->processor->isValidationRequired());
    }

    /**
     * @dataProvider processorWithScopeDataProvider
     */
    public function testProcessorWithScope(
        string $scope,
        array $data,
        array $allowedData,
        string $errorMessageSkus,
        bool $isRedirectRoute = false
    ) {
        $this->setupProcessorScope($scope, $data, $allowedData);

        $this->setupErrorMessages($errorMessageSkus);

        if ($isRedirectRoute) {
            $this->assertProcessorReturnRedirectResponse($data);
        } else {
            $this->assertNull($this->processor->process($data, new Request()));
        }
    }

    public function processorWithScopeDataProvider(): array
    {
        return [
            'restricted several with redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                    ],
                ],
                'errorMessageSkus' => 'sku02, sku03',
                'isRedirectRoute' => true,
            ],
            'restricted one with redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                    ],
                ],
                'errorMessageSkus' => 'sku03',
                'isRedirectRoute' => true,
            ],
            'restricted several without redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                    ],
                ],
                'errorMessageSkus' => 'sku02, sku03',
            ],
            'restricted one without redirect' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'allowedData' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                    ],
                ],
                'errorMessageSkus' => 'sku03',
            ],
        ];
    }

    /**
     * @dataProvider processorWithScopeAllRestricted
     */
    public function testProcessorWithScopeAllRestricted(
        string $scope,
        array $data,
        string $errorMessageSkus,
        bool $isRedirectRoute = false
    ) {
        $filteredData = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => []];

        $this->componentProcessorFilter->expects($this->any())
            ->method('filterData')
            ->willReturn($filteredData);

        $this->setupProcessorScope($scope, $data, $filteredData);

        $this->setupErrorMessages($errorMessageSkus);

        if ($isRedirectRoute) {
            $this->processor->setRedirectRouteName('route');
        }
        $this->router->expects($this->never())
            ->method('generate');

        $this->assertNull($this->processor->process($data, new Request()));
    }

    public function processorWithScopeAllRestricted(): array
    {
        return [
            'with redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'errorMessageSkus' => 'sku01, sku02, sku03',
                'isRedirectRoute' => true,
            ],
            'without redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'errorMessageSkus' => 'sku01, sku02, sku03',
            ],
        ];
    }

    /**
     * @dataProvider processorWithScopeAllAllowedDataProvider
     */
    public function testProcessorWithScopeAllAllowed(string $scope, array $data, bool $isRedirectRoute = false)
    {
        $this->setupProcessorScope($scope, $data, $data);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->session->expects($this->never())
            ->method('getFlashBag');

        if ($isRedirectRoute) {
            $this->assertProcessorReturnRedirectResponse($data);
        } else {
            $this->assertNull($this->processor->process($data, new Request()));
        }
    }

    public function processorWithScopeAllAllowedDataProvider(): array
    {
        return [
            'with redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
                'isRedirectRoute' => true,
            ],
            'without redirect route' => [
                'scope' => 'test',
                'data' => [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku01'],
                        ['productSku' => 'sku02'],
                        ['productSku' => 'sku03'],
                    ],
                ],
            ],
        ];
    }

    private function setupProcessorScope(string $scope, array $data, array $allowedData): void
    {
        $this->componentProcessorFilter->expects($this->once())
            ->method('filterData')
            ->with($data, ['scope' => $scope])
            ->willReturn($allowedData);

        $this->storage->expects($this->once())
            ->method('set')
            ->with($allowedData);

        $this->processor->setScope($scope);
    }

    private function setupErrorMessages(string $errorMessageSkus): void
    {
        $this->translator->expects($this->any())
            ->method('trans')
            ->with(
                'oro.product.frontend.quick_add.messages.not_added_products',
                ['%count%' => count(explode(', ', $errorMessageSkus)), '%sku%' => $errorMessageSkus]
            );

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('warning');

        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
    }

    private function assertProcessorReturnRedirectResponse(array $data): void
    {
        $redirectRoute = 'route';
        $targetUrl = 'url';

        $this->processor->setRedirectRouteName($redirectRoute);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($redirectRoute, [ProductDataStorage::STORAGE_KEY => true])
            ->willReturn($targetUrl);

        $response = $this->processor->process($data, new Request());

        $this->assertNotNull($response);
        $this->assertEquals($targetUrl, $response->getTargetUrl());
    }
}
