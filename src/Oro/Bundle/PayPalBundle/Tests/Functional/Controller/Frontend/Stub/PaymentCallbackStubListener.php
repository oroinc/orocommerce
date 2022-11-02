<?php

namespace Oro\Bundle\PayPalBundle\Tests\Functional\Controller\Frontend\Stub;

/**
 * This listener is used to set callbackCalled=true in case when onError or onReturn payment callbacks were called
 * This flag will be checked in test and, for example, build cal be marked as self::fail()
 *
 * It's reguired because self::fail() throws exception and if we will try to do this in listener then
 * Symfony just handle it and test will NOT be marked as failed
 */
class PaymentCallbackStubListener
{
    /**
     * Flag that shows test should be marked as failed
     * @var bool
     */
    private $callbackCalled;

    public function __construct(bool &$callbackCalled)
    {
        $this->callbackCalled = &$callbackCalled;
    }

    public function onError()
    {
        $this->callbackCalled = true;
    }

    public function onReturn()
    {
        $this->callbackCalled = true;
    }
}
