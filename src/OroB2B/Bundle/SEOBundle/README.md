OroB2B\Bundle\ProductBundle\OroB2BProductBundle
===============================================

Table of Contents
-----------------
 - [Description](#description)
 - [Bundle responsibilities](#bundle-responsibilities)
 - [Technical details](#technical-details)
 - [Expected dependencies](#expected-dependencies)

Description:
------------
The OroB2BSEOBundle introduces SEO (Search Engine Optimization) meta tags (title, description, keywords) for different pages. These meta fields can be edited from the admin section and they are added as meta tags on pages in the frontend (customer) application.

Bundle responsabilities:
----------------------
The OroB2BSEOBundle adds functionality both on the admin side and also on the frontend application. This is done through extension of existing entities from the platform by adding new SEO section on view/edit pages from admin side and adding meta tags on html of the configured pages.
The entities and their corresponding frontend pages that have been extended with this functionality are:
- Product (OroB2BProductBundle) with admin view and edit
- Category (OroB2BCatalogBundle) with admin edit
- LandingPage (OroB2BCMSBundle) with admin view and edit

The admin functionalities:
- provide new section on view of extended entities, section which displays the SEO fields title, description and keywords that are set for currently viewed entity
- provide new section on edit of extended entities, section which contains inputs for the SEO fields title, description and keywords for currently edited entity (value of each field can be modified for all locales)

Frontend functionality: the values of the SEO fields that are set for each of the entities mentioned above, are added in the HTML of the pages displayed in customer website.

Technical details:
------------------
The entities Product, Category and LandingPage have been extended with 3 new different fields, which are actually collections of LocalizedFallbackValue. Therefore these new fields added through extension are many-to-many relations between the specified entities and LocalizedFallbackValue entity.

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
OroB2B\Bundle\CatalogBundle
OroB2B\Bundle\CMSBundle
OroB2B\Bundle\FallbackBundle
OroB2B\Bundle\ProductBundle
Symfony\Component\Config\FileLocator
Symfony\Component\DependencyInjection\ContainerBuilder
Symfony\Component\DependencyInjection\Loader
Symfony\Component\Form
Symfony\Component\HttpFoundation
Symfony\Component\HttpKernel\Bundle\Bundle
Symfony\Component\HttpKernel\DependencyInjection\Extension
Symfony\Component\OptionsResolver\OptionsResolverInterface
Symfony\Component\Translation\TranslatorInterface