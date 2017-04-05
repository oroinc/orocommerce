Oro\Bundle\ProductBundle\OroProductBundle
=========================================

Table of Contents
-----------------
 - [Description](#description)
 - [Expected dependencies](#expected-dependencies)

Description:
------------
The OroSEOBundle introduces SEO (Search Engine Optimization) meta tags (title, description, keywords) for different pages. These meta fields can be edited from the admin section and they are added as meta tags on pages in the frontend (customer) application.
Also OroSEOBundle provide possibility to generate [sitemap.xml](https://www.sitemaps.org/protocol.html) file which displays list the web pages of your site for search engines.

- [SEO meta fields](./Resources/doc/seo_meta_fields.md)
- [Sitemap](./Resources/doc/sitemap.md)

Expected dependencies:
----------------------

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
Symfony\Component\OptionsResolver\OptionsResolverInterface
Symfony\Component\Translation\TranslatorInterface