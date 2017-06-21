<?php

namespace Oro\Bundle\CheckoutBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;

class CheckoutVoter extends AbstractEntityVoter implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ATTRIBUTE_CREATE = 'CHECKOUT_CREATE';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_CREATE
    ];

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_a($class, CheckoutSourceEntityInterface::class, true);
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        if (!in_array(self::ATTRIBUTE_CREATE, $attributes)) {
            return self::ACCESS_ABSTAIN;
        }

        $authorizationChecker = $this->getAuthorizationChecker();
        $checkout = new Checkout();

        // use owner from Checkout Source with permission level from Checkout to make decision
        // For example on Basic level create possibility on our shopping list
        if ($object instanceof CustomerOwnerAwareInterface) {
            $checkout->setCustomerUser($object->getCustomerUser());
        }
        if ($authorizationChecker->isGranted('VIEW', $object)
            && $authorizationChecker->isGranted('CREATE', $checkout)
        ) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return self::ACCESS_ABSTAIN;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface not injected');
        }

        return $this->container;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return $this->getContainer()->get('security.authorization_checker');
    }
}
