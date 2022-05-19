<?php

namespace Oro\Bundle\CMSBundle\Tools;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\Error;

/**
 * Checks that WYSIWYG content does not have errors after purifying.
 */
class WYSIWYGContentChecker
{
    /** @var HTMLPurifierScopeProvider */
    private $htmlPurifierScopeProvider;

    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /** @var Environment */
    private $twig;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        HTMLPurifierScopeProvider $htmlPurifierScopeProvider,
        HtmlTagHelper $htmlTagHelper,
        Environment $twig,
        TranslatorInterface $translator
    ) {
        $this->htmlPurifierScopeProvider = $htmlPurifierScopeProvider;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    public function check(string $content, string $className, string $fieldName): array
    {
        return array_merge(
            $this->getTwigErrorList($content),
            $this->getHtmlErrorList($content, $className, $fieldName)
        );
    }

    private function getTwigErrorList(string $content): array
    {
        try {
            $templateWrapper = $this->twig->createTemplate($content);
            $templateWrapper->render();
        } catch (Error $e) {
            $errors[] = [
                'message' => $this->translator->trans(
                    'oro.cms.wysiwyg.formatted_twig_error_line',
                    [
                        '{{ line }}' => $e->getTemplateLine(),
                        '{{ twig escaping link }}' => \sprintf(
                            '<a href="%s" target="_blank">%s</a>',
                            'https://twig.symfony.com/doc/2.x/templates.html#escaping',
                            $this->translator->trans('oro.cms.wysiwyg.twig_escaping_link_text')
                        ),
                    ]
                ),
                'line' => $e->getTemplateLine()
            ];
        }

        return $errors ?? [];
    }

    private function getHtmlErrorList(string $content, string $className, string $fieldName): array
    {
        $scope = $this->htmlPurifierScopeProvider->getScope($className, $fieldName);
        if (!$scope) {
            return [];
        }

        $this->htmlTagHelper->sanitize($content, $scope);

        $errorCollector = $this->htmlTagHelper->getLastErrorCollector();
        if (!$errorCollector || !$errorCollector->getRaw()) {
            return [];
        }

        return array_map(
            function (array $error) {
                return [
                    'message' => $this->translator->trans(
                        'oro.htmlpurifier.formatted_error_line',
                        [
                            '{{ line }}' => $error[\HTMLPurifier_ErrorCollector::LINENO],
                            '{{ message }}' => $error[\HTMLPurifier_ErrorCollector::MESSAGE],
                        ]
                    ),
                    'messageRaw' => $error[\HTMLPurifier_ErrorCollector::MESSAGE],
                    'line' => $error[\HTMLPurifier_ErrorCollector::LINENO]
                ];
            },
            $errorCollector->getRaw()
        );
    }
}
