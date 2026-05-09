# 第 17 章 Prompt 工程反例集

> **本章定位**：把"让 AI 在 Laravel 项目里产出垃圾代码"的 prompt 写法，挨个抓出来示众。
>
> 配合阅读：第 12 章（Boost 工具大全）+ 第 13 章（博客实战复盘）。
>
> **本章的"垃圾"标准**：能跑 ≠ 不垃圾。垃圾的 5 个特征——
> - 用了 Laravel 9 的旧 API（明明项目是 v12）
> - N+1 查询没有 eager load
> - 没写测试 / 测试里塞 `expect(true)->toBeTrue()`
> - 把权限判断散在 controller 里而不是用 Policy
> - 把字段验证写在 controller 里而不是 Form Request

---

## 0. 一图看懂"为什么 prompt 反例值得整理一章"

### Prompt 决定 AI 输出质量的 80%

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Prompt 烂  │ ──▶ │  AI 模型再强 │ ──▶ │  代码也是烂  │
└──────────────┘     └──────────────┘     └──────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Prompt 好   │ ──▶ │  Boost + AI  │ ──▶ │ 接近老手代码 │
└──────────────┘     └──────────────┘     └──────────────┘
```

**第 13 章博客实战的真实数据**：

| Prompt 写法 | AI 输出特征 |
|---|---|
| "帮我做个文章列表" | Laravel 9 旧 API、N+1 查询、无权限、无测试 |
| "帮我做个文章列表，eager-load author，分页 10，按 published_at 降序，仅显示已发布" | Eloquent scope + with() + Policy + Pest 测试 ⭐ |

→ 同一个 AI、同一个项目，prompt 改一下，**输出质量天差地别**。

### 这一章的目标

不是教你"写更长的 prompt"——而是教你**避开 5 个具体陷阱**。每个陷阱都给：
- 反例 prompt（你大概写过）
- AI 真实的"错误"输出（截图 / 引用第 13 章实测）
- 改写后的 prompt
- 改写后 AI 的"靠谱"输出

→ 可执行、可对照、可立即用。

---

## 1. 反例分类：5 大病理

### 5 大病理一览表

| # | 病理名 | 一句话症状 | 典型场景 |
|---|---|---|---|
| ① | 过度模糊 | 不说版本/约束/范围，AI 只能瞎猜 | "帮我做个登录" |
| ② | 复制粘贴需求 | 把客户/产品的话原封不动给 AI | "用户反馈说想看自己的文章" |
| ③ | 不让 AI 用工具 | 默认 AI 不查文档、不看数据库、不跑命令 | "用 Laravel 写个 ..." |
| ④ | 没写验收标准 | 不告诉 AI 完成的样子（测试通过？UI 长什么样？） | "做完告诉我" |
| ⑤ | 一个 prompt 塞太多 | 让 AI 同时做 5 件不相关的事 | "做个博客，要有评论、点赞、订阅、搜索、RSS" |

### 病理之间的关系

```
                      ┌──────────────────┐
                      │ ① 过度模糊       │
                      │ （所有问题之母） │
                      └────────┬─────────┘
                               │ 如果不补救
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
┌───────▼────────┐    ┌───────▼────────┐    ┌────────▼────────┐
│ ② 复制粘贴需求 │    │ ③ 不让 AI 用工具│    │ ④ 没写验收标准  │
└────────────────┘    └────────────────┘    └─────────────────┘
        │                      │                      │
        └──────────────────────▼──────────────────────┘
                      ┌─────────────────┐
                      │ ⑤ 一个 prompt   │
                      │   塞太多        │
                      └─────────────────┘
                               │
                               ▼
                      AI 输出"能跑但烂"的代码
```

→ 这 5 个病理是**叠加放大**的——一个 prompt 同时犯 3 个，输出基本不可用。

### 为什么 ThinkPHP 老手特别容易踩坑

我观察的 5 个原因：
1. ThinkPHP 项目里 PHP 文件结构松散，**习惯了"AI 写代码就是改字符串"**——不会想到要让 AI 调工具
2. ThinkPHP 没有 Eloquent scope / Form Request / Policy 这些**抽象单元**，不知道该把任务"拆"到哪个抽象上
3. ThinkPHP 几乎不写测试，**写验收标准的肌肉记忆为零**
4. ThinkPHP 项目版本变化小（5.x → 6.x 几乎不影响代码风格），**不知道要锁版本**
5. PHP 程序员普遍英文 prompt 不流畅，**倾向于用中文模糊表达**

→ 第 7 章会针对这 5 个原因专门讲。

---

## 2. 病理 ①：过度模糊（不说版本/约束/范围）

### 反例 2-1：「帮我做个登录」

#### ❌ 反例 prompt

```
帮我做个登录功能
```

#### AI 的输出（不开 Boost）

- 用 Laravel 9 风格的 `Auth::routes()`（v12 已经废弃）
- 把 `LoginController` 写在 `App\Http\Controllers\Auth\` 下，但没注册路由
- 没有 CSRF 保护
- 没有 session 重生（Session Fixation 漏洞）
- 没有 logout 路由
- 视图直接 `<form action="/login" method="post">`，不带 `@csrf`

→ **能跑，但是 4 个安全漏洞 + 1 个版本问题**。

#### ✅ 改写后的 prompt

```
我在 playground/ 项目（Laravel 12.x）做一个 demo 登录：
- 不要装 Breeze/Jetstream，手写
- 路由：GET /login（表单）、POST /login（提交）、POST /logout
- 用 AuthController（不要 LoginController/LogoutController 分开）
- 必须包含的安全细节：
  · session()->regenerate() 防 session fixation
  · logout() 三件套：logout + invalidate + regenerateToken
  · ->onlyInput('email') 不回填 password
  · redirect()->intended() 回跳原始访问页
- 视图用 layout 组件，登录表单带 @csrf
- 测试用户：test@example.com / password（已 seed）

