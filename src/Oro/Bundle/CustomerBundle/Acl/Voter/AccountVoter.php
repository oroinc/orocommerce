<?php

namespace Oro\Bundle\CustomerBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\AccountOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\AccountUserProvider;

class AccountVoter extends AbstractEntityVoter implements ContainerAwareInterface
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
    private $container;

    /**
     * @var AccountOwnerAwareInterface
     */
    protected $object;

    /**
     * @var AccountUser
     */
    protected $user;

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
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_a($class, AccountOwnerAwareInterface::class, true);
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $user = $this->getUser($token);
        if (!$user instanceof AccountUser) {
            return self::ACCESS_ABSTAIN;
        }

        $this->object = $object;
        $this->user = $user;

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
     * @param TokenInterface $token
     * @return mixed
     */
    protected function getUser(TokenInterface $token)
    {
        $trustResolver = $this->getAuthenticationTrustResolver();
        if ($trustResolver->isAnonymous($token)) {
            $user = new AccountUser();
            $relationsProvider = $this->getRelationsProvider();
            $user->setAccount($relationsProvider->getAccountIncludingEmpty());

            return $user;
        }

        return $token->getUser();
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

            return self::ACCESS_DENIED;
        }

        if ($this->isGrantedBasicPermission($attribute, $class) && $this->isSameUser($this->user, $this->object)) {
            return self::ACCESS_GRANTED;
        }

        if ($this->isGrantedLocalPermission($attribute, $class)) {
            if ($this->isSameAccount($this->user, $this->object) || $this->isSameUser($this->user, $this->object)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_DENIED;
    }

    /**
     * @param AccountUser $user
     * @param AccountOwnerAwareInterface $object
     * @return bool
     */
    protected function isSameAccount(AccountUser $user, AccountOwnerAwareInterface $object)
    {
        return $object->getAccount() && $user->getAccount()->getId() === $object->getAccount()->getId();
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
        $securityFacade = $this->getSecurityFacade();
        $descriptor = $this->getDescriptorByClass($class);

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
        return $this->getContainer()->get('oro_customer.security.account_user_provider');
    }

    /**
     * @return AuthenticationTrustResolverInterface
     */
    protected function getAuthenticationTrustResolver()
    {
        return $this->getContainer()->get('security.authentication.trust_resolver');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->getContainer()->get('oro_security.security_facade');
    }

    /**
     * @return AccountUserRelationsProvider
     */
    protected function getRelationsProvider()
    {
        return $this->getContainer()->get('oro_customer.provider.account_user_relations_provider');
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getDescriptorByClass($class)
    {
        return sprintf('entity:%s@%s', AccountUser::SECURITY_GROUP, $class);
    }
}
