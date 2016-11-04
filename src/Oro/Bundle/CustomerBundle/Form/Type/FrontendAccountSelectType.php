<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendAccountSelectType extends AbstractType
{
    const NAME = 'oro_customer_frontend_account_select';

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param AclHelper $aclHelper
     * @param Registry $registry
     */
    public function __construct(AclHelper $aclHelper, Registry $registry)
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
                'query_builder' => $this->getAccounts()
            ]
        );
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository|AccountUserRepository
     */
    public function getAccounts()
    {
        return $this->registry
            ->getManagerForClass('OroCustomerBundle:AccountUser')
            ->getRepository('OroCustomerBundle:AccountUser');
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
