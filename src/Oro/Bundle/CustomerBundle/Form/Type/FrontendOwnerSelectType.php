<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendOwnerSelectType extends AbstractType
{
    const NAME = 'oro_customer_frontend_owner_select';

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param AclHelper $aclHelper
     * @param ManagerRegistry $registry
     * @param ConfigProvider $configProvider
     */
    public function __construct(AclHelper $aclHelper, ManagerRegistry $registry, ConfigProvider $configProvider)
    {
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => 'OroCustomerBundle:Account',
                'choice_label' => function ($owner) {
                    if ($owner instanceof AccountUser) {
                        return $owner->getFullName();
                    }

                    return (string)$owner;
                },
            ]
        );
        $resolver->setDefined('targetObject');

        $resolver->setNormalizer('query_builder', function (Options $options, $value) {
            $data = $options['targetObject'];
            $class = ClassUtils::getClass($data);
            $permission = 'CREATE';
            $em = $this->registry->getManagerForClass($class);
            if ($em->contains($data)) {
                $permission = 'UPDATE';
            }

            $config = $this->configProvider->getConfig($class);

            $ownerType = $config->get('frontend_owner_type');
            $ownerClass = AccountUser::class;
            if ($ownerType == 'FRONTEND_ACCOUNT') {
                $ownerClass = Account::class;
            }
            if (!$class) {
                $class = $ownerClass;
            }

            $criteria = new Criteria();
            $ownerFieldName = $config->get('frontend_owner_field_name');
            $organizationFieldName = $config->get('organization_field_name');
            $this->aclHelper->applyAclToCriteria(
                $class,
                $criteria,
                $permission,
                [$ownerFieldName => 'owner.id', $organizationFieldName => 'owner.organization']
            );

            $qb = $this->registry->getRepository($ownerClass)
                ->createQueryBuilder('owner')
                ->addCriteria($criteria);

            return $qb;
        });
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
