<?php

namespace Oro\Bundle\ConsentBundle\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\DictionaryFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by accepted consents.
 */
class AcceptedConsentsFilter extends DictionaryFilter
{
    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($factory, $util);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        /** @var Consent[] $consents */
        $consents = $this->doctrine->getRepository(Consent::class)->findAll();

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
     * {@inheritdoc}
     */
    protected function getFilteredFieldName(FilterDatasourceAdapterInterface $ds)
    {
        return $this->get(FilterUtility::DATA_NAME_KEY);
    }
}
