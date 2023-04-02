<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to quick order process and save the handling result into {@see ProductDataStorage}.
 */
class DataStorageAwareComponentProcessor implements ComponentProcessorInterface
{
    protected ProductDataStorage $storage;
    protected ProductRepository $productRepository;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected TokenAccessorInterface $tokenAccessor;
    protected RequestStack $requestStack;
    protected TranslatorInterface $translator;
    protected UrlGeneratorInterface $router;
    private ?string $acl = null;
    private ?string $redirectRouteName = null;

    public function __construct(
        ProductDataStorage $storage,
        ProductRepository $productRepository,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        UrlGeneratorInterface $router,
    ) {
        $this->storage = $storage;
        $this->productRepository = $productRepository;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function setAcl(string $acl): void
    {
        $this->acl = $acl;
    }

    public function setRedirectRouteName(string $redirectRouteName): void
    {
        $this->redirectRouteName = $redirectRouteName;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowed(): bool
    {
        if (!$this->acl) {
            return true;
        }

        return $this->tokenAccessor->hasUser() && $this->authorizationChecker->isGranted($this->acl);
    }

    /**
     * {@inheritDoc}
     */
    public function process(array $data, Request $request): ?Response
    {
        $inputProductSkus = $this->getProductSkus($data);
        $data = $this->filterData($data);
        $filteredProductSkus = $this->getProductSkus($data);
        $this->checkNotAllowedProducts($inputProductSkus, $filteredProductSkus);
        $allowRedirect = !empty($filteredProductSkus);

        $this->storage->set($data);

        if ($allowRedirect && $this->redirectRouteName) {
            return new RedirectResponse($this->getRedirectUrl($this->redirectRouteName));
        }

        return null;
    }

    protected function getRedirectUrl(string $routeName): string
    {
        return $this->router->generate($routeName, [ProductDataStorage::STORAGE_KEY => true]);
    }

    protected function getProductSkus(array $data): array
    {
        return array_values(array_unique(array_map(
            function ($entityItem) {
                return $entityItem[ProductDataStorage::PRODUCT_SKU_KEY] ?? null;
            },
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]
        )));
    }

    protected function checkNotAllowedProducts(array $inputProductSkus, array $allowedProductSkus): void
    {
        $notAllowedProductSkus = array_diff($inputProductSkus, $allowedProductSkus);
        if (!empty($notAllowedProductSkus)) {
            $message = $this->translator->trans(
                'oro.product.frontend.quick_add.messages.not_added_products',
                ['%count%' => \count($notAllowedProductSkus), '%sku%' => implode(', ', $notAllowedProductSkus)]
            );
            $this->addFlashMessage('warning', $message);
        }
    }

    protected function addFlashMessage(string $type, string $message): void
    {
        $this->requestStack->getSession()->getFlashBag()->add($type, $message);
    }

    protected function filterData(array $data): array
    {
        $products = [];
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $product) {
            $skuUppercase = mb_strtoupper($product[ProductDataStorage::PRODUCT_SKU_KEY]);
            $products[$skuUppercase][] = $product;
        }

        $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] = [];

        if (empty($products)) {
            return $data;
        }

        $searchQuery = $this->productRepository->getFilterSkuQuery(array_keys($products));
        // Add marker `autocomplete_record_id` to be able to determine query context in listeners
        // `autocomplete_record_id` is used to be same to Quick Order Form behaviour
        $searchQuery->addSelect('integer.system_entity_id as autocomplete_record_id');

        $searchResult = $searchQuery->getResult();
        if (null === $searchResult) {
            throw new \RuntimeException('Result of search query cannot be null.');
        }

        /** @var Item[] $filteredProducts */
        $filteredProducts = $searchResult->toArray();
        foreach ($filteredProducts as $item) {
            $itemData = $item->getSelectedData();
            if (isset($itemData['sku'])) {
                $skuUppercase = mb_strtoupper($itemData['sku']);
                foreach ($products[$skuUppercase] as $product) {
                    $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = $product;
                }
            }
        }

        return $data;
    }
}
