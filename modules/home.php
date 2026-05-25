<?php
/**
 * WebEngine CMS
 * https://webenginecms.org/
 * 
 * @version 1.2.6-dvteam
 * @author Lautaro Angelico <http://lautaroangelico.com/>
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * 
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */
?>
<div class="row">
	<div class="col-xs-12 home-news-showcase">
		<div class="home-news-showcase-header">
			<h2><?php echo lang('news_txt_4'); ?></h2>
			<a href="<?php echo __BASE_URL__ . 'news/'; ?>" class="home-news-show-all"><?php echo lang('news_txt_5'); ?></a>
		</div>

		<?php
		$News = new News();
		$newsList = loadCache('news.cache');
		if(!is_array($newsList) || empty($newsList)) {
			$News->updateNewsCacheIndex();
			$newsList = loadCache('news.cache');
		}

		$validNews = array();
		if(is_array($newsList)) {
			foreach($newsList as $newsArticle) {
				if(!isset($newsArticle['news_id'], $newsArticle['news_title'], $newsArticle['news_date'])) {
					continue;
				}
				$validNews[] = $newsArticle;
				if(count($validNews) >= 6) {
					break;
				}
			}
		}

		if(!empty($validNews)) {
			$featuredNews = $validNews[0];
			$News->setId($featuredNews['news_id']);

			$featuredTitle = $featuredNews['news_title'];
			$decodedFeaturedTitle = base64_decode($featuredTitle, true);
			if($decodedFeaturedTitle !== false && base64_encode($decodedFeaturedTitle) === $featuredTitle) {
				$featuredTitle = $decodedFeaturedTitle;
			}

			if(config('language_switch_active', true)) {
				if(isset($_SESSION['language_display']) && isset($featuredNews['translations']) && is_array($featuredNews['translations']) && array_key_exists($_SESSION['language_display'], $featuredNews['translations'])) {
					$featuredTranslatedTitle = $featuredNews['translations'][$_SESSION['language_display']];
					$decodedFeaturedTranslated = base64_decode($featuredTranslatedTitle, true);
					if($decodedFeaturedTranslated !== false && base64_encode($decodedFeaturedTranslated) === $featuredTranslatedTitle) {
						$featuredTranslatedTitle = $decodedFeaturedTranslated;
					}
					$featuredTitle = $featuredTranslatedTitle;
				}
			}

			$featuredUrl = __BASE_URL__.'news/'.$featuredNews['news_id'].'/';
			$featuredBody = $News->LoadCachedNews(true);
			$featuredBody = str_replace('__', '', $featuredBody);
			$featuredTitleEscaped = htmlspecialchars((string)$featuredTitle, ENT_QUOTES, 'UTF-8');
			$featuredAuthor = isset($featuredNews['news_author']) ? htmlspecialchars((string)$featuredNews['news_author'], ENT_QUOTES, 'UTF-8') : '-';

			$newsTag = 'news';
			$combinedText = strtolower($featuredTitle . ' ' . strip_tags((string)$featuredBody));
			if(strpos($combinedText, 'maint') !== false || strpos($combinedText, 'manuten') !== false) {
				$newsTag = 'maintenance';
			} elseif(strpos($combinedText, 'alert') !== false || strpos($combinedText, 'aviso') !== false) {
				$newsTag = 'alert';
			} elseif(strpos($combinedText, 'event') !== false || strpos($combinedText, 'evento') !== false) {
				$newsTag = 'event';
			}

			echo '<article class="panel panel-news panel-news-featured">';
				echo '<div class="panel-heading">';
					echo '<span class="home-news-tag home-news-tag-'.$newsTag.'">'.strtoupper($newsTag).'</span>';
					echo '<h3 class="panel-title"><a href="'.$featuredUrl.'">'.$featuredTitleEscaped.'</a></h3>';
				echo '</div>';
				echo '<div class="panel-body">';
					echo '<div class="news-content">'.$featuredBody.'</div>';
					echo '<a href="'.$featuredUrl.'" class="news-readmore">'.lang('news_txt_3').'</a>';
				echo '</div>';
				echo '<div class="panel-footer">';
					echo '<div class="home-news-featured-meta">';
						echo '<span class="meta-author"><i class="fa-regular fa-user"></i> '.$featuredAuthor.'</span>';
						echo '<span class="meta-date"><i class="fa-regular fa-calendar"></i> '.date('Y/m/d H:i', (int)$featuredNews['news_date']).'</span>';
					echo '</div>';
				echo '</div>';
			echo '</article>';

			$remainingNews = array_slice($validNews, 1, 5);
			if(!empty($remainingNews)) {
				echo '<div class="home-news-quicklist">';
					echo '<h4 class="home-news-quicklist-title">'.lang('news_txt_6').'</h4>';
					echo '<div class="home-news-quicklist-items">';
						foreach($remainingNews as $newsArticle) {
							$newsTitle = $newsArticle['news_title'];
							$decodedTitle = base64_decode($newsTitle, true);
							if($decodedTitle !== false && base64_encode($decodedTitle) === $newsTitle) {
								$newsTitle = $decodedTitle;
							}

							if(config('language_switch_active', true)) {
								if(isset($_SESSION['language_display']) && isset($newsArticle['translations']) && is_array($newsArticle['translations']) && array_key_exists($_SESSION['language_display'], $newsArticle['translations'])) {
									$translatedTitle = $newsArticle['translations'][$_SESSION['language_display']];
									$decodedTranslatedTitle = base64_decode($translatedTitle, true);
									if($decodedTranslatedTitle !== false && base64_encode($decodedTranslatedTitle) === $translatedTitle) {
										$translatedTitle = $decodedTranslatedTitle;
									}
									$newsTitle = $translatedTitle;
								}
							}

							$newsUrl = __BASE_URL__.'news/'.$newsArticle['news_id'].'/';
							$newsTitle = htmlspecialchars((string)$newsTitle, ENT_QUOTES, 'UTF-8');

							echo '<div class="home-news-quickitem">';
								echo '<a href="'.$newsUrl.'" class="home-news-quickitem-title">'.$newsTitle.'</a>';
								echo '<span class="home-news-quickitem-date">'.date('Y/m/d', (int)$newsArticle['news_date']).'</span>';
							echo '</div>';
						}
					echo '</div>';
				echo '</div>';
			}
		} else {
			echo '<div class="home-news-block-empty">'.lang('error_61').'</div>';
		}
		?>
	</div>
</div>

<div class="row home-ranking-row">
	<div class="col-xs-12">
		<?php
		$rankingsDisplayFile = __PATH_TEMPLATE_ROOT__ . 'inc/modules/rankings-display.php';
		if(file_exists($rankingsDisplayFile)) {
			echo '<div class="panel panel-sidebar panel-home-ranking">';
				echo '<div class="panel-heading">';
					echo '<h3 class="panel-title">'.lang('module_titles_txt_10', true).'<a href="'.__BASE_URL__.'rankings" class="news-ranking-more">+ '.lang('menu_txt_10', true).'</a></h3>';
				echo '</div>';
				echo '<div class="panel-body">';
					echo '<div class="home-ranking-podium-wrap">';
						include $rankingsDisplayFile;
					echo '</div>';
				echo '</div>';
			echo '</div>';
		}
		?>
	</div>
</div>