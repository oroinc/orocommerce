<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Security\AccountUserProvider;

class AccountVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_VIEW = 'ACCOUNT_VIEW';
    const ATTRIBUTE_EDIT = 'ACCOUNT_EDIT';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_VIEW,
        self::ATTRIBUTE_EDIT,
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var AccountOwnerAwareInterface
     */
    protected $object;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ContainerInterface $container
     */
    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container = null)
    {
        parent::__construct($doctrineHelper);

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_a($class, 'OroB2B\Bundle\AccountBundle\Entity\AccountOwnerAwareInterface', true);
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
        /* @var $securityProvider AccountUserProvider */
        $securityProvider = $this->container->get('orob2b_account.security.account_user_provider');

        /* @var $user AccountUser */
        $user = $securityProvider->getLoggedUser();

        if (!$user instanceof AccountUser) {
            return self::ACCESS_ABSTAIN;
        }

        if ($securityProvider->isGrantedViewBasic($class)) {
            if ($this->object->getAccountUser() && $user->getId() === $this->object->getAccountUser()->getId()) {
                return self::ACCESS_GRANTED;
            }
        }

        if ($securityProvider->isGrantedViewLocal($class)) {
            if ($user->getAccount()->getId() === $this->object->getAccount()->getId()) {
                return self::ACCESS_GRANTED;
            }
            if ($this->object->getAccountUser() && $user->getId() === $this->object->getAccountUser()->getId()) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
