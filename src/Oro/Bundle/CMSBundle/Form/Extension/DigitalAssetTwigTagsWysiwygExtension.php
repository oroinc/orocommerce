<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Adds event subscriber for WYSIWYGType and WYSIWYGStyleType.
 */
class DigitalAssetTwigTagsWysiwygExtension extends AbstractTypeExtension
{
    /** @var DigitalAssetTwigTagsConverter */
    private DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter;

    /**
     * @param DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter
     */
    public function __construct(DigitalAssetTwigTagsConverter $digitalAssetTwigTagsConverter)
    {
        $this->digitalAssetTwigTagsConverter = $digitalAssetTwigTagsConverter;
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [WYSIWYGType::class, WYSIWYGStylesType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(
            new CallbackTransformer(
                function ($value) {
                    if (!$value) {
                        return $value;
                    }

                    return $this->digitalAssetTwigTagsConverter->convertToUrls($value);
                },
                function ($value) {
                    if (!$value) {
                        return $value;
                    }

                    return $this->digitalAssetTwigTagsConverter->convertToTwigTags($value);
                }
            )
        );
    }
}
