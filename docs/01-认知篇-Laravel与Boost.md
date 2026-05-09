# 第 1-2 章 认知篇 · 为什么是 Laravel + Boost

> **本章定位**：这是教程的"为什么"章节——回答"我为什么要从 ThinkPHP 转 Laravel？"和"为什么要装 Boost？"。
>
> 读完本章后，你应该有一个**清晰的判断**：**值不值得花一个月学 Laravel + Boost**。
>
> **不值得读这章**的人：你已经决定要学了，直接跳到 [`03-环境搭建实测.md`](./03-环境搭建实测.md)。
>
> **值得读这章**的人：还在评估，或者想把这套技术栈推荐给团队，需要"理论弹药"。

---

## 0. 序章：你拿在手里的这本书是什么

### 这本教程的"卖点"用一句话表达

> **这是为 ThinkPHP 老手写的 Laravel + AI 实战教程**——通过 7 篇真实战笔记（约 9000 行）展示如何用 Boost 让 AI 在 Laravel 项目里写出接近老手水准的代码。

### 它解决你 3 个真实痛点

| 痛点 | 教程的回答 |
|---|---|
| "ThinkPHP 老项目想用 Laravel 重写，但不知道工作量" | 看第 13 章——**2 小时博客实战**有完整的耗时/行数/测试数 |
| "AI 写 Laravel 代码总是用过时的 v9/v10 API" | 看第 17 章 + Boost 的 `application-info` + `search-docs` |
| "网上 Laravel 教程很多，但都是单点的，缺一个完整学习路径" | 看 [`00-教程导读.md`](./00-教程导读.md)——5 条学习路径，从 5 分钟到 1 周 |

### 它**不**适合谁

- ❌ **零基础新手**——假设你写过 PHP / 用过 ThinkPHP（5/6/8 都行）
- ❌ **想学 Laravel 底层原理**的人——本教程偏实战，Service Container / Provider 一笔带过
- ❌ **想学前端 SPA**的人——前端只用 Tailwind CDN + Blade，**0 npm**

### 它适合谁

- ✓ 用过 ThinkPHP（任何版本）想转 Laravel
- ✓ 已经在用 Cursor / Claude Code，想让 AI 写代码更靠谱
- ✓ 团队 leader，想推动技术栈升级
- ✓ 想做 SaaS / API / 后台 / 队列异步等"现代 PHP 项目"

### 数字证据

```
9 篇笔记      = 约 25 万字
playground/   = 实战项目（每行代码都跑过）
63 个 Pest 测试 = 0.84-2.79 秒跑完
6 个 Sprint × 4 章 = 详细到分钟的实战记录
12 个真实踩坑   = 含完整诊断 + 修复路径
```

→ 这不是"理论书"，是**手把手实战录**。

---

## 第 1 章：Laravel 是什么，和 ThinkPHP 有何区别

### 1.1 ThinkPHP 老手转 Laravel 的 5 个心理障碍

> 这一节先给你"心理准备"——列出 ThinkPHP 老手最容易遇到的 5 个**认知阻力**，让你提前知道"你以为的 != Laravel 的标准做法"。

### 障碍 1：ThinkPHP 用"数组"，Laravel 用"对象"

| ThinkPHP | Laravel |
|---|---|
| `Db::name('posts')->select()` 返回**关联数组列表** | `Post::all()` 返回 **Eloquent 模型集合** |
| `$row['title']`（字符串 key） | `$post->title`（属性访问） |
| `array_map` / `array_filter` 处理 | Collection 链式调用：`->map(fn ($p) => ...)->filter(...)` |

→ ThinkPHP 习惯思维：**SQL 思维**（你在脑子里画表）。
→ Laravel 习惯思维：**OO 思维**（你在脑子里画类）。

**解决心法**：从今天起把 `$post`**当对象看**，不再当数组看。`$post->author->name` 比 `$user_row['name']` 更接近自然语言。

### 障碍 2："Eloquent 是不是就是个 ORM"

不是。Eloquent 是**ORM + Active Record + 业务逻辑容器**：

```php
class Post extends Model
{
    protected $fillable = ['title', 'slug', 'body', 'user_id', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function author(): BelongsTo                    // 关系
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder // 业务条件
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isDraft(): bool                         // 业务方法
    {
        return $this->published_at === null;
    }
}
```

→ ThinkPHP 的 Model 主要做"映射 + 简单访问器"。
→ Laravel 的 Eloquent Model **同时承载** "字段映射 + 关系 + 业务条件 + 业务方法 + 类型转换"。

**解决心法**：把 Model 当成"一个领域概念的所有相关代码的家"。

> 实证：[第 13 章 §2.2 Post Model 的 4 个老手特征](./13-博客实战完整复盘.md)

### 障碍 3："约定优先"是束缚，不是自由

ThinkPHP 文化偏"灵活配置"——你能把 controller 放任意目录、用任意命名。
Laravel 文化偏"约定优先"——controller 必须在 `app/Http/Controllers/`，必须叫 `XxxController`。

**ThinkPHP 老手第一反应**：约定 = 束缚。
**实际**：约定 = AI 协作的核心红利。

| 没有约定时（ThinkPHP） | 有约定时（Laravel） |
|---|---|
| AI 不知道 controller 该放哪 | AI 默认放 `app/Http/Controllers/` |
| AI 不知道路由该叫什么 | AI 默认 `posts.show` 这种 dot.notation |
| AI 不知道权限该怎么判 | AI 默认建 Policy 类 |
| AI 不知道验证该写哪 | AI 默认建 Form Request 类 |

→ **约定 = AI 的"默认值"**——AI 在 Laravel 里能写出靠谱代码，**很大程度因为约定让它无处可错**。

**解决心法**：放弃"灵活"，拥抱"约定"。

### 障碍 4："Laravel 的 magic 让我看不懂"

Laravel 处处是 magic：

```php
$post->author->name              // 怎么查的 user 表？
@can('update', $post)            // 哪个文件检查的？
StorePostRequest $request        // 验证什么时候跑的？
->dispatch($post)                // job 怎么入队的？
```

→ ThinkPHP 老手不习惯 — "我看不到代码执行路径，怎么 debug？"

