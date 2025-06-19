<?php

namespace Oro\Bundle\OrderBundle\ImportExport\Converter;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationDataAccessorInterface;

/**
 * Adds customer data to order entity from the data of customerUser field.
 */
class CustomerConverter implements ComplexDataConverterInterface
{
    public function __construct(
        private readonly ComplexDataConvertationDataAccessorInterface $dataAccessor
    ) {
    }

    #[\Override]
    public function convert(array $item, mixed $sourceData): array
    {
        $relationships = $item[self::ENTITY][JsonApiDoc::RELATIONSHIPS];
        if (isset($relationships['customerUser'][JsonApiDoc::DATA])) {
            $customerUserId = (int)$relationships['customerUser'][JsonApiDoc::DATA][JsonApiDoc::ID];
            $customerUser = $this->dataAccessor->findEntity(CustomerUser::class, 'id', $customerUserId);
            if (null !== $customerUser) {
                $customerId = $customerUser->getCustomer()?->getId();
                if (null !== $customerId) {
                    $item[self::ENTITY][JsonApiDoc::RELATIONSHIPS]['customer'] = [
                        JsonApiDoc::DATA => [
                            JsonApiDoc::TYPE => 'customers',
                            JsonApiDoc::ID => (string)$customerId
                        ]
                    ];
                }
            }
        }

        return $item;
    }
}
