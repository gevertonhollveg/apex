<?php

try {
	
	// Module status
	if(!mconfig('active')) throw new Exception(lang('error_47',true));
	
	// News object
	$News = new News();
	$cachedNews = loadCache('news.cache');
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
		$news_title = base64_decode($newsArticle['news_title']);
		$news_author = $newsArticle['news_author'];
		$news_date = $newsArticle['news_date'];
		$news_url = __BASE_URL__.'news/'.$news_id.'/';
		$news_date_format = date("F j, Y", $news_date);
		
		// translated news title
		if(config('language_switch_active',true)) {
			if(isset($_SESSION['language_display']) && isset($newsArticle['translations']) && is_array($newsArticle['translations']) && array_key_exists($_SESSION['language_display'], $newsArticle['translations'])) {
				$news_title = base64_decode($newsArticle['translations'][$_SESSION['language_display']]);
			}
		}
		
		if(mconfig('news_short')) {
			if($showSingleNews) {
				$loadNewsCache = $News->LoadCachedNews();
			} else {
				$loadNewsCache = $News->LoadCachedNews(true);
				$loadNewsCache .= '<a href="'.$news_url.'" class="news-readmore">' . lang('news_txt_3') . '</a>';
			}
		} else {
			$loadNewsCache = $News->LoadCachedNews();
		}
		
		echo '<div class="panel panel-news">';
			echo '<div class="panel-heading">';
				echo '<h3 class="panel-title"><a href="'.$news_url.'">'.$news_title.'</a></h3>';
				echo '<div class="news-meta">';
					echo '<span><i class="fa-regular fa-user"></i> '.$news_author.'</span>';
					echo '<span><i class="fa-regular fa-calendar"></i> '.$news_date_format.'</span>';
				echo '</div>';
			echo '</div>';
			if(mconfig('news_expanded') > $i) {
				echo '<div class="panel-body">';
					echo '<div class="news-content">';
						echo $loadNewsCache;
					echo '</div>';
				echo '</div>';
				echo '<div class="panel-footer">';
					echo '<a href="'.$news_url.'" class="news-footer-link"><i class="fa-solid fa-book-open"></i> '.lang('news_txt_2').'</a>';
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