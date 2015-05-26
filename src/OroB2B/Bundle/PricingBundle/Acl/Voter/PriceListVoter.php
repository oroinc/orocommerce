<?php

namespace OroB2B\Bundle\PricingBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

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
        if ($this->object->isDefault()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
