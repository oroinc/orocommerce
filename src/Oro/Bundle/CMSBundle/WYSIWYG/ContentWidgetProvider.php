<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides a method to get a list of content widgets by their names.
 */
class ContentWidgetProvider
{
    protected AclHelper $aclHelper;

    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param WYSIWYGProcessedDTO $processedDTO
     * @param string[]               $names
     *
     * @return ContentWidget[]
     */
    public function getContentWidgets(WYSIWYGProcessedDTO $processedDTO, array $names): array
    {
        $qb = $this->getContentWidgetsQueryBuilder($processedDTO, $names);

        return $this->aclHelper->apply($qb)->getResult();
    }

    protected function getContentWidgetsQueryBuilder(WYSIWYGProcessedDTO $processedDTO, array $names): QueryBuilder
    {
        return $processedDTO->getProcessedEntity()
            ->getEntityManager()
            ->createQueryBuilder()
            ->from(ContentWidget::class, 'content_widget')
            ->select('content_widget')
            ->where('content_widget.name IN (:names)')
            ->setParameter(':names', $names, Connection::PARAM_STR_ARRAY);
    }
}
