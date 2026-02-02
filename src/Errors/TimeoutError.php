<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * TimeoutError
 * 操作超时时抛出
 *
 * 参考 ethers.js v6 的 TimeoutError
 * 错误代码: TIMEOUT
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/#TimeoutError
 */
class TimeoutError extends EthersException
{
    public const CODE = 'TIMEOUT';

    /**
     * 尝试的操作
     */
    public readonly string $operation;

    /**
     * 超时原因
     */
    public readonly string $reason;

    /**
     * 构造函数
     *
     * @param  string  $message  错误信息
     * @param  string  $operation  尝试的操作
     * @param  string  $reason  超时原因
     * @param  array<string, mixed>  $info  附加信息
     */
    public function __construct(
        string $message = '操作超时',
        string $operation = 'unknown',
        string $reason = 'timeout',
        array $info = []
    ) {
        $this->operation = $operation;
        $this->reason = $reason;

        parent::__construct($message, self::CODE, '操作超时', array_merge($info, [
            'operation' => $operation,
            'reason' => $reason,
        ]));
    }
}