完成后告诉我访问 http://127.0.0.1:8000/login 应该看到什么。
```

#### AI 的输出（Boost 开启 + 上面这个 prompt）

完全等于第 13 章 5.2 节的 `AuthController`——4 个安全细节全有，+ Pest 测试。

#### 关键差异点提炼

| 维度 | 反例 prompt | 改写 prompt |
|---|---|---|
| 版本 | 没说（AI 默认 v9） | 说了 v12 |
| 范围 | 没说（AI 装 Breeze） | 明确"不要 Breeze" |
| 安全细节 | 没说（AI 全省略） | 列出 4 项必含 |
| 验收 | 没说 | "应该看到什么" |

---

### 反例 2-2：「帮我做个文章列表」

#### ❌ 反例 prompt

```
帮我做个文章列表
```

#### AI 的输出（不开 Boost）

```php
class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();   // 全表查，没分页
        return view('posts.index', compact('posts'));
    }
}
```

视图：

```blade
@foreach ($posts as $post)
    <h2>{{ $post->title }}</h2>
    <p>By {{ $post->user->name }}</p>  {{-- N+1 --}}
    <p>{{ $post->body }}</p>           {{-- 没截断 --}}
@endforeach
```

→ 4 个问题：
1. `Post::all()` 全表查，10 万篇文章直接 OOM
2. `$post->user->name` 触发 N+1（10 篇文章 = 11 次 SQL）
3. `body` 不截断，列表页变成大段正文
4. 没分页

#### ✅ 改写后的 prompt

```
在 playground 项目（Laravel 12）做"已发布文章列表"：
- 路由：GET /posts → PostController@index
- 数据：仅显示 published_at 已到期的文章
  · 用 Post::published() scope（已经在 Model 里）
  · eager-load author 关系（Post::with('author')）
  · 按 published_at 降序
  · 分页 10 条
- 视图：resources/views/posts/index.blade.php
  · 用 x-layout 组件
  · 每篇文章显示：title（链接到 show）、author name、published_at、body 前 150 字（用 Str::limit）
  · 末尾 {{ $posts->links() }} 渲染分页
  · 空数据用 @forelse / @empty 处理

完成后让我访问 /posts 看到分页 + 10 条已发布文章。
```

#### AI 的输出

完全等于第 13 章 4.2 节的代码——`with('author')` 防 N+1、`Str::limit` 截正文、`@forelse` 处理空、`->links()` 渲染分页。

#### 关键差异点提炼

| 维度 | 反例 prompt | 改写 prompt |
|---|---|---|
| 数据范围 | "文章" | "published_at 已到期的文章" |
| 性能要求 | 没说 | "eager-load author" + "分页 10" |
| 视图细节 | 没说 | 列出 5 个具体字段 + 截断长度 |
| 边界情况 | 没说 | "@forelse / @empty 处理空" |

---

### 反例 2-3：「帮我做个权限」

#### ❌ 反例 prompt

```
帮我做个权限，只有作者能编辑自己的文章
```

#### AI 的可能输出（粗暴写法）

```php
public function edit(Post $post)
{
    if ($post->user_id !== auth()->id()) {
        abort(403);
    }
    return view('posts.edit', compact('post'));
}

public function update(Request $request, Post $post)
{
    if ($post->user_id !== auth()->id()) {
        abort(403);
    }
    // ...
}

public function destroy(Post $post)
{
    if ($post->user_id !== auth()->id()) {
        abort(403);
    }
    // ...
}
```

→ 散在 3 个方法里，**改一次规则要改 3 处**。视图里想加 "Edit/Delete 按钮只显示给作者"——还得在 view 里再写一次 `if`。

#### ✅ 改写后的 prompt

```
在 playground 项目做 Post 的权限控制，要求：
- 用 Policy 类（不要在 controller 里散写 if）
- PostPolicy 类型签名规则：
  · viewAny / view 用 ?User（允许游客）
  · create / update / delete 用 User（必须登录）
- 业务规则：
  · view：已发布文章任何人都能看；草稿/定时只有作者能看
  · update / delete：只有作者
- Controller 用 authorizeResource(Post::class, 'post') 一行注册
- 视图用 @can('update', $post) / @can('delete', $post) 控制按钮显隐

完成后跑 php artisan test 应该过。
```

#### AI 的输出

第 13 章 3.3 节的 `PostPolicy` + Controller 的 `authorizeResource()` + show.blade 的 `@can` 一条龙。

#### 关键差异点提炼

| 维度 | 反例 prompt | 改写 prompt |
|---|---|---|
| 抽象单元 | 没说（AI 散写 if） | "用 Policy 类" |
| 类型签名 | 没说 | `?User` vs `User` 区分游客 |
| 注册方式 | 没说 | `authorizeResource()` 一行 |
| 视图整合 | 没说 | `@can` 控制按钮 |

→ **关键是"指定抽象单元"**——告诉 AI 用什么 Laravel 模式，而不是描述行为。

---

## 3. 病理 ②：复制粘贴需求（业务描述当 prompt）

### 病症描述

把客户/产品/老板的话**原封不动**给 AI。例如：

```
用户反馈说想看自己写的文章，最好分成草稿、已发布、定时发布三类
```

这种 prompt 最大的问题：**业务描述 ≠ 技术描述**。AI 拿到这种 prompt 会瞎猜：

- 路由叫什么？`/my/posts`？`/dashboard`？`/posts/mine`？
- 用一个 Controller 还是新建？
- 三个分类用三个独立查询还是一个 union？
- 视图分页吗？每一类都分页？
- 草稿要按什么排序？

→ 每一个不确定，AI 都会**瞎猜一个**。10 个不确定累积起来，输出基本不可用。

---

### 反例 3-1：业务话 vs 技术话

#### ❌ 反例 prompt（业务话）

```
用户反馈说想看自己写的文章，最好分成草稿、已发布、定时发布三类
```

#### ✅ 改写 prompt（技术话）

```
在 playground 项目实现"我的文章"页面，要求：

【路由】
- 在 routes/web.php 加 GET /my/posts → PostController@mine
- 路由名 posts.mine
- 必须放在 Route::resource('posts', PostController::class) 之前
  （否则会被 /posts/{post} 当成 slug=mine 匹配走）
- 必须套 'auth' 中间件（用 HasMiddleware 接口里的 except）

【Controller】
public function mine(Request $request): View
- 一次返回三组数据：
  · published：当前用户的已发布，用 Post::published() scope，按 published_at 降序
  · drafts：当前用户的草稿，用 Post::draft() scope，按 updated_at 降序
  · scheduled：当前用户的定时，用 Post::scheduled() scope，按 published_at 升序