**实际**：这些 magic 都是**约定**，**记住约定后比"显式"快得多**。

| 看似 magic 的事 | 实际约定 |
|---|---|
| `$post->author` 自动 join | 因为 Model 里有 `author()` 方法返回 BelongsTo |
| `@can('update', $post)` 自动调 PostPolicy | 因为类名约定 `Post` → `PostPolicy` |
| Form Request 自动验证 | 因为路由解析器看到参数类型 `StorePostRequest` 时自动 inject + validate |
| `dispatch($post)` 自动入队 | 因为 Job 类 `implements ShouldQueue` |

**解决心法**：每个 magic 背后都是一个简单约定。**学约定，不要学 magic 的内部实现**。

> 实证：[06-11 章节速查](./06-11-章节速查.md)——把 7 个核心抽象的约定全部梳理成速查表。

### 障碍 5："我从来不写测试，Laravel 的测试文化让我焦虑"

ThinkPHP 项目里你大概率不写测试。Laravel 默认装 **Pest**（一个超优雅的测试框架），**63 个测试 0.84 秒跑完**。

**ThinkPHP 老手第一反应**："写测试不是浪费时间吗？写功能都来不及。"

**实际**：测试是 Laravel 体验里**最让人上瘾**的部分。3 个真实收益：

| 收益 | 实例 |
|---|---|
| **改代码不慌** | 改 PostController 后跑 `php artisan test`，2 秒确认没破坏现有功能 |
| **AI 协作可信** | AI 改完代码自己跑测试自检——你不用每次手工验收 |
| **业务规则被钉死** | 加 `before()` hook 后写测试钉死"不污染普通规则"——一年后有人改坏立刻发现 |

**解决心法**：把"写测试"看成"花 5 分钟省未来 5 小时"，不是"加班"。

> 实证：[第 13 章 §6 测试自带 buff](./13-博客实战完整复盘.md)——AI 在没要求的情况下主动写了 31 个测试。
> [第 16 章 §7](./16-Filament后台实战.md)——5 个测试钉死 admin 权限边界。

### 5 个障碍的应对总结

```
障碍                    应对心法
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. 数组 → 对象          把 $post 当对象，不当数组
2. Eloquent Model       把 Model 当"领域概念的家"
3. 约定 vs 灵活         放弃灵活，拥抱约定
4. magic 看不懂         学约定，不学 magic 内部
5. 测试焦虑             5 分钟测试 = 未来省 5 小时
```

→ 这 5 个心法**一旦内化**，转 Laravel 就只剩**纯粹的语法 / API 学习**——通常 2-3 周。

---

### 1.2 一图对比：ThinkPHP vs Laravel 设计哲学

### 设计哲学的根本差异

```
┌────────────────────────────────┬────────────────────────────────┐
│         ThinkPHP                │         Laravel                │
├────────────────────────────────┼────────────────────────────────┤
│ "PHP 框架"                      │ "用 PHP 写的 Ruby on Rails"    │
│                                 │                                │
│ 设计目标：                       │ 设计目标：                      │
│ 让 PHP 工程师习惯的方式工作        │ 用 PHP 实现 Rails 的最佳实践     │
│                                 │                                │
│ 核心理念：                       │ 核心理念：                      │
│ Convention OR Configuration     │ Convention OVER Configuration  │
│ （灵活配置）                      │ （约定优先）                     │
│                                 │                                │
│ 数据访问：                       │ 数据访问：                      │
│ Db::name() → 关联数组            │ Eloquent → 模型对象             │
│                                 │                                │
│ 业务逻辑：                       │ 业务逻辑：                      │
│ 散在 Controller / Service       │ 集中到 Model / Policy / Action  │
│                                 │                                │
│ 路由：                           │ 路由：                         │
│ 字符串 'Hello/index'             │ 类引用 [HelloController::class] │
│                                 │                                │
│ 测试：                           │ 测试：                         │
│ 几乎不写                         │ Pest 默认装，0.8 秒跑完          │
│                                 │                                │
│ 部署：                           │ 部署：                         │
│ 自己写脚本                       │ Forge / Vapor / Cloud 一键      │
└────────────────────────────────┴────────────────────────────────┘
```

### 一句话总结

> **ThinkPHP 是"PHP 框架"，Laravel 是"用 PHP 写的 Ruby on Rails"。**
>
> 你不会觉得 ThinkPHP "magic"，但 Laravel 处处是 magic。
> 这些 magic 是**约定**——记住约定后比"显式"快得多。

### 谁更适合什么场景

| 场景 | ThinkPHP | Laravel |
|---|---|---|
| 中国本地化项目（含微信支付/短信等） | ✓ 生态成熟 | ⚠️ 需要找第三方包 |
| 学校教学 / 入门 PHP | ✓ 中文文档丰富 | ⚠️ 英文文档为主 |
| 老项目维护 | ✓ 已有代码 | ❌ 重写成本高 |
| 新项目 SaaS / API / 后台 | ⚠️ 自己拼装 | ✓ Cashier / Sanctum / Filament 等套件 |
| 团队希望提高代码质量 | ⚠️ 缺测试文化 | ✓ Pest / Static Analysis |
| 想用 AI 协作开发 | ⚠️ 训练数据少 | ✓ 训练数据多 + Boost 加持 |

→ **本教程的核心论点**：**新项目优先 Laravel**，老项目按需重写。

---

### 1.3 目录结构对比

### ThinkPHP 6 默认目录

```
ThinkPHP 项目/
├── app/
│   ├── controller/        # 控制器
│   ├── model/             # 模型
│   ├── middleware/        # 中间件
│   ├── service/           # 业务服务（自己建）
│   └── validate/          # 验证类
├── config/                # 配置
├── route/                 # 路由
├── view/                  # 视图（按 controller 分目录）
└── public/                # 入口 + 静态资源
```

### Laravel 12 默认目录

