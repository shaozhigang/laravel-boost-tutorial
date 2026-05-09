import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'zh-CN',
  title: 'Laravel Boost 中文教程',
  description: '从 ThinkPHP 老手视角的 Laravel Boost 实测笔记 · 12 篇文档 · 约 40 万字 · 配套 Laravel 12 实战项目',

  // 部署到 GitHub Pages 时打开下一行（仓库名作为 base）
  base: '/laravel-boost-tutorial/',

  lastUpdated: true,
  cleanUrls: true,

  head: [
    ['meta', { name: 'theme-color', content: '#3c8772' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:locale', content: 'zh-CN' }],
    ['meta', { property: 'og:title', content: 'Laravel Boost 中文教程' }],
    ['meta', { property: 'og:site_name', content: 'Laravel Boost 中文教程' }],
  ],

  markdown: {
    // 教程里大量 Blade 语法 {{ $var }} 和 {{-- 注释 --}}
    // 不包 v-pre 的话会被 Vue 当成模板表达式解析报错
    config: (md) => {
      const originalRender = md.renderer.render.bind(md.renderer)
      md.renderer.render = (tokens, options, env) => {
        const html = originalRender(tokens, options, env)
        return `<div v-pre>${html}</div>`
      }
    },
    // shiki 不识别 ```env，回退到 bash 高亮
    languageAlias: {
      env: 'bash',
    },
  },

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: '首页', link: '/' },
      { text: '教程导读', link: '/00-教程导读' },
      { text: '完整大纲', link: '/Laravel_Boost_教程大纲' },
      {
        text: '实战代码',
        items: [
          { text: 'playground 源码', link: 'https://github.com/shaozhigang/laravel-boost-tutorial/tree/main/playground' },
          { text: 'README 启动指南', link: 'https://github.com/shaozhigang/laravel-boost-tutorial#%E8%B7%91%E8%B5%B7%E6%9D%A5-playground' },
        ],
      },
    ],

    sidebar: [
      {
        text: '入门 · 必读',
        collapsed: false,
        items: [
          { text: '00 · 教程导读 ⭐', link: '/00-教程导读' },
          { text: '01 · 认知篇 · Laravel 与 Boost', link: '/01-认知篇-Laravel与Boost' },
        ],
      },
      {
        text: '环境 · 把项目跑起来',
        collapsed: false,
        items: [
          { text: '03 · 环境搭建实测', link: '/03-环境搭建实测' },
        ],
      },
      {
        text: '基础 · Laravel 速查',
        collapsed: false,
        items: [
          { text: '06-11 · 章节速查（路由 / Eloquent / Migration / FormRequest / Policy / Blade）', link: '/06-11-章节速查' },
          { text: '12 · Boost 工具实战大全（9 个 MCP 工具）', link: '/12-Boost工具实战大全' },
        ],
      },
      {
        text: '实战 · 2 小时博客系统',
        collapsed: false,
        items: [
          { text: '13 · 博客实战完整复盘', link: '/13-博客实战完整复盘' },
          { text: '14 · API 化实战（Sanctum + Resources）', link: '/14-API化实战' },
          { text: '15 · 队列实战（Job + Queue + 邮件）', link: '/15-队列实战' },
          { text: '16 · Filament 后台实战', link: '/16-Filament后台实战' },
        ],
      },
      {
        text: '进阶 · 收官',
        collapsed: false,
        items: [
          { text: '17 · Prompt 工程反例集', link: '/17-Prompt工程反例集' },
          { text: '18 · 部署与生产实践 ⭐', link: '/18-部署与生产实践' },
        ],
      },
      {
        text: '附录',
        collapsed: true,
        items: [
          { text: '完整大纲', link: '/Laravel_Boost_教程大纲' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/shaozhigang/laravel-boost-tutorial' },
    ],

    search: {
      provider: 'local',
      options: {
        locales: {
          root: {
            translations: {
              button: {
                buttonText: '搜索文档',
                buttonAriaLabel: '搜索文档',
              },
              modal: {
                noResultsText: '无法找到相关结果',
                resetButtonTitle: '清除查询条件',
                footer: {
                  selectText: '选择',
                  navigateText: '切换',
                  closeText: '关闭',
                },
              },
            },
          },
        },
      },
    },

    docFooter: {
      prev: '上一篇',
      next: '下一篇',
    },

    outline: {
      label: '本页目录',
      level: [2, 3],
    },

    lastUpdated: {
      text: '最后更新于',
      formatOptions: {
        dateStyle: 'short',
        timeStyle: 'medium',
      },
    },

    returnToTopLabel: '回到顶部',
    sidebarMenuLabel: '菜单',
    darkModeSwitchLabel: '主题',
    lightModeSwitchTitle: '切换到浅色模式',
    darkModeSwitchTitle: '切换到深色模式',

    editLink: {
      pattern: 'https://github.com/shaozhigang/laravel-boost-tutorial/edit/main/docs/:path',
      text: '在 GitHub 上编辑此页',
    },

    footer: {
      message: '基于 <a href="https://opensource.org/licenses/MIT">MIT 许可</a> 发布',
      copyright: 'Copyright © 2026 shaozhigang',
    },
  },
})
