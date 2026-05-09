# 第 16 章 实测笔记：Filament 后台（v5.6.2）

> 实测时间：2026-05-08（约 1.5 小时）
> 项目：`playground/`（在第 13/14/15 章基础上加管理后台）
> 关联大纲：[第 16 章 生态包实战](./Laravel_Boost_教程大纲.md)
>
> **本章定位**：用 Filament 一行命令出整套博客管理后台——含 1 个**重大版本意外**（装上的是 v5.6.2 不是 v3）+ 1 个**Policy `before()` hook 复用**的精彩 pattern。

---

## 0. 战果速览

### 数字看战果

| 维度 | 数字 |
|---|---|
| 总耗时 | 约 1.5 小时 |
| Sprint 数 | 7 个 |
| 装上的 Filament 版本 | **v5.6.2** ⭐（教程预期是 v3，结果跳了 2 个主版本） |
| Filament 装的子包 | 11 个（filament/{filament,support,actions,notifications,schemas,infolists,forms,widgets,query-builder,tables}） |
| 新增/修改文件 | 9 个 |
| Filament Resource 文件结构 | **6 个文件**（v5 模块化），**v3 是 1 个文件** |
| PHP 代码行 | ~250 行 |
| 测试用例 | **5 个新增**（AdminPanelTest） |
| 测试断言 | 9 条新增 |
| **总测试** | **58 → 63 passed** in 2.79s |
| 复用 web 端资产 | Post 模型 / PostPolicy（**只加一个 `before()` hook**） |

### 时间线

```
T+0:00   "把博客加管理后台" → AI 拆 7 个 Sprint
T+0:05   composer require filament/filament:"^3.2" → ❌ 不兼容 Laravel 12
T+0:08   去掉版本约束 → Composer 自动选 v5.6.2 ⭐ 版本意外
T+0:15   php artisan filament:install --panels → AdminPanelProvider 生成
T+0:20   route:list --path=admin 看到 3 条路由（dashboard / login / logout）
T+0:25   migration 加 is_admin 字段
T+0:30   User implements FilamentUser + canAccessPanel()
T+0:35   Seeder 把 test@example.com 升级为 admin
T+0:42   make:filament-resource Post --generate 生成 6 文件 stub
T+0:55   改造 PostForm（Select 关联 + live slug 生成）
T+1:05   改造 PostsTable（状态徽章 + 过滤器 + 搜索）
T+1:15   PostPolicy 加 before() hook 让 admin bypass
T+1:25   写 5 个 AdminPanelTest 测试
T+1:30   63 passed (189 assertions) in 2.79s
T+1:40   写本笔记
```

→ **T+0:08 那次"版本意外"是这次最大教训**——Filament 已经从 v3 → v4 → v5，但网上 90% 中文教程仍是 v3。

### 后台 UI 截屏（文字描述）

```
┌─────────────────────────────────────────────────────────────────┐
│  Filament Admin Panel  /admin                                    │
├─────────────────────┬───────────────────────────────────────────┤
│ ▸ Dashboard         │ Blog Posts                          [+ New]│
│ ▸ Blog Posts ⭐     │                                            │
│                     │ ┌──────────────────────────────────────┐  │
│                     │ │ Title    Author    Status   Date     │  │
│                     │ ├──────────────────────────────────────┤  │
│                     │ │ Hello    Test User [Published] 5/7   │  │
│                     │ │ Draft 1  Bob       [Draft]            │  │
│                     │ │ Future   Alice     [Scheduled] 6/1    │  │
│                     │ └──────────────────────────────────────┘  │
│                     │                                            │
│                     │ Filters: [Status ▾] [Author ▾]             │
│                     │ Actions: [View] [Edit] [Delete]            │
│                     │ Bulk:    [☐] Delete Selected               │
└─────────────────────┴───────────────────────────────────────────┘
```

---

## 1. 起点：一句话需求 + 版本意外

### 给 AI 的原始 prompt

```
我要给博客系统加一个 Admin 后台：
- 用 Filament（Laravel 生态最流行的后台脚手架）
- 路径前缀 /admin
- 复用现有的 User 表（加 is_admin 字段控制访问，不另建 admins 表）
- 复用 PostPolicy（13 章已写）—— admin 应该能管理任何人的文章
- 不要装 Breeze/Jetstream（我们已经有自己的 web 认证系统）
- 后台要展示：
  - 文章列表（含状态徽章 草稿/定时/已发布）
  - 按状态过滤、按作者过滤
  - 搜索（标题 / slug / 正文）
  - 创建/编辑/删除文章
- 写测试钉死"非 admin 用户不能进后台"

完成后告诉我访问 /admin 应该看到什么。
```

