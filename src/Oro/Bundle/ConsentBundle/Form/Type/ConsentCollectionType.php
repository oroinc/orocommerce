<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\Validator\Constraints\UniqueConsent;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for the collection of Consent select with ordering types
 */
class ConsentCollectionType extends AbstractType
{
    /** @var DataTransformerInterface */
    protected $consentCollectionTransformer;

    public function __construct(DataTransformerInterface $consentCollectionTransformer)
    {
        $this->consentCollectionTransformer = $consentCollectionTransformer;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->consentCollectionTransformer);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => ConsentSelectWithPriorityType::class,
                'entry_options' => [
                    'data_class' => ConsentConfig::class,
                ],
                'mapped' => true,
                'constraints' => [new UniqueConsent()]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_consent_collection';
    }
}
