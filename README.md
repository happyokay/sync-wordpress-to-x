# Sync WordPress to X

A small WordPress plugin that publishes newly published WordPress posts to X with an AI-generated summary.

中文简介：将新发布的 WordPress 文章通过 AI 摘要同步发布到 X。

Author: [happy xiao](https://aa.ee) | [访问插件主页](https://github.com/happyokay/sync-wordpress-to-x)

The X post format is:

```text
Post title
AI-generated article summary
Post permalink
```

## What it does

- Watches for newly published WordPress posts.
- Sends the article title and body to DeepSeek for a short summary.
- Trims the final text to one X post.
- Publishes the text through the X API.
- Saves the X post id and last error in post meta so duplicate publishes are avoided.
- Provides Chinese and English settings page copy, including beginner-friendly X API credential instructions.
- Supports manual publishing from the WordPress post editor when auto-posting is disabled or you want to test one post.

## Requirements

- WordPress 6.0 or newer.
- PHP 7.4 or newer.
- An X developer app with user-context posting access.
- A DeepSeek API key.

The plugin uses:

- X API `POST https://api.x.com/2/tweets`
- DeepSeek `POST https://api.deepseek.com/chat/completions`

## Installation

### WordPress admin upload

1. Download `sync-wordpress-to-x.zip`.
2. In WordPress, open **Plugins > Add New Plugin > Upload Plugin**.
3. Upload the zip file and activate **Sync WordPress to X**.
4. Open **Settings > Sync WordPress to X**.
5. Add your X API key, X API key secret, X access token, X access token secret, and DeepSeek API key.
6. Enable auto-posting.

If you leave auto-posting disabled, open a published post in the WordPress editor and use the **Sync WordPress to X** sidebar box to publish that post manually.

### Manual install

1. Copy this folder to `wp-content/plugins/sync-wordpress-to-x`.
2. Activate **Sync WordPress to X** in WordPress.
3. Open **Settings > Sync WordPress to X**.
4. Add your X API key, X API key secret, X access token, X access token secret, and DeepSeek API key.
5. Enable auto-posting.

## X API notes

This plugin signs X requests with OAuth 1.0a user context. In your X developer portal, generate:

- API key
- API key secret
- Access token
- Access token secret

The X app must have write/post permissions.

If posting fails, open the WordPress post editor and check the **Sync WordPress to X** sidebar box. It shows the last error returned by DeepSeek or X. Common causes are missing auto-posting, a post that was already published before auto-posting was enabled, X app permissions that are not set to read and write, access tokens generated before the permission change, mixed credentials from different X apps, or a DeepSeek key/model/billing issue.

## DeepSeek notes

The default model is `deepseek-v4-flash`, matching DeepSeek's current quick-start documentation. You can change the model on the settings page.

Summary requests explicitly disable DeepSeek thinking mode because the plugin only needs a short public summary. If DeepSeek returns an empty summary, the plugin stores the response `finish_reason` and a short response excerpt in the post editor sidebar so the exact cause is easier to diagnose.

## Privacy

When auto-posting is enabled, the plugin sends article title and article body text to DeepSeek to generate a summary. The final title, summary, and permalink are sent to X.

## License

GPL-2.0-or-later
