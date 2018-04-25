<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

namespace PHPPgAdmin;

class Translations
{
    // List of language files, and encoded language name.

    public $appLangFiles = [
        'afrikaans'          => 'Afrikaans',
        'arabic'             => '&#1593;&#1585;&#1576;&#1610;',
        'catalan'            => 'Catal&#224;',
        'chinese-tr'         => '&#32321;&#39636;&#20013;&#25991;',
        'chinese-sim'        => '&#31616;&#20307;&#20013;&#25991;',
        'chinese-utf8-zh_TW' => '&#27491;&#39636;&#20013;&#25991;&#65288;UTF-8&#65289;',
        'chinese-utf8-zh_CN' => '&#31616;&#20307;&#20013;&#25991;&#65288;UTF-8&#65289;',
        'czech'              => '&#268;esky',
        'danish'             => 'Danish',
        'dutch'              => 'Nederlands',
        'english'            => 'English',
        'french'             => 'Français',
        'galician'           => 'Galego',
        'german'             => 'Deutsch',
        'greek'              => '&#917;&#955;&#955;&#951;&#957;&#953;&#954;&#940;',
        'hebrew'             => 'Hebrew',
        'hungarian'          => 'Magyar',
        'italian'            => 'Italiano',
        'japanese'           => '&#26085;&#26412;&#35486;',
        'lithuanian'         => 'Lietuvi&#371;',
        'mongol'             => 'Mongolian',
        'polish'             => 'Polski',
        'portuguese-br'      => 'Portugu&ecirc;s-Brasileiro',
        'portuguese-pt'      => 'Portugu&ecirc;s',
        'romanian'           => 'Rom&acirc;n&#259;',
        'russian-utf8'       => '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081; (UTF-8)',
        'russian'            => '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
        'slovak'             => 'Slovensky',
        'spanish'            => 'Espa&ntilde;ol',
        'swedish'            => 'Svenska',
        'turkish'            => 'T&uuml;rk&ccedil;e',
        'ukrainian'          => '&#1059;&#1082;&#1088;&#1072;&#9558;&#1085;&#1089;&#1100;&#1082;&#1072;',
    ];

    public $appClasses = [
        'afrikaans'          => 'Afrikaans',
        'arabic'             => 'Arabic',
        'catalan'            => 'Catalan',
        'chinese-tr'         => 'ChineseTr',
        'chinese-sim'        => 'ChineseSim',
        'chinese-utf8-zh_TW' => 'ChineseUtf8ZhTw',
        'chinese-utf8-zh_CN' => 'ChineseUtf8ZhCn',
        'czech'              => 'Czech',
        'danish'             => 'Danish',
        'dutch'              => 'Dutch',
        'english'            => 'English',
        'french'             => 'French',
        'galician'           => 'Galician',
        'german'             => 'German',
        'greek'              => 'Greek',
        'hebrew'             => 'Hebrew',
        'hungarian'          => 'Hungarian',
        'italian'            => 'Italian',
        'japanese'           => 'Japanese',
        'lithuanian'         => 'Lithuanian',
        'mongol'             => 'Mongol',
        'polish'             => 'Polish',
        'portuguese-br'      => 'PortugueseBr',
        'portuguese-pt'      => 'PortuguesePt',
        'romanian'           => 'Romanian',
        'russian-utf8'       => 'RussianUtf8',
        'russian'            => 'Russian',
        'slovak'             => 'Slovak',
        'spanish'            => 'Spanish',
        'swedish'            => 'Swedish',
        'turkish'            => 'Turkish',
        'ukrainian'          => 'Ukrainian',
    ];

    private $_language;

