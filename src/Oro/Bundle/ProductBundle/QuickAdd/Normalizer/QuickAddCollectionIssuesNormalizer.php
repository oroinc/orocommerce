<?php

namespace Oro\Bundle\ProductBundle\QuickAdd\Normalizer;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Normalizes the collection of issues to an array.
 */
class QuickAddCollectionIssuesNormalizer
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @return array<int, array{
     *     index: int,
     *     errors: array<int, array{message: string, propertyPath: string}>,
     *     warnings: array<int, array{message: string, propertyPath: string}>
     * }>
     */
    public function normalize(QuickAddRowCollection $quickAddRowCollection): array
    {
        $result = [];

        foreach ($quickAddRowCollection as $row) {
            $result[] = [
                'index' => $row->getIndex(),
                'errors' => $this->mapIssues($row->getErrors()),
                'warnings' => $this->mapIssues($row->getWarnings())
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array{message: string, parameters: array<string, mixed>, propertyPath?: string}> $issues
     * @return array<int, array{message: string, propertyPath: string}>
     */
    private function mapIssues(array $issues): array
    {
        return array_map(
            fn (array $issue) => [
                'message' => $this->translator->trans($issue['message'], $issue['parameters'], 'validators'),
                'propertyPath' => $issue['propertyPath'] ?? '',
            ],
            $issues
        );
    }
}
