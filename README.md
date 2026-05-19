# Sync WordPress to X

A small WordPress plugin that publishes newly published WordPress posts to X.

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

## DeepSeek notes

The default model is `deepseek-v4-flash`, matching DeepSeek's current quick-start documentation. You can change the model on the settings page.

## Privacy

When auto-posting is enabled, the plugin sends article title and article body text to DeepSeek to generate a summary. The final title, summary, and permalink are sent to X.

## License

GPL-2.0-or-later
