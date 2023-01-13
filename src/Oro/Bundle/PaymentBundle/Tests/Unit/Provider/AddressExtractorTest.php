<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\PaymentBundle\Provider\AddressExtractor;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AddressExtractorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AddressExtractor */
    private $extractor;

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

    /**
     * @dataProvider extractFailedDataProvider
     */
    public function testExtractFailed(object|array $object, string $property)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Object does not contains billingAddress');

        $this->expectExceptionMessage(sprintf('Object does not contains %s', $property));
        $this->extractor->extractAddress($object, $property);
    }

    public function extractFailedDataProvider(): array
    {
        return [
            'extract from checkout default billing address property' => [
                $this->getEntity(Checkout::class),
                'missingProperty',
            ],
            'extract from checkout returns null' => [
                $this->getEntity(Checkout::class, ['billingAddress' => null]),
                'billingAddress',
            ],
            'extract from array' => [
                ['address' => new AddressStub()],
                '[wrongKey]',
            ],
        ];
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
