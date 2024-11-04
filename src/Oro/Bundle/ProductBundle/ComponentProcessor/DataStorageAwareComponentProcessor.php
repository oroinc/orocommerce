<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
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
    protected ProductMapperInterface $productMapper;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected TokenAccessorInterface $tokenAccessor;
    protected RequestStack $requestStack;
    protected TranslatorInterface $translator;
    protected UrlGeneratorInterface $urlGenerator;
    private ?string $acl = null;
    private ?string $redirectRouteName = null;

    public function __construct(
        ProductDataStorage $storage,
        ProductMapperInterface $productMapper,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
    ) {
        $this->storage = $storage;
        $this->productMapper = $productMapper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function setAcl(string $acl): void
    {
        $this->acl = $acl;
    }

    public function setRedirectRouteName(string $redirectRouteName): void
    {
        $this->redirectRouteName = $redirectRouteName;
    }

    #[\Override]
    public function isAllowed(): bool
    {
        if (!$this->acl) {
            return true;
        }

        return $this->tokenAccessor->hasUser() && $this->authorizationChecker->isGranted($this->acl);
    }

    #[\Override]
    public function process(array $data, Request $request): ?Response
    {
        $inputProductSkus = $this->getProductSkus($data);
        $data = $this->filterData($data);
        $allowedProductSkus = $this->getProductSkus($data);
        $this->checkNotAllowedProducts($inputProductSkus, $allowedProductSkus);
        $allowRedirect = !empty($allowedProductSkus);

        $this->storage->set($data);

        if ($allowRedirect && $this->redirectRouteName) {
            return new RedirectResponse($this->getRedirectUrl($this->redirectRouteName));
        }

        return null;
    }

    protected function getRedirectUrl(string $routeName): string
    {
        return $this->urlGenerator->generate($routeName, [ProductDataStorage::STORAGE_KEY => true]);
    }

    protected function getProductSkus(array $data): array
    {
        $skus = [];
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $item) {
            $skus[] = $item[ProductDataStorage::PRODUCT_SKU_KEY];
        }

        return array_values(array_unique($skus));
    }

    protected function checkNotAllowedProducts(array $inputProductSkus, array $allowedProductSkus): void
    {
        $notAllowedProductSkus = array_diff($inputProductSkus, $allowedProductSkus);
        if (!empty($notAllowedProductSkus)) {
            $this->addFlashMessage(
                'warning',
                $this->translator->trans(
                    'oro.product.frontend.quick_add.messages.not_added_products',
                    ['%count%' => \count($notAllowedProductSkus), '%sku%' => implode(', ', $notAllowedProductSkus)]
                )
            );
        }
    }

    protected function addFlashMessage(string $type, string $message): void
    {
        $this->requestStack->getSession()->getFlashBag()->add($type, $message);
    }

    protected function filterData(array $data): array
    {
        if (empty($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])) {
            return $data;
        }

        $items = new ArrayCollection();
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $dataItem) {
            $items->add(new \ArrayObject($dataItem));
        }

        $this->productMapper->mapProducts($items);

        $updatedData = [];
        /** @var \ArrayObject $item */
        foreach ($items as $item) {
            $updatedData[] = $item->getArrayCopy();
        }
        $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] = $updatedData;

        return $data;
    }
}
