<?php
/**
 * Folha de Bel 3D — funções do tema.
 */
if (!defined('ABSPATH')) exit;

define('FDB_VER', '3.0.0');

/* ---------- Configuração básica ---------- */
function fdb_setup() {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('responsive-embeds');
	add_theme_support('automatic-feed-links');
	add_theme_support('custom-logo', array('height' => 120, 'width' => 520, 'flex-height' => true, 'flex-width' => true));
	add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
	register_nav_menus(array('primary' => 'Menu principal', 'footer' => 'Menu do rodapé'));
	add_image_size('fdb-lead', 1280, 800, true);
	add_image_size('fdb-card', 760, 480, true);
	add_image_size('fdb-thumb', 240, 240, true);
}
add_action('after_setup_theme', 'fdb_setup');

function fdb_assets() {
	wp_enqueue_style('fdb-fonts', 'https://fonts.googleapis.com/css2?family=Saira:wght@400;500;600;700;800;900&family=Saira+Condensed:wght@600;700;800;900&display=swap', array(), null);
	wp_enqueue_style('fdb-style', get_stylesheet_uri(), array('fdb-fonts'), FDB_VER . '.' . filemtime(get_template_directory() . '/style.css'));
	wp_enqueue_script('fdb-main', get_template_directory_uri() . '/assets/js/main.js', array(), FDB_VER . '.' . filemtime(get_template_directory() . '/assets/js/main.js'), true);
	wp_localize_script('fdb-main', 'FDB', array(
		'rest'   => esc_url_raw(rest_url('fdb/v1/view/')),
		'postId' => is_single() ? get_the_ID() : 0,
	));
}
add_action('wp_enqueue_scripts', 'fdb_assets');

/* Tema escuro sem "piscar": aplica a preferência salva antes de pintar a página */
function fdb_theme_boot() {
	echo "<script>try{var t=localStorage.getItem('fdb-tema');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>\n";
}
add_action('wp_head', 'fdb_theme_boot', 1);

/* ---------- Opções no Personalizar (links das redes, canal do Zap) ---------- */
function fdb_customize($wp) {
	$wp->add_section('fdb_redes', array('title' => 'Folha de Bel: redes e contato', 'priority' => 30));
	$campos = array(
		'fdb_instagram' => array('Instagram (URL)', 'https://www.instagram.com/folhadebel/'),
		'fdb_whatsapp'  => array('Canal do WhatsApp (URL)', ''),
		'fdb_facebook'  => array('Facebook (URL)', ''),
		'fdb_tiktok'    => array('TikTok (URL)', ''),
		'fdb_youtube'   => array('YouTube (URL)', ''),
		'fdb_anuncie'   => array('Link do "Anuncie aqui" (WhatsApp comercial ou e-mail)', 'mailto:contatofolhadebel@gmail.com'),
	);
	foreach ($campos as $id => $c) {
		$wp->add_setting($id, array('default' => $c[1], 'sanitize_callback' => 'esc_url_raw'));
		$wp->add_control($id, array('label' => $c[0], 'section' => 'fdb_redes', 'type' => 'url'));
	}
	$wp->add_setting('fdb_coluna_cat', array('default' => 'politica', 'sanitize_callback' => 'sanitize_title'));
	$wp->add_control('fdb_coluna_cat', array('label' => 'Slug da categoria da coluna em destaque (ex.: de-olho-em-28)', 'section' => 'fdb_redes', 'type' => 'text'));
}
add_action('customize_register', 'fdb_customize');

function fdb_opt($id) {
	$defaults = array('fdb_instagram' => 'https://www.instagram.com/folhadebel/', 'fdb_anuncie' => 'mailto:contatofolhadebel@gmail.com', 'fdb_coluna_cat' => 'politica');
	return get_theme_mod($id, isset($defaults[$id]) ? $defaults[$id] : '');
}

/* ---------- Anúncios: um tipo de post próprio ----------
 * Cada anúncio = título + imagem destacada + campos "posicao" (topo | meio | lateral | rodape)
 * e "link". Vários anúncios na mesma posição giram sozinhos.
 * Sem anúncio cadastrado, aparece o espaço "Anuncie aqui".
 */
function fdb_cpt_anuncio() {
	register_post_type('fdb_anuncio', array(
		'labels' => array('name' => 'Anúncios', 'singular_name' => 'Anúncio', 'add_new_item' => 'Novo anúncio', 'edit_item' => 'Editar anúncio'),
		'public' => false, 'show_ui' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-megaphone', 'menu_position' => 6,
		'supports' => array('title', 'thumbnail', 'custom-fields'),
	));
	foreach (array('posicao', 'link') as $k) {
		register_post_meta('fdb_anuncio', $k, array('type' => 'string', 'single' => true, 'show_in_rest' => true));
	}
}
add_action('init', 'fdb_cpt_anuncio');