### 第一步就踩坑：版本意外 ⭐

我让 AI 跑：

```bash
composer require filament/filament:"^3.2" -W
```

Composer 报错：

```
filament/filament v3.2.0 requires illuminate/console ^10.0
```

→ **Filament v3.2 是为 Laravel 10 写的**，不兼容 Laravel 12。

去掉版本约束：

```bash
composer require filament/filament -W
```

Composer 自动选 **v5.6.2**——比 v3.2 跳了 **2 个主版本**！

### 这意味着什么

| 我的预期（基于网上教程） | 实测装上的 v5.6.2 |
|---|---|
| 单文件 PostResource | **6 文件模块化结构** |
| `Filament\Forms\Form` | **`Filament\Schemas\Schema`** |
| `Filament\Tables\Actions\EditAction` | **`Filament\Actions\EditAction`**（命名空间统一） |
| `->actions([...])` | **`->recordActions([...])`** |
| `->bulkActions([...])` | **`->toolbarActions([...])`** |

→ **照抄 v3 教程到 v5 项目，会到处编译错误**。

### 这次教训值得记下来

> **Composer require 不锁版本时**，会自动选满足 PHP/Laravel 约束的**最新稳定版**。
> 在快速迭代的生态包（Filament / Livewire / Inertia）里，**这就是查"当前主流版本"的最快方式**。
>
> ```bash
> composer require some/package --dry-run
> ```
> `--dry-run` 不真的安装，只打印它**会**装哪个版本——查版本最快。

---

## 2. Sprint 1：装 Filament + 初始化 panel

### 3 条命令搞定

```bash
composer require filament/filament -W           # 不锁版本，让 Composer 自动选 v5.6.2
php artisan filament:install --panels           # 创建 admin panel
php artisan optimize:clear                       # 清缓存让 provider 生效
```

`-W` = `--with-all-dependencies` 让 Composer 升级被锁的依赖（Filament 依赖较多，Livewire 3 + Alpine 等）。

### `php artisan filament:install --panels` 做了什么

| # | 动作 |
|---|---|
| 1 | 创建 `app/Providers/Filament/AdminPanelProvider.php` |
| 2 | 在 `bootstrap/providers.php` 注册该 provider |
| 3 | 注册 `/admin` 路由前缀 |
| 4 | 发布前端资源（fonts / css / js）到 `public/css/filament/` 等 |
| 5 | 询问 panel ID（默认 admin） |

### v5 的 AdminPanelProvider

```php
return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login()
    ->colors([
        'primary' => Color::Amber,
    ])
    ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
    ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
    ->pages([
        Dashboard::class,
    ])
    ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
    ->widgets([
        AccountWidget::class,
        FilamentInfoWidget::class,
    ])
    ->middleware([...])
    ->authMiddleware([
        Authenticate::class,
    ]);
```

| 链 | 作用 |
|---|---|
| `->default()` | 标记为默认 panel（多 panel 时） |
| `->id('admin')` | panel 唯一 id |
| `->path('admin')` | URL 前缀 |
| `->login()` | 启用内置登录页 |
| `->colors([...])` | 主题色 |
| `->discoverResources(...)` | **自动扫描** `app/Filament/Resources/` 注册所有 Resource |
| `->discoverPages(...)` | 同上，扫页面 |
| `->discoverWidgets(...)` | 同上，扫 widget |
| `->middleware([...])` | 应用中间件 |
| `->authMiddleware([...])` | 登录后才生效的中间件 |

→ **panel 配置在 v3/v5 之间几乎一致**——破坏性变化在 Resource 内部。

### 验收

```bash
php artisan route:list --path=admin
```

应该看到：

```
GET|HEAD   admin           filament.admin.pages.dashboard
GET|HEAD   admin/login     filament.admin.auth.login
POST       admin/logout    filament.admin.auth.logout
```

→ **3 条路由就位**，但还不能登录——需要 Sprint 2 加 admin 用户。

### Sprint 1 完成验收

```bash
✓ composer.json 含 filament/filament v5.6.2
✓ AdminPanelProvider.php 生成
✓ /admin /admin/login /admin/logout 3 条路由
✓ public/css/filament/ public/js/filament/ 资源发布
```

---

## 3. Sprint 2：FilamentUser 接口 + admin 字段

### 设计决策

**默认 Filament 行为**：**任何** `User` 表的用户都能登录后台（只要邮箱密码对）。这显然不安全。

**我们的策略**：
1. 加 `is_admin` boolean 字段到 `users` 表
2. User 实现 `FilamentUser` 接口 + `canAccessPanel()` 方法
3. 把现有 `test@example.com` 升级为 admin（复用，不另建账号）

