# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 项目说明

**ethers-php** - PHP 版 Ethereum SDK, 参考 [ethers.js v6](https://docs.ethers.org/v6/) 实现.

本项目是 ethers.js v6 的 PHP 移植版本, 提供类似的 API 设计模式用于以太坊区块链交互.

## 常用命令

```bash
# 运行测试
composer test

# 运行指定测试文件
./vendor/bin/phpunit tests/Contract/ContractTest.php
```

## 版本发布

遵循 [语义化版本](https://semver.org/lang/zh-CN/) 规范:

```bash
# 创建新版本标签
git tag -a v1.0.0 -m "发布 v1.0.0"
git push origin v1.0.0
```

