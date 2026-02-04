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
}
