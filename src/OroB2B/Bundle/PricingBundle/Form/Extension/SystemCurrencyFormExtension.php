<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountType;

class SystemCurrencyFormExtension extends AbstractTypeExtension
{
    const ALLOWED_CURRENCIES = 'oro_currency.allowed_currencies';
    const ENABLED_CURRENCIES = 'oro_b2b_pricing.enabled_currencies';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(ConfigManager $configManager, TranslatorInterface $translator)
    {
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSetData']);
    }

    public function postSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $restrict = $form->getConfig()->getOption('restrict');
        $enabledCurrencies = $this->configManager->get('oro_b2b_pricing.enabled_currencies');

        if ($restrict && $enabledCurrencies) {
            $allowedCurrencies = $form->getData();
            $alreadyInUse = array_diff($enabledCurrencies, $allowedCurrencies);

            if ($alreadyInUse) {
                $form->addError(new FormError(
                    $this->translator->transChoice(
                        'orob2b.pricing.validators.using_as_available',
                        count($alreadyInUse),
                        array('%curr%' => implode(', ', $alreadyInUse)),
                        'validators'
                    )
                ));
            }
        }
    }
}