- 调用方式：$request->user()->posts()->published()->latest('published_at')->get()
  （用关系链 + scope）

【视图】
- resources/views/posts/mine.blade.php
- 用 x-layout，title="My Posts"
- 三个 section：草稿（drafts）/ 定时发布（scheduled）/ 已发布（published）
- 每个 section 标题带 count：草稿 ({{ $drafts->count() }})
- 每个 section 内部用 @forelse / @empty
- 草稿/定时显示 [Edit] 按钮，已发布显示 [View] 按钮

【顶部导航】
- layout 里 @auth 块加一个链接：<a href="{{ route('posts.mine') }}">My Posts</a>

【验收】
- 已登录用户访问 /my/posts 应该看到自己的三组文章
- 已登录用户访问 /my/posts 不应该看到别人的文章
- 未登录用户访问 /my/posts 应该被重定向到 /login
- 写 3 个 Pest 测试覆盖以上 3 条
```

#### 反例 vs 改写的 6 处差异

| 维度 | 反例 | 改写 |
|---|---|---|
| 路由路径 | 没说 | `/my/posts` + 命名 + 顺序 |
| 中间件 | 没说 | `auth` 中间件 |
| 数据查询方式 | 没说 | 关系链 + scope，3 组排序规则 |
| 视图结构 | 没说 | 3 个 section + count + @forelse |
| 导航链接 | 没说 | layout @auth 块加链接 |
| 验收 | 没说 | 3 条具体的测试场景 |

→ 反例 prompt 里**只有"业务意图"**。改写 prompt 里**业务意图 + 6 个层次的技术决策都明确了**。

---

### 反例 3-2：客户原话直接转 prompt

#### ❌ 反例 prompt

```
客户说：文章发布功能要让用户能上传图片当封面，
图片不能太大，最好压缩一下，封面要支持裁剪。
```

#### AI 可能的输出

- 装一个第三方包 `intervention/image`（**不告诉你要 composer require**）
- 写一个 `ImageController` 处理上传（**没用 Form Request 验证**）
- 用 `move()` 直接存到 `public/uploads/`（**绕开 Storage facade，部署时 chmod 错就 GG**）
- 没设置最大文件尺寸
- 裁剪用 JavaScript 现写 canvas 画一个（**前端代码 200 行写在 blade 里**）

→ **客户的"最好压缩一下"被 AI 自由发挥成了一个独立模块**。

#### ✅ 改写 prompt

```
基于第 13 章 Post 模型扩展：加封面图字段。

【数据层】
- 加 migration 给 posts 表加 cover_path 字段（string nullable）
- Post 模型 fillable 加 cover_path
- 加访问器 getCoverUrlAttribute() 返回 Storage::url($this->cover_path) 或 null

【上传】
- 不要装 intervention/image，先用基础上传跑通
- StorePostRequest / UpdatePostRequest 加规则：
  cover => ['nullable', 'image', 'max:2048', 'mimes:jpg,png,webp']
- Controller store/update 里：
  if ($request->hasFile('cover')) {
      $path = $request->file('cover')->store('covers', 'public');
      $data['cover_path'] = $path;
  }
- 跑 php artisan storage:link 建立软链

【视图】
- form-fields.blade.php 加 file input，name="cover"
- form 标签加 enctype="multipart/form-data"
- show.blade 里如果 $post->cover_url 存在，显示 <img>

【不要做的事】
- 不要做裁剪，那是后续优化
- 不要装 intervention/image，那是后续压缩

【验收】
- /posts/create 提交带图片应该看到 covers/ 下出现文件
- /posts/{slug} 应该显示封面图
- 不传图片也能正常创建（cover 是 nullable）
```

#### 关键技巧

1. **拆"现在做" vs "后续做"**——客户原话里 3 件事（上传、压缩、裁剪），prompt 里明确只做 1 件
2. **指定不要做什么**——避免 AI 自由发挥
3. **指定数据流转**——`store('covers', 'public')` + `Storage::url()` + `storage:link` 三件套
4. **指定字段命名**——`cover_path` 存路径，访问器返回 URL，不是混在一起

---

### 反例 3-3：把"问题"当 prompt

#### ❌ 反例 prompt

```
为什么我的文章列表很慢？
```

#### AI 的反应

- 猜原因 1：可能是 N+1
- 猜原因 2：可能是没分页
- 猜原因 3：可能是没加索引
- 猜原因 4：可能是 PHP 配置慢

**全是猜。** 因为 prompt 里没给任何**证据**。

#### ✅ 改写 prompt（带证据）

```
我访问 /posts 加载需要 3.2 秒。已经做的诊断：
- php artisan tinker 里 Post::published()->paginate(10)->toArray() 不到 50ms
- 浏览器 DevTools Network 显示 HTML 请求本身 3.1 秒

请用 Boost 的 read-log-entries 工具看 storage/logs/laravel.log 最近 10 条，
顺便用 database-query 看一下：
  EXPLAIN QUERY PLAN SELECT * FROM posts WHERE published_at <= datetime('now') 
  ORDER BY published_at DESC LIMIT 10;

然后判断是 N+1 还是别的问题。修完跑 php artisan test 确认没破坏现有测试。
```

#### 关键技巧

1. **给数据**：明确告诉 AI 已知信息（3.2s、tinker 50ms）
2. **缩小范围**：排除了"查询本身慢"
3. **指定工具**：让 AI 调 `read-log-entries` + `database-query` 而不是猜
4. **加保护**：修完跑测试

→ **debug 类 prompt 最忌讳"为什么 X"**。要写"X 现象 + 我已经排除了 Y + 请用 Z 工具诊断"。

---

## 4. 病理 ③：不让 AI 用工具（默认 AI 不查文档）

### 病症描述

prompt 里**没明确让 AI 用工具**，AI 会默认凭训练数据写代码——而训练数据可能是 2 年前的 Laravel 9。

第 12 章已经讲过 9 个 Boost 工具。这章讲**怎么在 prompt 里"召唤"它们**。

---

### 反例 4-1：版本不确定

#### ❌ 反例 prompt

```
帮我写一个 Post 模型，要支持 cast published_at 为 datetime
```

#### AI 输出（不召唤工具）

```php
class Post extends Model
{
    protected $casts = [           // ❌ 这是 Laravel 10 写法
        'published_at' => 'datetime',
    ];
}
```

#### ✅ 改写 prompt

```
帮我写 Post 模型。

