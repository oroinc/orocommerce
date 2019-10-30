<?php

namespace Oro\Bundle\CMSBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * Doctrine type for WYSIWYGProperties field that extends base Json type
 */
class WYSIWYGPropertiesType extends JsonType
{
    public const TYPE_SUFFIX = '_properties';

    public const TYPE = WYSIWYGType::TYPE . self::TYPE_SUFFIX;

    /** {@inheritdoc} */
    public function getName()
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
