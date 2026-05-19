# Sync WordPress to X

A small WordPress plugin that prepares or publishes newly published WordPress posts to X with an AI-generated summary.

Author: [happy xiao](https://aa.ee) | [Plugin homepage](https://github.com/happyokay/sync-wordpress-to-x)

The X post format is:

```text
Post title
AI-generated article summary
Post permalink
```

## English

### What it does

- Watches for newly published WordPress posts.
- Sends the article title and body to DeepSeek for a short summary.
- Trims the final text to one X post.
- Free semi-auto mode: saves the X text in the post editor, with copy and open-X-compose buttons.
- X API mode: publishes the text through the X API when the project has paid API access.
- Saves the X post id and last error in post meta so duplicate publishes are avoided.
- Provides Chinese and English settings page copy.

### Requirements

- WordPress 6.0 or newer.
- PHP 7.4 or newer.
- A DeepSeek API key.
- Optional: an X developer app with user-context posting access, only for X API mode.

The plugin uses:

- DeepSeek `POST https://api.deepseek.com/chat/completions`
- Optional X API mode: `POST https://api.x.com/2/tweets`

### Installation

1. Download `sync-wordpress-to-x.zip`.
2. In WordPress, open **Plugins > Add New Plugin > Upload Plugin**.
3. Upload the zip file and activate **Sync WordPress to X**.
4. Open **Settings > Sync WordPress to X**.
5. Add your DeepSeek API key.
6. Keep the default free semi-auto mode, or choose X API mode and add your X credentials.
7. Enable automatic processing if you want new posts to generate X text automatically.

In free semi-auto mode, open a published post in the WordPress editor and use the **Sync WordPress to X** sidebar box to generate text, copy it, or open X compose.

### X API Notes

X credentials are only required for X API mode. The default free semi-auto mode does not call the X API.

In X API mode, this plugin signs X requests with OAuth 1.0a user context. In your X developer portal, generate:

- API key
- API key secret
- Access token
- Access token secret

The X app must belong to a Project and have write/post permissions.

If X returns `client-not-enrolled` or `Appropriate Level of API Access`, open **Products > X API v2** in the X Developer Portal. X now shows the old Free plan as deprecated, so the Project needs Pay Per Use or a paid tier that includes `POST /2/tweets`.

### DeepSeek Notes

The default model is `deepseek-v4-flash`, matching DeepSeek's current quick-start documentation. You can change the model on the settings page.

Summary requests explicitly disable DeepSeek thinking mode because the plugin only needs a short public summary. If DeepSeek returns an empty summary, the plugin stores the response `finish_reason` and a short response excerpt in the post editor sidebar so the exact cause is easier to diagnose.

### Privacy

When automatic processing is enabled, the plugin sends article title and article body text to DeepSeek to generate a summary. In free semi-auto mode, the final title, summary, and permalink are saved in WordPress for manual posting. In X API mode, they are sent to X.

## 中文

### 插件简介

这是一个 WordPress 插件，用来把新发布的文章整理成适合 X.com 的三行文案：

```text
文章标题
AI 生成的文章摘要
文章链接
```

插件支持两种模式：

- 免费半自动模式：不需要 X API。插件调用 DeepSeek 生成摘要，并在文章编辑页保存 X 文案，提供“复制文案”和“打开 X 发帖”按钮，最后由你手动点击发布。
- X API 模式：保留自动发布能力，但需要你的 X Project 拥有可用的付费 API / Pay Per Use 访问权限。

### 功能

- 监听 WordPress 新发布文章。
- 把文章标题和正文发送给 DeepSeek，生成简短摘要。
- 自动把最终文案控制在一条 X 帖子的长度内。
- 免费半自动模式下，在文章编辑页侧边栏显示可复制的 X 文案。
- X API 模式下，通过 X API 自动发布。
- 保存 X post id、AI 摘要、X 文案和上一次错误，方便排查。
- 设置页支持英文和中文。

### 安装

1. 下载 `sync-wordpress-to-x.zip`。
2. 在 WordPress 后台打开 **插件 > 安装插件 > 上传插件**。
3. 上传 zip 并启用 **Sync WordPress to X**。
4. 打开 **设置 > Sync WordPress to X**。
5. 填入 DeepSeek API Key。
6. 保持默认的免费半自动模式，或切换到 X API 模式并填写 X 凭据。
7. 如果希望新文章发布后自动生成 X 文案，勾选“自动处理新文章”。

免费半自动模式下，你也可以打开任意已发布文章，在右侧 **Sync WordPress to X** 面板点击 **Generate X text**，然后复制文案或打开 X 发帖窗口。

### X API 说明

默认免费半自动模式不调用 X API，也不需要填写 X API Key。

只有在 X API 模式下，才需要填写以下四项：

- API Key
- API Key Secret
- Access Token
- Access Token Secret

你的 X App 必须属于某个 Project，并且权限需要是 Read and write。

如果 X 返回 `client-not-enrolled` 或 `Appropriate Level of API Access`，请在 X Developer Portal 打开 **Products > X API v2**。目前 X 显示旧 Free plan 已经 deprecated，所以 Project 需要 Pay Per Use 或包含 `POST /2/tweets` 的付费层级才能自动发布。

### DeepSeek 说明

默认模型是 `deepseek-v4-flash`，你可以在设置页修改。

插件会明确关闭 DeepSeek thinking 模式，因为这里只需要公开摘要。如果 DeepSeek 返回空摘要，插件会在文章编辑页侧边栏记录 `finish_reason` 和一小段返回内容，方便排查。

### 隐私说明

启用自动处理后，插件会把文章标题和正文发送给 DeepSeek 生成摘要。免费半自动模式下，最终标题、摘要和链接只保存在 WordPress 中，供你手动发布到 X。X API 模式下，最终文案会发送给 X。

## License

GPL-2.0-or-later