```
Laravel 项目/
├── app/
│   ├── Http/
│   │   ├── Controllers/                    # 控制器
│   │   │   ├── PostController.php
│   │   │   └── Api/                        # API 子命名空间（约定）
│   │   │       └── PostController.php
│   │   ├── Requests/                       # Form Request 验证类
│   │   │   ├── StorePostRequest.php
│   │   │   └── UpdatePostRequest.php
│   │   ├── Middleware/                     # 中间件
│   │   └── Resources/                      # API Resource（JSON 序列化）
│   │       └── PostResource.php
│   ├── Models/                             # Eloquent 模型
│   │   ├── Post.php
│   │   └── User.php
│   ├── Policies/                           # 权限策略
│   │   └── PostPolicy.php
│   ├── Jobs/                               # 队列任务
│   │   └── SendPostPublishedEmailJob.php
│   ├── Mail/                               # 邮件类
│   │   └── PostPublishedMail.php
│   ├── Filament/                           # Filament 后台资源（如装了）
│   │   └── Resources/Posts/
│   └── Providers/                          # 服务提供者
├── bootstrap/app.php                       # 应用 bootstrap（v11+ 中间件/异常配置）
├── config/                                 # 配置
├── database/
│   ├── migrations/                         # 数据库迁移
│   ├── factories/                          # Factory（造测试数据）
│   └── seeders/                            # Seeder（填充初始数据）
├── routes/
│   ├── web.php                             # web 路由
│   ├── api.php                             # API 路由（v11+ 用 install:api 才创建）
│   └── console.php                         # CLI 命令
├── resources/
│   └── views/                              # Blade 视图
│       ├── components/                     # Blade 组件
│       └── emails/                         # 邮件模板
├── tests/
│   ├── Feature/                            # 功能测试
│   │   ├── PostManagementTest.php
│   │   └── Api/PostApiTest.php
│   └── Unit/                               # 单元测试
└── public/                                 # 入口 + 静态资源
```

### 关键差异 5 处

| 差异 | ThinkPHP | Laravel | 影响 |
|---|---|---|---|
| 1. 控制器位置 | `app/controller/` | `app/Http/Controllers/` | 必须严格遵守约定 |
| 2. 验证类 | `app/validate/`（独立） | `app/Http/Requests/`（HTTP 子目录） | Laravel 的验证是 HTTP 层职责 |
| 3. 测试目录 | 通常没有 | `tests/Feature/` + `tests/Unit/` | 测试是一等公民 |
| 4. 数据库结构演进 | 自己写 SQL | `database/migrations/` Schema Builder | Migration 是必备 |
| 5. 业务规则归属 | controller 里写 if | `app/Policies/` 独立 Policy 类 | 权限抽象成业务对象 |

→ **Laravel 的目录结构本身就是设计**——每个目录对应一个抽象层。
→ ThinkPHP 老手最常见的错误：**把 Controller 当万能工具**，所有逻辑都塞 Controller。Laravel 让你把代码**散到正确的抽象层**。

> 实证：[06-11 §0](./06-11-章节速查.md)——"改一个功能要改哪些文件"对照表。

---

### 1.4 7 个核心抽象的对照（实证）

> 这一节给出 7 个核心抽象的**逐项对照**——每条都附"实证链接"指向具体笔记。

### 抽象 1：路由

| ThinkPHP 6 | Laravel 12 |
|---|---|
| `Route::rule('hello', 'Hello/index')` | `Route::get('/hello', [HelloController::class, 'index'])` |
| 字符串引用 controller | 类引用 controller（重构友好） |
| `Route::resource('blog', 'Blog')` | `Route::resource('blog', BlogController::class)` |
| 路由参数：闭包 | **路由模型绑定**：`function (Post $post)` 自动注入 |
| 命名路由：可选 | 命名路由：广泛使用，`route('posts.show', $post)` |

→ Laravel 的"类引用"+"模型绑定"让重构无忧——改类名 / 字段名 IDE 直接重命名。
→ 实证：[06-11 §6](./06-11-章节速查.md)

### 抽象 2：Eloquent ORM vs ThinkPHP Model

```php
// ThinkPHP
$posts = Db::name('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('posts.user_id', 1)
    ->whereNotNull('posts.published_at')
    ->orderBy('posts.published_at', 'desc')
    ->limit(10)
    ->select('posts.*', 'users.name as author_name')
    ->select();

foreach ($posts as $row) {
    echo $row['title'] . ' - ' . $row['author_name'];
}
```

```php
// Laravel
$posts = Post::published()
    ->ofAuthor(1)
    ->with('author')
    ->latest('published_at')
    ->take(10)
    ->get();

foreach ($posts as $post) {
    echo $post->title . ' - ' . $post->author->name;
}
```

| 维度 | ThinkPHP | Laravel |
|---|---|---|
| 起点 | 表 (`Db::name('posts')`) | 模型 (`Post::`) |
| 条件 | where 链 | scope (`published()`) |
| 关联 | join + select | with(关系) |
| 调用 | `$row['author_name']`（字符串） | `$post->author->name`（对象） |
| 复用 | 复制 SQL 片段 | 复用 scope |

→ 实证：[06-11 §7](./06-11-章节速查.md) | [13 章 §2.2](./13-博客实战完整复盘.md)

### 抽象 3：Migration vs 自己写 SQL

```php
// Laravel Migration
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title', 200);
    $table->string('slug')->unique();
    $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
});
```

→ DSL 屏蔽 SQL 方言差异——同代码跑 SQLite / MySQL / PostgreSQL。
→ Migration **是数据库版本控制的代码化**——回滚、重建、CI 都靠它。
→ 实证：[06-11 §8](./06-11-章节速查.md) | [13 章 §2.1](./13-博客实战完整复盘.md)

### 抽象 4：Form Request vs Validate 类

```php
// ThinkPHP（在 controller 内联）
public function store(Request $request) {
    $validate = new \app\validate\Post;
    if (!$validate->check($request->post())) {
        return ['error' => $validate->getError()];
    }
    // ...
}

// Laravel（type-hint 自动注入）
public function store(StorePostRequest $request) {
    $post = Post::create($request->validated());
    return redirect()->route('posts.show', $post);
}
```

| 优势 | 解释 |
|---|---|
| 验证逻辑独立类 | 可独立单测 |
| 自动注入 + 自动验证 | controller 拿到的就是验证过的数据 |
| Web/API 自动适配 | 同一段规则同时支持 redirect back 和 JSON 422 |
| `Rule::unique()->ignore()` 等高阶规则 | 编辑场景不撞自己 |

