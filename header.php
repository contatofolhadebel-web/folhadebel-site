<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0b2a7a">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="sr" href="#conteudo">Pular para o conteúdo</a>

<?php $ultimas = get_posts(array('numberposts' => 10)); if ($ultimas): ?>
<div class="ticker" role="region" aria-label="Últimas notícias">
	<span class="ticker__tag"><i class="pulse"></i>Últimas</span>
	<div class="ticker__viewport">
		<ul class="ticker__track">
			<?php for ($r = 0; $r < 2; $r++): foreach ($ultimas as $u): ?>
			<li<?php echo $r ? ' aria-hidden="true"' : ''; ?>><a href="<?php echo esc_url(get_permalink($u)); ?>"<?php echo $r ? ' tabindex="-1"' : ''; ?>><?php echo esc_html(get_the_title($u)); ?></a></li>
			<?php endforeach; endfor; ?>
		</ul>
	</div>
</div>
<?php endif; ?>

<header class="hd" id="topo">
	<div class="wrap hd__bar">
		<button class="burger" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button>
		<a class="logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Folha de Belford Roxo — página inicial">
			<?php if (has_custom_logo()) {
				$id = get_theme_mod('custom_logo');
				echo wp_get_attachment_image($id, 'full', false, array('class' => 'logo__img', 'alt' => 'Folha de Belford Roxo'));
			} else { ?>
				<img class="logo__img" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-branca.png'); ?>" alt="Folha de Belford Roxo" width="447" height="164">
			<?php } ?>
		</a>

		<nav class="menu" id="menu" aria-label="Editorias">
			<ul>
				<li<?php echo is_front_page() ? ' class="on"' : ''; ?>><a href="<?php echo esc_url(home_url('/')); ?>">Início</a></li>
				<?php foreach (fdb_menu_items() as $it): ?>
				<li<?php echo $it['on'] ? ' class="on"' : ''; ?>>
					<a href="<?php echo esc_url($it['u']); ?>"><?php echo esc_html($it['t']); ?></a>
					<?php $sub = get_posts(array('numberposts' => 3, 'cat' => $it['cat']->term_id)); if ($sub): ?>
					<div class="mega" role="group" aria-label="<?php echo esc_attr($it['t']); ?>">
						<?php foreach ($sub as $s): ?>
						<a class="mega__item" href="<?php echo esc_url(get_permalink($s)); ?>">
							<span class="mega__img"><?php echo get_the_post_thumbnail($s, 'fdb-thumb', array('loading' => 'lazy', 'alt' => '')); ?></span>
							<span class="mega__t"><?php echo esc_html(get_the_title($s)); ?></span>
						</a>
						<?php endforeach; ?>
						<a class="mega__all" href="<?php echo esc_url($it['u']); ?>">Tudo em <?php echo esc_html($it['t']); ?> →</a>
					</div>
					<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="hd__tools">
			<?php fdb_social('social social--hd'); ?>
			<button class="btn-ico" type="button" data-search aria-label="Buscar"><?php echo fdb_icon('search'); ?></button>
			<button class="btn-ico" type="button" data-theme-toggle aria-label="Alternar tema escuro"><?php echo fdb_icon('moon'); ?></button>
		</div>
	</div>

	<form class="search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" hidden>
		<div class="wrap">
			<label class="sr" for="busca">Buscar no site</label>
			<input id="busca" type="search" name="s" placeholder="O que você procura?" value="<?php echo esc_attr(get_search_query()); ?>">
			<button class="btn3d" type="submit">Buscar</button>
		</div>
	</form>
</header>

<main id="conteudo">
