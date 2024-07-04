<?php

namespace Drupal\workflow_eca\Plugin\ECA\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\EcaPluginBase;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca\Service\ContentEntityTypes;
use Drupal\workflow_eca\Event\TransitionEvent;
use Drupal\workflow_eca\WorkflowEvents;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the ECA workflow events.
 *
 * @EcaEvent(
 *   id = "workflow_eca",
 *   deriver = "Drupal\workflow_eca\Plugin\ECA\Event\WorkflowEventDeriver"
 * )
 */
class WorkflowEvent extends EventBase {

  /**
   * The content entity types service.
   *
   * @var \Drupal\eca\Service\ContentEntityTypes
   */
  protected ContentEntityTypes $entityTypes;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EcaPluginBase {
    /** @var \Drupal\workflow_eca\Plugin\ECA\Event\WorkflowEvent $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setEntityTypes($container->get('eca.service.content_entity_types'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $definitions = [];
    $definitions['transition'] = [
      'label' => 'Workflow ECA: state transition',
      'event_name' => WorkflowEvents::TRANSITION,
      'event_class' => TransitionEvent::class,
      'tags' => Tag::CONTENT | Tag::PERSISTENT | Tag::BEFORE,
    ];
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    if ($this->eventClass() === TransitionEvent::class) {
      $values = [
        'type' => '',
        'previous_state' => '',
        'current_state' => '',
      ];
    }
    else {
      $values = [];
    }
    return $values + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    if ($this->eventClass() === TransitionEvent::class) {
      $form['event_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => ['event-wrapper-container']
        ],
      ];

      $options = $this->getTypesAndBundlesWithWorkflowField();
      $default_type = $this->getDefaultEntityTypeBundle($form_state, $options);
      $form['event_wrapper']['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type (and bundle)'),
        '#options' => $options,
        '#default_value' => $default_type,
        '#weight' => 10,
        '#ajax' => [
          'callback' => [$this, 'workflowStateAjaxCallback'],
          'disable-refocus' => FALSE,
          'event' => 'change',
          'wrapper' => 'event-wrapper-container',
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Updating workflows...'),
          ],
        ],
      ];
      [$entity_type, $bundle] = explode(' ', $default_type);
      $options = $this->getPreviousWorkflowStatesForPlugin($entity_type, $bundle);
      $form['event_wrapper']['previous_state'] = [
        '#type' => 'select',
        '#title' => $this->t('From state'),
        '#description' => $this->t('Optionally restrict to a specific previous state.'),
        '#options' => $options,
        '#default_value' => $this->getDefaultWorkflowState($form_state, 'previous_state', $options),
        '#weight' => 20,
      ];
      $options = $this->getCurrentWorkflowStatesForPlugin($entity_type, $bundle);
      $form['event_wrapper']['current_state'] = [
        '#type' => 'select',
        '#title' => $this->t('To state'),
        '#description' => $this->t('Optionally restrict to a specific new state.'),
        '#options' => $options,
        '#default_value' => $this->getDefaultWorkflowState($form_state, 'current_state', $options),
        '#weight' => 30,
      ];
    }
    return $form;
  }

  /**
   * Return the default type, based on form_state, if available.
   */
  protected function getDefaultEntityTypeBundle(FormStateInterface $form_state, $options): string {
    // If we're in our Ajax callback, use the form input.
    // Form values aren't available yet, since it hasn't been submitted.
    $input = $form_state->getUserInput();
    if (!empty($input)) {
      $type = $input['event']['event_wrapper']['type'];
      return $type;
    }
    // When we're initially building the form, use the configuration, if
    // available, otherwise default to the first option.
    return $this->configuration['type'] ?: array_keys($options)[0];
  }

  /**
   * Ajax callback to render the "From" and "To" state select lists with the appropriate workflow states.
   */
  public function workflowStateAjaxCallback(array $form, FormStateInterface $form_state): array {
    return $form['event']['configuration']['event_wrapper'];
  }

  /**
   * Return the default type, based on form_state, if available.
   */
  protected function getDefaultWorkflowState(FormStateInterface $form_state, $field, $options): string {
    // If we're in our Ajax callback, use the form input.
    // Form values aren't available yet, since it hasn't been submitted.
    $input = $form_state->getUserInput();
    if (!empty($input)) {
      return $input['event']['event_wrapper'][$field];
    }
    return $this->configuration[$field] ?: array_keys($options)[0];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    [$entity_type, $bundle] = explode(' ', $values['event_wrapper']['type']);
    $workflow = $this->getWorkflowByType($entity_type, $bundle);
    foreach (['previous_state', 'current_state'] as $field) {
      $state = $values['event_wrapper'][$field];
      // The "Any" option is the empty string, but it valid.
      if (empty($state)) continue;
      // As long as the workflow state is valid for the bundle in question, we're good.
      if (array_key_exists($state, $workflow->states)) continue;
      $message = $this
        ->t('The %field field must be set to a workflow state for the %bundle bundle of the %type entity type.', [
          '%field' => $form['event_wrapper'][$field]['#title'],
          '%bundle' => $bundle,
          '%type' => $entity_type]);
      // @TODO: Figure out why this isn't highlighting the erroneous form field.
      $form_state->setErrorByName($field, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    if ($this->eventClass() === TransitionEvent::class) {
      $form_values = $form_state->getValues()['event_wrapper'];
      $this->configuration['type'] = $form_values['type'];
      $this->configuration['previous_state'] = $form_values['previous_state'];
      $this->configuration['current_state'] = $form_values['current_state'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    switch ($this->getDerivativeId()) {

      case 'transition':
        $config = $ecaEvent->getConfiguration();
        $type = $config['type'] ?? ContentEntityTypes::ALL;
        if ($type === ContentEntityTypes::ALL) {
          $wildcard = '*::*';
        }
        else {
          [$entityType, $bundle] = array_merge(explode(' ', $type), [ContentEntityTypes::ALL]);
          if ($bundle === ContentEntityTypes::ALL) {
            $wildcard = $entityType . '::*';
          }
          else {
            $wildcard = $entityType . '::' . $bundle;
          }
        }
        $wildcard .= in_array(($config['previous_state'] ?? ''), ['', '*']) ? '::*' : '::' . $config['previous_state'];
        $wildcard .= in_array(($config['current_state'] ?? ''), ['', '*']) ? '::*' : '::' . $config['current_state'];
        return $wildcard;

      default:
        return parent::lazyLoadingWildcard($eca_config_id, $ecaEvent);

    }
  }

  /**
   * Set the content entity types service.
   *
   * @param \Drupal\eca\Service\ContentEntityTypes $entity_types
   *   The content entity types service.
   */
  public function setEntityTypes(ContentEntityTypes $entity_types): void {
    $this->entityTypes = $entity_types;
  }

  /**
   * Return a list of entity types and bundles with workflow fields for the plugin form.
   */
  protected function getTypesAndBundlesWithWorkflowField(): array {
    $types_bundles = [];
    foreach ($this->getAllTypesAndBundles() as $type_bundle => $label) {
      [$type, $bundle] = explode(' ', $type_bundle);
      // Ignore bundles that do not have a workflow field.
      if (empty($this->getWorkflowFieldNames($type, $bundle))) continue;
      $types_bundles[$type_bundle] = $label;
    }

    return $types_bundles;
  }

  /**
   * Return a list of all entity types and bundles.
   */
  protected function getAllTypesAndBundles(): array {
    return $this->entityTypes->getTypesAndBundles(FALSE, FALSE);
  }

  /**
   * Return a list of workflow field names for a given entity type and bundle.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   */
  protected function getWorkflowFieldNames(string $entity_type, string $bundle): array {
    return \workflow_get_workflow_field_names(NULL, $entity_type, $bundle);
  }

  /**
   * Return a list of workflow states for the plugin form.
   */
  protected function getPreviousWorkflowStatesForPlugin(string $entity_type, string $bundle): array {
    $options = [
      '' => $this->t('- Any -'),
    ];
    foreach ($this->getWorkflowStates($entity_type, $bundle) as $sid => $state) {
      $options[$sid] = $state->label();
    }
    return $options;
  }

  /**
   * Return a list of workflow states for the plugin form without "Creation".
   */
  protected function getCurrentWorkflowStatesForPlugin(string $entity_type, string $bundle): array {
    $options = $this->getPreviousWorkflowStatesForPlugin($entity_type, $bundle);
    foreach (Workflow::loadMultiple() as $workflow) {
      $creation_sid = $workflow->getCreationSid();
      if (array_key_exists($creation_sid, $options)) {
        unset($options[$creation_sid]);
      }
    }
    return $options;
  }

  /**
   * Return the workflow states for a given entity type and bundle.
   */
  protected function getWorkflowStates(string $entity_type, string $bundle): array {
    return $this->getWorkflowByType($entity_type, $bundle)->states;
  }

  /**
   * Return the workflow for a given entity type and bundle.
   */
  protected function getWorkflowByType(string $entity_type, string $bundle): Workflow {
    return \workflow_get_workflows_by_type($bundle, $entity_type);
  }

}
