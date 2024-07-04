<?php

/**
 * @file
 * Provides custom tokens for Workflow ECA.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Drupal\workflow\Entity\WorkflowTransition;

/**
* Implements hook_token_info().
*/
function workflow_eca_token_info() {
  $type = [
    'name' => t('Custom Workflow ECA Token'),
      'description' => t('Custom tokens for Workflow ECA.'),
      'needs-data' => 'node',
  ];
  $pmt['workflow-comment'] = [
    'name' => t("Workflow comment"),
    'description' => t('Workflow comment'),
  ];
  $pmt['workflow-date'] = [
    'name' => t("Workflow date"),
    'description' => t("Date of last workflow transition"),
  ];
  return [
    'tokens' => ['node' => $pmt],
  ];
}

/**
 * Implements hook_tokens().
 *
 * Getting workflow comment of per node per save.
 */
function workflow_eca_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type === 'node' && !empty($data['node'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'workflow-comment':
          $replacements[$original] = _workflow_eca_workflow_comment($data['node']);
        break;
        case 'workflow-date':
          $replacements[$original] = _workflow_eca_workflow_date($data['node']);
      }
    }
  }
  return $replacements;
}

/**
 * Helper function to get workflow comment.
 *
 * @return string
 *   Returns the comment string.
 */
function _workflow_eca_workflow_comment(NodeInterface $node) {
  $workflow_field = _workflow_eca_get_node_workflow_field($node);
  if (!$node->hasField($workflow_field)) return '';
  if ($node->get($workflow_field)->isEmpty()) return '';
  $workflow_state = $node->get($workflow_field)->getValue();
  if (!isset($workflow_state[0]['comment'])) return '';
  return $workflow_state[0]['comment'];
}

/**
 * Helper function to get workflow comment.
 *
 * @return string
 *   Returns the comment string.
 */
function _workflow_eca_workflow_date(NodeInterface $node) {
  # Guard clauses: check this node entity has workflow tokens to render.
  $workflow_field = _workflow_eca_get_node_workflow_field($node);
  if (!$node->hasField($workflow_field)) return '';
  if ($node->get($workflow_field)->isEmpty()) return '';

  # At this point, we have a node with a known workflow. Look up its latest transition
  $latest_transition = WorkflowTransition::loadByProperties('node', $node->id(), [], $workflow_field, '', 'DESC');
  if (!$latest_transition) return '';

  # If we have a valid WorkflowTransition entity, look up its timestamp, and format that in a storage format.
  $timestamp = $latest_transition->getTimestamp();
  $date = \Drupal::service('date.formatter')
    ->format($timestamp, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

  return $date;
}

/**
 * Return the workflow field(s) for a given node.
 */
function _workflow_eca_get_node_workflow_fields(NodeInterface $node) {
  $entity_type = $node->getEntityTypeId();
  $bundle = $node->bundle();
  return workflow_get_workflow_field_names(NULL, $entity_type, $bundle);
}

/**
 * Return the workflow field for a given node.
 */
function _workflow_eca_get_node_workflow_field(NodeInterface $node) {
  $fields = _workflow_eca_get_node_workflow_fields($node);
  if (empty($fields))  return;
  $field = reset($fields);
  return $field;
}
