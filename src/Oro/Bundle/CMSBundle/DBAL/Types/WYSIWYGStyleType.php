<?php

namespace Oro\Bundle\CMSBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

/**
 * Doctrine type for WYSIWYGStyle field that extends base text type.
 */
class WYSIWYGStyleType extends TextType
{
    public const TYPE_SUFFIX = '_style';

    public const TYPE = WYSIWYGType::TYPE . self::TYPE_SUFFIX;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