→ 实证：[06-11 §9](./06-11-章节速查.md) | [13 章 §3.2](./13-博客实战完整复盘.md)

### 抽象 5：Policy vs 散写 if

```php
// ThinkPHP（散在 controller）
public function update(Post $post) {
    if ($post->user_id !== session('user_id')) {
        abort(403);
    }
    // ...
}

// Laravel（独立 Policy 类）
class PostPolicy {
    public function update(User $user, Post $post): bool {
        return $user->id === $post->user_id;
    }
}

// Controller
public function update(UpdatePostRequest $request, Post $post) {
    $this->authorize('update', $post);
    // ...
}

// Blade
@can('update', $post)
    <a href="...">Edit</a>
@endcan
```

| 优势 | 解释 |
|---|---|
| 权限**抽象成业务对象** | 可独立单测 |
| **跨端复用** | web/API/视图/Filament 后台共用 |
| **`before()` hook** | 一处改写让 admin 在所有地方 bypass |
| **`?User` 类型签名表达"游客可访问"** | 类型即规则 |

→ 实证：[16 章 §6 PostPolicy before() hook](./16-Filament后台实战.md)

### 抽象 6：Blade vs 模板字符串

| ThinkPHP | Laravel |
|---|---|
| `{$var}` | `{{ $var }}` |
| `{volist name="..." id="vo"}` | `@foreach (... as $vo)` |
| `{include file="..."}` | `@include(...)` 或 `<x-component>` |
| `{:url('xxx')}` | `{{ route('xxx') }}` |
| 没有"组件"概念 | `<x-layout>` `<x-posts.form-fields :post="$post">` |

→ Laravel Blade 组件 + props + 插槽 = 真正的"视图组件化"，不是 partial 拼贴。
→ 实证：[06-11 §10](./06-11-章节速查.md) | [13 章 §4](./13-博客实战完整复盘.md)

### 抽象 7：Pest 测试 vs 没有测试

```php
// Laravel + Pest
it('forces the authenticated user as the author', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    actingAs($user)->post(route('posts.store'), [
        'title' => 'Hijack', 'slug' => 'h', 'body' => 'x',
        'user_id' => $other->id,                              // attacker tries
    ]);

    $post = Post::where('slug', 'h')->firstOrFail();
    expect($post->user_id)->toBe($user->id);                  // assert real author
});
```

→ Pest 用**函数式语法 + 闭包**，比 PHPUnit 优雅得多。
→ 0.84 秒跑完 63 个测试——**改代码先跑测试**变得不再痛苦。
→ 实证：[13 章 §6](./13-博客实战完整复盘.md)

### 7 个对照的总结表

| # | 抽象 | ThinkPHP 心态 | Laravel 心态 |
|---|---|---|---|
| 1 | 路由 | 字符串路径 | 类引用 + 模型绑定 |
| 2 | Eloquent | 数据访问层 | "领域概念的家"（关系 + scope + 业务方法） |
| 3 | Migration | 自己写 SQL | DSL + 版本控制 |
| 4 | Form Request | controller 内联 | 独立类 + 自动注入 |
| 5 | Policy | 散写 if | 第一类对象 + 跨端复用 |
| 6 | Blade | 模板字符串 | 视图组件化 |
| 7 | Pest | 不写测试 | 测试是开发流程一部分 |

→ **掌握这 7 个对照 = 转 Laravel 完成 80%**。剩下 20% 是 API 细节（边用边查）。

---

### 1.5 为什么 Laravel 的约定性更适合 AI 辅助开发

> 这一节是**整本教程最重要的论点**——为什么本教程不是"教 Laravel"，而是"教 Laravel + AI 协作"。

### 论点：约定越多 = AI 出错概率越小

考虑下面这个 prompt：

```
帮我做一个评论功能
```

| 场景 | 没有约定时 | 有约定时（Laravel） |
|---|---|---|
| controller 放哪 | 任意 | `app/Http/Controllers/CommentController.php` |
| 路由怎么写 | 自由发挥 | `Route::resource('comments', CommentController::class)` |
| 路由名 | 自由发挥 | `comments.index` / `comments.show` |
| 字段验证放哪 | 自由发挥 | `app/Http/Requests/StoreCommentRequest.php` |
| 权限检查放哪 | 自由发挥 | `app/Policies/CommentPolicy.php` |
| 视图放哪 | 任意 | `resources/views/comments/index.blade.php` |

→ **每个"自由发挥"都是错误源**——AI 可能选 A，你期望 B，结果不一致。
→ Laravel 的约定让 AI 默认有"标准答案"——80% 场景下 AI 不需要"想"，**直接按约定写**。

### 论点：Laravel 约定 = AI 训练数据"集中"

| 框架 | GitHub 项目数（公开） | AI 训练样本量 |
|---|---|---|
| Laravel | ~500K+ | 大 |
| Symfony | ~200K | 中 |
| ThinkPHP | ~100K（中文为主） | 偏小 + 中文混合 |
| CodeIgniter | ~50K | 小 |

→ Laravel 项目**结构相似**（都遵循约定），AI 训练时**模式更集中**——它学到的"Laravel 写法"质量比 ThinkPHP 写法高得多。

→ 实证：让 GPT-4 / Claude 用 ThinkPHP 写代码 vs 用 Laravel 写代码——**Laravel 输出明显更标准**。

### 论点：约定让 AI 能用"语义化命名"

```php
$post->author->name           // ⭐ Laravel 让你这么写
$post['user']['name']         // ThinkPHP 你大概率这么写
$post->user_name              // 或者这样（自己写 join 后）
```

`author` vs `user` 是个微妙但重要的差异：

| 命名 | 语义 |
|---|---|
| `$post->user` | "这个 post 的 user" — 不太自然 |
| `$post->author` | "这个 post 的 author" — 自然 |

→ Laravel 的关系命名灵活（方法名 ≠ 字段名前缀只需要传第二个参数），让你能**用业务词汇**而不是技术词汇。
→ AI 看到 `$post->author->name` **比 `$post->user_name` 更容易理解上下文**。

→ 实证：[13 章 §2.2](./13-博客实战完整复盘.md)——AI 主动选了 `author` 命名而不是 `user`。