function fdb_anuncio_metabox() {
	add_meta_box('fdb_anuncio_box', 'Onde e para onde', function ($post) {
		$pos = get_post_meta($post->ID, 'posicao', true);
		$lnk = get_post_meta($post->ID, 'link', true);
		wp_nonce_field('fdb_anuncio', 'fdb_anuncio_nonce');
		echo '<p><label>Posição<br><select name="fdb_posicao">';
		foreach (array('topo' => 'Topo (banner largo)', 'meio' => 'Meio da página (banner largo)', 'lateral' => 'Lateral (vertical)', 'rodape' => 'Rodapé (banner largo)') as $v => $l) {
			printf('<option value="%s"%s>%s</option>', esc_attr($v), selected($pos, $v, false), esc_html($l));
		}
		echo '</select></label></p><p><label>Link ao clicar<br><input type="url" style="width:100%" name="fdb_link" value="' . esc_attr($lnk) . '"></label></p>';
		echo '<p style="color:#666">Tamanhos ideais: largo 1200×300 px · lateral 300×600 px. A imagem vai em "Imagem destacada".</p>';
	}, 'fdb_anuncio', 'side');
}
add_action('add_meta_boxes', 'fdb_anuncio_metabox');

function fdb_anuncio_save($id) {
	if (!isset($_POST['fdb_anuncio_nonce']) || !wp_verify_nonce($_POST['fdb_anuncio_nonce'], 'fdb_anuncio')) return;
	if (!current_user_can('edit_post', $id)) return;
	if (isset($_POST['fdb_posicao'])) update_post_meta($id, 'posicao', sanitize_key($_POST['fdb_posicao']));
	if (isset($_POST['fdb_link'])) update_post_meta($id, 'link', esc_url_raw($_POST['fdb_link']));
}
add_action('save_post_fdb_anuncio', 'fdb_anuncio_save');

function fdb_ad($pos) {
	$ads = get_posts(array('post_type' => 'fdb_anuncio', 'numberposts' => 6, 'meta_key' => 'posicao', 'meta_value' => $pos, 'orderby' => 'menu_order date'));
	$tipo = $pos === 'lateral' ? 'vert' : 'wide';
	echo '<aside class="ad ad--' . $tipo . '" aria-label="Publicidade"><span class="ad__label">Publicidade</span><div class="ad__stage" data-rotate>';
	$n = 0;
	foreach ($ads as $a) {
		if (!has_post_thumbnail($a)) continue;
		$link = get_post_meta($a->ID, 'link', true);
		$img  = get_the_post_thumbnail($a, 'full', array('alt' => esc_attr(get_the_title($a)), 'loading' => 'lazy'));
		echo '<div class="ad__slide' . ($n === 0 ? ' is-on' : '') . '">';
		echo $link ? '<a href="' . esc_url($link) . '" target="_blank" rel="sponsored noopener">' . $img . '</a>' : $img;
		echo '</div>';
		$n++;
	}
	if ($n === 0) fdb_ad_placeholder($tipo);
	echo '</div></aside>';
}

function fdb_ad_placeholder($tipo) {
	$nome = $tipo === 'vert' ? 'lateral' : 'largo';
	$n    = $tipo === 'vert' ? 2 : 3;
	$base = get_template_directory_uri() . '/assets/img/anuncie-' . $nome . '-';
	for ($i = 1; $i <= $n; $i++) {
		echo '<div class="ad__slide' . ($i === 1 ? ' is-on' : '') . '"><a href="' . esc_url(fdb_opt('fdb_anuncie')) . '" aria-label="Anuncie na Folha de Belford Roxo">';
		echo '<img src="' . esc_url($base . $i . '.jpg') . '" alt="Anuncie aqui na Folha de Belford Roxo" loading="lazy" width="' . ($tipo === 'vert' ? 600 : 2400) . '" height="' . ($tipo === 'vert' ? 1200 : 600) . '">';
		echo '</a></div>';
	}
}

