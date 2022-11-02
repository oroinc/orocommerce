<?php

namespace Oro\Bundle\PricingBundle\Form\OptionsConfigurator;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
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

    /**
     * @var EntityAliasResolver
     */
    private $entityAliasResolver;

    public function __construct(
        AutocompleteFieldsProviderInterface $autocompleteFieldsProvider,
        FormFactoryInterface $formFactory,
        Environment $environment,
        EntityAliasResolver $entityAliasResolver
    ) {
        $this->autocompleteFieldsProvider = $autocompleteFieldsProvider;
        $this->formFactory = $formFactory;
        $this->twig = $environment;
        $this->entityAliasResolver = $entityAliasResolver;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('numericOnly', false);
        $resolver->setDefault('dataProviderConfig', []);
        $resolver->setDefault('supportedNames', []);
        $resolver->setDefault('dataSource', []);
        $resolver->setAllowedTypes('numericOnly', 'bool');

        $resolver->setNormalizer('dataProviderConfig', function (Options $options, $dataProviderConfig) {
            if (empty($dataProviderConfig)) {
                return $this->autocompleteFieldsProvider->getDataProviderConfig($options['numericOnly']);
            }
            return $dataProviderConfig;
        });

        $resolver->setNormalizer('supportedNames', function (Options $options, $rootEntities) {
            if (empty($rootEntities)) {
                $entities = $this->autocompleteFieldsProvider->getRootEntities();
                return !empty($entities) ? array_values($entities) : [];
            }

            return $rootEntities;
        });

        $resolver->setNormalizer('dataSource', function (Options $options, $dataSource) {
            if (empty($options['supportedNames'])) {
                return $dataSource;
            }
            $entityAlias = $this->entityAliasResolver->getAlias(PriceList::class);
            if (in_array($entityAlias, $options['supportedNames'], true)) {
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
                        $entityAlias => $this->twig->render(
                            '@OroPricing/Form/form_widget.html.twig',
                            ['form' => $priceListSelectView]
                        )
                    ];
                } catch (\Exception $e) {
                    return $dataSource;
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
