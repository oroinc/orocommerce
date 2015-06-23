<?php

namespace OroB2B\Bundle\CustomerBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;

/**
 * This class represents the entity ownership metadata for AccountUser
 */
class FrontendOwnershipMetadata extends OwnershipMetadata implements OwnershipMetadataInterface
{
    const OWNER_TYPE_FRONTEND_USER = 4;
    const OWNER_TYPE_FRONTEND_CUSTOMER = 5;

    /**
     * @var integer
     */
    protected $frontendOwnerType;

    /**
     * @var string
     */
    protected $frontendOwnerFieldName;

    /**
     * @var string
     */
    protected $frontendOwnerColumnName;

    /**
     * {@inheritdoc}
     */
    public function isBasicLevelOwned()
    {
        return $this->frontendOwnerType === self::OWNER_TYPE_FRONTEND_USER;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalLevelOwned($deep = false)
    {
        return $this->frontendOwnerType === self::OWNER_TYPE_FRONTEND_CUSTOMER;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerType()
    {
        return $this->frontendOwnerType;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerFieldName()
    {
        return $this->frontendOwnerFieldName;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerColumnName()
    {
        return $this->frontendOwnerColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->ownerType,
                $this->ownerFieldName,
                $this->ownerColumnName,
                $this->organizationFieldName,
                $this->organizationColumnName,
                $this->frontendOwnerType,
                $this->frontendOwnerFieldName,
                $this->frontendOwnerColumnName,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->ownerType,
            $this->ownerFieldName,
            $this->ownerColumnName,
            $this->organizationFieldName,
            $this->organizationColumnName,
            $this->frontendOwnerType,
            $this->frontendOwnerFieldName,
            $this->frontendOwnerColumnName,
            ) = unserialize($serialized);
    }

    /**
     * @param string $frontendOwnerType
     * @param string $frontendOwnerFieldName
     * @param string $frontendOwnerColumnName
     */
    public function setFrontendOwner($frontendOwnerType, $frontendOwnerFieldName, $frontendOwnerColumnName)
    {
        $const = sprintf('static::OWNER_TYPE_FRONTEND_%s', strtoupper($frontendOwnerType));

        if (defined($const)) {
            $this->frontendOwnerType = constant($const);
        } else {
            if (!empty($frontendOwnerType)) {
                throw new \InvalidArgumentException(sprintf('Unknown frontend owner type: %s.', $frontendOwnerType));
            }
            $this->frontendOwnerType = self::OWNER_TYPE_NONE;
        }

        $this->frontendOwnerFieldName = $frontendOwnerFieldName;
        if ($this->frontendOwnerType !== self::OWNER_TYPE_NONE && empty($this->frontendOwnerFieldName)) {
            throw new \InvalidArgumentException('The frontend owner field name must not be empty.');
        }

        $this->frontendOwnerColumnName = $frontendOwnerColumnName;
        if ($this->frontendOwnerType !== self::OWNER_TYPE_NONE && empty($this->frontendOwnerColumnName)) {
            throw new \InvalidArgumentException('The frontend owner column name must not be empty.');
        }
    }
}