/* ---------- Mais lidas: contador simples, funciona mesmo com cache ---------- */
function fdb_rest_views() {
	register_rest_route('fdb/v1', '/view/(?P<id>\d+)', array(
		'methods' => 'POST', 'permission_callback' => '__return_true',
		'callback' => function ($r) {
			$id = (int) $r['id'];
			if (get_post_type($id) !== 'post' || get_post_status($id) !== 'publish') return new WP_REST_Response(null, 204);
			$total = (int) get_post_meta($id, 'fdb_views', true) + 1;
			update_post_meta($id, 'fdb_views', $total);
			$sem = get_post_meta($id, 'fdb_views_semana', true);
			$k = gmdate('oW');
			$sem = (is_array($sem) && isset($sem['k']) && $sem['k'] === $k) ? $sem : array('k' => $k, 'n' => 0);
			$sem['n']++;
			update_post_meta($id, 'fdb_views_semana', $sem);
			update_post_meta($id, 'fdb_views_rank', $k . str_pad($sem['n'], 7, '0', STR_PAD_LEFT));
			return new WP_REST_Response(null, 204);
		},
	));
}
add_action('rest_api_init', 'fdb_rest_views');

function fdb_mais_lidas($n = 5) {
	$q = get_posts(array('numberposts' => $n, 'meta_key' => 'fdb_views_rank', 'orderby' => 'meta_value', 'order' => 'DESC', 'date_query' => array(array('after' => '30 days ago'))));
	if (count($q) < $n) {
		$ids = wp_list_pluck($q, 'ID');
		$q = array_merge($q, get_posts(array('numberposts' => $n - count($q), 'post__not_in' => $ids, 'meta_key' => 'fdb_views', 'orderby' => 'meta_value_num date', 'order' => 'DESC')));
	}
	if (count($q) < $n) {
		$ids = wp_list_pluck($q, 'ID');
		$q = array_merge($q, get_posts(array('numberposts' => $n - count($q), 'post__not_in' => $ids)));
	}
	return $q;
}

/* ---------- Ajudantes de exibição ---------- */
function fdb_cat($id = 0) { $c = get_the_category($id); return !empty($c) ? $c[0] : null; }

function fdb_tag($id = 0, $class = '') {
	$c = fdb_cat($id);
	if (!$c) return;
	echo '<a class="tag3d ' . esc_attr($class) . '" data-cat="' . esc_attr($c->slug) . '" href="' . esc_url(get_category_link($c)) . '">' . esc_html($c->name) . '</a>';
}

function fdb_when($id = 0) {
	$t = get_post_time('U', true, $id);
	$d = time() - $t;
	if ($d < HOUR_IN_SECONDS) return 'há ' . max(1, round($d / 60)) . ' min';
	if ($d < DAY_IN_SECONDS) return 'há ' . round($d / HOUR_IN_SECONDS) . ' h';
	if ($d < 2 * DAY_IN_SECONDS) return 'ontem';
	return get_the_date('d/m/Y', $id);
}

function fdb_reading_time() {
	$w = str_word_count(wp_strip_all_tags(get_post_field('post_content', get_the_ID())));
	return max(1, (int) ceil($w / 200)) . ' min de leitura';
}

function fdb_img($size, $class = '') {
	if (has_post_thumbnail()) {
		the_post_thumbnail($size, array('loading' => 'lazy', 'class' => $class));
	} else {
		echo '<span class="ph"><img src="' . esc_url(get_template_directory_uri() . '/assets/img/logo-branca.png') . '" alt=""></span>';
	}
}

/**
 * Card de notícia. Variações: lead (manchete), over (texto sobre a foto), side (lista com miniatura),
 * grid (foto em cima), text (só texto), rank (mais lidas, número grande).
 */
function fdb_card($v = 'grid', $rank = 0) {
	$cls = 'card card--' . $v . ($v !== 'text' && $v !== 'rank' ? ' tilt' : '');
	?>
	<article <?php post_class($cls); ?>>
		<?php if ($v === 'rank'): ?>
			<span class="card__n"><?php echo (int) $rank; ?></span>
		<?php endif; ?>
		<?php if (in_array($v, array('lead', 'over', 'grid', 'side'), true)): ?>
			<a class="card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php fdb_img($v === 'lead' || $v === 'over' ? 'fdb-lead' : ($v === 'side' ? 'fdb-thumb' : 'fdb-card')); ?>
				<span class="card__shine"></span>
			</a>
		<?php endif; ?>
		<div class="card__body">
			<?php if ($v !== 'rank') fdb_tag(); ?>
			<h3 class="card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			<?php if ($v === 'lead' || $v === 'over'): $x = get_the_excerpt(); if ($x): ?>
				<p class="card__sub"><?php echo esc_html(wp_trim_words($x, 24)); ?></p>
			<?php endif; endif; ?>
			<time class="card__time" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(fdb_when()); ?></time>
		</div>
	</article>
	<?php
}

