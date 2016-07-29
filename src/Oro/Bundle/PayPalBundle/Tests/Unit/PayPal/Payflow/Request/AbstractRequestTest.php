<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request\Stub;

class AbstractRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Call AbstractRequest->withResolver($resolver) first
     */
    public function testResolverMissing()
    {
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

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage The option "TRXTYPE" with value "another_action" is invalid.
     */
    public function testLockTrxTypeToRequest()
    {
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
