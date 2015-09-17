<?php

namespace OroB2B\Bundle\AccountBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\FormBundle\Form\Type\EntityChangesetType;

use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryType;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Form\DataTransformer\EntityChangesetTypeToVisibilityForAccountGroupTransformer;

class CategoryFormExtension extends AbstractTypeExtension
{
    const CATEGORY_VISIBILITY = 'category_visibility';
    const ACCOUNT_CATEGORY_VISIBILITY = 'acc_ctgry_visibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY = 'acc_grp_ctgry_vsblity';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /** @var EntityRepository */
    protected $categoryVisibilityRepository;

    /** @var EntityRepository */
    protected $accountCategoryVisibilityRepository;

    /** @var EntityRepository */
    protected $accountGroupCategoryVisibilityRepository;

    /**
     * @param ManagerRegistry $registry
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(ManagerRegistry $registry, EnumValueProvider $enumValueProvider)
    {
        $this->registry = $registry;
        $this->enumValueProvider = $enumValueProvider;
        $this->categoryVisibilityRepository = $registry
            ->getManagerForClass('OroB2BAccountBundle:CategoryVisibility')
            ->getRepository('OroB2BAccountBundle:CategoryVisibility');
        $this->accountCategoryVisibilityRepository = $registry
            ->getManagerForClass('OroB2BAccountBundle:AccountCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:AccountCategoryVisibility');
        $this->accountGroupCategoryVisibilityRepository = $registry
            ->getManagerForClass('OroB2BAccountBundle:AccountGroupCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:AccountGroupCategoryVisibility');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'categoryVisibility',
                'oro_enum_select',
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.account.categoryvisibility.entity_label',
                    'enum_code' => 'category_visibility',
                    'configs' => [
                        'allowClear' => false,
                        'placeholder' => 'orob2b.account.categoryvisibility.default.label'
                    ]
                ]
            )
            ->add(
                'visibilityForAccount',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\Account'
                ]
            )
            ->add(
                'visibilityForAccountGroup',
                EntityChangesetType::NAME,
                [
                    'class' => 'OroB2B\Bundle\AccountBundle\Entity\AccountGroup'
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);

//        $builder->get('visibilityForAccountGroup')->addModelTransformer(
//            new EntityChangesetTypeToVisibilityForAccountGroupTransformer(
//                $this->accountGroupCategoryVisibilityRepository
//            )
//        );
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Category|null $category */
        $category = $event->getData();

        if (!$category || !$category->getId()) {
            return;
        }