要求：
1. 先调 application-info 工具确认 Laravel 版本
2. 如果是 Laravel 11+，用 protected function casts(): array { ... } 方法签名版
3. 如果是 Laravel 10 及以下，用 protected $casts 属性版
4. 字段：title, slug, body, user_id, published_at（datetime cast）
5. fillable 全部字段
```

#### AI 的实际行为

```
[调 application-info]
→ Laravel 12.58.0

[输出代码]
protected function casts(): array
{
    return ['published_at' => 'datetime'];
}
```

→ **多花 1 秒调工具，省下"Laravel 11 移除特性踩坑"半天**。

---

### 反例 4-2：表结构不确定

#### ❌ 反例 prompt

```
帮我建 posts 表的 migration，user_id 关联 users
```

#### AI 输出（不召唤工具）

```php
$table->bigInteger('user_id')->unsigned();  // ❌ 默认假设
$table->foreign('user_id')->references('id')->on('users');
```

如果项目里 `users.id` 实际是 `integer` 而不是 `bigInteger`，**外键类型不匹配，迁移失败**。

#### ✅ 改写 prompt

```
帮我建 posts 表的 migration。

第一步：调 database-schema 工具看一下 users 表结构，告诉我 id 是什么类型。

第二步：根据 users.id 的类型决定 user_id 怎么写：
- 如果是 bigInteger：用 foreignId('user_id')->constrained()->cascadeOnDelete()
- 如果是 integer：用 unsignedInteger + 手写 foreign key

字段：id, title(varchar 200), slug(unique), body(longText), user_id(外键), 
published_at(timestamp nullable), timestamps()
```

#### AI 的实际行为

```
[调 database-schema 工具]
→ users 表 id 是 integer

[输出代码]
$table->unsignedInteger('user_id');
$table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
```

→ **AI 不假设，AI 看现状**。

---

### 反例 4-3：API 用法不确定

#### ❌ 反例 prompt

```
帮我用 Laravel 的 Validator 做表单验证
```

AI 可能用 Laravel 7 的 `Validator::make()` 风格，**而 Laravel 12 推荐用 Form Request**。

#### ✅ 改写 prompt

```
帮我做 Post 表单的验证。

第一步：调 search-docs 工具，搜索 "form request validation laravel 12"，
拿最新文档中的写法。

第二步：用 php artisan make:request 命令生成 StorePostRequest 和 UpdatePostRequest。

第三步：UpdatePostRequest 里的 slug unique 规则要用 Rule::unique()->ignore()
忽略当前编辑的记录（避免编辑时报"slug 已存在"）。

第四步：authorize() 都返回 true，授权交给 PostPolicy 处理。
```

#### AI 的实际行为

```
[调 search-docs("form request validation laravel 12")]
→ 拿到 v12 文档片段

[运行 php artisan make:request StorePostRequest]
→ 生成文件

[填充 rules() + Rule::unique()->ignore() 标准模式]
```

→ **search-docs 是"反训练数据陈旧"的杀手锏**。

---

### 反例 4-4：错误诊断不调日志

#### ❌ 反例 prompt

```
我这个测试挂了，为什么？

[贴一段终端报错]
```

AI 看到错误片段，**只能猜**——可能错在 A，也可能错在 B。

#### ✅ 改写 prompt

```
PostManagementTest 里 "forces the authenticated user as the author" 这个测试挂了。

调以下工具协助诊断：
1. read-log-entries 看 storage/logs/laravel.log 最后 20 条
2. database-query 看 SELECT * FROM posts WHERE slug='hijack-attempt'
   （这个测试预期会创建一条 slug=hijack-attempt 的记录）
3. application-info 确认 APP_ENV=testing 没问题

诊断完告诉我：
A. 错误的根因是什么
B. 应该改 controller / form request / policy 哪个文件
C. 改完跑哪个命令验证
```

→ **debug prompt = 现象 + 工具调用清单 + 验证方式**。

---

### "召唤工具"的 prompt 模板

> 把下面这段加到任何 prompt 开头，AI 用工具的概率提升一截：

```
开始任务前，先调以下工具确认现状：
1. application-info：项目版本和数据库类型
2. database-schema：相关表结构
3. search-docs（如果用到任何 Laravel API）：拿最新文档

任务过程中，遇到任何不确定的事实（路由是否存在、字段是否存在、配置值是多少），
直接调对应的 Boost 工具确认，不要假设。
```

---

## 5. 病理 ④：没写验收标准（不告诉 AI"完成的样子"）

### 病症描述

prompt 写完 AI **怎么知道做完了**？

- 没有验收标准 → AI 写到自己觉得"差不多"就停
- "差不多"对 AI 和你的标准不一样 → 你要返工

→ **写验收标准 = 把 AI 的"完成"对齐你的"完成"**。

---

### 验收标准的 4 个维度

| 维度 | 写法示例 |
|---|---|
| 视觉 | "访问 /posts 应该看到 10 条文章列表 + 分页" |
| 命令 | "php artisan test 应该 31 passed" |
| 数据 | "数据库 posts 表应该有 6 个用户 × 平均 3 篇 = 约 20 条" |
| 文件 | "应该新增 5 个文件：StorePostRequest、UpdatePostRequest、PostPolicy、posts/index.blade.php、posts/show.blade.php" |

---

### 反例 5-1：「做完告诉我」

#### ❌ 反例 prompt

```
帮我做个 Post 的 CRUD，做完告诉我
```

#### AI 的"完成"

可能是：
- 只写了 controller，没写视图
- 写了视图但没建路由
- 写了 controller + 视图 + 路由，但没建 Form Request

每一种 AI 都会觉得自己"做完了"。

#### ✅ 改写 prompt

```
帮我做 Post 的 CRUD。

【验收清单】
✓ 文件清单（你最后要列出来，缺一个不算完成）：
  - app/Http/Controllers/PostController.php（7 个 RESTful action）
  - app/Http/Requests/StorePostRequest.php（4 条 rules）
  - app/Http/Requests/UpdatePostRequest.php（slug 用 Rule::unique()->ignore()）
  - app/Policies/PostPolicy.php（7 个方法）
  - resources/views/posts/index.blade.php
  - resources/views/posts/show.blade.php
  - resources/views/posts/create.blade.php
  - resources/views/posts/edit.blade.php
  - resources/views/components/posts/form-fields.blade.php（共享表单组件）

