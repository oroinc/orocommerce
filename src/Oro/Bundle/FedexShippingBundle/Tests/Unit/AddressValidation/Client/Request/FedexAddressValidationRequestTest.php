<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\AddressValidation\Client\Request;

use Oro\Bundle\FedexShippingBundle\AddressValidation\Client\Request\FedexAddressValidationRequest;
use PHPUnit\Framework\TestCase;

final class FedexAddressValidationRequestTest extends TestCase
{
    public function testGetters(): void
    {
        $uri = 'test/uri';
        $data = ['1', '2'];

        $request = new FedexAddressValidationRequest($uri, $data);

        self::assertEquals($uri, $request->getUri());
        self::assertEquals($data, $request->getRequestData());
        self::assertFalse($request->isCheckMode());

        $request = new FedexAddressValidationRequest($uri, $data, true);

        self::assertTrue($request->isCheckMode());
    }
}
