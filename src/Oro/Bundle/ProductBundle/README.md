Oro\Bundle\ProductBundle\OroProductBundle
===============================================

Table of Contents
-----------------
 - [Description](#description)
 - [Expected dependencies](#expected-dependencies)
 - Formatting
    - [Product Unit Formatting](./Resources/doc/product-unit-formatting.md)
 - Product creation
    - [Two Step Product Creation](./Resources/doc/two-step-product-creation.md)
    - [Default Product Unit](./Resources/doc/default-product-unit.md)
 - [Product API](./Resources/doc/product-api.md)
 - [Product Attributes](./Resources/doc/product-attributes.md)
 - [Customize products using layouts](./Resources/doc/customize-products.md)
 - [Related items](./Resources/doc/related-items.md)
 - [Actions](./Resources/doc/actions.md)

Description:
------------

OroProductBundle introduces the notion of products into the system. Products are the foundation of most commerce business cases and functionality. 

This bundle will provide a UI for product management by utilizing the functionality of other bundles. In other words, bundles of other features will be providing their pieces of product management functionality and the corresponding UI pieces for the product management UI (once installed in the system), with the exception of OroPricingBundle dependency (which should ideally be a replaceable dependency). 

The OroProductBundle is expected to be admin-heavy, so a matching bundle for non-admin applications will be introduced as well.

Expected dependencies:
----------------------

- Doctrine\Common\Collections\ArrayCollection
- Doctrine\Common\DataFixtures\AbstractFixture
- Doctrine\Common\DataFixtures\DependentFixtureInterface
- Doctrine\Common\Persistence\ObjectManager
- Doctrine\DBAL\Schema\Schema
- Doctrine\ORM\EntityManager
- Doctrine\ORM\Mapping as ORM
- FOS\RestBundle\Controller\Annotations\NamePrefix
- FOS\RestBundle\Routing\ClassResourceInterface
- Nelmio\ApiDocBundle\Annotation\ApiDoc
- Oro\Bundle\ConfigBundle\Config\ConfigManager
- Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder
- Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config
- Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField
- Oro\Bundle\MigrationBundle\Migration\Installation
- Oro\Bundle\MigrationBundle\Migration\Migration
- Oro\Bundle\MigrationBundle\Migration\QueryBag
- Oro\Bundle\OrganizationBundle\Entity\BusinessUnit
- Oro\Bundle\OrganizationBundle\Entity\Organization
- Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface
- Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface
- Oro\Bundle\SecurityBundle\Annotation\Acl
- Oro\Bundle\SecurityBundle\Annotation\AclAncestor
- Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController
- Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase
- Oro\Bundle\TestFrameworkBundle\Test\WebTestCase
- Oro\Bundle\UserBundle\Entity\User
- Oro\Component\Testing\Unit\EntityTestCase
- Oro\Component\Testing\Unit\FormHandlerTestCase
- Oro\Component\Testing\Unit\Form\Type\Stub\EntityType
- Sensio\Bundle\FrameworkExtraBundle\Configuration\Route
- Sensio\Bundle\FrameworkExtraBundle\Configuration\Template
- Symfony\Bundle\FrameworkBundle\Controller\Controller
- Symfony\Component\Config\Definition\Builder\TreeBuilder
- Symfony\Component\Config\Definition\ConfigurationInterface
- Symfony\Component\Config\FileLocator
- Symfony\Component\DependencyInjection\ContainerAwareInterface
- Symfony\Component\DependencyInjection\ContainerBuilder
- Symfony\Component\DependencyInjection\ContainerInterface
- Symfony\Component\DependencyInjection\Loader
- Symfony\Component\DomCrawler\Form
- Symfony\Component\Form
- Symfony\Component\HttpFoundation
- Symfony\Component\HttpKernel\Bundle\Bundle
- Symfony\Component\HttpKernel\DependencyInjection\Extension
- Symfony\Component\OptionsResolver\OptionsResolverInterface
- Symfony\Component\Translation\TranslatorInterface
- Symfony\Component\Validator\Validation
