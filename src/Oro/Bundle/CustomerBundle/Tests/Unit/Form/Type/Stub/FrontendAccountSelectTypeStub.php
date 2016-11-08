<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class FrontendAccountSelectTypeStub extends AbstractType
{
    const NAME = 'oro_customer_frontend_account_select';

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => 'OroCustomerBundle:Account',
                'query_builder' => function (AccountRepository $repository) {
                    return $repository->getAccountsQueryBuilder($this->aclHelper);
                },
            ]
        );
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
        return 'genemu_jqueryselect2_translatable_entity';
    }
}
