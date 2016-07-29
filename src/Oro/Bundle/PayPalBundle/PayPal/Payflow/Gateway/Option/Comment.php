<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

class Comment extends AbstractOption
{
    const COMMENT1 = 'COMMENT1';
    const COMMENT2 = 'COMMENT2';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined([Comment::COMMENT1, Comment::COMMENT2])
            ->addAllowedTypes(Comment::COMMENT1, 'string')
            ->addAllowedTypes(Comment::COMMENT2, 'string');
    }
}
