<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * UnsupportedOperationError
 * 尝试不支持的操作时抛出
 *
 * 参考 ethers.js v6 的 UnsupportedOperationError
 * 错误代码: UNSUPPORTED_OPERATION
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/#UnsupportedOperationError
 */
class UnsupportedOperationError extends EthersException
{
    public const CODE = 'UNSUPPORTED_OPERATION';

    /**
     * 尝试的操作
     */
    public readonly string $operation;

    /**
     * 构造函数
     *
     * @param  string  $message  错误信息
     * @param  string  $operation  尝试的操作
     * @param  array<string, mixed>  $info  附加信息
     */
    public function __construct(
        string $message = '不支持的操作',
        string $operation = 'unknown',
        array $info = []
    ) {
        $this->operation = $operation;

        parent::__construct($message, self::CODE, '不支持的操作', array_merge($info, [
            'operation' => $operation,
        ]));
    }
}
