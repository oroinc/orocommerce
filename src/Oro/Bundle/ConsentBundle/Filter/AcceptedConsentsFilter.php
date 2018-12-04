<?php

namespace Oro\Bundle\ConsentBundle\Filter;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Adds consents filter choices depending on consents feature enabled status
 */
class AcceptedConsentsFilter extends DictionaryFilter
{
    /**
     * @var DoctrineHelper $doctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;

        parent::__construct($factory, $util);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        /** @var Consent[] $consents */
        $consents = $this->doctrineHelper->getEntityRepository(Consent::class)->findAll();

        $dictionaryChoiceCollection = [];
        foreach ($consents as $consent) {
            $dictionaryChoiceCollection[] = [
                'id' => $consent->getId(),
                'value' => $consent->getId(),
                'text' => $consent->getDefaultName()->getString()
            ];
        }

        $metadata['class'] = '';
        $metadata['select2ConfigData'] = $dictionaryChoiceCollection;

        return $metadata;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @return mixed
     */
    protected function getFilteredFieldName(FilterDatasourceAdapterInterface $ds)
    {
        return $this->get(FilterUtility::DATA_NAME_KEY);
    }
}
