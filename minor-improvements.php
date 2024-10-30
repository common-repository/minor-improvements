<?php
/*
* Plugin Name: Minor Improvements
* Description: Package of several minor improvements. Why to install several plugins? You need this one only.
* Version: 1.8
* Author: Michal Novák
* Author URI: https://www.novami.cz
* License: GPLv3
* Text Domain: minor-improvements
*/

namespace MinorImprovements;

use WP_Http_Cookie;


if (!defined('ABSPATH')) {
    die();
}

include sprintf('%s/entity.php', dirname(__FILE__));

/**
 * Class Main
 * @package MinorImprovements
 */
class Main
{
    /** @var string */
    const UPDATE = 'update';

    /** @var string */
    const DISABLE_RECAPCTHA = 'disable_recaptcha';

    /** @var string */
    const RECAPTCHA_KEY_SUFIX = '_key';

    /** @var string */
    const RECAPTCHA_RESPONSE = 'mi-recaptcha-response';

    /** @var string */
    const YT_BASE_EMBED_URL = 'https://www.youtube-nocookie.com/embed';

    /** @var string */
    const MI_MAIN = 'mi_main';

    /** @var string */
    const MI_ACTION = 'mi_action';

    /** @var string */
    private $pluginName;

    /** @var Entity[] */
    private $options;

    public function __construct()
    {
        add_action('init', [$this, 'run']);
    }

    /**
     * @param int $type
     * @return int
     */
    private function getOptionFilter(int $type): int
    {
        return $type === Entity::INT ? FILTER_SANITIZE_NUMBER_INT : FILTER_SANITIZE_FULL_SPECIAL_CHARS;
    }

    /**
     * @return void
     */
    private function loadSettings(): void
    {
        foreach ($this->options as $id => $option) {
            $type = $option->getType();
            $filter = $this->getOptionFilter($type);
            $filteredValue = filter_var(get_option($id), $filter);
            $option->setValue($type === Entity::INT ? intval($filteredValue) : strval($filteredValue));
        }
    }

    /**
     * @param string $id
     * @return int|string
     */
    private function getOptionValue(string $id)
    {
        return $this->options[$id]->getValue();
    }

    /**
     * @return void
     */
    private function applyOptions(): void
    {
        if ($this->getOptionValue(Entity::AUTO_UPDATE)) {
            add_filter('allow_major_auto_core_updates', '__return_true');
            add_filter('auto_update_plugin', '__return_true');
            add_filter('auto_update_theme', '__return_true');
        }

        if (!$this->getOptionValue(Entity::UPDATE_NOTIFY)) {
            add_filter('auto_plugin_update_send_email', '__return_false');
            add_filter('auto_theme_update_send_email', '__return_false');
        }

        if (!$this->getOptionValue(Entity::XML_RPC)) {
            add_filter('xmlrpc_enabled', '__return_false');
        }

        if (!$this->getOptionValue(Entity::WWW_FIELD)) {
            add_filter('comment_form_default_fields', [$this, 'disableCommentWww']);
        }

        if ($authorSlug = $this->getOptionValue(Entity::AUTHOR_SLUG)) {
            global $wp_rewrite;
            $wp_rewrite->author_base = trim($authorSlug);
        }
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $this->pluginName = get_file_data(__FILE__, ['Name' => 'Plugin Name'])['Name'];

        $this->options = [
            Entity::AUTO_UPDATE => new Entity(__('Auto updates', 'minor-improvements'), Entity::INT),
            Entity::UPDATE_NOTIFY => new Entity(__('Update notify', 'minor-improvements'), Entity::INT),
            Entity::XML_RPC => new Entity(__('XML-RPC', 'minor-improvements'), Entity::INT),
            Entity::WWW_FIELD => new Entity(__('WWW field in comments', 'minor-improvements'), Entity::INT),
            Entity::AUTHOR_SLUG => new Entity(__('Author base', 'minor-improvements'), Entity::STRING),
            Entity::RECAPTCHA_SITE_KEY => new Entity(__('reCAPTCHA v3 Site Key', 'minor-improvements'), Entity::STRING),
            Entity::RECAPTCHA_SECRET_KEY => new Entity(__('reCAPTCHA v3 Secret Key', 'minor-improvements'), Entity::STRING)
        ];

        $this->updateSettings();

        $this->loadSettings();

        $this->disableRecaptcha();

        $this->enqueueMain();

        $this->recaptchaFrontend();

        $this->applyOptions();

        add_shortcode('mi_yt_last', [$this, 'getYtLastVideoByChannel']);
        add_shortcode('mi_yt', [$this, 'getYtVideoById']);

        add_filter(sprintf('plugin_action_links_%s', plugin_basename(__FILE__)), [$this, 'actionLinks']);
        add_action('admin_menu', [$this, 'menu']);
    }

