<?php
namespace DynamicCTA\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class GitHub_Updater
 * Fully automated WordPress dashboard update checker powered by GitHub Releases & Raw Branch fallback.
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
     * Get remote plugin information (checks Releases API first, falls back to Raw Main branch)
     *
     * @return object|null
     */
    private function get_repository_info(): ?object {
        if ($this->github_response !== null) {
            return $this->github_response;
        }

        $latest_info = null;
        $highest_version = '0.0.0';

        // 1. Read raw header from main branch
        $raw_url = "https://raw.githubusercontent.com/{$this->github_username}/{$this->github_repo}/main/dynamic-cta-elementor.php?v=" . time();
        $raw_response = wp_remote_get($raw_url, [
            'timeout' => 10,
            'headers' => ['Cache-Control' => 'no-cache'],
        ]);

        if (!is_wp_error($raw_response) && wp_remote_retrieve_response_code($raw_response) === 200) {
            $content = wp_remote_retrieve_body($raw_response);
            if (preg_match('/Version:\s*([0-9\.]+)/i', $content, $matches)) {
                $version = trim($matches[1]);
                $package = "https://github.com/{$this->github_username}/{$this->github_repo}/archive/refs/heads/main.zip";

                $latest_info = (object) [
                    'version'      => $version,
                    'package'      => $package,
                    'changelog'    => 'Automated update from main branch.',
                    'download_url' => $package,
                ];
                $highest_version = $version;
            }
        }

        // 2. Try Releases API
        $release_url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest";
        $response = wp_remote_get($release_url, [
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ],
            'timeout' => 10,
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            if ($data && isset($data->tag_name)) {
                $rel_version = ltrim($data->tag_name, 'v');
                if (version_compare($rel_version, $highest_version, '>')) {
                    $package_url = $data->zipball_url;
                    if (!empty($data->assets) && isset($data->assets[0]->browser_download_url)) {
                        $package_url = $data->assets[0]->browser_download_url;
                    }

                    $latest_info = (object) [
                        'version'      => $rel_version,
                        'package'      => $package_url,
                        'changelog'    => $data->body ? $data->body : 'Automatic update via GitHub Release.',
                        'download_url' => $package_url,
                    ];
                }
            }
        }

        $this->github_response = $latest_info;
        return $this->github_response;
    }

    /**
     * Check if a new version is available on GitHub
     *
     * @param object $transient
     * @return object
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $info = $this->get_repository_info();
        if (!$info) {
            return $transient;
        }

        if (version_compare($info->version, DYNAMIC_CTA_VERSION, '>')) {
            $obj = new \stdClass();
            $obj->slug        = $this->plugin_slug;
            $obj->plugin      = $this->basename;
            $obj->new_version = $info->version;
            $obj->url         = "https://github.com/{$this->github_username}/{$this->github_repo}";
            $obj->package     = $info->package;
            $obj->icons       = [];
            $obj->banners     = [];

            $transient->response[$this->basename] = $obj;
        }

        return $transient;
    }

    /**
     * Display details modal inside WordPress Dashboard
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

        $info = $this->get_repository_info();
        if (!$info) {
            return $result;
        }

        $plugin_info = new \stdClass();
        $plugin_info->name           = 'Dynamic CTA for Elementor';
        $plugin_info->slug           = $this->plugin_slug;
        $plugin_info->version        = $info->version;
        $plugin_info->author         = '<a href="https://indahweb.com/">Mujaddid Halimurrosyid</a>';
        $plugin_info->homepage       = "https://github.com/{$this->github_username}/{$this->github_repo}";
        $plugin_info->download_link  = $info->download_url;
        $plugin_info->sections       = [
            'description' => 'Dynamic area-based CTA link migration plugin for Elementor Pro.',
            'changelog'   => nl2br(esc_html($info->changelog)),
        ];

        return $plugin_info;
    }

    /**
     * Fix destination directory after GitHub zip unzipping
     *
     * @param bool $true
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function post_install($true, $hook_extra, $result) {
        global $wp_filesystem;

        $proper_folder = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        
        // Move unzipped directory to match plugin slug directory name
        if ($result['destination'] !== $proper_folder) {
            $wp_filesystem->move($result['destination'], $proper_folder);
            $result['destination'] = $proper_folder;
        }

        return $result;
    }
}
