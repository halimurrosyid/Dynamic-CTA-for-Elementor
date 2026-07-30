<?php
namespace DynamicCTA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Scanner
 * Automated scanning of WordPress posts to extract area/city keywords from permalinks and populate mappings.
 */
class Scanner {

    /**
     * Common known Indonesian cities/regions keywords list for enhanced auto-detection accuracy.
     */
    private static array $known_areas = [
        'bandung', 'bekasi', 'jakarta', 'surabaya', 'semarang', 'medan', 'palembang',
        'tangerang', 'depok', 'bogor', 'malang', 'solo', 'yogyakarta', 'denpasar',
        'makassar', 'manado', 'batam', 'pekanbaru', 'banjarmasin', 'balikpapan',
        'samarinda', 'pontianak', 'lampung', 'jambi', 'cirebon', 'tasikmalaya',
        'sukabumi', 'purwakarta', 'karawang', 'subang', 'garut', 'cianjur', 'indramayu',
        'sumedang', 'majalengka', 'kuningan', 'ciamis', 'banjar', 'pangandaran',
        'sleman', 'bantu', 'kulonprogo', 'gunungkidul', 'klaten', 'boyolali',
        'sragen', 'karanganyar', 'sukoharjo', 'wonogiri', 'magelang', 'temanggung',
        'wonosobo', 'purworejo', 'kebumen', 'banyumas', 'purwokerto', 'cilacap',
        'banjarnegara', 'pekalongan', 'batang', 'kendal', 'demak', 'kudus', 'jepara',
        'pati', 'rembang', 'blora', 'grobogan', 'sidoarjo', 'gresik', 'mojokerto',
        'jombang', 'pasuruan', 'probolinggo', 'lumajang', 'bondowoso', 'situbondo',
        'banyuwangi', 'jember', 'kediri', 'blitar', 'tulungagung', 'trenggalek',
        'nganjuk', 'madiun', 'magetan', 'ngawi', 'ponorogo', 'pacitan', 'bojonegoro',
        'tuban', 'lamongan', 'badung', 'gianyar', 'tabanan', 'buleleng', 'jembrana',
        'karangasem', 'klungkung', 'bangli', 'mataram', 'lombok', 'kupang', 'jayapura'
    ];

    /**
     * Scan published posts and generate area mappings automatically.
     *
     * @return array
     */
    public static function scan_and_generate_mappings(): array {
        global $wpdb;
        $table = DB::get_mappings_table();

        // Get existing keywords from database to avoid duplicate checks
        $existing_keywords = $wpdb->get_col("SELECT keyword FROM {$table}");
        if (!is_array($existing_keywords)) {
            $existing_keywords = [];
        }
        $existing_keywords_map = array_fill_keys(array_map('strtolower', $existing_keywords), true);

        // Fetch published posts
        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        $post_ids = get_posts($args);
        $total_scanned = count($post_ids);
        $inserted_count = 0;
        $skipped_count = 0;

        $base_url = get_option('dynamic_cta_default_url', 'https://jasawifi.com/iconnet/');
        $base_url = rtrim($base_url, '/') . '/';

        foreach ($post_ids as $pid) {
            $post = get_post($pid);
            if (!$post) {
                continue;
            }

            $slug = strtolower($post->post_name);
            $keyword = self::extract_keyword_from_slug($slug);

            if (!$keyword) {
                continue;
            }

            $keyword = sanitize_key($keyword);

            if (isset($existing_keywords_map[$keyword])) {
                $skipped_count++;
                continue;
            }

            // Format Area Name (e.g. bandung -> Bandung, surabaya-selatan -> Surabaya Selatan)
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
                $existing_keywords_map[$keyword] = true;
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
     * Extract keyword from post slug
     *
     * @param string $slug
     * @return string|null
     */
    private static function extract_keyword_from_slug(string $slug): ?string {
        if (empty($slug)) {
            return null;
        }

        // Check against known list of Indonesian cities first
        foreach (self::$known_areas as $known) {
            if (str_contains($slug, $known)) {
                return $known;
            }
        }

        // Pattern matching for standard slug structures like:
        // pasang-iconnet-bandung, harga-iconnet-bekasi, promo-iconnet-jakarta, paket-iconnet-surabaya
        $prefixes = [
            'pasang-iconnet-',
            'harga-iconnet-',
            'paket-iconnet-',
            'promo-iconnet-',
            'iconnet-bandung-',
            'iconnet-',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($slug, $prefix)) {
                $extracted = substr($slug, strlen($prefix));
                if (!empty($extracted)) {
                    return $extracted;
                }
            }
        }

        // Fallback: Check last hyphenated segment if length >= 3
        $parts = explode('-', $slug);
        if (count($parts) > 1) {
            $last_part = end($parts);
            if (strlen($last_part) >= 3 && !in_array($last_part, ['murah', 'terbaru', '2023', '2024', '2025', '2026', 'home', 'pro'], true)) {
                return $last_part;
            }
        }

        return null;
    }
}
