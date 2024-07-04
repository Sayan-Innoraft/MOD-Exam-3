<?php

namespace Drupal\workflow_eca\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase as EcaBaseSubscriber;
use Drupal\workflow_eca\Plugin\ECA\Event\WorkflowEvent;

/**
 * Workflow ECA event subscriber.
 */
class WorkflowEca extends EcaBaseSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (WorkflowEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