    /**
     * ISO639 language code to language file mapping.
     * See http://www.w3.org/WAI/ER/IG/ert/iso639.htm for language codes
     * If it's available 'language-country', but not general
     * 'language' translation (eg. 'portuguese-br', but not 'portuguese')
     * specify both 'la' => 'language-country' and 'la-co' => 'language-country'.
     */
    public $availableLanguages = [
        'af'         => 'afrikaans',
        'ar'         => 'arabic',
        'ca'         => 'catalan',
        'zh'         => 'chinese-tr',
        'zh-cn'      => 'chinese-sim',
        'utf8-zh-cn' => 'chinese-utf8-zh_TW',
        'utf8-zh-tw' => 'chinese-utf8-zh_CN',
        'cs'         => 'czech',
        'da'         => 'danish',
        'nl'         => 'dutch',
        'en'         => 'english',
        'fr'         => 'french',
        'gl'         => 'galician',
        'de'         => 'german',
        'el'         => 'greek',
        'he'         => 'hebrew',
        'hu'         => 'hungarian',
        'it'         => 'italian',
        'ja'         => 'japanese',
        'lt'         => 'lithuanian',
        'mn'         => 'mongol',
        'pl'         => 'polish',
        'pt-br'      => 'portuguese-br',
        'pt'         => 'portuguese-pt',
        'ro'         => 'romanian',
        'ru'         => 'russian',
        'ru'         => 'russian',
        'sk'         => 'slovak',
        'es'         => 'spanish',
        'sv'         => 'swedish',
        'tr'         => 'turkish',
        'uk'         => 'ukrainian',
    ];

    public function __construct($container)
    {
        $availableLanguages = $this->availableLanguages;
        $appLangFiles       = $this->appLangFiles;
        $appClasses         = $this->appClasses;
        $_language          = $this->_language;
        $conf               = $container->conf;

        $languages_iso_code = array_flip($availableLanguages);

        if (!isset($conf['default_lang'])) {
            $conf['default_lang'] = 'english';
        }

        // 1. Check for the language from a request var
        if (isset($_REQUEST['language'], $appLangFiles[$_REQUEST['language']])) {
            /* save the selected language in cookie for a year */
            setcookie('webdbLanguage', $_REQUEST['language'], time() + 31536000);
            $_language = $_REQUEST['language'];
        } elseif (!isset($_language) && isset($_SESSION['webdbLanguage'], $appLangFiles[$_SESSION['webdbLanguage']])) {
            // 2. Check for language session var
            $_language = $_SESSION['webdbLanguage'];
        } elseif (!isset($_language) && isset($_COOKIE['webdbLanguage'], $appLangFiles[$_COOKIE['webdbLanguage']])) {
            // 3. Check for language in cookie var
            $_language = $_COOKIE['webdbLanguage'];
        } elseif (!isset($_language) && $conf['default_lang'] == 'auto' && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // 4. Check for acceptable languages in HTTP_ACCEPT_LANGUAGE var
            // extract acceptable language tags
            // (http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4)
            preg_match_all(
                '/\s*([a-z]{1,8}(?:-[a-z]{1,8})*)(?:;q=([01](?:.[0-9]{0,3})?))?\s*(?:,|$)/',
                strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']),
                $_m,
                PREG_SET_ORDER
            );
            foreach ($_m as $_l) {
                // $_l[1] = language tag, [2] = quality
                if (!isset($_l[2])) {
                    $_l[2] = 1;
                }
                // Default quality to 1
                if ($_l[2] > 0 && $_l[2] <= 1 && isset($availableLanguages[$_l[1]])) {
                    // Build up array of (quality => language_file)
                    $_acceptLang[$_l[2]] = $availableLanguages[$_l[1]];
                }
            }
            unset($_m, $_l);

            if (isset($_acceptLang)) {
                // Sort acceptable languages by quality
                krsort($_acceptLang, SORT_NUMERIC);
                $_language = reset($_acceptLang);
                unset($_acceptLang);
            }
        } elseif (!isset($_language) && $conf['default_lang'] != 'auto' && isset($appLangFiles[$conf['default_lang']])) {
            // 5. Otherwise resort to the default set in the config file
            $_language = $conf['default_lang'];
        } else {
            // 6. Otherwise, default to english.
            $_language = 'english';
        }

        $_type = '\PHPPgAdmin\Translations\\'.$appClasses[$_language];

        $langClass = new $_type();

        $_SESSION['webdbLanguage'] = $_language;

        if (array_key_exists($_language, $languages_iso_code)) {
            $_isolang = $languages_iso_code[$_language];
        } else {
            $_isolang = '';
        }
        $_SESSION['isolang'] = $_isolang;

        $container->offsetSet('appLangFiles', $appLangFiles);
        $container->offsetSet('language', $_language);
        $container->offsetSet('isolang', $_isolang);

        $this->lang = $langClass->getLang();
    }
}
