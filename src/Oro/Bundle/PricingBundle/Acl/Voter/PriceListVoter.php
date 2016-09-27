<?php

namespace Oro\Bundle\PricingBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\PriceListIsReferentialCheckerInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\PricingBundle\Entity\PriceList;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PriceListVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'DELETE';

    /**
     * @var array
     */
    protected $supportedAttributes = [self::ATTRIBUTE_DELETE];

    /**
     * @var PriceList
     */
    protected $object;

    /**
     * @var PriceListIsReferentialCheckerInterface
     */
    protected $isReferentialChecker;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListIsReferentialCheckerInterface $isReferentialChecker
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListIsReferentialCheckerInterface $isReferentialChecker
    ) {
        $this->isReferentialChecker = $isReferentialChecker;
        parent::__construct($doctrineHelper);
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
        if ($this->isReferentialChecker->isReferential($this->object)) {
            return self::ACCESS_DENIED;
        }

        if ($this->object->isDefault()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
