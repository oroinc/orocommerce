<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use PHPUnit\Framework\Constraint\Constraint;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait VisibilityAwareTestTrait
{
    use DefaultWebsiteIdTestTrait;

    private function updateVisibility(ManagerRegistry $registry, VisibilityInterface $visibility): VisibilityInterface
    {
        $em = $registry->getManagerForClass(ClassUtils::getClass($visibility));
        $em->persist($visibility);
        $em->flush();

        return $visibility;
    }

    private function getCategoryVisibility(ManagerRegistry $registry, Category $category): VisibilityInterface
    {
        $entity = $registry->getRepository(CategoryVisibility::class)
            ->findOneBy(['category' => $category]);

        if (!$entity) {
            $entity = (new CategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity::getDefault($entity->getTargetEntity()));
        }

        return $entity;
    }

    private function getCategoryVisibilityForCustomerGroup(
        ManagerRegistry $registry,
        Category $category,
        CustomerGroup $customerGroup
    ): VisibilityInterface {
        $scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $entity = $registry->getRepository(CustomerGroupCategoryVisibility::class)
            ->findOneBy(['category' => $category, 'scope' => $scope]);

        if (!$entity) {
            $entity = (new CustomerGroupCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity::getDefault($entity->getTargetEntity()))
                ->setScope($scope);
        }

        return $entity;
    }

    private function getCategoryVisibilityForCustomer(
        ManagerRegistry $registry,
        Category $category,
        Customer $customer
    ): VisibilityInterface {
        $scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $entity = $registry->getRepository(CustomerCategoryVisibility::class)
            ->findOneBy(['category' => $category, 'scope' => $scope]);

        if (!$entity) {
            $entity = (new CustomerCategoryVisibility());
            $entity->setTargetEntity($category)
                ->setVisibility($entity::getDefault($entity->getTargetEntity()))
                ->setScope($scope);
        }

        return $entity;
    }

    private static function createProductVisibility(Product $product, string $visibility): ProductVisibility
    {
        $visibility = (new ProductVisibility())
            ->setScope(self::getScopeForProductVisibility())
            ->setProduct($product)
            ->setVisibility($visibility);
        $entityManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(ProductVisibility::class);
        $entityManager->persist($visibility);
        $entityManager->flush($visibility);

        return $visibility;
    }

    private static function createCustomerGroupProductVisibility(
        Product $product,
        CustomerGroup $customerGroup,
        string $visibility
    ): CustomerGroupProductVisibility {
        $visibility = (new CustomerGroupProductVisibility())
            ->setScope(self::getScopeForCustomerGroupVisibility($customerGroup))
            ->setProduct($product)
            ->setVisibility($visibility);
        $entityManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(CustomerGroupProductVisibility::class);
        $entityManager->persist($visibility);
        $entityManager->flush($visibility);

        return $visibility;
    }

    private static function createCustomerProductVisibility(
        Product $product,
        Customer $customer,
        string $visibility
    ): CustomerProductVisibility {
        $visibility = (new CustomerProductVisibility())
            ->setScope(self::getScopeForCustomerVisibility($customer))
            ->setProduct($product)
            ->setVisibility($visibility);
        $entityManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(CustomerProductVisibility::class);
        $entityManager->persist($visibility);
        $entityManager->flush($visibility);

        return $visibility;
    }

    private static function assertProductVisibility(
        Constraint $constraint,
        Product $product,
        ?CustomerUser $customerUser = null
    ): void {
        if ($customerUser) {
            $tokenStorage = self::getContainer()->get('security.token_storage');
            $originalToken = $tokenStorage->getToken();
            $tokenStorage->setToken(new UsernamePasswordToken($customerUser, 'frontend', [$customerUser->getRoles()]));
        }

        try {
            $resolvedProductVisibilityProvider = self::getContainer()
                ->get('oro_visibility.provider.resolved_product_visibility_provider');
            $resolvedProductVisibilityProvider->clearCache($product->getId());

            self::assertThat($resolvedProductVisibilityProvider->isVisible($product->getId()), $constraint);
        } finally {
            if ($customerUser) {
                $tokenStorage->setToken($originalToken);
            }
        }
    }

    private static function getScopeForProductVisibility(): Scope
    {
        return self::getContainer()
            ->get('oro_scope.scope_manager')
            ->findOrCreate(ProductVisibility::VISIBILITY_TYPE);
    }

    private static function getScopeForCustomerGroupVisibility(
        CustomerGroup $customerGroup,
        ?Website $website = null
    ): Scope {
        return self::getContainer()
            ->get('oro_scope.scope_manager')
            ->findOrCreate(
                CustomerGroupProductVisibility::VISIBILITY_TYPE,
                ['customerGroup' => $customerGroup, 'website' => $website ?? self::getDefaultWebsite()]
            );
    }

    private static function getScopeForCustomerVisibility(Customer $customer, ?Website $website = null): Scope
    {
        return self::getContainer()
            ->get('oro_scope.scope_manager')
            ->findOrCreate(
                CustomerProductVisibility::VISIBILITY_TYPE,
                ['customer' => $customer, 'website' => $website ?? self::getDefaultWebsite()]
            );
    }

    private static function assertCategoryVisibility(
        Constraint $constraint,
        Category $category,
        ?CustomerUser $customerUser = null
    ): void {
        if ($customerUser) {
            $tokenStorage = self::getContainer()->get('security.token_storage');
            $originalToken = $tokenStorage->getToken();
            $tokenStorage->setToken(new UsernamePasswordToken($customerUser, 'frontend', [$customerUser->getRoles()]));
        }

        try {
            $categoryVisibilityResolver = self::getContainer()
                ->get('oro_visibility.visibility.resolver.category_visibility_resolver');

            self::assertThat(
                $categoryVisibilityResolver->isCategoryVisibleForCustomer($category, $customerUser->getCustomer()),
                $constraint
            );
        } finally {
            if ($customerUser) {
                $tokenStorage->setToken($originalToken);
            }
        }
    }

    private static function createCategoryVisibility(Category $category, string $visibility): CategoryVisibility
    {
        $visibility = (new CategoryVisibility())
            ->setScope(self::getScopeForCategoryVisibility())
            ->setCategory($category)
            ->setVisibility($visibility);
        $entityManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(Category::class);
        $entityManager->persist($visibility);
        $entityManager->flush($visibility);

        return $visibility;
    }

    private static function getScopeForCategoryVisibility(?Website $website = null): Scope
    {
        return self::getContainer()
            ->get('oro_scope.scope_manager')
            ->findOrCreate(CategoryVisibility::VISIBILITY_TYPE, ['website' => $website ?? self::getDefaultWebsite()]);
    }
}
