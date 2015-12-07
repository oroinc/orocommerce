<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Model\Action\ChangeCategoryPosition;

class ChangeCategoryPositionTest extends CategoryCaseActionTestCase
{
    /**
     * @var ChangeCategoryPosition
     */
    protected $action;

    /**
     * @return string
     */
    protected function getActionContainerId()
    {
        // TODO
        return 'container.id';
    }

    /**
     * @dataProvider positionChangeDataProvider
     *
     * @param string $categoryVisibilityReference
     * @param string $visibility
     * @param array $expectedData
     */
    public function testPositionChange($categoryVisibilityReference, $visibility, array $expectedData)
    {
        $this->markTestIncomplete('Waiting for action service');

        /** @var VisibilityInterface $categoryVisibility */
        $categoryVisibility = $this->getReference($categoryVisibilityReference);

        $categoryVisibility->setVisibility($visibility);

        $this->context->expects($this->once())
            ->method('getEntity')
            ->willReturn($categoryVisibility);

        $this->action->execute($this->context);

        $this->assertProductVisibilityResolvedCorrect($expectedData);
    }

    /**
     * @return array
     */
    public function positionChangeDataProvider()
    {
        return [

        ];
    }
}
