<?php

namespace Drupal\workflow_eca;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\BaseHookHandler;
use Drupal\eca\Service\ContentEntityTypes;
use Drupal\workflow\Entity\WorkflowManagerInterface;
use Drupal\workflow\Entity\Workflow;

/**
 * The handler for hook implementations within the eca_base.module file.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class HookHandler extends BaseHookHandler {

  /**
   * The content entity types service.
   *
   * @var \Drupal\eca\Service\ContentEntityTypes
   */
  protected ContentEntityTypes $contentEntityTypes;

  /**
   * The workflow manager service.
   *
   * @var \Drupal\workflow\Entity\WorkflowManagerInterface
   */
  protected WorkflowManagerInterface $workflowManager;

  /**
   * Set the content entity types service.
   *
   * @param \Drupal\eca\Service\ContentEntityTypes $content_entity_types
   *   The content entity types service.
   */
  public function setContentEntityTypes(ContentEntityTypes $content_entity_types): void {
    $this->contentEntityTypes = $content_entity_types;
  }

  /**
   * Set the workflow manager service.
   *
   * @param \Drupal\workflow\Entity\WorkflowManagerInterface
   *   The workflow manager service.
   */
  public function setWorkflowManager(WorkflowManagerInterface $workflowManager): void {
    $this->workflowManager = $workflowManager;
  }

  /**
   * Triggers moderation state transition when the entity is a moderated one.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   */
  public function transition(ContentEntityInterface $entity, bool $entity_is_new = FALSE): void {
    // The hook that calls this method fires on all entity insertions,
    // including many that don't have a workflow associated.
    $field_name = \workflow_get_field_name($entity);
    if (!$field_name) {
      return;
    }

    if ($entity_is_new) {
      // During hook_entity_insert() the previous and current states will
      // match. So we override it here, to allow for triggering events on
      // entity creation.
      $previous_state = $this->getCreationStateId($entity, $field_name);
    } else {
      $previous_state = $this->workflowManager->getPreviousStateId($entity, $field_name);
    }
    $current_state = $this->workflowManager->getCurrentStateId($entity, $field_name);

    if ($previous_state !== $current_state) {
      $this->triggerEvent->dispatchFromPlugin('workflow_eca:transition', $entity, $previous_state, $current_state, $this->contentEntityTypes);
    }
  }

  /**
   * Gets the creation sid for a given $entity and $field_name.
   *
   * Copied from Workflow::getCreationStateId()
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   *
   * @return string
   *   The ID of the creation State for the Workflow of the field.
   */
  protected function getCreationStateId(EntityInterface $entity, $field_name) {
    $sid = '';

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_config */
    $field_config = $entity->get($field_name)->getFieldDefinition();
    $field_storage = $field_config->getFieldStorageDefinition();
    $wid = $field_storage->getSetting('workflow_type');
    if ($wid) {
      /** @var \Drupal\workflow\Entity\Workflow $workflow */
      $workflow = Workflow::load($wid);
      if (!$workflow) {
        \Drupal::messenger()->addError(t('Workflow %wid cannot be loaded. Contact your system administrator.', ['%wid' => $wid]));
      }
      else {
        $sid = $workflow->getCreationSid();
      }
    }

    return $sid;
  }

}
