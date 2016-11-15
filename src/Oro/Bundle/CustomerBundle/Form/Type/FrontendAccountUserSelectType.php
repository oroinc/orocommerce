<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class FrontendAccountUserSelectType extends AbstractType
{
    const NAME = 'oro_account_frontend_account_user_select';

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param AclHelper $aclHelper
     * @param ManagerRegistry $registry
     */
    public function __construct(AclHelper $aclHelper, ManagerRegistry $registry)
    {
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => 'OroCustomerBundle:AccountUser',
                'choice_label' => function (AccountUser $user) {
                    return $user->getFullName();
                },
                'query_builder' => $this->getAccountUsersQueryBuilder()
            ]
        );
    }

    /**
     * @return QueryBuilder
     */
    private function getAccountUsersQueryBuilder()
    {
        /** @var EntityRepository $entityRepository */
        $entityRepository = $this->registry->getRepository('OroCustomerBundle:AccountUser');
        $criteria = new Criteria();
        $qb = $entityRepository->createQueryBuilder('account_user');
        $this->aclHelper->applyAclToCriteria(
            AccountUser::class,
            $criteria,
            'VIEW',
            ['account' => 'account_user.account']
        );
        return $qb->addCriteria($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_translatable_entity';
    }
}
