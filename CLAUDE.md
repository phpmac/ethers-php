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

## 代码格式化

使用 Laravel Pint 保持代码风格一致:

```bash
# 格式化所有文件
./vendor/bin/pint

# 格式化指定文件
./vendor/bin/pint app/Contract/Contract.php

# 显示将要修改的内容 (不实际修改)
./vendor/bin/pint --test
```

## 批量请求 (multicall)

使用 JSON-RPC 2.0 批量请求特性,将多个合约调用合并为**一次 HTTP 请求**.

**参考示例:** `examples/multicall_demo.php`

**关键特点:**

- 一次 HTTP 请求获取多个数据
- 返回顺序与请求顺序一致
- 使用 `Units::formatUnits()` 格式化代币数量

## 版本发布

遵循 [语义化版本](https://semver.org/lang/zh-CN/) 规范.

### <workflow>开发流程</workflow>

<workflow>
1. 基于最新正式版本创建开发分支 (e.g. feature/xxx 基于 v1.2.0)
2. 在开发分支上进行修改
3. 运行测试确保通过
4. 推送分支并创建 PR
5. 使用 CodeReview 进行代码检查
6. 合并到 main 分支后, 发布新版本 (创建 git tag)
</workflow>

**禁止在 main 分支直接开发或推送代码！**

```bash
# 1. 创建开发分支 (基于最新正式版本)
git checkout -b feature/xxx v1.2.0

# 2. 开发完成后, 推送分支
git push -u origin feature/xxx

# 3. 创建 PR
gh pr create --title "描述" --body "描述内容"

# 4. CodeReview 检查
/code-review

# 5. 合并后发布版本
git tag -a v1.3.0 -m "发布 v1.3.0"
git push origin v1.3.0

# 6. 合并到 main
git checkout main
git merge feature/xxx
git push origin main
```

