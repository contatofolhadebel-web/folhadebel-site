<?php
if (!defined('ABSPATH')) exit;

function fdb_setup(){
 add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('responsive-embeds'); add_theme_support('align-wide'); add_theme_support('automatic-feed-links');
 add_theme_support('custom-logo',array('height'=>120,'width'=>520,'flex-height'=>true,'flex-width'=>true));
 add_theme_support('html5',array('search-form','gallery','caption','style','script'));
 register_nav_menus(array('primary'=>'Menu principal','footer'=>'Menu do rodapé'));
 add_image_size('fdb-lead',1280,760,true); add_image_size('fdb-card',720,450,true); add_image_size('fdb-thumb',240,240,true);
}
add_action('after_setup_theme','fdb_setup');

function fdb_assets(){wp_enqueue_style('fdb-style',get_stylesheet_uri(),array(),wp_get_theme()->get('Version'));}
add_action('wp_enqueue_scripts','fdb_assets');

function fdb_cat($id=0){$c=get_the_category($id);return !empty($c)?$c[0]:null;}

function fdb_kicker(){$e=trim((string)get_post_field('post_excerpt',get_the_ID()));if($e)return wp_strip_all_tags($e);$c=fdb_cat();return $c?$c->name:'';}

function fdb_brand($class='wordmark'){
 if(has_custom_logo()){the_custom_logo();return;}
 echo '<a class="'.esc_attr($class).'" href="'.esc_url(home_url('/')).'"><span class="wordmark__name">folha</span><span class="wordmark__place">Belford Roxo</span></a>';
}

function fdb_reading_time(){
 $w=str_word_count(wp_strip_all_tags(get_post_field('post_content',get_the_ID())));
 $m=max(1,(int)ceil($w/200));
 return $m.' min de leitura';
}

function fdb_when($id=0){
 $t=get_post_time('U',true,$id);
 $d=time()-$t;
 if($d < DAY_IN_SECONDS) return 'há '.human_time_diff($t,time());
 if($d < 2*DAY_IN_SECONDS) return 'ontem';
 return get_the_date('j M Y',$id);
}

function fdb_card($v='wide'){
 $c=fdb_cat();
 $img = $v==='lead' ? 'fdb-lead' : ($v==='mini' || $v==='list' ? 'fdb-thumb' : 'fdb-card');
 ?>
 <article <?php post_class('c c--'.$v); ?>>
  <?php if($v!=='list'): ?>
  <a class="c__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
   <?php if(has_post_thumbnail()) the_post_thumbnail($img,array('loading'=>'lazy'));
         else echo '<span class="c__ph"><b>f</b></span>'; ?>
  </a>
  <?php endif; ?>
  <div class="c__body">
   <?php if($c): ?><a class="eyebrow" href="<?php echo esc_url(get_category_link($c)); ?>"><?php echo esc_html($c->name); ?></a><?php endif; ?>
   <h3 class="c__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
   <?php if($v==='lead'||$v==='wide'): $x=get_the_excerpt(); if($x): ?>
   <p class="c__sub"><?php echo esc_html(wp_trim_words($x,26)); ?></p>
   <?php endif; endif; ?>
   <time class="c__time" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(fdb_when()); ?></time>
  </div>
 </article>
 <?php
}

function fdb_section($cat,$n=4){
 $q=new WP_Query(array('cat'=>$cat->term_id,'posts_per_page'=>$n,'ignore_sticky_posts'=>true));
 if(!$q->have_posts()){wp_reset_postdata();return;}
 ?>
 <section class="ed">
  <div class="ed__head">
   <h2><a href="<?php echo esc_url(get_category_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a></h2>
   <a class="ed__all" href="<?php echo esc_url(get_category_link($cat)); ?>">Ver tudo</a>
  </div>
  <div class="ed__grid">
   <?php $i=0; while($q->have_posts()):$q->the_post(); fdb_card($i===0?'tall':'mini'); $i++; endwhile; ?>
  </div>
 </section>
 <?php wp_reset_postdata();
}

function fdb_cats($n=8){
 return get_categories(array('number'=>$n,'orderby'=>'count','order'=>'DESC','hide_empty'=>true));
}

function fdb_excerpt_more($m){return '…';}
add_filter('excerpt_more','fdb_excerpt_more');
function fdb_excerpt_length($l){return 32;}
add_filter('excerpt_length','fdb_excerpt_length');