✓ 命令验证：
  - php artisan route:list --path=posts 应该列出 7 条路由
  - php artisan test --filter PostManagementTest 应该 19 passed
  - 浏览器访问 /posts 应该看到列表，访问 /posts/create 应该看到表单

✓ 测试覆盖（你要把测试也写了）：
  - 游客访问 /posts/create 应该被重定向到 /login
  - 已登录用户能成功创建文章
  - 创建时强制 user_id 是当前用户（hijack 测试）
  - 非作者更新别人文章应该 403
  - 非作者删除别人文章应该 403
```

#### 关键技巧

1. **文件清单**：明确列出"完成 = 这些文件都存在"
2. **命令验证**：给可执行的"自检命令"
3. **测试覆盖**：把"完成 = 测试通过"也写进去

→ **验收清单 = AI 的 checklist**。AI 会**逐项核对**。

---

### 反例 5-2：「修一下 bug」

#### ❌ 反例 prompt

```
登录后跳转有点问题，帮我修一下
```

"有点问题"是什么问题？怎么算修好了？

#### ✅ 改写 prompt

```
登录跳转 bug 修复。

【现象】
- 复现步骤：未登录访问 /posts/create → 被重定向到 /login → 登录成功 → 
  应该回 /posts/create，但实际跳到了 /
- 期望：登录后回到原始访问页

【已知信息】
- AuthController::login() 里现在写的是 redirect()->route('posts.index')
- 应该改成 redirect()->intended(route('posts.index'))

【验收】
1. 跑 php artisan test --filter "intended" 应该有一条相关测试通过
   （如果没有这条测试，你需要补一条）
2. 手动复现：未登录访问 /posts/create → 被踢去 /login → 登录 → 回到 /posts/create
3. 边界：直接访问 /login（没有原始页）→ 登录后应该回 /posts.index 默认页
```

#### 关键技巧

1. **现象**：怎么复现
2. **期望**：什么算"对"
3. **猜测原因**（如果你已经知道）：节省 AI 探索时间
4. **验收**：3 条具体场景（含边界）

---

### 反例 5-3：「把这个改好看点」

#### ❌ 反例 prompt

```
posts/index 页面太丑了，改好看点
```

"好看"对每个人定义都不一样。AI 可能直接给你换成深色主题 + 紫色 gradient + 浮夸动画——你想要的可能只是"间距大一点"。

#### ✅ 改写 prompt

```
posts/index 页面美化（保持极简风格，不要过度设计）。

【保持不变】
- Tailwind CDN（不要换 UI 库）
- 整体白底 + 灰文字（不要换深色 / 不要 gradient）
- 不要加任何动画

【调整】
- 列表项之间间距：现在 py-2，改成 py-4
- 列表项之间分隔：现在没有，加一个 border-b border-gray-200
- 标题字号：现在 text-base，改成 text-xl，字重 font-bold
- 作者+时间小字：现在 text-base，改成 text-sm text-gray-500
- 摘要：现在 text-base text-gray-700，改成 text-sm text-gray-600，行高 leading-relaxed

【验收】
- 截图对比前后差异
- 移动端 375px 宽度下不能横向滚动
- 1024px 桌面下列表内容仍居中（max-w-3xl mx-auto）
```

#### 关键技巧

1. **保持不变清单**：明确说什么**不要动**（防止 AI 自由发挥）
2. **改动清单**：每条改动指定**具体的 Tailwind class**
3. **响应式验收**：375px / 1024px 两个尺寸的具体要求

→ UI 类 prompt 最容易"AI 自由发挥"。**约束 = 自由的反义词。约束越多，输出越可控**。

---

## 6. 病理 ⑤：一个 prompt 塞太多（让 AI 失焦）

### 病症描述

一个 prompt 让 AI 同时做 5 件事——AI 会**每件都做一半**。

```
帮我做一个博客系统：
1. 文章 CRUD
2. 评论功能
3. 点赞 / 收藏
4. 邮件订阅新文章
5. 全文搜索
6. RSS feed
7. 管理后台
8. API 化
9. 支持多语言
10. 部署文档
```

→ AI 看到这种 prompt，**抓哪个先做都不对**。结果通常是：
- CRUD 写了但没测试
- 评论模型写了但没视图
- 全文搜索装了 Scout 但没建索引
- 其他 6 项**完全没动**

而 AI 会写一段"已完成大部分功能"——其实啥也没完成。

---

### 反例 6-1：博客功能塞 10 件事

#### ❌ 反例 prompt

```
帮我做一个博客系统：
1. 文章 CRUD
2. 评论功能
3. 点赞 / 收藏
4. 邮件订阅新文章
5. 全文搜索
6. RSS feed
7. 管理后台
8. API 化
9. 支持多语言
10. 部署文档
```

#### ✅ 改写：拆成 10 个独立 prompt

按依赖关系排序：

```
Sprint 1（数据基础）
1. 文章 CRUD（含 Migration / Model / Factory / Seeder / Policy / Form Request / Controller / 视图 / 测试）

Sprint 2（用户互动）
2. 评论功能（依赖 1）
3. 点赞 / 收藏（依赖 1）

Sprint 3（内容分发）
4. 全文搜索（用 Scout + Meilisearch 或 SQLite FTS5）
5. RSS feed
6. 邮件订阅（依赖 1，新文章触发 Notification）

