<?php

declare(strict_types=1);

namespace Yablonsky\Performance;

/**
 * Canonical YPerf admin-locale registry.
 *
 * YPerf never changes the WordPress locale itself. The current administrator's
 * WordPress locale is the authority; this class only loads the matching plugin
 * translation catalog and exposes the agreed product language scope.
 */
final class LanguageRegistry
{
    /** @var string */
    private static $loadedLocale = '';

    /** @var bool */
    private static $registeredPath = false;

    /**
     * Canonical language scope:
     * Agreed Yablonsky family scope: 36 first-class language families.
     * Simplified and Traditional Chinese are distinct canonical families;
     * regional WordPress locales remain variants inside each family.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function languages(): array
    {
        return array(
            'en' => array('name' => 'English', 'native' => 'English', 'locales' => array('en_US', 'en_GB')),
            'zh-CN' => array('name' => 'Chinese (Simplified)', 'native' => '简体中文', 'locales' => array('zh_CN', 'zh_SG')),
            'hi' => array('name' => 'Hindi', 'native' => 'हिन्दी', 'locales' => array('hi_IN')),
            'es' => array('name' => 'Spanish', 'native' => 'Español', 'locales' => array('es_ES', 'es_MX')),
            'fr' => array('name' => 'French', 'native' => 'Français', 'locales' => array('fr_FR')),
            'ar' => array('name' => 'Arabic', 'native' => 'العربية', 'locales' => array('ar'), 'rtl' => true),
            'bn' => array('name' => 'Bengali', 'native' => 'বাংলা', 'locales' => array('bn_BD')),
            'pt' => array('name' => 'Portuguese', 'native' => 'Português', 'locales' => array('pt_PT', 'pt_BR')),
            'ru' => array('name' => 'Russian', 'native' => 'Русский', 'locales' => array('ru_RU')),
            'ur' => array('name' => 'Urdu', 'native' => 'اردو', 'locales' => array('ur'), 'rtl' => true),
            'bg' => array('name' => 'Bulgarian', 'native' => 'Български', 'locales' => array('bg_BG')),
            'hr' => array('name' => 'Croatian', 'native' => 'Hrvatski', 'locales' => array('hr')),
            'cs' => array('name' => 'Czech', 'native' => 'Čeština', 'locales' => array('cs_CZ')),
            'da' => array('name' => 'Danish', 'native' => 'Dansk', 'locales' => array('da_DK')),
            'nl' => array('name' => 'Dutch', 'native' => 'Nederlands', 'locales' => array('nl_NL')),
            'et' => array('name' => 'Estonian', 'native' => 'Eesti', 'locales' => array('et')),
            'fi' => array('name' => 'Finnish', 'native' => 'Suomi', 'locales' => array('fi')),
            'de' => array('name' => 'German', 'native' => 'Deutsch', 'locales' => array('de_DE')),
            'el' => array('name' => 'Greek', 'native' => 'Ελληνικά', 'locales' => array('el')),
            'hu' => array('name' => 'Hungarian', 'native' => 'Magyar', 'locales' => array('hu_HU')),
            'ga' => array('name' => 'Irish', 'native' => 'Gaeilge', 'locales' => array('ga')),
            'it' => array('name' => 'Italian', 'native' => 'Italiano', 'locales' => array('it_IT')),
            'lv' => array('name' => 'Latvian', 'native' => 'Latviešu', 'locales' => array('lv')),
            'lt' => array('name' => 'Lithuanian', 'native' => 'Lietuvių', 'locales' => array('lt_LT')),
            'mt' => array('name' => 'Maltese', 'native' => 'Malti', 'locales' => array('mt_MT')),
            'pl' => array('name' => 'Polish', 'native' => 'Polski', 'locales' => array('pl_PL')),
            'ro' => array('name' => 'Romanian', 'native' => 'Română', 'locales' => array('ro_RO')),
            'sk' => array('name' => 'Slovak', 'native' => 'Slovenčina', 'locales' => array('sk_SK')),
            'sl' => array('name' => 'Slovenian', 'native' => 'Slovenščina', 'locales' => array('sl_SI')),
            'sv' => array('name' => 'Swedish', 'native' => 'Svenska', 'locales' => array('sv_SE')),
            'uk' => array('name' => 'Ukrainian', 'native' => 'Українська', 'locales' => array('uk')),
            'tr' => array('name' => 'Turkish', 'native' => 'Türkçe', 'locales' => array('tr_TR')),
            'id' => array('name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'locales' => array('id_ID')),
            'zh-TW' => array('name' => 'Chinese (Traditional)', 'native' => '繁體中文', 'locales' => array('zh_TW', 'zh_HK')),
            'ja' => array('name' => 'Japanese', 'native' => '日本語', 'locales' => array('ja')),
            'ko' => array('name' => 'Korean', 'native' => '한국어', 'locales' => array('ko_KR')),
        );
    }

    /** @return array<int,string> */
    public static function targetLocales(): array
    {
        $locales = array();
        foreach (self::languages() as $language) {
            foreach ((array) ($language['locales'] ?? array()) as $locale) {
                $locales[] = (string) $locale;
            }
        }
        return array_values(array_unique($locales));
    }

