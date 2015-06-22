<?php
namespace OroB2B\Bundle\CustomerBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * When address is created/updated from single form, it will ensure the rules of one address has default mark per type
 */
class FixCustomerAddressesDefaultSubscriber implements EventSubscriberInterface
{
    /**
     * Property path to collection of all addresses (e.g. 'owner.address' means $address->getOwner()->getAddresses())
     *
     * @var string
     */
    protected $addressesProperty;

    /**
     * @var PropertyAccess
     */
    protected $addressAccess;

    public function __construct($addressesProperty)
    {
        $this->addressesAccess = PropertyAccess::createPropertyAccessor();
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
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var CustomerAddress $address */
        $address = $event->getData();

        /** @var CustomerAddress[] $allAddresses */
        $allAddresses = $this->addressesAccess->getValue($address, $this->addressesProperty);

        $this->handleDefaultType($address, $allAddresses);
    }

    /**
     * Only one address must have one default address per type.
     *
     * @param CustomerAddress $address
     * @param CustomerAddress[] $allAddresses
     */
    protected function handleDefaultType(CustomerAddress $address, $allAddresses)
    {
        /** @var Collection|CustomerAddress[] $addressDefaults */
        $addressDefaults = $address->getDefaults();

        foreach ($allAddresses as $otherAddresses) {
            $otherAddressDefaults = $otherAddresses->getDefaults();
            foreach ($addressDefaults as $addressDefaultType) {
                if ($otherAddressDefaults->contains($addressDefaultType) && $address !== $otherAddresses) {
                    $otherAddressDefaults->removeElement($addressDefaultType);
                }
            }
            $otherAddresses->setDefaults($otherAddressDefaults);
        }
    }
}
