<?php

namespace Drupal\workflow_eca\Plugin\ECA\Condition;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;

/**
 * Plugin implementation of the ECA condition for checking if entity has field.
 *
 * @EcaCondition(
 *   id = "workflow_eca_entity_has_field",
 *   label = @Translation("Entity: has field"),
 *   description = @Translation("Evaluates whether the field exists on the entity."),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity", label = @Translation("Entity"))
 *   }
 * )
 */
class EntityHasField extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $entity = $this->getValueFromContext('entity');
    if (!($entity instanceof FieldableEntityInterface)) {
      return FALSE;
    }
    $field_name = trim((string) $this->tokenServices->replaceClear($this->configuration['field_name'] ?? ''));
    if (($field_name === '') || !($entity->hasField($field_name))) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'field_name' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field machine name'),
      '#description' => $this->t('The machine name of the field to check.'),
      '#default_value' => $this->configuration['field_name'] ?? '',
      '#required' => TRUE,
      '#weight' => -20,
      '#placeholder' => $this->t('field_test'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    parent::submitConfigurationForm($form, $form_state);
  }

}
