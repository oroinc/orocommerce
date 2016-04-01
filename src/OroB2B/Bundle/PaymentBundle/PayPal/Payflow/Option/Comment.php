<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

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
