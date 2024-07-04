<?php

namespace Drupal\workflow_eca;

/**
 * Defines events provided by the Workflow ECA module.
 */
final class WorkflowEvents {

  /**
   * Dispatches when a moderation state changed.
   *
   * @Event
   *
   * @var string
   */
  public const TRANSITION = 'workflow_eca.transition';

}
