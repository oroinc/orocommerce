<?php

namespace Oro\Bundle\ApruveBundle\Form\Type;

use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ApruveSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_apruve_settings';

    /**
     * @var string
     *
     * For the sake of extensibility.
     */
    protected $dataClass;

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @param TransportInterface $transport
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.apruve.settings.labels.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'shortLabels',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.apruve.settings.short_labels.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add('merchantId', TextType::class, [
                'label' => 'oro.apruve.settings.merchant_id.label',
                'required' => true,
            ])
            ->add('apiKey', TextType::class, [
                'label' => 'oro.apruve.settings.api_key.label',
                'required' => true,
            ])
            ->add('testMode', CheckboxType::class, [
                'label' => 'oro.apruve.settings.test_mode.label',
                'required' => false,
            ])
            ->add('webhookToken', WebhookTokenType::class, [
                'label' => 'oro.apruve.settings.webhook_url.label',
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass ?: $this->transport->getSettingsEntityFQCN(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
