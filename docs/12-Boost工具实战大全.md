# 第 12 章 实测笔记：Boost MCP 工具实战大全

> 实测时间：2026-05-07
> Boost 版本：`laravel/boost@2.4.6`
> 关联生态：`laravel/mcp@0.7.0` + `laravel/roster@1.x`
> 实测项目：`playground/`（Laravel `12.58.0` + SQLite）

---

## 0. 大纲修订声明（看完这一节再读下面）

原大纲第 12 章列了 **12 个**工具：

```
app_info / inspect_schema / run_query / run_tinker / search_docs / list_routes /
list_artisan / read_logs / read_config / browser_logs / last_errors / report_feedback
```

**实测下来 Boost 2.4.6 实际暴露的 MCP 工具是 9 个**：

| 大纲名 | 实际名 | 状态 |
|---|---|---|
| `app_info` | `application-info` | ✅ 改名 |
| `inspect_schema` | `database-schema` | ✅ 改名 |
| `run_query` | `database-query` | ✅ 改名 |
| `run_tinker` | — | ❌ Boost 2.4.6 未暴露此工具 |
| `search_docs` | `search-docs` | ✅ 改名 |
| `list_routes` | — | ❌ 未暴露（`list-artisan-commands` 也未暴露） |
| `list_artisan` | — | ❌ 未暴露 |
| `read_logs` | `read-log-entries` | ✅ 改名 |
| `read_config` | — | ❌ 未暴露 |
| `browser_logs` | `browser-logs` | ✅ 改名 |
| `last_errors` | `last-error` | ✅ 改名（单数）|
| `report_feedback` | — | ❌ 未暴露 |
| — | `database-connections` | ⭐ 大纲漏列 |
| — | `get-absolute-url` | ⭐ 大纲漏列 |

**结论**：

1. 工具命名规则从 `snake_case` 统一改成 `kebab-case`
2. `list_routes` / `list_artisan` / `read_config` / `run_tinker` 在 2.4.6 没暴露——可能在更新版本里加上，也可能由 AI 自己跑 `php artisan ...` 命令替代
3. 多了 `database-connections` 和 `get-absolute-url`

→ **大纲第 12 章那张表必须重写**，本笔记末尾给出建议替换稿。

---

## 1. 工具全景（一张图理解）

把 9 个工具按"做什么"分 4 类：

```
┌─────────────────────────────────────────────────────────────┐
│  🔵 项目自省（让 AI 看清你的项目）                          │
│  └─ application-info        当前 PHP / Laravel / 包版本     │
│                                                             │
│  🟢 数据库（让 AI 看清数据层）                              │
│  ├─ database-connections    所有数据库连接                  │
│  ├─ database-schema         所有表结构                      │
│  └─ database-query          只读 SQL（SELECT/SHOW/EXPLAIN） │
│                                                             │
│  🟡 日志诊断（出错时让 AI 排查）                            │
│  ├─ read-log-entries        读最近 N 条 laravel.log         │
│  ├─ last-error              拿最近一条错误的完整堆栈        │
│  └─ browser-logs            读浏览器 console 日志           │
│                                                             │
│  🟣 路由 + 文档（让 AI 写对代码）                           │
│  ├─ get-absolute-url        路径/命名路由 → 完整 URL        │
│  └─ search-docs ⭐          版本化的 Laravel 生态文档搜索   │
└─────────────────────────────────────────────────────────────┘
```

| 工具 | 是否 Boost 独有 | 优先级 | 一句话用途 |
|---|---|---|---|
| `application-info` | ✅ Boost 独有 | P0 | 验证 MCP 是否接通的第一发 |
| `database-connections` | ✅ Boost 独有 | P1 | 多 driver 项目排查必备 |
| `database-schema` | ✅ Boost 独有 | P0 | CRUD 之前 AI 必看 |
| `database-query` | ✅ Boost 独有 | P1 | 调试数据状态 |
| `read-log-entries` | ✅ Boost 独有 | P2 | 跑起来报错时 |
| `last-error` | ✅ Boost 独有 | P0 | 500 错误的最快定位 |
| `browser-logs` | ✅ Boost 独有 | P2 | 前端报错排查 |
| `get-absolute-url` | ✅ Boost 独有 | P3 | 给测试链接 / 邮件链接用 |
| `search-docs` | ⭐ **Boost 杀手锏** | P0 | 写代码前查最新语法 |

---

