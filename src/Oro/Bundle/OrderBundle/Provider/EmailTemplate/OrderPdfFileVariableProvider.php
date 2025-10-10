<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Provider\EmailTemplate;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides definition for the order PDF file variable with configurable name (e.g. orderDefaultPdfFile)
 * for email templates.
 */
class OrderPdfFileVariableProvider implements EntityVariablesProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $pdfFileVariableName
    ) {
    }

    #[\Override]
    public function getVariableDefinitions(): array
    {
        return [
            Order::class => [
                $this->pdfFileVariableName => [
                    'type' => 'ref-one',
                    'related_entity_name' => File::class,
                    'label' => $this->translator->trans(
                        'oro.order.email_template.variables.' . $this->getPdfVariableSnakeName(),
                    ),
                ],
            ],
        ];
    }

    #[\Override]
    public function getVariableGetters(): array
    {
        return [
            Order::class => [
                $this->pdfFileVariableName => null,
            ],
        ];
    }

    #[\Override]
    public function getVariableProcessors(string $entityClass): array
    {
        if ($entityClass === Order::class) {
            return [
                $this->pdfFileVariableName => [
                    'processor' => $this->getPdfVariableSnakeName(),
                ],
            ];
        }

        return [];
    }

    private function getPdfVariableSnakeName(): string
    {
        return (new UnicodeString($this->pdfFileVariableName))->snake()->toString();
    }
}
