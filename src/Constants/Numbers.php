<?php

declare(strict_types=1);

namespace Ethers\Constants;

/**
 * 数值常量 (对齐 ethers.js v6 src.ts/constants/numbers.ts)
 */
class Numbers
{
    /**
     * uint256 最大值 (2^256 - 1)
     */
    public const MaxUint256 = '0x'.self::_FF;

    /**
     * int256 最大值 (2^255 - 1)
     */
    public const MaxInt256 = '0x'.self::_7F;

    /**
     * int256 最小值 (-2^255)
     * 对齐 ethers.js: -0x8000000000000000000000000000000000000000000000000000000000000000n
     */
    public const MinInt256 = '-0x8000000000000000000000000000000000000000000000000000000000000000';

    /**
     * secp256k1 曲线阶
     */
    public const N = '0xfffffffffffffffffffffffffffffffebaaedce6af48a03bbfd25e8cd0364141';

    /**
     * 1 ETH = 10^18 wei
     */
    public const WeiPerEther = '1000000000000000000';
}
