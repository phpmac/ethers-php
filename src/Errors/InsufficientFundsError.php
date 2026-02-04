<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * InsufficientFundsError
 * 账户余额不足以支付交易费用时抛出
 *
 * 参考 ethers.js v6 的 InsufficientFundsError
 * 错误代码: INSUFFICIENT_FUNDS
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/#InsufficientFundsError
 */
class InsufficientFundsError extends EthersException
{
    public const CODE = 'INSUFFICIENT_FUNDS';

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
        string $message = '余额不足',
        ?array $transaction = null,
        array $info = []
    ) {
        $this->transaction = $transaction;

        parent::__construct($message, self::CODE, '余额不足', array_merge($info, [
            'transaction' => $transaction,
        ]));
    }

    /**
     * 从 RPC 错误创建
     *
     * @param  int  $rpcCode  RPC 错误代码
     * @param  string  $rpcMessage  RPC 错误信息
     * @param  array<string, mixed>|null  $transaction  交易信息
     */
    public static function fromRpcError(
        int $rpcCode,
        string $rpcMessage,
        ?array $transaction = null
    ): self {
        return new self(
            'insufficient funds',
            $transaction,
            ['rpcCode' => $rpcCode, 'rpcMessage' => $rpcMessage]
        );
    }
}