    /**
     * @return void
     */
    public function updateSettings(): void
    {
        $postAction = strval(filter_input(INPUT_POST, self::MI_ACTION, FILTER_SANITIZE_SPECIAL_CHARS));

        if ($postAction === self::UPDATE && current_user_can('manage_options')) {
            $hash = null;
            foreach ($this->options as $key => $option) {
                $postValue = filter_input(INPUT_POST, $key, $this->getOptionFilter($option->getType()));

                if ($postValue) {
                    update_option($key, $postValue);

                    if (substr($key, -strlen(self::RECAPTCHA_KEY_SUFIX)) === self::RECAPTCHA_KEY_SUFIX) {
                        $hash .= $postValue;
                    } elseif ($key === Entity::AUTHOR_SLUG) {
                        flush_rewrite_rules();
                    }
                } else {
                    delete_option($key);
                }
            }

            setcookie(Entity::RECAPTCHA_HASH, md5($hash), time() + 60 * 60 * 24 * 10, '/');

            echo sprintf('<div class="notice notice-success"><p><strong>%s</strong></p></div>', __('Settings saved!', 'minor-improvements'));
        }
    }

    /**
     * @param $links
     * @return string[]
     */
    public function actionLinks($links): array
    {
        return array_merge(['settings' => sprintf('<a href="options-general.php%s">%s</a>', Entity::PAGE_QUERY, __('Settings', 'minor-improvements'))], $links);
    }

    /**
     * @return void
     */
    public function optionsPage(): void
    {
        echo sprintf('<div class="wrap"><h1>%s - %s</h1><form method="post" action="%s">', $this->pluginName, __('Settings', 'minor-improvements'), Entity::PAGE_QUERY);

        settings_fields('mi_header_section');
        do_settings_sections('mi_options');

        echo sprintf('<input type="hidden" name="%s" value="%s">', self::MI_ACTION, self::UPDATE);

        submit_button();

        echo sprintf('%s</form>%s</div>', PHP_EOL, $this->recaptchaProtectionStatus());
    }

    /**
     * @return void
     */
    public function menu(): void
    {
        add_submenu_page('options-general.php', $this->pluginName, $this->pluginName, 'manage_options', 'mi_options', [$this, 'optionsPage']);
        add_action('admin_init', [$this, 'displayOptions']);
    }

    /**
     * @return void
     */
    public function display_mi_auto_update(): void
    {
        echo sprintf('<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />', Entity::AUTO_UPDATE, checked(1, $this->getOptionValue(Entity::AUTO_UPDATE), false));
    }

    /**
     * @return void
     */
    public function display_mi_update_notify(): void
    {
        echo sprintf('<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />', Entity::UPDATE_NOTIFY, checked(1, $this->getOptionValue(Entity::UPDATE_NOTIFY), false));
    }

    /**
     * @return void
     */
    public function display_mi_xml_rpc(): void
    {
        echo sprintf('<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />', Entity::XML_RPC, checked(1, $this->getOptionValue(Entity::XML_RPC), false));
    }

    /**
     * @return void
     */
    public function display_mi_www_field(): void
    {
        echo sprintf('<input type="checkbox" name="%1$s" id="%1$s" value="1" %2$s />', Entity::WWW_FIELD, checked(1, $this->getOptionValue(Entity::WWW_FIELD), false));
    }

