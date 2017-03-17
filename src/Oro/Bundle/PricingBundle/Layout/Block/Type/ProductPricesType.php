<?php

namespace Oro\Bundle\PricingBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class ProductPricesType extends AbstractContainerType
{
    const NAME = 'product_prices';

    const PRICES_GROUP_CODE = 'prices';

    /** @var AttributeGroupRenderRegistry */
    protected $groupRenderRegistry;

    /**
     * @param AttributeGroupRenderRegistry $groupRenderRegistry
     */
    public function __construct(AttributeGroupRenderRegistry $groupRenderRegistry)
    {
        $this->groupRenderRegistry = $groupRenderRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        if (null === $options->get('attributeFamily')) {
            return;
        }

        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $options->get('attributeFamily');
        if (!$attributeFamily->getAttributeGroups()->containsKey(self::PRICES_GROUP_CODE)) {
            return;
        }

        $attributeGroup = $attributeFamily->getAttributeGroup(self::PRICES_GROUP_CODE);
        $this->groupRenderRegistry->setRendered($attributeFamily, $attributeGroup);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions(
            $view,
            $options,
            ['product', 'productPrices', 'productUnitSelectionVisible']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined('productUnitSelectionVisible')
            ->setDefaults(
                [
                    'product' => null,
                    'attributeFamily' => null
                ]
            )
            ->setRequired('productPrices');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
