<?php
/**
 * Plugin Name: Sync WordPress to X
 * Description: Publishes newly published WordPress posts to X with a DeepSeek-generated summary.
 * Version: 0.1.1
 * Author: Open Source Contributors
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sync-wordpress-to-x
 */

if (!defined('ABSPATH')) {
    exit;
}

final class SWTX_Sync_WordPress_To_X {
    private const OPTION_NAME = 'swtx_settings';
    private const POST_META_X_ID = '_swtx_x_post_id';
    private const POST_META_ERROR = '_swtx_last_error';
    private const POST_META_SUMMARY = '_swtx_summary';
    private const X_CHARACTER_LIMIT = 280;

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('transition_post_status', [__CLASS__, 'maybe_publish_to_x'], 10, 3);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [__CLASS__, 'add_action_links']);
    }

    public static function add_action_links(array $links): array {
        $settings_url = admin_url('options-general.php?page=sync-wordpress-to-x');
        array_unshift($links, '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'sync-wordpress-to-x') . '</a>');
        return $links;
    }

    public static function add_settings_page(): void {
        add_options_page(
            __('Sync WordPress to X', 'sync-wordpress-to-x'),
            __('Sync WordPress to X', 'sync-wordpress-to-x'),
            'manage_options',
            'sync-wordpress-to-x',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings(): void {
        register_setting('swtx_settings_group', self::OPTION_NAME, [
            'sanitize_callback' => [__CLASS__, 'sanitize_settings'],
            'default' => self::default_settings(),
        ]);
    }

    public static function sanitize_settings($input): array {
        $current = self::get_settings();
        $input = is_array($input) ? $input : [];

        return [
            'enabled' => empty($input['enabled']) ? '0' : '1',
            'language' => in_array(($input['language'] ?? 'zh_CN'), ['zh_CN', 'en_US'], true) ? $input['language'] : 'zh_CN',
            'post_type' => sanitize_key($input['post_type'] ?? 'post'),
            'x_api_key' => sanitize_text_field($input['x_api_key'] ?? ''),
            'x_api_secret' => self::preserve_secret($input, $current, 'x_api_secret'),
            'x_access_token' => sanitize_text_field($input['x_access_token'] ?? ''),
            'x_access_token_secret' => self::preserve_secret($input, $current, 'x_access_token_secret'),
            'deepseek_api_key' => self::preserve_secret($input, $current, 'deepseek_api_key'),
            'deepseek_model' => sanitize_text_field($input['deepseek_model'] ?? 'deepseek-v4-flash'),
        ];
    }

    public static function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        $post_types = get_post_types(['public' => true], 'objects');
        $language = self::settings_language($settings);
        $copy = self::settings_copy($language);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html($copy['title']); ?></h1>
            <p><?php echo esc_html($copy['intro']); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields('swtx_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="swtx_language"><?php echo esc_html($copy['language_label']); ?></label></th>
                        <td>
                            <select id="swtx_language" name="<?php echo esc_attr(self::OPTION_NAME); ?>[language]">
                                <option value="zh_CN" <?php selected($language, 'zh_CN'); ?>>中文</option>
                                <option value="en_US" <?php selected($language, 'en_US'); ?>>English</option>
                            </select>
                            <p class="description"><?php echo esc_html($copy['language_help']); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html($copy['enabled_label']); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[enabled]" value="1" <?php checked($settings['enabled'], '1'); ?>>
                                <?php echo esc_html($copy['enabled_help']); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="swtx_post_type"><?php echo esc_html($copy['post_type_label']); ?></label></th>
                        <td>
                            <select id="swtx_post_type" name="<?php echo esc_attr(self::OPTION_NAME); ?>[post_type]">
                                <?php foreach ($post_types as $post_type) : ?>
                                    <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($settings['post_type'], $post_type->name); ?>>
                                        <?php echo esc_html($post_type->labels->singular_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html($copy['x_start_label']); ?></th>
                        <td>
                            <p>
                                <?php echo esc_html($copy['x_start_intro']); ?>
                                <a href="https://developer.x.com/en/portal/dashboard" target="_blank" rel="noopener noreferrer"><?php echo esc_html($copy['x_developer_link']); ?></a>
                            </p>
                            <ol>
                                <?php foreach ($copy['x_start_steps'] as $step) : ?>
                                    <li><?php echo esc_html($step); ?></li>
                                <?php endforeach; ?>
                            </ol>
                            <p class="description"><?php echo esc_html($copy['x_start_note']); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html($copy['x_app_keys_label']); ?></th>
                        <td>
                            <p><?php echo esc_html($copy['x_app_keys_intro']); ?></p>
                            <ol>
                                <?php foreach ($copy['x_app_keys_steps'] as $step) : ?>
                                    <li><?php echo esc_html($step); ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="swtx_x_api_key"><?php echo esc_html($copy['x_api_key_label']); ?></label></th>
                        <td><input class="regular-text" id="swtx_x_api_key" name="<?php echo esc_attr(self::OPTION_NAME); ?>[x_api_key]" type="text" value="<?php echo esc_attr($settings['x_api_key']); ?>"></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="swtx_x_api_secret"><?php echo esc_html($copy['x_api_secret_label']); ?></label></th>
                        <td>
                            <input class="regular-text" id="swtx_x_api_secret" name="<?php echo esc_attr(self::OPTION_NAME); ?>[x_api_secret]" type="password" value="<?php echo esc_attr($settings['x_api_secret']); ?>" autocomplete="off">
                            <p class="description"><?php echo esc_html($copy['oauth_note']); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html($copy['x_user_tokens_label']); ?></th>
                        <td>
                            <p><?php echo esc_html($copy['x_user_tokens_intro']); ?></p>
                            <ol>
                                <?php foreach ($copy['x_user_tokens_steps'] as $step) : ?>
                                    <li><?php echo esc_html($step); ?></li>
                                <?php endforeach; ?>
                            </ol>
                            <p class="description"><?php echo esc_html($copy['x_user_tokens_note']); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="swtx_x_access_token"><?php echo esc_html($copy['x_access_token_label']); ?></label></th>
                        <td><input class="regular-text" id="swtx_x_access_token" name="<?php echo esc_attr(self::OPTION_NAME); ?>[x_access_token]" type="text" value="<?php echo esc_attr($settings['x_access_token']); ?>"></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="swtx_x_access_token_secret"><?php echo esc_html($copy['x_access_token_secret_label']); ?></label></th>
                        <td><input class="regular-text" id="swtx_x_access_token_secret" name="<?php echo esc_attr(self::OPTION_NAME); ?>[x_access_token_secret]" type="password" value="<?php echo esc_attr($settings['x_access_token_secret']); ?>" autocomplete="off"></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="swtx_deepseek_api_key"><?php echo esc_html($copy['deepseek_api_key_label']); ?></label></th>
                        <td>
                            <input class="regular-text" id="swtx_deepseek_api_key" name="<?php echo esc_attr(self::OPTION_NAME); ?>[deepseek_api_key]" type="password" value="<?php echo esc_attr($settings['deepseek_api_key']); ?>" autocomplete="off">
                            <p class="description"><?php echo esc_html($copy['deepseek_help']); ?> <a href="https://platform.deepseek.com/api_keys" target="_blank" rel="noopener noreferrer">https://platform.deepseek.com/api_keys</a></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="swtx_deepseek_model"><?php echo esc_html($copy['deepseek_model_label']); ?></label></th>
                        <td>
                            <input class="regular-text" id="swtx_deepseek_model" name="<?php echo esc_attr(self::OPTION_NAME); ?>[deepseek_model]" type="text" value="<?php echo esc_attr($settings['deepseek_model']); ?>">
                            <p class="description"><?php echo esc_html($copy['deepseek_model_help']); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function maybe_publish_to_x(string $new_status, string $old_status, WP_Post $post): void {
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }

        $settings = self::get_settings();
        if ($settings['enabled'] !== '1' || $post->post_type !== $settings['post_type']) {
            return;
        }

        if (wp_is_post_revision($post->ID) || wp_is_post_autosave($post->ID) || get_post_meta($post->ID, self::POST_META_X_ID, true)) {
            return;
        }

        $missing = self::missing_required_settings($settings);
        if ($missing) {
            update_post_meta($post->ID, self::POST_META_ERROR, 'Missing settings: ' . implode(', ', $missing));
            return;
        }

        $summary = self::generate_summary($post, $settings);
        if (is_wp_error($summary)) {
            update_post_meta($post->ID, self::POST_META_ERROR, $summary->get_error_message());
            return;
        }

        $text = self::build_x_text($post, $summary);
        $result = self::create_x_post($text, $settings);
        if (is_wp_error($result)) {
            update_post_meta($post->ID, self::POST_META_ERROR, $result->get_error_message());
            return;
        }

        update_post_meta($post->ID, self::POST_META_X_ID, $result['id']);
        update_post_meta($post->ID, self::POST_META_SUMMARY, $summary);
        delete_post_meta($post->ID, self::POST_META_ERROR);
    }

    private static function generate_summary(WP_Post $post, array $settings) {
        $content = wp_strip_all_tags(strip_shortcodes($post->post_content));
        $content = trim(preg_replace('/\s+/', ' ', $content));
        $content = self::truncate($content, 5000);

        $prompt = sprintf(
            "Summarize this WordPress article for one X post. Return only the summary, no hashtags, no link, no title. Keep it concise and factual.\n\nTitle: %s\n\nArticle: %s",
            get_the_title($post),
            $content
        );

        $response = wp_remote_post('https://api.deepseek.com/chat/completions', [
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $settings['deepseek_api_key'],
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'model' => $settings['deepseek_model'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You write concise social summaries.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => false,
                'max_tokens' => 120,
                'temperature' => 0.3,
            ]),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('swtx_deepseek_error', self::api_error_message('DeepSeek', $code, $body));
        }

        $summary = trim((string) ($body['choices'][0]['message']['content'] ?? ''));
        if ($summary === '') {
            return new WP_Error('swtx_empty_summary', __('DeepSeek returned an empty summary.', 'sync-wordpress-to-x'));
        }

        return self::sanitize_line($summary);
    }

    private static function create_x_post(string $text, array $settings) {
        $url = 'https://api.x.com/2/tweets';
        $body = wp_json_encode(['text' => $text]);
        $headers = [
            'Authorization' => self::oauth_header('POST', $url, $settings),
            'Content-Type' => 'application/json',
        ];

        $response = wp_remote_post($url, [
            'timeout' => 20,
            'headers' => $headers,
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('swtx_x_error', self::api_error_message('X', $code, $body));
        }

        $id = (string) ($body['data']['id'] ?? '');
        if ($id === '') {
            return new WP_Error('swtx_x_missing_id', __('X did not return a post id.', 'sync-wordpress-to-x'));
        }

        return ['id' => $id, 'text' => (string) ($body['data']['text'] ?? $text)];
    }

    private static function oauth_header(string $method, string $url, array $settings): string {
        $oauth = [
            'oauth_consumer_key' => $settings['x_api_key'],
            'oauth_nonce' => wp_generate_password(32, false, false),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $settings['x_access_token'],
            'oauth_version' => '1.0',
        ];

        $base_params = $oauth;
        ksort($base_params);

        $base_string = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($base_params, '', '&', PHP_QUERY_RFC3986));
        $signing_key = rawurlencode($settings['x_api_secret']) . '&' . rawurlencode($settings['x_access_token_secret']);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));

        $parts = [];
        foreach ($oauth as $key => $value) {
            $parts[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }

        return 'OAuth ' . implode(', ', $parts);
    }

    private static function build_x_text(WP_Post $post, string $summary): string {
        $title = self::sanitize_line(get_the_title($post));
        $url = get_permalink($post);
        $separator_length = 2 + 2;
        $available_summary_length = self::X_CHARACTER_LIMIT - self::length($title) - self::length($url) - $separator_length;

        if ($available_summary_length < 20) {
            $title = self::truncate($title, 80);
            $available_summary_length = self::X_CHARACTER_LIMIT - self::length($title) - self::length($url) - $separator_length;
        }

        $summary = self::truncate($summary, max(0, $available_summary_length));
        return $title . "\n" . $summary . "\n" . $url;
    }

    private static function missing_required_settings(array $settings): array {
        $required = [
            'x_api_key' => 'X API key',
            'x_api_secret' => 'X API key secret',
            'x_access_token' => 'X access token',
            'x_access_token_secret' => 'X access token secret',
            'deepseek_api_key' => 'DeepSeek API key',
            'deepseek_model' => 'DeepSeek model',
        ];

        $missing = [];
        foreach ($required as $key => $label) {
            if (empty($settings[$key])) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    private static function api_error_message(string $service, int $code, ?array $body): string {
        $detail = $body['error']['message'] ?? $body['detail'] ?? $body['title'] ?? $body['errors'][0]['detail'] ?? '';
        return trim(sprintf('%s API error (%d): %s', $service, $code, is_string($detail) ? $detail : wp_json_encode($body)));
    }

    private static function preserve_secret(array $input, array $current, string $key): string {
        $value = (string) ($input[$key] ?? '');
        if ($value === '' && !empty($current[$key])) {
            return $current[$key];
        }

        return sanitize_text_field($value);
    }

    private static function settings_language(array $settings): string {
        return in_array(($settings['language'] ?? 'zh_CN'), ['zh_CN', 'en_US'], true) ? $settings['language'] : 'zh_CN';
    }

    private static function settings_copy(string $language): array {
        if ($language === 'en_US') {
            return [
                'title' => 'Sync WordPress to X',
                'intro' => 'When a new post is published, this plugin asks DeepSeek for a short summary and publishes three lines to X: title, summary, and permalink.',
                'language_label' => 'Settings language',
                'language_help' => 'This only changes the plugin settings page.',
                'enabled_label' => 'Enable auto-posting',
                'enabled_help' => 'Publish new WordPress posts to X automatically',
                'post_type_label' => 'Post type',
                'x_start_label' => 'Get started with X Developer',
                'x_start_intro' => 'Open the X Developer Portal here:',
                'x_developer_link' => 'X Developer Portal',
                'x_start_steps' => [
                    'Sign in with the X account that will publish posts.',
                    'Apply for or activate developer access. Choose the free tier if it is available for your account and usage.',
                    'Create a project and app. Give it a simple name, such as your blog name.',
                    'In the app settings, enable read and write permissions so the app is allowed to create posts.',
                ],
                'x_start_note' => 'X changes its developer screens occasionally. The names may vary slightly, but you need one app with write/post permission.',
                'x_app_keys_label' => 'Where to find API Key and API Key Secret',
                'x_app_keys_intro' => 'These two values identify your X developer app.',
                'x_app_keys_steps' => [
                    'In the X Developer Portal, open your project, then open your app.',
                    'Go to Keys and tokens.',
                    'Under Consumer Keys or API Key and Secret, copy API Key into the first field below.',
                    'Copy API Key Secret into the second field below. If it is hidden, regenerate or reveal it from the portal.',
                ],
                'x_api_key_label' => 'X API Key',
                'x_api_secret_label' => 'X API Key Secret',
                'oauth_note' => 'This plugin uses OAuth 1.0a user context for posting.',
                'x_user_tokens_label' => 'Where to find Access Token and Access Token Secret',
                'x_user_tokens_intro' => 'These two values authorize the X account that will publish the post.',
                'x_user_tokens_steps' => [
                    'In the same app, stay on Keys and tokens.',
                    'Find Access Token and Secret or Authentication Tokens.',
                    'Generate tokens for your own account. If X asks for permissions, choose read and write.',
                    'Copy Access Token into the third field and Access Token Secret into the fourth field.',
                ],
                'x_user_tokens_note' => 'If posting fails with a permission error, regenerate the access token after changing the app permission to read and write.',
                'x_access_token_label' => 'X Access Token',
                'x_access_token_secret_label' => 'X Access Token Secret',
                'deepseek_api_key_label' => 'DeepSeek API Key',
                'deepseek_help' => 'Create or copy your DeepSeek API key here:',
                'deepseek_model_label' => 'DeepSeek Model',
                'deepseek_model_help' => 'Default: deepseek-v4-flash.',
            ];
        }

        return [
            'title' => '同步 WordPress 到 X',
            'intro' => '当新文章发布时，插件会调用 DeepSeek 生成短摘要，并按三行格式发布到 X：标题、摘要、文章链接。',
            'language_label' => '设置页面语言',
            'language_help' => '这里只影响插件设置页的显示语言。',
            'enabled_label' => '启用自动发布',
            'enabled_help' => '新 WordPress 文章发布后，自动发布到 X',
            'post_type_label' => '文章类型',
            'x_start_label' => '开始配置 X 开发者账号',
            'x_start_intro' => '先打开 X 开发者后台：',
            'x_developer_link' => 'X Developer Portal',
            'x_start_steps' => [
                '用准备发帖的 X 账号登录。',
                '申请或启用开发者权限。如果你的账号可选择 Free / 免费层，选择免费层即可。',
                '创建一个 Project 和 App。名称可以用你的博客名，方便以后识别。',
                '进入 App 设置，把权限改成 Read and write，这样插件才有权限发帖。',
            ],
            'x_start_note' => 'X 的后台页面偶尔会调整名称，但目标是不变的：创建一个有写入/发帖权限的 App。',
            'x_app_keys_label' => '如何获取 API Key 和 API Key Secret',
            'x_app_keys_intro' => '这两个值用来识别你的 X 开发者 App。',
            'x_app_keys_steps' => [
                '在 X Developer Portal 里打开你的 Project，再打开里面的 App。',
                '进入 Keys and tokens 页面。',
                '在 Consumer Keys 或 API Key and Secret 区域，把 API Key 复制到下面第一个输入框。',
                '把 API Key Secret 复制到下面第二个输入框。如果后台隐藏了它，可以在该页面重新生成或查看。',
            ],
            'x_api_key_label' => 'X API Key',
            'x_api_secret_label' => 'X API Key Secret',
            'oauth_note' => '插件使用 OAuth 1.0a 用户授权方式发帖。',
            'x_user_tokens_label' => '如何获取 Access Token 和 Access Token Secret',
            'x_user_tokens_intro' => '这两个值用来授权具体哪个 X 账号来发帖。',
            'x_user_tokens_steps' => [
                '仍然在同一个 App 的 Keys and tokens 页面。',
                '找到 Access Token and Secret 或 Authentication Tokens 区域。',
                '为当前账号生成 Access Token。生成时如果要求选择权限，请选择 Read and write。',
                '把 Access Token 复制到下面第三个输入框，把 Access Token Secret 复制到第四个输入框。',
            ],
            'x_user_tokens_note' => '如果发帖时报权限错误，通常是先生成了 token 后才修改 App 权限；把权限改成 Read and write 后重新生成 Access Token 即可。',
            'x_access_token_label' => 'X Access Token',
            'x_access_token_secret_label' => 'X Access Token Secret',
            'deepseek_api_key_label' => 'DeepSeek API Key',
            'deepseek_help' => '在这里创建或复制你的 DeepSeek API Key：',
            'deepseek_model_label' => 'DeepSeek 模型',
            'deepseek_model_help' => '默认值：deepseek-v4-flash。',
        ];
    }

    private static function sanitize_line(string $value): string {
        $value = wp_strip_all_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'));
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    private static function truncate(string $value, int $limit): string {
        if ($limit <= 0 || self::length($value) <= $limit) {
            return $value;
        }

        $suffix = '...';
        $slice_length = max(0, $limit - self::length($suffix));
        if (function_exists('mb_substr')) {
            return rtrim(mb_substr($value, 0, $slice_length)) . $suffix;
        }

        return rtrim(substr($value, 0, $slice_length)) . $suffix;
    }

    private static function length(string $value): int {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    private static function get_settings(): array {
        $settings = get_option(self::OPTION_NAME, []);
        return wp_parse_args(is_array($settings) ? $settings : [], self::default_settings());
    }

    private static function default_settings(): array {
        return [
            'enabled' => '0',
            'language' => 'zh_CN',
            'post_type' => 'post',
            'x_api_key' => '',
            'x_api_secret' => '',
            'x_access_token' => '',
            'x_access_token_secret' => '',
            'deepseek_api_key' => '',
            'deepseek_model' => 'deepseek-v4-flash',
        ];
    }
}

SWTX_Sync_WordPress_To_X::init();
