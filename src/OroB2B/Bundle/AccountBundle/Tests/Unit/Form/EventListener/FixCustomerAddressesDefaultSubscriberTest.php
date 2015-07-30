<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Form\EventListener\FixAccountAddressesDefaultSubscriber;

use Symfony\Component\Form\FormEvents;

class FixCustomerAddressesDefaultSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FixAccountAddressesDefaultSubscriber
     */
    protected $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->subscriber = new FixAccountAddressesDefaultSubscriber('owner.addresses');
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::POST_SUBMIT => 'postSubmit'],
            $this->subscriber->getSubscribedEvents()
        );
    }

    /**
     * @dataProvider postSubmitDataProvider
     */
    public function testPostSubmit(array $allAddresses, $formAddressKey, array $expectedAddressesData)
    {
        // Set owner for all addresses
        $customer = new Account();
        foreach ($allAddresses as $address) {
            $customer->addAddress($address);
        }

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($allAddresses[$formAddressKey]));

        $this->subscriber->postSubmit($event);

        foreach ($expectedAddressesData as $addressKey => $expectedData) {
            /** @var AccountAddress $address */
            $address = $allAddresses[$addressKey];

            $defaultTypeNames = [];
            /** @var AddressType $defaultType */
            foreach ($address->getDefaults() as $defaultType) {
                $defaultTypeNames[] = $defaultType->getName();
            }
            $this->assertEquals($expectedData['defaults'], $defaultTypeNames);
        }
    }

    public function postSubmitDataProvider()
    {
        $billing = new AddressType(AddressType::TYPE_BILLING);
        $shipping = new AddressType(AddressType::TYPE_SHIPPING);

        return [
            'default' => [
                'allAddresses' => [
                    'foo' => $this->createAddress()->addType($billing)->setDefaults([$billing]),
                    'bar' => $this->createAddress()->addType($billing)->setDefaults([$billing]),
                    'baz' => $this->createAddress()->addType($billing)->addType($shipping)->setDefaults([
                            $billing,
                            $shipping
                        ]),
                ],
                'formAddressKey' => 'foo',
                'expectedAddressesData' => [
                    'foo' => ['defaults' => [AddressType::TYPE_BILLING]],
                    'bar' => ['defaults' => []],
                    'baz' => ['defaults' => [AddressType::TYPE_SHIPPING]],
                ]
            ],
            'change_default_after_remove' => [
                'allAddresses' => [
                    'foo' => $this->createAddress()->addType($billing)->setDefaults([$billing])->removeType($billing),
                ],
                'formAddressKey' => 'foo',
                'expectedAddressesData' => [
                    'foo' => ['defaults' => []],
                ]
            ],
        ];
    }

    /**
     * @return AccountAddress
     */
    protected function createAddress()
    {
        return new AccountAddress();
    }
}
