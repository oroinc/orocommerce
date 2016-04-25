<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\SecureTokenIdentifier;

class SecureTokenIdentifierTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new SecureTokenIdentifier();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        $token = SecureTokenIdentifier::generate();

        return [
            'empty' => [],
            'invalid type' => [
                ['SECURETOKENID' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SECURETOKENID" with value 12345 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['SECURETOKENID' => $token], ['SECURETOKENID' => $token]],
        ];
    }

    public function testGenerateUnique()
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('No need to run with xdebug');
        }

        $i = 0;
        $iterations = 1000000;
        $tokens = new \SplDoublyLinkedList();
        while ($i < $iterations) {
            $token = SecureTokenIdentifier::generate();
            if (!$tokens->offsetExists($token)) {
                $tokens->push($token);
            }
            $i++;
        }

        $this->assertSame($iterations, $tokens->count());
    }
}
