<?php

namespace Oro\Bundle\CMSBundle\Tools;

use Oro\Bundle\CMSBundle\Provider\HTMLPurifierScopeProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks that WYSIWYG content does not have errors after purifying.
 */
class WYSIWYGContentChecker
{
    /** @var HTMLPurifierScopeProvider */
    private $htmlPurifierScopeProvider;

    /** @var HtmlTagHelper */
    private $htmlTagHelper;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param HTMLPurifierScopeProvider $htmlPurifierScopeProvider
     * @param HtmlTagHelper $htmlTagHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        HTMLPurifierScopeProvider $htmlPurifierScopeProvider,
        HtmlTagHelper $htmlTagHelper,
        TranslatorInterface $translator
    ) {
        $this->htmlPurifierScopeProvider = $htmlPurifierScopeProvider;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->translator = $translator;
    }

    /**
     * @param string $content
     * @param string $className
     * @param string $fieldName
     * @return array
     */
    public function check(string $content, string $className, string $fieldName): array
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
                    'line' => $error[\HTMLPurifier_ErrorCollector::LINENO]
                ];
            },
            $errorCollector->getRaw()
        );
    }
}
