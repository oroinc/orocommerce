<?php

namespace Oro\Bundle\CommerceMenuBundle\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;

class MenuExtension extends \Twig_Extension
{
    const NAME = 'oro_commercemenu';

    /** @var MatcherInterface */
    private $matcher;

    /**
     * @param MatcherInterface $matcher
     */
    public function __construct(MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            'oro_commercemenu_is_current' => new \Twig_SimpleFunction(
                'oro_commercemenu_is_current',
                [$this, 'isCurrent']
            ),
            'oro_commercemenu_is_ancestor' => new \Twig_SimpleFunction(
                'oro_commercemenu_is_ancestor',
                [$this, 'isAncestor']
            ),
        ];
    }

    /**
     * @param ItemInterface $item
     *
     * @return bool
     */
    public function isCurrent(ItemInterface $item)
    {
        return $this->matcher->isCurrent($item);
    }

    /**
     * @param ItemInterface $item
     *
     * @return bool
     */
    public function isAncestor(ItemInterface $item)
    {
        return $this->matcher->isAncestor($item);
    }
}
