<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\AbstractOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;

/**
 * Configures comment options for PayPal Payflow Gateway transactions.
 *
 * Allows up to two comment fields for storing transaction-related notes or metadata.
 */
class Comment extends AbstractOption
{
    public const COMMENT1 = 'COMMENT1';
    public const COMMENT2 = 'COMMENT2';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined([Comment::COMMENT1, Comment::COMMENT2])
            ->addAllowedTypes(Comment::COMMENT1, 'string')
            ->addAllowedTypes(Comment::COMMENT2, 'string');
    }
}
