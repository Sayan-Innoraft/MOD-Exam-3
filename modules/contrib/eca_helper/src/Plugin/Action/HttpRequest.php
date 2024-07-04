<?php

namespace Drupal\eca_helper\Plugin\Action;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Service\YamlParser;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Simple http request action for ECA.
 *
 * @Action(
 *   id = "eca_helper_http_request",
 *   label = @Translation("ECA Helper: Http Request"),
 *   description = @Translation("Add Http request for ECA"),
 * )
 */
class HttpRequest extends ConfigurableActionBase {

  /**
   * The YAML parser.
   */
  protected YamlParser $yamlParser;

  /**
   * The Http Client.
   */
  protected ClientInterface $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setYamlParser($container->get('eca.service.yaml_parser'));
    $instance->setHttpClient($container->get('http_client'));
    return $instance;
  }

  /**
   * Set the YAML parser.
   *
   * @param \Drupal\eca\Service\YamlParser $yaml_parser
   *   The YAML parser.
   */
  public function setYamlParser(YamlParser $yaml_parser): void {
    $this->yamlParser = $yaml_parser;
  }

  /**
   * Set the http client.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The http client.
   */
  public function setHttpClient(ClientInterface $client): void {
    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'method' => 'get',
      'url' => '',
      'data' => '',
      'data_serialization' => 'raw',
      'query_parameters' => '',
      'headers' => '',
      'cookies' => '',
      'output' => 'json',
      'token_name' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#default_value' => $this->configuration['method'],
      '#options' => [
        'get' => $this->t('GET'),
        'post' => $this->t('POST'),
        'patch' => $this->t('PATCH'),
        'put' => $this->t('PUT'),
        'delete' => $this->t('DELETE'),
      ],
    ];

    $form['url'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->configuration['url'],
    ];

    $form['data'] = [
      '#type' => 'textarea',
      '#required' => FALSE,
      '#title' => $this->t('Data'),
      '#default_value' => $this->configuration['data'],
    ];

    $form['data_serialization'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Serialization'),
      '#default_value' => $this->configuration['data_serialization'],
      '#options' => [
        'raw' => $this->t('Raw'),
        'json' => $this->t('JSON'),
        'url_encode' => $this->t('URL Encode'),
      ],
    ];

    $form['query_parameters'] = [
      '#type' => 'textarea',
      '#required' => FALSE,
      '#title' => $this->t('Query Parameters'),
      '#description' => $this->t('YAML format, for example mykey: myvalue. When using tokens and YAML altogether, make sure that tokens are wrapped as a string. Example: title: "[node:title]"'),
      '#default_value' => $this->configuration['query_parameters'],
    ];

    $form['headers'] = [
      '#type' => 'textarea',
      '#required' => FALSE,
      '#title' => $this->t('Headers'),
      '#description' => $this->t('Headers with YAML format.'),
      '#default_value' => $this->configuration['headers'],
    ];

    $form['cookies'] = [
      '#type' => 'textarea',
      '#required' => FALSE,
      '#title' => $this->t('Cookies'),
      '#description' => $this->t('Cookies with YAML format.'),
      '#default_value' => $this->configuration['cookies'],
    ];

    $form['output'] = [
      '#type' => 'select',
      '#title' => $this->t('Send to output'),
      '#default_value' => $this->configuration['output'],
      '#options' => [
        'json' => $this->t('Response json'),
        'body' => $this->t('Response body'),
        'header' => $this->t('Headers only'),
        'status' => $this->t('Status code'),
      ],
    ];

    $form['token_name'] = [
      '#required' => TRUE,
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#description' => $this->t('The field value will be loaded into this specified token.'),
      '#eca_token_reference' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['method'] = $form_state->getValue('method');
    $this->configuration['url'] = $form_state->getValue('url');
    $this->configuration['data_serialization'] = $form_state->getValue('data_serialization');
    $this->configuration['query_parameters'] = $form_state->getValue('query_parameters');
    $this->configuration['headers'] = $form_state->getValue('headers');
    $this->configuration['cookies'] = $form_state->getValue('cookies');
    $this->configuration['output'] = $form_state->getValue('output');
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $tokenService = $this->tokenService;
    $method = $this->configuration['method'];
    $url = $this->configuration['url'];
    $data = $this->configuration['data'];
    if (mb_strlen($data) > 0) {
      $data = $tokenService->getOrReplace($data);
    }

    $data_serialization = $this->configuration['data_serialization'];

    $query_parameters = $this->configuration['query_parameters'];
    try {
      $query_parameters = $this->yamlParser->parse($query_parameters);
    }
    catch (ParseException $e) {
      \Drupal::logger('eca')->error('Tried parsing query parameters as YAML format, but parsing failed.');
      return;
    }

    $headers = $this->configuration['headers'];
    try {
      $headers = $this->yamlParser->parse($headers);
    }
    catch (ParseException $e) {
      \Drupal::logger('eca')->error('Tried parsing headers as YAML format, but parsing failed.');
      return;
    }

    $cookies = $this->configuration['cookies'];
    try {
      $cookies = $this->yamlParser->parse($cookies);
    }
    catch (ParseException $e) {
      \Drupal::logger('eca')->error('Tried parsing cookies as YAML format, but parsing failed.');
      return;
    }

    $output = $this->configuration['output'];
    $options = [];
    if (!empty($headers)) {
      $options['headers'] = $headers;
    }
    if (!empty($cookies)) {
      $parsed = parse_url($url);
      $jar = CookieJar::fromArray(
        $cookies,
        $parsed['host']
      );
      $options['cookies'] = $jar;
    }

    if (!empty($data)) {
      if ($data_serialization === 'raw') {
        $options['body'] = $data;
      }
      if ($data_serialization === 'json') {
        $options['json'] = is_string($data) ? Json::decode($data) : $data;
      }
      if ($data_serialization === 'url_encode') {
        $options['form_params'] = is_string($data) ? Json::decode($data) : $data;
      }
    }

    if (!empty($query_parameters) && is_array($query_parameters)) {
      $options['query'] = $query_parameters;
    }

    try {
      $response = $this->httpClient->request($method, $url, $options);
      if (!empty($this->configuration['token_name'])) {
        $result = '';
        if ($output === 'body') {
          $result = $response->getBody()->getContents();
        }
        if ($output === 'json') {
          $result = Json::decode($response->getBody()->getContents());
        }
        if ($output === 'header') {
          $result = $response->getHeaders();
        }
        if ($output === 'status') {
          $result = $response->getStatusCode();
        }

        $tokenService->addTokenData($this->configuration['token_name'], $result);
        if ($output !== 'status') {
          $tokenService->addTokenData($this->configuration['token_name'] . '__status_code', $response->getStatusCode());
        }
      }
    }
    catch (GuzzleException $e) {
      \Drupal::logger('eca')->error($e->getMessage());
    }

  }

}
