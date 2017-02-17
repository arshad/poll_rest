<?php

namespace Drupal\poll_rest\Plugin\rest\resource;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\poll\Entity\Poll;
use Drupal\poll\PollInterface;
use Drupal\poll\PollVoteStorageInterface;
use Drupal\rest\Annotation\RestResource;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use HttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @RestResource(
 *   id = "poll_vote_resource",
 *   label = @Translation("Poll vote"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/poll/{pid}/vote",
 *     "https://www.drupal.org/link-relations/create" = "/api/v1/poll/{id}/vote"
 *   }
 * )
 */
class PollVoteResource extends ResourceBase {

  /**
   * @var \Drupal\poll\PollVoteStorageInterface
   */
  protected $pollVoteStorage;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\poll\PollVoteStorageInterface $pollVoteStorage
   *  The poll storage class.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack used to retrieve the current request.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, PollVoteStorageInterface $pollVoteStorage, AccountProxyInterface $account,RequestStack $requestStack, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->pollVoteStorage = $pollVoteStorage;
    $this->requestStack = $requestStack;
    $this->time = $time;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('poll_vote.storage'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('datetime.time')
    );
  }
  
  /**
   * Response to the POST request.
   *
   * @param $pid
   *  The poll id.
   * @param $data
   *  The $data array.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *
   * @throws \HttpException
   */
  public function post($pid, $data = array()) {
    /** @var PollInterface $poll */
    $poll = Poll::load($pid);

    $options = [];
    $options['chid'] = $data['chid'];
    $options['uid'] = $this->account->id();
    $options['pid'] = $poll->id();
    $options['hostname'] = $this->requestStack->getCurrentRequest()->getClientIp();
    $options['timestamp'] = $this->time->getRequestTime();

    try {
      $url = $poll->toUrl()->toString();
      $this->pollVoteStorage->saveVote($options);
      $response = new ModifiedResourceResponse($poll, 201, ['Location' => $url]);
      return $response;
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    // Allow all for now.
    return [];
  }
}
