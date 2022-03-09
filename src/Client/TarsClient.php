<?php

declare(strict_types=1);

namespace Imi\Tars\Client;

use Imi\Tars\Client\Connector\Connector;
use Imi\Event\Event;
use Imi\Rpc\Client\IRpcClient;
use Imi\Rpc\Client\IService;
use Imi\Tars\Client\Connector\ConnectorConfig;
use Imi\Tars\Registry\Registry;

/**
 * Tars 客户端.
 */
class TarsClient implements IRpcClient
{
    /**
     * Client.
     */
    protected ?Connector $connector = null;

    /**
     * 配置.
     */
    protected array $options;

    /**
     * Tars Connector配置
     * @var ConnectorConfig|null
     */
    private ?ConnectorConfig $connectorConfig;

    /**
     * Tars 服务发现
     * @var Registry
     */
    private Registry $registry;

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function __construct(array $options)
    {
        // 获取tars配置 不存在则抛异常
        $this->options = $options;
        $this->connectorConfig = new ConnectorConfig($this->options);
        $this->registry = new Registry($this->connectorConfig);
    }

    /**
     * {@inheritDoc}
     */
    public function open(): bool
    {
        $this->connector = new Connector($this->connectorConfig, $this->registry);
        Event::trigger('IMI.RPC.TARS.CLIENT.OPEN', [
            'connector'    => $this->connector,
        ], $this);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        $this->connector = null;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function checkConnected(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance(): Connector
    {
        return $this->connector;
    }

    /**
     * {@inheritDoc}
     */
    public function getService(?string $name = null): IService
    {
        return new TarsService($this, $name);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return ConnectorConfig|null
     */
    public function getConnectorConfig(): ?ConnectorConfig
    {
        return $this->connectorConfig;
    }
}
