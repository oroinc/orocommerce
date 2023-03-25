<?php

namespace Oro\Bundle\PricingBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\PriceListReferenceChecker;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Prevents removal of referential price lists.
 */
class PriceListVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::DELETE];

    private ContainerInterface $container;

    private mixed $object;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_pricing.price_list_reference_checker' => PriceListReferenceChecker::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->object = $object;
        try {
            return parent::vote($token, $object, $attributes);
        } finally {
            $this->object = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->getPriceListReferenceChecker()->isReferential($this->object)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function getPriceListReferenceChecker(): PriceListReferenceChecker
    {
        return $this->container->get('oro_pricing.price_list_reference_checker');
    }
}
