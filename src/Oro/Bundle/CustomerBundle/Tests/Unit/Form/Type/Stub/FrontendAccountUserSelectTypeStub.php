<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class FrontendAccountUserSelectTypeStub extends AbstractType
{
    const NAME = 'oro_account_frontend_account_user_select';

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
                'class' => 'OroCustomerBundle:AccountUser',
                'query_builder' => function (AccountUserRepository $repository) {
                    return $repository->getAccountUsersQueryBuilder($this->aclHelper);
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

