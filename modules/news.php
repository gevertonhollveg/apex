<?php

try {
	
	// Module status
	if(!mconfig('active')) throw new Exception(lang('error_47',true));
	
	// News object
	$News = new News();
	$cachedNews = loadCache('news.cache');
	if(!is_array($cachedNews) || empty($cachedNews)) {
		$News->updateNewsCacheIndex();
		$cachedNews = loadCache('news.cache');
	}
	if(!is_array($cachedNews)) throw new Exception(lang('error_61'));
	
	// Set news language
	if(config('language_switch_active',true)) {
		if(isset($_SESSION['language_display'])) {
			$News->setLanguage($_SESSION['language_display']);
		}
	}
	
	// Single news
	$requestedNewsId = isset($_GET['subpage']) ? $_GET['subpage'] : '';
	$showSingleNews = false;
	if(check_value($requestedNewsId) && $News->newsIdExists($requestedNewsId)) {
		$showSingleNews = true;
		$newsID = $requestedNewsId;
	}

	// Pagination setup
	$newsPerPage = max(1, (int)mconfig('news_list_limit'));
	$totalNews = count($cachedNews);
	$totalPages = max(1, (int)ceil($totalNews / $newsPerPage));
	$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
	$newsToDisplay = $cachedNews;
	if(!$showSingleNews) {
		$startIndex = ($currentPage - 1) * $newsPerPage;
		$newsToDisplay = array_slice($cachedNews, $startIndex, $newsPerPage);
	}
	
	// News list
	$i = 0;
	foreach($newsToDisplay as $newsArticle) {
		if($showSingleNews) if($newsArticle['news_id'] != $newsID) continue;
		$News->setId($newsArticle['news_id']);
		$news_id = $newsArticle['news_id'];
		$news_title = $newsArticle['news_title'];
		$news_author = $newsArticle['news_author'];
		$news_date = $newsArticle['news_date'];
		$news_url = __BASE_URL__.'news/'.$news_id.'/';
		$news_date_format = date("F j, Y", $news_date);

		// Backward compatibility: decode only if value is still base64.
		$decodedTitle = base64_decode($news_title, true);
		if($decodedTitle !== false && base64_encode($decodedTitle) === $news_title) {
			$news_title = $decodedTitle;
		}

		// translated news title
		if(config('language_switch_active',true)) {
			if(isset($_SESSION['language_display']) && isset($newsArticle['translations']) && is_array($newsArticle['translations']) && array_key_exists($_SESSION['language_display'], $newsArticle['translations'])) {
				$translatedTitle = $newsArticle['translations'][$_SESSION['language_display']];
				$decodedTranslatedTitle = base64_decode($translatedTitle, true);
				if($decodedTranslatedTitle !== false && base64_encode($decodedTranslatedTitle) === $translatedTitle) {
					$translatedTitle = $decodedTranslatedTitle;
				}
				$news_title = $translatedTitle;
			}
		}

		$news_title = htmlspecialchars((string)$news_title, ENT_QUOTES, 'UTF-8');

		// Sempre carrega o resumo
		$news_summary = $News->LoadCachedNews(true);
		$news_full = $News->LoadCachedNews();

		echo '<div class="panel panel-news">';
		echo '<div class="panel-heading">';
		echo '<h3 class="panel-title"><a href="'.$news_url.'">'.$news_title.'</a></h3>';
		echo '<div class="news-meta">';
		echo '<span><i class="fa-regular fa-user"></i> '.$news_author.'</span>';
		echo '<span><i class="fa-regular fa-calendar"></i> '.$news_date_format.'</span>';
		echo '</div>';
		echo '</div>';

		$shortNewsEnabled = (bool)mconfig('news_short');

		if($showSingleNews) {
			echo '<div class="panel-body">';
			echo '<div class="news-content">'.$news_full.'</div>';
			echo '</div>';
		} else if(!$shortNewsEnabled && mconfig('news_expanded') > $i) {
			echo '<div class="panel-body">';
			echo '<div class="news-content">'.$news_full.'</div>';
			echo '</div>';
			echo '<div class="panel-footer">';
			echo '<a href="'.$news_url.'" class="news-footer-link"><i class="fa-solid fa-book-open"></i> '.lang('news_txt_2').'</a>';
			echo '</div>';
		} else {
			echo '<div class="panel-body">';
			echo '<div class="news-content">'.$news_summary.'<a href="'.$news_url.'" class="news-readmore">' . lang('news_txt_3') . '</a></div>';
			echo '</div>';
		}

		echo '</div>';
		$i++;
	}

	// Pagination controls
	if(!$showSingleNews && $totalPages > 1) {
		echo '<div class="news-pagination">';
		for($i = 1; $i <= $totalPages; $i++) {
			$activeClass = $i === $currentPage ? 'active' : '';
			echo '<a href="'.__BASE_URL__.'news/?page='.$i.'" class="pagination-link '.$activeClass.'">'.$i.'</a>';
		}
		echo '</div>';
	}

} catch(Exception $ex) {
	message('warning', $ex->getMessage());
}