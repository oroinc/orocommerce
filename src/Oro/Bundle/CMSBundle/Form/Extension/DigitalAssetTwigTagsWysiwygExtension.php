<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGStylesType;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds event subscriber for WYSIWYGType and WYSIWYGStyleType.
 */
class DigitalAssetTwigTagsWysiwygExtension extends AbstractTypeExtension
{
    /** @var DigitalAssetTwigTagsConverter */
    private $digitalAssetTwigTagsConverter;

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
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'], 512);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit'], -512);
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        if ($data) {
            $event->setData($this->digitalAssetTwigTagsConverter->convertToUrls($data));
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if ($data) {
            $event->setData($this->digitalAssetTwigTagsConverter->convertToTwigTags($data));
        }
    }
}
