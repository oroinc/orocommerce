<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class UnitVisibilityExtension extends \Twig_Extension
{
    /**
     * @var ProductUnitFieldsSettingsInterface
     */
    protected $unitVisibility;

    /**
     * @param UnitVisibilityInterface $unitVisibility
     */
    public function __construct(UnitVisibilityInterface $unitVisibility)
    {
        $this->unitVisibility = $unitVisibility;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_is_unit_code_visible',
                [$this->unitVisibility, 'isUnitCodeVisible']
            ),
        ];
    }
}