    /**
     * @return void
     */
    public function display_mi_author_slug(): void
    {
        echo sprintf('<input type="text" name="%1$s" class="regular-text" id="%1$s" value="%2$s" />', Entity::AUTHOR_SLUG, $this->getOptionValue(Entity::AUTHOR_SLUG));
    }

    /**
     * @return void
     */
    public function display_mi_recaptcha_site_key(): void
    {
        echo sprintf('<input type="text" name="%1$s" class="regular-text" id="%1$s" value="%2$s" />', Entity::RECAPTCHA_SITE_KEY, $this->getOptionValue(Entity::RECAPTCHA_SITE_KEY));
    }

    /**
     * @return void
     */
    public function display_mi_recaptcha_secret_key(): void
    {
        echo sprintf('<input type="text" name="%1$s" class="regular-text" id="%1$s" value="%2$s" />', Entity::RECAPTCHA_SECRET_KEY, $this->getOptionValue(Entity::RECAPTCHA_SECRET_KEY));
    }

    /**
     * @return void
     */
    public function displayOptions(): void
    {
        add_settings_section('mi_header_section', __('Settings', 'minor-improvements'), [], 'mi_options');

        foreach ($this->options as $key => $option) {
            add_settings_field($key, $option->getName(), [$this, sprintf('display_%s', $key)], 'mi_options', 'mi_header_section');
            register_setting('mi_header_section', $key);
        }
    }

    /**
     * @param $fields
     * @return mixed
     */
    public function disableCommentWww($fields)
    {
        unset($fields['url']);

        return $fields;
    }

    /**
     * @return void
     */
    public function enqueueMain(): void
    {
        $jsName = 'mi.js';
        $jsPath = plugin_dir_path(__FILE__) . $jsName;

        wp_enqueue_script(self::MI_MAIN, plugin_dir_url(__FILE__) . $jsName, [], filemtime($jsPath));
        wp_localize_script(self::MI_MAIN, self::MI_MAIN, [Entity::RECAPTCHA_SITE_KEY => $this->getOptionValue(Entity::RECAPTCHA_SITE_KEY)]);

        $cssName = 'mi.css';
        $cssPath = plugin_dir_path(__FILE__) . $cssName;

        wp_enqueue_style(self::MI_MAIN, plugin_dir_url(__FILE__) . $cssName, [], filemtime($cssPath));
    }

    /**
     * @return void
     */
    public function enqueueRecaptcha(): void
    {
        $jsUrl = sprintf('https://www.recaptcha.net/recaptcha/api.js?hl=%s&render=%s&onload=mi_rcp3', get_locale(), $this->getOptionValue(Entity::RECAPTCHA_SITE_KEY));
        wp_enqueue_script('mi_recaptcha', $jsUrl, [], time());
    }

    /**
     * @param string $url
     * @return string
     */
    public function getYtIframe(string $url): string
    {
        return sprintf("<div class=\"mi-container\">
            <iframe src=\"%s/%s\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>
        </div>", self::YT_BASE_EMBED_URL, filter_var($url, FILTER_SANITIZE_URL));
    }

    /**
     * @param $atts
     * @return string
     */
    public function getYtLastVideoByChannel($atts): string
    {
        $id = [];
        $error = true;
        $errorMsg = sprintf('<p>%s</p>', __('Couldn\'t get last video', 'minor-improvements'));

        if (!isset($atts['channel'])) {
            return $errorMsg;
        }

        $cacheKey = sprintf('%s_last_%s', sanitize_title($this->pluginName), $atts['channel']);
        $cache = get_transient($cacheKey);
        if ($cache) {
            $id[1] = $cache;
            $error = false;
        } else {
            $today = date('Ymd');
            $cookie = new WP_Http_Cookie('CONSENT');
            $cookie->name = 'CONSENT';
            $cookie->value = sprintf('YES+cb.%s-17-p0.en+F+886', $today);
            $cookie->expires = mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1);
            $cookie->path = '/';
            $cookie->domain = '.youtube.com';

            $source = wp_remote_get(sprintf('https://www.youtube.com/c/%s/videos', $atts['channel']), ['limit_response_size' => 1000 * 300, 'cookies' => [$cookie]]);
            if (intval(wp_remote_retrieve_response_code($source)) === 200) {
                //file_put_contents(dirname(__FILE__) . '/response.txt', wp_remote_retrieve_body($source));
                preg_match('/watch\?v=([a-zA-Z0-9_-]{5,15})/s', wp_remote_retrieve_body($source), $id);

                if ($id[1]) {
                    set_transient($cacheKey, $id[1], 60 * 60);
                    $error = false;
                }
            }
        }

        if ($error) {
            return $errorMsg;
        } else {
            return $this->getYtVideoById(['id' => $id[1]]);
        }
    }

