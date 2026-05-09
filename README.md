# Laravel Boost 中文教程 · 从 ThinkPHP 老手视角

> 一份**实测过、能跑通**的 Laravel Boost 入门到精通教程，配套一个 2 小时搭起来的 Laravel 12 博客实战项目（31 个 Pest 测试 passed）。

**适合谁读**：

- 有 ThinkPHP 经验、想转 Laravel 但被它的"魔法"劝退过的 PHP 开发者
- 想把 AI 编码助手（Cursor / Claude Code / GitHub Copilot）真正用在 Laravel 项目里的人
- 对 Laravel Boost 感兴趣、想知道它到底能省多少时间的人

**实测环境**（教程内所有命令都在这个版本下跑过）：

| 工具 | 版本 |
|---|---|
| PHP | 8.2.9 |
| Laravel | 12.58.0 |
| Composer | 2.x |
| `laravel/boost` | 2.4.6（2025-09 发布） |
| 测试框架 | Pest 3.8 |
| 后台框架 | Filament 5.6 |

---

## 仓库结构

```
.
├── docs/             # 12 篇中文教程（约 40 万字，全部已写完）
├── playground/       # 配套实战项目：Laravel 12 + Sanctum + Filament + Pest
└── .cursor/          # Cursor MCP 配置（接 Boost）
```

`docs/` 和 `playground/` 是**绑在一起**的——教程里大量引用 `playground/` 的真实代码、迁移文件、Pest 测试。建议 clone 整个仓库后对照阅读。

---

## 推荐阅读路线

### 🚀 30 分钟快速上手

1. [`docs/00-教程导读.md`](./docs/00-教程导读.md) — 全书入口，先读这个 ⭐
2. [`docs/03-环境搭建实测.md`](./docs/03-环境搭建实测.md) — 把 playground 跑起来
3. 在 `playground/` 里跑 `php artisan serve`，看实战项目长啥样

### 📚 完整路线（按顺序）

| 编号 | 文档 | 主题 |
|---|---|---|
| 00 | [教程导读](./docs/00-教程导读.md) ⭐ | 全书结构 + 学习方法论 |
| 01 | [认知篇·Laravel 与 Boost](./docs/01-认知篇-Laravel与Boost.md) | ThinkPHP 老手的 5 个心理障碍 + Boost 三大能力 |
| 03 | [环境搭建实测](./docs/03-环境搭建实测.md) | PHP / Composer / Laravel / Boost 装机踩坑 |
| 06-11 | [章节速查](./docs/06-11-章节速查.md) | 路由 / Eloquent / Migration / FormRequest / Policy / Blade |
| 12 | [Boost 工具实战大全](./docs/12-Boost工具实战大全.md) | Boost 的 9 个 MCP 工具，每个怎么用 |
| 13 | [博客实战完整复盘](./docs/13-博客实战完整复盘.md) | 2 小时从零到上线的全过程 |
| 14 | [API 化实战](./docs/14-API化实战.md) | Sanctum + API Resources + 错误规范 |
| 15 | [队列实战](./docs/15-队列实战.md) | Job / Queue / 邮件通知 / 测试 |
| 16 | [Filament 后台实战](./docs/16-Filament后台实战.md) | 一行命令搞定后台 CRUD |
| 17 | [Prompt 工程反例集](./docs/17-Prompt工程反例集.md) | 给 AI 写 prompt 的 N 个坑 |
| 18 | [部署与生产实践](./docs/18-部署与生产实践.md) ⭐ | 教程收官，从本地到生产 |

完整大纲：[`docs/Laravel_Boost_教程大纲.md`](./docs/Laravel_Boost_教程大纲.md)

---

## 跑起来 playground

```bash
cd playground

# 1. 装依赖
composer install

# 2. 初始化环境
cp .env.example .env
php artisan key:generate

# 3. 用 SQLite（默认配置，零依赖）
touch database/database.sqlite
php artisan migrate --seed

# 4. 跑测试，确认环境 OK
./vendor/bin/pest

# 5. 起开发服务器
php artisan serve
# 浏览器打开 http://localhost:8000
```

**测试账号**（DatabaseSeeder 里写死的）：

- 邮箱：`test@example.com`
- 密码：`password`
- 角色：admin（在 `PostPolicy` 里有 admin bypass）

**Filament 后台入口**：`/admin`

---

## Boost 是什么 / 为什么要装

> 一句话：**让 Cursor 等 AI 工具更懂 Laravel 的 MCP 服务器 + 一套版本对齐的 AI guidelines。**

详见 [`docs/01-认知篇-Laravel与Boost.md`](./docs/01-认知篇-Laravel与Boost.md) 的"Boost 三大核心能力"。

简单地装一下：

```bash
cd playground
composer require laravel/boost --dev
php artisan boost:install
```

---

## 这个仓库的"开发轨迹"

`playground/` 的 git 历史**保留了从零搭建的全过程**——18 个 commit，能直观看到一个 Laravel 项目是怎么一步步加功能的：

```
project init
  → Add user-post relationship + seeder
  → Add scopes for published / draft / scheduled posts
  → Route model binding + resource routes
  → Implement authorization in PostController
  → Add authentication links + login routes
  → Add 'mine' method (我的文章)
  → Add Pest and related packages
  → 装 Sanctum + 初始化 API 路由
  → API 登录/登出 + API Resources + 文章 CRUD
  → Refactor: 简化 API 路由
  → Add job to send email when post published
  → Add Sanctum support to User model
  → Update composer + add Filament
  → Add admin bypass to PostPolicy
```

`git log --oneline` 看一眼，比看任何"项目模板"都直观。

---

## 教程的更新约定

- 每篇文档头部写明**实测时的版本号**，时效性出问题时可对照
- 命令、报错、修复方案都来自真实操作记录，不是从官方文档抄的
- 看到任何"应该可以"的描述时，请告诉我——这通常意味着我没实测过

发现错误 / 版本失配 / 跑不通：欢迎开 issue 或 PR。

---

## License

[MIT](./LICENSE) — 教程文档和代码都用 MIT，转载、引用、二次创作都不用问。

如果对你有帮助，给个 star 是最大的鼓励。
