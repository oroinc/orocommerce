<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Comment;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class CommentTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Comment()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid comment1' => [
                ['COMMENT1' => 123, 'COMMENT2' => 321],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "COMMENT1" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'invalid comment2' => [
                ['COMMENT1' => '123', 'COMMENT2' => 321],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "COMMENT2" with value 321 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['COMMENT1' => '123', 'COMMENT2' => '321'], ['COMMENT1' => '123', 'COMMENT2' => '321']],
        ];
    }
}
