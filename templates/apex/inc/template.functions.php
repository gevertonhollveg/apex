<?php
/**
 * WebEngine CMS — Modern Template Functions
 *
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * Licensed under the MIT license
 */

function templateBuildNavbar() {
    $cfg = loadConfig('navbar');
    if(!is_array($cfg)) return;

    $visibleElements = array();
    $groupItems = array();

    foreach($cfg as $element) {
        if(!is_array($element)) continue;
        if(!$element['active']) continue;

        if($element['visibility'] == 'guest') if(isLoggedIn()) continue;
        if($element['visibility'] == 'user') if(!isLoggedIn()) continue;

        $link = ($element['type'] == 'internal' ? __BASE_URL__ . $element['link'] : $element['link']);
        $title = (check_value(lang($element['phrase'], true)) ? lang($element['phrase'], true) : 'Unk_phrase');

        $item = array(
            'raw' => $element,
            'link' => $link,
            'title' => $title,
        );

        $visibleElements[] = $item;

        if($element['type'] === 'internal') {
            $normalizedLink = strtolower(trim((string)$element['link']));
            if(
                in_array($normalizedLink, array('info', 'droplist', 'roadmap')) ||
                preg_match('/(^|\?|&)page=(info|droplist|roadmap)($|&)/i', $normalizedLink)
            ) {
                $groupItems[] = $item;
            }
        }
    }

    if(!count($visibleElements)) return;

    usort($groupItems, function($a, $b) {
        return ((int)$a['raw']['order']) - ((int)$b['raw']['order']);
    });

    $groupRendered = false;
    $groupTitle = (check_value(lang('menu_txt_11', true)) ? lang('menu_txt_11', true) : 'Info');

    foreach($visibleElements as $item) {
        $element = $item['raw'];

        $isGroupedItem = false;
        if($element['type'] === 'internal') {
            $normalizedLink = strtolower(trim((string)$element['link']));
            if(
                in_array($normalizedLink, array('info', 'droplist', 'roadmap')) ||
                preg_match('/(^|\?|&)page=(info|droplist|roadmap)($|&)/i', $normalizedLink)
            ) {
                $isGroupedItem = true;
            }
        }

        if($isGroupedItem) {
            if($groupRendered) {
                continue;
            }

            if(count($groupItems) > 0) {
                echo '<li class="has-dropdown">';
                    echo '<button type="button" class="navbar-dropdown-toggle">'.$groupTitle.' <i class="fa fa-angle-down"></i></button>';
                    echo '<ul class="navbar-dropdown-menu">';
                        foreach($groupItems as $groupItem) {
                            $target = $groupItem['raw']['newtab'] ? ' target="_blank"' : '';
                            echo '<li><a href="'.$groupItem['link'].'"'.$target.'>'.$groupItem['title'].'</a></li>';
                        }
                    echo '</ul>';
                echo '</li>';
                $groupRendered = true;
            }
        } else {
            if($element['newtab']) {
                echo '<li><a href="'.$item['link'].'" target="_blank">'.$item['title'].'</a></li>';
            } else {
                echo '<li><a href="'.$item['link'].'">'.$item['title'].'</a></li>';
            }
        }
    }
}

function templateBuildUsercp() {
    $cfg = loadConfig('usercp');
    if(!is_array($cfg)) return;

    echo '<ul class="usercp-list">';
    foreach($cfg as $element) {
        if(!is_array($element)) continue;
        if(!$element['active']) continue;

        $link = ($element['type'] == 'internal' ? __BASE_URL__ . $element['link'] : $element['link']);
        $title = (check_value(lang($element['phrase'], true)) ? lang($element['phrase'], true) : 'Unk_phrase');

        if($element['visibility'] == 'guest') if(isLoggedIn()) continue;
        if($element['visibility'] == 'user') if(!isLoggedIn()) continue;

        if($element['newtab']) {
            echo '<li><a href="'.$link.'" target="_blank">'.$title.'</a></li>';
        } else {
            echo '<li><a href="'.$link.'">'.$title.'</a></li>';
        }
    }
    echo '</ul>';
}

function templateLanguageSelector() {
    $langList = array(
        'en' => array('English', 'US'),
        'es' => array('Español', 'ES'),
        'ph' => array('Filipino', 'PH'),
        'br' => array('Português', 'BR'),
        'ro' => array('Romanian', 'RO'),
        'cn' => array('Simplified Chinese', 'CN'),
        'ru' => array('Russian', 'RU'),
        'lt' => array('Lithuanian', 'LT'),
    );

    if(isset($_SESSION['language_display'])) {
        $lang = $_SESSION['language_display'];
    } else {
        $lang = config('language_default', true);
    }

    echo '<div class="language-selector">';
    foreach($langList as $language => $languageInfo) {
        $activeClass = ($language == $lang) ? ' active' : '';
        echo '<a href="'.__BASE_URL__.'language/switch/to/'.strtolower($language).'" class="lang-option'.$activeClass.'" title="'.$languageInfo[0].'">'.strtoupper($language).'</a>';
    }
    echo '</div>';
}

function templateRecaptchaV2() {
    if(!config('recaptcha_v2_active', true)) return;
    $siteKey = config('recaptcha_v2_site_key', true);
    if(!check_value($siteKey)) return;
    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    echo '<div class="g-recaptcha" data-sitekey="'.$siteKey.'" data-theme="dark" style="margin:12px 0;"></div>';
}