Sprint 4（管理 + 集成）
7. 管理后台（用 Filament 或自写）
8. API 化（apiResource + Sanctum）
9. 多语言（lang/）
10. 部署文档
```

→ **一次 prompt 一个 Sprint 的一个任务**。10 个 prompt 累计耗时反而比一个大 prompt 短，因为：
- 每个任务做完能立刻验证
- 出错能立刻修
- AI 不会失焦

---

### 反例 6-2：把"实现 + 优化 + 测试"塞一起

#### ❌ 反例 prompt

```
帮我做文章列表页面，要支持分页，性能要好（避免 N+1），
还要写完整的测试，UI 要美观，移动端也要好用
```

5 件事：实现、分页、N+1、测试、UI、移动端。AI 会样样都"做一点"。

#### ✅ 改写：分 3 步

**Step 1（实现）**：

```
做文章列表 PostController@index：
- Post::published()->with('author')->latest('published_at')->paginate(10)
- 视图 posts/index.blade.php 用 x-layout
- 显示 title / author / published_at / body 前 150 字
- @forelse / @empty 处理空，{{ $posts->links() }} 渲染分页
```

**Step 2（测试）**：

```
为 posts.index 写 Pest 测试：
- 列表显示已发布文章
- 列表隐藏草稿
- 列表隐藏定时（published_at 在未来）
- 分页正确
- 文章按 published_at 降序
跑 php artisan test --filter PostListingTest 应该 5 passed
```

**Step 3（UI 美化）**：

```
posts/index 视觉调整（保持极简）：
[列出具体 Tailwind class 改动]
```

→ 3 步分开，每步**可单独验收**。

---

### 反例 6-3：让 AI 在一个 prompt 里同时改 3 个文件

#### ❌ 反例 prompt

```
我要给文章加 cover_path 字段，请同时改：
- migrations 加字段
- Post 模型加 fillable
- 表单加 file input
- StorePostRequest 加 rules
- UpdatePostRequest 加 rules
- Controller 处理上传
- 视图显示封面
- 跑测试
```

8 个改动同时进行。任何一处错，**全盘卡住**。

#### ✅ 改写：按"数据 → HTTP → 视图 → 测试"分层

```
Step 1（数据层）：
- 加 migration 给 posts 加 cover_path
- Post fillable 加 cover_path
- 跑 php artisan migrate
- tinker 验证：Post::factory()->create(['cover_path' => 'test.jpg']) 不报错

Step 2（HTTP 层）：
- StorePostRequest / UpdatePostRequest 加 cover 规则
- Controller store/update 处理 $request->file('cover')->store('covers', 'public')
- 跑 php artisan storage:link

Step 3（视图层）：
- form-fields.blade 加 file input
- show.blade 加封面图片显示
- form 标签加 enctype

Step 4（测试）：
- 写 1 条 Pest 测试覆盖"上传带图片创建文章"
- 跑 php artisan test
```

→ **每一步做完，跑命令验证**。下一步基于上一步的"已知正常"前进。

---

### "拆分大任务"的元规则

| 何时拆分 | 怎么拆 |
|---|---|
| 超过 3 个独立功能 | 拆成多个 Sprint，每个 Sprint 一个 prompt |
| 一个功能涉及超过 4 个文件 | 按"数据/HTTP/视图/测试"4 层拆 |
| 既要"做"又要"优化" | 先做能跑的版本，再优化 |
| 既要"实现"又要"重构" | 先实现，再重构（重构基于"通过的测试"安全得多） |

---

## 7. ThinkPHP 老手最容易写的 5 个 prompt 反例

### 这一节专门给 ThinkPHP 转 Laravel 的人

ThinkPHP 项目的工作模式塑造了一些**反 Laravel 的 prompt 习惯**。下面 5 个反例都是我自己写过、也看到同事写过的。

---

### 反例 7-1：「用 Db 类写一个查询」

#### ❌ ThinkPHP 思维 prompt

```
帮我写一个查询：从 posts 表查出 user_id=1 的已发布文章，按 published_at 降序，
取前 10 条，关联 users 表查作者名。
```

AI 会**忠实地**输出：

```php
$posts = DB::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('posts.user_id', 1)
    ->whereNotNull('posts.published_at')
    ->where('posts.published_at', '<=', now())
    ->orderByDesc('posts.published_at')
    ->limit(10)
    ->select('posts.*', 'users.name as author_name')
    ->get();
```

→ 能跑。但**完全没用 Eloquent 的优势**：
- 没有 Model 关系
- 没有 scope
- 没有 cast（`published_at` 还是字符串）
- 不能用 Policy 守门

#### ✅ 改写为 Laravel 思维 prompt

```
基于 Post 模型查询当前用户的已发布文章（前 10 条）：

要求：
- 用 Eloquent 而不是 Query Builder
- 必须用 Post::published() scope（已经在 Model 里）
- 必须 eager-load author 关系（防 N+1）
- 用关系链：$user->posts()->published()->latest('published_at')->take(10)->get()
- 在视图里 $post->author->name 直接调用
```

#### 关键差异

| 维度 | ThinkPHP 思维 | Laravel 思维 |
|---|---|---|
| 起点 | 表 (`DB::table('posts')`) | 模型 (`Post::`) |
| 条件 | where 链 | scope (`published()`) |
| 关联 | join + select | with(关系) |
| 调用 | `$row->author_name`（字符串） | `$post->author->name`（对象） |

→ **Laravel 思维 = 基于模型 + 关系 + scope。ThinkPHP 思维 = 基于表 + SQL**。

---

### 反例 7-2：「写一个验证」

#### ❌ ThinkPHP 思维 prompt

```
帮我在 PostController 里写一个验证，title 必填，slug 唯一，body 必填
```

AI 会输出（在 controller 里）：

```php
public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:200',
        'slug'  => 'required|string|unique:posts,slug',
        'body'  => 'required|string',
    ]);
    // ...
}
```

→ **能跑，但没用 Form Request**。问题：
- 验证规则散在 controller，**测试时要走 HTTP 才能测**
- update 时要复制一遍 + 改成 `Rule::unique()->ignore()`
- 多端共用同一个表单（API + Web）时要复制

#### ✅ 改写

```
做 Post 的表单验证，要求：
- 用 Form Request 类，不要在 controller 里写 $request->validate()
- StorePostRequest 和 UpdatePostRequest 各一个
- UpdatePostRequest 的 slug rule 用 Rule::unique()->ignore($this->route('post'))
- authorize() 都返回 true（授权交给 Policy）
- Controller 方法签名直接 type-hint：public function store(StorePostRequest $request)
- $request->validated() 拿验证后的数据
```

#### 关键差异

| 维度 | ThinkPHP 思维 | Laravel 思维 |
|---|---|---|
| 验证位置 | controller 内联 | 独立 Form Request 类 |
| 复用 | 复制粘贴 | 类继承 / 共享 trait |
| 测试 | 必须走 HTTP | 单测 Form Request 类 |
| update vs store | 复制一份 | 两个独立类，各自 rules |

---

### 反例 7-3：「加权限判断」

#### ❌ ThinkPHP 思维 prompt

```
update 方法里加一个判断，只有作者能编辑自己的文章
```

AI 会输出：

```php
public function update(Request $request, Post $post)
{
    if ($post->user_id !== auth()->id()) {
        abort(403, '只有作者能编辑');
    }
    // ...
}
```

→ 还是把 if 散在 controller 里。

#### ✅ 改写

```
做 Post 的"作者唯一编辑"权限：
- 用 PostPolicy 类（不要在 controller 里写 if）
- update 方法：return $user->id === $post->user_id;
- Controller 用 authorizeResource(Post::class, 'post') 在 __construct 里一行注册
- 视图 show.blade 里 @can('update', $post) 控制 Edit 按钮显隐
- 写一条 Pest 测试：非作者 PUT /posts/{slug} 应该 403
```

#### 关键差异

ThinkPHP 思维下，权限是**散落的 if**。Laravel 思维下，权限是**第一类对象（Policy）**——可继承、可测试、可在视图复用。

---

### 反例 7-4：「跑 SQL 查数据」

#### ❌ ThinkPHP 思维 prompt

```
帮我写一段 SQL：select count(*) from posts where user_id = 1
```

#### ✅ 改写

```
帮我用 Boost 的 database-query 工具直接跑：
SELECT COUNT(*) FROM posts WHERE user_id = 1

