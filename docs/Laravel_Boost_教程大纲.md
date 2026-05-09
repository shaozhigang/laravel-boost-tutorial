# Laravel Boost 入门到精通教程大纲

> **适合人群**：有 ThinkPHP 经验的 PHP 开发者
> **工具版本（实测）**：Laravel 12.58.0 · PHP 8.2.9 · Composer 2.x · `laravel/boost@2.4.6`（2025-09 发布）
> **章节总数**：5 阶段 · 18 章节 + 4 篇实测笔记
> **配套实战项目**：`playground/`（2 小时实测博客系统，31 个 Pest 测试 passed）

> **状态图例**：
> - ✓ = 已完成（含实测笔记，可直接开读）
> - 🚧 = 实战已覆盖，待整理成成稿
> - 📌 = 待启动

---

## 实测笔记索引（写完即更新，可直接当"番外"读）

| 编号 | 文档 | 行数 | 对应主大纲章节 |
|---|---|---|---|
| ✓ | [`docs/00-教程导读.md`](./00-教程导读.md) | 1073 | **全书入口（推荐第一篇读）** ⭐ |
| ✓ | [`docs/01-认知篇-Laravel与Boost.md`](./01-认知篇-Laravel与Boost.md) | 1229 | 第 1 / 2 章 |
| ✓ | [`docs/03-环境搭建实测.md`](./03-环境搭建实测.md) | 383 | 第 3 / 4 / 5 章 |
| ✓ | [`docs/06-11-章节速查.md`](./06-11-章节速查.md) | 1485 | 第 6-11 章（速查） |
| ✓ | [`docs/12-Boost工具实战大全.md`](./12-Boost工具实战大全.md) | 530 | 第 12 章 |
| ✓ | [`docs/13-博客实战完整复盘.md`](./13-博客实战完整复盘.md) | 1279 | 第 6-11 / 13 章 |
| ✓ | [`docs/14-API化实战.md`](./14-API化实战.md) | 1128 | 第 14 章 |
| ✓ | [`docs/15-队列实战.md`](./15-队列实战.md) | 1077 | 第 15 章 |
| ✓ | [`docs/16-Filament后台实战.md`](./16-Filament后台实战.md) | 1106 | 第 16 章 |
| ✓ | [`docs/17-Prompt工程反例集.md`](./17-Prompt工程反例集.md) | 1549 | 第 17 章 |
| ✓ | [`docs/18-部署与生产实践.md`](./18-部署与生产实践.md) | 1364 | 第 18 章（教程收官） ⭐ |

> **建议阅读顺序**：先看实测笔记（具象、有数字），再看本大纲对应章节（抽象、有路线图）。
> "先做后学"比"先学后做"更适合 PHP 老手。

---

## 第一阶段：认知篇 · 搞清楚 Laravel Boost 是什么

