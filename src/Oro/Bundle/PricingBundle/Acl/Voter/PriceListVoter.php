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
    protected $supportedAttributes = [BasicPermission::DELETE];

    private ContainerInterface $container;

    private mixed $object;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_pricing.price_list_reference_checker' => PriceListReferenceChecker::class
        ];
    }

    #[\Override]
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        $this->object = $object;
        try {
            return parent::vote($token, $object, $attributes);
        } finally {
            $this->object = null;
        }
    }

    #[\Override]
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
