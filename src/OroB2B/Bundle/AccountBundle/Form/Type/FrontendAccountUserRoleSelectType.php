<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;

class FrontendAccountUserRoleSelectType extends AbstractType
{
    const NAME = 'orob2b_account_frontend_account_user_role_select';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $roleClass;

    /**
     * @param SecurityFacade $securityFacade
     * @param Registry $registry
     */
    public function __construct(SecurityFacade $securityFacade, Registry $registry)
    {
        $this->securityFacade = $securityFacade;
        $this->registry = $registry;
    }

    /**
     * @return ManagerRegistry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return AccountUserRoleSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $loggedUser = $this->securityFacade->getLoggedUser();
        if (!$loggedUser instanceof AccountUser) {
            return;
        }

        $resolver->setNormalizer('loader', function () use ($loggedUser) {
            /** @var $repo AccountUserRoleRepository */
            $repo = $this->registry->getManagerForClass($this->roleClass)
                ->getRepository($this->roleClass);
            /** @var  $qb QueryBuilder */
            $qb = $repo->getAvailableRolesByAccountUserQueryBuilder($loggedUser);

            return new ORMQueryBuilderLoader($qb);
        });
    }

    /**
     * @param string $roleClass
     */
    public function setRoleClass($roleClass)
    {
        $this->roleClass = $roleClass;
    }
}
