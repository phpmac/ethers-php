<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * ReplacementUnderpricedError
 * 替换交易 gas 价格过低时抛出
 *
 * 参考 ethers.js v6 的 ReplacementUnderpricedError
 * 错误代码: REPLACEMENT_UNDERPRICED
 *
 * 场景: 尝试用相同 nonce 替换交易，但 gas price 没有比之前高至少 10%
 */
class ReplacementUnderpricedError extends EthersException
{
    public const CODE = 'REPLACEMENT_UNDERPRICED';

    /**
     * 当前 gas 价格
     */
    public readonly ?string $currentGasPrice;

    /**
     * 需要的最低 gas 价格
     */
    public readonly ?string $requiredGasPrice;

    public function __construct(
        string $message = '替换交易 gas 价格过低',
        ?string $currentGasPrice = null,
        ?string $requiredGasPrice = null,
        array $info = []
    ) {
        $this->currentGasPrice = $currentGasPrice;
        $this->requiredGasPrice = $requiredGasPrice;

        parent::__construct(
            $message,
            self::CODE,
            '替换交易 gas 价格过低',
            array_merge($info, [
                'currentGasPrice' => $currentGasPrice,
                'requiredGasPrice' => $requiredGasPrice,
            ])
        );
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
            'replacement transaction underpriced',
            null,
            null,
            ['rpcCode' => $rpcCode, 'transaction' => $transaction]
        );
    }
}
