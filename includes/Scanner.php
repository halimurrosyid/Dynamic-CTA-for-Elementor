<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Scanner
 * Universal & High-Accuracy Area Scanner supporting XML Sitemaps, WP Posts, and Taxonomies.
 */
class Scanner {

    /**
     * Comprehensive database of Indonesian cities, regencies, and major districts
     */
    private static array $indonesian_areas = [
        // DKI Jakarta & Sub-districts
        'jakarta', 'jakarta-selatan', 'jakarta-barat', 'jakarta-timur', 'jakarta-utara', 'jakarta-pusat',
        'jaksel', 'jakbar', 'jaktim', 'jakut', 'jakpus', 'kebayoran', 'tebet', 'cilandak', 'jagakarsa',
        'pasar-minggu', 'pancoran', 'mampang', 'setiabudi', 'kuningan', 'senayan', 'palmerah', 'grogol',
        'kembangan', 'kebon-jeruk', 'kalideres', 'cengkareng', 'tanjung-priok', 'kelapa-gading', 'sunter',
        'kemayoran', 'cempaka-putih', 'menteng', 'tanah-abang', 'gambir', 'sawah-besar', 'matraman',
        'pulo-gadung', 'jatinegara', 'duren-sawit', 'kramat-jati', 'makasar', 'ciracas', 'pasar-rebo', 'cipayung',

        // West Java (Jawa Barat)
        'bandung', 'bandung-barat', 'kabupaten-bandung', 'cimahi', 'bekasi', 'kabupaten-bekasi', 'cikarang',
        'bogor', 'kabupaten-bogor', 'cibinong', 'depok', 'cinere', 'sawangan', 'tasikmalaya', 'cirebon',
        'sukabumi', 'purwakarta', 'karawang', 'subang', 'garut', 'cianjur', 'indramayu', 'sumedang',
        'majalengka', 'kuningan', 'ciamis', 'banjar', 'pangandaran', 'soreang', 'ngamprah', 'singaparna',
        'lembang', 'banjaran', 'majalaya', 'cileunyi', 'rancaekek', 'dago', 'pasteur', 'buplatform',

        // Banten
        'tangerang', 'tangerang-selatan', 'tangsel', 'kabupaten-tangerang', 'serang', 'cilegon', 'pandeglang',
        'lebak', 'rangkasbitung', 'bsd', 'bintaro', 'ciputat', 'pamulang', 'serpong', 'cikupa', 'balaraja',

        // Central Java & DIY (Jawa Tengah & Yogyakarta)
        'semarang', 'kabupaten-semarang', 'ungaran', 'solo', 'surakarta', 'yogyakarta', 'jogja', 'sleman',
        'bantul', 'kulonprogo', 'gunungkidul', 'wates', 'wonosari', 'klaten', 'boyolali', 'sragen',
        'karanganyar', 'sukoharjo', 'wonogiri', 'magelang', 'mungkid', 'temanggung', 'wonosobo', 'purworejo',
        'kebumen', 'banyumas', 'purwokerto', 'cilacap', 'banjarnegara', 'pekalongan', 'batang', 'kendal',
        'demak', 'kudus', 'jepara', 'pati', 'rembang', 'blora', 'grobogan', 'purwodadi', 'salatiga',

        // East Java (Jawa Timur)
        'surabaya', 'sidoarjo', 'gresik', 'malang', 'batu', 'kabupaten-malang', 'mojokerto', 'jombang',
        'pasuruan', 'probolinggo', 'lumajang', 'bondowoso', 'situbondo', 'banyuwangi', 'jember', 'kediri',
        'blitar', 'tulungagung', 'trenggalek', 'nganjuk', 'madiun', 'magetan', 'ngawi', 'ponorogo',
        'pacitan', 'bojonegoro', 'tuban', 'lamongan', 'sampang', 'pamekasan', 'sumenep', 'bangkalan', 'madura',

        // Bali & Nusa Tenggara
        'denpasar', 'badung', 'kuta', 'canggu', 'seminyak', 'ubud', 'gianyar', 'tabanan', 'buleleng',
        'singaraja', 'jembrana', 'negara', 'karangasem', 'klungkung', 'bangli', 'mataram', 'lombok',
        'lombok-barat', 'lombok-tengah', 'lombok-timur', 'bima', 'sumbawa', 'kupang', 'labuan-bajo',

        // Sumatra
        'medan', 'deli-serdang', 'binjai', 'pematang-siantar', 'tebing-tinggi', 'kabanjahe', 'balige',
        'palembang', 'lubuklinggau', 'prabumulih', 'pekanbaru', 'dumai', 'batam', 'tanjung-pinang',
        'padang', 'bukittinggi', 'payakumbuh', 'pariaman', 'bandar-lampung', 'metro', 'lampung',
        'jambi', 'muaro-jambi', 'bengkulu', 'bengkulu-selatan', 'pangkal-pinang', 'bangka', 'belitung',

        // Kalimantan
        'banjarmasin', 'banjarbaru', 'martapura', 'balikpapan', 'samarinda', 'bontang', 'kutai',
        'pontianak', 'singkawang', 'palangkaraya', 'sampit', 'tarakan', 'nununkan',

        // Sulawesi, Maluku & Papua
        'makassar', 'gowa', 'maros', 'bone', 'parepare', 'palopo', 'manado', 'bitung', 'tomohon',
        'palu', 'poso', 'kendari', 'baubau', 'gorontalo', 'ambon', 'ternate', 'jayapura', 'sorong',
        'manokwari', 'merauke', 'timika'
    ];