## 2. 工具详解（9 个，每个含真实实测数据）

### 2.1 `application-info`

**用途**：返回 PHP 版本、Laravel 版本、数据库引擎、所有 Laravel 生态包及精确版本号。

**参数**：无。

**真实调用结果**（在 `playground/` 项目里）：

```json
{
  "php_version": "8.2",
  "laravel_version": "12.58.0",
  "database_engine": "sqlite",
  "packages": [
    {"roster_name": "LARAVEL",  "version": "12.58.0", "package_name": "laravel/framework"},
    {"roster_name": "PROMPTS",  "version": "0.3.17",  "package_name": "laravel/prompts"},
    {"roster_name": "BOOST",    "version": "2.4.6",   "package_name": "laravel/boost"},
    {"roster_name": "MCP",      "version": "0.7.0",   "package_name": "laravel/mcp"},
    {"roster_name": "PAIL",     "version": "1.2.6",   "package_name": "laravel/pail"},
    {"roster_name": "PINT",     "version": "1.29.1",  "package_name": "laravel/pint"},
    {"roster_name": "SAIL",     "version": "1.58.0",  "package_name": "laravel/sail"},
    {"roster_name": "PHPUNIT",  "version": "11.5.55", "package_name": "phpunit/phpunit"}
  ]
}
```

**关键认知**：

1. **`laravel/laravel` ≠ `laravel/framework`**
   - 前者是项目脚手架（v12.10.0，写 `composer create-project` 时拿到的版本）
   - 后者是真正的框架内核（v12.58.0，决定你能用什么 API）
   - **`laravel_version` 字段返回的是 framework 版本**——这是写代码该参考的数字

2. **`roster_name` 字段**是 Boost 内部用来跟 Guidelines 对应的"包识别符"。`CLAUDE.md` 第 12-20 行那个版本对照表用的就是这些 `roster_name`：

```
- laravel/framework (LARAVEL) - v12     ← 大写是 roster_name
- laravel/boost (BOOST) - v2
```

3. **精确版本 vs Major 版本两本账**：
   - `application-info` 返回精确补丁号（`12.58.0` / `2.4.6`）—— 排错时用
   - `CLAUDE.md` 只锁 major（`v12` / `v2`）—— 写代码时用

**AI 应该什么时候调用**：

- ✅ 跨多个项目工作时，**新进一个项目第一件事**应该调它确认上下文
- ✅ 用户问"我这个 Laravel 是什么版本"时
- ✅ Boost 自身怀疑被 disable 时——这是最轻的健康检查

---

### 2.2 `database-connections`

**用途**：列出 `config/database.php` 里所有数据库连接的名字 + 默认用的哪个。

**参数**：无。

**真实调用结果**：

```json
{
  "default_connection": "sqlite",
  "connections": ["sqlite", "mysql", "mariadb", "pgsql", "sqlsrv"]
}
```

**关键认知**：

1. Laravel 12 默认有 **5 个驱动**——`mariadb` 是 Laravel 11+ 新加的（之前都靠 `mysql` 驱动复用）。如果你的项目实际是 MariaDB，**用 `mariadb` 驱动比 `mysql` 驱动更准确**（避免某些 SQL 语法兼容性问题）
2. **多数据库场景**（比如读写分离、多租户）：你会看到 `connections` 里出现 `mysql`、`mysql_read`、`mysql_write` 这种命名
3. `default_connection` 来自 `.env` 的 `DB_CONNECTION`

**AI 应该什么时候调用**：

- ✅ 写跨数据库迁移时（确认源/目标 driver）
- ✅ 看到 `database-query` 的 SQL 语法报错时（先确认是什么 driver，SQLite/MySQL/PostgreSQL 语法差很多）

---

### 2.3 `database-schema`

**用途**：返回项目里所有数据库表的结构（列名、类型、索引、外键）。

**参数**：

| 参数 | 类型 | 默认 | 作用 |
|---|---|---|---|
| `summary` | bool | `false` | true = 只列表名+列类型，省 60%+ token |
| `database` | string | 默认连接 | 指定连接名（多 DB 场景） |
| `filter` | string | — | 表名子串过滤（比如 `"posts"` 只返回 posts 相关表） |
| `include_column_details` | bool | `false` | 加 nullable/default/comment/auto_increment |
| `include_views` | bool | `false` | 包含视图 |
| `include_routines` | bool | `false` | 包含存储过程/函数/序列 |

