<?php

declare(strict_types=1);

namespace Tests\Ethers\Contract;

use Ethers\Contract\Contract;
use Ethers\Provider\JsonRpcProvider;
use PHPUnit\Framework\TestCase;

/**
 * Multicall 集成测试
 *
 * 使用 BSC 网络真实 USDT 合约进行测试
 *
 * @internal
 */
class MulticallIntegrationTest extends TestCase
{
    private ?JsonRpcProvider $provider;

    private string $usdtAddress = '0x55d398326f99059fF775485246999027B3197955';

    protected function setUp(): void
    {
        // BSC 公共 RPC 节点
        $this->provider = new JsonRpcProvider('https://bsc-dataseed.binance.org');
    }

    protected function tearDown(): void
    {
        $this->provider = null;
    }

    /**
     * @test
     *
     * 使用 JSON-RPC 批量请求一次性获取 USDT 信息
     * 只发送一次 HTTP 请求，获取 name, symbol, decimals, totalSupply
     */
    public function test_multicall_usdt_bsc_batch_request(): void
    {
        $abi = [
            'function name() view returns (string)',
            'function symbol() view returns (string)',
            'function decimals() view returns (uint8)',
            'function totalSupply() view returns (uint256)',
        ];

        $contract = new Contract($this->usdtAddress, $abi, $this->provider);

        // multicall 使用 JSON-RPC 批量请求，只发送一次 HTTP 请求
        $results = $contract->multicall([
            ['method' => 'name', 'args' => []],
            ['method' => 'symbol', 'args' => []],
            ['method' => 'decimals', 'args' => []],
            ['method' => 'totalSupply', 'args' => []],
        ]);


        // 验证返回结果
        $this->assertCount(4, $results);

        // name 应该是 "Tether USD"
        $this->assertIsArray($results[0]);
        $this->assertSame('Tether USD', $results[0][0]);

        // symbol 应该是 "USDT"
        $this->assertIsArray($results[1]);
        $this->assertSame('USDT', $results[1][0]);

        // decimals 应该是 18
        $this->assertIsArray($results[2]);
        $this->assertSame('18', $results[2][0]);

        // totalSupply 应该是大数字
        $this->assertIsArray($results[3]);
        $this->assertGreaterThan(0, (int) $results[3][0]);
    }

    /**
     * @test
     *
     * 测试 sendBatch 方法直接发送批量请求
     */
    public function test_send_batch_directly(): void
    {
        // 使用 USDT 合约的 name() 和 symbol() 调用的编码数据
        // name(): 0x06fdde03
        // symbol(): 0x95d89b41
        $requests = [
            [
                'method' => 'eth_call',
                'params' => [['to' => $this->usdtAddress, 'data' => '0x06fdde03'], 'latest'],
            ],
            [
                'method' => 'eth_call',
                'params' => [['to' => $this->usdtAddress, 'data' => '0x95d89b41'], 'latest'],
            ],
        ];

        // 发送批量请求 (一次 HTTP 请求)
        $results = $this->provider->sendBatch($requests);

        $this->assertCount(2, $results);

        // 验证返回的是有效的十六进制数据
        $this->assertStringStartsWith('0x', $results[0]);
        $this->assertStringStartsWith('0x', $results[1]);

        // 解码验证
        // name 返回值解码
        $this->assertGreaterThan(100, strlen($results[0]));
        // symbol 返回值解码
        $this->assertGreaterThan(100, strlen($results[1]));
    }

    /**
     * @test
     *
     * 测试空 multicall 返回空数组
     */
    public function test_empty_multicall_returns_empty_array(): void
    {
        $abi = ['function name() view returns (string)'];
        $contract = new Contract($this->usdtAddress, $abi, $this->provider);

        $results = $contract->multicall([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     *
     * 测试单个调用的 multicall
     */
    public function test_single_call_multicall(): void
    {
        $abi = ['function name() view returns (string)'];
        $contract = new Contract($this->usdtAddress, $abi, $this->provider);

        $results = $contract->multicall([
            ['method' => 'name', 'args' => []],
        ]);

        $this->assertCount(1, $results);
        $this->assertIsArray($results[0]);
        $this->assertSame('Tether USD', $results[0][0]);
    }

    /**
     * @test
     *
     * 测试批量请求顺序保持
     */
    public function test_multicall_preserves_order(): void
    {
        $abi = [
            'function name() view returns (string)',
            'function symbol() view returns (string)',
            'function decimals() view returns (uint8)',
        ];

        $contract = new Contract($this->usdtAddress, $abi, $this->provider);

        // 按特定顺序请求
        $results = $contract->multicall([
            ['method' => 'symbol', 'args' => []],
            ['method' => 'name', 'args' => []],
            ['method' => 'decimals', 'args' => []],
        ]);

        // 验证顺序与请求一致
        $this->assertSame('USDT', $results[0][0]);
        $this->assertSame('Tether USD', $results[1][0]);
        $this->assertSame('18', $results[2][0]);
    }
}