    /**
     * Scan XML Sitemap URL and extract area mappings
     *
     * @param string $sitemap_url
     * @return array
     */
    public static function scan_sitemap(string $sitemap_url): array {
        if (empty($sitemap_url) || !filter_var($sitemap_url, FILTER_VALIDATE_URL)) {
            return ['error' => __('Invalid Sitemap URL provided.', 'dynamic-cta-elementor')];
        }

        $urls = self::extract_urls_from_sitemap($sitemap_url);
        if (empty($urls)) {
            return ['error' => __('No URLs could be retrieved from the specified Sitemap.', 'dynamic-cta-elementor')];
        }

        return self::process_url_list($urls);
    }

    /**
     * Recursively fetch URLs from XML Sitemap or Sitemap Index
     *
     * @param string $url
     * @param int $depth
     * @return array
     */
    private static function extract_urls_from_sitemap(string $url, int $depth = 0): array {
        if ($depth > 3) {
            return [];
        }

        $response = wp_remote_get($url, [
            'timeout'    => 15,
            'sslverify'  => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) WordPress DynamicCTA/1.0',
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $xml_content = wp_remote_retrieve_body($response);
        if (empty($xml_content)) {
            return [];
        }

        $extracted_urls = [];

        // Parse XML using SimpleXML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        if ($xml === false) {
            // Regex fallback if XML parsing fails
            preg_match_all('/<loc>(https?:\/\/[^<]+)<\/loc>/i', $xml_content, $matches);
            if (!empty($matches[1])) {
                return array_unique($matches[1]);
            }
            return [];
        }

        // Handle Sitemap Index (<sitemapindex>)
        if ($xml->getName() === 'sitemapindex') {
            foreach ($xml->sitemap as $sub_sitemap) {
                $sub_url = (string) $sub_sitemap->loc;
                if (!empty($sub_url)) {
                    $child_urls = self::extract_urls_from_sitemap($sub_url, $depth + 1);
                    $extracted_urls = array_merge($extracted_urls, $child_urls);
                }
            }
        } 
        // Handle URLset (<urlset>)
        elseif ($xml->getName() === 'urlset') {
            foreach ($xml->url as $url_entry) {
                $loc = (string) $url_entry->loc;
                if (!empty($loc)) {
                    $extracted_urls[] = $loc;
                }
            }
        }

        libxml_clear_errors();
        return array_unique($extracted_urls);
    }

    /**
     * Scan WordPress Posts & Categories cleanly
     *
     * @return array
     */
    public static function scan_posts_and_taxonomies(): array {
        $post_ids = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        $urls = [];
        foreach ($post_ids as $pid) {
            $permalink = get_permalink($pid);
            if ($permalink) {
                $urls[] = $permalink;
            }
        }

        // Also check category slugs & names
        $categories = get_categories(['hide_empty' => false]);
        if (!is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $urls[] = $cat->slug;
            }
        }

        return self::process_url_list($urls);
    }

    /**
     * Process list of URLs/strings against Location Dictionary and populate database
     *
     * @param array $items
     * @return array
     */
    private static function process_url_list(array $items): array {
        global $wpdb;
        $table = DB::get_mappings_table();

        $existing_keywords = $wpdb->get_col("SELECT keyword FROM {$table}");
        if (!is_array($existing_keywords)) {
            $existing_keywords = [];
        }
        $existing_map = array_fill_keys(array_map('strtolower', $existing_keywords), true);

        $base_url = get_option('dynamic_cta_default_url', 'https://your-destination-site.com/target-path/');
        $base_url = rtrim($base_url, '/') . '/';

        $total_scanned = count($items);
        $inserted_count = 0;
        $skipped_count = 0;

        foreach ($items as $item) {
            $slug = strtolower(trim(wp_parse_url($item, PHP_URL_PATH) ?? $item, '/'));
            if (empty($slug)) {
                continue;
            }

            // Extract valid area keyword STRICTLY from dictionary
            $matched_keyword = self::detect_area_from_string($slug);

            if (!$matched_keyword) {
                continue;
            }

            $keyword = sanitize_key($matched_keyword);

            if (isset($existing_map[$keyword])) {
                $skipped_count++;
                continue;
            }

            // Format Area Name (e.g. bandung-barat -> Bandung Barat)
            $area_name = ucwords(str_replace('-', ' ', $keyword));
            $destination_url = $base_url . $keyword . '/';

            $result = $wpdb->insert(
                $table,
                [
                    'keyword'         => $keyword,
                    'area_name'       => $area_name,
                    'destination_url' => esc_url_raw($destination_url),
                ],
                ['%s', '%s', '%s']
            );

            if ($result) {
                $inserted_count++;
                $existing_map[$keyword] = true;
            }
        }

        CTA_Resolver::clear_cache();

        return [
            'total_scanned' => $total_scanned,
            'inserted'      => $inserted_count,
            'skipped'       => $skipped_count,
        ];
    }

    /**
     * Detect area keyword STRICTLY from string using area dictionary
     *
     * @param string $subject
     * @return string|null
     */
    public static function detect_area_from_string(string $subject): ?string {
        if (empty($subject)) {
            return null;
        }

        $best_match = null;
        $best_len = 0;

        foreach (self::$indonesian_areas as $area) {
            if (str_contains($subject, $area)) {
                $len = strlen($area);
                if ($len > $best_len) {
                    $best_len = $len;
                    $best_match = $area;
                }
            }
        }

        return $best_match;
    }

    /**
     * Clear all mappings from database
     *
     * @return bool
     */
    public static function truncate_mappings(): bool {
        global $wpdb;
        $table = DB::get_mappings_table();
        $result = $wpdb->query("TRUNCATE TABLE {$table}");
        CTA_Resolver::clear_cache();
        return (bool) $result;
    }
}
