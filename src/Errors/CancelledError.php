<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * CancelledError
 * 操作被取消时抛出
 *
 * 参考 ethers.js v6 的 CancelledError
 * 错误代码: CANCELLED
 *
 * 场景:
 * - 用户拒绝签名
 * - 用户关闭确认对话框
 * - 操作超时中断
 * - 前置条件检查失败取消
 */
class CancelledError extends EthersException
{
    public const CODE = 'CANCELLED';

    /**
     * 取消原因
     */
    public readonly ?string $cancelReason;

    /**
     * 原始错误
     */
    public readonly ?\Throwable $cause;

    public function __construct(
        string $message = '操作已取消',
        ?string $cancelReason = null,
        ?\Throwable $cause = null,
        array $info = []
    ) {
        $this->cancelReason = $cancelReason;
        $this->cause = $cause;

        parent::__construct(
            $message,
            self::CODE,
            '操作已取消',
            array_merge($info, [
                'cancelReason' => $cancelReason,
                'cause' => $cause?->getMessage(),
            ])
        );
    }

    /**
     * 用户取消
     */
    public static function userCancelled(string $reason = '用户取消操作'): self
    {
        return new self($reason, 'user_cancelled');
    }

    /**
     * 超时取消
     */
    public static function timeoutCancelled(int $timeoutSeconds): self
    {
        return new self(
            "操作在 {$timeoutSeconds} 秒后超时取消",
            'timeout',
            null,
            ['timeout' => $timeoutSeconds]
        );
    }
}
