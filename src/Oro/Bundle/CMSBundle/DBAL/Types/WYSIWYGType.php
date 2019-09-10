<?php

namespace Oro\Bundle\CMSBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

/**
 * Doctrine type for WYSIWYG field that extends base text type.
 */
class WYSIWYGType extends TextType
{
    public const TYPE = 'wysiwyg';

    /** {@inheritdoc} */
    public function getName(): string
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
