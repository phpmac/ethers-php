<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * BadDataError
 * 数据格式错误时抛出
 *
 * 参考 ethers.js v6 的 BadDataError
 * 错误代码: BAD_DATA
 *
 * 场景:
 * - ABI 编码/解码失败
 * - 返回数据格式不正确
 * - Hex 字符串格式错误
 */
class BadDataError extends EthersException
{
    public const CODE = 'BAD_DATA';

    /**
     * 错误数据
     */
    public readonly mixed $data;

    /**
     * 期望的数据格式
     */
    public readonly ?string $expected;

    public function __construct(
        string $message,
        mixed $data = null,
        ?string $expected = null,
        array $info = []
    ) {
        $this->data = $data;
        $this->expected = $expected;

        parent::__construct(
            $message,
            self::CODE,
            '数据格式错误',
            array_merge($info, [
                'data' => $data,
                'expected' => $expected,
            ])
        );
    }

    /**
     * 解码错误
     */
    public static function decodeError(string $data, string $reason = ''): self
    {
        $message = '数据解码失败';
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self($message, $data);
    }

    /**
     * 编码错误
     */
    public static function encodeError(mixed $data, string $reason = ''): self
    {
        $message = '数据编码失败';
        if ($reason) {
            $message .= ': ' . $reason;
        }

        return new self($message, $data);
    }
}
