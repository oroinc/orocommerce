<?php

namespace Oro\Component\Expression\Preprocessor;

/**
 * Orchestrates multiple expression preprocessors to transform expressions iteratively.
 *
 * This class manages a collection of preprocessors and applies them sequentially to an expression
 * until the expression stabilizes (no further changes occur). It prevents infinite loops by enforcing
 * a maximum iteration limit, ensuring that preprocessing terminates even if preprocessors create
 * circular transformations.
 */
class ExpressionPreprocessor implements ExpressionPreprocessorInterface
{
    public const MAX_ITERATIONS = 100;

    /**
     * @var array|ExpressionPreprocessorInterface[]
     */
    protected $preprocessors = [];

    public function registerPreprocessor(ExpressionPreprocessorInterface $preprocessor)
    {
        $this->preprocessors[] = $preprocessor;
    }

    #[\Override]
    public function process($expression)
    {
        $iteration = 0;
        do {
            $iteration++;
            $unprocessedExpression = $expression;
            foreach ($this->preprocessors as $preprocessor) {
                $expression = $preprocessor->process($expression);
            }
        } while ($unprocessedExpression !== $expression && $iteration < self::MAX_ITERATIONS);

        if ($iteration === self::MAX_ITERATIONS) {
            throw new \RuntimeException(sprintf('Max iterations count %d exceed', self::MAX_ITERATIONS));
        }

        return $expression;
    }
}
