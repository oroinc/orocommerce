<?php

namespace OroB2B\Bundle\AccountBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

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
     * @var AccountUser
     */
    protected $user;

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
        if (!$token->getUser() instanceof AccountUser) {
            return self::ACCESS_ABSTAIN;
        }

        $this->object = $object;
        $this->user = $token->getUser();

        if (!$object || !is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        // both entity and identity objects are supported
        $class = $this->getEntityClass($object);

        try {
            $identifier = $this->getEntityIdentifier($object);
        } catch (NotManageableEntityException $e) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->getPermission($class, $identifier, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if (null === $identifier) {
            if ($this->isGrantedClassPermission($attribute, $class)) {
                return self::ACCESS_GRANTED;
            }

            return self::ACCESS_ABSTAIN;
        }

        if ($this->isGrantedBasicPermission($attribute, $class)) {
            if ($this->isSameUser($this->user, $this->object)) {
                return self::ACCESS_GRANTED;
            }
        }

        if ($this->isGrantedLocalPermission($attribute, $class)) {
            if ($this->isSameAccount($this->user, $this->object) || $this->isSameUser($this->user, $this->object)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param AccountUser $user
     * @param AccountOwnerAwareInterface $object
     * @return bool
     */
    protected function isSameAccount(AccountUser $user, AccountOwnerAwareInterface $object)
    {
        return $user->getAccount()->getId() === $object->getAccount()->getId();
    }

   /**
     * @param AccountUser $user
     * @param AccountOwnerAwareInterface $object
     * @return bool
     */
    protected function isSameUser(AccountUser $user, AccountOwnerAwareInterface $object)
    {
        return $object->getAccountUser() && $user->getId() === $object->getAccountUser()->getId();
    }

    /**
     * @param string $attribute
     * @param string $class
     * @return bool
     */
    protected function isGrantedClassPermission($attribute, $class)
    {
        /* @var $securityFacade SecurityFacade */
        $securityFacade = $this->container->get('oro_security.security_facade');

        $descriptor = sprintf('entity:%s@%s', AccountUser::SECURITY_GROUP, $class);

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                $isGranted = $securityFacade->isGranted(BasicPermissionMap::PERMISSION_VIEW, $descriptor);
                break;

            case self::ATTRIBUTE_EDIT:
                $isGranted = $securityFacade->isGranted(BasicPermissionMap::PERMISSION_EDIT, $descriptor);
                break;

            default:
                $isGranted = false;
        }

        return $isGranted;
    }

    /**
     * @param string $attribute
     * @param string $class
     * @return bool
     */
    protected function isGrantedBasicPermission($attribute, $class)
    {
        $securityProvider = $this->getSecurityProvider();

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                $isGranted = $securityProvider->isGrantedViewBasic($class);
                break;

            case self::ATTRIBUTE_EDIT:
                $isGranted = $securityProvider->isGrantedEditBasic($class);
                break;

            default:
                $isGranted = false;
        }

        return $isGranted;
    }

    /**
     * @param string $attribute
     * @param string $class
     * @return bool
     */
    protected function isGrantedLocalPermission($attribute, $class)
    {
        $securityProvider = $this->getSecurityProvider();

        switch ($attribute) {
            case self::ATTRIBUTE_VIEW:
                $isGranted = $securityProvider->isGrantedViewLocal($class);
                break;

            case self::ATTRIBUTE_EDIT:
                $isGranted = $securityProvider->isGrantedEditLocal($class);
                break;

            default:
                $isGranted = false;
        }

        return $isGranted;
    }

    /**
     * @return AccountUserProvider
     */
    protected function getSecurityProvider()
    {
        return $this->container->get('orob2b_account.security.account_user_provider');
    }
}
