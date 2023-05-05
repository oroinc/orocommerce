<?php

namespace Oro\Bundle\ProductBundle\Search;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * These service should provide name collection of attributes that should be always added to index as a separate fields,
 * because they are used at main product grid of front store
 */
class ProductIndexFieldsProvider implements ProductIndexAttributeProviderInterface
{
    /** @var ArrayCollection */
    protected $fields;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    /**
     * @param string $field
     */
    public function addForceIndexed(string $field) : void
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
        }
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public function isForceIndexed(string $field) : bool
    {
        return $this->fields->contains($field);
    }
}
