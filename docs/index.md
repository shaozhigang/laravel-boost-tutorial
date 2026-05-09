---
layout: home

hero:
  name: "Laravel Boost"
  text: "中文教程 · 实测笔记"
  tagline: 从 ThinkPHP 老手视角，把 Laravel + Boost + AI 编码助手用熟的完整路径
  actions:
    - theme: brand
      text: 开始阅读
      link: /00-教程导读
    - theme: alt
      text: 查看完整大纲
      link: /Laravel_Boost_教程大纲
    - theme: alt
      text: GitHub
      link: https://github.com/shaozhigang/laravel-boost-tutorial

features:
  - icon: 🎯
    title: 实测优先，不抄文档
    details: 所有命令、报错、修复方案都在 Laravel 12.58 + PHP 8.2.9 + Boost 2.4.6 真实环境跑过。看到任何"应该可以"的描述，请告诉我——这通常意味着没实测过。

  - icon: 🛠️
    title: 配套实战代码
    details: playground/ 目录是一个真实可跑的 Laravel 12 博客系统——31 个 Pest 测试 passed，含 Sanctum / API Resources / Queue / Filament 后台。clone 即可启动。

  - icon: 🤖
    title: Boost 的真实使用记录
    details: 9 个 MCP 工具一个一个用过来，告诉你哪个真好用、哪个是噱头。AI Guidelines 版本对齐机制 + Skills 体系都拆解到能复制粘贴。

  - icon: 📐
    title: ThinkPHP 视角的对照学习
    details: 5 个心理障碍 + 7 个核心抽象的对照表（路由 / Eloquent / Migration / FormRequest / Policy / Blade / Pest），让 PHP 老手不掉进"魔法陷阱"。

  - icon: ⚡
    title: 2 小时上手实战
    details: 第 13 章完整复盘——从 laravel new 到博客上线只用了 2 小时（含装包、写 model、迁移、Policy、测试、发布）。每一步都有时间戳。

  - icon: 🚀
    title: 收官有部署，不止教写
    details: 第 18 章不止讲 git push，覆盖 Nginx / PHP-FPM / Supervisor / Horizon / 零停机部署 / 备份 / 监控的完整生产实践。
---

<style>
.VPHero .name,
.VPHero .text {
  background: linear-gradient(120deg, #ff6b6b 30%, #ee0979);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}
</style>

## 🚀 30 分钟快速上手

如果只想先尝个鲜、看看 Boost 到底能干啥：

1. 先读 [`00 · 教程导读`](/00-教程导读) — 全书结构 + 学习方法论（5 分钟）
2. 跟 [`03 · 环境搭建实测`](/03-环境搭建实测) 把环境装起来（10 分钟）
3. clone 仓库，进 `playground/`，跑 `composer install && php artisan serve`，看实战项目长啥样（15 分钟）

完整路径请看 [完整大纲](/Laravel_Boost_教程大纲)。

---

## 📊 教程版本对齐

| 工具 | 实测版本 |
|---|---|
| PHP | 8.2.9 |
| Laravel | 12.58.0 |
| Composer | 2.x |
| `laravel/boost` | 2.4.6（2025-09 发布） |
| Pest | 3.8 |
| Filament | 5.6 |

教程里每个命令都标了出处。看到任何版本/命令对不上的，欢迎在 [GitHub](https://github.com/shaozhigang/laravel-boost-tutorial/issues) 开 issue。

---

## 🎯 这套教程**不**讲什么

- **不**讲 PHP 基础（默认你写过 ThinkPHP / 用过 PHP 5+）
- **不**讲 Laravel 全套（聚焦 Boost 相关 + 真实业务用得到的部分）
- **不**抄官方文档（所有内容都过实测，有版本号、有报错、有修复）
- **不**讲"理论上 Laravel 怎么优雅"（讲"实际上 ThinkPHP 老手怎么少踩坑")

---

<p align="center">
  如果对你有帮助，<a href="https://github.com/shaozhigang/laravel-boost-tutorial">在 GitHub 上 ★ Star</a> 一下是最大的鼓励。
</p>
