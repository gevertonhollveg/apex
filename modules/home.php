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
	<div class="col-xs-12 home-news-block">
		<div class="row home-news-block-header">
			<div class="col-xs-8">
				<h2><?php echo lang('news_txt_4'); ?></h2>
			</div>
			<div class="col-xs-4 text-right">
				<a href="<?php echo __BASE_URL__ . 'news/'; ?>"><?php echo lang('news_txt_5'); ?></a>
			</div>
		</div>
		<div class="row home-news-block-body">
			<div class="col-xs-12">
				<?php
					$News = new News();
					$newsList = loadCache('news.cache');
					if(!is_array($newsList) || empty($newsList)) {
						$News->updateNewsCacheIndex();
						$newsList = loadCache('news.cache');
					}
					$newsCount = 0;

					if(is_array($newsList)) {
						foreach($newsList as $key => $newsArticle) {
							if($key >= 7) break;
							if(!isset($newsArticle['news_id'], $newsArticle['news_title'], $newsArticle['news_date'])) continue;

							$newsCount++;
							$news_title = $newsArticle['news_title'];

							// Backward compatibility: decode only if cache value is still base64.
							$decodedTitle = base64_decode($news_title, true);
							if($decodedTitle !== false && base64_encode($decodedTitle) === $news_title) {
								$news_title = $decodedTitle;
							}

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

							$news_url = __BASE_URL__.'news/'.$newsArticle['news_id'].'/';
							$news_title = htmlspecialchars((string)$news_title, ENT_QUOTES, 'UTF-8');

							echo '<div class="row home-news-block-article">';
								echo '<div class="col-xs-12 col-sm-2">';
									echo '<span class="home-news-block-article-type">'.lang('news_txt_6').'</span>';
								echo '</div>';
								echo '<div class="col-xs-12 col-sm-7 home-news-block-article-title-container">';
									echo '<span class="home-news-block-article-title"><a href="'.$news_url.'">'.$news_title.'</a></span>';
								echo '</div>';
								echo '<div class="col-xs-12 col-sm-3 text-right home-news-block-article-date-col">';
									echo '<span class="home-news-block-article-date">'.date("Y/m/d", (int)$newsArticle['news_date']).'</span>';
								echo '</div>';
							echo '</div>';
						}
					}

					if($newsCount === 0) {
						echo '<div class="home-news-block-empty">'.lang('error_61').'</div>';
					}
				?>
			</div>
		</div>
	</div>
</div>

<div class="row home-ranking-row">
	<div class="col-xs-12">
		<?php
		$rankingsDisplayFile = __PATH_TEMPLATE_ROOT__ . 'inc/modules/rankings-display.php';
		if(file_exists($rankingsDisplayFile)) {
			echo '<div class="panel panel-sidebar panel-home-ranking">';
				echo '<div class="panel-heading">';
					echo '<h3 class="panel-title">'.lang('module_titles_txt_10', true).'</h3>';
				echo '</div>';
				echo '<div class="panel-body">';
					include $rankingsDisplayFile;
				echo '</div>';
			echo '</div>';
		}
		?>
	</div>
</div>