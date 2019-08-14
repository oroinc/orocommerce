# OroProductBundle

OroProductBundle adds the Product entity to the OroCommerce application, provides UI for the OroCommerce management console user to create and manage simple or configurable products and product brands, and enables the configuration of product presentation in the storefront via the system configuration menu.

## Table of Contents

 - [Expected dependencies](#expected-dependencies)
 - Formatting
    - [Product Unit Formatting](./Resources/doc/product-unit-formatting.md)
 - Product creation
    - [Two Step Product Creation](./Resources/doc/two-step-product-creation.md)
    - [Default Product Unit](./Resources/doc/default-product-unit.md)
 - [Product Attributes](./Resources/doc/product-attributes.md)
 - [Customize products using layouts](./Resources/doc/customize-products.md)
 - [Customize product SKU validation pattern](./Resources/doc/customize-products-sku-validation.md)
 - [Related items](./Resources/doc/related-items.md)
 - [Actions](./Resources/doc/actions.md)
 - [Product Variant Search](./Resources/doc/product-variant-search.md)

## Expected dependencies:

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
- Symfony\Component\OptionsResolver\OptionsResolver
- Symfony\Contracts\Translation\TranslatorInterface
- Symfony\Component\Validator\Validation
