<?php

namespace Oro\Bundle\PricingBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\PricingBundle\Entity\PriceList;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PriceListVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_DELETE = 'DELETE';

    /**
     * @var PriceList
     */
    protected $object;

    /**
     * @var PriceListReferenceChecker
     */
    protected $priceListReferenceChecker;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListReferenceChecker $priceListReferenceChecker
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListReferenceChecker $priceListReferenceChecker
    ) {
        parent::__construct($doctrineHelper);

        $this->supportedAttributes = [self::ATTRIBUTE_DELETE];
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