### Migration

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_admin')->default(false)->after('email');
    });
}
```

| 修饰符 | 作用 |
|---|---|
| `boolean()` | SQLite/MySQL 都是 TINYINT(1) |
| `default(false)` | **关键**——已有用户默认非 admin（不会"加字段后所有人都成 admin"） |
| `after('email')` | 字段位置（仅 MySQL，SQLite 忽略） |

### User 实现 FilamentUser 接口

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'is_admin',                          // ⭐ 加 fillable
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',         // ⭐ 加 cast
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }
}
```

### 5 个值得停下看的细节

#### ① `FilamentUser` 接口 + `canAccessPanel()` 是 v5 的入口

**没实现这个方法的话**：默认行为是"任何 User 都能登录后台"——开发期方便，**生产期严重漏洞**。

→ Filament v5 故意让你**显式实现**这个方法——逼你思考"谁能进后台"这个核心安全问题。

#### ② `Panel $panel` 参数允许"多 panel 不同规则"

```php
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin' => $this->is_admin,
        'merchant' => $this->is_merchant,
        default => false,
    };
}
```

→ 一个 app 里如果有多个 Filament panel（比如总后台 + 商家后台），**同一个 User 表可以分别控制访问权限**。

#### ③ Model `casts()` 加 `'is_admin' => 'boolean'`

没有这一行，`$user->is_admin` 会是字符串 `'1'` 或 `'0'`（SQLite 存的是 INT，PHP 取出后是 string）。

`=== true` 严格比较会失败。**cast 必加**。

#### ④ Seeder 升级 test@example.com 为 admin

```php
if (! $testUser->is_admin) {
    $testUser->forceFill(['is_admin' => true])->save();
}
```

`forceFill` 绕过 `$fillable` 限制——**防御性写法**：以后即使有人移除 fillable 也不会挂掉。

#### ⑤ 验证字段更新成功

```bash
php storage/smoke-admin.php
```

输出：

```
[ADMIN] Test User              test@example.com
        Ryann Becker           nickolas71@example.org
        Colleen Abshire Jr.    metz.suzanne@example.net
        ...
```

→ **只有 test@example.com 是 admin**，其他 5 个 factory 用户**不是 admin**。

### Sprint 2 完成验收

```bash
✓ users 表加了 is_admin 字段
✓ User 实现 FilamentUser 接口
✓ test@example.com 升级为 admin
✓ 浏览器：用 test@example.com 登录 /admin/login → 看到 dashboard
✓ 浏览器：用别的 factory 用户邮箱登录 → 403 Forbidden
```

---

## 4. Sprint 3：生成 PostResource（v5 模块化结构）

### 用 Filament 自己生成 stub（绕过"网上教程过时"风险）

```bash
echo title | php artisan make:filament-resource Post --generate --no-interaction
```

**关键参数**：
- `--generate` —— **根据 Post 模型字段自动生成 form/table 字段**
- `--no-interaction` —— 跳过交互问题
- `echo title |` —— 通过 stdin 回答 "title attribute" 问题（用 `title` 字段当记录标签）

### v5 的"模块化" 6 文件结构

```
app/Filament/Resources/Posts/
├── PostResource.php              ← 主类（路由 + 元信息）
├── Pages/
│   ├── ListPosts.php
│   ├── CreatePost.php
│   └── EditPost.php
├── Schemas/
│   └── PostForm.php              ← 表单字段（v5 新分离）
└── Tables/
    └── PostsTable.php            ← 表格列 + 过滤 + 操作
```

→ **v3 把全部塞 PostResource 一个文件**（容易长到 300+ 行）。
→ **v5 拆成 6 个文件** —— 表单和表格独立，单一职责更清晰。

### PostResource.php 主类

```php
class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Blog Posts';
    protected static ?string $modelLabel = 'Post';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'body'];
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);   // ⭐ 委托给独立 schema 类
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit'   => EditPost::route('/{record}/edit'),
        ];
    }
}
```

### 改造后的 PostForm（关键 3 处）

```php
class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)                                    // ⭐ 失焦响应式
                    ->afterStateUpdated(function (?string $state, callable $set, ?string $context) {
                        if ($context === 'create' && $state) {
                            $set('slug', Str::slug($state));                // ⭐ 自动生成 slug
                        }
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('URL-friendly identifier. Auto-generated from title.'),

                Textarea::make('body')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),

                Select::make('user_id')                                     // ⭐ 关联下拉
                    ->label('Author')
                    ->relationship('author', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id()),

                DateTimePicker::make('published_at')
                    ->label('Publish at')
                    ->seconds(false)
                    ->helperText('Leave empty for draft. Set future time for scheduled post.'),
            ]);
    }
}
```

