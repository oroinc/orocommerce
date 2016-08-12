<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;

use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\AccountBundle\Entity\AddressPhoneAwareInterface;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\BillingAddressDiffMapper;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * @SuppressWarnings("TooManyPublicMethods")
 */
class BillingAddressDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new BillingAddressDiffMapper();
    }

    public function testGetName()
    {
        $this->assertEquals('billingAddress', $this->mapper->getName());
    }

    public function testGetCurrentStateAccountUserAddress()
    {
        $accountUserAddress = $this->fillAddress(new AccountUserAddress(), 'accountUserAddress');
        $orderAddress = new OrderAddress();
        $orderAddress->setAccountUserAddress($accountUserAddress);

        $addressString = 'testNamePrefix accountUserAddress testLastName testMiddleName testNameSuffix ' .
            'testOrganization testStreet testStreet2 testCity testRegionText  12344555M 00099988877766';

        $this->checkout->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($orderAddress);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals($addressString, $result);
    }

    public function testGetCurrentStateAccountAddress()
    {
        $accountAddress = $this->fillAddress(new AccountAddress(), 'accountAddress');
        $orderAddress = new OrderAddress();
        $orderAddress->setAccountAddress($accountAddress);

        $addressString = 'testNamePrefix accountAddress testLastName testMiddleName testNameSuffix ' .
            'testOrganization testStreet testStreet2 testCity testRegionText  12344555M 00099988877766';

        $this->checkout->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($orderAddress);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals($addressString, $result);
    }

    public function testGetCurrentState()
    {
        $orderAddress = $this->fillAddress(new OrderAddress(), 'orderAddress');

        $addressString = 'testNamePrefix orderAddress testLastName testMiddleName testNameSuffix ' .
            'testOrganization testStreet testStreet2 testCity testRegionText  12344555M 00099988877766';

        $this->checkout->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($orderAddress);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals($addressString, $result);
    }

    public function testGetCurrentStateEmptyBillingAddress()
    {
        $this->checkout->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn(null);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals([], $result);
    }

    public function testIsStatesEqualTrue()
    {
        $state1 = [
            'parameter1' => 10,
            'billingAddress' => 'test address',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'billingAddress' => 'test address',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $state1 = [
            'parameter1' => 10,
            'billingAddress' => 'test address',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'billingAddress' => 'test other address',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(false, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState1()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'billingAddress' => 'test address',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState2()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
            'billingAddress' => 'test address',
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    /**
     * @param AbstractAddress $address
     * @param string $firstName
     * @return AbstractAddress
     */
    private function fillAddress(AbstractAddress $address, $firstName)
    {
        $address->setNamePrefix('testNamePrefix')
            ->setFirstName($firstName)
            ->setLastName('testLastName')
            ->setMiddleName('testMiddleName')
            ->setNameSuffix('testNameSuffix')
            ->setOrganization('testOrganization')
            ->setStreet('testStreet')
            ->setStreet2('testStreet2')
            ->setCity('testCity')
            ->setRegionText('testRegionText')
            ->setCountry(new Country('US'))
            ->setPostalCode('12344555M');

        if ($address instanceof AddressPhoneAwareInterface) {
            $address->setPhone('00099988877766');
        }

        return $address;
    }
}
