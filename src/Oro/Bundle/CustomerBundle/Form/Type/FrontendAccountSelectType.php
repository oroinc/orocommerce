<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

class FrontendAccountSelectType extends AbstractType
{
    const NAME = 'oro_customer_frontend_account_select';

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
                'class' => 'OroCustomerBundle:Account',
                'query_builder' => $this->getAccountsQueryBuilder(),
            ]
        );
    }

    /**
     * @return QueryBuilder
     */
    private function getAccountsQueryBuilder()
    {
        $entityRepository = $this->registry->getRepository('OroCustomerBundle:Account');
        $criteria = new Criteria();
        $qb = $entityRepository->createQueryBuilder('account');
        $this->aclHelper->applyAclToCriteria(
            AccountUser::class,
            $criteria,
            'VIEW',
            ['account' => 'account.id']
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