### 第 1 章：Laravel 是什么，和 ThinkPHP 有何区别 ✓
[实测笔记 →](./01-认知篇-Laravel与Boost.md#第-1-章laravel-是什么和-thinkphp-有何区别)

- ThinkPHP 老手转 Laravel 的 5 个心理障碍（数组→对象 / Model 定位 / 约定 vs 灵活 / 看 magic / 测试焦虑）
- 一图对比：ThinkPHP vs Laravel 设计哲学（"PHP 框架" vs "用 PHP 写的 Rails"）
- 目录结构对比 + 关键差异 5 处
- **7 个核心抽象的对照**（路由 / Eloquent / Migration / FormRequest / Policy / Blade / Pest）
- 为什么 Laravel 的约定性更适合 AI 辅助开发（5 个论点）

### 第 2 章：Laravel Boost 的三大核心能力 ✓
[实测笔记 →](./01-认知篇-Laravel与Boost.md#第-2-章laravel-boost-的三大核心能力)

- Laravel Boost 是什么（30 秒读完）
- 核心能力一：**MCP 工具集**——实测可用 **9 个**（不是宣传的 15+；其余是 LLM 评估工具）
- 核心能力二：**AI Guidelines**——版本化的 Laravel 规范指令文件
- 核心能力三：**Skills 体系**——按需激活的"主动行为"规则
- Boost 让 AI 输出质量提升多少（Migration / Policy / API 三个对比实证）
- 装 Boost 的 ROI 计算（40 分钟投入 → 每天节省 2-4 小时）
- Boost 与 Cursor、Claude Code、GitHub Copilot 的集成关系

---

## 第二阶段：环境篇 · 从零搭建开发环境

### 第 3 章：环境安装（PHP 8.2+ / Composer / Laravel）✓

- PHP 版本检查与升级（**实测：Laravel 12 要求 PHP 8.2+**，原大纲写 8.1+ 已过时）
- Composer 安装与换源（**实测踩坑**：腾讯云 → 404、阿里云 → tinker 元数据缺失、最终用官方源）
- `laravel new` 创建第一个项目（实测花费约 1 分钟）
- 项目目录结构详解（对比 ThinkPHP 的 `app/` 目录）
- `.env` 配置文件解读（数据库、缓存、队列）
- **实测踩坑①**：SQLite 驱动需要在 `php.ini` 启用 `extension=pdo_sqlite` + `extension=sqlite3`
- **实测踩坑②**：PHPStudy 的 PHP 默认是 nts 版本，注意路径

> **完整实测笔记**：[`docs/03-环境搭建实测.md`](./03-环境搭建实测.md)

### 第 4 章：安装 Laravel Boost ✓

- 以开发依赖方式安装：`composer require laravel/boost --dev`
- 运行初始化命令：`php artisan boost:install`
- 安装过程中的交互选项解读
- 自动检测并选择生态包（Livewire、Filament 等）
- 生成的文件说明：`.ai/guidelines/` 目录 + `.ai/skills/` 目录

> **实测**：Boost 安装后只生成本地 markdown 文件（Guidelines / Skills），不修改 `composer.json` 之外的任何 Laravel 项目文件。可放心安装，不破坏现有项目结构。

### 第 5 章：配置 AI 编辑器（Cursor / Claude Code）✓

- MCP Server 的概念与注册方式
- Cursor 接入 Boost MCP 步骤：在 `.cursor/mcp.json` 写配置
- **实测踩坑（重要！）**：Cursor 的 workspace root 与 Laravel 项目根目录不同时，**`mcp.json` 必须用绝对路径**指向 `artisan`：

```json
{
  "mcpServers": {
    "laravel-boost": {
      "command": "php",
      "args": [
        "D:/workspace/cursor/laravel-boost/playground/artisan",
        "boost:mcp"
      ]
    }
  }
}
```

- Claude Code 接入 Boost MCP 步骤
- 验证连接是否成功（让 AI 执行一条 `application-info` 工具调用）
- 常见问题排查：
  - MCP 标签找不到 → 用 Ctrl+Shift+P → "MCP: Show Connected Servers"
  - 连接失败 → 检查 `php artisan boost:mcp` 单独跑能不能正常输出 stdio 协议

---

## 第三阶段：基础篇 · Laravel 核心功能快速掌握

### 第 6 章：路由系统（对比 ThinkPHP）🚧
[速查 →](./06-11-章节速查.md#6-路由系统速查)

- `web.php` 与 `api.php` 的分工
- RESTful 风格路由定义（`Route::resource` 一行 = 7 条路由）
- 路由分组（`prefix` / `middleware` / `namespace` / `name`）
- 命名路由与路由反查（`route('posts.show', $post)`）
- 路由模型绑定（默认按 id / 自定义 `getRouteKeyName()` 用 slug）
- **实测要点**：自定义路由必须放在 `Route::resource` **前面**（否则被 `posts/{post}` 当 slug 匹配走）
- 使用 Boost 的 `list-routes` 工具让 AI 检查路由结构

> **配套实战**：[第 13 章 §3.4 路由设计](./13-博客实战完整复盘.md)

### 第 7 章：Eloquent ORM（对比 TP 的 Model）🚧
[速查 →](./06-11-章节速查.md#7-eloquent-orm-速查)

- Model 定义与数据库表映射
- 基础 CRUD 操作
- **`fillable` 必填**——Laravel 12 默认所有字段都禁止批量赋值（与 TP 默认开放完全相反）
- **`casts()` 方法签名**（Laravel 11+）vs `$casts` 属性（旧）
- 关联关系：`hasOne` / `hasMany` / `belongsTo` / `belongsToMany`
- 关系命名陷阱：方法名 ≠ 字段前缀时**必须显式传外键**
- 查询构建器与 Eloquent 链式调用
- **Local Scope**——把业务条件集中到 Model（TP 没有等价物）
- 使用 Boost 的 `database-schema` 工具让 AI 了解你的数据结构

> **配套实战**：[第 13 章 §2 数据层](./13-博客实战完整复盘.md)（含 Migration / Model / Factory / Seeder 全套实例）

### 第 8 章：Migration 数据库迁移 + Artisan 命令 🚧
[速查 →](./06-11-章节速查.md#8-migration--artisan-速查)

- Migration 是什么（数据库版本控制）
- 常用命令：`make:migration` / `migrate` / `migrate:rollback` / `migrate:fresh --seed`
- **现代 Schema Builder**：`foreignId().constrained().cascadeOnDelete()` 一行 = 老语法 4 行
- `php artisan make:*` 系列命令速查
- Tinker 交互式调试工具使用
- **实测踩坑**：tinker 是长进程，**会缓存数据库连接**——如果 seed 后查不到数据，重启 tinker
- Seeder 幂等改造：`firstOrCreate()` + `wasRecentlyCreated` 模式
- 使用 Boost 的 `list-artisan-commands` 工具

> **配套实战**：[第 13 章 §2.5 DatabaseSeeder UNIQUE 踩坑 + 幂等改造](./13-博客实战完整复盘.md)

### 第 9 章：Controller + Form Request 验证 🚧
[速查 →](./06-11-章节速查.md#9-controller--form-request-速查)

- 资源控制器生成与使用
- `php artisan make:controller --resource`
- **Laravel 11+ 中间件注册**：`HasMiddleware` 接口 + 静态 `middleware()` 方法（不是 `__construct`）
- Form Request 类封装验证逻辑
- 常用验证规则速查（对比 TP 的 `validate()`）
- **`Rule::unique()->ignore()` 模式**——编辑场景不与自己撞唯一约束
- 让 Boost 引导 AI 生成符合规范的控制器代码

> **配套实战**：[第 13 章 §3 HTTP 层](./13-博客实战完整复盘.md)（含 PostController / StorePostRequest / UpdatePostRequest 全套实例）

### 第 10 章：Blade 模板 + 中间件 🚧
[速查 →](./06-11-章节速查.md#10-blade--中间件速查)

- Blade 语法速查（`{{ }}` / `@if` / `@foreach` / `@forelse` / `@can`）
- 对比 ThinkPHP 的 `{$var}` 模板语法
- **组件化开发**（`<x-layout>` / `<x-posts.form-fields>`）
- `@props` 定义组件参数 + 默认值
- `old('field', $model?->field)` 用 nullsafe 处理"创建+编辑"两个场景
- 中间件定义与注册（认证、日志、限流）
- 使用 Boost 的 `read-config-keys` 工具读取应用配置

> **配套实战**：[第 13 章 §4 视图层](./13-博客实战完整复盘.md)（含 5 个完整视图 + 共享组件）

### 第 11 章：认证系统（Auth / Sanctum / Breeze）🚧
[速查 →](./06-11-章节速查.md#11-认证系统速查)

- Laravel 内置 Auth 系统：`Auth::attempt()` / `Auth::logout()` / `auth()->user()`
- **Breeze 脚手架**一键生成登录/注册页面（实战中**故意不用**，手写学习）
- 手写 Demo 登录的 4 个安全细节：
  - `session()->regenerate()` 防 Session Fixation
  - `redirect()->intended()` 回跳原始访问页
  - `->onlyInput('email')` 错误回填不带 password
  - logout 三件套：`logout` + `invalidate` + `regenerateToken`
- Sanctum 实现 API Token 鉴权（第 14 章详细做）
- **权限管理：Policy + `@can` 指令 + `authorizeResource()`**
- `?User` vs `User` 类型签名差异（区分游客/登录）
- 前后端分离场景下的认证方案

> **配套实战**：[第 13 章 §5 认证层](./13-博客实战完整复盘.md)（含 AuthController + PostPolicy 完整实例）

---

## 第四阶段：实战篇 · 用 Boost + AI 构建真实项目

### 第 12 章：Boost MCP 工具实战大全 ✓

#### 实测的 9 个核心工具（修正版）

| # | 工具名（kebab-case） | 功能说明 | 实战使用频率 |
|---|---|---|---|
| 1 | `application-info` | 读取 PHP/Laravel 版本、已安装包信息 | ⭐⭐⭐⭐⭐（必用） |
| 2 | `database-schema` | 分析完整数据库结构 | ⭐⭐⭐⭐⭐（必用） |
| 3 | `database-query` | 直接对数据库执行只读查询 | ⭐⭐⭐⭐ |
| 4 | `database-connections` | 列出所有数据库连接配置 | ⭐⭐ |
| 5 | `tinker` | 在 Laravel 上下文中执行代码 | ⭐⭐⭐⭐ |
| 6 | `search-docs` | 搜索版本化的 Laravel 生态文档（17K+ chunks） | ⭐⭐⭐⭐⭐（必用） |
| 7 | `read-log-entries` | 读取应用错误日志最后 N 条 | ⭐⭐⭐ |
| 8 | `last-error` | 获取最近一条应用错误 | ⭐⭐⭐ |
| 9 | `browser-logs` / `get-absolute-url` | 浏览器调试辅助 | ⭐⭐ |

> **与原大纲差异**：原大纲列了 12 个工具（含 `list_routes` / `list_artisan` / `read_config` / `report_feedback`），实测发现：
> - `list_routes` / `list_artisan` 没有作为独立 MCP 工具暴露——AI 直接调 `php artisan route:list` / `php artisan list` 即可
> - `read_config` 实际叫 `read-config-keys`，使用频率低，并入"辅助工具"分类
> - `report_feedback` 不是日常使用工具，移到第 17 章末尾介绍

> **完整实战笔记**：[`docs/12-Boost工具实战大全.md`](./12-Boost工具实战大全.md)（每个工具都有真实参数 + 真实输出 + 真实用例）

### 第 13 章：实战项目一 · 博客系统（CRUD + 认证）✓

- **2 小时实测博客**：6 用户 / 20 文章 / 31 Pest 测试 passed
- 需求分析与数据库设计（AI + Boost 辅助）
- Migration 建表（让 AI 生成并用 `database-schema` 工具验证）
- 文章 CRUD 接口实现（含 Policy 权限 + Form Request 验证）
- 用户认证集成（手写 Demo 登录）
- Blade 前端页面开发（5 视图 + 2 共享组件）
- 用 Boost 让 AI 自动补全测试用例（**实测：AI 主动写了 31 个 Pest 测试**）

> **完整实战笔记**：[`docs/13-博客实战完整复盘.md`](./13-博客实战完整复盘.md)（1279 行 / 11 节 / 含数字战果 + 时间线 + 8 个老手代码点对照表 + 4 个 AI 表现一般的瞬间）

### 第 14 章：实战项目二 · RESTful API 开发 ✓
[实测笔记 →](./14-API化实战.md)

- API Resource 层（`Illuminate\Http\Resources\Json\JsonResource`）+ `whenLoaded` + ISO8601
- Sanctum Token 认证接入（`createToken` / `auth:sanctum` 中间件 / `currentAccessToken`）
- `apiResource` vs `resource` 路由差异
- API 错误响应规范（401/403/404/422 统一 JSON 结构）
- **Form Request / Policy / Job 全链路复用 web 端代码** ⭐⭐⭐
- **重大踩坑实测**：`authorizeResource()` 在 Laravel 11+ + `HasMiddleware` 不兼容 ⭐
- 18 个 Pest API 测试（Sanctum::actingAs + assertJsonStructure + assertJsonValidationErrors）
- 用 Boost 让 AI 按规范自动生成测试

> **配套实战**：`playground/` 在第 13 章博客 + 第 15 章队列基础上扩展为 API。

### 第 15 章：队列 Queue + Job + Mailable ✓
[实测笔记 →](./15-队列实战.md)

- 队列的使用场景（发邮件、处理图片、异步通知）
- Queue Worker 配置与运行（`database` driver / `redis` driver 对比）
- Job 类定义与分发（`dispatch()` 静态分发 + `Queueable` trait 聚合）
- Mailable 类 + Markdown 邮件双输出（HTML + 纯文本）
- 失败重试链路：`$tries` / `$backoff` / `failed()` 钩子
- **测试技巧**：`Queue::fake()` / `Mail::fake()` / `Log::spy()` / `Mockery::shouldReceive`
- **重大踩坑实测**：Windows + SQLite 队列并发锁问题（含 5 个解决方向）
- **实战场景**：博客文章发布 → 异步发邮件通知作者（含 9 个 Pest 测试）
- 用 Boost 的 `read-log-entries` 工具调试失败的 Job

### 第 16 章：生态包实战（Filament 后台）✓
[实测笔记 →](./16-Filament后台实战.md)

- **重大版本意外**：Filament 已经 v5.6.2，不是网上 90% 教程的 v3 ⭐
- 用 `composer require filament/filament -W` 不锁版本自动选最新
- `php artisan filament:install --panels` 创建 admin panel
- **FilamentUser 接口** + `canAccessPanel()` 实现"谁能进后台"
- v5 模块化结构：`PostResource` + `Pages/` + `Schemas/` + `Tables/`（vs v3 单文件）
- Schema builder：`live(onBlur)` + `Select::relationship` + `unique(ignoreRecord)`
- Table builder：状态徽章（虚拟字段）+ SelectFilter（自定义 query）+ recordActions/toolbarActions
- **Policy `before()` hook** 让 admin 在 Filament/web/API 三端 bypass，**普通用户规则一字未动** ⭐
- 5 个 Pest 测试钉死 admin 权限边界
- Boost 为 16+ 生态包提供版本化 AI Guidelines
- 避免 AI 因版本混淆生成错误代码的方法

> **配套实战**：`playground/` 在第 13/14/15 章基础上加管理后台。

---

## 第五阶段：进阶篇 · 精通 AI 辅助 Laravel 开发

### 第 17 章：Prompt 工程 · 让 AI 写出"Laravel 老手"级代码 ✓

- 为什么没有 Boost 时 AI 容易写出"野路子" PHP
- **5 大病理对照集**：
  - ① 过度模糊（不说版本/约束/范围）
  - ② 复制粘贴需求（业务话当 prompt）
  - ③ 不让 AI 用工具（默认 AI 不查文档）
  - ④ 没写验收标准（不告诉 AI"完成的样子"）
  - ⑤ 一个 prompt 塞太多（让 AI 失焦）
- **6 个 Prompt 模板库**（拿来即用）：
  - 模板 1：新增"资源型"功能
  - 模板 2：修改现有功能
  - 模板 3：debug
  - 模板 4：性能优化
  - 模板 5：UI 美化
  - 模板 6：API 化
- ThinkPHP 老手最容易写的 5 个 prompt 反例
- 如何利用 Guidelines 文件约束 AI 行为
- Skills 按需激活（减少上下文污染）
- 用 `report-feedback` 工具向官方反馈问题
- 定制自己项目的 AI 规则（团队协作场景）

> **完整笔记**：[`docs/17-Prompt工程反例集.md`](./17-Prompt工程反例集.md)（1549 行 / 11 节 / 含 16 组 prompt 反例 vs 改写对照 + 6 个模板 + 3 个练习题）

### 第 18 章：部署上线 + 性能优化 + 安全 ✓
[实测笔记 →](./18-部署与生产实践.md)

- 4 个部署方案对比（Forge / Vapor / Laravel Cloud / 自建）+ 选型决策树
- **生产环境配置 14 项检查清单** ⭐（APP_DEBUG / APP_KEY / DB / Queue / Cache / Mail / Log...）
- 性能优化的 4 个层级 + "黄金顺序"（防 N+1 → config:cache → 索引 → Redis → Octane）
- Octane 实战（Swoole / FrankenPHP）+ 4 个陷阱
- 安全加固 10 大项（CSRF / SQL 注入 / XSS / Mass Assignment / Rate Limit / HTTPS / Session / Sanctum / 密钥 / PHP 配置）
- 监控的 4 个层次（Sentry / Horizon / Telescope / Pail）
- **dev 包移除（含 Boost 处理）**——`composer install --no-dev` 自动跳过
- **真实部署演练**：把 playground 部署到 Forge（10 步流程，约 65 分钟）
- ThinkPHP vs Laravel 部署/配置/性能/安全 4 表对照
- **教程整体收官** + 18 章回顾 + 致谢

---

## 推荐学习资源

- 官方文档：[laravel.com/docs](https://laravel.com/docs)
- Boost 文档：[laravel.com/docs/boost](https://laravel.com/docs/12.x/boost)
- GitHub 仓库：[github.com/laravel/boost](https://github.com/laravel/boost)
- 推荐 IDE：Cursor（内置 MCP 支持）或 Claude Code

## 编写进度（自跟踪）

| 阶段 | 章节 | 状态 |
|---|---|---|
| 第一阶段（认知篇） | 第 1-2 章 | ✓ 完成（`01-认知篇-Laravel与Boost.md`） |
| 第二阶段（环境篇） | 第 3-5 章 | ✓ 实测完成（`03-环境搭建实测.md`） |
| 第三阶段（基础篇） | 第 6-11 章 | ✓ 速查完成（`06-11-章节速查.md`） + 实战素材（`13-博客实战完整复盘.md`） |
| 第四阶段（实战篇）| 第 12 章 | ✓ 完成（`12-Boost工具实战大全.md`） |
| 第四阶段（实战篇）| 第 13 章 | ✓ 完成（`13-博客实战完整复盘.md`） |
| 第四阶段（实战篇）| 第 14 章 | ✓ 完成（`14-API化实战.md`） |
| 第四阶段（实战篇）| 第 15 章 | ✓ 完成（`15-队列实战.md`） |
| 第四阶段（实战篇）| 第 16 章 | ✓ 完成（`16-Filament后台实战.md`） |
| 第五阶段（进阶篇）| 第 17 章 | ✓ 完成（`17-Prompt工程反例集.md`） |
| 第五阶段（进阶篇）| 第 18 章 | ✓ 完成（`18-部署与生产实践.md`） |

**完成度**：**18/18 主章节全部成稿** ⭐⭐⭐ + 11 篇实测笔记（共约 12500 行 / 31 万字）

---

*本大纲基于 Laravel Boost 2.4.6（2025-09 发布）实测整理，配套项目 `playground/` 使用 Laravel 12.58 + PHP 8.2.9 + SQLite。*
