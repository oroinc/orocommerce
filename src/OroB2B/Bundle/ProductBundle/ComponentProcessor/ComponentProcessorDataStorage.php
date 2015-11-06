<?php

namespace OroB2B\Bundle\ProductBundle\ComponentProcessor;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class ComponentProcessorDataStorage implements ComponentProcessorInterface
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var ProductDataStorage */
    protected $storage;

    /** @var ComponentProcessorFilter */
    protected $componentProcessorFilter;

    /** @var string */
    protected $name;

    /** @var string */
    protected $redirectRouteName;

    /** @var bool */
    protected $validationRequired = true;

    /** @var string */
    protected $acl;

    /** @var string */
    protected $scope;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var Session */
    protected $session;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param UrlGeneratorInterface $router
     * @param ProductDataStorage $storage
     * @param SecurityFacade $securityFacade
     * @param ComponentProcessorFilter $componentProcessorFilter
     * @param Session $session
     * @param TranslatorInterface $translator
     */
    public function __construct(
        UrlGeneratorInterface $router,
        ProductDataStorage $storage,
        SecurityFacade $securityFacade,
        ComponentProcessorFilter $componentProcessorFilter,
        Session $session,
        TranslatorInterface $translator
    ) {
        $this->router = $router;
        $this->storage = $storage;
        $this->securityFacade = $securityFacade;
        $this->componentProcessorFilter = $componentProcessorFilter;
        $this->session = $session;
        $this->translator = $translator;
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
        if ($this->scope) {
            $inputProductSkus = $this->getProductSkus($data);
            $data = $this->componentProcessorFilter->filterData($data, ['scope' => $this->scope]);
            $allowedProductSkus = $this->getProductSkus($data);
            $this->checkNotAllowedProducts($inputProductSkus, $allowedProductSkus);
        }

        $this->storage->set($data);

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
    protected function getProductSkus(array $data)
    {
        return array_map(
            function ($entityItem) {
                return $entityItem['productSku'];
            },
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]
        );
    }

    /**
     * @param array $inputProductSkus
     * @param array $allowedProductSkus
     */
    protected function checkNotAllowedProducts(array $inputProductSkus, array $allowedProductSkus)
    {
        $notAllowedProductSkus = array_diff($inputProductSkus, $allowedProductSkus);
        if (!empty($notAllowedProductSkus)) {
            $this->addFlashMessage($notAllowedProductSkus);
        }
    }

    /**
     * @param array $skus
     */
    protected function addFlashMessage(array $skus)
    {
        $message = '';
        foreach ($skus as $sku) {
            $message .= $this->translator->trans(
                'orob2b.product.frontend.quick_add.messages.not_added_products',
                ['%sku%' => $sku]
            );
        }
        $this->session->getFlashBag()->add('warning', $message);
    }
}
