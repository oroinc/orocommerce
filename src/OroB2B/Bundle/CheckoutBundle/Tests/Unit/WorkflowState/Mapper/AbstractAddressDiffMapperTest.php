<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Entity\AddressPhoneAwareInterface;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

abstract class AbstractAddressDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function testGetCurrentStateAccountUserAddress()
    {
        $accountUserAddress = $this->fillAddress(new AccountUserAddress());

        $orderAddress = new OrderAddress();
        $orderAddress->setAccountUserAddress($accountUserAddress);
        $this->checkout->{$this->getAddressMethodName('set')}($orderAddress);

        $result = $this->mapper->getCurrentState($this->checkout);
        $this->assertEquals($this->getStringAddressView(), $result);
    }

    public function testGetCurrentStateAccountAddress()
    {
        $accountAddress = $this->fillAddress(new AccountAddress());

        $orderAddress = new OrderAddress();
        $orderAddress->setAccountAddress($accountAddress);
        $this->checkout->{$this->getAddressMethodName('set')}($orderAddress);

        $result = $this->mapper->getCurrentState($this->checkout);
        $this->assertEquals($this->getStringAddressView(), $result);
    }

    public function testGetCurrentState()
    {
        $orderAddress = $this->fillAddress(new OrderAddress());
        $this->checkout->{$this->getAddressMethodName('set')}($orderAddress);

        $result = $this->mapper->getCurrentState($this->checkout);
        $this->assertEquals($this->getStringAddressView(), $result);
    }

    public function testGetCurrentStateEmptyBillingAddress()
    {
        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals([], $result);
    }

    public function testIsStatesEqualTrue()
    {
        $state1 = 'test address';
        $state2 = 'test address';

        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, $state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $state1 = 'test address';
        $state2 = 'test other address';

        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, $state1, $state2));
    }

    /**
     * @param AbstractAddress $address
     * @return AbstractAddress
     */
    protected function fillAddress(AbstractAddress $address)
    {
        $address->setNamePrefix('testNamePrefix')
            ->setFirstName('testFirstName')
            ->setLastName('testLastName')
            ->setMiddleName('testMiddleName')
            ->setNameSuffix('testNameSuffix')
            ->setOrganization('testOrganization')
            ->setStreet('testStreet')
            ->setStreet2('testStreet2')
            ->setCity('testCity')
            ->setRegionText('testRegionText')
            ->setCountry((new Country('US'))->setName('US'))
            ->setPostalCode('12344555M');

        if ($address instanceof AddressPhoneAwareInterface) {
            $address->setPhone('00099988877766');
        }

        return $address;
    }

    /**
     * @return string
     */
    protected function getStringAddressView()
    {
        return 'testNamePrefix testFirstName testLastName testMiddleName testNameSuffix ' .
        'testOrganization testStreet testStreet2 testCity testRegionText US 12344555M 00099988877766';
    }

    /**
     * @param string $methodType
     * @return string
     */
    protected function getAddressMethodName($methodType)
    {
        return sprintf('%s%s', $methodType, ucfirst($this->getTestAddressFieldName()));
    }

    /**
     * Return address field name
     * Ex: 'billingAddress'
     *
     * @return string
     */
    abstract protected function getTestAddressFieldName();
}
