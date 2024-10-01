<?php

namespace Oro\Bundle\SaleBundle\Provider;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a text representation of Quote entity.
 */
class QuoteEntityNameProvider implements EntityNameProviderInterface
{
    private const TRANSLATION_KEY = 'oro.frontend.sale.quote.title.label';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function getName($format, $locale, $entity)
    {
        if (!$entity instanceof Quote) {
            return false;
        }

        if (self::FULL === $format) {
            return $this->trans(self::TRANSLATION_KEY, ['%id%' => $entity->getId()], $locale);
        }

        return false;
    }

    #[\Override]
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!is_a($className, Quote::class, true)) {
            return false;
        }

        if (self::FULL === $format) {
            return sprintf('CONCAT(%s)', str_replace(
                '%id%',
                sprintf("', %s.id, '", $alias),
                (string)(new Expr())->literal($this->trans(self::TRANSLATION_KEY, [], $locale))
            ));
        }

        return false;
    }

    private function trans(string $key, array $params, string|Localization|null $locale): string
    {
        if ($locale instanceof Localization) {
            $locale = $locale->getLanguageCode();
        }

        return $this->translator->trans($key, $params, null, $locale);
    }
}
