<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Event\CategoryEditEvent;

class CategoryEditSubscriber implements EventSubscriberInterface
{
    const CATEGORY_VISIBILITY = 'category_visibility';
    const ACCOUNT_CATEGORY_VISIBILITY = 'acc_ctgry_visibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY = 'acc_grp_ctgry_vsblity';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /** @var  ArrayCollection|EntityManager[] */
    protected $entityManagers;

    /**
     * @param DoctrineHelper    $doctrineHelper
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, EnumValueProvider $enumValueProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumValueProvider = $enumValueProvider;
        $this->entityManagers = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [CategoryEditEvent::NAME => 'onCategoryEdit'];
    }

    /**
     * @param CategoryEditEvent $event
     */
    public function onCategoryEdit(CategoryEditEvent $event)
    {
        $category = $event->getCategory();
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

        foreach ($this->entityManagers as $em) {
            $em->flush();
        }
    }

    /**
     * @param Category          $category
     * @param AbstractEnumValue $visibilityEnum
     */
    protected function processCategoryVisibility(Category $category, AbstractEnumValue $visibilityEnum)
    {
        $categoryVisibility = $this
            ->doctrineHelper
            ->getEntityRepository('OroB2BAccountBundle:CategoryVisibility')
            ->findOneBy(['category' => $category]);

        if (!$categoryVisibility) {
            $categoryVisibility = (new CategoryVisibility())
                ->setCategory($category);
        }

        $this->applyVisibility($categoryVisibility, self::CATEGORY_VISIBILITY, $visibilityEnum->getId());
    }

    /**
     * @param Category        $category
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
     * @param Category        $category
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

        if (!$this->entityManagers->offsetExists($entityClass)) {
            $em = $this->doctrineHelper->getEntityManager($visibilityEntity);
            $this->entityManagers->offsetSet($entityClass, $em);
        } else {
            $em = $this->entityManagers->offsetGet($entityClass);
        }

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
     * @param Category        $category
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
        $repo = $this
            ->doctrineHelper
            ->getEntityRepository('OroB2BAccountBundle:AccountCategoryVisibility');

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
     * @param Category        $category
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

        /** @var AccountGroupCategoryVisibilityRepository $repo */
        $repo = $this
            ->doctrineHelper
            ->getEntityRepository('OroB2BAccountBundle:AccountGroupCategoryVisibility');

        $visibilities = new ArrayCollection();
        $repo
            ->findForAccountGroups($accountGroups, $category)
            ->map(
                function ($visibility) use ($visibilities) {
                    /** @var AccountGroupCategoryVisibility $visibility */
                    $visibilities->offsetSet($visibility->getAccountGroup()->getId(), $visibility);
                }
            );

        return $visibilities;
    }
}
