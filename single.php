<?php get_header(); ?>

<?php while (have_posts()): the_post(); $cat = fdb_cat(); ?>
<div class="wrap wrap--first"><?php fdb_ad('topo'); ?></div>

<div class="wrap layout layout--post">
	<article <?php post_class('post'); ?>>
		<header class="post__head">
			<?php fdb_tag(0, 'tag3d--lg'); ?>
			<h1 class="post__title"><?php the_title(); ?></h1>
			<?php if (has_excerpt()): ?><p class="post__sub"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
			<div class="post__meta">
				<span>Por <b><?php the_author(); ?></b></span>
				<time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('d/m/Y \à\s H\hi')); ?></time>
				<span><?php echo esc_html(fdb_reading_time()); ?></span>
			</div>
			<?php fdb_share(); ?>
		</header>

		<?php if (has_post_thumbnail()): ?>
		<figure class="post__hero tilt tilt--soft">
			<?php the_post_thumbnail('fdb-lead'); ?>
			<?php $cap = get_the_post_thumbnail_caption(); if ($cap): ?><figcaption><?php echo esc_html($cap); ?></figcaption><?php endif; ?>
		</figure>
		<?php endif; ?>

		<div class="post__content">
			<?php the_content(); ?>
		</div>

		<footer class="post__foot">
			<?php fdb_share(); ?>
			<div class="cta-line">
				<?php echo fdb_icon('whatsapp'); ?>
				<p>Receba as notícias da Folha no seu WhatsApp.</p>
				<a class="btn3d btn3d--zap" href="<?php echo esc_url(fdb_opt('fdb_whatsapp') ?: fdb_opt('fdb_instagram')); ?>" target="_blank" rel="noopener">Entrar no canal</a>
			</div>
		</footer>
	</article>

	<?php get_sidebar(); ?>
</div>

<?php
$rel = get_posts(array('numberposts' => 4, 'post__not_in' => array(get_the_ID()), 'cat' => $cat ? $cat->term_id : 0, 'ignore_sticky_posts' => true));
if (count($rel) < 4) $rel = array_merge($rel, get_posts(array('numberposts' => 4 - count($rel), 'post__not_in' => array_merge(array(get_the_ID()), wp_list_pluck($rel, 'ID')))));
if ($rel): ?>
<section class="wrap sec">
	<?php fdb_section_head('Leia também'); ?>
	<div class="grid4">
		<?php global $post; foreach ($rel as $post): setup_postdata($post); fdb_card('grid'); endforeach; wp_reset_postdata(); ?>
	</div>
</section>
<?php endif; ?>

<?php endwhile; ?>

<?php get_footer();
