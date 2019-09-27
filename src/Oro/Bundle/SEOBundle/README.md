# OroSEOBundle

OroSEOBundle provides CLI to generate [sitemap.xml](https://www.sitemaps.org/protocol.html) and [robots.txt](http://www.robotstxt.org/) files for the OroCommerce application and enables management console administrators to configure sitemap generation options in the system configuration UI. The bundle also provides UI for content managers to set localized Search Engine Optimization (SEO) meta tags (title, description, keywords) for every product, category, content node, and CMS page.

## Table of Contents

 - [SEO meta fields](./Resources/doc/seo_meta_fields.md)
 - [Sitemap](./Resources/doc/sitemap.md)
 - [Expected dependencies](#expected-dependencies)

## Expected dependencies:

Doctrine\Common\Collections\ArrayCollection
Doctrine\Common\DataFixtures\AbstractFixture
Doctrine\Common\DataFixtures\DependentFixtureInterface
Doctrine\Common\Persistence\ObjectManager
Doctrine\DBAL\Schema\Schema
Doctrine\ORM\EntityManager
Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
Oro\Bundle\EntityBundle\ORM\OroEntityManager;
Oro\Bundle\MigrationBundle\Migration\Installation
Oro\Bundle\MigrationBundle\Migration\Migration
Oro\Bundle\MigrationBundle\Migration\QueryBag
Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
Oro\Bundle\UIBundle\View\ScrollData;
Oro\Bundle\TestFrameworkBundle\Test\WebTestCase
Oro\Bundle\CatalogBundle
Oro\Bundle\CMSBundle
Oro\Bundle\FallbackBundle
Oro\Bundle\ProductBundle
Symfony\Component\Config\FileLocator
Symfony\Component\DependencyInjection\ContainerBuilder
Symfony\Component\DependencyInjection\Loader
Symfony\Component\Form
Symfony\Component\HttpFoundation
Symfony\Component\HttpKernel\Bundle\Bundle
Symfony\Component\HttpKernel\DependencyInjection\Extension
Symfony\Component\OptionsResolver\OptionsResolver
Symfony\Contracts\Translation\TranslatorInterface
