<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\PaymentBundle\Provider\AddressExtractor;

class AddressExtractorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AddressExtractor */
    protected $extractor;

    protected function setUp()
    {
        $this->extractor = new AddressExtractor(PropertyAccess::createPropertyAccessor());
    }

    protected function tearDown()
    {
        unset($this->extractor);
    }

    /**
     * @param mixed $object
     * @param string $property
     * @param mixed $expected
     *
     * @dataProvider extractDataProvider
     */
    public function testExtract($object, $property, $expected)
    {
        $this->assertEquals($expected, $this->extractor->extractAddress($object, $property));
    }

    /**
     * @return array
     */
    public function extractDataProvider()
    {
        $addressStub = new AddressStub();

        $stdClass = new \stdClass();
        $stdClass->customAddress = $addressStub;

        $array = ['address' => $addressStub];

        return [
            'extract from custom property' => [$stdClass, 'customAddress', $addressStub],
            'extract from array property' => [$array, '[address]', $addressStub],
        ];
    }

    /**
     * @param mixed $object
     * @param string $property
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object does not contains billingAddress
     *
     * @dataProvider extractFailedDataProvider
     */
    public function testExtractFailed($object, $property)
    {
        $this->extractor->extractAddress($object, $property);
    }

    /**
     * @return array
     */
    public function extractFailedDataProvider()
    {
        return [
            'extract from checkout default billing address property' => [
                $this->getEntity('Oro\Bundle\CheckoutBundle\Entity\Checkout'),
                'missingProperty',
            ],
            'extract from checkout returns null' => [
                $this->getEntity('Oro\Bundle\CheckoutBundle\Entity\Checkout', ['billingAddress' => null]),
                'billingAddress',
            ],
            'extract from array' => [
                ['address' => new AddressStub()],
                '[wrongKey]',
            ],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "Oro\Bundle\LocaleBundle\Model\AddressInterface" expected, "stdClass" found
     */
    public function testWrongType()
    {
        $entity = new \stdClass();
        $entity->billingAddress = new \stdClass();

        $this->extractor->extractAddress($entity, 'billingAddress');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Object should not be empty
     */
    public function testNotAnObject()
    {
        $this->extractor->extractAddress(null);
    }

    public function testGetCountryIso2Failed()
    {
        $entity = new \stdClass();

        $this->assertNull(
            $this->extractor->getCountryIso2($entity)
        );
    }

    public function testGetCountryIso2()
    {
        $entity = new \stdClass();
        $entity->billingAddress = new AddressStub();

        $this->assertEquals('US', $this->extractor->getCountryIso2($entity));
    }
}