    public static function currentAdminLocale(): string
    {
        if (function_exists('determine_locale')) {
            return (string) determine_locale();
        }
        return function_exists('get_locale') ? (string) get_locale() : 'en_US';
    }

    /**
     * Load the administrator's effective locale without changing WordPress' locale.
     * The explicit custom path is needed for WordPress < 6.8 and enables
     * WordPress core to reload this domain after switch_to_locale().
     * WordPress 6.8+ uses the plugin headers for JIT registration.
     */
    public static function loadTextdomain(): void
    {
        global $wp_version;
        $locale = self::currentAdminLocale();
        if (!preg_match('/^[A-Za-z_]+$/D', $locale)) {
            return;
        }

        // WordPress >= 6.8 owns the bundled translations and locale switches.
        if (is_string($wp_version) && version_compare($wp_version, '6.8', '>=')) {
            self::$loadedLocale = $locale;
            return;
        }

        if (!self::$registeredPath && function_exists('load_plugin_textdomain')) {
            load_plugin_textdomain(
                'yablonsky-performance',
                false,
                dirname(plugin_basename(YPERF_FILE)) . '/languages'
            );
            self::$registeredPath = true;
        }

        if (self::$loadedLocale === $locale && function_exists('is_textdomain_loaded')
            && is_textdomain_loaded('yablonsky-performance')) {
            return;
        }

        // Prefer a translation that WordPress/WPML has already loaded.
        if (function_exists('is_textdomain_loaded') && is_textdomain_loaded('yablonsky-performance')) {
            self::$loadedLocale = $locale;
            return;
        }

        $mofile = dirname(__DIR__) . '/languages/yablonsky-performance-' . $locale . '.mo';
        if (is_readable($mofile) && function_exists('load_textdomain')) {
            load_textdomain('yablonsky-performance', $mofile);
        }
        self::$loadedLocale = $locale;
    }

    /**
     * WordPress fires change_locale after its own textdomain reload.
     * Retry the fallback on older installations if the domain was not found;
     * never override an already loaded WordPress/WPML domain.
     */
    public static function onLocaleChange(string $locale): void
    {
        self::$loadedLocale = '';
        self::loadTextdomain();
    }

    /**
     * A locale is complete only when an actual compiled catalog ships.
     * English is the source language and does not need a catalog.
     */
    public static function hasCompletePack(string $locale): bool
    {
        if (in_array($locale, array('en', 'en_US', 'en_GB'), true)) {
            return true;
        }
        $path = dirname(__DIR__) . '/languages/manifest.json';
        if (!is_readable($path)) {
            return false;
        }
        $manifest = wp_json_file_decode($path, array('associative' => true));
        $packs = is_array($manifest['packs'] ?? null) ? $manifest['packs'] : array();
        foreach ($packs as $pack) {
            if (!is_array($pack) || sanitize_key((string) ($pack['status'] ?? 'target')) !== 'complete' || (int) ($pack['coverage_percent'] ?? 0) !== 100) {
                continue;
            }
            $locales = array_map('strval', (array) ($pack['locales'] ?? array()));
            if (!in_array($locale, $locales, true)) {
                continue;
            }
            $base = dirname(__DIR__) . '/languages/yablonsky-performance-' . $locale;
            return is_readable($base . '.po') && is_readable($base . '.mo');
        }
        return false;
    }
}
