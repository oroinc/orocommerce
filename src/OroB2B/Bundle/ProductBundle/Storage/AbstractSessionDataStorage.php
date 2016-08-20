<?php

namespace Oro\Bundle\ProductBundle\Storage;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractSessionDataStorage extends AbstractDataStorage implements DataStorageInterface
{
    const BAG_NAME = 'product_data_bag';

    /** @var SessionInterface */
    protected $session;

    /** @var AttributeBagInterface */
    protected $bag;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @return AttributeBagInterface
     */
    protected function getBag()
    {
        if (!$this->bag) {
            $this->bag = $this->session->getBag(self::BAG_NAME);
        }

        return $this->bag;
    }

    /** {@inheritdoc} */
    public function set(array $data)
    {
        $this->getBag()->set($this->getKey(), $this->prepareData($data));
    }

    /** {@inheritdoc} */
    public function get()
    {
        if (!$this->getBag()->has($this->getKey())) {
            return [];
        }

        return $this->parseData($this->getBag()->get($this->getKey(), null));
    }

    /** {@inheritdoc} */
    public function remove()
    {
        if ($this->getBag()->has($this->getKey())) {
            $this->getBag()->remove($this->getKey());
        }
    }

    /**
     * @return string
     */
    abstract protected function getKey();
}
