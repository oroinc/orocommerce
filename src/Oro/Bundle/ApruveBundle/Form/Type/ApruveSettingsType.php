<?php

namespace Oro\Bundle\ApruveBundle\Form\Type;

use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactoryInterface;
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
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var CryptedDataTransformerFactoryInterface
     */
    private $cryptedDataTransformerFactory;

    /**
     * @param TransportInterface $transport
     * @param CryptedDataTransformerFactoryInterface $cryptedDataTransformerFactory
     */
    public function __construct(
        TransportInterface $transport,
        CryptedDataTransformerFactoryInterface $cryptedDataTransformerFactory
    ) {
        $this->transport = $transport;
        $this->cryptedDataTransformerFactory = $cryptedDataTransformerFactory;
    }

    /**
     * {@inheritDoc}
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
            ->add('apruveMerchantId', TextType::class, [
                'label' => 'oro.apruve.settings.merchant_id.label',
                'required' => true,
            ])
            ->add('apruveApiKey', TextType::class, [
                'label' => 'oro.apruve.settings.api_key.label',
                'required' => true,
            ])
            ->add('apruveTestMode', CheckboxType::class, [
                'label' => 'oro.apruve.settings.test_mode.label',
                'required' => false,
            ])
            ->add('apruveWebhookToken', WebhookTokenType::class, [
                'label' => 'oro.apruve.settings.webhook_url.label',
                'required' => false,
            ]);

        $this->enableEncryption($builder, 'apruveApiKey');
        $this->enableEncryption($builder, 'apruveMerchantId');
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => $this->transport->getSettingsEntityFQCN()]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string $fieldName
     */
    private function enableEncryption(FormBuilderInterface $builder, $fieldName)
    {
        $builder->get($fieldName)->addModelTransformer($this->cryptedDataTransformerFactory->create());
    }
}
