<?php

declare(strict_types=1);

namespace Ethers\Errors;

/**
 * NetworkError
 * 网络连接问题时抛出
 *
 * 参考 ethers.js v6 的 NetworkError
 * 错误代码: NETWORK_ERROR
 *
 * @see https://docs.ethers.org/v6/api/utils/errors/#NetworkError
 */
class NetworkError extends EthersException
{
    public const CODE = 'NETWORK_ERROR';

    /**
     * 网络事件
     */
    public readonly string $event;

    /**
     * 构造函数
     *
     * @param  string  $message  错误信息
     * @param  string  $event  网络事件
     * @param  array<string, mixed>  $info  附加信息
     */
    public function __construct(
        string $message = '网络连接失败',
        string $event = 'unknown',
        array $info = []
    ) {
        $this->event = $event;

        parent::__construct($message, self::CODE, '网络连接失败', array_merge($info, [
            'event' => $event,
        ]));
    }
}
