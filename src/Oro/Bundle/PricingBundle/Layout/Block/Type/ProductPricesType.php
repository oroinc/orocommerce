<?php

namespace Oro\Bundle\PricingBundle\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

/**
 * Product price layout block type.
 */
class ProductPricesType extends AbstractContainerType
{
    const NAME = 'product_prices';

    /** @var AttributeRenderRegistry */
    private $attributeRenderRegistry;

    /** @var AttributeManager */
    private $attributeManager;

    /** @var array */
    private $fetchedAttributes = [];

    public function __construct(AttributeRenderRegistry $attributeRenderRegistry, AttributeManager $attributeManager)
    {
        $this->attributeRenderRegistry = $attributeRenderRegistry;
        $this->attributeManager = $attributeManager;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return FieldConfigModel|null
     */
    private function getAttribute(AttributeFamily $attributeFamily)
    {
        $attributeName = PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES;
        $code = $attributeFamily->getCode();
        if (!isset($this->fetchedAttributes[$code][$attributeName])) {
            $attribute = $this->attributeManager->getAttributeByFamilyAndName($attributeFamily, $attributeName);

            $this->fetchedAttributes[$code][$attributeName] = $attribute;
        }

        return $this->fetchedAttributes[$code][$attributeName];
    }

    /**
     * {@inheritDoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, Options $options)
    {
        if (null === $options['attributeFamily']) {
            return;
        }

        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $options['attributeFamily'];

        $attribute = $this->getAttribute($attributeFamily);
        if (!$attribute) {
            return;
        }

        $attributeOptions = $attribute->toArray('frontend');
        $options->setMultiple(['visible' => $attributeOptions['is_displayable'] ?? true]);

        $this->attributeRenderRegistry->setAttributeRendered($attributeFamily, $attribute->getFieldName());
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions(
            $view,
            $options,
            ['product', 'productPrices', 'isPriceUnitsVisible']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired([
                'productPrices',
                'isPriceUnitsVisible',
            ])
            ->setDefaults(
                [
                    'product' => null,
                    'attributeFamily' => null
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
