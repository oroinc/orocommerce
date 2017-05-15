<?php

namespace Oro\Bundle\CommerceMenuBundle\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;

use Symfony\Component\HttpFoundation\RequestStack;

class MenuExtension extends \Twig_Extension
{
    const NAME = 'oro_commercemenu';

    /** @var MatcherInterface */
    private $matcher;

    /** @var RequestStack */
    private $requestStack;

    /**
     * @param MatcherInterface $matcher
     */
    public function __construct(MatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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
            'oro_commercemenu_get_url' => new \Twig_SimpleFunction('oro_commercemenu_get_url', [$this, 'getUrl']),
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

    /**
     * @param string $url
     *
     * @return string
     */
    public function getUrl($url)
    {
        $result = parse_url($url);
        if (array_key_exists('host', $result) || array_key_exists('scheme', $result)) {
            return $url;
        }
        $request = $this->requestStack->getCurrentRequest();

        if (0 !== strpos($url, '/')) {
            $url = '/'.$url;
        }

        return $request->getUriForPath($url);
    }
}
