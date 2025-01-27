<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\AddressValidation\ResultHandler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\AddressValidation\ResultHandler\CheckoutAddressValidationResultHandler;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Utils\AddressBookAddressUtils;
use Oro\Bundle\CustomerBundle\Utils\AddressCopier;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CheckoutAddressValidationResultHandlerTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;

    private AddressCopier&MockObject $addressCopier;

    private CheckoutAddressValidationResultHandler $handler;

    private EntityManager&MockObject $entityManager;

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->addressCopier = $this->createMock(AddressCopier::class);

        $this->handler = new CheckoutAddressValidationResultHandler(
            $doctrine,
            $this->authorizationChecker,
            $this->addressCopier
        );

        $this->entityManager = $this->createMock(EntityManager::class);
        $doctrine
            ->method('getManagerForClass')
            ->with(CustomerUserAddress::class)
            ->willReturn($this->entityManager);
    }

    public function testDoesNothingWhenFormNotValidAndNoSuggestions(): void
    {
        $request = new Request();
        $originalAddress = new OrderAddress();
        $suggestedAddresses = [];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('submit')
            ->with(['address' => '0']);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->addressCopier
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);
    }

    public function testDoesNothingWhenFormNotValidAndHasSuggestions(): void
    {
        $request = new Request();
        $originalAddress = new OrderAddress();
        $suggestedAddresses = [new OrderAddress()];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('submit');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->addressCopier
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);
    }

    public function testSetsOnlyValidatedAtWhenNoAddressBookAddress(): void
    {
        $request = new Request();
        $selectedAddress = $originalAddress = new OrderAddress();
        $suggestedAddresses = [];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('submit')
            ->with(['address' => '0']);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('handleRequest');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['address' => $selectedAddress]);

        $this->addressCopier
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($selectedAddress->getValidatedAt());

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);

        self::assertInstanceOf(\DateTimeInterface::class, $selectedAddress->getValidatedAt());
    }

    /**
     * @dataProvider addressBookAddressDataProvider
     */
    public function testResetsAddressBookAddress(
        CustomerAddress|CustomerUserAddress $addressBookAddress
    ): void {
        $request = new Request();
        $originalAddress = new OrderAddress();
        $selectedAddress = new OrderAddress();
        AddressBookAddressUtils::setAddressBookAddress($selectedAddress, $addressBookAddress);

        $suggestedAddresses = [$selectedAddress];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('submit');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['address' => $selectedAddress]);

        $this->addressCopier
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        self::assertNotNull(AddressBookAddressUtils::getAddressBookAddress($selectedAddress));

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);

        self::assertNull(AddressBookAddressUtils::getAddressBookAddress($selectedAddress));
    }

    public function addressBookAddressDataProvider(): \Generator
    {
        yield [
            new CustomerUserAddress(),
        ];

        yield [
            new CustomerAddress(),
        ];
    }

    public function testNotUpdatesValidatedAtWhenOriginalAddressSelectedAndUpdateIsNotGranted(): void
    {
        $request = new Request();
        $originalAddress = $selectedAddress = new OrderAddress();
        $addressBookAddress = new CustomerUserAddress();
        AddressBookAddressUtils::setAddressBookAddress($originalAddress, $addressBookAddress);
        $suggestedAddresses = [];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('submit')
            ->with(['address' => '0']);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('handleRequest');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['address' => $selectedAddress]);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::EDIT, $addressBookAddress)
            ->willReturn(false);

        $this->addressCopier
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($selectedAddress->getValidatedAt());

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);

        self::assertInstanceOf(\DateTimeInterface::class, $selectedAddress->getValidatedAt());
    }

    public function testResetsAddressBookRelationWhenOriginalAddressSelectedAndUpdateIsNotGranted(): void
    {
        $request = new Request();
        $originalAddress = $selectedAddress = new OrderAddress();
        $addressBookAddress = new CustomerUserAddress();
        AddressBookAddressUtils::setAddressBookAddress($originalAddress, $addressBookAddress);
        $suggestedAddresses = [];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('submit')
            ->with(['address' => '0']);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('handleRequest');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['address' => $selectedAddress]);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::EDIT, $addressBookAddress)
            ->willReturn(false);

        $this->addressCopier
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($selectedAddress->getValidatedAt());
        self::assertNotNull(AddressBookAddressUtils::getAddressBookAddress($selectedAddress));

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);

        self::assertInstanceOf(\DateTimeInterface::class, $selectedAddress->getValidatedAt());
        self::assertNull(AddressBookAddressUtils::getAddressBookAddress($selectedAddress));
    }

    public function testUpdatesValidatedAtWhenOriginalAddressSelectedAndUpdateIsGranted(): void
    {
        $request = new Request();
        $originalAddress = $selectedAddress = new OrderAddress();
        $addressBookAddress = new CustomerUserAddress();
        AddressBookAddressUtils::setAddressBookAddress($originalAddress, $addressBookAddress);
        $suggestedAddresses = [];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('submit')
            ->with(['address' => '0']);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('handleRequest');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['address' => $selectedAddress]);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::EDIT, $addressBookAddress)
            ->willReturn(true);

        $this->addressCopier
            ->expects(self::never())
            ->method(self::anything());

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        self::assertNull($selectedAddress->getValidatedAt());
        self::assertNull($addressBookAddress->getValidatedAt());

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);

        self::assertInstanceOf(\DateTimeInterface::class, $selectedAddress->getValidatedAt());
        self::assertEquals($selectedAddress->getValidatedAt(), $addressBookAddress->getValidatedAt());
    }

    public function testUpdatesAddressBookAddressWhenSuggestedAddressSelectedAndUpdateIsChecked(): void
    {
        $request = new Request();
        $originalAddress = (new OrderAddress())
            ->setLabel('Original Address');
        $addressBookAddress = new CustomerUserAddress();
        AddressBookAddressUtils::setAddressBookAddress($originalAddress, $addressBookAddress);
        $selectedAddress = (new OrderAddress())
            ->setLabel('Selected Address');
        $suggestedAddresses = [$selectedAddress];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('submit');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['address' => $selectedAddress, 'update_address' => true]);

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->addressCopier
            ->expects(self::once())
            ->method('copyToAddress')
            ->with($selectedAddress, $addressBookAddress);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        self::assertNull($selectedAddress->getValidatedAt());

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);

        self::assertInstanceOf(\DateTimeInterface::class, $selectedAddress->getValidatedAt());
    }

    public function testNotUpdatesAddressBookAddressWhenSuggestedAddressSelectedAndUpdateIsNotChecked(): void
    {
        $request = new Request();
        $originalAddress = (new OrderAddress())
            ->setLabel('Original Address');
        $addressBookAddress = new CustomerUserAddress();
        AddressBookAddressUtils::setAddressBookAddress($originalAddress, $addressBookAddress);
        $selectedAddress = (new OrderAddress())
            ->setLabel('Selected Address');
        $suggestedAddresses = [$selectedAddress];

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig
            ->method('getOption')
            ->willReturnMap([
                ['suggested_addresses', null, $suggestedAddresses],
                ['original_address', null, $originalAddress],
            ]);

        $addressValidationResultForm = $this->createMock(FormInterface::class);
        $addressValidationResultForm
            ->method('getConfig')
            ->willReturn($formConfig);

        $addressValidationResultForm
            ->expects(self::never())
            ->method('submit');

        $addressValidationResultForm
            ->expects(self::once())
            ->method('handleRequest')
            ->with($request);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $addressValidationResultForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(['address' => $selectedAddress, 'update_address' => false]);

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        $this->addressCopier
            ->expects(self::never())
            ->method('copyToAddress');

        $this->entityManager
            ->expects(self::never())
            ->method('flush');

        self::assertNull($selectedAddress->getValidatedAt());

        $this->handler->handleAddressValidationRequest($addressValidationResultForm, $request);

        self::assertInstanceOf(\DateTimeInterface::class, $selectedAddress->getValidatedAt());
    }
}