### 论点：约定让"代码 review"自动化

老手 review 代码时大脑里有一张"应该这样写"的清单：
- 验证应该用 Form Request，不应该在 controller 内联
- 权限应该用 Policy，不应该散写 if
- 时间字段应该 cast 为 datetime
- 关系应该 eager-load 防 N+1

**这张清单就是 Boost 的 AI Guidelines**——它把老手的 review 清单**编码成 markdown**，喂给 AI，让 AI 写代码时**自我 review**。

→ ThinkPHP 没有等价物——因为它没有这种"标准答案"。

### 论点：测试文化让 AI 协作可靠

ThinkPHP 项目里 AI 写完代码你只能"目测 + 浏览器试一下"。
Laravel 项目里 AI 写完代码可以**自动跑 Pest 测试**——测试通过 = 代码确实可工作。

```
传统流程：AI 写代码 → 你手工测 → 发现 bug → 跟 AI 沟通 → AI 改 → 你再测...

Laravel + AI：AI 写代码 → AI 自己跑 php artisan test → 看到结果 → 自己修复 → 报告
```

→ **测试 = AI 的"自我验证"工具**。Boost + Pest + Laravel 这个组合让 AI 协作真的能"放手让它做"。

→ 实证：[13 章 §6](./13-博客实战完整复盘.md)——AI 主动写了 31 个 Pest 测试，没要求过。

### 总结：Laravel + AI 是"天作之合"

```
约定优先 → AI 默认按约定 → 减少"自由发挥"错误
集中训练数据 → AI 输出更标准
语义化命名 → AI 上下文理解更好
Guidelines + Skills → 老手 review 清单自动化
Pest 测试 → AI 可自我验证
```

→ **这 5 点叠加，让 Laravel + AI + Boost 的开发体验远超 ThinkPHP**。
→ 这就是这本教程的论点：**对 AI 协作时代，Laravel 的回报是 ThinkPHP 的几倍**。

---

## 第 2 章：Laravel Boost 的三大核心能力

### 2.1 Laravel Boost 是什么（30 秒读完）

### 一句话定义

> **Laravel Boost** = Laravel 官方发布的 **MCP Server 工具包**，让 AI 助手在 Laravel 项目里有"老手意识"。

### 拆解这句话

| 关键词 | 含义 |
|---|---|
| **Laravel 官方** | `laravel/boost` 包，由 Taylor Otwell 团队维护，不是社区项目 |
| **MCP Server** | Model Context Protocol——Anthropic 提出的"AI 与外部工具通信"协议 |
| **工具包** | 不是单个工具，是 9 个工具 + Guidelines + Skills 的组合 |
| **老手意识** | AI 知道"该用什么 API、该按什么风格、该自检什么" |

### 装它有多简单（一句话）

```bash
composer require laravel/boost --dev
php artisan boost:install
```

→ 装完不修改你的 Laravel 项目代码——**只生成 markdown 文件 + 注册 MCP**。
→ 卸载也只需要 `composer remove laravel/boost --dev`，0 残留。

### 用它有多简单（一句话）

让你的 AI 编辑器（Cursor / Claude Code 等）连上 MCP，**任何 prompt 里 AI 自动会用 Boost 工具**。

不需要：
- ❌ 改你的 controller / model / migration
- ❌ 改你的 composer.json 之外的任何东西
- ❌ 改你的 prompt 写法（Boost 自己会被 AI 调用）

### 它**不是**什么

- ❌ 不是一个 IDE 插件（它是 MCP Server，IDE 通过 MCP 协议连接）
- ❌ 不是 Laravel 框架的一部分（独立 dev 依赖）
- ❌ 不是要替代你的 AI 编辑器（它是给 AI 用的工具）

### 它的价值定位

```
没装 Boost：AI 凭训练数据写 Laravel 代码（可能用 v9/v10 旧 API）
装了 Boost：AI 调工具确认现状 + 拿最新文档 + 按 Guidelines 风格写
```

→ **Boost = 给 AI 装上"Laravel 老手的眼睛和习惯"**。

---

### 2.2 三大核心能力详解

### 能力 1：MCP 工具集（9 个）

> 让 AI **调用工具**确认项目状态，而不是凭训练数据猜。

#### 9 个工具按使用频率（实测）

| # | 工具 | 实测使用频率 | 真实场景 |
|---|---|---|---|
| 1 | `application-info` | ⭐⭐⭐⭐⭐ | 确认 Laravel 版本，避免 AI 用 v9 API |
| 2 | `database-schema` | ⭐⭐⭐⭐⭐ | 看现有表结构，写新 migration 时不瞎猜 |
| 3 | `search-docs` | ⭐⭐⭐⭐⭐ | 查最新版本官方文档（17K+ chunks） |
| 4 | `database-query` | ⭐⭐⭐⭐ | 验证 seed 数据、debug 查询 |
| 5 | `tinker` | ⭐⭐⭐⭐ | 试 model / 关系 / scope |
| 6 | `read-log-entries` | ⭐⭐⭐ | debug 队列失败时看日志 |
| 7 | `last-error` | ⭐⭐⭐ | 快速看最近 error |
| 8 | `database-connections` | ⭐⭐ | 多 DB 切换 |
| 9 | `browser-logs` / `get-absolute-url` | ⭐⭐ | 浏览器测试 debug |

#### 实战场景（一个 prompt 触发多个工具）

prompt：
```
帮我建 posts 表的 migration，外键关联 users
```

AI 在 Boost 加持下做的事：
```
1. 调 application-info → 知道是 Laravel 12 → 用 v12 写法
2. 调 database-schema('users') → 看到 id 是 integer → 决定 user_id 类型
3. 调 search-docs('foreignId constrained') → 拿 v12 文档
4. 写出代码：
   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

→ **AI 不假设、不猜——它调工具确认**。
→ 实证：[12 章](./12-Boost工具实战大全.md)——9 个工具的真实参数 / 输出 / 用例。

### 能力 2：AI Guidelines（版本化的 Laravel 规范）

> Boost 安装时**自动生成 markdown 文件**，描述"在这个项目里，代码应该怎么写"。

#### Guidelines 文件位置

```
.ai/guidelines/
├── controllers.md       # 用 HasMiddleware 接口、用 Form Request
├── eloquent.md          # 优先 scope、永远 eager load 关系
├── auth.md              # 用 Sanctum、用 Policy
├── testing.md           # 用 Pest、所有 PR 必有测试
├── api.md               # 用 JsonResource、状态码语义化
├── livewire.md          # （如果项目用 Livewire）
└── filament.md          # （如果项目用 Filament）
```

#### 一段示例（`testing.md`）

```markdown
# Testing Guidelines

