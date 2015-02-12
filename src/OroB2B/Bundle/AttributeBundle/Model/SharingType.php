<?php

namespace OroB2B\Bundle\AttributeBundle\Model;

class SharingType
{
    const GENERAL = 'general';
    const GROUP   = 'group';
    const WEBSITE = 'website';

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [self::GENERAL, self::GROUP, self::WEBSITE];
    }
}
