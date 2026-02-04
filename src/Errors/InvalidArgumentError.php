<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * InvalidArgumentError
 * 参数无效时抛出
 *
 * 参考 ethers.js v6 的 InvalidArgumentError
 * 错误代码: INVALID_ARGUMENT
 */
class InvalidArgumentError extends EthersException
{
    public const CODE = 'INVALID_ARGUMENT';

    /**
     * 参数名
     */
    public readonly ?string $argument;

    /**
     * 参数值
     */
    public readonly mixed $value;

    public function __construct(
        string $message,
        ?string $argument = null,
        mixed $value = null,
        array $info = []
    ) {
        $this->argument = $argument;
        $this->value = $value;

        parent::__construct(
            $message,
            self::CODE,
            '参数无效',
            array_merge($info, [
                'argument' => $argument,
                'value' => $value,
            ])
        );
    }

    /**
     * 创建指定参数无效的错误
     */
    public static function forArgument(string $argument, mixed $value, string $expected = ''): self
    {
        $message = "参数 '{$argument}' 无效";
        if ($expected) {
            $message .= "，期望: {$expected}";
        }

        return new self($message, $argument, $value);
    }
}
