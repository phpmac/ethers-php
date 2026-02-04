<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * UnknownError
 * 未知错误时抛出 (兜底异常)
 *
 * 参考 ethers.js v6 的 UnknownError
 * 错误代码: UNKNOWN_ERROR
 */
class UnknownError extends EthersException
{
    public const CODE = 'UNKNOWN_ERROR';

    /**
     * 原始错误信息
     */
    public readonly ?string $originalError;

    public function __construct(
        string $message = '未知错误',
        ?string $originalError = null,
        array $info = []
    ) {
        $this->originalError = $originalError;

        parent::__construct(
            $message,
            self::CODE,
            '未知错误',
            array_merge($info, [
                'originalError' => $originalError,
            ])
        );
    }

    /**
     * 从异常创建
     */
    public static function fromException(\Throwable $e): self
    {
        return new self(
            $e->getMessage(),
            $e::class . ': ' . $e->getMessage(),
            [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        );
    }
}
