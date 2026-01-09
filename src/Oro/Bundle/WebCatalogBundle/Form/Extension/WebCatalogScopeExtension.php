<?php

namespace Oro\Bundle\WebCatalogBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeType;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Extends the scope form to add web catalog selection field.
 */
class WebCatalogScopeExtension extends AbstractTypeExtension
{
    public const SCOPE_FIELD = 'webCatalog';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (array_key_exists(self::SCOPE_FIELD, $options['scope_fields'])) {
            $builder->add(
                self::SCOPE_FIELD,
                EntityIdentifierType::class,
                [
                    'data' => $options['web_catalog'],
                    'class' => WebCatalog::class,
                    'multiple' => false,
                    'data_class' => null
                ]
            );
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('web_catalog', null);
        $resolver->setAllowedTypes('web_catalog', ['null', WebCatalog::class]);
        $resolver->setNormalizer(
            'scope_type',
            function (Options $options, $scopeType) {
                if ($scopeType === 'web_content' && !$options['web_catalog']) {
                    throw new InvalidConfigurationException('The option "web_catalog" must be set.');
                }

                return $scopeType;
            }
        );
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ScopeType::class];
    }
}
