<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CMSBundle\Entity\Page;

class LoadPageData extends AbstractFixture
{
    const PAGE_1 = 'page.1';
    const PAGE_1_2 = 'page.1_2';
    const PAGE_1_3 = 'page.1_3';

    /**
     * @var array
     */
    protected static $page = [
        self::PAGE_1 => [],
        self::PAGE_1_2 => [
            'parent' => 'page.1'
        ],
        self::PAGE_1_3 => [
            'parent' => 'page.1'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$page as $menuItemReference => $data) {
            $entity = new Page();
            $entity->setTitle($menuItemReference)
                ->setContent($menuItemReference);
            if (isset($data['parent'])) {
                $entity->setParentPage($this->getReference($data['parent']));
            }
            $this->setReference($menuItemReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
