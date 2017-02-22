<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class UnitVisibilityExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction(
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
