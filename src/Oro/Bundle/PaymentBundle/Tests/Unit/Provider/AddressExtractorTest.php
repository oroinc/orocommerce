<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\PaymentBundle\Provider\AddressExtractor;

class AddressExtractorTest extends \PHPUnit\Framework\TestCase
{
    private AddressExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new AddressExtractor(PropertyAccess::createPropertyAccessor());
    }

    /**
     * @dataProvider extractDataProvider
     */
    public function testExtract(object|array $object, string $property, AddressStub $expected)
    {
        $this->assertEquals($expected, $this->extractor->extractAddress($object, $property));
    }

    public function extractDataProvider(): array
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

    public function testExtractFromEntityWithNullAddress()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object does not contains billingAddress');

        $entity = new \stdClass();
        $entity->billingAddress = null;

        $this->extractor->extractAddress($entity, 'billingAddress');
    }

    public function testExtractFromEntityWithWrongAddressProperty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object does not contains missingProperty');

        $this->extractor->extractAddress(new \stdClass(), 'missingProperty');
    }

    public function testExtractFromArrayWithWrongAddressKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object does not contains [missingKey]');

        $this->extractor->extractAddress(['address' => new AddressStub()], '[missingKey]');
    }

    public function testWrongType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"Oro\Bundle\LocaleBundle\Model\AddressInterface" expected, "stdClass" found');

        $entity = new \stdClass();
        $entity->billingAddress = new \stdClass();

        $this->extractor->extractAddress($entity, 'billingAddress');
    }

    public function testNotAnObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object should not be empty');

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
