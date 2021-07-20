<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "default" field for Localization entity.
 */
class ComputeLocalizationDefaultField implements ProcessorInterface
{
    private const DEFAULT_FIELD = 'default';

    /** @var LocalizationManager */
    private $localizationManager;

    public function __construct(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
    }

    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequestedForCollection(self::DEFAULT_FIELD, $data)) {
            return;
        }

        $defaultLocalization = $this->localizationManager->getDefaultLocalization();
        foreach ($data as $key => $item) {
            $item[self::DEFAULT_FIELD] = $item['id'] === $defaultLocalization->getId();
            $data[$key] = $item;
        }

        $context->setData($data);
    }
}