function fdb_section_head($title, $link = '', $kicker = '') {
	echo '<header class="sec__head">';
	if ($kicker) echo '<span class="sec__kicker">' . esc_html($kicker) . '</span>';
	echo '<h2 class="sec__title">' . ($link ? '<a href="' . esc_url($link) . '">' . esc_html($title) . '</a>' : esc_html($title)) . '</h2><span class="sec__rule"></span>';
	if ($link) echo '<a class="sec__more" href="' . esc_url($link) . '">Ver tudo <svg viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg></a>';
	echo '</header>';
}

/* Menu: usa o menu "Menu principal" se existir; senão, as categorias com notícias */
function fdb_menu_items() {
	$out = array();
	foreach (get_categories(array('orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true, 'number' => 9)) as $c) {
		$out[] = array('t' => $c->name, 'u' => get_category_link($c), 'on' => is_category($c->term_id), 'cat' => $c);
	}
	return $out;
}

function fdb_icon($n) {
	$i = array(
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" class="f"/>',
		'whatsapp'  => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a4 4 0 0 1-2-2l.8-1-1-2z" class="f"/>',
		'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v7h4v-7h3l1-4h-4V8.5c0-.3.2-.5.5-.5z"/>',
		'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.5 2.5 4 5 4"/>',
		'youtube'   => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.5v5l4.5-2.5z" class="f"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="M16.5 16.5 21 21"/>',
		'moon'      => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5z"/>',
		'share'     => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
		'link'      => '<path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
		'x'         => '<path d="M4 4l16 16M20 4L4 20"/>',
	);
	return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">' . (isset($i[$n]) ? $i[$n] : '') . '</svg>';
}

function fdb_social($class = 'social') {
	echo '<div class="' . esc_attr($class) . '">';
	foreach (array('instagram', 'whatsapp', 'facebook', 'tiktok', 'youtube') as $r) {
		$u = fdb_opt('fdb_' . $r);
		if ($u) echo '<a class="btn-ico" href="' . esc_url($u) . '" target="_blank" rel="noopener" aria-label="' . esc_attr(ucfirst($r)) . '">' . fdb_icon($r) . '</a>';
	}
	echo '</div>';
}

/* Bloco "Canal do Zap" + Instagram na lateral */
function fdb_cta_canais() {
	$zap  = fdb_opt('fdb_whatsapp');
	$ig   = fdb_opt('fdb_instagram');
	$logo = esc_url(get_template_directory_uri() . '/assets/img/logo-branca.png');
	$cards = array(
		array('zap', $zap ? $zap : $ig, 'Canal da Folha no Zap', 'As notícias de Belford Roxo direto no seu WhatsApp.', 'Entrar no canal', 'whatsapp'),
		array('ig', $ig, '@folhadebel', 'Siga e acompanhe a Baixada em tempo real.', 'Seguir no Instagram', 'instagram'),
	);
	foreach ($cards as $c) {
		echo '<a class="cta3d cta3d--' . $c[0] . '" href="' . esc_url($c[1]) . '" target="_blank" rel="noopener">';
		echo '<span class="cta3d__avatar"><img src="' . $logo . '" alt="" width="447" height="164"><i>' . fdb_icon($c[5]) . '</i></span>';
		echo '<span class="cta3d__txt"><strong>' . esc_html($c[2]) . '</strong><span>' . esc_html($c[3]) . '</span></span>';
		echo '<span class="cta3d__btn">' . esc_html($c[4]) . ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>';
		echo '</a>';
	}
}

function fdb_share() {
	$u = rawurlencode(get_permalink());
	$t = rawurlencode(get_the_title());
	echo '<div class="share"><span>Compartilhe</span>';
	echo '<a class="btn-ico btn-ico--zap" target="_blank" rel="noopener" href="https://wa.me/?text=' . $t . '%20' . $u . '" aria-label="WhatsApp">' . fdb_icon('whatsapp') . '</a>';
	echo '<a class="btn-ico" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u=' . $u . '" aria-label="Facebook">' . fdb_icon('facebook') . '</a>';
	echo '<a class="btn-ico" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text=' . $t . '&url=' . $u . '" aria-label="X">' . fdb_icon('x') . '</a>';
	echo '<button class="btn-ico" type="button" data-copy="' . esc_url(get_permalink()) . '" aria-label="Copiar link">' . fdb_icon('link') . '</button>';
	echo '</div>';
}

/* Resumo */
add_filter('excerpt_more', function () { return '…'; });
add_filter('excerpt_length', function () { return 30; });

/* Esconde a barra de admin no front para quem não é editor (visual limpo) */
add_filter('show_admin_bar', function ($s) { return $s && current_user_can('edit_posts'); });
