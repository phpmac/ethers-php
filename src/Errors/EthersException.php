<?php

declare(strict_types=1);

namespace Ethers\Errors;

use Exception;

/**
 * EthersException
 * 基础异常类, 所有 ethers 异常都继承自此类
 *
 * 参考 ethers.js v6 的 EthersError
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/
 */
class EthersException extends Exception
{
    /**
     * 错误代码
     * 参考 ethers.js v6 的 ErrorCode
     */
    public $code;

    /**
     * 简短错误信息
     */
    public string $shortMessage;

    /**
     * 附加信息
     *
     * @var array<string, mixed>
     */
    public array $info;

    /**
     * 构造函数
     *
     * @param  string  $message  完整错误信息
     * @param  string  $code  错误代码
     * @param  string  $shortMessage  简短错误信息
     * @param  array<string, mixed>  $info  附加信息
     * @param  Exception|null  $previous  前一个异常
     */
    public function __construct(
        string $message,
        string $code = 'UNKNOWN_ERROR',
        string $shortMessage = '',
        array $info = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->code = $code;
        $this->shortMessage = $shortMessage ?: $message;
        $this->info = $info;
    }

    /**
     * 检查是否为指定错误代码
     */
    public function isError(string $code): bool
    {
        return $this->code === $code;
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->getMessage(),
            'shortMessage' => $this->shortMessage,
            'info' => $this->info,
        ];
    }
}
