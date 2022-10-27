<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadContentVariantScopes extends AbstractFixture implements DependentFixtureInterface
{
    public const BASE_NODE = LoadContentNodesData::CATALOG_1_ROOT;
    /** @var string ContentNode with using parent scopes */
    public const NODE_1 = LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1;
    public const NODE_1_VARIANT_1 = LoadContentVariantsData::CONTENT_VARIANT_SUBNODE_1;
    /** @var string ContentNode with custom scopes */
    public const NODE_2 = LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2;
    public const NODE_2_SCOPE = 'web_catalog.1.node.2.scope1';
    public const NODE_2_VARIANT_1 = LoadContentVariantsData::CONTENT_VARIANT_SUBNODE_2;
    public const NODE_2_VARIANT_2 = 'web_catalog.content_variant.subnode_2_with_custom_scopes';
    public const NODE_2_VARIANT_2_SCOPE = 'web_catalog.1.node.2.variant.2.scope1';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContentNodesData::class,
            LoadCustomers::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $nodeScope = new Scope();
        $nodeScope->setWebCatalog($webCatalog);
        $manager->persist($nodeScope);
        $this->addReference(self::NODE_2_SCOPE, $nodeScope);

        // Default content variants has scopes from parent node
        $this->getReference(self::NODE_1_VARIANT_1)->setDefault(true)->addScope($nodeScope);
        $this->getReference(self::NODE_2_VARIANT_1)->setDefault(true)->addScope($nodeScope);

        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $variantScope = new Scope();
        $variantScope->setWebCatalog($webCatalog);
        $variantScope->setCustomer($customer);
        $manager->persist($variantScope);
        $this->addReference(self::NODE_2_VARIANT_2_SCOPE, $variantScope);

        $contentVariant = new ContentVariant();
        $contentVariant->setType(SystemPageContentVariantType::TYPE);
        $contentVariant->setSystemPageRoute('oro_customer_frontend_account_user_index');
        $contentVariant->addScope($variantScope);
        $manager->persist($contentVariant);
        $this->setReference(self::NODE_2_VARIANT_2, $contentVariant);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(self::NODE_2);
        $contentNode->addScope($nodeScope);
        $contentNode->addContentVariant($contentVariant);

        $manager->flush();
    }
}
