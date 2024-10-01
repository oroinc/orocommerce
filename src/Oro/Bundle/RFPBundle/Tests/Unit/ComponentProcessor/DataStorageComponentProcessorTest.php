<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\ComponentProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\ComponentProcessor\DataStorageComponentProcessor;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

    /** @var ProductMapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productMapper;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ProductRFPAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productAvailabilityProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var DataStorageComponentProcessor */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->productMapper = $this->createMock(ProductMapperInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->productAvailabilityProvider = $this->createMock(ProductRFPAvailabilityProvider::class);
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
            $this->productMapper,
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->tokenAccessor,
            $requestStack,
            $translator,
            $this->urlGenerator,
            $this->productAvailabilityProvider,
            $this->featureChecker
        );
        $this->processor->setRedirectRouteName('route');
    }

    private function expectsFilterData(array $processedData): void
    {
        $this->productMapper->expects(self::once())
            ->method('mapProducts')
            ->willReturnCallback(function (ArrayCollection $collection) use ($processedData) {
                $collection->clear();
                foreach ($processedData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataItem) {
                    $collection->add(new \ArrayObject($dataItem));
                }
            });

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with(
                'route',
                [ProductDataStorage::STORAGE_KEY => true],
                UrlGeneratorInterface::ABSOLUTE_PATH
            )
            ->willReturn('url');
    }

    public function testProcessWhenRfpNotAllowed(): void
    {
        $initialStoredData = ['key' => 'value'];
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];
        $processedData = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01', 'productId' => 1]]];
        $request = $this->createMock(Request::class);

        $this->expectsFilterData($processedData);

        $this->storage->expects(self::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($initialStoredData, $processedData);
        $this->storage->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive([$processedData], [$initialStoredData]);

        $this->productAvailabilityProvider->expects(self::once())
            ->method('hasProductsAllowedForRFP')
            ->with([1])
            ->willReturn(false);

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
        $processedData = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01', 'productId' => 1]]];
        $request = $this->createMock(Request::class);

        $this->expectsFilterData($processedData);

        $this->storage->expects(self::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls([], $processedData);
        $this->storage->expects(self::once())
            ->method('set')
            ->with($processedData);

        $this->productAvailabilityProvider->expects(self::once())
            ->method('hasProductsAllowedForRFP')
            ->with([1])
            ->willReturn(true);

        $this->session->expects(self::never())
            ->method('getFlashBag');

        self::assertInstanceOf(RedirectResponse::class, $this->processor->process($data, $request));
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
