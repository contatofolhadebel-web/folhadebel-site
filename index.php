<?php
/* Listagens: categorias, busca, autor, arquivo e a página de posts. */
get_header();

if (is_search()) {
	$titulo = 'Busca: ' . get_search_query();
	$kicker = 'Resultados';
} elseif (is_category()) {
	$titulo = single_cat_title('', false);
	$kicker = 'Editoria';
} elseif (is_archive()) {
	$titulo = wp_strip_all_tags(get_the_archive_title());
	$kicker = 'Arquivo';
} else {
	$titulo = 'Últimas notícias';
	$kicker = 'Agora';
}
?>

<div class="wrap wrap--first"><?php fdb_ad('topo'); ?></div>

<div class="wrap layout">
	<div class="layout__main">
		<section class="sec">
			<?php fdb_section_head($titulo, '', $kicker); ?>
			<?php if (is_category() && category_description()): ?><div class="sec__desc"><?php echo wp_kses_post(category_description()); ?></div><?php endif; ?>

			<?php if (have_posts()): ?>
			<div class="ult">
				<?php $i = 0; while (have_posts()): the_post(); fdb_card($i === 0 && !is_paged() ? 'over' : 'grid'); $i++; endwhile; ?>
			</div>
			<nav class="pager" aria-label="Paginação">
				<?php echo paginate_links(array('prev_text' => '← Anteriores', 'next_text' => 'Próximas →')); ?>
			</nav>
			<?php else: ?>
			<div class="vazio box3d">
				<p>Nada encontrado por aqui. Tente outra busca:</p>
				<?php get_search_form(); ?>
			</div>
			<?php endif; ?>
		</section>
	</div>
	<?php get_sidebar(); ?>
</div>

<?php get_footer();