    /**
     * @param array $atts
     * @return string
     */
    public function getYtVideoById(array $atts): string
    {
        $url = sprintf('%s?rel=0', $atts['id']);

        return $this->getYtIframe($url);
    }

    /**
     * @return void
     */
    public function recaptchaFrontend(): void
    {
        $rcpActivate = !is_user_logged_in() && !wp_doing_ajax() && !function_exists('wpcf7_contact_form_shortcode');
        $recaptchaSiteKey = $this->getOptionValue(Entity::RECAPTCHA_SITE_KEY);
        $recaptchaSecretKey = $this->getOptionValue(Entity::RECAPTCHA_SECRET_KEY);

        if ($rcpActivate && $recaptchaSiteKey && $recaptchaSecretKey) {
            $mi_display_list = [
                'bp_after_signup_profile_fields',
                'comment_form_after_fields',
                'lostpassword_form',
                'register_form',
                'woocommerce_lostpassword_form',
                'woocommerce_register_form',
                'login_form',
                'woocommerce_login_form'
            ];

            $mi_verify_list = [
                'bp_signup_validate',
                'lostpassword_post',
                'preprocess_comment',
                'registration_errors',
                'woocommerce_register_post',
                'authenticate'
            ];

            foreach ($mi_display_list as $mi_display) {
                add_action($mi_display, [$this, 'enqueueRecaptcha']);
                add_action($mi_display, [$this, 'recaptchaDisplay']);
            }

            foreach ($mi_verify_list as $mi_verify) {
                add_action($mi_verify, [$this, 'recaptchaVerify']);
            }
        }
    }

    /**
     * @return void
     */
    public function recaptchaDisplay(): void
    {
        $badgeText = sprintf('%s<p class="mi-infotext">%s</p>', PHP_EOL, __('This site is protected by reCAPTCHA and the Google <a href="https://policies.google.com/privacy">Privacy Policy</a> and <a href="https://policies.google.com/terms">Terms of Service</a> apply.', 'minor-improvements'));

        if ($this->recaptchaAdminCookieHash()) {
            echo sprintf('<p class="mi-infotext"><a href="?%s=%s">%s</a></p>', self::MI_ACTION, self::DISABLE_RECAPCTHA, __('Emergency reCAPTCHA deactivate', 'minor-improvements'));
        }

        echo sprintf('<input type="hidden" name="%s" class="mi-main">%s', self::RECAPTCHA_RESPONSE, $badgeText);
    }

    /**
     * @return void
     */
    private function disableRecaptcha(): void
    {
        $getAction = strval(filter_input(INPUT_GET, self::MI_ACTION, FILTER_SANITIZE_SPECIAL_CHARS));

        if ($getAction === self::DISABLE_RECAPCTHA && $this->recaptchaAdminCookieHash()) {
            $keys = [
                Entity::RECAPTCHA_SITE_KEY,
                Entity::RECAPTCHA_SECRET_KEY
            ];

            foreach ($keys as $key) {
                delete_option($key);
                $this->options[$key]->setValue('');
            }
        }
    }

