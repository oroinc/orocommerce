<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Form\Handler\ContentWidgetHandler;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;

class ContentWidgetHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private const FORM_NAME = 'test_form';

    /** @var array */
    private const FORM_DATA = ['settings' => ['param' => 'value']];

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ContentWidgetHandler */
    private $handler;

    /** @var ContentWidget */
    private $data;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request */
    private $request;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(EntityManager::class);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ContentWidget::class)
            ->willReturn($this->manager);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new ContentWidgetHandler($registry, $this->eventDispatcher);

        $this->data = new ContentWidget();

        $formBuilder = Forms::createFormFactory()
            ->createNamedBuilder(self::FORM_NAME, FormType::class, null, ['data_class' => ContentWidget::class])
            ->add('settings', FormType::class);

        $formBuilder->get('settings')
            ->add('param', TextType::class);

        $this->form = $formBuilder->getForm();

        $this->request = new Request([], [self::FORM_NAME => self::FORM_DATA]);
    }

    public function testProcessWrongRequest(): void
    {
        $this->request->setMethod(Request::METHOD_GET);

        $this->manager->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->handler->process($this->data, $this->form, $this->request));
        $this->assertFalse($this->form->isSubmitted());
        $this->assertEquals(new ContentWidget(), $this->data);
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testProcessInvalidForm(string $method): void
    {
        $this->request->initialize([], [self::FORM_NAME => ['settings' => new \stdClass()]]);
        $this->request->setMethod($method);

        $this->manager->expects($this->never())
            ->method('persist');

        $this->manager->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->handler->process($this->data, $this->form, $this->request));
        $this->assertFalse($this->form->isValid());
        $this->assertEquals(new ContentWidget(), $this->data);
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testProcessWithUpdateMarker(string $method): void
    {
        $this->request->request->set(ContentWidgetHandler::UPDATE_MARKER, true);
        $this->request->setMethod($method);

        $this->manager->expects($this->never())
            ->method('persist');

        $this->manager->expects($this->never())
            ->method('flush');

        $this->assertFalse($this->handler->process($this->data, $this->form, $this->request));
        $this->assertTrue($this->form->isValid());

        $expected = new ContentWidget();
        $expected->setSettings(self::FORM_DATA['settings']);

        $this->assertEquals($expected, $this->data);
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testProcess(string $method): void
    {
        $this->request->setMethod($method);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->data);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [static::anything(), Events::BEFORE_FORM_DATA_SET],
                [static::anything(), Events::BEFORE_FORM_SUBMIT],
                [static::anything(), Events::BEFORE_FLUSH],
                [static::anything(), Events::AFTER_FLUSH]
            );

        $this->assertTrue($this->handler->process($this->data, $this->form, $this->request));
        $this->assertTrue($this->form->isValid());

        $expected = new ContentWidget();
        $expected->setSettings(self::FORM_DATA['settings']);

        $this->assertEquals($expected, $this->data);
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testProcessException(string $method): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument data should be instance of ContentWidget entity');

        $this->request->setMethod($method);

        $this->handler->process(new \stdClass(), $this->form, $this->request);
    }

    public function handleDataProvider(): array
    {
        return [
            [
                'method' => Request::METHOD_POST,
            ],
            [
                'method' => Request::METHOD_PUT,
            ]
        ];
    }
}
