<?php

namespace OroB2B\Bundle\AccountBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;

/**
 * When address is created/updated from single form, it will ensure the rules of one address has default mark per type
 */
class FixAccountAddressesDefaultSubscriber implements EventSubscriberInterface
{
    /**
     * Property path to collection of all addresses
     * (e.g. 'frontendOwner.address' means $address->getFrontendOwner()->getAddresses())
     *
     * @var string
     */
    protected $addressesProperty;

    /**
     * @var PropertyAccessor
     */
    private $addressAccess;

    /**
     * @param string $addressesProperty
     */
    public function __construct($addressesProperty)
    {
        $this->addressesProperty = $addressesProperty;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit'
        ];
    }

    /**
     * @return PropertyAccessor
     */
    protected function getAddressesAccess()
    {
        if (!$this->addressAccess) {
            $this->addressAccess = PropertyAccess::createPropertyAccessor();
        }

        return $this->addressAccess;
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var AccountAddress $address */
        $address = $event->getData();

        /** @var AccountAddress[] $allAddresses */
        $allAddresses = $this->getAddressesAccess()->getValue($address, $this->addressesProperty);

        $this->handleDefaultType($address, $allAddresses);
    }

    /**
     * Only one address must have one default address per type.
     *
     * @param AbstractDefaultTypedAddress $address
     * @param AbstractDefaultTypedAddress[] $allAddresses
     */
    protected function handleDefaultType(AbstractDefaultTypedAddress $address, $allAddresses)
    {
        /** @var Collection|AddressType[] $addressDefaults */
        $addressDefaults = $address->getDefaults();

        foreach ($allAddresses as $otherAddresses) {
            if ($address === $otherAddresses) {
                continue;
            }

            $otherAddressDefaults = $otherAddresses->getDefaults();
            foreach ($addressDefaults as $addressDefaultType) {
                foreach ($otherAddressDefaults as $otherAddressDefault) {
                    if ($otherAddressDefault->getName() === $addressDefaultType->getName() &&
                        $otherAddressDefaults->contains($otherAddressDefault)
                    ) {
                        $otherAddressDefaults->removeElement($otherAddressDefault);
                    }
                }
            }
            $otherAddresses->setDefaults($otherAddressDefaults);
        }
    }
}
