<?php

namespace LTO\LiveContracts\Tester;

use JsonSerializable;
use LTO\Account;
use LTO\EventChain;
use BadMethodCallException;
use RuntimeException;

/**
 * Representation of a Live Contracts process
 */
class Process implements JsonSerializable
{
    /**
     * @var string
     */
    public $schema = 'https://specs.livecontracts.io/v0.2.0/process/schema.json#';

    /**
     * @var string
     */
    public $id;

    /**
     * @var array
     */
    public $scenario;

    /**
     * @var Account[]
     */
    public $actors = [];

    /**
     * @var EventChain
     */
    protected $chain;

    /**
     * @var array
     */
    protected $projection;

    /**
     * The hash of last event of the chain when the projection was fetched
     * @var string
     */
    protected $eventAtProjection;


    /**
     * Process constructor.
     *
     * @param EventChain  $chain
     * @param string|null $ref
     */
    public function __construct(EventChain $chain, ?string $ref = null)
    {
        $this->chain = $chain;
        $this->id = $chain->createResourceId($ref);
    }


    /**
     * Load a scenario
     *
     * @param string $name
     * @param string $path
     * @return void
     * @throws BadMethodCallException if the scenario is already set
     * @throws RuntimeException if the scenario can't be loaded
     */
    public function loadScenario(string $name, string $path): void
    {
        if (isset($this->scenario)) {
            throw new BadMethodCallException("Scenario already set");
        }

        $this->id = $this->chain->createResourceId('process:' . $name);

        $this->scenario = ScenarioLoader::getInstance()->load($name, $path);
        $this->scenario->id = $this->chain->createResourceId('scenario:' . $path);
    }

    /**
     * Create a response of an action
     *
     * @param string $actionKey
     * @param string $key
     * @param mixed  $data
     * @return array
     * @throws BadMethodCallException if the scenario is not set
     */
    public function createResponse(string $actionKey, ?string $key, $data = null): array
    {
        if (!isset($key)) {
            $key = $this->scenario->actions->$actionKey->default_response ?? 'ok';
        }

        $response = [
            '$schema' => 'https://specs.livecontracts.io/v0.2.0/response/schema.json#',
            'process' => $this->id,
            'action' => [
                'key' => $actionKey
            ],
            'key' => $key,
            'data' => $data
        ];

        return $response;
    }


    /**
     * Get the projection of the process
     *
     * @return array|null
     */
    public function getProjection(): ?array
    {
        return $this->eventAtProjection === $this->chain->getLatestHash() ? $this->projection : null;
    }

    /**
     * Set the projection of the process
     *
     * @param array $projection
     */
    public function setProjection(array $projection)
    {
        $this->projection = $projection;
        $this->eventAtProjection === $this->chain->getLatestHash();
    }

    /**
     * Prepare JSON serialization.
     *
     * @return \stdClass
     */
    public function jsonSerialize(): \stdClass
    {
        return (object)[
            '$schema' => $this->schema,
            'id' => $this->id,
            'scenario' => $this->scenario,
            'actors' => $this->actors
        ];
    }
}