或者更"Laravel 化"：
用 Boost 的 tinker 工具跑：
User::find(1)->posts()->count()
```

→ ThinkPHP 老手第一反应是写 SQL。**Laravel + Boost 的标准做法是用 tinker + Eloquent**——同样的查询，多得到：
- 关系自动 join
- 类型自动 cast
- scope 可用

---

### 反例 7-5：「先把功能写出来再说」

#### ❌ ThinkPHP 思维 prompt

```
先把功能写出来跑通，测试以后再补
```

→ ThinkPHP 项目里 90% 没测试，"以后再补"=永远不补。

#### ✅ 改写（Laravel 思维）

```
功能 + 测试一起做：
- 实现完每个 Controller action 立刻补 Pest 测试
- 每个 Policy 方法都要有对应的 it('xxx user can / cannot xxx', ...) 测试
- 跑 php artisan test 应该全 passed 才算"做完"
```

→ Laravel 生态默认装 Pest，**测试跑 0.6 秒**——成本极低。"先实现后补测试"是 ThinkPHP 时代的成本观念，不适合 Laravel。

---

## 8. Prompt 模板库（拿来即用）

### 模板 1：新增"资源型"功能

适用场景：加一个 CRUD 模型（Post / Comment / Category 等）。

```
我要在 playground 项目（Laravel 12）新增 [资源名] 资源。

【调研先行】
1. 调 application-info 确认项目版本
2. 调 database-schema 看相关表（特别是 [资源名] 要关联的表）
3. 必要时调 search-docs 拿最新 API

【数据层】
- migration：[列字段 + 类型 + 约束]
- Model：[填 fillable / casts / 关系 / scope]
- Factory：[列 definition + state]
- Seeder：用 firstOrCreate 保持幂等

【HTTP 层】
- 路由：Route::resource('[复数名]', [资源名]Controller::class)
- Controller：implements HasMiddleware + 静态 middleware()
- Form Request：Store + Update 两个类
- Policy：viewAny/view (?User) + create/update/delete (User)

【视图层】
- 5 个标准视图：index / show / create / edit
- 共享组件：components/[资源名]/form-fields.blade.php
- 用 x-layout 包裹

【测试】
- [资源名]ListingTest（读相关）
- [资源名]ManagementTest（写相关 + 权限相关 + 安全相关）

【验收】
- php artisan route:list 显示新路由
- php artisan test --filter [资源名] 全 passed
- 浏览器访问 /[复数名] 看到列表
```

---

### 模板 2：修改现有功能

适用场景：给已有模型加字段、加方法、改逻辑。

```
我要给 [资源名] 加 [新功能]。

【现状】
- [描述当前代码做了什么]
- [描述测试覆盖情况]

【目标】
- [描述修改后的行为]
- [明确"不能破坏"的现有功能]

【改动清单】
- 文件 1：[改什么]
- 文件 2：[改什么]
- 文件 3：[新建什么]

【验收】
- 跑 php artisan test 应该所有现有测试还通过
- 新加的行为有 [N] 条新测试覆盖
- 手动复现：[具体步骤]
```

---

### 模板 3：debug

```
现象：[复现步骤]
期望：[正确行为]
已经排除的可能：[A、B、C]

请用以下工具协助诊断：
1. read-log-entries（看最近日志）
2. database-query（看相关数据）
3. application-info（确认环境）

诊断后告诉我：
A. 根因
B. 应改的文件
C. 验证命令
```

---

### 模板 4：性能优化

```
现象：[路径] 加载需要 [X] 秒
已知：
- [现有代码片段]
- [相关 SQL]

请：
1. 调 database-query 跑 EXPLAIN QUERY PLAN 看执行计划
2. 检查是否 N+1（看代码里 $model->relation 调用是否在循环里）
3. 检查是否缺索引
4. 检查是否分页

修完跑 php artisan test 确保不破坏现有功能。
```

---

### 模板 5：UI 美化

```
[页面路径] 美化。

【保持不变】
- [UI 库 / 颜色风格 / 不做的动画]

【调整】
- [元素 1]：现在 [class]，改成 [class]
- [元素 2]：现在 [class]，改成 [class]

【响应式验收】
- 375px 宽度：不能横向滚动
- 1024px 宽度：[具体要求]
```

---

### 模板 6：API 化

```
把现有 [资源名] Web 路由扩展为 API。

【路由】
- 用 routes/api.php
- Route::apiResource('[复数名]', Api\[资源名]Controller::class)

【Controller】
- 新建 Api\[资源名]Controller，单独命名空间
- 用 [资源名]Resource 包装响应

【认证】
- 用 Sanctum，POST /api/login 颁发 token
- API 路由套 'auth:sanctum' middleware

