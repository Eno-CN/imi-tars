<?php

declare(strict_types=1);

namespace Imi\Server\Tars;

use Imi\App;
use Imi\Event\EventParam;
use Imi\Rpc\BaseRpcTcpServer;
use Imi\Server\Protocol;
use Imi\Server\ServerManager;
use Imi\Swoole\Server\Contract\ISwooleServer;
use Imi\Swoole\Server\Event\Param\CloseEventParam;
use Imi\Swoole\Server\Event\Param\ConnectEventParam;
use Imi\Swoole\Server\Event\Param\ReceiveEventParam;
use Imi\Swoole\Util\Co\ChannelContainer;
use Imi\Tars\Route\Annotation\TarsAction;
use Imi\Tars\Route\Annotation\TarsRoute;
use Imi\Tars\Route\Annotation\TarsServant;

class Server extends BaseRpcTcpServer
{
    private bool $isHookTarsOn = false;

    /**
     * {@inheritDoc}
     */
    protected function createServer(): void
    {
        $config = $this->getServerInitConfig();
        $this->swooleServer = new \Swoole\Server($config['host'], $config['port'], $config['mode'], $config['sockType']);
    }

    /**
     * {@inheritDoc}
     */
    protected function createSubServer(): void
    {
        $config = $this->getServerInitConfig();
        /** @var ISwooleServer $server */
        $server = ServerManager::getServer('main', ISwooleServer::class);
        $this->swooleServer = $server->getSwooleServer();
        $this->swoolePort = $this->swooleServer->addListener($config['host'], $config['port'], $config['sockType']);
        $configs = &$this->config['configs'];
        foreach (static::SWOOLE_PROTOCOLS as $protocol)
        {
            $configs[$protocol] ??= false;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getServerInitConfig(): array
    {
        return [
            'host'      => $this->config['host'] ?? '0.0.0.0',
            'port'      => $this->config['port'] ?? 8080,
            'sockType'  => isset($this->config['sockType']) ? (\SWOOLE_SOCK_TCP | $this->config['sockType']) : \SWOOLE_SOCK_TCP,
            'mode'      => $this->config['mode'] ?? \SWOOLE_BASE,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function on($name, $callback, int $priority = 0): void
    {
        if ($this->isHookTarsOn)
        {
            parent::on($name, function (EventParam $e) use ($callback) {
                $data = $e->getData();
                $data['server'] = $this->swooleServer;
                $callback(...array_values($data));
            }, $priority);
        }
        else
        {
            parent::on($name, $callback, $priority);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function __bindEvents(): void
    {
        $events = $this->config['events'] ?? null;
        if ($event = ($events['connect'] ?? true))
        {
            $this->swoolePort->on('connect', \is_callable($event) ? $event : function (\Swoole\Server $server, int $fd, int $reactorId) {
                try
                {
                    $channelId = 'connection:' . $fd;
                    $channel = ChannelContainer::getChannel($channelId);
                    $this->trigger('connect', [
                        'server'          => $this,
                        'clientId'        => $fd,
                        'reactorId'       => $reactorId,
                    ], $this, ConnectEventParam::class);
                }
                catch (\Throwable $ex)
                {
                    // @phpstan-ignore-next-line
                    App::getBean('ErrorLog')->onException($ex);
                }
                finally
                {
                    if (isset($channel, $channelId))
                    {
                        while (($channel->stats()['consumer_num'] ?? 0) > 0)
                        {
                            $channel->push(1);
                        }
                        ChannelContainer::removeChannel($channelId);
                    }
                }
            });
        }
        else
        {
            $this->swoolePort->on('connect', static function () {
            });
        }

        if ($event = ($events['receive'] ?? true))
        {
            $this->swoolePort->on('receive', \is_callable($event) ? $event : function (\Swoole\Server $server, int $fd, int $reactorId, string $data) {
                try
                {
                    $channelId = 'connection:' . $fd;
                    if (ChannelContainer::hasChannel($channelId))
                    {
                        ChannelContainer::pop($channelId);
                    }
                    $this->trigger('receive', [
                        'server'          => $this,
                        'clientId'        => $fd,
                        'reactorId'       => $reactorId,
                        'data'            => $data,
                    ], $this, ReceiveEventParam::class);
                }
                catch (\Throwable $ex)
                {
                    // @phpstan-ignore-next-line
                    if (true !== $this->getBean('TarsErrorHandler')->handle($ex)) {
                        // @phpstan-ignore-next-line
                        App::getBean('ErrorLog')->onException($ex);
                    }
                }
            });
        }
        else
        {
            $this->swoolePort->on('receive', static function () {
            });
        }

        if ($event = ($events['close'] ?? true))
        {
            $this->swoolePort->on('close', \is_callable($event) ? $event : function (\Swoole\Server $server, int $fd, int $reactorId) {
                try
                {
                    $this->trigger('close', [
                        'server'          => $this,
                        'clientId'        => $fd,
                        'reactorId'       => $reactorId,
                    ], $this, CloseEventParam::class);
                }
                catch (\Throwable $ex)
                {
                    // @phpstan-ignore-next-line
                    App::getBean('ErrorLog')->onException($ex);
                }
            });
        }
        else
        {
            $this->swoolePort->on('close', static function () {
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRpcType(): string
    {
        return 'Tars';
    }

    /**
     * {@inheritDoc}
     */
    public function getControllerAnnotation(): string
    {
        return TarsServant::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getActionAnnotation(): string
    {
        return TarsAction::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteAnnotation(): string
    {
        return TarsRoute::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteClass(): string
    {
        return 'TarsRoute';
    }

    /**
     * {@inheritDoc}
     */
    public function isLongConnection(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isSSL(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocol(): string
    {
        return Protocol::TCP;
    }
}