        $this->setCategoryVisibility($event, $category);
        $this->setAccountCategoryVisibility($event, $category);
        $this->setAccountGroupCategoryVisibility($event, $category);
    }

    /**
     * @param FormEvent $event
     * @param Category $category
     */
    protected function setCategoryVisibility(FormEvent $event, Category $category)
    {
        $categoryVisibility = $this->categoryVisibilityRepository->findOneBy(['category' => $category]);

        if ($categoryVisibility instanceof CategoryVisibility) {
            $event->getForm()->get('categoryVisibility')->setData($categoryVisibility->getVisibility());
        }
    }

    /**
     * @param FormEvent $event
     * @param Category $category
     */
    protected function setAccountCategoryVisibility(FormEvent $event, Category $category)
    {
        $accountCategoryVisibilities = $this->accountCategoryVisibilityRepository->findBy(['category' => $category]);

        $accountCategoryVisibilityData = [];
        /** @var AccountCategoryVisibility $accountCategoryVisibility */
        foreach ($accountCategoryVisibilities as $accountCategoryVisibility) {
            $accountCategoryVisibilityData[$accountCategoryVisibility->getAccount()->getId()] = [
                'entity' => $accountCategoryVisibility->getAccount(),
                'data' => [
                    'visibility' => $accountCategoryVisibility->getVisibility()->getId()
                ]
            ];
        }

        if (count($accountCategoryVisibilityData) > 0) {
            $event->getForm()->get('visibilityForAccount')->setData($accountCategoryVisibilityData);
        }
    }

    /**
     * @param FormEvent $event
     * @param Category $category
     */
    protected function setAccountGroupCategoryVisibility(FormEvent $event, Category $category)
    {
        $accountGroupCategoryVisibilities = $this->accountGroupCategoryVisibilityRepository
            ->findBy(['category' => $category]);

        $accountGroupCategoryVisibilityData = [];
        /** @var AccountGroupCategoryVisibility $accountGroupCategoryVisibility */
        foreach ($accountGroupCategoryVisibilities as $accountGroupCategoryVisibility) {
            $accountGroupCategoryVisibilityData[$accountGroupCategoryVisibility->getAccountGroup()->getId()] = [
                'entity' => $accountGroupCategoryVisibility->getAccountGroup(),
                'data' => [
                    'visibility' => $accountGroupCategoryVisibility->getVisibility()->getId()
                ]
            ];
        }

        if (count($accountGroupCategoryVisibilityData) > 0) {
            $event->getForm()->get('visibilityForAccountGroup')->setData($accountGroupCategoryVisibilityData);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var Category $category */
        $category = $event->getData();
        $form = $event->getForm();

        /** @var AbstractEnumValue $visibilityEnum */
        $visibilityEnum = $form->get('categoryVisibility')->getData();
        /** @var ArrayCollection $accountChangeSet */
        $accountChangeSet = $form->get('visibilityForAccount')->getData();
        /** @var ArrayCollection $accountGroupChangeSet */
        $accountGroupChangeSet = $form->get('visibilityForAccountGroup')->getData();

        if ($visibilityEnum) {
            $this->processCategoryVisibility($category, $visibilityEnum);
        }

        if (!$accountChangeSet->isEmpty()) {
            $this->processAccountVisibility($category, $accountChangeSet);
        }

        if (!$accountGroupChangeSet->isEmpty()) {
            $this->processAccountGroupVisibility($category, $accountGroupChangeSet);
        }
    }

    /**
     * @param Category $category
     * @param AbstractEnumValue $visibilityEnum
     */
    protected function processCategoryVisibility(Category $category, AbstractEnumValue $visibilityEnum)
    {
        $categoryVisibility = $this->categoryVisibilityRepository
            ->findOneBy(['category' => $category]);

        if (!$categoryVisibility) {
            $categoryVisibility = (new CategoryVisibility())
                ->setCategory($category);
        }

        $this->applyVisibility($categoryVisibility, self::CATEGORY_VISIBILITY, $visibilityEnum->getId());
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountChangeSet
     */
    protected function processAccountVisibility(Category $category, ArrayCollection $accountChangeSet)
    {
        $accountVisibilities = $this->getAccountVisibilities($category, $accountChangeSet);
        foreach ($accountChangeSet as $item) {
            /** @var Account $account */
            $account = $item['entity'];

            $accountCategoryVisibility = $accountVisibilities->offsetGet($account->getId());
            if (!$accountCategoryVisibility) {
                $accountCategoryVisibility = (new AccountCategoryVisibility())
                    ->setCategory($category)
                    ->setAccount($account);
            }

            $this->applyVisibility(
                $accountCategoryVisibility,
                self::ACCOUNT_CATEGORY_VISIBILITY,
                $item['data']['visibility']
            );
        }
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountChangeSet
     */
    protected function processAccountGroupVisibility(Category $category, ArrayCollection $accountChangeSet)
    {
        $accountGroupVisibilities = $this->getAccountGroupVisibilities($category, $accountChangeSet);
        foreach ($accountChangeSet as $item) {
            /** @var AccountGroup $accountGroup */
            $accountGroup = $item['entity'];

            $accountGroupCategoryVisibility = $accountGroupVisibilities->offsetGet($accountGroup->getId());
            if (!$accountGroupCategoryVisibility) {
                $accountGroupCategoryVisibility = (new AccountGroupCategoryVisibility())
                    ->setCategory($category)
                    ->setAccountGroup($accountGroup);
            }

            $this->applyVisibility(
                $accountGroupCategoryVisibility,
                self::ACCOUNT_GROUP_CATEGORY_VISIBILITY,
                $item['data']['visibility']
            );
        }
    }

    /**
     * @param CategoryVisibility|AccountCategoryVisibility|AccountGroupCategoryVisibility $visibilityEntity
     * @param string $enumCode
     * @param string $visibilityCode
     */
    protected function applyVisibility($visibilityEntity, $enumCode, $visibilityCode)
    {
        $entityClass = ClassUtils::getClass($visibilityEntity);

        $em = $this->registry->getManagerForClass($entityClass);

        if ($visibilityCode === $visibilityEntity->getDefault()) {
            if ($visibilityEntity->getVisibility()) {
                $em->remove($visibilityEntity);
            }

            return;
        }

        $visibility = $this->enumValueProvider->getEnumValueByCode($enumCode, $visibilityCode);
        $visibilityEntity->setVisibility($visibility);

        $em->persist($visibilityEntity);
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountChangeSet
     *
     * @return ArrayCollection|\OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility[]
     */
    protected function getAccountVisibilities(Category $category, ArrayCollection $accountChangeSet)
    {
        /** @var Account[] $accounts */
        $accounts = $accountChangeSet->map(
            function ($item) {
                return $item['entity'];
            }
        )->toArray();

        /** @var AccountCategoryVisibilityRepository $repo */
        $repo = $this->accountCategoryVisibilityRepository;

        $visibilities = new ArrayCollection();
        $repo
            ->findForAccounts($accounts, $category)
            ->map(
                function ($visibility) use ($visibilities) {
                    /** @var AccountCategoryVisibility $visibility */
                    $visibilities->offsetSet($visibility->getAccount()->getId(), $visibility);
                }
            );

        return $visibilities;
    }

    /**
     * @param Category $category
     * @param ArrayCollection $accountGroupChangeSet
     *
     * @return ArrayCollection|\OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility[]
     */
    protected function getAccountGroupVisibilities(Category $category, ArrayCollection $accountGroupChangeSet)
    {
        /** @var AccountGroup[] $accountGroups */
        $accountGroups = $accountGroupChangeSet->map(
            function ($item) {
                return $item['entity'];
            }
        )->toArray();

        $repo = $this->accountGroupCategoryVisibilityRepository;

        $visibilities = $repo->findForAccountGroups($accountGroups, $category);

        return $visibilities;
    }
}
