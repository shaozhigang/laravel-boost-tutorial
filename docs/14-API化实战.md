# 第 14 章 实测笔记：API 化（Sanctum + apiResource + JsonResource）

> 实测时间：2026-05-08（约 1.5 小时）
> 项目：`playground/`（在第 13 章博客 + 第 15 章队列基础上做 API 化）
> 关联大纲：[第 14 章 RESTful API 开发](./Laravel_Boost_教程大纲.md)
>
> **本章定位**：把 web 端的博客（13 章）+ 队列邮件（15 章）扩展为 API 接口——含真实生产里会撞的 1 个 Laravel 12 坑：`authorizeResource()` 与 `HasMiddleware` 不兼容。

---

## 0. 战果速览

### 数字看战果

| 维度 | 数字 |
|---|---|
| 总耗时 | 约 1.5 小时 |
| Sprint 数 | 7 个 |
| 新增/修改文件 | 8 个 |
| PHP 代码行 | ~280 行 |
| API 路由 | 8 条（3 auth + 5 RESTful） |
| 复用的 web 资产 | StorePostRequest / UpdatePostRequest / PostPolicy / Post 模型 |
| 测试用例 | **18 个**（6 AuthApi + 12 PostApi） |
| 测试断言 | 101 条（API 部分） |
| **总测试** | **40 → 58 passed in 1.16s** |
| 真实踩坑 | 2 次（authorizeResource + Form Request 自动 JSON） |

### 时间线

```
T+0:00   "把博客 API 化" → AI 拆 7 个 Sprint
T+0:05   composer require laravel/sanctum 4.3.2 → 装包成功
T+0:10   php artisan install:api → routes/api.php + personal_access_tokens migration
T+0:15   migrate → 表建好
T+0:20   User 加 HasApiTokens trait（install:api 没自动加！）
T+0:25   Api\AuthController + login/logout/me 路由
T+0:30   smoke test：tinker 创建 token 成功
T+0:40   PostResource + UserResource（含 whenLoaded / toIso8601String）
T+0:55   Api\PostController + apiResource 路由
T+1:00   ❌ 跑测试报 500：authorizeResource 不兼容 HasMiddleware ⭐
T+1:05   修复：改成手动 $this->authorize() 调用
T+1:15   bootstrap/app.php 注册 4 个异常 JSON 响应
T+1:30   写 18 个 Pest API 测试
T+1:40   58 passed (180 assertions) in 1.16s
T+1:50   写本笔记
```

→ **T+1:00 那次踩坑是这次最值的瞬间**——网上 90% 的 Laravel API 教程仍用 `authorizeResource()`，**Laravel 11+ 已经不兼容**。

### 18 个 API 测试覆盖的"业务规则地图"

```
                ┌─────────────────────────────────────┐
                │     /api/login                      │
                ├─────────────────────────────────────┤
                │ test 1  发 token                    │
                │ test 2  错密码 → 422                 │
                │ test 3  缺 device_name → 422         │
                └─────────────────────────────────────┘

                ┌─────────────────────────────────────┐
                │     /api/me                         │
                ├─────────────────────────────────────┤
                │ test 4  带 token 返回 user           │
                │ test 5  无 token → 401 JSON          │
                └─────────────────────────────────────┘

                ┌─────────────────────────────────────┐
                │     /api/logout                     │
                ├─────────────────────────────────────┤
                │ test 6  仅撤销当前 token             │
                └─────────────────────────────────────┘

                ┌─────────────────────────────────────┐
                │     /api/posts (RESTful)            │
                ├─────────────────────────────────────┤
                │ test 7  index 返回分页 JSON          │
                │ test 8  index 隐藏 draft/scheduled   │
                │ test 9  show 返回 wrapped resource   │
                │ test 10 author 不泄露 email          │
                │ test 11 不存在 → 404 JSON            │
                │ test 12 未登录 store → 401           │
                │ test 13 已登录 store → 201 + queue   │
                │ test 14 store 验证 → 422             │
                │ test 15 author 能 update             │
                │ test 16 非 author update → 403       │
                │ test 17 author 能 delete             │
                │ test 18 非 author delete → 403       │
                └─────────────────────────────────────┘
```

---

## 1. 起点：一句话需求

### 给 AI 的原始 prompt

```
我要把第 13 章的博客系统扩展为 API（保留 web 端不动）：
- 用 Sanctum，颁发 personal access token
- 路由前缀 /api，支持 login / logout / me / posts CRUD
- 不要装 Passport（OAuth2 太重）
- 复用现有的 StorePostRequest / UpdatePostRequest / PostPolicy（不要重写）
- 用 API Resource（JsonResource）做字段白名单，特别是不要泄露 author 的 email
- HTTP 状态码语义化：201 创建、422 验证错、401 未登录、403 无权限、404 不存在
- 错误响应统一 JSON 格式 {"message": "...", "errors": {...}}
- 写完 Pest 测试覆盖以上场景

完成后告诉我跑哪个命令验证。
```

