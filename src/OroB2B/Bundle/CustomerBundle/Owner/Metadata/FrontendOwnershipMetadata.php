<?php

namespace OroB2B\Bundle\CustomerBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

/**
 * This class represents the entity ownership metadata for AccountUser
 */
class FrontendOwnershipMetadata extends OwnershipMetadata
{
    const OWNER_TYPE_FRONTEND_USER = 4;
    const OWNER_TYPE_FRONTEND_CUSTOMER = 5;

    /**
     * {@inheritdoc}
     */
    protected function getConstantName($ownerType)
    {
        return sprintf('static::OWNER_TYPE_FRONTEND_%s', strtoupper($ownerType));
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalLevelOwned($deep = false)
    {
        return $this->ownerType === self::OWNER_TYPE_FRONTEND_CUSTOMER;
    }

    /**
     * {@inheritdoc}
     */
    public function isBasicLevelOwned()
    {
        return $this->ownerType === self::OWNER_TYPE_FRONTEND_USER;
    }

    /**
     * {@inheritdoc}
     */
    public function isGlobalLevelOwned()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobalOwnerColumnName()
    {
        throw new \BadMethodCallException('Frontend entities are not owned by organization');
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobalOwnerFieldName()
    {
        throw new \BadMethodCallException('Frontend entities are not owned by organization');
    }
}
