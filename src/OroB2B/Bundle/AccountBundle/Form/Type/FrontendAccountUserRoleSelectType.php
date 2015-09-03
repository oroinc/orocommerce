<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;

class FrontendAccountUserRoleSelectType extends AbstractType
{
    const NAME = 'orob2b_account_frontend_account_user_role_select';

    /** @var   SecurityFacade */
    protected $securityFacade;

    /** @var ManagerRegistry */
    protected $registry;

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
        if (!$this->securityFacade->getLoggedUser())
            return;
        $resolver->setNormalizer('loader', function () {
            /** @var $repo AccountUserRoleRepository */
            $repo = $this->registry->getManagerForClass('OroB2BAccountBundle:AccountUserRole')
                ->getRepository('OroB2BAccountBundle:AccountUserRole');
            /** @var  $qb QueryBuilder */
            $qb = $repo->getAvailableRolesByAccountUserQueryBuilder($this->securityFacade->getLoggedUser());
            return new ORMQueryBuilderLoader($qb);
        });
    }
}
