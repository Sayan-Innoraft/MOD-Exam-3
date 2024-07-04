<?php

namespace Drupal\workflow_eca\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityEventInterface;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Service\ContentEntityTypes;
use Drupal\eca\Token\DataProviderInterface;

/**
 * Dispatched when a workflow changed.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class TransitionEvent extends Event implements ConditionalApplianceInterface, EntityEventInterface, DataProviderInterface {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $entity;

  /**
   * From state (if given).
   *
   * @var string|null
   */
  protected ?string $previousState;

  /**
   * To state (if given).
   *
   * @var string
   */
  protected string $currentState;

  /**
   * The entity types service.
   *
   * @var \Drupal\eca\Service\ContentEntityTypes
   */
  protected ContentEntityTypes $entityTypes;

  /**
   * Constructs a new TransitionEvent object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string|null $previous_state
   *   (optional) From state.
   * @param string $current_state
   *   New state.
   * @param \Drupal\eca\Service\ContentEntityTypes $entity_types
   *   The entity types service.
   */
  public function __construct(ContentEntityInterface $entity, ?string $previous_state, string $current_state, ContentEntityTypes $entity_types) {
    $this->entity = $entity;
    $this->previousState = $previous_state;
    $this->currentState = $current_state;
    $this->entityTypes = $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the from state (if available).
   *
   * @return string|null
   *   The from state, or NULL if not available.
   */
  public function getPreviousState(): ?string {
    return $this->previousState;
  }

  /**
   * Get the new state.
   *
   * @return string
   *   The new state.
   */
  public function getCurrentState(): string {
    return $this->currentState;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    switch ($key) {

      case 'previous_state':
        if (isset($this->previousState)) {
          return DataTransferObject::create($this->previousState);
        }
        return NULL;

      case 'current_state':
        return DataTransferObject::create($this->currentState);

      default:
        return NULL;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_entity_type_id, $w_entity_bundle, $w_previous_state, $w_current_state] = explode('::', $wildcard);
    $entity = $this->getEntity();
    if (($w_entity_type_id !== '*') && ($w_entity_type_id !== $entity->getEntityTypeId())) {
      return FALSE;
    }
    if (($w_entity_bundle !== '*') && ($w_entity_bundle !== $entity->bundle())) {
      return FALSE;
    }
    if (($w_previous_state !== '*') && ($w_previous_state !== $this->previousState)) {
      return FALSE;
    }
    if (($w_current_state !== '*') && ($w_current_state !== $this->currentState)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $entity = $this->getEntity();
    if (!$this->entityTypes->bundleFieldApplies($entity, $arguments['type'])) {
      return FALSE;
    }
    $previous_state = $arguments['previous_state'] ?? '';
    if (!in_array($previous_state, ['', '*'], TRUE) && ($previous_state !== $this->previousState)) {
      return FALSE;
    }
    $current_state = $arguments['current_state'] ?? '';
    if (!in_array($current_state, ['', '*'], TRUE) && ($current_state !== $this->currentState)) {
      return FALSE;
    }
    return TRUE;
  }

}