**真实调用结果**（`summary=true`，节选）：

```json
{
  "engine": "sqlite",
  "tables": {
    "users": {
      "id": "integer", "name": "varchar", "email": "varchar",
      "email_verified_at": "datetime", "password": "varchar",
      "remember_token": "varchar", "created_at": "datetime", "updated_at": "datetime"
    },
    "posts": {
      "id": "integer", "title": "varchar", "slug": "varchar", "body": "text",
      "user_id": "integer", "published_at": "datetime",
      "created_at": "datetime", "updated_at": "datetime"
    }
  }
}
```

**关键认知**：

1. **永远先 `summary=true` 再决定要不要细看**——大项目（50+ 张表）默认输出可能上万 token
2. 看到陌生表先 `filter="users"` 单表细看，比一次性拉全量高效
3. `engine` 字段告诉你是 SQLite / MySQL / PostgreSQL——影响 SQL 写法

**AI 应该什么时候调用**：

- ✅ **写 migration / Eloquent Model 之前**——必须先看现有结构，避免外键类型不匹配
- ✅ 写 `Rule::unique('users', 'email')` 这种 validation 之前——确认表名+列名拼写

---

### 2.4 `database-query`

**用途**：在数据库上执行**只读** SQL 语句。

**参数**：

| 参数 | 类型 | 必填 | 作用 |
|---|---|---|---|
| `query` | string | ✅ | SQL 语句，**只允许 SELECT / SHOW / EXPLAIN / DESCRIBE** |
| `database` | string | — | 连接名 |

**真实调用 1：列出所有表的 CREATE 语句（SQLite 写法）**：

```sql
SELECT name, sql FROM sqlite_master
WHERE type='table' AND name NOT LIKE 'sqlite_%'
```

返回（节选）：

```json
[
  {
    "name": "posts",
    "sql": "CREATE TABLE \"posts\" (\"id\" integer primary key autoincrement not null, \"title\" varchar not null, \"slug\" varchar not null, \"body\" text not null, \"user_id\" integer not null, \"published_at\" datetime, \"created_at\" datetime, \"updated_at\" datetime, foreign key(\"user_id\") references \"users\"(\"id\") on delete cascade)"
  }
]
```

**真实调用 2：统计**：

```sql
SELECT 'users' AS tbl, COUNT(*) AS cnt FROM users
UNION ALL SELECT 'posts', COUNT(*) FROM posts
```

返回：

```json
[{"tbl": "users", "cnt": 6}, {"tbl": "posts", "cnt": 20}]
```

**安全约束**：

```
✅ SELECT name FROM users
✅ SHOW TABLES
✅ EXPLAIN SELECT * FROM posts
✅ DESCRIBE users
❌ INSERT INTO users (...)         ← 拒绝
❌ UPDATE users SET ...             ← 拒绝
❌ DELETE FROM users                ← 拒绝
❌ DROP TABLE users                 ← 拒绝
❌ ALTER TABLE users ADD ...        ← 拒绝
```

→ **这是 Boost 的核心安全设计：让 AI 不能误删数据**。如果你需要 AI 写入数据，让它生成 migration 或 seeder 让你审查后跑。

**AI 应该什么时候调用**：

- ✅ 调试时确认数据状态（"用户表里真的有 ID=1 的记录吗？"）
- ✅ 写复杂查询前 EXPLAIN 看执行计划
- ❌ **不要**用它做"AI 帮我跑迁移"这种事——那是 `php artisan migrate` 该做的

---

### 2.5 `read-log-entries`

**用途**：读 `storage/logs/laravel.log` 最近 N 条 PSR-3 格式日志（自动处理多行堆栈和 JSON 格式）。

**参数**：

| 参数 | 类型 | 必填 | 作用 |
|---|---|---|---|
| `entries` | int | ✅ | 读最近多少条（不是行数，是 log entries） |

**真实调用结果**（项目刚搭，未运行过）：

```
Log file not found at D:\workspace\cursor\laravel-boost\playground\storage\logs/laravel.log
```

→ **不是 bug，是诊断信息**：项目没运行过任何请求，log 文件根本没生成。

**AI 应该什么时候调用**：

- ✅ 用户说"我刚才报错了，帮我看看"
- ✅ 写完代码后想看自己的 `Log::info(...)` 有没有打出来

---

### 2.6 `last-error`

**用途**：拿最近一条错误的完整堆栈（自动从 log 提取最后一个 ERROR/CRITICAL 级别记录）。

