<?php
?><!doctype html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="sr" href="#conteudo">Pular para o conteúdo</a>

<header class="hd">
 <div class="topbar"><div class="wrap">
  <span class="topbar__city"><b>Belford Roxo</b> &middot; RJ</span>
  <span class="topbar__date"><?php echo esc_html(wp_date('l, j \d\e F \d\e Y')); ?></span>
  <span class="topbar__social"><a href="https://www.instagram.com/folhadebel/" rel="noopener" target="_blank">Instagram</a></span>
 </div></div>

 <div class="wrap mast">
  <button class="burger" aria-label="Abrir menu" aria-expanded="false"><i></i><i></i><i></i></button>
  <div class="mast__brand"><?php fdb_brand(); ?></div>
  <form class="mast__search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
   <label class="sr" for="q">Buscar no site</label>
   <input id="q" type="search" name="s" placeholder="Buscar" value="<?php echo esc_attr(get_search_query()); ?>">
   <button type="submit" aria-label="Buscar"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4.5 4.5"/></svg></button>
  </form>
 </div>

 <nav class="nav" id="nav"><div class="wrap"><ul>
  <li<?php echo (is_front_page()||is_home())?' class="on"':''; ?>><a href="<?php echo esc_url(home_url('/')); ?>">Início</a></li>
  <?php foreach(fdb_cats(7) as $c): ?>
  <li<?php echo is_category($c->term_id)?' class="on"':''; ?>><a href="<?php echo esc_url(get_category_link($c)); ?>"><?php echo esc_html($c->name); ?></a></li>
  <?php endforeach; ?>
 </ul></div></nav>
</header>

<?php $ticker=get_posts(array('numberposts'=>4)); if($ticker): ?>
<div class="ticker"><div class="wrap">
 <span class="ticker__tag">Últimas</span>
 <ul class="ticker__list">
  <?php foreach($ticker as $t): ?>
  <li><a href="<?php echo esc_url(get_permalink($t)); ?>"><?php echo esc_html(get_the_title($t)); ?></a></li>
  <?php endforeach; ?>
 </ul>
</div></div>
<?php endif; ?>

<main id="conteudo">

<?php if(is_single()): while(have_posts()):the_post(); $cat=fdb_cat(); ?>
<article class="post">
 <header class="post__head wrap-n">
  <?php if($cat): ?><a class="eyebrow eyebrow--lg" href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a><?php endif; ?>
  <h1><?php the_title(); ?></h1>
  <?php $k=fdb_kicker(); if($k && (!$cat || $k!==$cat->name)): ?><p class="post__kicker"><?php echo esc_html($k); ?></p><?php endif; ?>
  <div class="post__meta">
   <span>Por <b><?php the_author(); ?></b></span>
   <span><?php echo esc_html(get_the_date('j \d\e F \d\e Y, H:i')); ?></span>
   <span><?php echo esc_html(fdb_reading_time()); ?></span>
  </div>
 </header>
 <?php if(has_post_thumbnail()): ?>
 <figure class="post__hero"><div class="wrap"><?php the_post_thumbnail('full');
  $cap=get_the_post_thumbnail_caption(); if($cap): ?><figcaption><?php echo wp_kses_post($cap); ?></figcaption><?php endif; ?></div></figure>
 <?php endif; ?>
 <div class="post__body wrap-n"><?php the_content(); wp_link_pages(); ?></div>
 <?php if($cat):
  $rel=new WP_Query(array('cat'=>$cat->term_id,'posts_per_page'=>3,'post__not_in'=>array(get_the_ID()),'ignore_sticky_posts'=>true));
  if($rel->have_posts()): ?>
 <section class="rel"><div class="wrap">
  <div class="ed__head"><h2>Leia também</h2></div>
  <div class="rel__grid"><?php while($rel->have_posts()):$rel->the_post();fdb_card('tall');endwhile; ?></div>
 </div></section>
 <?php endif; wp_reset_postdata(); endif; ?>
</article>
<?php endwhile;

elseif(is_404()): ?>
<section class="e404 wrap"><div class="e404__code">404</div><h1>Esta página não existe.</h1><p>O endereço pode ter mudado ou a página foi removida.</p><a class="btn" href="<?php echo esc_url(home_url('/')); ?>">Ir para a capa</a></section>

<?php elseif(is_front_page() || is_home()):
 $lead=new WP_Query(array('posts_per_page'=>4,'ignore_sticky_posts'=>true));
 $used=wp_list_pluck($lead->posts,'ID'); ?>
<div class="home">
<?php if($lead->have_posts()): ?>
 <section class="hero wrap">
  <?php $lead->the_post(); fdb_card('lead'); ?>
  <div class="hero__side"><?php while($lead->have_posts()):$lead->the_post();fdb_card('mini');endwhile; ?></div>
 </section>
