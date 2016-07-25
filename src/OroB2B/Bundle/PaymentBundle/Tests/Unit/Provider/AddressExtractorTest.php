<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\PaymentBundle\Provider\AddressExtractor;

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
        $orderAddress = new OrderAddress();

        $stdClass = new \stdClass();
        $stdClass->customAddress = $orderAddress;

        $array = ['address' => $orderAddress];

        return [
            'extract from checkout default billing address property' => [
                $this->getEntity(
                    'OroB2B\Bundle\CheckoutBundle\Entity\Checkout',
                    [
                        'billingAddress' => $orderAddress,
                    ]
                ),
                AddressExtractor::PROPERTY_PATH,
                $orderAddress,
            ],
            'extract from order default billing address property' => [
                $this->getEntity(
                    'OroB2B\Bundle\OrderBundle\Entity\Order',
                    [
                        'billingAddress' => $orderAddress,
                    ]
                ),
                AddressExtractor::PROPERTY_PATH,
                $orderAddress,
            ],
            'extract from custom property' => [$stdClass, 'customAddress', $orderAddress],
            'extract from array property' => [$array, '[address]', $orderAddress],
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
                $this->getEntity('OroB2B\Bundle\CheckoutBundle\Entity\Checkout'),
                'missingProperty',
            ],
            'extract from checkout returns null' => [
                $this->getEntity('OroB2B\Bundle\CheckoutBundle\Entity\Checkout', ['billingAddress' => null]),
                'billingAddress',
            ],
            'extract from array' => [
                ['address' => new OrderAddress()],
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
        $iso2code = 'US';

        $entity = new \stdClass();
        $entity->billingAddress = $this->getEntity(
            'OroB2B\Bundle\OrderBundle\Entity\OrderAddress',
            ['country' => new Country($iso2code)]
        );

        $this->assertEquals(
            $iso2code,
            $this->extractor->getCountryIso2($entity)
        );
    }
}
