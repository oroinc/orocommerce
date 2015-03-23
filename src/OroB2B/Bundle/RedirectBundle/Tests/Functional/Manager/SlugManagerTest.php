<?php

namespace OroB2B\Bundle\RedirectBundle\Tests\Functional\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;
use OroB2B\Bundle\RedirectBundle\Manager\SlugManager;

/**
 * @dbIsolation
 */
class SlugManagerTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SlugManager
     */
    protected $slugManager;


    protected function setUp()
    {
        $this->initClient();
        $this->registry    = $this->getContainer()->get('doctrine');
        $this->slugManager = $this->getContainer()->get('orob2b_redirect.slug.manager');
    }

    public function testMakeUrlUnique()
    {
        $manager =  $this->registry->getManagerForClass('OroB2BRedirectBundle:Slug');

        $slug = new Slug();
        $slug->setUrl('domain.com/hvac-equipment/detection-kits');
        $slug->setRouteName('orob2b_cms_page_view');
        $slug->setRouteParameters(['id' => 1]);
        $manager->persist($slug);

        $slug1 = new Slug();
        $slug1->setUrl('domain.com/hvac-equipment/detection-kits1-1');
        $slug1->setRouteName('orob2b_cms_page_view');
        $slug1->setRouteParameters(['id' => 1]);
        $manager->persist($slug1);

        $slug2 = new Slug();
        $slug2->setUrl('domain.com/hvac-equipment/detection-kits1-2');
        $slug2->setRouteName('orob2b_cms_page_view');
        $slug2->setRouteParameters(['id' => 1]);
        $manager->persist($slug2);

        $manager->flush();

        $testSlug = new Slug();
        $testSlug->setUrl('domain.com/hvac-equipment/detection-kits');
        $testSlug->setRouteName('orob2b_cms_page_view');
        $testSlug->setRouteParameters(['id' => 2]);

        $this->slugManager->makeUrlUnique($testSlug);
        $manager->persist($testSlug);
        $manager->flush();

        $this->assertEquals('domain.com/hvac-equipment/detection-kits-1', $testSlug->getUrl());

        $testSlug1 = new Slug();
        $testSlug1->setUrl('domain.com/hvac-equipment/detection-kits');
        $testSlug1->setRouteName('orob2b_cms_page_view');
        $testSlug1->setRouteParameters(['id' => 21]);

        $this->slugManager->makeUrlUnique($testSlug1);
        $manager->persist($testSlug1);
        $manager->flush();

        $this->assertEquals('domain.com/hvac-equipment/detection-kits-2', $testSlug1->getUrl());

        $testSlug2 = new Slug();
        $testSlug2->setUrl('domain.com/hvac-equipment/detection-kits1-1');
        $testSlug2->setRouteName('orob2b_cms_page_view');
        $testSlug2->setRouteParameters(['id' => 21]);

        $this->slugManager->makeUrlUnique($testSlug2);
        $manager->persist($testSlug2);
        $manager->flush();

        $this->assertEquals('domain.com/hvac-equipment/detection-kits1-3', $testSlug2->getUrl());
    }
}
