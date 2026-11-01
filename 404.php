<?php get_header(); ?>
<section class="wrap sec wrap--first nao-achei">
	<span class="nao-achei__n" aria-hidden="true">404</span>
	<h1 class="post__title">Essa página saiu de circulação</h1>
	<p>O link pode ter mudado. Volte para a capa ou procure a notícia:</p>
	<?php get_search_form(); ?>
	<p><a class="btn3d" href="<?php echo esc_url(home_url('/')); ?>">Ir para a capa</a></p>
</section>
<?php get_footer();