### AI 拿到 prompt 后做的 5 件事

1. **调 application-info 工具** → 确认 Laravel 12 + 已装 boost
2. **检查 composer.json** → 确认 Sanctum **未装**
3. **检查 routes/** → 确认 `routes/api.php` **不存在**（Laravel 11+ 默认）
4. **拆 7 个 Sprint** → 每个明确产出 + 验收命令
5. **预告会复用什么** → 让我有"我能省哪些代码"的预期

---

## 2. Sprint 1：Sanctum 安装 + routes/api.php 初始化

### 3 条命令搞定

```bash
composer require laravel/sanctum                 # 装 sanctum 4.3.2
php artisan install:api                          # ⭐ Laravel 11+ 一键命令
php artisan migrate                              # 建 personal_access_tokens 表
```

### `php artisan install:api` 做了什么

这是 Laravel 11+ 引入的"一键 API 脚手架"命令，**3 个动作**：

| # | 动作 |
|---|---|
| 1 | 创建 `routes/api.php` 文件（含 `GET /api/user` 示例路由） |
| 2 | 在 `bootstrap/app.php` 的 `withRouting()` 里注册 API 路由文件 |
| 3 | 询问"是否安装 Sanctum"——选 yes 时发布 Sanctum migration |

会问你 1 个问题：

```
Would you like to install Laravel Sanctum? (yes/no) [yes]
```

→ 直接回车选 yes。

### Sanctum 装好后的 jobs 表（**实测踩坑**：你以为的 != 实际的）

**期望**：`install:api` 自动给 `User` 模型加 `HasApiTokens` trait
**实测**：**没加**！

```php
// 实测后的 User.php（没加 HasApiTokens）
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    // ↑ 没看到 HasApiTokens
}
```

→ **这是 Sprint 2 必做的事**：手动加 trait。

### 反模式提醒

> **网上很多 2025 年之前的 Laravel API 教程**让你跑：
> ```bash
> composer require laravel/sanctum
> php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
> php artisan migrate
> ```
> **Laravel 11+ 已经不需要这套**——`install:api` 一行搞定。

### Sprint 1 完成验收

```bash
✓ composer.json 含 "laravel/sanctum": "^4.3"
✓ routes/api.php 已创建
✓ personal_access_tokens 表已建
```

---

## 3. Sprint 2：User trait + Api\AuthController + 登录/登出路由

### User 加 trait

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;   // ⭐ 加 HasApiTokens
}
```

### Api\AuthController 标准模板

文件：`app/Http/Controllers/Api/AuthController.php`

```php
public function login(Request $request): JsonResponse
{
    $credentials = $request->validate([
        'email'       => ['required', 'email'],
        'password'    => ['required', 'string'],
        'device_name' => ['required', 'string', 'max:100'],
    ]);

    $user = User::where('email', $credentials['email'])->first();

    if (! $user || ! Hash::check($credentials['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    $token = $user->createToken($credentials['device_name'])->plainTextToken;

    return response()->json([
        'user'  => $user->only(['id', 'name', 'email']),
        'token' => $token,
    ], 201);
}

public function logout(Request $request): JsonResponse
{
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out.']);
}

public function me(Request $request): JsonResponse
{
    return response()->json([
        'user' => $request->user()->only(['id', 'name', 'email']),
    ]);
}
```

### 5 个老手细节

#### ① Token 格式 `1|TUvf1o4WdGJv...`

```
1|TUvf1o4WdGJvDqNw4GAhEkHPlNKzpyXTqE8gnAaJ0964301f
↑ ↑
id 真正的随机字符串
```

竖线前是**数据库 id**，后是**真正的明文**。客户端发请求时只需发整个字符串，**Laravel 内部用 id 快速定位 + 用 hash 验证后半部分**——比"全表扫描 hash"快得多。

#### ② `createToken('device-name')` 命名是设备名

```php
$user->createToken('mobile-app')->plainTextToken;
$user->createToken('web-spa')->plainTextToken;
$user->createToken('postman-test')->plainTextToken;
```

→ 用户后台可以列出"你登录了哪些设备"——点击撤销某个设备就行。

#### ③ `plainTextToken` 只有此刻能拿到

```php
$token = $user->createToken('device')->plainTextToken;
//        ↑ 只在创建瞬间拿得到，数据库里只存 sha256(token)
```

**和密码 hash 完全相同的安全模型**——服务端永远不能反推明文。

#### ④ `currentAccessToken()->delete()` 而不是 `tokens()->delete()`

| 方法 | 作用 |
|---|---|
| `currentAccessToken()->delete()` ⭐ | 仅撤销当前请求用的 token |
| `tokens()->delete()` | 撤销该用户所有设备的 token（账号被盗时用） |

→ 用户在某个设备 logout 不应该把别的设备也 logout——这是好的 UX。

#### ⑤ 用 `ValidationException::withMessages()` 处理"密码错误"

```php
throw ValidationException::withMessages([
    'email' => ['The provided credentials are incorrect.'],
]);
```

→ 这样响应是 **422 + 标准的 `{message, errors: {email: [...]}}` 格式**，前端处理一致。

如果用 `abort(401)` 会破格式（`{message: "Unauthenticated"}` 没 errors 字段）。

### routes/api.php

```php
Route::name('api.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });

    Route::apiResource('posts', PostController::class);
});
```

→ **`Route::name('api.')` 前缀**让 API 路由名变成 `api.login` / `api.posts.show`，**避免和 web 路由 `posts.show` 冲突**。

### Sprint 2 完成验收

```bash
✓ User 加了 HasApiTokens trait
✓ Api\AuthController 4 个方法（login/logout/me）
✓ routes/api.php 套 api. 命名前缀
✓ tinker smoke test：createToken() 返回 token，tokens count = 1
```

---

## 4. Sprint 3：API Resources（PostResource + UserResource）

### 为什么需要 API Resource？

**反例**：直接 `return $post`，会发生什么？

```php
public function show(Post $post): JsonResponse
{
    return response()->json($post);   // ❌ 危险
}
```

**3 个问题**：
1. **泄露字段** —— 加了 `internal_score` 字段，前端立刻能看到
2. **格式不一致** —— `published_at` 默认输出带 6 位微秒，前端 JS 可能解析不了
3. **关系字段不可控** —— `$post->author` 触发 N+1 + 暴露 author 的 password_hash

**正解**：用 `JsonResource` 做"对外字段白名单 + 格式化"。

### PostResource 标准模板

文件：`app/Http/Resources/PostResource.php`

```php
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'body'         => $this->body,
            'published_at' => $this->published_at?->toIso8601String(),
            'is_published' => $this->published_at !== null && $this->published_at->isPast(),
            'author'       => new UserResource($this->whenLoaded('author')),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

### UserResource 故意只暴露 id + name

```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
```

→ **没有 email、created_at、updated_at**——公开 API 列出某文章作者时，**不应该让任何人看到作者的 email**。

### 5 个设计决策

#### ① `$this->whenLoaded('author')` —— 防 N+1 + 优雅省略

```php
'author' => new UserResource($this->whenLoaded('author')),
```

| 场景 | 输出 |
|---|---|
| Controller 调了 `Post::with('author')` | `"author": { "id": 1, "name": "..." }` |
| Controller 没 eager load | `"author": "MissingValue"` → JSON 中**字段省略** |
| 调了但 author 是 null | `"author": null` |

→ **API 设计里最优雅的特性之一**：客户端**靠字段是否出现**就知道是否预加载了关系。

#### ② `toIso8601String()` —— 时间格式标准化

| 方法 | 输出 | 前端友好度 |
|---|---|---|
| 默认 JSON | `"2026-05-08T14:32:11.000000Z"`（6 位微秒） | ⚠️ 部分 JS 库不识别 |
| `->toIso8601String()` | `"2026-05-08T14:32:11+08:00"` | ✓ 通用 |
| `->timestamp` | `1778225973` | ✓ 但失去时区 |

**原则**：API 时间字段**永远用 ISO8601**——`+00:00` 或 `+08:00` 后缀清晰表达时区。

#### ③ `is_published` —— 衍生字段

```php
'is_published' => $this->published_at !== null && $this->published_at->isPast(),
```

→ 前端不需要再判断 `published_at` 是不是过去时间——API 直接给 boolean。

#### ④ Resource 嵌套

`PostResource` 内含 `UserResource`——层级清晰：
- `PostResource` 决定"文章字段"
- `UserResource` 决定"用户字段"
- 修改一个不影响另一个

#### ⑤ 自动包装 `data` 字段

```json
{
  "data": { "id": 1, "title": "...", ... }
}
```

→ JsonResource 默认把响应包在 `data` 字段里。collection 还会**自动加** `links` + `meta`（分页信息）：

```json
{
  "data": [{...}, {...}],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 10, "total": 25 }
}
```

→ 这是 API 设计的**标准 envelope** —— 前端只需关注 `data`，分页信息在 `meta`。

### Sprint 3 完成验收

```bash
✓ app/Http/Resources/PostResource.php 存在
✓ app/Http/Resources/UserResource.php 存在
✓ PostResource 用 whenLoaded + toIso8601String + 衍生字段 is_published
```

---

## 5. Sprint 4：Api\PostController CRUD（含 Laravel 12 兼容性坑 ⭐）

### 关键复用 ⭐⭐⭐

**完全不重写**：
- `StorePostRequest` / `UpdatePostRequest`（Web 已经写好）
- `PostPolicy`（Web 已经写好）
- `Post` 模型 + scopes

**新增**：`Api\PostController`（5 个 RESTful 方法 + 复用上面所有）

### Api\PostController 模板

```php
class PostController extends Controller implements HasMiddleware
{
    use AuthorizesRequests;

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Post::class);
        $posts = Post::published()->with('author')->latest('published_at')->paginate(10);
        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);
        $post = $request->user()->posts()->create($request->validated());
        if ($post->published_at?->isPast()) {
            SendPostPublishedEmailJob::dispatch($post);   // ⭐ 复用第 15 章队列
        }
        $post->load('author');
        return PostResource::make($post)->response()->setStatusCode(201);
    }

    // show / update / destroy 略
}
```

### 6 个亮点

#### ① `apiResource` vs `resource` 的差异

| 方法 | 生成路由数 | 包含 |
|---|---|---|
| `Route::resource(...)` | **7** | index/create/store/show/edit/update/destroy |
| `Route::apiResource(...)` | **5** | index/store/show/update/destroy（去掉 create/edit） |

→ API 不需要"显示创建表单/编辑表单"页面——那是前端 SPA 的事。

#### ② Form Request 完全复用 ⭐

```php
public function store(StorePostRequest $request): JsonResponse  // ← 用 web 的 Form Request
```

**Form Request 没改一行**——同一个 `StorePostRequest` 同时为 web 和 API 服务。

**Laravel 怎么决定返回 JSON 而不是 redirect back？**

```
请求带 Accept: application/json 头  →  返回 JSON 422 含 { errors: {...} }
请求路径以 /api 开头              →  同上（隐式判断）
其他                              →  redirect()->back()->withErrors()
```

→ **同一段验证规则，同时支持"网页表单"和"JSON API"**——这是 Form Request 设计的精妙。

#### ③ Policy 完全复用 ⭐

```php
$this->authorize('create', Post::class);
```

`PostPolicy` 没改一行。`?User` vs `User` 类型签名设计在这里发挥威力——
- Web 路由：guest → Laravel 给 `?User` 传 null
- API 路由：guest → 同样行为

#### ④ HTTP 状态码语义化

```php
return PostResource::make($post)->response()->setStatusCode(201);   // ⭐ 创建用 201

return response()->json(['message' => '...'], 200);   // 其他用 200
```

| 操作 | 推荐状态码 |
|---|---|
| 创建成功 | **201 Created** ⭐ |
| 更新成功 | 200 OK |
| 删除成功 | 200 OK 或 204 No Content |
| 验证失败 | 422 Unprocessable Entity（Laravel 自动） |
| 未登录 | 401 Unauthorized |
| 无权限 | 403 Forbidden |
| 不存在 | 404 Not Found |
| 服务器错误 | 500 |

→ ThinkPHP 老手最容易踩的坑：**所有响应都返 200 + 自定义 code 字段**。这违反 HTTP 协议——浏览器、CDN、监控工具看到 200 就以为成功。

#### ⑤ 路由名空间分离

```php
Route::name('api.')->group(function () {
    Route::apiResource('posts', PostController::class);
});
```

→ Web 是 `posts.show`，API 是 `api.posts.show`。Web 视图 `route('posts.show', $post)` 永远拿到 `/posts/xxx`。

#### ⑥ 队列复用第 15 章

```php
if ($post->published_at?->isPast()) {
    SendPostPublishedEmailJob::dispatch($post);
}
```

→ 第 15 章写的 Job 在 API 端**完全可用**——dispatch 1 行触发整套异步邮件。

### Laravel 12 兼容性坑 ⭐

第一版我用了 `authorizeResource()`：

```php
public function __construct()
{
    $this->authorizeResource(Post::class, 'post');
}
```

→ 跑测试报：

```
Error: Call to a member function only() on array
at AuthorizesRequests.php:104
```

**根因**：`authorizeResource()` 内部假设 controller 有 `$this->middleware(...)` 实例方法（Laravel 10 时代）。Laravel 11+ 引入 `HasMiddleware` 接口后**移除了这个实例方法**。

详见**第 8 节**深度分析。

### Sprint 4 完成验收

```bash
✓ Api\PostController 5 个 RESTful 方法
✓ routes/api.php 含 Route::apiResource
✓ php artisan route:list 显示 8 条 api 路由
```

---

## 6. Sprint 5：API 错误响应规范化（bootstrap/app.php）

### 默认 Laravel 12 在 API 上的"不友好"

| 错误场景 | 默认行为 | 期望行为 |
|---|---|---|
| 未登录访问 `/api/posts` (POST) | 重定向到 `/login` 网页 ❌ | JSON `{message: "..."}` 401 |
| 文章不存在 `/api/posts/foo` | HTML 404 错误页 ❌ | JSON `{message: "Not Found"}` 404 |
| Policy deny | HTML 403 错误页 ❌ | JSON `{message: "..."}` 403 |
| 验证失败（Form Request） | ✓ 自动 JSON 422 | ✓ 自动（已工作） |

→ 前 3 个需要在 `bootstrap/app.php` 注册 API 异常处理器。

### bootstrap/app.php 改动

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
    });

    $exceptions->render(function (AuthorizationException $e, Request $request) {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => $e->getMessage() ?: 'This action is unauthorized.',
            ], 403);
        }
    });

    $exceptions->render(function (ModelNotFoundException $e, Request $request) {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }
    });

    $exceptions->render(function (NotFoundHttpException $e, Request $request) {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Endpoint not found.'], 404);
        }
    });
})
```

### 闭包返回 null 的精妙

```php
$exceptions->render(function (AuthenticationException $e, Request $request) {
    if ($request->is('api/*') || $request->expectsJson()) {
        return response()->json([...], 401);
    }
    // ↑ 不是 API → 没 return → 返回 null
});
```

→ **闭包返回 null = Laravel 继续走默认渲染逻辑**。
→ 所以 web 路由的"未登录跳 /login"、"404 错误页"等行为**完全不变**。

这是 Laravel 11+ `withExceptions` 设计的关键——**渐进式拦截**而不是粗暴覆盖。

### `$request->is('api/*') || $request->expectsJson()`

双判断的好处：

| 场景 | `is('api/*')` | `expectsJson()` |
|---|---|---|
| GET `/api/posts` | ✓ | 取决于 Accept 头 |
| GET `/posts` 带 `Accept: application/json`（前端 fetch） | ❌ | ✓ |
| GET `/posts`（普通浏览器） | ❌ | ❌ |

→ **前两种都返 JSON，最后一种返 HTML**。这是兼容"传统 web + 现代 SPA"的标准模式。

### Sprint 5 完成验收

```bash
✓ bootstrap/app.php 注册了 4 个异常 JSON 渲染器
✓ web 测试 31 passed（行为没变化）
✓ 错误响应格式：401 / 403 / 404 都返 JSON
```

---

## 7. Sprint 6：18 个 Pest API 测试

### 测试组织

```
tests/Feature/Api/
├── AuthApiTest.php   (6 测试)
└── PostApiTest.php   (12 测试)
```

→ **新建 `Api/` 子目录** 让 web 测试和 API 测试分开。`php artisan test --filter Api` 一键跑所有 API 测试。

### 6 大测试技巧

#### ① `Sanctum::actingAs($user)` —— 模拟"已登录用户"

```php
$user = User::factory()->create();
Sanctum::actingAs($user);

getJson('/api/me')->assertOk();
```

→ **不需要先 POST /api/login 拿 token**——直接模拟"已登录"。这是 API 测试的标准技巧。

#### ② `assertJsonStructure` vs `assertJsonPath`

```php
->assertJsonStructure([
    'data' => ['id', 'title', '...'],
    'links' => [...],
    'meta' => [...],
])
->assertJsonPath('data.author.id', $author->id)
```

| 断言 | 用途 |
|---|---|
| `assertJsonStructure` | 验证**字段存在**（不验证值） |
| `assertJsonPath` | 验证**特定字段的值** |

→ 配合用：先验**字段在不在**，再验**值对不对**。

#### ③ `assertJsonValidationErrors`

```php
->assertStatus(422)
->assertJsonValidationErrors(['email', 'device_name']);
```

→ 一行同时验证：
- 状态码 422
- 响应 JSON 含 `errors.email` 和 `errors.device_name` 字段
- 错误格式符合 Laravel 标准（`{message, errors: {field: [msg]}}`）

#### ④ `assertExactJson` —— 严格相等

```php
getJson('/api/me')
    ->assertStatus(401)
    ->assertExactJson(['message' => 'Unauthenticated.']);
```

→ **必须**和给定数组**完全一致**——多一个字段都不行。适合"严格契约"场景。

#### ⑤ Pest expect 链 + collection 操作

```php
$titles = collect($response->json('data'))->pluck('title');
expect($titles)->toContain('Published one')
    ->not->toContain('Hidden draft');
```

→ Laravel collection + Pest `not` 链——读起来像英语。

#### ⑥ HTTP method helpers

```php
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;
```

→ 这四个助手自动设置 `Accept: application/json` 头——**保证响应是 JSON**，避免被 web 路由意外抢走。

### 关键测试 ⭐：`it exposes only id and name on the embedded author (no email leak)`

```php
it('exposes only id and name on the embedded author (no email leak)', function () {
    $post = Post::factory()->create();

    $response = getJson("/api/posts/{$post->slug}")->assertOk();

    expect($response->json('data.author'))
        ->toHaveKeys(['id', 'name'])
        ->not->toHaveKey('email')
        ->not->toHaveKey('password');
});
```

**为什么这个测试这么重要**：
- 它**钉死了 UserResource 的字段白名单**
- 如果某天有人改 UserResource 加了 `email` 字段——这个测试**立刻挂掉**

→ **这就是测试的"防退化"价值**——把今天的"业务规则"钉死，未来有人改坏会被立刻发现。

### Sprint 6 完成验收

```bash
✓ tests/Feature/Api/AuthApiTest.php 6 测试 passed
✓ tests/Feature/Api/PostApiTest.php 12 测试 passed
✓ php artisan test 整套 58 passed (180 assertions) in 1.16s
```

---

## 8. 重大踩坑：authorizeResource() 在 Laravel 11+ 的不兼容

### 这次最值的踩坑

**整个第 14 章实战，最值的不是 18 个 API 测试 passed**，而是 Sprint 4 那次 `authorizeResource` 不兼容。**真实生产里 90% 的从 v9/v10 升级到 v11/v12 的项目都会撞**。

### 现象

```
Error: Call to a member function only() on array
at vendor\laravel\framework\src\Illuminate\Foundation\Auth\Access\AuthorizesRequests.php:104
```

完整 trace 显示：
```
PostController->__construct() →
  authorizeResource('App\\Models\\Post', 'post') →
    ⚠️ 内部某个数组上调 ->only() 失败
```

### 根因（一图看懂）

```
┌────────────────────────────────────────────┐
│ Laravel ≤ 10 时代的 Controller             │
├────────────────────────────────────────────┤
│ class PostController extends Controller    │
│ {                                          │
│   public function __construct() {          │
│     $this->middleware('auth');     ⭐      │
│     $this->authorizeResource(...);         │
│   }                                        │
│ }                                          │
│                                            │
│ Controller::middleware() 是【实例方法】     │
│ authorizeResource() 内部调它              │
└────────────────────────────────────────────┘
                    ↓
                   v11
                    ↓
┌────────────────────────────────────────────┐
│ Laravel 11+ 的 Controller                  │
├────────────────────────────────────────────┤
│ class PostController extends Controller    │
│   implements HasMiddleware                 │
│ {                                          │
│   public static function middleware()      │
│   {                                        │
│     return [...];                          │
│   }                                        │
│ }                                          │
│                                            │
│ ❌ Controller 不再有 middleware() 实例方法 │
│ ❌ authorizeResource() 内部调它会崩       │
└────────────────────────────────────────────┘
```

### 3 个解决方案

| 方案 | 写法 | 推荐度 |
|---|---|---|
| **A. 手动 authorize**（本次用的） | 每个方法 `$this->authorize('action', $model)` | ⭐⭐⭐ |
| B. 旧式 controller | 不实现 `HasMiddleware`，用 `__construct` + `$this->middleware()` | ⭐ |
| C. 等待官方 fix | Laravel 12.5+ 可能修复 | ❌ |

### 方案 A 的实现

```php
// ❌ 不工作
public function __construct()
{
    $this->authorizeResource(Post::class, 'post');
}

// ✓ 工作
public function index(): AnonymousResourceCollection
{
    $this->authorize('viewAny', Post::class);
    // ... 业务代码
}

public function store(StorePostRequest $request): JsonResponse
{
    $this->authorize('create', Post::class);
    // ... 业务代码
}
// ... 每个方法手动 authorize
```

### 教训：Boost Guidelines 应该提示

→ AI 训练数据里**大量** Laravel 9/10 的 `authorizeResource()` 用法。Boost 的 `controllers.md` 应该明确写：

> **Laravel 11+：不要用 `authorizeResource()` 在 `HasMiddleware` 接口的 controller 里。**
> 在每个方法手动 `$this->authorize()`。

### 元意义

> 这次失败的真正价值，**不是修复它**，而是**让你看到**：
>
> 1. **AI 训练数据陈旧**是最大风险——`authorizeResource()` 是 AI 第一反应推荐的写法
> 2. **Boost 的 `application-info` 工具**应该早期介入——确认 Laravel 12 → AI 应当避免该 API
> 3. **跑测试是最快验证方式**——靠肉眼看代码很难发现这种"运行时才暴露"的兼容问题
>
> 这套**调试流程**复用到任何 Laravel 升级项目。

---

## 9. ThinkPHP vs Laravel API 开发对照

### API 概念对照

| ThinkPHP 6 | Laravel 12 |
|---|---|
| 写一个独立的 ApiController 模块 | 在 Controllers 下建 `Api/` 子命名空间 |
| 路由 `route/api.php`（自己建） | `routes/api.php`（`install:api` 自动建） |
| `Route::resource('posts', ...)` | `Route::apiResource('posts', ...)`（去掉 create/edit） |
| 路径前缀手写 `/api` | `bootstrap/app.php` `withRouting(api: ...)` 自动加前缀 |

### 认证 / Token 对照

| ThinkPHP 6 | Laravel 12 |
|---|---|
| 自己实现 token 表 + JWT 解析 | 内置 `Sanctum` + `personal_access_tokens` 表 |
| 自己写 token 生成 + 验证逻辑 | `$user->createToken('name')->plainTextToken` |
| 中间件自己写 token 解析 | `auth:sanctum` 中间件一行 |
| 客户端发 token：自己定义 header | 标准 `Authorization: Bearer {token}` |
| 撤销 token：自己删表 | `$user->currentAccessToken()->delete()` |

### 响应序列化对照

| ThinkPHP 6 | Laravel 12 |
|---|---|
| 直接 `json($data)` 返回 | `JsonResource` 类做字段白名单 |
| 字段控制：手动写 `unset` 或选择字段 | Resource 的 `toArray()` 显式列出 |
| 时间格式：自己写 `date('Y-m-d', $time)` | `$post->published_at->toIso8601String()` |
| 关系字段：手动 `with` + `toArray` | `whenLoaded('author')` 自动判断 |
| 分页：手动包装 `data` + `meta` | Resource collection 自动包 `data + links + meta` |

### 验证对照

| ThinkPHP 6 | Laravel 12 |
|---|---|
| Validate 类放 `app/validate/` | FormRequest 类放 `app/Http/Requests/` |
| API 失败：自己 catch + 返 JSON | FormRequest 自动按 `Accept` 头返 JSON 422 |
| API 和 Web 共用验证：复制粘贴 | **同一个 FormRequest，自动适配两端** ⭐ |

### 错误响应对照

| ThinkPHP 6 | Laravel 12 |
|---|---|
| 全部 200 + 自定义 `code` 字段 | 状态码语义化（201 / 401 / 403 / 422 / 404） |
| 错误格式：自己定义 | Laravel 标准 `{message, errors: {...}}` |
| 异常处理：自己写 ExceptionHandler | `bootstrap/app.php` `withExceptions` 注册 |

### 一句话总结差异

> **ThinkPHP API**：你装包，自己写 token、自己包响应、自己处理异常。
>
> **Laravel API**：`install:api` + `Sanctum` + `JsonResource` + `FormRequest`——四件套搭起来，**复用 web 的所有业务逻辑**（Form Request / Policy / Model）。

→ ThinkPHP 老手最容易吃惊的：**Web 端写好的 `StorePostRequest` / `PostPolicy` 完全不改一行直接服务于 API**。这是 Laravel"约定优先"哲学的最佳例子。

---

## 10. 完整代码索引

### 8 个文件的索引

#### 新增（6 个）

```
playground/app/Http/Controllers/Api/AuthController.php          47 行
playground/app/Http/Controllers/Api/PostController.php          80 行
playground/app/Http/Resources/PostResource.php                  21 行
playground/app/Http/Resources/UserResource.php                  17 行
playground/tests/Feature/Api/AuthApiTest.php                   71 行（6 测试）
playground/tests/Feature/Api/PostApiTest.php                   154 行（12 测试）
```

#### 修改（3 个）

```
playground/app/Models/User.php                                  （加 HasApiTokens trait）
playground/routes/api.php                                       （8 条路由）
playground/bootstrap/app.php                                    （4 个异常 JSON 渲染器）
```

#### 复用（0 改动 ⭐）

```
playground/app/Http/Requests/StorePostRequest.php               （web/api 共用）
playground/app/Http/Requests/UpdatePostRequest.php              （web/api 共用）
playground/app/Policies/PostPolicy.php                          （web/api 共用）
playground/app/Models/Post.php                                  （含所有 scope）
playground/app/Jobs/SendPostPublishedEmailJob.php               （第 15 章产物）
```

### 关键命令清单（按 Sprint 出场顺序）

```bash
# Sprint 1
composer require laravel/sanctum
php artisan install:api      # 询问"装 Sanctum?" 选 yes
php artisan migrate

# Sprint 2 — smoke test 用临时 PHP 文件
php storage/smoke-token.php

# Sprint 4
php artisan route:list --path=api    # 应该看到 8 条路由

# Sprint 6
php artisan test --filter "AuthApiTest|PostApiTest"
php artisan test                     # 全套 58 passed

# 真实交互（手动 curl）
curl.exe -X POST http://127.0.0.1:8000/api/login `
  -H "Accept: application/json" `
  -H "Content-Type: application/json" `
  -d '{\"email\":\"test@example.com\",\"password\":\"password\",\"device_name\":\"my-laptop\"}'

curl.exe http://127.0.0.1:8000/api/me `
  -H "Authorization: Bearer 你的token" `
  -H "Accept: application/json"
```

---

## 11. 复盘总结 + 教程整体里程碑

### 6 个可以继续玩的方向

| # | 方向 | 难度 | 学到什么 |
|---|---|---|---|
| 1 | 加 Token Abilities（每个 token 限定权限） | ⭐⭐ | `createToken('app', ['posts:read'])` |
| 2 | 加 Rate Limiting（限制 60 req/min） | ⭐ | `RateLimiter::for(...)` |
| 3 | 加 API 版本管理（`/api/v1/` `/api/v2/`） | ⭐⭐ | 路由前缀 + Resource 子类 |
| 4 | 加 Sanctum SPA 模式（同域 cookie 认证） | ⭐⭐⭐ | CSRF 配合 Sanctum |
| 5 | 加 OpenAPI/Swagger 文档 | ⭐⭐ | 装 `darkaonline/l5-swagger` |
| 6 | 加自定义 API Throttling Job | ⭐⭐⭐ | RateLimiter + Cache |

### 推荐 Boost 演示场景

| 推荐度 | 方向 | Boost 工具组合 |
|---|---|---|
| 🔥 高 | 1. Token Abilities | search-docs（找 abilities API） + tinker（试 abilities） |
| 🔥 高 | 5. OpenAPI 文档 | search-docs（找 swagger 配置） + read-config-keys（看路由） |

---

## 复盘总结

### 这次实战回答了 3 个问题

#### Q1：为什么 Laravel API 比 ThinkPHP API 强？

**A**：从"自己拼装" → "约定 + 复用"。具体收益：
- `install:api` 一行代替"手动配 routes/api.php + middleware + provider"
- Sanctum 一行代替"自己写 token 表 + JWT 解析"
- JsonResource 一行代替"手动 unset 字段 + 时间格式化"
- **Form Request / Policy 完全复用** ⭐⭐⭐ —— 同一段验证规则同时服务 web 和 API

#### Q2：这次踩坑值得吗？

**A**：**值得**。`authorizeResource()` 不兼容 `HasMiddleware` 是 Laravel 11+ 升级时的高频问题。这次让你知道：
- AI 训练数据陈旧是真实风险
- Boost 的 `application-info` 应该早期介入避免该 API
- 测试是发现兼容问题的**最快**方式（不靠肉眼读代码）

#### Q3：Boost 在这次起了什么作用？

**TOP 3**：
1. **`application-info`**——确认 Laravel 12，让 AI 用 `Foundation\Queue\Queueable`、ISO8601 时间、JsonResource 等 v12 标准
2. **`search-docs`**——查到 `Sanctum::actingAs` / `assertJsonValidationErrors` / Resource 的最新 v12 用法
3. **`database-query`**——验证 `personal_access_tokens` 表内容、Token 是否真的写入

→ 详见 [`docs/12-Boost工具实战大全.md`](./12-Boost工具实战大全.md)。

---

## 教程整体里程碑（截至本章）

```
docs/
├── Laravel_Boost_教程大纲.md                326 行 ✓
├── 03-环境搭建实测.md                        383 行 ✓
├── 06-11-章节速查.md                       1485 行 ✓
├── 12-Boost工具实战大全.md                   530 行 ✓
├── 13-博客实战完整复盘.md                   1279 行 ✓
├── 14-API化实战.md                         本篇 ✓ NEW
├── 15-队列实战.md                          1077 行 ✓
└── 17-Prompt工程反例集.md                  1549 行 ✓

playground/ 实战项目状态：
- Laravel 12.58 + Sanctum 4.3.2 + Pest 3
- 14 个数据表（含 jobs / failed_jobs / personal_access_tokens / posts）
- 58 个 Pest 测试全 passed in 1.16s
- "博客 + 认证 + 队列 + API" 四件套实战项目
```

→ **6/18 主章节成稿 + 7 篇实测笔记**。教程"实战篇 + 进阶篇"骨架基本完成。

剩余优先级：
- 第 1-2 章（认知篇）—— 给教程一个像样的"为什么"开头
- 第 16 章 Filament 后台
- 第 18 章 部署 + 性能 + 安全

---

> **最终战果**：1.5 小时 → 完整 API 系统（认证 + CRUD + 错误规范 + 18 测试）
> **Boost 的角色**：让 AI 用 Laravel 12 最新 API 写代码，避开 `authorizeResource` 兼容性陷阱
> **下一步**：选择性补 Token Abilities + OpenAPI 文档；或继续做第 1/16/18 章


