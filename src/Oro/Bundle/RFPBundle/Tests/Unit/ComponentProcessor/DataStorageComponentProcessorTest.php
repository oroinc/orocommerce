<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\ComponentProcessor;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\ComponentProcessor\DataStorageComponentProcessor;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataStorageComponentProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductDataStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var ProductAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productAvailabilityProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var DataStorageComponentProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->productAvailabilityProvider = $this->createMock(ProductAvailabilityProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::any())
            ->method('getSession')
            ->willReturn($this->session);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        $this->processor = new DataStorageComponentProcessor(
            $this->storage,
            $this->productRepository,
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->tokenAccessor,
            $requestStack,
            $translator,
            $this->createMock(UrlGeneratorInterface::class),
            $this->productAvailabilityProvider,
            $this->featureChecker
        );
    }

    public function testProcessWhenRfpNotAllowed(): void
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];
        $request = $this->createMock(Request::class);

        $this->productAvailabilityProvider->expects(self::once())
            ->method('hasProductsAllowedForRFPByProductData')
            ->with($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
            ->willReturn(false);

        $this->productRepository->expects(self::never())
            ->method('getFilterSkuQuery');

        $this->storage->expects(self::never())
            ->method('set');

        $flashBag = $this->createMock(FlashBag::class);
        $this->session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $flashBag->expects(self::once())
            ->method('add')
            ->with('warning', 'oro.frontend.rfp.data_storage.no_products_be_added_to_rfq (translated)');

        self::assertNull($this->processor->process($data, $request));
    }

    public function testProcessWhenRfpAllowed(): void
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];
        $request = $this->createMock(Request::class);

        $this->productAvailabilityProvider->expects(self::once())
            ->method('hasProductsAllowedForRFPByProductData')
            ->with($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
            ->willReturn(true);

        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $this->productRepository->expects(self::once())
            ->method('getFilterSkuQuery')
            ->with(['SKU01'])
            ->willReturn($searchQuery);
        $searchResult = $this->createMock(SearchResult::class);
        $searchQuery->expects(self::once())
            ->method('getResult')
            ->willReturn($searchResult);
        $searchResult->expects(self::once())
            ->method('toArray')
            ->willReturn([new SearchResultItem('product', 1, '/product/1', ['sku' => 'sku01'])]);

        $this->storage->expects(self::once())
            ->method('set')
            ->with($data);

        $this->session->expects(self::never())
            ->method('getFlashBag');

        self::assertNull($this->processor->process($data, $request));
    }

    public function testIsAllowedForGuestWhenNoSecurityToken(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertFalse($this->processor->isAllowedForGuest());
    }

    public function testIsAllowedForGuestWhenSecurityTokenIsNotAnonymousCustomerUserToken(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        self::assertFalse($this->processor->isAllowedForGuest());
    }

    public function testIsAllowedForGuestWhenItIsNotAllowed(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousCustomerUserToken::class));

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('guest_rfp')
            ->willReturn(false);

        self::assertFalse($this->processor->isAllowedForGuest());
    }

    public function testIsAllowedForGuestWhenItIsAllowed(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousCustomerUserToken::class));

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('guest_rfp')
            ->willReturn(true);

        self::assertTrue($this->processor->isAllowedForGuest());
    }
}
