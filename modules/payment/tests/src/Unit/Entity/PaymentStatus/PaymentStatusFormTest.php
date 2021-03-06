<?php

/**
 * @file
 * Contains \Drupal\Tests\payment\Unit\Entity\PaymentStatus\PaymentStatusFormTest.
 */

namespace Drupal\Tests\payment\Unit\Entity\PaymentStatus {

  use Drupal\Core\DependencyInjection\ClassResolverInterface;
  use Drupal\Core\Entity\EntityManagerInterface;
  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Form\FormState;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Language\LanguageInterface;
  use Drupal\payment\Entity\PaymentStatus\PaymentStatusForm;
  use Drupal\payment\Entity\PaymentStatusInterface;
  use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorInterface;
  use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
  use Drupal\plugin\PluginType\PluginType;
  use Drupal\plugin\PluginType\PluginTypeManagerInterface;
  use Drupal\Tests\UnitTestCase;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * @coversDefaultClass \Drupal\payment\Entity\PaymentStatus\PaymentStatusForm
   *
   * @group Payment
   */
  class PaymentStatusFormTest extends UnitTestCase {

    /**
     * The payment status.
     *
     * @var \Drupal\payment\Entity\PaymentStatus|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentStatus;

    /**
     * The payment method configuration manager.
     *
     * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentStatusManager;

    /**
     * The payment status storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentStatusStorage;

    /**
     * The plugin selector manager.
     *
     * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pluginSelectorManager;

    /**
     * The plugin type manager.
     *
     * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pluginTypeManager;

    /**
     * The string translator.
     *
     * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stringTranslation;

    /**
     * The form under test.
     *
     * @var \Drupal\payment\Entity\PaymentStatus\PaymentStatusForm
     */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    public function setUp() {
      $this->paymentStatusManager = $this->getMock(PaymentStatusManagerInterface::class);

      $this->paymentStatusStorage = $this->getMock(EntityStorageInterface::class);

      $this->paymentStatus = $this->getMock(PaymentStatusInterface::class);

      $this->pluginSelectorManager = $this->getMock(PluginSelectorManagerInterface::class);

      $class_resolver = $this->getMock(ClassResolverInterface::class);

      $this->stringTranslation = $this->getStringTranslationStub();

      $this->pluginTypeManager = $this->getMock(PluginTypeManagerInterface::class);
      $plugin_type_definition = [
        'id' => $this->randomMachineName(),
        'label' => $this->randomMachineName(),
        'provider' => $this->randomMachineName(),
      ];
      $plugin_type = new PluginType($plugin_type_definition, $this->stringTranslation, $class_resolver, $this->paymentStatusManager);
      $this->pluginTypeManager->expects($this->any())
        ->method('getPluginType')
        ->with('payment_status')
        ->willReturn($plugin_type);

      $this->sut = new PaymentStatusForm($this->stringTranslation, $this->paymentStatusStorage, $this->pluginSelectorManager, $this->pluginTypeManager);
      $this->sut->setEntity($this->paymentStatus);
    }

