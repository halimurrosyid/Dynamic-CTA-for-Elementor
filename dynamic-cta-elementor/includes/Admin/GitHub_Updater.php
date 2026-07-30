<?php
namespace DynamicCTA\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GitHub_Updater
 * Enables automatic 1-click plugin updates directly inside WordPress dashboard from GitHub releases.
 */
class GitHub_Updater {

    private string $file;
    private string $plugin_slug;
    private string $basename;
    private string $github_username = 'halimurrosyid';
    private string $github_repo     = 'Dynamic-CTA-for-Elementor';
    private ?object $github_response = null;

    /**
     * Constructor
     *
     * @param string $file
     */
    public function __construct(string $file) {
        $this->file        = $file;
        $this->basename    = plugin_basename($file);
        $this->plugin_slug = current(explode('/', $this->basename));

        add_filter('site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_popup'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'post_install'], 10, 3);
    }

    /**
     * Fetch latest release info from GitHub API
     *
     * @return object|null
     */
    private function get_repository_info(): ?object {
        if ($this->github_response !== null) {
            return $this->github_response;
        }

        $url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest";

        $response = wp_remote_get($url, [
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (!$data || !isset($data->tag_name)) {
            return null;
        }

        $this->github_response = $data;
        return $this->github_response;
    }

    /**
     * Hook into WP update_plugins transient check
     *
     * @param object $transient
     * @return object
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $repo_info = $this->get_repository_info();
        if (!$repo_info) {
            return $transient;
        }

        $remote_version = ltrim($repo_info->tag_name, 'v');
        $current_version = DYNAMIC_CTA_VERSION;

        if (version_compare($remote_version, $current_version, '>')) {
            $package = $repo_info->zipball_url;
            if (!empty($repo_info->assets) && isset($repo_info->assets[0]->browser_download_url)) {
                $package = $repo_info->assets[0]->browser_download_url;
            }

            $obj = new \stdClass();
            $obj->slug        = $this->plugin_slug;
            $obj->plugin      = $this->basename;
            $obj->new_version = $remote_version;
            $obj->url         = "https://github.com/{$this->github_username}/{$this->github_repo}";
            $obj->package     = $package;
            $obj->icons       = [];
            $obj->banners     = [];

            $transient->response[$this->basename] = $obj;
        }

        return $transient;
    }

    /**
     * Provide Plugin Info popup modal content inside WordPress dashboard
     *
     * @param mixed $result
     * @param string $action
     * @param object $args
     * @return mixed
     */
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $repo_info = $this->get_repository_info();
        if (!$repo_info) {
            return $result;
        }

        $remote_version = ltrim($repo_info->tag_name, 'v');

        $plugin_info = new \stdClass();
        $plugin_info->name           = 'Dynamic CTA for Elementor';
        $plugin_info->slug           = $this->plugin_slug;
        $plugin_info->version        = $remote_version;
        $plugin_info->author         = '<a href="https://indahweb.com/">Mujaddid Halimurrosyid</a>';
        $plugin_info->homepage       = "https://github.com/{$this->github_username}/{$this->github_repo}";
        $plugin_info->download_link  = $repo_info->zipball_url;
        $plugin_info->sections       = [
            'description' => nl2br(esc_html($repo_info->body ? $repo_info->body : 'Dynamic CTA for Elementor release.')),
            'changelog'   => '<h4>' . esc_html($repo_info->tag_name) . '</h4>' . nl2br(esc_html($repo_info->body)),
        ];

        return $plugin_info;
    }

    /**
     * Ensure correct destination directory name after update installation
     *
     * @param bool $true
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function post_install($true, $hook_extra, $result) {
        global $wp_filesystem;

        $proper_folder = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        $wp_filesystem->move($result['destination'], $proper_folder);
        $result['destination'] = $proper_folder;

        return $result;
    }
}