### 3 个 v5 表单亮点

#### ① `live(onBlur: true)` + `afterStateUpdated` —— 响应式

→ 标题失焦时**自动填充 slug**——不用前端写一行 JS。
→ `$context === 'create'` 防止"编辑文章时也覆盖手动改过的 slug"。

#### ② `Select` 关联 + `searchable` + `preload`

| 链 | 作用 |
|---|---|
| `relationship('author', 'name')` | 用 Post 模型的 `author()` 关系 + 显示 User 的 `name` 字段 |
| `searchable()` | 输入框搜索（用户多时不卡） |
| `preload()` | 一次性加载所有选项（用户少时用） |
| `default(fn () => auth()->id())` | 默认作者 = 当前登录的 admin |

#### ③ `unique(ignoreRecord: true)` —— 编辑时忽略自己

```php
->unique(ignoreRecord: true)
```

→ 等价于 web 端 `Rule::unique('posts', 'slug')->ignore($this->route('post'))`，但写法精简得多。

### Sprint 3 完成验收

```bash
✓ make:filament-resource 生成 6 个文件
✓ 改造 PostForm：live slug + Select author + helperText
✓ 改造 PostResource：navigationLabel + globally searchable
```

---

## 5. Sprint 4：表格增强（状态徽章 + 过滤器）

### 改造后的 PostsTable（5 处增强）

```php
public static function configure(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')
                ->searchable()
                ->limit(40)                          // 截断长标题
                ->sortable(),

            TextColumn::make('author.name')          // ⭐ 关系字段
                ->label('Author')
                ->searchable()
                ->sortable(),

            TextColumn::make('status')               // ⭐ 虚拟字段（计算列）
                ->label('Status')
                ->badge()
                ->state(function ($record): string {
                    if ($record->published_at === null) {
                        return 'Draft';
                    }
                    return $record->published_at->isFuture() ? 'Scheduled' : 'Published';
                })
                ->color(fn (string $state) => match ($state) {
                    'Draft'     => 'gray',
                    'Scheduled' => 'warning',
                    'Published' => 'success',
                }),

            TextColumn::make('published_at')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->placeholder('—'),

            TextColumn::make('created_at')
                ->dateTime('Y-m-d H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->defaultSort('published_at', 'desc')
        ->filters([
            SelectFilter::make('status')             // ⭐ 自定义 query 过滤器
                ->options([
                    'draft'     => 'Draft',
                    'scheduled' => 'Scheduled',
                    'published' => 'Published',
                ])
                ->query(function ($query, array $data) {
                    return match ($data['value'] ?? null) {
                        'draft'     => $query->whereNull('published_at'),
                        'scheduled' => $query->where('published_at', '>', now()),
                        'published' => $query->whereNotNull('published_at')->where('published_at', '<=', now()),
                        default     => $query,
                    };
                }),

            SelectFilter::make('author')
                ->relationship('author', 'name')
                ->searchable()
                ->preload(),
        ])
        ->recordActions([                            // ⭐ v5 改名（v3 是 actions）
            ViewAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->toolbarActions([                           // ⭐ v5 改名（v3 是 bulkActions）
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
}
```

### 5 个 v5 表格亮点

#### ① 状态徽章（虚拟字段 + 颜色映射）

`status` 字段**实际不在数据库里**——是**计算字段**。

`state()` 闭包从 `published_at` 计算出 'Draft' / 'Scheduled' / 'Published'，`color()` 根据状态值返回 Tailwind 颜色名。

→ 这是 Filament 的"虚拟字段"模式——把 web 端的"业务规则"（什么是已发布）**搬到表格列**显示。

#### ② 自定义 query 的 SelectFilter

```php
->query(function ($query, array $data) {
    return match ($data['value'] ?? null) {
        'draft'     => $query->whereNull('published_at'),
        'scheduled' => $query->where('published_at', '>', now()),
        'published' => $query->whereNotNull('published_at')->where('published_at', '<=', now()),
        default     => $query,
    };
})
```

→ 用户选"Draft" → 闭包决定查询如何修改。
→ 这其实就是**第 13 章 Post 模型 3 个 scope 的 UI 暴露**。

#### ③ 关系字段过滤器

```php
SelectFilter::make('author')
    ->relationship('author', 'name')
    ->searchable()
    ->preload(),
```

→ 一行得到"按作者过滤"——下拉显示所有用户的 name，搜索 + 预加载。

#### ④ Action 在 `Filament\Actions\` 命名空间下（v5 重构）

| v3 | v5 |
|---|---|
| `Filament\Tables\Actions\EditAction` | `Filament\Actions\EditAction` |
| `Filament\Tables\Actions\BulkActionGroup` | `Filament\Actions\BulkActionGroup` |

