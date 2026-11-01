<?php get_header(); ?>
<?php while (have_posts()): the_post(); ?>
<div class="wrap layout layout--post wrap--first">
	<article <?php post_class('materia'); ?>>
		<header class="post__head"><h1 class="post__title"><?php the_title(); ?></h1></header>
		<div class="post__content"><?php the_content(); ?></div>
	</article>
	<?php get_sidebar(); ?>
</div>
<?php endwhile; ?>
<?php get_footer();
