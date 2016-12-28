<?php

namespace Oro\Bundle\CheckoutBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\AccountOwnerAwareInterface;

class CheckoutVoter extends AbstractEntityVoter implements ContainerAwareInterface
{
    const ATTRIBUTE_CREATE = 'CHECKOUT_CREATE';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_CREATE
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

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

        $securityFacade = $this->getSecurityFacade();
        $checkout = new Checkout();

        // use owner from Checkout Source with permission level from Checkout to make decision
        // For example on Basic level create possibility on our shopping list
        if ($object instanceof AccountOwnerAwareInterface) {
            $checkout->setAccountUser($object->getAccountUser());
        }
        if ($securityFacade->isGranted('VIEW', $object) && $securityFacade->isGranted('CREATE', $checkout)) {
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
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
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
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->getContainer()->get('oro_security.security_facade');
    }
}