→ v5 把 actions **统一到一个命名空间**——这样 form / table / page 共用一套 action 类。

#### ⑤ `recordActions` / `toolbarActions` 改名

| v3 | v5 |
|---|---|
| `->actions([...])` | `->recordActions([...])` |
| `->bulkActions([...])` | `->toolbarActions([...])` |

→ 命名更准确：**recordActions = 行级动作**（每行一个），**toolbarActions = 工具栏动作**（顶部）。

### Sprint 4 完成验收

```bash
✓ 表格显示 author.name（关系字段）
✓ 状态徽章：Draft（灰）/ Scheduled（黄）/ Published（绿）
✓ 2 个过滤器：status / author
✓ ViewAction / EditAction / DeleteAction（行级）
✓ DeleteBulkAction（批量）
```

---

## 6. Sprint 5：Policy `before()` hook 复用 13 章权限 ⭐

### 这一节是这章最值的一节

**问题**：13 章 PostPolicy 的规则是"只有作者能改/删"。如果直接复用，**admin 反而不能管理别人的文章**——这违反"管理员"的业务直觉。

**两个方案**：

| 方案 | 写法 |
|---|---|
| ❌ A. 在 Filament 端绕过 Policy | 不调 `->authorize()`，违反 Laravel 最佳实践 |
| ✓ B. 给 PostPolicy 加 `before()` hook | admin 跳过所有检查，**普通用户规则一字未动** ⭐ |

### 加 `before()` 一处改动 — 不到 10 行 ⭐

```php
class PostPolicy
{
    /**
     * Admins bypass all policy checks (Filament backend / web / API alike).
     * Returning null lets specific methods continue to evaluate.
     */
    public function before(?User $user, string $ability): ?bool
    {
        if ($user?->is_admin === true) {
            return true;
        }

        return null;
    }

    // 原有方法一字未动 ↓
    public function viewAny(?User $user): bool { ... }
    public function view(?User $user, Post $post): bool { ... }
    public function create(User $user): bool { ... }
    public function update(User $user, Post $post): bool { ... }
    public function delete(User $user, Post $post): bool { ... }
}
```

### `before()` 的语义

| 返回值 | Laravel 行为 |
|---|---|
| `true` | **立即通过**（不调用具体方法） |
| `false` | **立即拒绝**（不调用具体方法） |
| `null` | **让具体方法继续判断**（按原规则） |

→ 这一段**不到 10 行代码**让 admin 在**任何地方**（Filament 后台 / web / API）都能管理任何人的文章——而**普通用户的规则一字未动**。

### 微观但重要的细节

```php
if ($user?->is_admin === true) {
```

| 为什么这么写 | 否则会怎样 |
|---|---|
| `$user?->...` | `$user` 可能是 null（游客调 `viewAny`）—— 用 `?->` 防 null 调用 |
| `=== true` | 严格比较——避免 `null === true` 算 false |

### 一处修改的"全局影响"

加 `before()` hook 后：

| 调用方 | 行为变化 |
|---|---|
| Filament 后台 | admin 能 view/edit/delete **所有**文章 ✓ |
| Web 端 PostController | admin 用户做 web 操作也能管理任何文章（**意外的 bonus**） |
| API 端 Api\PostController | admin 用 API 也能管理任何文章（同上） |
| 普通用户 web/API | **规则没变**——非 admin 仍只能操作自己的文章 |
| 测试 | 现有 58 个测试全 PASS（用的都是 factory 默认非 admin 用户）✓ |

→ **这就是"约定优先 + 抽象单元"的回报**：一个 hook，三个端点同时受益。

### Sprint 5 完成验收

```bash
✓ PostPolicy 加 before() hook
✓ 现有 58 测试全 PASS（不破坏普通用户规则）
✓ admin 用户在 Filament / web / API 都能 bypass
```

---

## 7. Sprint 6：5 个 Pest 测试钉死后台权限

### 测试组织

新建 `tests/Feature/Admin/AdminPanelTest.php`（5 测试 / 9 断言）。

### 5 个测试覆盖什么

| # | 测试名 | 验证 |
|---|---|---|
| 1 | redirects guests to the admin login page | 后台不允许游客 |
| 2 | lets an admin user reach the admin dashboard | admin 能进 |
| 3 | forbids a non-admin user from reaching the admin dashboard | **`canAccessPanel` 防越权** ⭐ |
| 4 | allows an admin to update any post (PostPolicy::before bypass) | **admin bypass 工作** ⭐ |
| 5 | still forbids a non-admin from updating someone else's post | **`before` 不污染普通规则** ⭐ |

### 关键测试 1：`canAccessPanel` 防越权