- Always write tests for new features.
- Use Pest, not raw PHPUnit.
- Use `Queue::fake()` / `Mail::fake()` instead of letting jobs/mails actually run.
- Use Eloquent factories for test data; never insert raw SQL.
- Test files mirror source: app/Http/Controllers/PostController → tests/Feature/PostControllerTest
```

→ AI 看到这段，**就知道项目里测试要怎么写**。
→ 比起在每个 prompt 里都说"用 Pest 写测试"——**Guidelines 让规范一次写好永久生效**。

#### 版本化的关键

Boost 的 Guidelines 内容是**版本化的**——它知道你装的 Laravel 是 11 还是 12，**只展示当前版本相关的规范**。

→ Laravel 11 项目里 AI 不会写 `__construct + $this->middleware()`（这是 v10 的写法）。
→ Laravel 12 项目里 AI 不会写 `protected $casts = [...]`（已被 `casts()` 方法替代）。

### 能力 3：Skills 体系（按需激活的"主动行为"）

> Skills = "更主动的 Guidelines"——让 AI **主动**做一些事，而不是等你在 prompt 里要求。

#### Skill 文件示例

```
.ai/skills/
├── always-add-tests.md
├── always-eager-load.md
├── prefer-scope-over-where.md
└── ...
```

#### 实证：13 章博客实战中 Skills 触发的"惊喜"

我**没在 prompt 里要求** AI 做的事，但 AI **主动**做了：

| AI 主动做的事 | 触发的 Skill |
|---|---|
| 给 Post 加 `getRouteKeyName() = 'slug'` | `prefer-slug-routing.md` |
| 给查询自动加 `with('author')` | `always-eager-load.md` |
| 写了 31 个 Pest 测试 | `always-add-tests.md` |
| Policy 用 `?User` 类型签名区分游客 | `auth-typing.md` |

→ **Skills 把老手的 "好习惯" 编码成 markdown**，让 AI 默认遵循。
→ 实证：[13 章 §7 复盘 ✓](./13-博客实战完整复盘.md)——8 个老手代码点对应的 Skills 触发。

### 三大能力的协作关系

```
┌──────────────────────────────────────────────┐
│  AI 收到 prompt                                │
│  "帮我做评论功能"                              │
└──────────────────┬───────────────────────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  Skills 触发主动行为   │
        │  "记得写测试"          │
        │  "记得 eager load"    │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  Guidelines 提供规范   │
        │  "用 HasMiddleware"   │
        │  "用 Form Request"    │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  调 MCP 工具确认现状   │
        │  application-info     │
        │  database-schema      │
        │  search-docs          │
        └──────────┬───────────┘
                   │
                   ▼
        ┌──────────────────────┐
        │  写出"老手风格"代码     │
        └──────────────────────┘
```

→ **三件事不是独立的**——它们组合成一个完整的"AI 老手意识"系统。

---

### 2.3 Boost 让 AI 输出质量提升多少（实测对照）

> 这一节给"实证"——展示**没装 Boost vs 装了 Boost**的真实对比。

### 对比 1：写 Migration

#### 没装 Boost

prompt: `帮我建 posts 表的 migration，外键关联 users`

AI 输出（基于训练数据猜测，可能 Laravel 9）：

```php
$table->bigInteger('user_id')->unsigned();
$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
```

→ ⚠️ 4 个问题：
1. 没看 users 表实际字段类型（万一是 integer 不是 bigInteger 会失败）
2. 用了 v9 的老语法（v12 推荐 `foreignId().constrained()`）
3. 没加 `published_at` 字段（你的需求暗示要"发布"概念）
4. 没生成 Model / Factory / Seeder（migration 没 Model 用不了）

#### 装了 Boost

AI 输出：

```php
// 1. 调 application-info：确认 Laravel 12
// 2. 调 database-schema('users')：看到 id 是 integer
// 3. 调 search-docs('foreignId constrained')：拿 v12 文档

$table->id();
$table->string('title', 200);
$table->string('slug')->unique();
$table->longText('body');
$table->foreignId('user_id')                                // ⭐ v12 写法
    ->constrained()
    ->cascadeOnDelete();
$table->timestamp('published_at')->nullable();              // ⭐ 主动加
$table->timestamps();
```

+ 顺手生成 `Post.php` Model + `PostFactory.php` + 提示跑 migration

→ ✓ 4 个改进：
1. **看了** users 表
2. 用 v12 链式 API
3. 主动补 `published_at`（基于"博客"语义推断）
4. 主动生成 Model + Factory

### 对比 2：写权限

#### 没装 Boost

prompt: `加一个判断，只有作者能编辑文章`

AI 输出：

```php
public function update(Request $request, Post $post)
{
    if ($post->user_id !== auth()->id()) {
        abort(403, '只有作者能编辑');
    }
    // ...
}
```

→ ⚠️ 散写 if，update / delete / edit 三个方法都要复制一份。视图按钮显示也要再写 if。

#### 装了 Boost

AI 输出：

```php
// 生成 PostPolicy
class PostPolicy {
    public function update(User $user, Post $post): bool {
        return $user->id === $post->user_id;
    }
    public function delete(User $user, Post $post): bool {
        return $user->id === $post->user_id;
    }
}

// Controller
public function update(UpdatePostRequest $request, Post $post) {
    $this->authorize('update', $post);
    // ...
}

