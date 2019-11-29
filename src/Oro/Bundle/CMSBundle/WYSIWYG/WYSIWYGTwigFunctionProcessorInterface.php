<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;

/**
 * Processing twig functions usages on save entities with wysiwyg fields.
 */
interface WYSIWYGTwigFunctionProcessorInterface
{
    public const FIELD_CONTENT_TYPE = WYSIWYGType::TYPE;
    public const FIELD_STYLES_TYPE = WYSIWYGStyleType::TYPE;

    /**
     * @return string[][] ['DBAL_type' => ['function_name', ...], ...]
     */
    public function getApplicableMapping(): array;

    /**
     * @param WYSIWYGProcessedDTO $processedDTO
     * @param array $twigFunctionCalls
     * @return bool True if entity manager flush is needed, false otherwise
     */
    public function processTwigFunctions(WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls): bool;

    /**
     * @param WYSIWYGProcessedDTO $processedDTO
     * @return bool True if entity manager flush is needed, false otherwise
     */
    public function onPreRemove(WYSIWYGProcessedDTO $processedDTO): bool;
}
