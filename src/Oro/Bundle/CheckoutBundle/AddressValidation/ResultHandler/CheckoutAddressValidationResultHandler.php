<?php

namespace Oro\Bundle\CheckoutBundle\AddressValidation\ResultHandler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\AddressValidationResultHandler\AddressValidationResultHandlerInterface;
use Oro\Bundle\AddressValidationBundle\Model\AddressValidatedAtAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\AddressBookAwareInterface;
use Oro\Bundle\CustomerBundle\Utils\AddressBookAddressUtils;
use Oro\Bundle\CustomerBundle\Utils\AddressCopier;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Handles the address validation result form.
 * Updates the related address in an address book.
 */
class CheckoutAddressValidationResultHandler implements AddressValidationResultHandlerInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AddressCopier $addressCopier
    ) {
    }

    public function handleAddressValidationRequest(FormInterface $addressValidationResultForm, Request $request): void
    {
        $formConfig = $addressValidationResultForm->getConfig();
        $suggestedAddresses = $formConfig->getOption('suggested_addresses');

        if (!count($suggestedAddresses)) {
            $addressValidationResultForm->submit(['address' => '0']);
        } else {
            $addressValidationResultForm->handleRequest($request);
        }

        if (!$addressValidationResultForm->isSubmitted() || !$addressValidationResultForm->isValid()) {
            return;
        }

        $addressValidationResult = $addressValidationResultForm->getData();
        $originalAddress = $formConfig->getOption('original_address');

        $this->handleSelectedAddress(
            $addressValidationResult['address'],
            $originalAddress,
            $addressValidationResult['update_address'] ?? false
        );
    }

    private function handleSelectedAddress(
        AbstractAddress&AddressValidatedAtAwareInterface&AddressBookAwareInterface $selectedAddress,
        AbstractAddress&AddressValidatedAtAwareInterface&AddressBookAwareInterface $originalAddress,
        bool $saveAddressBookAddress
    ): void {
        $addressBookAddress = AddressBookAddressUtils::getAddressBookAddress($originalAddress);

        $selectedAddress->setValidatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $doFlush = false;
        if ($addressBookAddress !== null) {
            if ($selectedAddress === $originalAddress) {
                if ($this->authorizationChecker->isGranted(BasicPermission::EDIT, $addressBookAddress)) {
                    $addressBookAddress->setValidatedAt(clone $selectedAddress->getValidatedAt());

                    $doFlush = true;
                } else {
                    AddressBookAddressUtils::resetAddressBookAddress($selectedAddress);
                }
            } elseif ($saveAddressBookAddress === true) {
                /** @var AddressBookAwareInterface $selectedAddress */
                AddressBookAddressUtils::setAddressBookAddress($selectedAddress, $addressBookAddress);

                $this->addressCopier->copyToAddress($selectedAddress, $addressBookAddress);

                $doFlush = true;
            } else {
                // Nothing to do.
            }
        } else {
            AddressBookAddressUtils::resetAddressBookAddress($selectedAddress);
        }

        if ($doFlush === true) {
            $entityManager = $this->doctrine->getManagerForClass($addressBookAddress::class);
            $entityManager->flush();
        }
    }
}