// Blade
@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Edit</a>
@endcan
```

+ 写 2 个 Pest 测试钉死规则

→ ✓ 改进：
1. 权限抽象成 Policy 类
2. Controller / 视图 / 测试**3 处**自动用同一份 Policy
3. 自带测试

### 对比 3：写 API

#### 没装 Boost

prompt: `帮我把 Post 改成 API`

AI 输出（直接 return model）：

```php
public function show(Post $post) {
    return response()->json($post);
}
```

→ ⚠️ 4 个问题：
1. **泄露字段**——`fillable` 之外的字段都在 JSON 里（含 `password_hash` 如果 join 了）
2. 时间格式不友好（默认 6 位微秒）
3. 关系字段不可控
4. 没有 `data` envelope（前端拿到结构不一致）

#### 装了 Boost

AI 输出（用 JsonResource）：

```php
class PostResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'published_at' => $this->published_at?->toIso8601String(),  // ⭐
            'is_published' => $this->published_at !== null && $this->published_at->isPast(),
            'author' => new UserResource($this->whenLoaded('author')),  // ⭐
        ];
    }
}

class UserResource extends JsonResource {
    public function toArray(Request $request): array {
        return ['id' => $this->id, 'name' => $this->name];  // 不暴露 email
    }
}
```

→ ✓ 改进：
1. 字段白名单（不泄露）
2. ISO8601 时间
3. `whenLoaded` 防 N+1
4. 自动 `data` envelope
5. UserResource 故意不暴露 email

### 对比表（实证总结）

| 维度 | 没装 Boost | 装了 Boost |
|---|---|---|
| 用 Laravel 12 API | ⚠️ 偶尔（看 AI 心情） | ✓ 总是（application-info + Guidelines 锁定） |
| 看现有表结构 | ❌ 不看 | ✓ 调 database-schema |
| 防 N+1 | ❌ 经常忘 | ✓ Skills 提示 + with() |
| 主动写测试 | ❌ 不会 | ✓ Skills "always add tests" |
| 用抽象单元（Policy/FormRequest） | ⚠️ 看 prompt | ✓ Guidelines 默认 |
| 错误响应格式 | ⚠️ 不一致 | ✓ Guidelines 规定 |
| 时间格式 | ⚠️ 默认 | ✓ ISO8601 |

→ **每一行的"装了 Boost ✓"都来自 13/14/15/16 章的实际实战观察**。
→ 实证：[13 章 §7](./13-博客实战完整复盘.md)——8 个老手代码点 + 触发它的 Boost 机制。

---

### 2.4 装 Boost 的 ROI 计算

> 给一个清晰的"投入 vs 回报"账本——让你知道装 Boost 划不划算。

### 投入

| 项 | 时间 |
|---|---|
| 装包 | 1 分钟（`composer require laravel/boost --dev`） |
| 初始化 | 1 分钟（`php artisan boost:install`） |
| Cursor MCP 配置 | 5 分钟（含踩坑） |
| 学习 9 个工具的用法 | 30 分钟（看 [12 章](./12-Boost工具实战大全.md)） |
| **合计** | **约 40 分钟**（一次性投入） |

### 回报（按 4 个维度估算）

#### 回报 1：每个 prompt 节省时间

| 类型 | 没装 Boost 平均 | 装了 Boost 平均 | 节省 |
|---|---|---|---|
| 简单 CRUD prompt | 写 500 字（含所有风格规范） | 写 150 字（只业务） | **70%** |
| 中等任务 | 写 1500 字 | 写 400 字 | **73%** |
| 复杂任务 | 写 3000 字 | 写 800 字 | **73%** |

→ **每个 prompt 节省约 70% 字数**——一天写 10 个 prompt 就省 1-2 小时。
→ 实证：[17 章 §9](./17-Prompt工程反例集.md)

#### 回报 2：减少 AI 用过时 API 的 bug

教程里实测撞过的 5 个"AI 用过时 API"问题：

1. `authorizeResource()` 不兼容 `HasMiddleware`（v11+）
2. `make:queue-table` 在 v12 已不需要
3. Filament v3 教程语法到 v5 全是 Undefined
4. `$casts` 属性 vs `casts()` 方法
5. `__construct + middleware()` vs `HasMiddleware`

每个 bug **平均诊断 + 修复时间 30 分钟**。Boost 让 AI 用 `application-info` + `search-docs` 主动确认版本——**5 个 bug 减少为 0**。

→ **节省约 5 × 30 = 150 分钟 / 项目**。

#### 回报 3：自动化"老手 review 清单"

老手手工 review AI 代码会查的 8 件事（13 章 §7 实证）：
1. 用了对的 Laravel 版本 API 吗？
2. eager-load 防 N+1 了吗？
3. 验证用 Form Request 了吗？
4. 权限用 Policy 了吗？
5. 用 scope 集中业务条件了吗？
6. 用语义化命名（author 而不是 user）了吗？
7. 主动写测试了吗？
8. 错误响应格式统一了吗？

→ 老手 review 平均 **20 分钟**，Boost 让 AI 自己 review = **节省 20 分钟 / PR**。

#### 回报 4：踩坑诊断时间减少

教程里 12 个真实踩坑：

| 类型 | 没装 Boost 平均 | 装了 Boost 平均 |
|---|---|---|
| SQLite 锁问题 | 1 小时 | 30 分钟（read-log-entries 自动找日志） |
| 兼容性 bug | 1 小时 | 30 分钟（search-docs 拿最新文档） |
| 路由命名冲突 | 30 分钟 | 10 分钟（list-routes 自检） |

→ 平均**节省 50%** 诊断时间。

### ROI 总结表

| 维度 | 量化节省 |
|---|---|
| 1. Prompt 字数 | 70%（约 1-2 小时/天） |
| 2. AI 过时 API bug | 150 分钟/项目 |
| 3. 老手 review 自动化 | 20 分钟/PR |
| 4. 踩坑诊断 | 50% 时间 |

**保守估计**：装 Boost 的项目里，**每天节省 2-4 小时开发时间**。

**40 分钟一次性投入 vs 每天节省 2-4 小时** = **第一天就回本**。

### 还有"无法量化"的回报

1. **代码质量更稳定**——AI 不会"今天用 v9 写法明天用 v12 写法"
2. **新人上手更快**——Guidelines 是"团队代码标准的活文档"
3. **维护成本下降**——AI 主动写测试，6 个月后维护时不慌
4. **跨项目复用**——同一份 Boost 配置在多个 Laravel 项目里共享

---

### 2.5 Boost 与 Cursor / Claude Code / Copilot 的关系

> 这一节澄清"工具栈"——Boost 跟你已经在用的 AI 编辑器是**互补**关系，不是替代。

### 整体架构

```
┌──────────────────────────────────────────────────────────┐
│           你的 AI 编辑器（Cursor / Claude Code）            │
│                                                          │
│  - 提供 IDE 体验（编辑器、文件树、Git 集成）                  │
│  - 调 LLM（Claude 4.7 / GPT-4 / o1...）                  │
│  - 实现 Agentic Loop（让 AI 自主调工具）                    │
└─────────────────────┬────────────────────────────────────┘
                      │ MCP 协议
                      ▼
