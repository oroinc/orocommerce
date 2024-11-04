<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\DataFixtures\LoadCustomerData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadFrontendProductAttributesData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    public $container;

    #[\Override]
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadProductData::class,
            LoadCustomerData::class,
            LoadProductEnumAttributes::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->getProductAttributesData() as $item) {
            $product = $this->getReference($item['product']);
            $product->setTestAttrEnum($this->findEnum($manager, 'test_prod_attr_enum', $item['testAttrEnumOption']));
            $product->setContactType($this->findEnum($manager, 'test_prod_attr_enum', 'enum_second_option'));
            $product->setTypeContact($this->findEnum($manager, 'test_prod_attr_enum', 'enum_third_option'));
            foreach ($item['testAttrMultiEnumOptions'] as $option) {
                $product->addTestAttrMultiEnum(
                    $this->findEnum($manager, 'test_prod_attr_m_enum', $option)
                );
            }
            $product->setTestAttrManyToOne($this->getReference($item['testAttrManyToOne']));
            $product->setTestAttrBoolean($item['testAttrBoolean']);
        }

        $manager->flush();

        $this->updateWebsiteSearchIndex();
    }

    /**
     * @return array
     */
    private function getProductAttributesData()
    {
        return [
            [
                'product' => 'product-1',
                'testAttrEnumOption' => 'enum_second_option',
                'testAttrMultiEnumOptions' => [
                    'multi_enum_first_option',
                    'multi_enum_second_option'
                ],
                'testAttrManyToOne' => 'customer.1',
                'testAttrBoolean' => true
            ],
            [
                'product' => 'product-2',
                'testAttrEnumOption' => 'enum_third_option',
                'testAttrMultiEnumOptions' => [
                    'multi_enum_first_option'
                ],
                'testAttrManyToOne' => 'customer.1',
                'testAttrBoolean' => true
            ],
            [
                'product' => 'product-3',
                'testAttrEnumOption' => 'enum_third_option',
                'testAttrMultiEnumOptions' => [
                    'multi_enum_second_option',
                    'multi_enum_third_option'
                ],
                'testAttrManyToOne' => 'customer.1',
                'testAttrBoolean' => false
            ],
        ];
    }

    private function updateWebsiteSearchIndex()
    {
        $this->container->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    private function findEnum(ObjectManager $manager, string $enumCode, string $id): ?EnumOptionInterface
    {
        return $manager
            ->getRepository(EnumOption::class)
            ->find(ExtendHelper::buildEnumOptionId($enumCode, $id));
    }
}