【验收】
- POST /api/login 返回 token
- GET /api/[复数名] 带 token 返回 JSON
- POST /api/[复数名] 带 token 创建成功
- 未带 token 返回 401
- 写 [资源名]ApiTest 覆盖以上 4 条
```

---

## 9. Boost 让 prompt 工程"省一半力"的 3 个原因

### 原因 1：AI Guidelines 接管"风格细节"

没装 Boost：你要在每个 prompt 里写——
- "用 Laravel 12 的 casts() 方法签名"
- "用 HasMiddleware 接口"
- "用 Form Request 不要内联验证"
- "Policy 而不是 if"
- "scope 而不是散查询"

装了 Boost：**这些细节 AI 自己看 Guidelines 就懂了**。你的 prompt 只写"业务意图"。

#### 对比

| Prompt 长度 | 不装 Boost | 装了 Boost |
|---|---|---|
| 简单 CRUD 任务 | ~500 字（要列所有风格规范） | ~150 字（只列业务） |
| 中等任务 | ~1500 字 | ~400 字 |
| 复杂任务 | ~3000 字（写到怀疑人生） | ~800 字 |

→ **Boost 把"风格"从 prompt 里抽走，prompt 只剩"意图"**。

---

### 原因 2：工具替代"假设"

没装 Boost：AI 必须**假设**项目状态——
- 假设 Laravel 版本
- 假设字段类型
- 假设路由是否存在
- 假设当前是否有数据

每个假设都是错误源。

装了 Boost：AI 调工具**确认**——
- `application-info` 确认版本
- `database-schema` 确认字段
- `list-routes` 确认路由
- `database-query` 确认数据

→ **prompt 不再需要写"假设条件"**。

---

### 原因 3：Skills 触发"主动行为"

第 13 章里 AI 在没要求的情况下**主动**做了：
1. 给 Post 加 `getRouteKeyName() = 'slug'`（看到 slug 是 unique 字段，主动美化 URL）
2. 给查询加 `with('author')`（防 N+1）
3. 写了 31 个 Pest 测试（Skills 里 testing.md 规则）
4. PostPolicy 用 `?User` 类型签名（区分游客/登录）

这些**主动行为**没出现在 prompt 里。是 Boost Skills 触发的。

#### 不用 Boost 的等价 prompt

要让 AI 做出同样的"主动行为"，prompt 要加：

```
另外请：
1. 如果某字段是 unique 字段，考虑用它当 route key
2. 任何 with relationship 调用都要 eager-load
3. 自动写 Pest feature 测试覆盖关键路径
4. Policy 方法签名根据"是否允许游客"决定 ?User 还是 User
5. ...还有十几条
```

→ **每次 prompt 都要写。** Skills 一劳永逸——写一次，永久生效。

---

### 一句话总结这一节

```
没装 Boost：prompt 工程 = 写"AI 该做的所有事"
装了 Boost：prompt 工程 = 写"AI 不该做的所有事 + 业务意图"
```

---

## 10. 一句话原则

### 把 5 大病理浓缩成一句话

> **Prompt = 业务意图 + 抽象单元 + 工具调用 + 验收标准 + 边界约束**

| 元素 | 病理对应 | 例子 |
|---|---|---|
| 业务意图 | 反例 ②③ | "实现文章列表" |
| 抽象单元 | 反例 ① | "用 Eloquent scope" / "用 Form Request" / "用 Policy" |
| 工具调用 | 反例 ③ | "先调 application-info / database-schema" |
| 验收标准 | 反例 ④ | "php artisan test 应该 31 passed" |
| 边界约束 | 反例 ⑤ | "不要装 npm / 不要做评论" |

---

### 5 个最高 ROI 的 prompt 习惯（按效果排序）

| # | 习惯 | 效果 |
|---|---|---|
| 1 | **总是说版本** | 一句话避开 80% 的 API 过时问题 |
| 2 | **指定抽象单元** | 把"散写 if"变成"用 Policy"等 Laravel 标准用法 |
| 3 | **召唤工具** | "先调 X 工具确认 Y" 是 prompt 里最值的一句 |
| 4 | **写验收标准** | 让 AI 知道何时停 |
| 5 | **拆成多个小 prompt** | 一个 prompt 一件事，做完跑测试再下一个 |

---

### 最后留 3 个练习题

#### 练习 1：把下面这个 prompt 改写

```
帮我做一个评论功能
```

提示：用本章模板 1（资源型功能）改写。预期改写后 600+ 字，覆盖：
- 调研先行（看 posts 表）
- 数据层（comments migration、Comment 模型、与 Post 的关系）
- HTTP 层（嵌套路由 / Controller / Form Request / Policy）
- 视图层（show.blade 集成评论列表 + 评论表单）
- 测试

#### 练习 2：诊断这个 prompt 哪里有问题

```
我的网站好像被攻击了，最近 user_id 经常被改，帮我修一下
```

提示：5 大病理它至少犯了 4 个。

参考改写思路：
- 病理 ②（业务话）→ 改成"具体哪个表的 user_id 字段，修改时间范围"
- 病理 ③（不让用工具）→ "请用 database-query 查 SELECT id, user_id, updated_at FROM posts ORDER BY updated_at DESC LIMIT 50"
- 病理 ④（无验收）→ "修复后跑 PostManagementTest，预期 hijack-attempt 测试通过"
- 病理 ⑤（一个 prompt 太多）→ 拆成"先诊断"+"再修复"两步

#### 练习 3：自己评估你最近写过的 5 个 prompt

打分表：

| Prompt | ① 模糊 | ② 业务话 | ③ 没召唤工具 | ④ 没验收 | ⑤ 塞太多 | 总分 |
|---|---|---|---|---|---|---|
| Prompt 1 |  |  |  |  |  | / 5 |
| Prompt 2 |  |  |  |  |  | / 5 |
| ... |  |  |  |  |  |  |

每犯一项扣 1 分。如果你 5 个 prompt 平均得分低于 3，**这一章值得反复读**。

---

> **本章核心**：不是教你写更长的 prompt，是教你写**有结构的**prompt。
>
> 模板 1-6 + 5 大病理对照 = 90% 的日常 prompt 场景都覆盖了。
>
> **下一章预告**（第 18 章）：当 Prompt 工程也救不了 AI 时——人类老手必须接管的 5 个时刻。


