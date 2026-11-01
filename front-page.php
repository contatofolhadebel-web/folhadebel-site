<?php
get_header();

$usados = array();
$pega = function ($args) use (&$usados) {
	$args = wp_parse_args($args, array('post__not_in' => $usados, 'ignore_sticky_posts' => true));
	$posts = get_posts($args);
	$usados = array_merge($usados, wp_list_pluck($posts, 'ID'));
	return $posts;
};

$manchete = $pega(array('numberposts' => 1));
$lista    = $pega(array('numberposts' => 4));
$col_slug = fdb_opt('fdb_coluna_cat');
$col_cat  = $col_slug ? get_category_by_slug($col_slug) : null;
$coluna   = $col_cat ? get_posts(array('numberposts' => 1, 'cat' => $col_cat->term_id)) : array();
?>

<div class="wrap wrap--first"><?php fdb_ad('topo'); ?></div>

<section class="wrap sec sec--destaques">
	<?php fdb_section_head('Destaques'); ?>
	<div class="dest">
		<?php if ($coluna): $p = $coluna[0]; ?>
		<a class="coluna tilt" href="<?php echo esc_url(get_permalink($p)); ?>">
			<span class="coluna__bg"><?php echo get_the_post_thumbnail($p, 'fdb-card', array('alt' => '', 'loading' => 'lazy')); ?></span>
			<span class="coluna__layer"></span>
			<span class="coluna__k">Coluna</span>
			<strong class="coluna__name"><?php echo esc_html($col_cat->name); ?></strong>
			<span class="coluna__t"><?php echo esc_html(get_the_title($p)); ?></span>
			<span class="btn3d btn3d--sm">Ler a coluna</span>
		</a>
		<?php endif; ?>

		<div class="dest__lead">
			<?php global $post; foreach ($manchete as $post): setup_postdata($post); fdb_card('lead'); endforeach; wp_reset_postdata(); ?>
		</div>

		<div class="dest__side">
			<?php foreach ($lista as $post): setup_postdata($post); fdb_card('side'); endforeach; wp_reset_postdata(); ?>
		</div>
	</div>
</section>

<div class="wrap"><?php fdb_ad('meio'); ?></div>

<div class="wrap layout">
	<div class="layout__main">
		<section class="sec">
			<?php $pfp = (int) get_option('page_for_posts'); fdb_section_head('Últimas', $pfp ? get_permalink($pfp) : '', 'Agora'); ?>
			<?php $ult = $pega(array('numberposts' => 7)); ?>
			<div class="ult">
				<?php foreach ($ult as $i => $post): setup_postdata($post); fdb_card($i === 0 ? 'over' : 'grid'); endforeach; wp_reset_postdata(); ?>
			</div>
		</section>

		<?php
		$cats = get_categories(array('orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true, 'number' => 4));
		foreach ($cats as $c):
			$ps = get_posts(array('numberposts' => 4, 'cat' => $c->term_id, 'ignore_sticky_posts' => true));
			if (count($ps) < 2) continue;
		?>
		<section class="sec sec--ed" data-cat="<?php echo esc_attr($c->slug); ?>">
			<?php fdb_section_head($c->name, get_category_link($c), 'Editoria'); ?>
			<div class="ed">
				<?php foreach ($ps as $i => $post): setup_postdata($post); fdb_card($i === 0 ? 'grid' : 'side'); endforeach; wp_reset_postdata(); ?>
			</div>
		</section>
		<?php endforeach; ?>
	</div>

	<?php get_sidebar(); ?>
</div>

<section class="lidas">
	<div class="wrap">
		<?php fdb_section_head('Mais lidas', '', 'Da semana'); ?>
		<ol class="lidas__list">
			<?php foreach (fdb_mais_lidas(5) as $i => $post): setup_postdata($post); ?>
			<li><?php fdb_card('rank', $i + 1); ?></li>
			<?php endforeach; wp_reset_postdata(); ?>
		</ol>
	</div>
</section>

<?php get_footer();
