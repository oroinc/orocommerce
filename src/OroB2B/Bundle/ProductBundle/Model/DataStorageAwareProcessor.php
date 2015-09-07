<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class DataStorageAwareProcessor implements ComponentProcessorInterface
{
    const QUICK_ADD_PARAM = 'quick_add';

    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var ProductDataStorage */
    protected $storage;

    /** @var string */
    protected $name;

    /** @var string */
    protected $redirectRouteName;

    /** @var string */
    protected $acl;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param UrlGeneratorInterface $router
     * @param ProductDataStorage $storage
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        UrlGeneratorInterface $router,
        ProductDataStorage $storage,
        SecurityFacade $securityFacade
    ) {
        $this->router = $router;
        $this->storage = $storage;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
        return $this->securityFacade->isGranted($this->acl);
    }

    /**
     * @param string $redirectRouteName
     */
    public function setRedirectRouteName($redirectRouteName)
    {
        $this->redirectRouteName = $redirectRouteName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $data, Request $request)
    {
        $this->storage->set($data);

        return empty($this->redirectRouteName) ? null : new RedirectResponse($this->getUrl($this->redirectRouteName));
    }

    /**
     * @param string $routeName
     * @return string
     */
    protected function getUrl($routeName)
    {
        return $this->router->generate($routeName, [self::QUICK_ADD_PARAM => 1]);
    }
}
