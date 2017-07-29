<?php
    /**
     * Supported Translations for phpPgAdmin
     *
     * $Id: translations.php,v 1.4 2007/02/10 03:48:34 xzilla Exp $
     */

// List of language files, and encoded language name.

    $appLangFiles = [
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
        'romanian'           => 'Rom&acirc;n&#259;',
        'russian'            => '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
        'russian-utf8'       => '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081; (UTF-8)',
        'slovak'             => 'Slovensky',
        'swedish'            => 'Svenska',
        'spanish'            => 'Espa&ntilde;ol',
        'turkish'            => 'T&uuml;rk&ccedil;e',
        'ukrainian'          => '&#1059;&#1082;&#1088;&#1072;&#9558;&#1085;&#1089;&#1100;&#1082;&#1072;',
    ];

// ISO639 language code to language file mapping.
// See http://www.w3.org/WAI/ER/IG/ert/iso639.htm for language codes

// If it's available 'language-country', but not general
// 'language' translation (eg. 'portuguese-br', but not 'portuguese')
// specify both 'la' => 'language-country' and 'la-co' => 'language-country'.

    $availableLanguages = [
        'af'         => 'afrikaans',
        'ar'         => 'arabic',
        'ca'         => 'catalan',
        'zh'         => 'chinese-tr',
        'zh-cn'      => 'chinese-sim',
        'utf8-zh-cn' => 'chinese-utf8-cn',
        'utf8-zh-tw' => 'chinese-utf8-tw',
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
        'pt'         => 'portuguese-br',
        'pt-br'      => 'portuguese-br',
        'ro'         => 'romanian',
        'ru'         => 'russian',
        'sk'         => 'slovak',
        'sv'         => 'swedish',
        'es'         => 'spanish',
        'tr'         => 'turkish',
        'uk'         => 'ukrainian',
    ];

    $languages_iso_code = array_flip($availableLanguages);

// Always include english.php, since it's the master language file
    if (!isset($conf['default_lang'])) {
        $conf['default_lang'] = 'english';
    }

    $lang = [];
    include_once BASE_PATH . '/src/lang/english.php';

// Determine language file to import:
    unset($_language);

// 1. Check for the language from a request var
    if (isset($_REQUEST['language']) && isset($appLangFiles[$_REQUEST['language']])) {
        /* save the selected language in cookie for a year */
        setcookie('webdbLanguage', $_REQUEST['language'], time() + 31536000);
        $_language = $_REQUEST['language'];
    }

// 2. Check for language session var
    if (!isset($_language) && isset($_SESSION['webdbLanguage']) && isset($appLangFiles[$_SESSION['webdbLanguage']])) {
        $_language = $_SESSION['webdbLanguage'];
    }

// 3. Check for language in cookie var
    if (!isset($_language) && isset($_COOKIE['webdbLanguage']) && isset($appLangFiles[$_COOKIE['webdbLanguage']])) {
        $_language = $_COOKIE['webdbLanguage'];
    }

// 4. Check for acceptable languages in HTTP_ACCEPT_LANGUAGE var
    if (!isset($_language) && $conf['default_lang'] == 'auto' && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        // extract acceptable language tags
        // (http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4)
        preg_match_all('/\s*([a-z]{1,8}(?:-[a-z]{1,8})*)(?:;q=([01](?:.[0-9]{0,3})?))?\s*(?:,|$)/', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), $_m,
            PREG_SET_ORDER);
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
        unset($_m);
        unset($_l);
        if (isset($_acceptLang)) {
            // Sort acceptable languages by quality
            krsort($_acceptLang, SORT_NUMERIC);
            $_language = reset($_acceptLang);
            unset($_acceptLang);
        }
    }

// 5. Otherwise resort to the default set in the config file
    if (!isset($_language) && $conf['default_lang'] != 'auto' && isset($appLangFiles[$conf['default_lang']])) {
        $_language = $conf['default_lang'];
    }

// 6. Otherwise, default to english.
    if (!isset($_language)) {
        $_language = 'english';
    }

// Import the language file
    if (isset($_language)) {
        include BASE_PATH . "/src/lang/{$_language}.php";
        $_SESSION['webdbLanguage'] = $_language;

        if (array_key_exists($_language, $languages_iso_code)) {
            $_isolang = $languages_iso_code[$_language];
        } else {
            $_isolang = '';
        }
        $_SESSION['isolang'] = $_isolang;
        //\PC::debug($_language, 'webdbLanguage');
    }
