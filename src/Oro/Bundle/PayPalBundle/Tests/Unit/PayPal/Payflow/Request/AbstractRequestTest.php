<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class AbstractRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testResolverMissing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Call AbstractRequest->withResolver($resolver) first');

        $request = new Stub\NoResolverAbstractRequestStub();
        $request->configureOptions(new Option\OptionsResolver());
    }

    public function testRequiredOptions()
    {
        $request = new Stub\AbstractRequestStub();
        $resolver = new Option\OptionsResolver();
        $request->configureOptions($resolver);

        $resolver->resolve(
            [
                Option\Transaction::TRXTYPE => 'some_action',
                Option\Partner::PARTNER => Option\Partner::AMEX,
                Option\Password::PASSWORD => 'password',
                Option\User::USER => 'user',
                Option\Vendor::VENDOR => 'vendor',
            ]
        );
    }

    public function testLockTrxTypeToRequest()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "TRXTYPE" with value "another_action" is invalid.');

        $request = new Stub\AbstractRequestStub();
        $resolver = new Option\OptionsResolver();
        $request->configureOptions($resolver);

        $resolver->resolve(
            [
                Option\Transaction::TRXTYPE => 'another_action',
                Option\Partner::PARTNER => Option\Partner::AMEX,
                Option\Password::PASSWORD => 'password',
                Option\User::USER => 'user',
                Option\Vendor::VENDOR => 'vendor',
            ]
        );
    }
}
