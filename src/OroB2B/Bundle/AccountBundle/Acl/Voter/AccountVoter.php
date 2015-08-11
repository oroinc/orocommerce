<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserOwnerInterface;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\SecurityFacade;

class AccountVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_VIEW = 'ACCOUNT_VIEW';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_VIEW,
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var AccountOwnerInterface
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
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object instanceof AccountUserOwnerInterface) {
            return self::ACCESS_ABSTAIN;
        }

        $this->object = $object;

        return parent::vote($token, $object, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /* @var $securityFacade SecurityFacade */
        $securityFacade = $this->container->get('orob2b_account.security_facade');

        /* @var $user AccountUser */
        $user = $securityFacade->getLoggedUser();

        if (!$user instanceof AccountUser) {
            return self::ACCESS_ABSTAIN;
        }

        if ($securityFacade->isGrantedViewBasic($class)) {
            if ($this->object->getAccountUser() && $user->getId() === $this->object->getAccountUser()->getId()) {
                return self::ACCESS_GRANTED;
            }
        }

        if ($securityFacade->isGrantedViewLocal($class)) {
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
