<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CustomerBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\VisibilityBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

class CategoryVisibleListener
{
    // listener should run only on this route
    const PRODUCT_INDEX_ROUTE = 'oro_product_frontend_product_index';

    /**
     * @var CategoryVisibilityResolverInterface
     */
    private $categoryVisibilityResolver;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var AccountUserRelationsProvider
     */
    private $accountUserRelationsProvider;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param CategoryRepository                  $categoryRepository
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     * @param AccountUserRelationsProvider        $accountUserRelationsProvider
     * @param TokenStorageInterface               $tokenStorage
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
        AccountUserRelationsProvider $accountUserRelationsProvider,
        TokenStorageInterface $tokenStorage
    ) {
        $this->categoryVisibilityResolver   = $categoryVisibilityResolver;
        $this->categoryRepository           = $categoryRepository;
        $this->accountUserRelationsProvider = $accountUserRelationsProvider;
        $this->tokenStorage                 = $tokenStorage;
    }

    /**
     * @param FilterControllerEvent $event
     * @throws NotFoundHttpException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->isApplicable($request)) {
            return;
        }

        /** @var Category $category */
        $category = $this->categoryRepository->find((int)$request->get(RequestProductHandler::CATEGORY_ID_KEY));

        if ($category === null) {
            $this->throwCategoryNotFound($request);
        }

        if (!$this->isCategoryVisible($category)) {
            $this->throwCategoryNotFound($request);
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isApplicable(Request $request)
    {
        $isApplicable = true;
        $route        = $request->attributes->get('_route', null);

        if ($route === null || $route !== static::PRODUCT_INDEX_ROUTE) {
            $isApplicable = false;
        }

        $categoryExistsInUri = $request->get(RequestProductHandler::CATEGORY_ID_KEY, null) !== null;

        if (!$categoryExistsInUri) {
            $isApplicable = false;
        }

        return $isApplicable;
    }

    /**
     * @param $category
     * @return bool
     */
    private function isCategoryVisible($category)
    {
        $user         = $this->getUser();
        $account      = $this->accountUserRelationsProvider->getAccount($user);
        $accountGroup = $this->accountUserRelationsProvider->getAccountGroup($user);

        if ($account) {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisibleForAccount($category, $account);

        } elseif ($accountGroup) {
            $isCategoryVisible = $this->categoryVisibilityResolver->isCategoryVisibleForAccountGroup(
                $category,
                $accountGroup
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
     * @param $request
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
