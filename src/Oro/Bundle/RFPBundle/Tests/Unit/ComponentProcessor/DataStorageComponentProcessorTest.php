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
    /**
     * @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $router;

    /**
     * @var ProductDataStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storage;

    /**
     * @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $authorizationChecker;

    /**
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenAccessor;

    /**
     * @var Session|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $session;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var RequestDataStorageExtension|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestDataStorageExtension;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @var DataStorageComponentProcessor
     */
    protected $processor;

    protected function setUp(): void
    {
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->storage = $this->getMockBuilder(ProductDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->requestDataStorageExtension = $this->getMockBuilder(RequestDataStorageExtension::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    protected function tearDown(): void
    {
        unset(
            $this->router,
            $this->storage,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->session,
            $this->translator,
            $this->requestDataStorageExtension,
            $this->processor,
            $this->featureChecker
        );
    }

    public function testProcessNotAllowedRFP()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request **/
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FlashBag|\PHPUnit\Framework\MockObject\MockObject $flashBag **/
        $flashBag = $this->getMockBuilder(FlashBag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flashBag->expects($this->once())->method('add');

        $this->requestDataStorageExtension->expects($this->once())->method('isAllowedRFP')->willReturn(false);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($flashBag);

        $this->assertNull($this->processor->process($data, $request));
    }

    public function testProcessAllowedRFP()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request **/
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestDataStorageExtension->expects($this->once())->method('isAllowedRFP')->willReturn(true);

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
            ->will($this->returnValue($token));

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_rfp')
            ->will($this->returnValue(true));

        $this->assertEquals(true, $this->processor->isAllowedForGuest());
    }
}
