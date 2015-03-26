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


            if ($row['parentId'] > 0 && array_key_exists($row['parentId'], $this->pages)) {
                /** @var Page $parent */
                $parent = $this->pages[$row['parentId']];
                $parent->addChildPage($page);
            }

            $manager->persist($page);

            $this->pages[$row['id']] = $page;
        }

        fclose($handler);

        $manager->flush();
    }
}
