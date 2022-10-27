<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Updates the SEO attributes configuration.
 */
class LoadProductSeoAttributesAndGroupData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use UserUtilityTrait;
    use MakeProductAttributesTrait;

    private const GROUP_CODE = 'seo';

    private static array $groups = [
        self::GROUP_CODE => [
            'groupLabel' => 'SEO',
            'groupCode' => self::GROUP_CODE,
            'attributes' => [
                'metaKeywords',
                'metaDescriptions',
                'metaTitles',
            ],
            'groupVisibility' => false,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        if (!$this->skipIfAppliedPreviously()) {
            $user = $this->getFirstUser($manager);
            $organization = $user->getOrganization();

            $attributeFamily = $manager->getRepository(AttributeFamily::class)
                ->findOneBy(
                    ['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE, 'owner' => $organization]
                );

            $this->makeProductAttributes(
                array_fill_keys(self::$groups[self::GROUP_CODE]['attributes'], []),
                ExtendScope::OWNER_SYSTEM,
                ['frontend' => ['is_displayable' => false]]
            );

            $this->addGroupsWithAttributesToFamily(
                self::$groups,
                $attributeFamily,
                $manager
            );
        }
    }

    /**
     * If metaKeywords is already attribute then old version of AttributeFamilyData migration was applied.
     *
     * @return bool
     */
    private function skipIfAppliedPreviously(): bool
    {
        $attributeHelper = $this->container->get('oro_entity_config.config.attributes_config_helper');

        return $attributeHelper->isFieldAttribute(Product::class, 'metaKeywords');
    }

    public function getDependencies(): array
    {
        return [
            LoadProductDefaultAttributeFamilyData::class,
        ];
    }
}
