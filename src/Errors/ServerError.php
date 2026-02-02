<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * ServerError
 * RPC 服务器返回错误时抛出
 *
 * 参考 ethers.js v6 的 ServerError
 * 错误代码: SERVER_ERROR
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/#ServerError
 */
class ServerError extends EthersException
{
    public const CODE = 'SERVER_ERROR';

    /**
     * 请求的 URL
     */
    public readonly string $request;

    /**
     * RPC 错误代码
     */
    public readonly int $rpcCode;

    /**
     * 构造函数
     *
     * @param  string  $message  错误信息
     * @param  string  $request  请求 URL
     * @param  int  $rpcCode  RPC 错误代码
     * @param  array<string, mixed>  $info  附加信息
     */
    public function __construct(
        string $message = '服务器错误',
        string $request = '',
        int $rpcCode = -1,
        array $info = []
    ) {
        $this->request = $request;
        $this->rpcCode = $rpcCode;

        parent::__construct($message, self::CODE, '服务器错误', array_merge($info, [
            'request' => $request,
            'rpcCode' => $rpcCode,
        ]));
    }

    /**
     * 从 RPC 错误创建
     */
    public static function fromRpcError(
        int $rpcCode,
        string $rpcMessage,
        string $url = ''
    ): self {
        return new self(
            "RPC Error [{$rpcCode}]: {$rpcMessage}",
            $url,
            $rpcCode
        );
    }
}
