<?php

namespace OroB2B\Bundle\SaleBundle\Acl\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class AccountVoter extends AbstractEntityVoter
{
    const ATTRIBUTE_VIEW = 'CUSTOM_VIEW';

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
     * @var Quote
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
        /* @var $securityFacade SecurityFacade */
        $securityFacade = $this->container->get('oro_security.security_facade');

        /* @var $user AccountUser */
        $user = $securityFacade->getLoggedUser();

        if (!$user instanceof AccountUser) {
            return self::ACCESS_ABSTAIN;
        }

        if ($securityFacade->isGrantedClassMask(EntityMaskBuilder::MASK_VIEW_LOCAL, $class)) {
            if ($user->getCustomer() === $this->object->getAccount()) {
                return self::ACCESS_GRANTED;
            }
        } else {
            if ($user === $this->object->getAccountUser()) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
