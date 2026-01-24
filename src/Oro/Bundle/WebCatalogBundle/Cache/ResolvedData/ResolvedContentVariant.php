<?php

namespace Oro\Bundle\WebCatalogBundle\Cache\ResolvedData;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Represents a resolved content variant with cached data for improved performance.
 *
 * This class is used to store pre-computed content variant data in the cache, avoiding the need to load
 * full Doctrine entities during content resolution. It implements {@see ContentVariantInterface} and uses
 * magic methods to provide dynamic property access to the cached data array, making it a lightweight alternative
 * to the full ContentVariant entity for read operations.
 */
class ResolvedContentVariant implements ContentVariantInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var Collection|LocalizedFallbackValue[]
     */
    protected $localizedUrls;

    public function __construct()
    {
        $this->localizedUrls = new ArrayCollection();
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getType()
    {
        return $this->type;
    }

    #[\Override]
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getLocalizedUrls()
    {
        return $this->localizedUrls;
    }

    /**
     * @param LocalizedFallbackValue $value
     * @return $this
     */
    public function addLocalizedUrl(LocalizedFallbackValue $value)
    {
        $this->localizedUrls->add($value);

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }
}
