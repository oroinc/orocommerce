<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Model;

use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;

class AddressOptionModelTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $optionModel = $this->createOptionalModel();

        $this->assertEquals('First Name', $optionModel->getFirstName());
        $this->assertEquals('Last Name', $optionModel->getLastName());
        $this->assertEquals('Street', $optionModel->getStreet());
        $this->assertEquals('Street2', $optionModel->getStreet2());
        $this->assertEquals('City', $optionModel->getCity());
        $this->assertEquals('State', $optionModel->getRegionCode());
        $this->assertEquals('Zip Code', $optionModel->getPostalCode());
        $this->assertEquals('US', $optionModel->getCountryIso2());

    }

    /**
     * @return AddressOptionModel
     */
    private function createOptionalModel()
    {
        $optionModel = new AddressOptionModel();
        $optionModel
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setStreet('Street')
            ->setStreet2('Street2')
            ->setCity('City')
            ->setRegionCode('State')
            ->setPostalCode('Zip Code')
            ->setCountryIso2('US');

        return $optionModel;
    }
}
