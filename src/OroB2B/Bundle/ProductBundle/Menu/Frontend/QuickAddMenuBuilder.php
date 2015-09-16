<?php

namespace OroB2B\Bundle\ProductBundle\Menu\Frontend;

use Knp\Menu\ItemInterface;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;

use OroB2B\Bundle\ProductBundle\Model\ComponentProcessorRegistry;

class QuickAddMenuBuilder implements BuilderInterface
{
    /**
     * @var ComponentProcessorRegistry
     */
    protected $componentRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ComponentProcessorRegistry $componentRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(ComponentProcessorRegistry $componentRegistry, TranslatorInterface $translator)
    {
        $this->componentRegistry = $componentRegistry;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function build(ItemInterface $menu, array $options = array(), $alias = null)
    {
        if (!$this->componentRegistry->hasAllowedProcessor()) {
            return;
        }
        $menu
            ->addChild(
                $this->translator->trans('orob2b.product.frontend.quick_add.title'),
                [
                    'route' => 'orob2b_product_frontend_quick_add',
                    'extras' => [
                        'position' => 500,
                        'description' => $this->translator->trans('orob2b.product.frontend.quick_add.description'),
                    ],
                ]
            );
    }
}
