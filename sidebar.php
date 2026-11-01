<aside class="layout__side" aria-label="Lateral">
	<div class="side-sticky">
		<?php fdb_cta_canais(); ?>
		<?php fdb_ad('lateral'); ?>
		<?php if (!is_front_page()): ?>
		<section class="box3d">
			<h3 class="box3d__title">Mais lidas</h3>
			<ol class="rank-mini">
				<?php global $post; foreach (fdb_mais_lidas(5) as $i => $post): setup_postdata($post); ?>
				<li><?php fdb_card('rank', $i + 1); ?></li>
				<?php endforeach; wp_reset_postdata(); ?>
			</ol>
		</section>
		<?php endif; ?>
	</div>
</aside>
