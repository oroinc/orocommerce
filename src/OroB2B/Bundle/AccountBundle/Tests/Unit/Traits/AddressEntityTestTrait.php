<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Traits;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;

trait AddressEntityTestTrait
{
    use EntityTestCaseTrait;

    public function testAddressesCollection()
    {
        $account = $this->createTestedEntity();
        static::assertPropertyCollections($account, [['addresses', $this->createAddressEntity()]]);
    }

    /**
     * @param AbstractDefaultTypedAddress[] $addresses
     * @param string                        $searchName
     * @param AbstractDefaultTypedAddress   $expectedAddress
     * @dataProvider getAddressByTypeNameProvider
     */
    public function testGetAddressByTypeName($addresses, $searchName, $expectedAddress)
    {
        $account = $this->createTestedEntity();
        foreach ($addresses as $address) {
            $account->addAddress($address);
        }

        $actualAddress = $account->getAddressByTypeName($searchName);
        \PHPUnit_Framework_Assert::assertEquals($expectedAddress, $actualAddress);
    }

    /**
     * @return array
     */
    public function getAddressByTypeNameProvider()
    {
        $billingType = new AddressType(AddressType::TYPE_BILLING);
        $shippingType = new AddressType(AddressType::TYPE_SHIPPING);

        $addressWithBilling = $this->createAddressEntity();
        $addressWithBilling->addType($billingType);

        $addressWithShipping = $this->createAddressEntity();
        $addressWithShipping->addType($shippingType);

        $addressWithShippingAndBilling = $this->createAddressEntity();
        $addressWithShippingAndBilling->addType($shippingType);
        $addressWithShippingAndBilling->addType($billingType);

        return [
            'not found address with type (empty addresses)' => [
                'addresses' => [],
                'searchName' => AddressType::TYPE_BILLING,
                'expectedAddress' => null
            ],
            'not found address with type (some address exists)' => [
                'addresses' => [$addressWithShipping],
                'searchName' => AddressType::TYPE_BILLING,
                'expectedAddress' => null
            ],
            'find address by shipping name' => [
                'addresses' => [$addressWithShipping],
                'searchName' => AddressType::TYPE_SHIPPING,
                'expectedAddress' => $addressWithShipping
            ],
            'find first address by shipping name' => [
                'addresses' => [$addressWithShippingAndBilling, $addressWithShipping],
                'searchName' => AddressType::TYPE_SHIPPING,
                'expectedAddress' => $addressWithShippingAndBilling
            ],
        ];
    }

    /**
     * @param $addresses
     * @param $expectedAddress
     * @dataProvider getPrimaryAddressProvider
     */
    public function testGetPrimaryAddress($addresses, $expectedAddress)
    {
        $account = $this->createTestedEntity();
        foreach ($addresses as $address) {
            $account->addAddress($address);
        }

        \PHPUnit_Framework_Assert::assertEquals($expectedAddress, $account->getPrimaryAddress());
    }

    public function getPrimaryAddressProvider()
    {
        $primaryAddress = $this->createAddressEntity();
        $primaryAddress->setPrimary(true);

        $notPrimaryAddress = $this->createAddressEntity();

        return [
            'without primary address' => [
                'addresses' => [$notPrimaryAddress],
                'expectedAddress' => null
            ],
            'one primary address' => [
                'addresses' => [$primaryAddress],
                'expectedAddress' => $primaryAddress
            ],
            'get one primary by few address' => [
                'addresses' => [$primaryAddress, $notPrimaryAddress],
                'expectedAddress' => $primaryAddress
            ],
        ];
    }

    /**
     * Return tested entity
     *
     * @return AccountUser|Account
     */
    abstract protected function createTestedEntity();

    /**
     * Return address entity related with entity
     * returned from `createTestedEntity`
     *
     * @return AbstractDefaultTypedAddress
     */
    abstract protected function createAddressEntity();
}
