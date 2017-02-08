<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\CMSBundle\ContentVariantType\CmsPageContentVariantType;
use Oro\Component\WebCatalog\Form\AbstractPageVariantType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CmsPageVariantType extends AbstractPageVariantType
{
    const NAME = 'oro_cms_page_variant';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'cmsPage',
                PageSelectType::NAME,
                [
                    'label' => 'oro.cms.page.entity_label',
                    'required' => true,
                    'constraints' => [new NotBlank()]
                ]
            );

        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPageContentVariantTypeName()
    {
        return CmsPageContentVariantType::TYPE;
    }
}
