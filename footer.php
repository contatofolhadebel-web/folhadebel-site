</main>

<div class="wrap"><?php fdb_ad('rodape'); ?></div>

<footer class="ft">
	<div class="ft__glow" aria-hidden="true"></div>
	<div class="wrap ft__grid">
		<div class="ft__brand">
			<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-branca.png'); ?>" alt="Folha de Belford Roxo" width="224" height="82" loading="lazy">
			<p>Notícias de Belford Roxo e da Baixada Fluminense, com apuração, contexto e o olhar de quem vive aqui.</p>
			<?php fdb_social('social social--ft'); ?>
		</div>
		<nav class="ft__col" aria-label="Editorias no rodapé">
			<h4>Editorias</h4>
			<ul>
				<?php foreach (fdb_menu_items() as $it): ?>
				<li><a href="<?php echo esc_url($it['u']); ?>"><?php echo esc_html($it['t']); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<div class="ft__col">
			<h4>Anuncie</h4>
			<p>Banners, publieditoriais e posts patrocinados no site e no Instagram.</p>
			<a class="btn3d" href="<?php echo esc_url(fdb_opt('fdb_anuncie')); ?>">Fale com a gente</a>
		</div>
	</div>
	<div class="wrap ft__base">
		<span>© <?php echo esc_html(wp_date('Y')); ?> Folha de Belford Roxo</span>
		<a href="#topo">Voltar ao topo ↑</a>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