**参数**：无。

**真实调用结果**（同样项目刚搭）：

```
Log file not found at D:\workspace\cursor\laravel-boost\playground\storage\logs/laravel.log
```

**AI 应该什么时候调用**：

- ✅ **500 错误的最快定位**——比让用户截图错误页给你看快
- ✅ 调试 queue job 失败时（Job 错误也写进 log）

---

### 2.7 `browser-logs`

**用途**：读浏览器 console 日志（需要前端集成 Pail 或类似机制写入服务端）。

**参数**：

| 参数 | 类型 | 必填 | 作用 |
|---|---|---|---|
| `entries` | int | ✅ | 读最近多少条 |

**真实调用结果**（没启动前端）：

```
No log file found, probably means no logs yet.
```

**AI 应该什么时候调用**：

- ✅ Inertia / Livewire 项目里前端报错时
- ❌ 纯 Blade 项目没多大用（前端 JS 错误浏览器自己看就行）

---

### 2.8 `get-absolute-url`

**用途**：把相对路径或命名路由转成完整 URL（基于 `.env` 的 `APP_URL`）。

**参数**：

| 参数 | 类型 | 互斥 | 作用 |
|---|---|---|---|
| `path` | string | path 或 route 二选一 | 相对路径，例 `"/posts/1"` |
| `route` | string | 同上 | 命名路由名，例 `"posts.show"` |

**真实调用 1**：

```json
// 输入: { "path": "/" }
"http://localhost"
```

**真实调用 2（命名路由）**：

```json
// 输入: { "route": "posts.show", "params": {"post": "hello"} }
"http://localhost/posts/hello"
```

**AI 应该什么时候调用**：

- ✅ 生成测试链接时（"给用户发邮件让他点这个链接"）
- ✅ 写文档/截图说明时（"访问 xxx 看效果"）

---

### 2.9 `search-docs` ⭐ Boost 杀手锏

**用途**：在 Boost 内置的 17000+ 条**版本化 Laravel 生态文档**里做语义搜索。

**参数**：

| 参数 | 类型 | 必填 | 作用 |
|---|---|---|---|
| `queries` | string[] | ✅ | 多个查询并发搜（一次调用搜多件事） |
| `packages` | string[] | — | 限定包，例 `["laravel/framework", "inertiajs/inertia-laravel"]` |
| `token_limit` | int | 默认 3000，最大 1M | 返回大小上限 |

**真实调用结果**（节选）：

```
输入: { "queries": ["eloquent relationship hasMany example", "form request validation rules"],
        "token_limit": 2000 }

输出:
## Validation Form Request Validation Creating Form Requests
Validation | laravel/framework@12.x | Source: https://github.com/laravel/docs/blob/12.x/validation.md
                ↑ 注意这里：版本精确到 12.x，不是模糊的"Laravel 文档"

[文档原文，含可运行的代码片段]
```

**关键认知**（**这是 Boost 价值的核心**）：

1. **每条结果都带版本标记 `@12.x`**——AI 拿到的是和你项目版本对齐的文档，不是训练数据里 Laravel 9/10/11/12 混合记忆
2. **每条结果都带 GitHub 源链接**——你可以点开看原文
3. **代码示例是文档原文**，不是 AI 凭印象写的
4. **支持 queries 数组并发搜索**——AI 不需要为查 2 件事调 2 次工具
5. `token_limit` 默认 3000 偏小，写复杂代码时建议调到 8000-15000

**AI 应该什么时候调用**：

- ✅ **写任何 Laravel 代码之前**——尤其是用某个 API 不确定语法时
- ✅ 看到用户用了过期语法时（先 search-docs 拿最新写法再纠正）
- ✅ 用户问"Laravel 12 里 X 怎么做"时

**没有 `search-docs` 会发生什么**：

→ AI 凭训练数据里混着的 Laravel 9 / 10 / 11 / 12 各版本写法瞎写，**容易写出"野路子"代码**（用过期 API、用已废弃的写法、混用版本特性）。这正是大纲第 17 章「Prompt 工程」的核心论点，详见 `docs/17-Prompt工程反例集.md`（待写）。

---

## 3. 工具协作流程（实战场景）

### 场景 A：从 0 建一个 CRUD（实测过）

