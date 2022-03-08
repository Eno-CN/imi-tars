<?php

declare(strict_types=1);

namespace Imi\Tars\Error;

use Imi\Bean\Annotation\Bean;
use Imi\RequestContext;

/**
 * @Bean("TarsErrorHandler")
 */
class ErrorHandler
{
    protected string $handler = TarsErrorHandler::class;

    /**
     * {@inheritDoc}
     */
    public function handle(\Throwable $throwable): bool
    {
        return RequestContext::getServerBean($this->handler)->handle($throwable);
    }
}
