<?php

namespace Oro\Bundle\PricingBundle\Form\OptionsConfigurator;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

/**
 * Configurator for price rule editor autocomplete data.
 */
class PriceRuleEditorOptionsConfigurator
{
    /**
     * @var AutocompleteFieldsProviderInterface
     */
    private $autocompleteFieldsProvider;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(
        AutocompleteFieldsProviderInterface $autocompleteFieldsProvider,
        FormFactoryInterface $formFactory,
        Environment $environment
    ) {
        $this->autocompleteFieldsProvider = $autocompleteFieldsProvider;
        $this->formFactory = $formFactory;
        $this->twig = $environment;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('numericOnly', false);
        $resolver->setDefault('entities', []);
        $resolver->setDefault('dataSource', []);
        $resolver->setAllowedTypes('numericOnly', 'bool');

        $resolver->setNormalizer('entities', function (Options $options, $entities) {
            if (empty($entities)) {
                return $this->autocompleteFieldsProvider->getAutocompleteData($options['numericOnly']);
            }

            return $entities;
        });

        $resolver->setNormalizer('dataSource', function (Options $options, $dataSource) {
            if (!empty($options['entities'])) {
                $rootKey = AutocompleteFieldsProviderInterface::ROOT_ENTITIES_KEY;
                if (!empty($options['entities'][$rootKey][PriceList::class])) {
                    $key = $options['entities'][$rootKey][PriceList::class];
                    $priceListSelectView = $this->formFactory
                        ->createNamed(
                            uniqid('price_list_select___name___', false),
                            PriceListSelectType::class,
                            null,
                            ['create_enabled' => false]
                        )
                        ->createView();

                    try {
                        return [
                            $key => $this->twig->render(
                                '@OroPricing/Form/form_widget.html.twig',
                                ['form' => $priceListSelectView]
                            )
                        ];
                    } catch (\Exception $e) {
                        return $dataSource;
                    }
                }
            }

            return $dataSource;
        });
    }

    public function limitNumericOnlyRules(FormView $view, array $options)
    {
        if ($options['numericOnly']) {
            $componentOptions = json_decode($view->vars['attr']['data-page-component-options'], JSON_OBJECT_AS_ARRAY);
            $componentOptions['allowedOperations'] = ['math'];
            $view->vars['attr']['data-page-component-options'] = json_encode($componentOptions);
        }
    }
}