```
1. application-info       看清当前 Laravel + 生态包版本
2. database-schema        看现有表结构（避免外键类型错配）
3. search-docs            查 Laravel 12 的 migration / Eloquent / Controller 最新语法
4. AI 生成 migration 文件
5. 用户跑 php artisan migrate
6. database-schema (filter) 验证新表创建成功
7. AI 生成 Model + Controller + Form Request + Policy + Blade 视图
8. 用户跑 php artisan serve
9. 浏览器测试
10. (出错时) last-error / read-log-entries 排错
```

### 场景 B：排查 500 错误

```
1. last-error             拿到错误堆栈
2. database-query (EXPLAIN ...) 如果是慢查询 / 数据问题
3. read-log-entries(entries=20) 看上下文（错误前发生了什么）
4. search-docs            查异常类的文档
5. AI 给出修复方案
```

### 场景 C：跨项目接活

```
1. application-info       第一发，确认上下文
2. database-connections   看数据架构
3. database-schema(summary=true) 看数据规模
4. read-log-entries(entries=5) 看项目近况是否健康
5. 然后才开始读代码
```

---

## 4. 大纲第 12 章修订建议（替换原表）

把大纲第 12 章原来那张 12 行的工具表替换为下表：

```markdown
### 第 12 章：Boost MCP 工具实战大全（Boost 2.4.6）

> 实测下 Boost 2.4.6 实际暴露 9 个工具。Boost 升级后可能新增。

| 工具名 | 分类 | 一句话作用 | 优先级 |
|---|---|---|---|
| `application-info` | 项目自省 | 读 PHP/Laravel 版本 + 已装包 | P0 |
| `database-connections` | 数据库 | 列所有数据库连接 | P1 |
| `database-schema` | 数据库 | 读所有表结构（含 summary 模式） | P0 |
| `database-query` | 数据库 | 执行只读 SQL（SELECT/SHOW/EXPLAIN） | P1 |
| `read-log-entries` | 日志诊断 | 读 laravel.log 多行条目 | P2 |
| `last-error` | 日志诊断 | 拿最近一条错误的完整堆栈 | P0 |
| `browser-logs` | 日志诊断 | 读浏览器 console 日志（需前端集成） | P2 |
| `get-absolute-url` | 路由 | 路径 / 命名路由 → 完整 URL | P3 |
| `search-docs` ⭐ | 文档 | 版本化 Laravel 生态文档语义搜索 | P0 |

**Boost 杀手锏 = `search-docs`**
- 17000+ 条版本化文档，每条带 `@12.x` 标签 + GitHub 源链接
- AI 不再凭训练数据混合版本瞎写
- 支持 queries 数组并发搜索

**安全约束**
- `database-query` 只允许 SELECT / SHOW / EXPLAIN / DESCRIBE
- 写操作必须由 AI 生成 migration / seeder，由开发者审查后手动跑

**实战场景**
- 从 0 建 CRUD：application-info → database-schema → search-docs → ...
- 排查 500：last-error → database-query → read-log-entries → search-docs
```

---

## 5. 学习心得（对 ThinkPHP 老手特别说）

**这些工具的真正价值不是"AI 帮我跑命令"，而是"让 AI 看清楚我的项目"**。

ThinkPHP 转 Laravel 时最大的痛点是 AI 写出来的代码"长得像 Laravel"但实际跑不通——因为 AI 不知道：

- 你这个项目用的是 Laravel 几（12 vs 11 写法差异巨大）
- 你的 users 表 id 是 `integer` 还是 `bigInteger`（影响外键类型）
- 你装了哪些生态包（用了 Sanctum 没？用了 Inertia 没？）
- 你的 `.env` 怎么配的（`APP_URL` 是什么？数据库连什么？）

**有了这 9 个工具，AI 的"瞎猜"被堵死**——这才是 Boost 真正的差异化竞争力。

**单看其中一个 `search-docs`**，就足以让 AI 写出来的 Laravel 代码质量上一个台阶。这就是大纲第 17 章会重点展开的核心命题。

---

## 6. 实测 checklist

- [x] 9 个工具每个调用过至少 1 次
- [x] 每个工具记录了真实输入参数和输出 JSON
- [x] 区分了"有真实输出"vs"输出为空但属正常"两种情况
- [x] `search-docs` 的"版本化文档"特性真实验证
- [x] `database-query` 的只读约束验证
- [x] 大纲第 12 章修订建议给出
- [ ] **更新大纲文件**（`Laravel_Boost_教程大纲.md` 第 113-126 行的工具表）—— 留给最后一篇文档统一处理