```php
it('forbids a non-admin user from reaching the admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
```

→ 钉死"非 admin 用户尝试进 /admin → 403"。
→ 如果某天有人删除 `canAccessPanel` 方法 → **测试立刻挂掉**。

### 关键测试 2：admin bypass

```php
it('allows an admin to update any post (PostPolicy::before bypass)', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $other = User::factory()->create();
    $post = Post::factory()->for($other, 'author')->create();

    expect($admin->can('update', $post))->toBeTrue()
        ->and($admin->can('delete', $post))->toBeTrue()
        ->and($admin->can('view', $post))->toBeTrue();
});
```

→ 用 `$user->can('action', $model)` API **直接验证 Policy**——不需要走 HTTP 路由。
→ 钉死"admin 能做任何事"。

### 关键测试 3：admin bypass 不污染普通规则 ⭐

```php
it('still forbids a non-admin from updating someone else\'s post', function () {
    $randomUser = User::factory()->create(['is_admin' => false]);
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    expect($randomUser->can('update', $post))->toBeFalse()
        ->and($randomUser->can('delete', $post))->toBeFalse();
});
```

→ **这个测试比测试 2 更重要**——它钉死"加了 admin bypass **不影响**普通用户的规则"。
→ 防止某天有人把 `before()` 写错（比如 `return true` 而不是 `return null` 当非 admin 时），导致**所有人都能 bypass**。

### 性能观察：dashboard 测试 7.26s ⚠️

```
✓ it lets an admin user reach the admin dashboard         7.26s
✓ it forbids a non-admin user from reaching ...           0.04s
```

→ **第一次渲染整个 Filament dashboard** 需要 boot 大量类（widgets / theme / livewire 组件）。
→ 后续测试快（0.04s）—— Pest 进程内类已加载。

→ 实战意义：**如果 Filament 测试套件慢**，考虑用 `Sanctum::actingAs` + 直接断言**业务规则**（测试 4/5 那样），而不是渲染完整页面。

### Sprint 6 完成验收

```bash
✓ tests/Feature/Admin/AdminPanelTest.php 5 测试 passed
✓ php artisan test 整套 63 passed (189 assertions) in 2.79s
```

---

## 8. 重大踩坑：Filament v3 教程已经全面过时

### 这次最值的发现

**整个第 16 章实战，最大的收获不是 Filament 后台跑通**，而是发现 **Filament v3 教程已经全面过时**。

### 网上 90% 中文教程仍是 v3 写法

下面是常见的"过时 vs v5 现实"对照：

#### 命名空间变化

| 教程里的 v3 写法 | v5 现实 |
|---|---|
| `Filament\Forms\Form` | `Filament\Schemas\Schema` |
| `Filament\Tables\Actions\EditAction` | `Filament\Actions\EditAction` |
| `Filament\Tables\Actions\BulkAction` | `Filament\Actions\BulkAction` |
| `Filament\Tables\Actions\ActionGroup` | `Filament\Actions\ActionGroup` |
| `Filament\Tables\Filters\SelectFilter` | 仍是这个（少数没变的） |
| `Filament\Forms\Components\TextInput` | 仍是这个 |

#### 方法名变化

| v3 | v5 |
|---|---|
| `->actions([...])` | `->recordActions([...])` |
| `->bulkActions([...])` | `->toolbarActions([...])` |
| `Form::make($schema)->schema([...])` | `$schema->components([...])` |

#### Resource 文件结构

| v3 | v5 |
|---|---|
| 单文件 PostResource.php（300+ 行） | 6 文件模块化 |
| `public static function form(Form $form): Form` | `public static function form(Schema $schema): Schema` |
| `public static function getPages()` | 同 |

### 怎么应对"网上教程过时"

#### 策略 1：用 Filament 自己生成 stub

```bash
php artisan make:filament-resource Post --generate
```

→ stub 是 **Filament v5 自己生成的**，**100% 是当前版本的标准用法**。基于 stub 改远比照抄网上教程靠谱。

#### 策略 2：先用 Boost 的 `application-info` + `search-docs`

```
让 AI 调 application-info → 知道 Filament 版本
让 AI 调 search-docs("filament v5 form schema") → 拿最新文档
```

→ **避开"AI 训练数据陈旧"陷阱**。

#### 策略 3：相信 IDE 的"Undefined type"报错

我们这次 25 个 lint 错误**全是** intelephense 索引问题——但**这反过来教我们**：当 IDE 找不到类时，**先查最新版的命名空间**，**别盲目按教程 import**。

### 推论：所有 Laravel 生态包都有这个风险

