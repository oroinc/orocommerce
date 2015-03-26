<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroB2B\Bundle\CMSBundle\Entity\Page;

class LoadPageDemoData extends AbstractFixture implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $pages = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $locator  = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BCMSBundle/Migrations/Data/Demo/ORM/data/pages.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 5000, ',');

        while (($data = fgetcsv($handler, 5000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $page = new Page();
            $page->setTitle($row['title']);
            $page->setContent($row['content']);
            $page->setOrganization($organization);
            $page->setCurrentSlugUrl($row['slug']);

            if ($row['parentPageSlug'] && array_key_exists($row['parentPageSlug'], $this->pages)) {
                /** @var Page $parent */
                $parent = $this->pages[$row['parentPageSlug']];
                $parent->addChildPage($page);
            }

            $manager->persist($page);
            $manager->flush();

            $slug = $page->getCurrentSlug();
            $slug->setRouteName('orob2b_cms_page_view');
            $slug->setRouteParameters(['id' => $page->getId()]);
            $manager->persist($slug);
            $manager->flush();

            $this->pages[$page->getCurrentSlug()->getSlugUrl()] = $page;
        }

        fclose($handler);
    }
}
