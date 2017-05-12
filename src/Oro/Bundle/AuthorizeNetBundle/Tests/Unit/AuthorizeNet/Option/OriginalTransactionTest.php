<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class OriginalTransactionTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\OriginalTransaction(false)];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'invalid type' => [
                ['original_transaction' => 123.456],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "original_transaction" with value 123.456 is expected to be of type '.
                    '"integer" or "string", but is of type "double".',
                ],
            ],
            'valid_string' => [
                ['original_transaction' => "1"],
                ['original_transaction' => "1"],
            ],
            'valid_integer' => [
                ['original_transaction' => 1],
                ['original_transaction' => 1],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "original_transaction" is missing.
     */
    public function testRequired()
    {
        $originalTransaction = new Option\OriginalTransaction();

        $resolver = new Option\OptionsResolver();
        $resolver->addOption($originalTransaction);
        $resolver->resolve();
    }
}