    /**
     * @param string|null $error_code
     * @return string
     */
    private function recaptchaErrorMessage(?string $error_code): string
    {
        switch ($error_code) {
            case 'missing-input-secret':
                return __('The secret parameter is missing.', 'minor-improvements');
            case 'missing-input-response':
                return __('The response parameter is missing.', 'minor-improvements');
            case 'invalid-input-secret':
                return __('The secret parameter is invalid or malformed.', 'minor-improvements');
            case 'invalid-input-response':
                return __('The response parameter is invalid or malformed.', 'minor-improvements');
            case 'bad-request':
                return __('The request is invalid or malformed.', 'minor-improvements');
            case 'timeout-or-duplicate':
                return __('The response is no longer valid: either is too old or has been used previously.', 'minor-improvements');
            default:
                return __('Unknown error.', 'minor-improvements');
        }
    }

    /**
     * @param $response
     * @return array|mixed
     */
    private function recaptchaResponseParse($response)
    {
        $secretKey = $this->getOptionValue(Entity::RECAPTCHA_SECRET_KEY);
        $rcpUrl = sprintf('https://www.recaptcha.net/recaptcha/api/siteverify?secret=%s&response=%s', $secretKey, $response);
        $response = (array)wp_remote_get($rcpUrl);

        $falseResponse = [
            'success' => false,
            'error-codes' => ['general-fail']
        ];

        return isset($response['body']) ? json_decode($response['body'], 1) : $falseResponse;
    }

    /**
     * @param $input
     * @return mixed|void
     */
    public function recaptchaVerify($input)
    {
        if (!empty($_POST)) {
            $response = strval(filter_input(INPUT_POST, self::RECAPTCHA_RESPONSE, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $parsedResponse = $this->recaptchaResponseParse($response);

            if (isset($parsedResponse['success']) && $parsedResponse['success'] === true) {
                return $input;
            } else {
                $errorTitle = 'reCAPTCHA';
                $errorParams = ['response' => 403, 'back_link' => 1];
                $failedMsg = '<p><strong>%s:</strong> Google reCAPTCHA %s. %s</p>';
                $error = __('Error', 'minor-improvements');
                $verificationFailed = __('verification failed', 'minor-improvements');

                if (!$response) {
                    wp_die(sprintf($failedMsg, $error, $verificationFailed, __('Do you have JavaScript enabled?', 'minor-improvements')), $errorTitle, $errorParams);
                }

                wp_die(sprintf($failedMsg, $error, $verificationFailed, $this->recaptchaErrorMessage($parsedResponse['error-codes'][0] ?? null)), $errorTitle, $errorParams);
            }
        }
    }

    /**
     * @return string
     */
    public function recaptchaProtectionStatus(): string
    {
        $class = 'warning';
        $name = __('Notice', 'minor-improvements');
        $status = __('is enabled', 'minor-improvements');
        $msg = __('Keep on mind, that in case of emergency, you can disable this plugin via FTP access, just rename the plugin folder.', 'minor-improvements');

        if (!$this->getOptionValue(Entity::RECAPTCHA_SITE_KEY) || !$this->getOptionValue(Entity::RECAPTCHA_SECRET_KEY)) {
            $class = 'error';
            $name = __('Warning', 'minor-improvements');
            $status = __('is disabled', 'minor-improvements');
            $msg = __('You have to <a href="https://www.google.com/recaptcha/admin" rel="external">register your domain</a>, get required Google reCAPTCHA keys V3 and save them bellow.', 'minor-improvements');
        }

        return sprintf('<div class="notice notice-%s"><p><strong>%s:</strong> Google reCAPTCHA %s!</p><p>%s</p></div>', $class, $name, $status, $msg);
    }

    /**
     * @return bool
     */
    public function recaptchaAdminCookieHash(): bool
    {
        $cookieHash = strval(filter_input(INPUT_COOKIE, Entity::RECAPTCHA_HASH, FILTER_SANITIZE_SPECIAL_CHARS));

        if ($cookieHash === md5($this->getOptionValue(Entity::RECAPTCHA_SITE_KEY) . $this->getOptionValue(Entity::RECAPTCHA_SECRET_KEY))) {
            return true;
        } else {
            return false;
        }
    }
}

new Main();
