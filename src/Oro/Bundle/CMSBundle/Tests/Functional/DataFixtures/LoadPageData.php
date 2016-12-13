<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LoadPageData extends AbstractFixture
{
    const PAGE_1 = 'page.1';
    const PAGE_2 = 'page.2';
    const PAGE_3 = 'page.3';

    /**
     * @var array
     */
    protected static $page = [
        self::PAGE_1 => [],
        self::PAGE_2 => [],
        self::PAGE_3 => [],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$page as $menuItemReference => $data) {
            $entity = new Page();
            $entity->addTitle((new LocalizedFallbackValue())->setString($menuItemReference));
            $entity->setContent($menuItemReference);
            $this->setReference($menuItemReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
