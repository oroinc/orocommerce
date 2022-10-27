<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTextContentVariantsData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ContentBlock $contentBlock */
        $contentBlock = $this->getReference('content_block_1');

        $contentVariant1 = $this->createContentVariant('text_content_variant1', true);
        $contentVariant2 = $this->createContentVariant('text_content_variant2', false);
        $contentVariant3 = $this->createContentVariant('text_content_variant3', false);

        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        $scope1 = $this->createScopeWithCustomer($customer);
        $contentVariant2->addScope($scope1);
        $this->addReference('content_variant2_scope1', $scope1);

        /** @var Customer $secondCustomer */
        $secondCustomer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);
        $scope2 = $this->createScopeWithCustomer($secondCustomer);
        $contentVariant3->addScope($scope2);
        $this->addReference('content_variant3_scope2', $scope2);

        $contentBlock->addContentVariant($contentVariant1);
        $contentBlock->addContentVariant($contentVariant2);
        $contentBlock->addContentVariant($contentVariant3);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadContentBlockData::class,
            LoadCustomers::class
        ];
    }

    protected function createContentVariant(string $alias, bool $default): TextContentVariant
    {
        $contentVariant = new TextContentVariant();
        $contentVariant->setContent($alias);
        $contentVariant->setDefault($default);
        $this->addReference($alias, $contentVariant);

        return $contentVariant;
    }

    /**
     * @param Customer $customer
     * @return Scope
     */
    protected function createScopeWithCustomer(Customer $customer)
    {
        return $this->container->get('oro_scope.scope_manager')
            ->findOrCreate('web_content', ['customer' => $customer]);
    }
}