| 包 | 主要版本节奏 |
|---|---|
| Filament | 1 年 1 个主版本（v3 → v4 → v5） |
| Livewire | 1 年 1 个主版本（v2 → v3） |
| Inertia | 较稳定（v0 → v1 → v2） |
| Spatie Permission | 慢（v5 持续多年） |
| Sanctum | 慢（v3 → v4） |

→ **生态包升级时，永远先查官方 upgrade guide**，不要假设 API 兼容。

---

## 9. ThinkPHP vs Laravel 后台脚手架对照

### ThinkPHP 后台脚手架方案

ThinkPHP 生态没有"等价 Filament"的统治级方案，常见组合：

| 方案 | 特性 |
|---|---|
| **GoView / DCat Admin / EasyAdmin** | 国内 ThinkPHP 后台模板（多基于 ThinkPHP 5/6） |
| **手写 Bootstrap + jQuery** | 大量项目仍这么做 |
| **Vue + ElementUI + 后端 API** | 前后端分离方案 |

### 概念对照

| ThinkPHP 后台 | Laravel + Filament v5 |
|---|---|
| 自己写 controller / 视图 / 路由 | `make:filament-resource` 生成 6 文件 |
| 表格自己写 HTML + 分页 + 搜索 | `Table` builder 0 配置 |
| 表单自己写 HTML + 验证 | `Schema` builder + 30+ 字段类型 |
| 关联下拉自己 SQL 查 | `Select::relationship('author', 'name')` |
| 状态徽章自己 CSS | `TextColumn::badge()->color()` |
| 权限自己 if 判断 | 复用 PostPolicy + `before` hook |
| 批量操作自己 form 写 | `BulkActionGroup` 0 配置 |
| 全局搜索自己写 | `getGloballySearchableAttributes()` |

### 4 个最值的差异

#### ① 模型驱动 vs HTML 驱动

ThinkPHP 后台从"HTML 表单"开始想——字段一行行写。
Filament 从"模型字段"开始想——`make:filament-resource Post --generate` **直接读 migration 字段**生成表单。

#### ② Policy 复用

ThinkPHP 后台权限**几乎肯定要重写**——前台的 if 判断不能直接拿到后台用。
Filament 后台**默认遵守 Laravel Policy**——加 `before` hook 一次，全端生效。

#### ③ Livewire 而不是 jQuery

Filament v5 用 **Livewire 3 + Alpine**——前端响应式 0 JS（PHP 写法触发 DOM 更新）。
ThinkPHP 后台普遍仍用 jQuery + AJAX。

#### ④ 0 npm（CDN 也不要）