┌──────────────────────────────────────────────────────────┐
│           Laravel Boost MCP Server                        │
│                                                          │
│  - 提供 9 个工具（application-info / database-schema...）  │
│  - 提供 Guidelines 文件                                   │
│  - 提供 Skills 文件                                       │
│  - 提供 17K+ chunks 的版本化文档                          │
└──────────────────────────────────────────────────────────┘
                      │
                      ▼
┌──────────────────────────────────────────────────────────┐
│           你的 Laravel 项目                                │
│                                                          │
│  - 代码（app/、routes/、database/...）                     │
│  - 数据库（SQLite/MySQL...）                               │
│  - 日志（storage/logs/laravel.log）                       │
└──────────────────────────────────────────────────────────┘
```

### 谁负责什么

| 组件 | 负责 |
|---|---|
| **AI 编辑器**（Cursor 等） | UI / 文件操作 / 调 LLM / Agentic Loop |
| **LLM**（Claude / GPT 等） | 实际"思考"和"写代码"的大脑 |
| **MCP 协议** | 让编辑器和工具服务器通信的标准 |
| **Boost MCP Server** | 提供 Laravel 专属的工具 + 知识 |
| **你的 Laravel 项目** | 被操作的对象 |

### 兼容性

Boost 兼容**任何支持 MCP 的 AI 编辑器**：

| 编辑器 | MCP 支持 | 集成难度 |
|---|---|---|
| Cursor | ✓ 内置 | 低（编辑 `.cursor/mcp.json`） |
| Claude Code | ✓ 内置 | 低（编辑 `claude_desktop_config.json`） |
| Cline (VSCode 插件) | ✓ 内置 | 低 |
| Continue (VSCode 插件) | ⚠️ 实验性 | 中 |
| GitHub Copilot | ❌ 不支持 MCP | 不能用 Boost |

→ **不是用 MCP 的编辑器（如 GitHub Copilot）暂时无法用 Boost**——它们走自己的 Agent 协议。
→ 实证：[03 章 §5 Cursor 接入 MCP](./03-环境搭建实测.md)

### 不是非 Cursor 不可

本教程里我们用 Cursor 做演示，但**核心价值在 Boost 本身**——任何支持 MCP 的编辑器都行。

→ 团队里有人用 Cursor、有人用 Claude Code 都没问题——**他们用同一个 Boost server 配置**。

### 多人协作时

```
Project Repo
├── composer.json (含 laravel/boost)
├── .ai/guidelines/        ← 共享，进 git
├── .ai/skills/             ← 共享，进 git
└── .cursor/mcp.json       ← 个人配置，建议加 .gitignore
```

→ **Guidelines / Skills 进 git** = 团队共享代码标准。
→ 个人编辑器配置不进 git = 每人自己的环境路径自己管。

---

## 收尾：下一步看哪里

### 你已经知道了什么

读完本章，你应该清楚：

| 问题 | 你的答案 |
|---|---|
| Laravel 和 ThinkPHP 有什么本质区别？ | **约定优先 vs 灵活配置**——Laravel 让 AI 默认有"标准答案" |
| 转 Laravel 要克服哪些心理障碍？ | 5 个：数组→对象、Model 定位、约定vs灵活、看懂 magic、写测试 |
| 7 个核心抽象的对照？ | 路由 / Eloquent / Migration / FormRequest / Policy / Blade / Pest |
| 为什么 Laravel + AI 是天作之合？ | 5 点：约定 / 训练数据 / 命名 / Guidelines / 测试 |
| Laravel Boost 是什么？ | MCP Server——给 AI 装上"Laravel 老手意识" |
| Boost 的三大能力？ | MCP 工具集（9 个） + Guidelines + Skills |
| Boost 的 ROI？ | 40 分钟投入 → 每天节省 2-4 小时 → 第一天就回本 |

### 下一步看哪里

#### 如果你**想立刻开始动手**

→ 跳到 [`03-环境搭建实测.md`](./03-环境搭建实测.md)，按它跑一遍环境。

#### 如果你**还想看更多理论**

→ 看 [`00-教程导读.md`](./00-教程导读.md) 选一条学习路径（A/B/C/D/E）。

#### 如果你**想看实战的真实样子**

→ 跳到 [`13-博客实战完整复盘.md`](./13-博客实战完整复盘.md) §0-§3，看 2 小时博客怎么做出来的。

#### 如果你**对 prompt 工程感兴趣**

→ 跳到 [`17-Prompt工程反例集.md`](./17-Prompt工程反例集.md) §1-§2，看"过度模糊"的反例怎么改写。

#### 如果你**还在怀疑值不值得装 Boost**

→ 跳到 [`12-Boost工具实战大全.md`](./12-Boost工具实战大全.md) §1，看 `application-info` 工具的真实输出。

---

### 一句话送给读完这章的你

> **下决定的时刻**：
>
> - 如果你读完本章感到"5 个心理障碍其实我都习惯过来了" + "Boost 的 ROI 划算"——继续往下读 03 章环境搭建，把 Boost 装上。
> - 如果你读完感到"Laravel 的 magic 我不认同"——这本教程不是为你写的，关掉就好。
> - 如果你读完感到"我想试试，但担心团队接受不了"——把本章打印出来给 leader 看，里面有"理论弹药"。
>
> **40 分钟 vs 每天 2-4 小时——这是 PHP 后端开发者 2026 年最值得做的一次投入决策。**

---

> **第 1-2 章 认知篇 完。**
> 下一篇推荐：[`03-环境搭建实测.md`](./03-环境搭建实测.md)（动手装环境，30-60 分钟）


