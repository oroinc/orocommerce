<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Extension\FreightClassesExtensionInterface;

/**
 * The provider for freight classes.
 */
class FreightClassesProvider extends MeasureUnitProvider
{
    /** @var iterable|FreightClassesExtensionInterface[]|null */
    private $extensions;

    /**
     * @param iterable|FreightClassesExtensionInterface[] $extensions
     */
    public function setExtensions(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @param ProductShippingOptions $options
     *
     * @return FreightClass[]
     */
    public function getFreightClasses(ProductShippingOptions $options = null)
    {
        $sourceUnits = $this->getUnits();

        if (!$this->hasExtensions()) {
            return $sourceUnits;
        }

        return array_filter($sourceUnits, function (FreightClass $class) use ($options) {
            foreach ($this->extensions as $extension) {
                if ($extension->isApplicable($class, $options ?: new ProductShippingOptions())) {
                    return true;
                }
            }

            return false;
        });
    }

    private function hasExtensions(): bool
    {
        if (null !== $this->extensions) {
            foreach ($this->extensions as $extension) {
                return true;
            }
        }

        return false;
    }
}