    /**
     * @covers ::create
     * @covers ::__construct
     */
    function testCreate() {
      $entity_manager = $this->getMock(EntityManagerInterface::class);
      $entity_manager->expects($this->any())
        ->method('getStorage')
        ->with('payment_status')
        ->willReturn($this->paymentStatusStorage);

      $container = $this->getMock(ContainerInterface::class);
      $map = array(
        array('entity.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $entity_manager),
        array('plugin.manager.plugin.plugin_selector', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->pluginSelectorManager),
        array('plugin.plugin_type_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->pluginTypeManager),
        array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
      );
      $container->expects($this->any())
        ->method('get')
        ->willReturnMap($map);

      $form = PaymentStatusForm::create($container);
      $this->assertInstanceOf(PaymentStatusForm::class, $form);
    }

    /**
     * @covers ::form
     */
    public function testForm() {
      $label = $this->randomMachineName();
      $id = $this->randomMachineName();
      $is_new = FALSE;
      $parent_id = $this->randomMachineName();
      $description = $this->randomMachineName();

      $form_state = $this->getMock(FormStateInterface::class);

      $language = $this->getMock(LanguageInterface::class);

      $parent_selector_form = [
        '#foo' => $this->randomMachineName(),
      ];

      $parent_selector = $this->getMock(PluginSelectorInterface::class);
      $parent_selector->expects($this->atLeastOnce())
        ->method('buildSelectorForm')
        ->with([], $form_state)
        ->willReturn($parent_selector_form);

      $this->pluginSelectorManager->expects($this->atLeastOnce())
        ->method('createInstance')
        ->willReturn($parent_selector);

      $this->paymentStatus->expects($this->any())
        ->method('id')
        ->willReturn($id);
      $this->paymentStatus->expects($this->any())
        ->method('getDescription')
        ->willReturn($description);
      $this->paymentStatus->expects($this->any())
        ->method('getParentId')
        ->willReturn($parent_id);
      $this->paymentStatus->expects($this->any())
        ->method('isNew')
        ->willReturn($is_new);
      $this->paymentStatus->expects($this->any())
        ->method('label')
        ->willReturn($label);
      $this->paymentStatus->expects($this->any())
        ->method('language')
        ->willReturn($language);

      $build = $this->sut->form([], $form_state);
      unset($build['#process']);
      unset($build['langcode']);

      $expected_build['label'] = [
        '#type' => 'textfield',
        '#default_value' => $label,
        '#maxlength' => 255,
        '#required' => TRUE,
      ];
      unset($build['label']['#title']);
      $expected_build['id'] = [
        '#default_value' => $id,
        '#disabled' => !$is_new,
        '#machine_name' => array(
          'source' => array('label'),
          'exists' => array($this->sut, 'PaymentStatusIdExists'),
        ),
        '#maxlength' => 255,
        '#type' => 'machine_name',
        '#required' => TRUE,
      ];
      unset($build['id']['#title']);
      $expected_build['parent_id'] = $parent_selector_form;
      $expected_build['description'] = [
        '#type' => 'textarea',
        '#default_value' => $description,
        '#maxlength' => 255,
      ];
      unset($build['description']['#title']);
      $expected_build['#after_build'] = ['::afterBuild'];

      $this->assertSame($expected_build, $build);
    }

    /**
     * @covers ::copyFormValuesToEntity
     */
    public function testCopyFormValuesToEntity() {
      $description = $this->randomMachineName();
      $id = $this->randomMachineName();
      $label = $this->randomMachineName();
      $parent_id = $this->randomMachineName();

      $this->paymentStatus->expects($this->once())
        ->method('setDescription')
        ->with($description);
      $this->paymentStatus->expects($this->once())
        ->method('setId')
        ->with($id);
      $this->paymentStatus->expects($this->once())
        ->method('setLabel')
        ->with($label);
      $this->paymentStatus->expects($this->once())
        ->method('setParentId')
        ->with($parent_id);

      $parent_status = $this->getMock(PluginSelectorInterface::class);
      $parent_status->expects($this->atLeastOnce())
        ->method('getPluginId')
        ->willReturn($parent_id);

      $parent_selector = $this->getMock(PluginSelectorInterface::class);
      $parent_selector->expects($this->atLeastOnce())
        ->method('getSelectedPlugin')
        ->willReturn($parent_status);

      $this->pluginSelectorManager->expects($this->atLeastOnce())
        ->method('createInstance')
        ->willReturn($parent_selector);

      $form = [];
      $form_state = new FormState();
      $form_state->setValue('description', $description);
      $form_state->setValue('id', $id);
      $form_state->setValue('label', $label);
      $form_state->setValue('parent_id', $parent_id);

      $method = new \ReflectionMethod($this->sut, 'copyFormValuesToEntity');
      $method->setAccessible(TRUE);

      $method->invokeArgs($this->sut, array($this->paymentStatus, $form, $form_state));
    }

    /**
     * @covers ::paymentStatusIdExists
     */
    public function testPaymentStatusIdExists() {
      $method = new \ReflectionMethod($this->sut, 'paymentStatusIdExists');
      $method->setAccessible(TRUE);

      $payment_method_configuration_id = $this->randomMachineName();

      $this->paymentStatusStorage->expects($this->at(0))
        ->method('load')
        ->with($payment_method_configuration_id)
        ->willReturn($this->paymentStatus);
      $this->paymentStatusStorage->expects($this->at(1))
        ->method('load')
        ->with($payment_method_configuration_id)
        ->willReturn(NULL);

      $this->assertTrue($method->invoke($this->sut, $payment_method_configuration_id));
      $this->assertFalse($method->invoke($this->sut, $payment_method_configuration_id));
    }

    /**
     * @covers ::save
     */
    public function testSave() {
      $form_state = $this->getMock(FormStateInterface::class);
      $form_state->expects($this->once())
        ->method('setRedirect')
        ->with('entity.payment_status.collection');

      /** @var \Drupal\payment\Entity\PaymentStatus\PaymentStatusForm|\PHPUnit_Framework_MockObject_MockObject $form */
      $form = $this->getMockBuilder(PaymentStatusForm::class)
        ->setConstructorArgs(array($this->stringTranslation, $this->paymentStatusStorage, $this->pluginSelectorManager, $this->pluginTypeManager))
        ->setMethods(array('copyFormValuesToEntity'))
        ->getMock();
      $form->setEntity($this->paymentStatus);

      $this->paymentStatus->expects($this->once())
        ->method('save');

      $form->save([], $form_state);
    }

  }

}

namespace {

if (!function_exists('drupal_set_message')) {
  function drupal_set_message() {}
}

}
