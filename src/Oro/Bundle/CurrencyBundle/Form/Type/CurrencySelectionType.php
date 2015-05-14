<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class CurrencySelectionType extends AbstractType
{
    const NAME = 'oro_currency_selection';

    /**
     * @var array
     */
    protected $currencies = [];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param ConfigManager $configManager
     * @param string $locale
     */
    public function __construct(ConfigManager $configManager, $locale)
    {
        $this->currencies = $configManager->get('oro_currency.allowed_currencies');
        $this->locale = $locale;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $currencies = array_intersect_key(
            Intl::getCurrencyBundle()->getCurrencyNames($this->locale),
            array_fill_keys($this->currencies, null)
        );

        $resolver->setDefaults([
            'choices' => $currencies,
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }
}