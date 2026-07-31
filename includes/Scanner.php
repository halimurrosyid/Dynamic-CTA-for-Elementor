<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Scanner
 * Universal & High-Accuracy Area Scanner with Word-Boundary Collision Prevention.
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
        'lembang', 'banjaran', 'majalaya', 'cileunyi', 'rancaekek', 'dago', 'pasteur',

        // Banten
        'tangerang', 'tangerang-selatan', 'tangsel', 'kabupaten-tangerang', 'serang', 'cilegon', 'pandeglang',
        'lebak', 'rangkasbitung', 'bsd', 'bintaro', 'ciputat', 'pamulang', 'serpong', 'cikupa', 'balaraja',

        // Central Java & DIY (Jawa Tengah & Yogyakarta)
        'semarang', 'kabupaten-semarang', 'kab-semarang', 'ungaran', 'solo', 'surakarta', 'yogyakarta', 'jogja', 'sleman',
        'bantul', 'kulonprogo', 'gunungkidul', 'wates', 'wonosari', 'klaten', 'boyolali', 'sragen',
        'karanganyar', 'sukoharjo', 'wonogiri', 'magelang', 'mungkid', 'temanggung', 'wonosobo', 'purworejo',
        'kebumen', 'banyumas', 'purwokerto', 'cilacap', 'banjarnegara', 'pekalongan', 'kab-pekalongan', 'kabupaten-pekalongan', 'batang', 'kendal',
        'demak', 'kudus', 'jepara', 'pati', 'rembang', 'blora', 'grobogan', 'purwodadi', 'salatiga', 'pemalang',

        // East Java (Jawa Timur)
        'surabaya', 'sidoarjo', 'gresik', 'malang', 'batu', 'kabupaten-malang', 'mojokerto', 'jombang',
        'pasuruan', 'probolinggo', 'lumajang', 'bondowoso', 'situbondo', 'banyuwangi', 'jember', 'kediri',
        'blitar', 'tulungagung', 'trenggalek', 'nganjuk', 'madiun', 'magetan', 'ngawi', 'ponorogo',
        'pacitan', 'bojonegoro', 'tuban', 'lamongan', 'sampang', 'pamekasan', 'sumenep', 'bangkalan', 'madura',

        // Bali & Nusa Tenggara
        'denpasar', 'badung', 'kuta', 'canggu', 'seminyak', 'ubud', 'gianyar', 'tabanan', 'buleleng',
        'singaraja', 'jembrana', 'negara', 'karangasem', 'klungkung', 'bangli', 'mataram', 'lombok',

        // Sumatra
        'medan', 'deli-serdang', 'binjai', 'pematang-siantar', 'tebing-tinggi', 'kabanjahe',
        'palembang', 'lubuklinggau', 'prabumulih', 'pekanbaru', 'dumai', 'batam', 'tanjung-pinang',
        'padang', 'padang-panjang', 'bukittinggi', 'payakumbuh', 'pariaman', 'bandar-lampung', 'metro', 'lampung',
        'jambi', 'muaro-jambi', 'bengkulu', 'pangkal-pinang', 'bangka', 'belitung',

        // Kalimantan
        'banjarmasin', 'banjarbaru', 'martapura', 'balikpapan', 'samarinda', 'bontang', 'kutai',
        'pontianak', 'singkawang', 'palangkaraya', 'sampit', 'tarakan',

        // Sulawesi, Maluku & Papua
        'makassar', 'gowa', 'maros', 'bone', 'parepare', 'palopo', 'manado', 'bitung', 'tomohon',
        'palu', 'poso', 'kendari', 'baubau', 'gorontalo', 'ambon', 'ternate', 'jayapura', 'sorong',
        'manokwari', 'merauke', 'timika'
    ];

    /**
     * Import Destination URLs from Destination Sitemap & map Source URLs from current website
     *
     * @param string $sitemap_url
     * @return array
     */
    public static function import_destination_sitemap(string $sitemap_url): array {
        if (empty($sitemap_url) || !filter_var($sitemap_url, FILTER_VALIDATE_URL)) {
            return ['error' => __('Invalid Destination Sitemap URL provided.', 'dynamic-cta-elementor')];
        }

        $urls = self::extract_urls_from_sitemap($sitemap_url);
        if (empty($urls)) {
            return ['error' => __('No destination URLs could be retrieved from the specified Sitemap URL.', 'dynamic-cta-elementor')];
        }

        global $wpdb;
        $table = DB::get_mappings_table();

        $existing_keywords = $wpdb->get_col("SELECT keyword FROM {$table}");
        if (!is_array($existing_keywords)) {
            $existing_keywords = [];
        }
        $existing_map = array_fill_keys(array_map('strtolower', $existing_keywords), true);

        $total_scanned  = count($urls);
        $inserted_count = 0;
        $updated_count  = 0;

        foreach ($urls as $destination_url) {
            $destination_url = esc_url_raw(trim($destination_url));
            $path = trim(wp_parse_url($destination_url, PHP_URL_PATH) ?? '', '/');
            if (empty($path)) {
                continue;
            }

            $matched_keyword = self::detect_area_from_string($path);
            if (!$matched_keyword) {
                continue;
            }

            $keyword   = sanitize_key($matched_keyword);
            $area_name = ucwords(str_replace('-', ' ', $keyword));

            // Find matching source URL on current website
            $source_url = self::find_matching_source_url($keyword);

            if (isset($existing_map[$keyword])) {
                $wpdb->update(
                    $table,
                    [
                        'area_name'       => $area_name,
                        'source_url'      => $source_url,
                        'destination_url' => $destination_url,
                    ],
                    ['keyword' => $keyword],
                    ['%s', '%s', '%s'],
                    ['%s']
                );
                $updated_count++;
            } else {
                $result = $wpdb->insert(
                    $table,
                    [
                        'keyword'         => $keyword,
                        'area_name'       => $area_name,
                        'source_url'      => $source_url,
                        'destination_url' => $destination_url,
                    ],
                    ['%s', '%s', '%s', '%s']
                );

                if ($result) {
                    $inserted_count++;
                    $existing_map[$keyword] = true;
                }
            }
        }

        CTA_Resolver::clear_cache();

        return [
            'total_scanned' => $total_scanned,
            'inserted'      => $inserted_count,
            'updated'       => $updated_count,
        ];
    }

    /**
     * Find sample/matching source URL on current website for a given keyword
     *
     * @param string $keyword
     * @return string
     */
    public static function find_matching_source_url(string $keyword): string {
        $posts = get_posts([
            'post_type'      => ['post', 'page'],
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            's'              => $keyword,
            'fields'         => 'ids',
        ]);

        if (!empty($posts)) {
            $permalink = get_permalink($posts[0]);
            if ($permalink) {
                return esc_url_raw($permalink);
            }
        }

        return esc_url_raw(home_url('/' . $keyword . '/'));
    }

    /**
     * Recursively fetch URLs from XML Sitemap
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

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        if ($xml === false) {
            preg_match_all('/<loc>(https?:\/\/[^<]+)<\/loc>/i', $xml_content, $matches);
            if (!empty($matches[1])) {
                return array_unique($matches[1]);
            }
            return [];
        }

        if ($xml->getName() === 'sitemapindex') {
            foreach ($xml->sitemap as $sub_sitemap) {
                $sub_url = (string) $sub_sitemap->loc;
                if (!empty($sub_url)) {
                    $child_urls = self::extract_urls_from_sitemap($sub_url, $depth + 1);
                    $extracted_urls = array_merge($extracted_urls, $child_urls);
                }
            }
        } elseif ($xml->getName() === 'urlset') {
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
     * Detect area keyword STRICTLY with Word Boundary matching to prevent collisions (e.g. 'malang' vs 'pemalang')
     *
     * @param string $subject
     * @return string|null
     */
    public static function detect_area_from_string(string $subject): ?string {
        if (empty($subject)) {
            return null;
        }

        $subject = strtolower(trim($subject, '/'));
        $parts = explode('/', $subject);
        $last_segment = end($parts);

        // 1. Exact match on path segment (e.g. 'malang' or 'pemalang')
        if (in_array($last_segment, self::$indonesian_areas, true)) {
            return $last_segment;
        }

        // 2. Word/Hyphen Boundary Match on full string (longest area first)
        $areas = self::$indonesian_areas;
        usort($areas, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($areas as $area) {
            $pattern = '/(?<=^|[-_\s\/])' . preg_quote($area, '/') . '(?=$|[-_\s\/])/i';
            if (preg_match($pattern, $subject)) {
                return $area;
            }
        }

        return null;
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
