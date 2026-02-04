<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * TransactionReplacedError
 * 交易被替换时抛出
 *
 * 参考 ethers.js v6 的 TransactionReplacedError
 * 错误代码: TRANSACTION_REPLACED
 *
 * 场景: 原交易被另一个相同 nonce 的交易替换
 */
class TransactionReplacedError extends EthersException
{
    public const CODE = 'TRANSACTION_REPLACED';

    /**
     * 原交易哈希
     */
    public readonly string $originalHash;

    /**
     * 替换后的交易哈希
     */
    public readonly string $replacedHash;

    /**
     * 替换原因: 'cancelled' | 'replaced' | 'repriced'
     */
    public readonly string $reason;

    public function __construct(
        string $originalHash,
        string $replacedHash,
        string $reason = 'replaced',
        array $info = []
    ) {
        $this->originalHash = $originalHash;
        $this->replacedHash = $replacedHash;
        $this->reason = $reason;

        $reasonMap = [
            'cancelled' => '交易被取消',
            'replaced' => '交易被替换',
            'repriced' => '交易被重新定价',
        ];

        parent::__construct(
            "交易 {$originalHash} 被 {$replacedHash} 替换",
            self::CODE,
            $reasonMap[$reason] ?? '交易被替换',
            array_merge($info, [
                'originalHash' => $originalHash,
                'replacedHash' => $replacedHash,
                'reason' => $reason,
            ])
        );
    }
}
