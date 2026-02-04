<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * ErrorFactory
 * 根据 RPC 错误创建对应的异常
 *
 * 参考 ethers.js v6 的错误分类
 */
class ErrorFactory
{
    /**
     * 从 RPC 错误创建对应的异常
     *
     * @param int $rpcCode RPC 错误码
     * @param string $rpcMessage RPC 错误消息
     * @param array|null $transaction 交易信息
     * @return EthersException
     */
    public static function fromRpcError(
        int $rpcCode,
        string $rpcMessage,
        ?array $transaction = null
    ): EthersException {
        $lowerMessage = strtolower($rpcMessage);

        // 替换交易 gas 价格过低
        if (str_contains($lowerMessage, 'replacement transaction underpriced')) {
            return ReplacementUnderpricedError::fromRpcError($rpcCode, $rpcMessage, $transaction);
        }

        // nonce 过期 (nonce too low)
        if (str_contains($lowerMessage, 'nonce too low')) {
            return NonceExpiredError::fromRpcError($rpcCode, $rpcMessage, $transaction);
        }

        // nonce 太高
        if (str_contains($lowerMessage, 'nonce too high')) {
            return NonceExpiredError::fromRpcError($rpcCode, $rpcMessage, $transaction);
        }

        // 无效 nonce
        if (str_contains($lowerMessage, 'invalid nonce')) {
            return NonceExpiredError::fromRpcError($rpcCode, $rpcMessage, $transaction);
        }

        // 余额不足
        if (str_contains($lowerMessage, 'insufficient funds')) {
            return InsufficientFundsError::fromRpcError($rpcCode, $rpcMessage, $transaction);
        }

        // 其他服务器错误
        return ServerError::fromRpcError($rpcCode, $rpcMessage);
    }
}
