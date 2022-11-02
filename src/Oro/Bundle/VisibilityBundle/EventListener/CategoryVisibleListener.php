<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Restricts access to the category based on its visibility settings.
 */
class CategoryVisibleListener
{
    // listener should run only on this route
    const PRODUCT_INDEX_ROUTE = 'oro_product_frontend_product_index';

    /**
     * @var CategoryVisibilityResolverInterface
     */
    private $categoryVisibilityResolver;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var CustomerUserRelationsProvider
     */
    private $customerUserRelationsProvider;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        ManagerRegistry $registry,
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
        CustomerUserRelationsProvider $customerUserRelationsProvider,
        TokenStorageInterface $tokenStorage
    ) {
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->registry = $registry;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @throws NotFoundHttpException
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isApplicable($request)) {
            return;
        }

        /** @var Category $category */
        $category = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->find((int)$request->get(RequestProductHandler::CATEGORY_ID_KEY));

        if (!$category || !$this->isCategoryVisible($category)) {
            $this->throwCategoryNotFound($request);
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isApplicable(Request $request)
    {
        $route = $request->attributes->get('_route');

        if ($route === null || $route !== static::PRODUCT_INDEX_ROUTE) {
            return false;
        }

        return $request->get(RequestProductHandler::CATEGORY_ID_KEY) !== null;
    }

    /**
     * @param Category $category
     * @return bool
     */
    private function isCategoryVisible(Category $category)
    {
        $user = $this->getUser();
        $customer = $this->customerUserRelationsProvider->getCustomer($user);
        $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup($user);

        if ($customer) {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisibleForCustomer($category, $customer);
        } elseif ($customerGroup) {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisibleForCustomerGroup(
                $category,
                $customerGroup
            );
        } else {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisible($category);
        }

        return $isCategoryVisible;
    }

    /**
     * @return UserInterface|null
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        if (!is_object($user)) {
            return null;
        }

        return $user;
    }

    /**
     * @throws NotFoundHttpException
     */
    private function throwCategoryNotFound($request)
    {
        throw new NotFoundHttpException(sprintf(
            'Category %s has not been found',
            $request->get(RequestProductHandler::CATEGORY_ID_KEY)
        ));
    }
}
