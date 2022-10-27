<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\ComponentProcessor;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\ComponentProcessor\DataStorageComponentProcessor;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataStorageComponentProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var ProductDataStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var RequestDataStorageExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $requestDataStorageExtension;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var DataStorageComponentProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->requestDataStorageExtension = $this->createMock(RequestDataStorageExtension::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new DataStorageComponentProcessor(
            $this->router,
            $this->storage,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->session,
            $this->translator,
            $this->requestDataStorageExtension,
            $this->featureChecker
        );
    }

    public function testProcessNotAllowedRFP()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];

        $request = $this->createMock(Request::class);

        $flashBag = $this->createMock(FlashBag::class);

        $flashBag->expects($this->once())
            ->method('add');

        $this->requestDataStorageExtension->expects($this->once())
            ->method('isAllowedRFP')
            ->willReturn(false);
        $this->session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->assertNull($this->processor->process($data, $request));
    }

    public function testProcessAllowedRFP()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];

        $request = $this->createMock(Request::class);

        $this->requestDataStorageExtension->expects($this->once())
            ->method('isAllowedRFP')
            ->willReturn(true);

        $this->assertNull($this->processor->process($data, $request));
    }

    public function testNotAllowedForGuest()
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled')
            ->with('guest_rfp');

        $this->assertEquals(false, $this->processor->isAllowedForGuest());
    }

    public function testAllowedForGuest()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_rfp')
            ->willReturn(true);

        $this->assertEquals(true, $this->processor->isAllowedForGuest());
    }
}
