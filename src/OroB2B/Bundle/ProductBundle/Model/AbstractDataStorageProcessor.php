<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

abstract class AbstractDataStorageProcessor implements ComponentProcessorInterface
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var ProductDataStorage */
    protected $storage;

    /** @var string */
    protected $redirectRouteName;

    /**
     * @param UrlGeneratorInterface $router
     * @param ProductDataStorage $storage
     * @param string $redirectRouteName
     */
    public function __construct(UrlGeneratorInterface $router, ProductDataStorage $storage, $redirectRouteName)
    {
        $this->router = $router;
        $this->storage = $storage;
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
     * {@inheritdoc}
     */
    abstract public function getName();

    /**
     * @param string $routeName
     * @return string
     */
    protected function getUrl($routeName)
    {
        return $this->router->generate($routeName, ['quick_add' => 1]);
    }
}