Filament 装好后**前端资源已经 publish 到 public/**。没有 npm install / build 步骤。
ThinkPHP 后台脚手架普遍要装 npm 编译。

### 一句话总结差异

> **ThinkPHP 后台**：你拼装一套——controller / 视图 / 表单 / 权限都自己写。
>
> **Laravel + Filament v5**：`make:filament-resource Post --generate` + 改改字段 = **生产可用的管理后台**。

→ ThinkPHP 老手最容易吃惊的：**整个后台不到 250 行 PHP 代码**——还含权限 + 测试。

---

## 10. 完整代码索引

### 9 个文件的索引

#### 新增（7 个）

```
playground/app/Filament/Resources/Posts/PostResource.php                  48 行
playground/app/Filament/Resources/Posts/Pages/ListPosts.php
playground/app/Filament/Resources/Posts/Pages/CreatePost.php
playground/app/Filament/Resources/Posts/Pages/EditPost.php
playground/app/Filament/Resources/Posts/Schemas/PostForm.php              48 行
playground/app/Filament/Resources/Posts/Tables/PostsTable.php             89 行
playground/app/Providers/Filament/AdminPanelProvider.php                  60 行
playground/database/migrations/2026_05_08_085020_add_is_admin_to_users_table.php
playground/tests/Feature/Admin/AdminPanelTest.php                         62 行（5 测试）
```

#### 修改（3 个）

```
playground/app/Models/User.php                  （加 FilamentUser 接口 + canAccessPanel）
playground/app/Policies/PostPolicy.php          （加 before() hook，仅 10 行）⭐
playground/database/seeders/DatabaseSeeder.php  （test@example.com 升级 admin）
```

### 关键命令清单

```bash
# Sprint 1 — 装包
composer require filament/filament -W              # 不锁版本，自动选 v5.6.2
php artisan filament:install --panels              # 创建 admin panel
php artisan optimize:clear                         # 清缓存让 provider 生效

# Sprint 2 — admin 字段
php artisan make:migration add_is_admin_to_users_table --table=users
php artisan migrate
php artisan db:seed                                # 升级 test@example.com 为 admin

# Sprint 3 — 生成 PostResource
echo title | php artisan make:filament-resource Post --generate --no-interaction

# Sprint 6 — 测试
php artisan test --filter AdminPanelTest
php artisan test                                    # 整套 63 passed

# 浏览器验证
php artisan serve
# → http://127.0.0.1:8000/admin/login
# → test@example.com / password
```

---

## 11. 复盘总结 + 教程整体里程碑

### 7 个可以继续玩的方向

| # | 方向 | 难度 | 学到什么 |
|---|---|---|---|
| 1 | 加 Dashboard 自定义 Widget（统计图） | ⭐ | StatsOverview / ChartWidget |
| 2 | 加 Notification 系统（创建文章弹通知） | ⭐⭐ | Filament Notifications |
| 3 | 加角色 + 权限（用 Spatie Permission） | ⭐⭐ | RBAC + Filament Shield |
| 4 | 加 Activity Log（记录谁改了什么） | ⭐⭐ | spatie/laravel-activitylog |
| 5 | 加 Profile 页（admin 改自己资料/密码） | ⭐⭐ | Filament profile pages |
| 6 | 多 panel（admin + merchant + customer） | ⭐⭐⭐ | `canAccessPanel($panel)` 多分支 |
| 7 | 自定义主题（颜色/logo/暗黑模式） | ⭐ | `FilamentColor::register` |

### 推荐 Boost 演示场景

| 推荐度 | 方向 | Boost 工具组合 |
|---|---|---|
| 🔥 高 | 3. RBAC + Filament Shield | search-docs（v5 Shield）+ database-query |
| 🔥 高 | 4. Activity Log | search-docs + read-log-entries |

---

## 复盘总结

### 这次实战回答了 3 个问题

#### Q1：为什么 Filament 让人上瘾？

**A**：从"自己拼后台" → "约定优先 + 模型驱动"。具体收益：
- `make:filament-resource Post --generate` 一行出 6 文件 stub
- Schema builder 30+ 字段类型 0 配置
- Table builder 自动支持搜索/排序/过滤/分页/批量操作
- 复用 Eloquent 关系、Policy、Form Request **0 改动**
- 前端 0 JS（Livewire + Alpine 自动响应式）

#### Q2：这次踩坑值得吗？

**A**：**值得**。版本意外（v3.2 → v5.6.2 跳 2 个主版本）让你**亲眼看到**：
- AI 训练数据陈旧的真实风险
- 网上中文教程 90% 已过时
- Boost 的 `application-info` 应该早期介入避免该 API
- `composer require ... --dry-run` 是查"当前主流版本"的最快方式

#### Q3：Policy `before()` hook 的精妙

**A**：**这是这章最值得记住的 pattern**：
- 不到 10 行代码
- admin 在 Filament / web / API 三处都能 bypass
- **完全不动现有规则**
- 测试 5（"不污染普通规则"）防止退化

→ ThinkPHP 后台权限要重新写一遍。Laravel 一个 hook 三端搞定。

---

## 教程整体里程碑（截至本章）

```
docs/
├── Laravel_Boost_教程大纲.md            337 行 ✓
├── 03-环境搭建实测.md                    383 行 ✓
├── 06-11-章节速查.md                   1485 行 ✓
├── 12-Boost工具实战大全.md               530 行 ✓
├── 13-博客实战完整复盘.md                1279 行 ✓
├── 14-API化实战.md                     1128 行 ✓
├── 15-队列实战.md                      1077 行 ✓
├── 16-Filament后台实战.md              本篇 ✓ NEW
└── 17-Prompt工程反例集.md              1549 行 ✓

playground/ 实战项目状态：
- Laravel 12.58 + Sanctum 4.3.2 + Pest 3 + Filament 5.6.2
- 15 个数据表（含 jobs / failed_jobs / personal_access_tokens / posts / users(is_admin)）
- 63 个 Pest 测试全 passed in 2.79s
- 完整的"博客 + 认证 + 队列 + API + 后台"五件套实战项目
```

→ **7/18 主章节成稿 + 8 篇实测笔记**。第四阶段（实战篇）13/14/15/16 全部完成 ⭐⭐⭐

剩余优先级：
- 第 1-2 章（认知篇）—— 给教程一个像样的"为什么"开头
- 第 18 章 部署 + 性能 + 安全
- 总结回顾整理 → 把所有笔记串成"一本"成品教程

---

> **最终战果**：1.5 小时 → 完整管理后台（认证 + CRUD + 权限 + 5 个测试）
> **Boost 的角色**：让 AI 用 Filament v5 最新 API 写代码，避开"v3 教程已过时"陷阱
> **下一步**：选择 Filament Shield + RBAC（生产正解），或继续做第 1/2/18 章