<?php else: ?>
 <section class="wrap empty"><span class="eyebrow">Folha de Belford Roxo</span><h1>O portal está no ar.</h1><p>As matérias aparecem aqui assim que forem publicadas.</p></section>
<?php endif; wp_reset_postdata(); ?>

 <div class="wrap cols">
  <div class="cols__main">
   <?php $q=new WP_Query(array('posts_per_page'=>8,'post__not_in'=>$used,'ignore_sticky_posts'=>true)); if($q->have_posts()): ?>
   <section class="ed">
    <div class="ed__head"><h2>Últimas notícias</h2></div>
    <div class="feed">
     <?php while($q->have_posts()):$q->the_post();fdb_card('wide');endwhile; ?>
    </div>
   </section>
   <?php endif; $used=array_merge($used,wp_list_pluck($q->posts,'ID')); wp_reset_postdata(); ?>
  </div>

  <aside class="cols__side">
   <section class="rank">
    <div class="ed__head"><h2>Mais lidas</h2></div>
    <ol>
     <?php $r=new WP_Query(array('posts_per_page'=>5,'orderby'=>'comment_count','ignore_sticky_posts'=>true));
     while($r->have_posts()):$r->the_post(); ?>
     <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
     <?php endwhile; wp_reset_postdata(); ?>
    </ol>
   </section>

   <section class="promo">
    <span>Acompanhe no Instagram</span>
    <strong>@folhadebel</strong>
    <p>A notícia de Belford Roxo e da Baixada primeiro na sua timeline.</p>
    <a class="btn btn--ghost" href="https://www.instagram.com/folhadebel/" rel="noopener" target="_blank">Seguir</a>
   </section>

   <section class="eds">
    <div class="ed__head"><h2>Editorias</h2></div>
    <ul>
     <?php foreach(fdb_cats(8) as $c): ?>
     <li><a href="<?php echo esc_url(get_category_link($c)); ?>"><span><?php echo esc_html($c->name); ?></span><b><?php echo esc_html($c->count); ?></b></a></li>
     <?php endforeach; ?>
    </ul>
   </section>
  </aside>
 </div>

 <div class="wrap"><?php foreach(fdb_cats(4) as $c) fdb_section($c,4); ?></div>
</div>

<?php else: ?>
<div class="wrap list">
 <header class="list__head">
  <span class="eyebrow"><?php echo is_search()?'Busca':'Editoria'; ?></span>
  <h1><?php if(is_search()) printf('%s', esc_html(get_search_query())); elseif(is_archive()) the_archive_title(); else echo 'Notícias'; ?></h1>
  <?php if(is_category()){$d=category_description(); if($d) echo '<p>'.wp_kses_post($d).'</p>';} ?>
 </header>
 <div class="feed">
  <?php if(have_posts()): while(have_posts()):the_post();fdb_card('wide');endwhile;
  else: ?><p class="list__none">Nada encontrado por aqui. Tente outra busca.</p><?php endif; ?>
 </div>
 <?php the_posts_pagination(array('prev_text'=>'Anterior','next_text'=>'Próxima','mid_size'=>1)); ?>
</div>
<?php endif; ?>

</main>

<footer class="ft">
 <div class="wrap ft__top">
  <div class="ft__brand">
   <?php fdb_brand('wordmark wordmark--ft'); ?>
   <p>Jornalismo local de Belford Roxo e da Baixada Fluminense. Informação apurada, perto de quem vive a cidade.</p>
   <a class="ft__ig" href="https://www.instagram.com/folhadebel/" rel="noopener" target="_blank">@folhadebel</a>
  </div>
  <div class="ft__cols">
   <div><h3>Editorias</h3><ul><?php foreach(fdb_cats(6) as $c): ?><li><a href="<?php echo esc_url(get_category_link($c)); ?>"><?php echo esc_html($c->name); ?></a></li><?php endforeach; ?></ul></div>
   <div><h3>O portal</h3><ul>
    <li><a href="<?php echo esc_url(home_url('/')); ?>">Capa</a></li>
    <li><a href="<?php echo esc_url(home_url('/?s=')); ?>">Buscar</a></li>
    <li><a href="https://www.instagram.com/folhadebel/" rel="noopener" target="_blank">Instagram</a></li>
   </ul></div>
  </div>
 </div>
 <div class="wrap ft__bar">
  <span>&copy; <?php echo esc_html(wp_date('Y')); ?> Folha de Belford Roxo</span>
  <span>Belford Roxo &middot; Rio de Janeiro</span>
 </div>
</footer>

<script>
(function(){
 var b=document.querySelector('.burger'), n=document.getElementById('nav');
 if(b&&n) b.addEventListener('click',function(){var o=n.classList.toggle('open');b.classList.toggle('x',o);b.setAttribute('aria-expanded',o);});
})();
</script>
<?php wp_footer(); ?></body></html>
