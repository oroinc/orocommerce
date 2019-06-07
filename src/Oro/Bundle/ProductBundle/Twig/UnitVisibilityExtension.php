<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get check oro_product.visibility.unit container parameter value:
 *   - oro_is_unit_code_visible
 */
class UnitVisibilityExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return UnitVisibilityInterface
     */
    protected function getUnitVisibility()
    {
        return $this->container->get('oro_product.visibility.unit');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_is_unit_code_visible',
                [$this, 'isUnitCodeVisible']
            ),
        ];
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function isUnitCodeVisible($code)
    {
        return $this->getUnitVisibility()->isUnitCodeVisible($code);
    }
}
