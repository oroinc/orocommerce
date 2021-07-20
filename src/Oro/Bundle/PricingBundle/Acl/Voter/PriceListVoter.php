<?php

namespace Oro\Bundle\PricingBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevents removal of default and referential price lists.
 */
class PriceListVoter extends AbstractEntityVoter
{
    /** @var array */
    protected $supportedAttributes = [BasicPermission::DELETE];

    /** @var PriceList */
    protected $object;

    /** @var PriceListReferenceChecker */
    protected $priceListReferenceChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListReferenceChecker $priceListReferenceChecker
    ) {
        parent::__construct($doctrineHelper);
        $this->priceListReferenceChecker = $priceListReferenceChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->object = $object;

        return parent::vote($token, $object, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->priceListReferenceChecker->isReferential($this->object)) {
            return self::ACCESS_DENIED;
        }

        if ($this->object->isDefault()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
