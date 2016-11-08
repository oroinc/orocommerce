<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserRepository;

class FrontendAccountUserSelectType extends AbstractType
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => 'OroCustomerBundle:AccountUser',
                'choice_label' => function (AccountUser $user) {
                    return $user->getFirstName().' '.$user->getLastName();
                },
                'query_builder' => function (AccountUserRepository $repository) {
                    return $repository->getAccountUsersQueryBuilder($this->aclHelper);
                }
            ]
        );
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
