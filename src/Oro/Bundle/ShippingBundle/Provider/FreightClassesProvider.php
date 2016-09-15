<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Extension\FreightClassesExtensionInterface;

class FreightClassesProvider extends MeasureUnitProvider
{
    /** @var array|FreightClassesExtensionInterface[] */
    protected $extensions = [];

    /**
     * @param string $name
     * @param FreightClassesExtensionInterface $extension
     */
    public function addExtension($name, FreightClassesExtensionInterface $extension)
    {
        $this->extensions[$name] = $extension;
    }

    /**
     * @param ProductShippingOptions $options
     * @return FreightClass[]
     */
    public function getFreightClasses(ProductShippingOptions $options = null)
    {
        $sourceUnits = $this->getUnits();

        if (!$this->extensions) {
            return $sourceUnits;
        }

        return array_filter($sourceUnits, function (FreightClass $class) use ($options) {
            foreach ($this->extensions as $extension) {
                /* @var $extension FreightClassesExtensionInterface */
                if ($extension->isApplicable($class, $options ?: new ProductShippingOptions())) {
                    return true;
                }
            }

            return false;
        });
    }
}
