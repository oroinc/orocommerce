<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

class LoadMenuItemData extends AbstractFixture
{
    /**
     * @var array
     */
    protected static $menuItems = [
        'menu_item.1' => [
            'uri' => '#',
        ],
        'menu_item.1_2' => [
            'uri' => '#',
            'parent' => 'menu_item.1'
        ],
        'menu_item.1_3' => [
            'uri' => '#',
            'parent' => 'menu_item.1'
        ],
        'menu_item.4' => [
            'uri' => '#',
        ],
        'menu_item.4_5' => [
            'uri' => '#',
            'parent' => 'menu_item.4'
        ],
        'menu_item.4_5_6' => [
            'uri' => '#',
            'parent' => 'menu_item.4_5'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$menuItems as $menuItemReference => $data) {
            $title = new LocalizedFallbackValue();
            $title->setString($menuItemReference);
            $entity = new MenuItem();
            $entity->addTitle($title)
                ->setUri($data['uri']);
            if (isset($data['parent'])) {
                $entity->setParentMenuItem($this->getReference($data['parent']));
            }
            $this->setReference($menuItemReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
