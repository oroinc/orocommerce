<?php

namespace Oro\Component\Expression\Preprocessor;

class ExpressionPreprocessor implements ExpressionPreprocessorInterface
{
    const MAX_ITERATIONS = 100;

    /**
     * @var array|ExpressionPreprocessorInterface[]
     */
    protected $preprocessors = [];

    /**
     * @param ExpressionPreprocessorInterface $preprocessor
     */
    public function registerPreprocessor(ExpressionPreprocessorInterface $preprocessor)
    {
        $this->preprocessors[] = $preprocessor;
    }

    /**
     * {@inheritdoc}
     */
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
