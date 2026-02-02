<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * NonceExpiredError
 * 交易 nonce 已被使用时抛出
 *
 * 参考 ethers.js v6 的 NonceExpiredError
 * 错误代码: NONCE_EXPIRED
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/#NonceExpiredError
 */
class NonceExpiredError extends EthersException
{
    public const CODE = 'NONCE_EXPIRED';

    /**
     * 触发异常的交易信息
     *
     * @var array<string, mixed>|null
     */
    public readonly ?array $transaction;

    /**
     * 构造函数
     *
     * @param  string  $message  错误信息
     * @param  array<string, mixed>|null  $transaction  交易信息
     * @param  array<string, mixed>  $info  附加信息
     */
    public function __construct(
        string $message = 'Nonce 已过期',
        ?array $transaction = null,
        array $info = []
    ) {
        $this->transaction = $transaction;

        parent::__construct($message, self::CODE, 'Nonce 已过期', array_merge($info, [
            'transaction' => $transaction,
        ]));
    }

    /**
     * 从 RPC 错误创建
     */
    public static function fromRpcError(
        int $rpcCode,
        string $rpcMessage,
        ?array $transaction = null
    ): self {
        return new self(
            "RPC Error [{$rpcCode}]: {$rpcMessage}",
            $transaction,
            ['rpcCode' => $rpcCode]
        );
    }
}
