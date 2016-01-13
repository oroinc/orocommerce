<?php

namespace OroB2B\Bundle\MenuBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\FactoryInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class DatabaseBuilder implements BuilderInterface
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @param RegistryInterface $registry
     * @param FactoryInterface $factory
     */
    public function __construct(RegistryInterface $registry, FactoryInterface $factory)
    {
        $this->registry = $registry;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function build($alias, array $options = [])
    {
        $root = $this->getRepository()->findMenuItemWithChildrenAndTitleByTitle($alias);
        $item = $this->factory->createItem($alias);
        $this->createFromMenuItem($item, $root, $options);
        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($alias)
    {
        return $this->getRepository()->findMenuItemByTitle($alias) !== null;
    }

    /**
     * @param array $options
     * @return Locale|null
     */
    protected function getLocale(array $options)
    {
        if (isset($options['locale']) && $options['locale'] instanceof Locale) {
            return $options['locale'];
        }
        return null;
    }

    /**
     * @param ItemInterface $item
     * @param MenuItem $root
     * @param array $options
     */
    protected function createFromMenuItem(ItemInterface $item, MenuItem $root, array $options)
    {
        foreach ($root->getChildren() as $child) {
            $item->addChild($child->getTitle(), $this->menuItemEntityToArray($child, $options));
            $this->createFromMenuItem($item, $child, $options);
        }
    }

    /**
     * @param MenuItem $item
     * @param array $options
     * @return array
     */
    protected function menuItemEntityToArray(MenuItem $item, array $options)
    {
        $locale = $this->getLocale($options);
        $getData = function ($key) use ($item) {
            $data = $item->getData();
            return isset($data[$key]) ? $data[$key] : [];
        };
        return array_merge($options, [
            'uri' => $item->getUri(),
            'label' => $item->getTitle($locale)->getString(),
            'attributes' => $getData('attributes'),
            'linkAttributes' => $getData('linkAttributes'),
            'childrenAttributes' => $getData('childrenAttributes'),
            'labelAttributes' => $getData('labelAttributes'),
            'extras' => $getData('extras'),
            'display' => $item->getDisplay(),
            'displayChildren' => $item->getDisplayChildren(),
        ]);
    }

    /**
     * @return \OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass('OroB2BMenuBundle:MenuItem')
            ->getRepository('OroB2BMenuBundle:MenuItem');
    }
}
