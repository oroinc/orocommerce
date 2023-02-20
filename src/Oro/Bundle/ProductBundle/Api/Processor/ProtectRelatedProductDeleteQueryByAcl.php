<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;

/**
 * Modifies the query that is used to retrieve a related product to be deleted
 * to return only relations for which both products in relation are accessible by an user,
 * and adds an additional ACL check for VIEW permission for the query.
 * It is required because of Delete action sets ACL before the query is modified.
 */
class ProtectRelatedProductDeleteQueryByAcl extends ProtectRelatedProductQueryByAcl
{
    private AclHelper $aclHelper;

    public function __construct(DoctrineHelper $doctrineHelper, AclHelper $aclHelper)
    {
        parent::__construct($doctrineHelper);
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        parent::process($context);

        $context->setQuery($this->aclHelper->apply($context->getQuery()));
    }
}
