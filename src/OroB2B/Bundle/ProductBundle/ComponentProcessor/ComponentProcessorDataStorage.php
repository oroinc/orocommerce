<?php

namespace OroB2B\Bundle\ProductBundle\ComponentProcessor;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ComponentProcessorDataStorage implements ComponentProcessorInterface
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var ProductDataStorage */
    protected $storage;

    /** @var  ComponentProcessorFilter */
    protected $componentProcessorFilter;

    /** @var string */
    protected $name;

    /** @var string */
    protected $redirectRouteName;

    /** @var bool */
    protected $validationRequired = true;

    /** @var string */
    protected $acl;

    /** @var  string */
    protected $scope;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param UrlGeneratorInterface $router
     * @param ProductDataStorage $storage
     * @param SecurityFacade $securityFacade
     * @param ComponentProcessorFilter $componentProcessorFilter
     */
    public function __construct(
        UrlGeneratorInterface $router,
        ProductDataStorage $storage,
        SecurityFacade $securityFacade,
        ComponentProcessorFilter $componentProcessorFilter
    ) {
        $this->router = $router;
        $this->storage = $storage;
        $this->securityFacade = $securityFacade;
        $this->componentProcessorFilter = $componentProcessorFilter;
    }

    /**
     * @param string $name
     * @return ComponentProcessorInterface
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $acl
     */
    public function setAcl($acl)
    {
        $this->acl = $acl;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        if (!$this->acl) {
            return true;
        }

        return $this->securityFacade->hasLoggedUser() && $this->securityFacade->isGranted($this->acl);
    }

    /**
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * @param string $redirectRouteName
     */
    public function setRedirectRouteName($redirectRouteName)
    {
        $this->redirectRouteName = $redirectRouteName;
    }

    /**
     * @param bool $validationRequired
     * @return ComponentProcessorInterface
     */
    public function setValidationRequired($validationRequired)
    {
        $this->validationRequired = (bool)$validationRequired;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValidationRequired()
    {
        return $this->validationRequired;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $data, Request $request)
    {
        $restrictedData = $this->filterData($data);
        if ($notAllowedProductSkus = $this->getNotAllowedProductSkus($data, $restrictedData)) {
            throw new AccessDeniedException(implode(',', $notAllowedProductSkus) . 'is not allowed');
        }
        $this->storage->set($restrictedData);

        return empty($this->redirectRouteName) ? null : new RedirectResponse($this->getUrl($this->redirectRouteName));
    }

    /**
     * @param string $routeName
     * @return string
     */
    protected function getUrl($routeName)
    {
        return $this->router->generate($routeName, [ProductDataStorage::STORAGE_KEY => true]);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function filterData(array $data)
    {
        return $this->componentProcessorFilter->filterData($data, ['scope' => $this->scope]);
    }

    /**
     * @param array $data
     * @param array $restrictedData
     * @return array
     */
    protected function getNotAllowedProductSkus(array $data, array $restrictedData)
    {
        $notAllowedProductSkus = [];
        $restrictedSkus = $this->getProductsFromData($restrictedData);
        foreach ($this->getProductsFromData($data) as $sku) {
            if (!in_array($sku, $restrictedSkus)) {
                $notAllowedProductSkus[] = $sku;
            }
        }
        return $notAllowedProductSkus;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getProductsFromData(array $data)
    {
        $products = [];
        foreach ($data['entity_items_data'] as $product) {
            $products[$product['productSku']] = $product;
        }
        return $products;
    }
}
