<?php

declare(strict_types=1);

namespace Ethers\Utils;

use kornrunner\Keccak as KeccakLib;

/**
 * Keccak 工具类
 * Keccak-256 哈希计算
 */
class Keccak
{
    /**
     * 计算 Keccak-256 哈希
     */
    public static function hash(string $data): string
    {
        // 如果是十六进制字符串, 先转换为二进制
        if (str_starts_with($data, '0x')) {
            $data = hex2bin(Hex::stripPrefix($data));
        }

        return '0x'.KeccakLib::hash($data, 256);
    }

    /**
     * 计算函数选择器 (取前 4 字节)
     */
    public static function functionSelector(string $signature): string
    {
        $hash = self::hash($signature);

        return substr($hash, 0, 10);
    }

    /**
     * 计算事件主题 (完整的 32 字节哈希)
     */
    public static function eventTopic(string $signature): string
    {
        return self::hash($signature);
    }

    /**
     * 计算十六进制数据的 Keccak-256 哈希
     *
     * @param  string  $hex  十六进制数据 (带或不带 0x 前缀)
     * @return string 哈希值 (带 0x 前缀)
     */
    public static function hashHex(string $hex): string
    {
        $hex = Hex::stripPrefix($hex);
        $data = hex2bin($hex);
        if ($data === false) {
            throw new \InvalidArgumentException('无效的十六进制数据');
        }

        return '0x'.KeccakLib::hash($data, 256);
    }
}
