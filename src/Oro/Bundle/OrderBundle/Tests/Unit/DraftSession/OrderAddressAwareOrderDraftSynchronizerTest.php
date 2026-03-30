<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\DraftSession\OrderAddressAwareOrderDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Unit\Stub\OrderStub;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderAddressAwareOrderDraftSynchronizerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private OrderAddressAwareOrderDraftSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $referenceResolver = new EntityDraftSyncReferenceResolver($this->doctrine);

        $this->synchronizer = new OrderAddressAwareOrderDraftSynchronizer($referenceResolver);
    }

    public function testSupportsOrderClass(): void
    {
        self::assertTrue($this->synchronizer->supports(Order::class));
    }

    public function testDoesNotSupportOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(\stdClass::class));
    }

    public function testSynchronizeFromDraftCopiesBillingAddress(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $country = new Country('US');
        $region = new Region('US-CA');
        $customerAddress = new CustomerAddress();
        ReflectionUtil::setId($customerAddress, 1);

        $sourceAddress = new OrderAddress();
        $sourceAddress->setLabel('Office');
        $sourceAddress->setOrganization('Acme Inc');
        $sourceAddress->setNamePrefix('Mr.');
        $sourceAddress->setFirstName('John');
        $sourceAddress->setMiddleName('M');
        $sourceAddress->setLastName('Doe');
        $sourceAddress->setNameSuffix('Jr.');
        $sourceAddress->setStreet('123 Main St');
        $sourceAddress->setStreet2('Suite 100');
        $sourceAddress->setCity('Los Angeles');
        $sourceAddress->setRegion($region);
        $sourceAddress->setRegionText('California');
        $sourceAddress->setPostalCode('90001');
        $sourceAddress->setCountry($country);
        $sourceAddress->setPhone('+1-555-123-4567');
        $sourceAddress->setCustomerAddress($customerAddress);
        $sourceAddress->setFromExternalSource(true);
        $sourceAddress->setValidatedAt(new \DateTime('2026-01-15'));

        $draft = new OrderStub();
        $draft->setBillingAddress($sourceAddress);

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $targetAddress = $entity->getBillingAddress();
        self::assertNotNull($targetAddress);
        self::assertEquals('Office', $targetAddress->getLabel());
        self::assertEquals('Acme Inc', $targetAddress->getOrganization());
        self::assertEquals('Mr.', $targetAddress->getNamePrefix());
        self::assertEquals('John', $targetAddress->getFirstName());
        self::assertEquals('M', $targetAddress->getMiddleName());
        self::assertEquals('Doe', $targetAddress->getLastName());
        self::assertEquals('Jr.', $targetAddress->getNameSuffix());
        self::assertEquals('123 Main St', $targetAddress->getStreet());
        self::assertEquals('Suite 100', $targetAddress->getStreet2());
        self::assertEquals('Los Angeles', $targetAddress->getCity());
        self::assertSame($region, $targetAddress->getRegion());
        self::assertEquals('California', $targetAddress->getRegionText());
        self::assertEquals('90001', $targetAddress->getPostalCode());
        self::assertSame($country, $targetAddress->getCountry());
        self::assertEquals('+1-555-123-4567', $targetAddress->getPhone());
        self::assertSame($customerAddress, $targetAddress->getCustomerAddress());
        self::assertTrue($targetAddress->isFromExternalSource());
        self::assertNotNull($targetAddress->getValidatedAt());
    }

    public function testSynchronizeFromDraftCopiesShippingAddress(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $sourceAddress = new OrderAddress();
        $sourceAddress->setFirstName('Jane');
        $sourceAddress->setLastName('Smith');
        $sourceAddress->setStreet('456 Oak Ave');
        $sourceAddress->setCity('San Francisco');

        $draft = new OrderStub();
        $draft->setShippingAddress($sourceAddress);

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $targetAddress = $entity->getShippingAddress();
        self::assertNotNull($targetAddress);
        self::assertEquals('Jane', $targetAddress->getFirstName());
        self::assertEquals('Smith', $targetAddress->getLastName());
        self::assertEquals('456 Oak Ave', $targetAddress->getStreet());
        self::assertEquals('San Francisco', $targetAddress->getCity());
    }

    public function testSynchronizeFromDraftUpdatesExistingTargetAddress(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $sourceAddress = new OrderAddress();
        $sourceAddress->setFirstName('Updated');
        $sourceAddress->setStreet('999 New St');

        $existingAddress = new OrderAddress();
        $existingAddress->setFirstName('Old');
        $existingAddress->setStreet('111 Old St');

        $draft = new OrderStub();
        $draft->setBillingAddress($sourceAddress);

        $entity = new OrderStub();
        $entity->setBillingAddress($existingAddress);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($existingAddress, $entity->getBillingAddress());
        self::assertEquals('Updated', $existingAddress->getFirstName());
        self::assertEquals('999 New St', $existingAddress->getStreet());
    }

    public function testSynchronizeFromDraftSkipsNullSourceAddresses(): void
    {
        $draft = new OrderStub();
        // No addresses set

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertNull($entity->getBillingAddress());
        self::assertNull($entity->getShippingAddress());
    }

    public function testSynchronizeFromDraftClearsValidatedAtWhenSourceIsNull(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $sourceAddress = new OrderAddress();
        $sourceAddress->setFirstName('Test');
        // validatedAt is null by default

        $existingAddress = new OrderAddress();
        $existingAddress->setValidatedAt(new \DateTime('2025-01-01'));

        $draft = new OrderStub();
        $draft->setBillingAddress($sourceAddress);

        $entity = new OrderStub();
        $entity->setBillingAddress($existingAddress);

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertNull($existingAddress->getValidatedAt());
    }

    public function testSynchronizeFromDraftClonesValidatedAt(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $validatedAt = new \DateTime('2026-03-01 10:00:00');

        $sourceAddress = new OrderAddress();
        $sourceAddress->setValidatedAt($validatedAt);

        $draft = new OrderStub();
        $draft->setBillingAddress($sourceAddress);

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        $targetValidatedAt = $entity->getBillingAddress()->getValidatedAt();
        self::assertNotNull($targetValidatedAt);
        self::assertEquals($validatedAt, $targetValidatedAt);
        self::assertNotSame($validatedAt, $targetValidatedAt);
    }

    public function testSynchronizeFromDraftCopiesCustomerUserAddress(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $customerUserAddress = new CustomerUserAddress();
        ReflectionUtil::setId($customerUserAddress, 5);

        $sourceAddress = new OrderAddress();
        $sourceAddress->setCustomerUserAddress($customerUserAddress);

        $draft = new OrderStub();
        $draft->setBillingAddress($sourceAddress);

        $entity = new OrderStub();

        $this->synchronizer->synchronizeFromDraft($draft, $entity);

        self::assertSame($customerUserAddress, $entity->getBillingAddress()->getCustomerUserAddress());
    }

    public function testSynchronizeToDraftCopiesBillingAddress(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $country = new Country('DE');
        $region = new Region('DE-BY');

        $sourceAddress = new OrderAddress();
        $sourceAddress->setFirstName('Anna');
        $sourceAddress->setLastName('Müller');
        $sourceAddress->setStreet('Hauptstraße 1');
        $sourceAddress->setCity('Munich');
        $sourceAddress->setCountry($country);
        $sourceAddress->setRegion($region);
        $sourceAddress->setPostalCode('80331');

        $entity = new OrderStub();
        $entity->setBillingAddress($sourceAddress);

        $draft = new OrderStub();

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        $targetAddress = $draft->getBillingAddress();
        self::assertNotNull($targetAddress);
        self::assertEquals('Anna', $targetAddress->getFirstName());
        self::assertEquals('Müller', $targetAddress->getLastName());
        self::assertEquals('Hauptstraße 1', $targetAddress->getStreet());
        self::assertEquals('Munich', $targetAddress->getCity());
        self::assertSame($country, $targetAddress->getCountry());
        self::assertSame($region, $targetAddress->getRegion());
        self::assertEquals('80331', $targetAddress->getPostalCode());
    }

    public function testSynchronizeToDraftCopiesShippingAddress(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $sourceAddress = new OrderAddress();
        $sourceAddress->setFirstName('Bob');
        $sourceAddress->setStreet('Elm Street 5');

        $entity = new OrderStub();
        $entity->setShippingAddress($sourceAddress);

        $draft = new OrderStub();

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        $targetAddress = $draft->getShippingAddress();
        self::assertNotNull($targetAddress);
        self::assertEquals('Bob', $targetAddress->getFirstName());
        self::assertEquals('Elm Street 5', $targetAddress->getStreet());
    }

    public function testSynchronizeToDraftUpdatesExistingTargetAddress(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $sourceAddress = new OrderAddress();
        $sourceAddress->setFirstName('NewName');
        $sourceAddress->setCity('New City');

        $existingDraftAddress = new OrderAddress();
        $existingDraftAddress->setFirstName('OldName');
        $existingDraftAddress->setCity('Old City');

        $entity = new OrderStub();
        $entity->setBillingAddress($sourceAddress);

        $draft = new OrderStub();
        $draft->setBillingAddress($existingDraftAddress);

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        self::assertSame($existingDraftAddress, $draft->getBillingAddress());
        self::assertEquals('NewName', $existingDraftAddress->getFirstName());
        self::assertEquals('New City', $existingDraftAddress->getCity());
    }

    public function testSynchronizeToDraftSkipsNullSourceAddresses(): void
    {
        $entity = new OrderStub();
        // No addresses set

        $draft = new OrderStub();

        $this->synchronizer->synchronizeToDraft($entity, $draft);

        self::assertNull($draft->getBillingAddress());
        self::assertNull($draft->getShippingAddress());
    }
}
