<?php
namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;


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
        $resolver->setOptional(['loader']);
        $resolver->setNormalizer('loader', function () {
            $qb = $this->registry->getManager()
                ->getRepository('OroB2BAccountBundle:AccountUserRole')
                ->getAvailableRolesByAccountUserQueryBuilder($this->securityFacade->getLoggedUser());
            return new ORMQueryBuilderLoader($qb);
        });
    }


}
