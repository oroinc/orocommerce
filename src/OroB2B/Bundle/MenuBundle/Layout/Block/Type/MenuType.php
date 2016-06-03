<?php

namespace OroB2B\Bundle\MenuBundle\Layout\Block\Type;

use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Provider\MenuProviderInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

class MenuType extends AbstractType
{
    const NAME = 'menu';

    /**
     * @var MenuProviderInterface
     */
    protected $menuProvider;

    /**
     * @var MatcherInterface
     */
    protected $matcher;

    /**
     * @param MenuProviderInterface $menuProvider
     * @param MatcherInterface $matcher
     */
    public function __construct(MenuProviderInterface $menuProvider, MatcherInterface $matcher)
    {
        $this->menuProvider = $menuProvider;
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'depth' => null,
                'matchingDepth' => null,
                'currentAsLink' => true,
                'currentClass' => 'current',
                'ancestorClass' => 'current_ancestor',
                'firstClass' => 'first',
                'lastClass' => 'last',
                'allow_safe_labels' => false,
                'clear_matcher' => true,
                'leaf_class' => null,
                'branch_class' => null,
                'child_attr' => [],
                'link_attr' => [],
                'label_attr' => [],
            ]
        );

        $resolver->setRequired(['menu']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $title = $options['menu'];

        if (!$this->menuProvider->has($title)) {
            throw new \RuntimeException(sprintf('Menu "%s" doesn\'t exist.', $title));
        }
        $view->vars['item'] = $this->menuProvider->get($title);
        $view->vars['options'] = $options;
        $view->vars['matcher'] = $this->matcher;
        $view->vars['child_attr'] = $options['child_attr'];
        $view->vars['link_attr'] = $options['link_attr'];
        $view->vars['label_attr'] = $options['label_attr'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
