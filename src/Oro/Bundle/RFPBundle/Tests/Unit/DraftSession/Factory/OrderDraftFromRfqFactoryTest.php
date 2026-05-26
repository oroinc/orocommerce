<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\DraftSession\Factory;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RFPBundle\DraftSession\Factory\OrderDraftFromRfqFactory;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Provider\RfqCurrencyProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderDraftFromRfqFactoryTest extends TestCase
{
    private EntityDraftSyncReferenceResolver&MockObject $draftSyncReferenceResolver;

    private RfqCurrencyProvider&MockObject $rfqCurrencyProvider;

    private WebsiteManager&MockObject $websiteManager;

    private OrderDraftFromRfqFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $this->rfqCurrencyProvider = $this->createMock(RfqCurrencyProvider::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->factory = new OrderDraftFromRfqFactory(
            $this->draftSyncReferenceResolver,
            $this->rfqCurrencyProvider,
            $this->websiteManager,
        );
    }

    public function testSupportsReturnsTrueForRequest(): void
    {
        self::assertTrue($this->factory->supports(Request::class));
    }

    public function testSupportsReturnsFalseForOtherClass(): void
    {
        self::assertFalse($this->factory->supports(Order::class));
    }

    public function testCreateDraftSynchronizesRequestFields(): void
    {
        $request = new Request();
        ReflectionUtil::setId($request, 42);

        $organization = new Organization();
        $organizationReference = new Organization();
        $request->setOrganization($organization);

        $customer = new Customer();
        $customerReference = new Customer();
        $request->setCustomer($customer);

        $customerUser = new CustomerUser();
        $customerUserReference = new CustomerUser();
        $request->setCustomerUser($customerUser);

        $website = new Website();
        $websiteReference = new Website();
        $request->setWebsite($website);

        $shipUntil = new \DateTime('2026-05-01');
        $request->setShipUntil($shipUntil);
        $request->setPoNumber('PO-0001');
        $request->setNote('RFQ note');

        $this->draftSyncReferenceResolver
            ->expects(self::exactly(4))
            ->method('getReference')
            ->willReturnMap([
                [$organization, $organizationReference],
                [$customer, $customerReference],
                [$customerUser, $customerUserReference],
                [$website, $websiteReference],
            ]);

        $this->websiteManager
            ->expects(self::never())
            ->method('getDefaultWebsite');

        $this->rfqCurrencyProvider
            ->expects(self::once())
            ->method('getRfqCurrency')
            ->with($request)
            ->willReturn('USD');

        $orderDraft = $this->factory->createDraft($request, 'draft-uuid');

        self::assertSame('draft-uuid', $orderDraft->getDraftSessionUuid());
        self::assertSame($organizationReference, $orderDraft->getOrganization());
        self::assertSame($customerReference, $orderDraft->getCustomer());
        self::assertSame($customerUserReference, $orderDraft->getCustomerUser());
        self::assertSame($websiteReference, $orderDraft->getWebsite());
        self::assertSame('PO-0001', $orderDraft->getPoNumber());
        self::assertSame('USD', $orderDraft->getCurrency());
        self::assertSame('RFQ note', $orderDraft->getCustomerNotes());
        self::assertSame(Request::class, $orderDraft->getSourceEntityClass());
        self::assertSame(42, $orderDraft->getSourceEntityId());
        self::assertSame('PO-0001', $orderDraft->getSourceEntityIdentifier());
        self::assertEquals($shipUntil, $orderDraft->getShipUntil());
        self::assertNotSame($shipUntil, $orderDraft->getShipUntil());
    }

    public function testCreateDraftUsesDefaultWebsiteWhenRequestWebsiteIsNull(): void
    {
        $request = new Request();
        ReflectionUtil::setId($request, 7);

        $customer = new Customer();
        $customerReference = new Customer();
        $request->setCustomer($customer);

        $customerUser = new CustomerUser();
        $customerUserReference = new CustomerUser();
        $request->setCustomerUser($customerUser);
        $request->setPoNumber('PO-0007');

        $defaultWebsite = new Website();
        $defaultWebsiteReference = new Website();

        $this->websiteManager
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($defaultWebsite);

        $this->draftSyncReferenceResolver
            ->expects(self::exactly(3))
            ->method('getReference')
            ->willReturnMap([
                [$customer, $customerReference],
                [$customerUser, $customerUserReference],
                [$defaultWebsite, $defaultWebsiteReference],
            ]);

        $this->rfqCurrencyProvider
            ->expects(self::once())
            ->method('getRfqCurrency')
            ->with($request)
            ->willReturn('EUR');

        $orderDraft = $this->factory->createDraft($request, 'fallback-website-uuid');

        self::assertSame($defaultWebsiteReference, $orderDraft->getWebsite());
        self::assertSame($customerReference, $orderDraft->getCustomer());
        self::assertSame($customerUserReference, $orderDraft->getCustomerUser());
        self::assertSame('EUR', $orderDraft->getCurrency());
    }

    public function testCreateDraftSkipsOptionalFieldsWhenMissing(): void
    {
        $request = new Request();
        $request->setPoNumber('PO-NO-WEBSITE');

        $this->websiteManager
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn(null);

        $this->draftSyncReferenceResolver
            ->expects(self::exactly(2))
            ->method('getReference')
            ->withConsecutive([null], [null])
            ->willReturn(null);

        $this->rfqCurrencyProvider
            ->expects(self::once())
            ->method('getRfqCurrency')
            ->with($request)
            ->willReturn('GBP');

        $orderDraft = $this->factory->createDraft($request, 'no-optional-fields-uuid');

        self::assertNull($orderDraft->getOrganization());
        self::assertNull($orderDraft->getCustomer());
        self::assertNull($orderDraft->getCustomerUser());
        self::assertNull($orderDraft->getWebsite());
        self::assertNull($orderDraft->getShipUntil());
        self::assertNull($orderDraft->getCustomerNotes());
        self::assertSame('GBP', $orderDraft->getCurrency());
    }
}
