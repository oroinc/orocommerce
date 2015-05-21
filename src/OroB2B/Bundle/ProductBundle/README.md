OroB2B\Bundle\ProductBundle\OroB2BProductBundle
===============================================

Table of Contents
-----------------
 - [Description](#description)
 - [Bundle responsibilities](#bundle-responsibilities)
 - [Expected dependencies](#expected-dependencies)
 - Formatting
    - [Product Unit Value Formatting](./Resources/doc/product-unit-value-formatting.md)

Description:
------------

The OroB2BProductBundle introduces the notion of products, which is the foundation of most commerce business cases and functionality, into the system. This bundle will also provide a UI for product management by utilizing functionality of other bundles. Or, with the exception of the OroB2BAttributeBundle dependency (which ideally should be a replaceable dependency), it would be better to say that other feature bundles will be providing their pieces of product management functionality and corresponding UI pieces for the product management UI, once installed in the system.

The OroB2BProductBundle is expected to be admin-heavy, so a matching bundle for non-admin applications will be introduced as well.

Expected dependencies:
----------------------

Doctrine\Common\Collections\ArrayCollection
Doctrine\Common\DataFixtures\AbstractFixture
Doctrine\Common\DataFixtures\DependentFixtureInterface
Doctrine\Common\Persistence\ObjectManager
Doctrine\DBAL\Schema\Schema
Doctrine\ORM\EntityManager
Doctrine\ORM\Mapping as ORM
FOS\RestBundle\Controller\Annotations\NamePrefix
FOS\RestBundle\Routing\ClassResourceInterface
Nelmio\ApiDocBundle\Annotation\ApiDoc
OroB2B\Bundle\AttributeBundle\AttributeType\String
OroB2B\Bundle\AttributeBundle\Form\Extension\IntegerExtension
OroB2B\Bundle\AttributeBundle\Migrations\Data\ORM\AbstractLoadAttributeData
OroB2B\Bundle\CatalogBundle\Entity\Category
OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType
OroB2B\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture
Oro\Bundle\ConfigBundle\Config\ConfigManager
Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder
Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config
Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField
Oro\Bundle\MigrationBundle\Migration\Installation
Oro\Bundle\MigrationBundle\Migration\Migration
Oro\Bundle\MigrationBundle\Migration\QueryBag
Oro\Bundle\OrganizationBundle\Entity\BusinessUnit
Oro\Bundle\OrganizationBundle\Entity\Organization
Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface
Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface
Oro\Bundle\SecurityBundle\Annotation\Acl
Oro\Bundle\SecurityBundle\Annotation\AclAncestor
Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController
Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase
Oro\Bundle\TestFrameworkBundle\Test\WebTestCase
Oro\Bundle\UserBundle\Entity\User
Oro\Component\Testing\Unit\EntityTestCase
Oro\Component\Testing\Unit\FormHandlerTestCase
Oro\Component\Testing\Unit\Form\Type\Stub\EntityType
Sensio\Bundle\FrameworkExtraBundle\Configuration\Route
Sensio\Bundle\FrameworkExtraBundle\Configuration\Template
Symfony\Bundle\FrameworkBundle\Controller\Controller
Symfony\Component\Config\Definition\Builder\TreeBuilder
Symfony\Component\Config\Definition\ConfigurationInterface
Symfony\Component\Config\FileLocator
Symfony\Component\DependencyInjection\ContainerAwareInterface
Symfony\Component\DependencyInjection\ContainerBuilder
Symfony\Component\DependencyInjection\ContainerInterface
Symfony\Component\DependencyInjection\Loader
Symfony\Component\DomCrawler\Form
Symfony\Component\Form
Symfony\Component\HttpFoundation
Symfony\Component\HttpKernel\Bundle\Bundle
Symfony\Component\HttpKernel\DependencyInjection\Extension
Symfony\Component\OptionsResolver\OptionsResolverInterface
Symfony\Component\Translation\TranslatorInterface
Symfony\Component\Validator\Validation
