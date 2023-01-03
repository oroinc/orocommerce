<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Comment;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class CommentTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Comment()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid comment1' => [
                ['COMMENT1' => 123, 'COMMENT2' => 321],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "COMMENT1" with value 123 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'invalid comment2' => [
                ['COMMENT1' => '123', 'COMMENT2' => 321],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "COMMENT2" with value 321 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'valid' => [['COMMENT1' => '123', 'COMMENT2' => '321'], ['COMMENT1' => '123', 'COMMENT2' => '321']],
        ];
    }
}
