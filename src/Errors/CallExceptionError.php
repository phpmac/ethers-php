<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * CallExceptionError
 * 合约调用或交易执行失败时抛出
 *
 * 参考 ethers.js v6 的 CallExceptionError
 * 错误代码: CALL_EXCEPTION
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/#CallExceptionError
 */
class CallExceptionError extends EthersException
{
    public const CODE = 'CALL_EXCEPTION';

    /**
     * 触发异常的操作
     * call | estimateGas | getTransactionResult | sendTransaction | unknown
     */
    public readonly string $action;

    /**
     * revert 返回的数据
     */
    public readonly ?string $data;

    /**
     * 人类可读的 revert 原因
     */
    public readonly ?string $reason;

    /**
     * 触发异常的交易信息
     *
     * @var array{from?: string, to: string|null, data: string}|null
     */
    public readonly ?array $transaction;

    /**
     * 构造函数
     *
     * @param  string  $message  错误信息
     * @param  string  $action  触发操作 (call, estimateGas, sendTransaction 等)
     * @param  string|null  $data  revert 数据
     * @param  string|null  $reason  revert 原因
     * @param  array|null  $transaction  交易信息
     * @param  array<string, mixed>  $info  附加信息
     */
    public function __construct(
        string $message,
        string $action = 'unknown',
        ?string $data = null,
        ?string $reason = null,
        ?array $transaction = null,
        array $info = []
    ) {
        $this->action = $action;
        $this->data = $data;
        $this->reason = $reason;
        $this->transaction = $transaction;

        $shortMessage = $reason ?? '交易执行失败';

        parent::__construct($message, self::CODE, $shortMessage, array_merge($info, [
            'action' => $action,
            'data' => $data,
            'reason' => $reason,
            'transaction' => $transaction,
        ]));
    }

    /**
     * 从 RPC 错误创建
     *
     * @param  int  $rpcCode  RPC 错误代码
     * @param  string  $rpcMessage  RPC 错误信息
     * @param  string|null  $data  revert 数据
     * @param  string  $action  触发操作
     * @param  array|null  $transaction  交易信息
     */
    public static function fromRpcError(
        int $rpcCode,
        string $rpcMessage,
        ?string $data = null,
        string $action = 'unknown',
        ?array $transaction = null
    ): self {
        $reason = self::parseRevertReason($rpcMessage, $data);

        return new self(
            "RPC Error [{$rpcCode}]: {$rpcMessage}",
            $action,
            $data,
            $reason,
            $transaction,
            ['rpcCode' => $rpcCode]
        );
    }

    /**
     * 解析 revert 原因
     */
    private static function parseRevertReason(string $message, ?string $data): ?string
    {
        // 尝试从错误信息中提取 revert 原因
        if (preg_match('/execution reverted:?\s*(.+)/i', $message, $matches)) {
            return trim($matches[1]);
        }

        // 尝试从 data 解析 Error(string) 格式
        if ($data !== null && strlen($data) > 10) {
            $selector = substr($data, 0, 10);
            // Error(string) selector: 0x08c379a0
            if ($selector === '0x08c379a0' && strlen($data) >= 138) {
                // 跳过 selector(4) + offset(32) + length(32) = 68 bytes = 136 hex chars + 0x
                $stringData = substr($data, 138);
                // 读取长度
                $length = hexdec(substr($data, 74, 64));
                if ($length > 0 && $length <= 1000) {
                    $hex = substr($stringData, 0, $length * 2);
                    $reason = hex2bin($hex);
                    if ($reason !== false) {
                        return $reason;
                    }
                }
            }
        }

        return null;
    }
}
