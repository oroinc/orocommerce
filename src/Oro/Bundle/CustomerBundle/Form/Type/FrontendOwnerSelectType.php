<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
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
                'choice_label' => function ($owner) {
                    if ($owner instanceof AccountUser) {
                        return $owner->getFullName();
                    }

                    return (string)$owner;
                },
                'class' => null,
            ]
        );

        $resolver->setDefined('targetObject');
        $resolver->setDefined('query_builder');
        $resolver->setDefined('class');

        $resolver->setNormalizer('query_builder', function (Options $options) {
            $data = $options['targetObject'];
            $class = ClassUtils::getClass($data);
            $permission = 'CREATE';
            $em = $this->registry->getManagerForClass($class);
            if ($em->contains($data)) {
                $permission = 'EDIT';
            }
            $config = $this->configProvider->getConfig($class);
            $ownerClass = $this->getOwnerClass($config);

            $criteria = new Criteria();
            $ownerFieldName = $config->get('frontend_owner_field_name');
            $organizationFieldName = $config->get('organization_field_name');
            $this->aclHelper->applyAclToCriteria(
                $class,
                $criteria,
                $permission,
                [$ownerFieldName => 'owner.id', $organizationFieldName => 'owner.organization']
            );

            /** @var EntityRepository $repo */
            $repo = $this->registry->getRepository($ownerClass);
            $qb = $repo
                ->createQueryBuilder('owner')
                ->addCriteria($criteria);

            return $qb;
        });

        $resolver->setNormalizer('class', function (Options $options) {
            $data = $options['targetObject'];
            $class = ClassUtils::getClass($data);
            $config = $this->configProvider->getConfig($class);
            return $this->getOwnerClass($config);
        });
    }

    /**
     * @param ConfigInterface $config
     * @return string
     */
    private function getOwnerClass(ConfigInterface $config)
    {
        $ownerType = $config->get('frontend_owner_type');
        $ownerClass = ($ownerType == 'FRONTEND_ACCOUNT') ? Account::class : AccountUser::class;
        return $ownerClass;
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
