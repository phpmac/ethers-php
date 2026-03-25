<?php

declare(strict_types=1);

namespace Tests\Ethers\Contract;

use Ethers\Contract\Contract;
use Ethers\Provider\JsonRpcProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MulticallTest extends TestCase
{
    private array $abi;

    protected function setUp(): void
    {
        $this->abi = [
            'function name() view returns (string)',
            'function symbol() view returns (string)',
            'function decimals() view returns (uint8)',
            'function balanceOf(address account) view returns (uint256)',
        ];
    }

    /**
     * @test
     */
    public function test_multicall_requires_provider(): void
    {
        // 没有 provider 时应该抛出异常
        $contract = new Contract('0x1234567890123456789012345678901234567890', $this->abi);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('需要 Provider 才能使用 multicall');

        $contract->multicall([
            ['method' => 'name', 'args' => []],
        ]);
    }

    /**
     * @test
     */
    public function test_multicall_returns_empty_array_for_empty_calls(): void
    {
        $provider = $this->createMock(JsonRpcProvider::class);
        $contract = new Contract('0x1234567890123456789012345678901234567890', $this->abi, $provider);

        $results = $contract->multicall([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * @test
     */
    public function test_multicall_uses_send_batch(): void
    {
        $provider = $this->createMock(JsonRpcProvider::class);

        // 验证 sendBatch 被调用一次
        $provider->expects($this->once())
            ->method('sendBatch')
            ->with($this->callback(function ($requests) {
                // 验证请求格式
                $this->assertCount(2, $requests);
                $this->assertSame('eth_call', $requests[0]['method']);
                $this->assertSame('eth_call', $requests[1]['method']);

                return true;
            }))
            ->willReturn([
                '0x000000000000000000000000000000000000000000000000000000000000002000000000000000000000000000000000000000000000000000000000000000045465737400000000000000000000000000000000000000000000000000000000',
                '0x000000000000000000000000000000000000000000000000000000000000002000000000000000000000000000000000000000000000000000000000000000035453540000000000000000000000000000000000000000000000000000000000',
            ]);

        $contract = new Contract('0x1234567890123456789012345678901234567890', $this->abi, $provider);

        $results = $contract->multicall([
            ['method' => 'name', 'args' => []],
            ['method' => 'symbol', 'args' => []],
        ]);

        $this->assertCount(2, $results);
    }

    /**
     * @test
     */
    public function test_multicall_throws_for_invalid_method(): void
    {
        $provider = $this->createMock(JsonRpcProvider::class);
        $contract = new Contract('0x1234567890123456789012345678901234567890', $this->abi, $provider);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('方法 invalidMethod 不存在');

        $contract->multicall([
            ['method' => 'invalidMethod', 'args' => []],
        ]);
    }

    /**
     * @test
     */
    public function test_multicall_throws_on_rpc_error(): void
    {
        $provider = $this->createMock(JsonRpcProvider::class);

        $provider->method('sendBatch')
            ->willReturn([
                new \Exception('RPC Error'),
            ]);

        $contract = new Contract('0x1234567890123456789012345678901234567890', $this->abi, $provider);

        $this->expectException(\Exception::class);

        $contract->multicall([
            ['method' => 'name', 'args' => []],
        ]);
    }

    /**
     * @test
     */
    public function test_multicall_with_method_args(): void
    {
        $provider = $this->createMock(JsonRpcProvider::class);

        // 验证 balanceOf 带参数调用
        $provider->expects($this->once())
            ->method('sendBatch')
            ->with($this->callback(function ($requests) {
                $this->assertCount(1, $requests);
                // 验证 data 包含 address 参数
                $data = $requests[0]['context']['transaction']['data'];
                $this->assertStringContainsString('70a08231', $data); // balanceOf(address) 的 selector

                return true;
            }))
            ->willReturn([
                '0x0000000000000000000000000000000000000000000000000000000000000001', // true
            ]);

        $contract = new Contract('0x1234567890123456789012345678901234567890', $this->abi, $provider);

        $results = $contract->multicall([
            ['method' => 'balanceOf', 'args' => ['0x1234567890123456789012345678901234567890']],
        ]);

        $this->assertCount(1, $results);
    }

    /**
     * @test
     */
    public function test_multicall_with_multiple_calls_and_args(): void
    {
        $provider = $this->createMock(JsonRpcProvider::class);

        $provider->expects($this->once())
            ->method('sendBatch')
            ->with($this->callback(function ($requests) {
                $this->assertCount(3, $requests);

                // 验证三个请求的方法名
                $this->assertSame('eth_call', $requests[0]['method']);
                $this->assertSame('eth_call', $requests[1]['method']);
                $this->assertSame('eth_call', $requests[2]['method']);

                // 验证目标地址一致
                $target = '0x1234567890123456789012345678901234567890';
                $this->assertSame($target, $requests[0]['context']['transaction']['to']);
                $this->assertSame($target, $requests[1]['context']['transaction']['to']);
                $this->assertSame($target, $requests[2]['context']['transaction']['to']);

                return true;
            }))
            ->willReturn([
                '0x000000000000000000000000000000000000000000000000000000000000002000000000000000000000000000000000000000000000000000000000000000045465737400000000000000000000000000000000000000000000000000000000',
                '0x000000000000000000000000000000000000000000000000000000000000002000000000000000000000000000000000000000000000000000000000000000035453540000000000000000000000000000000000000000000000000000000000',
                '0x0000000000000000000000000000000000000000000000000000000000000012',
            ]);

        $contract = new Contract('0x1234567890123456789012345678901234567890', $this->abi, $provider);

        $results = $contract->multicall([
            ['method' => 'name', 'args' => []],
            ['method' => 'symbol', 'args' => []],
            ['method' => 'decimals', 'args' => []],
        ]);

        // 验证返回数组结构
        $this->assertCount(3, $results);
        $this->assertIsArray($results[0]);
        $this->assertIsArray($results[1]);
        $this->assertIsArray($results[2]);
    }
}
