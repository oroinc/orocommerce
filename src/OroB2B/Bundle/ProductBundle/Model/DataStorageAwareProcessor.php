<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    /**
     * @param UrlGeneratorInterface $router
     * @param ProductDataStorage $storage
     */
    public function __construct(UrlGeneratorInterface $router, ProductDataStorage $storage)
    {
        $this->router = $router;
        $this->storage = $storage;
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
