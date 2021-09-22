<?php

namespace Oro\Bundle\ProductBundle\Storage;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Base class for data storages in session.
 */
abstract class AbstractSessionDataStorage extends AbstractDataStorage implements DataStorageInterface
{
    protected RequestStack $requestStack;

    protected ?AttributeBagInterface $bag = null;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    protected function getBag(): AttributeBagInterface
    {
        if (!$this->bag) {
            $bag = $this->requestStack->getSession()->getBag('product_data_bag');
            if (!$bag instanceof AttributeBagInterface) {
                throw new \LogicException(
                    'Session bag %s was expected to be of type %s',
                    'product_data_bag',
                    AttributeBagInterface::class
                );
            }

            $this->bag = $bag;
        }

        return $this->bag;
    }

    public function set(array $data): void
    {
        $this->getBag()->set($this->getKey(), $this->prepareData($data));
    }

    public function get(): array
    {
        if (!$this->getBag()->has($this->getKey())) {
            return [];
        }

        return $this->parseData($this->getBag()->get($this->getKey(), null));
    }

    public function remove(): void
    {
        if ($this->getBag()->has($this->getKey())) {
            $this->getBag()->remove($this->getKey());
        }
    }

    /**
     * Returns the key for the element that stores storage data in a session bag.
     *
     * @return string
     */
    abstract protected function getKey(): string;
}
