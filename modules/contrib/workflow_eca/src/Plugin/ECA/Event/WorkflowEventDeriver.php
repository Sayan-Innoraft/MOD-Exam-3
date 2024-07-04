<?php

namespace Drupal\workflow_eca\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 * Deriver for Workflow ECA event plugins.
 */
class WorkflowEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function definitions(): array {
    return WorkflowEvent::definitions();
  }

}
