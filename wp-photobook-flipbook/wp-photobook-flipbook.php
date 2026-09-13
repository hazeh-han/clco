<?php
/**
 * Plugin Name: WP Photobook Flipbook
 * Description: ACF 갤러리 필드에 올린 사진을 실제 책처럼 페이지를 넘겨서 보여주는 가벼운 숏코드([wp_photobook])를 제공합니다. 외부 유료 플러그인/라이브러리 의존 없이 순수 CSS 3D transform으로 동작합니다.
 * Version: 1.0.0
 * Requires PHP: 7.2
 * License: GPL-2.0-or-later
 * Text Domain: wp-photobook
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pull a usable image URL out of one ACF gallery item, whatever the
 * field's "Return Format" is set to (Image Array / Image ID / Image URL).
 */
function wp_photobook_extract_url( $image, $size ) {
	if ( is_string( $image ) ) {
		return $image;
	}
	if ( is_numeric( $image ) ) {
		$url = wp_get_attachment_image_url( (int) $image, $size );
		return $url ? $url : '';
	}
	if ( is_array( $image ) ) {
		if ( ! empty( $image['sizes'][ $size ] ) ) {
			return $image['sizes'][ $size ];
		}
		if ( ! empty( $image['url'] ) ) {
			return $image['url'];
		}
	}
	return '';
}

/**
 * Build a simple inline-SVG cover (front or back) as a data URI, so the
 * book always has a first/last "leaf" even without a dedicated cover photo.
 */
function wp_photobook_cover_svg( $title, $subtitle, $variant = 'front' ) {
	$w = 480;
	$h = 672;

	$title    = esc_html( $title );
	$subtitle = esc_html( $subtitle );

	ob_start();
	?>
<svg xmlns="http://www.w3.org/2000/svg" width="<?php echo esc_attr( $w ); ?>" height="<?php echo esc_attr( $h ); ?>" viewBox="0 0 <?php echo esc_attr( $w ); ?> <?php echo esc_attr( $h ); ?>">
	<defs>
		<linearGradient id="wppbCoverGrad" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0" stop-color="#3f3149" />
			<stop offset="1" stop-color="#17121d" />
		</linearGradient>
	</defs>
	<rect width="<?php echo esc_attr( $w ); ?>" height="<?php echo esc_attr( $h ); ?>" fill="url(#wppbCoverGrad)" />
	<rect x="28" y="28" width="<?php echo esc_attr( $w - 56 ); ?>" height="<?php echo esc_attr( $h - 56 ); ?>" fill="none" stroke="#d6a85c" stroke-width="2" stroke-opacity="0.85" />
	<?php if ( 'front' === $variant ) : ?>
		<rect x="38" y="38" width="<?php echo esc_attr( $w - 76 ); ?>" height="<?php echo esc_attr( $h - 76 ); ?>" fill="none" stroke="#d6a85c" stroke-width="2" stroke-opacity="0.45" />
		<text x="50%" y="47%" text-anchor="middle" font-family="Georgia, 'Times New Roman', serif" font-weight="700" font-size="40" fill="#efd9a8"><?php echo $title; ?></text>
		<text x="50%" y="58%" text-anchor="middle" font-family="ui-monospace, Menlo, monospace" font-size="13" letter-spacing="2" fill="#efd9a8" fill-opacity="0.75"><?php echo $subtitle; ?></text>
	<?php else : ?>
		<text x="50%" y="50%" text-anchor="middle" font-family="ui-monospace, Menlo, monospace" font-size="14" fill="#efd9a8" fill-opacity="0.8"><?php echo $title; ?></text>
		<text x="50%" y="56%" text-anchor="middle" font-family="ui-monospace, Menlo, monospace" font-size="12" fill="#efd9a8" fill-opacity="0.55"><?php echo $subtitle; ?></text>
	<?php endif; ?>
</svg>
	<?php
	$svg = ob_get_clean();
	return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}

/**
 * Shared CSS, printed once per page load regardless of how many
 * [wp_photobook] instances are on it.
 */
function wp_photobook_styles() {
	return <<<'CSS'
<style>
.wppb-root{
	--wppb-ink:#221c1a;
	--wppb-ink-soft:#6a5f52;
	--wppb-surface-2:#ddd5c0;
	--wppb-paper:#f4eee0;
	--wppb-border:#cec1a0;
	--wppb-accent:#a97928;
	--wppb-accent-soft:#e3c688;
	--wppb-shadow:rgba(29,22,14,0.35);
	--wppb-focus:#8a5c1e;
	--wppb-font-mono:'IBM Plex Mono',ui-monospace,'SFMono-Regular',Menlo,monospace;
	max-width:760px;margin:0 auto;box-sizing:border-box;
}
@media (prefers-color-scheme: dark){
	.wppb-root:not([data-theme="light"]){
		--wppb-ink:#efe7d8;--wppb-ink-soft:#b6ac97;--wppb-surface-2:#1c1723;
		--wppb-paper:#efe6d2;--wppb-border:#332c3a;--wppb-accent:#dcae64;
		--wppb-accent-soft:#f0d8a5;--wppb-shadow:rgba(0,0,0,0.55);--wppb-focus:#e0b365;
	}
}
.wppb-root[data-theme="dark"]{
	--wppb-ink:#efe7d8;--wppb-ink-soft:#b6ac97;--wppb-surface-2:#1c1723;
	--wppb-paper:#efe6d2;--wppb-border:#332c3a;--wppb-accent:#dcae64;
	--wppb-accent-soft:#f0d8a5;--wppb-shadow:rgba(0,0,0,0.55);--wppb-focus:#e0b365;
}
.wppb-root *{box-sizing:border-box;}
.wppb-stage{position:relative;}
.wppb-book-stage{position:relative;width:min(100%,760px);margin:0 auto;perspective:2200px;}
.wppb-book-stage.wppb-single{width:min(100%,320px);}
.wppb-book{position:relative;width:100%;aspect-ratio:10/7;overflow:hidden;border-radius:8px;background:var(--wppb-paper);
	box-shadow:0 30px 56px -24px var(--wppb-shadow),0 10px 22px -12px var(--wppb-shadow),inset 0 0 0 1px var(--wppb-border);}
.wppb-book.wppb-single{aspect-ratio:5/7;}
.wppb-spine{position:absolute;top:0;left:50%;width:10px;height:100%;transform:translateX(-50%);
	background:linear-gradient(to right,transparent,var(--wppb-shadow) 45%,var(--wppb-shadow) 55%,transparent);opacity:.45;pointer-events:none;z-index:4;}
.wppb-book.wppb-single .wppb-spine{display:none;}
.wppb-slot{position:absolute;top:0;height:100%;overflow:hidden;background:var(--wppb-paper);}
.wppb-slot img{width:100%;height:100%;object-fit:cover;display:block;}
.wppb-slot-a{left:0;width:50%;}
.wppb-slot-b{right:0;width:50%;}
.wppb-book.wppb-single .wppb-slot-a{width:100%;}
.wppb-book.wppb-single .wppb-slot-b{display:none;}
.wppb-slot-a::after{content:'';position:absolute;top:0;right:0;bottom:0;width:14%;background:linear-gradient(to left,var(--wppb-shadow),transparent);opacity:.3;pointer-events:none;}
.wppb-slot-b::before{content:'';position:absolute;top:0;left:0;bottom:0;width:14%;background:linear-gradient(to right,var(--wppb-shadow),transparent);opacity:.3;pointer-events:none;}
.wppb-book.wppb-single .wppb-slot-a::after{display:none;}
.wppb-flipper{position:absolute;top:0;height:100%;width:50%;left:0;transform-style:preserve-3d;opacity:0;pointer-events:none;
	transition:transform .7s cubic-bezier(.45,.05,.15,1);z-index:10;}
.wppb-flipper.wppb-visible{opacity:1;}
.wppb-flipper.wppb-at-start{left:0;right:auto;}
.wppb-flipper.wppb-at-end{right:0;left:auto;}
.wppb-flipper.wppb-origin-left{transform-origin:left center;}
.wppb-flipper.wppb-origin-right{transform-origin:right center;}
.wppb-book.wppb-single .wppb-flipper{width:100%;left:0;right:auto;}
.wppb-face{position:absolute;inset:0;backface-visibility:hidden;-webkit-backface-visibility:hidden;overflow:hidden;background:var(--wppb-paper);}
.wppb-face img{width:100%;height:100%;object-fit:cover;display:block;}
.wppb-face-back{transform:rotateY(180deg);}
.wppb-face-front::after,.wppb-face-back::after{content:'';position:absolute;inset:0;pointer-events:none;
	background:linear-gradient(to right,rgba(0,0,0,.22),transparent 22%,transparent 78%,rgba(0,0,0,.22));}
@media (prefers-reduced-motion: reduce){.wppb-flipper{transition:none;}}
.wppb-nav-zone{position:absolute;top:0;bottom:0;width:50%;background:transparent;border:0;padding:0;margin:0;cursor:pointer;}
.wppb-nav-prev{left:0;}
.wppb-nav-next{right:0;}
.wppb-controls{display:flex;align-items:center;justify-content:center;gap:16px;margin-top:18px;}
.wppb-ctl-btn{width:44px;height:44px;flex:none;border-radius:50%;border:1px solid var(--wppb-border);background:var(--wppb-surface-2);
	color:var(--wppb-ink);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .15s;}
.wppb-ctl-btn:hover:not(:disabled){background:var(--wppb-accent-soft);}
.wppb-ctl-btn:disabled{opacity:.35;cursor:default;}
.wppb-ctl-btn:focus-visible{outline:2px solid var(--wppb-focus);outline-offset:2px;}
.wppb-ctl-mid{display:flex;flex-direction:column;align-items:center;gap:6px;width:180px;}
.wppb-ctl-mid input[type=range]{width:100%;accent-color:var(--wppb-accent);}
.wppb-ctl-mid input[type=range]:focus-visible{outline:2px solid var(--wppb-focus);outline-offset:2px;}
.wppb-ctl-count{font-family:var(--wppb-font-mono);font-size:.8rem;color:var(--wppb-ink-soft);font-variant-numeric:tabular-nums;}
.wppb-hint{text-align:center;font-size:.8rem;color:var(--wppb-ink-soft);margin:10px 0 0;}
</style>
CSS;
}

/**
 * Shared JS engine, printed once per page load. Exposes
 * window.WPPhotobookInit(rootEl, leaves) which every shortcode instance
 * calls with its own container + image list.
 */
function wp_photobook_script() {
	return <<<'JS'
<script>
window.WPPhotobookInit = function(root, leaves){
	if(!root || !leaves || !leaves.length) return;

	var bookStage = root.querySelector('.wppb-book-stage');
	var book = root.querySelector('.wppb-book');
	var imgA = root.querySelector('.wppb-img-a');
	var imgB = root.querySelector('.wppb-img-b');
	var flipper = root.querySelector('.wppb-flipper');
	var flipFront = root.querySelector('.wppb-face-front img');
	var flipBack = root.querySelector('.wppb-face-back img');
	var btnPrev = root.querySelector('.wppb-btn-prev');
	var btnNext = root.querySelector('.wppb-btn-next');
	var zonePrev = root.querySelector('.wppb-nav-prev');
	var zoneNext = root.querySelector('.wppb-nav-next');
	var slider = root.querySelector('.wppb-slider');
	var countEl = root.querySelector('.wppb-ctl-count');

	var totalLeaves = leaves.length;
	var pv = 2;
	var viewIndex = 0;
	var animating = false;
	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var mq = window.matchMedia('(max-width: 680px)');

	function maxView(){ return Math.ceil(totalLeaves / pv) - 1; }

	function applyMode(){
		var single = mq.matches;
		pv = single ? 1 : 2;
		book.classList.toggle('wppb-single', single);
		bookStage.classList.toggle('wppb-single', single);
	}

	function preload(src){ if(src){ var i = new Image(); i.src = src; } }

	function renderStatic(){
		imgA.src = leaves[viewIndex * pv];
		if(pv === 2){ imgB.src = leaves[viewIndex * pv + 1]; }
		updateControls();
		var mv = maxView();
		if(viewIndex < mv){ preload(leaves[Math.min((viewIndex + 1) * pv, totalLeaves - 1)]); }
		if(viewIndex > 0){ preload(leaves[Math.max((viewIndex - 1) * pv, 0)]); }
	}

	function updateControls(){
		var mv = maxView();
		btnPrev.disabled = viewIndex <= 0;
		btnNext.disabled = viewIndex >= mv;
		if(slider){ slider.min = 0; slider.max = mv; slider.value = viewIndex; }
		if(countEl){ countEl.textContent = (viewIndex + 1) + ' / ' + (mv + 1); }
	}

	function doFlip(dir){
		if(animating) return;
		var mv = maxView();
		if(dir === 1 && viewIndex >= mv) return;
		if(dir === -1 && viewIndex <= 0) return;

		if(reduceMotion){
			viewIndex += dir;
			renderStatic();
			return;
		}

		animating = true;
		flipper.classList.remove('wppb-origin-left','wppb-origin-right','wppb-at-start','wppb-at-end');
		var frontSrc, backSrc;
		if(dir === 1){
			frontSrc = leaves[viewIndex * pv + (pv - 1)];
			backSrc = leaves[(viewIndex + 1) * pv];
			flipper.classList.add('wppb-origin-left','wppb-at-end');
		} else {
			frontSrc = leaves[viewIndex * pv];
			backSrc = leaves[viewIndex * pv - 1];
			flipper.classList.add('wppb-origin-right','wppb-at-start');
		}
		flipFront.src = frontSrc;
		flipBack.src = backSrc;

		flipper.style.transition = 'none';
		flipper.style.transform = 'rotateY(0deg)';
		flipper.classList.add('wppb-visible');
		void flipper.offsetWidth;
		flipper.style.transition = '';

		requestAnimationFrame(function(){
			requestAnimationFrame(function(){
				flipper.style.transform = dir === 1 ? 'rotateY(-180deg)' : 'rotateY(180deg)';
			});
		});

		function onEnd(e){
			if(e.propertyName !== 'transform') return;
			flipper.removeEventListener('transitionend', onEnd);
			flipper.classList.remove('wppb-visible');
			flipper.style.transform = 'rotateY(0deg)';
			viewIndex += dir;
			renderStatic();
			animating = false;
		}
		flipper.addEventListener('transitionend', onEnd);
	}

	btnPrev.addEventListener('click', function(){ doFlip(-1); });
	btnNext.addEventListener('click', function(){ doFlip(1); });
	if(zonePrev){ zonePrev.addEventListener('click', function(){ doFlip(-1); }); }
	if(zoneNext){ zoneNext.addEventListener('click', function(){ doFlip(1); }); }
	root.addEventListener('keydown', function(e){
		if(e.key === 'ArrowLeft') doFlip(-1);
		if(e.key === 'ArrowRight') doFlip(1);
	});
	if(slider){
		slider.addEventListener('input', function(e){
			if(animating){ e.target.value = viewIndex; return; }
			viewIndex = +e.target.value;
			renderStatic();
		});
	}

	var resizeTimer = null;
	window.addEventListener('resize', function(){
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function(){
			var oldPv = pv;
			var absPos = viewIndex * oldPv;
			applyMode();
			viewIndex = Math.min(Math.floor(absPos / pv), maxView());
			renderStatic();
		}, 150);
	});

	applyMode();
	renderStatic();
};
</script>
JS;
}

function wp_photobook_shortcode( $atts ) {
	static $instance = 0;
	static $assets_printed = false;

	if ( ! function_exists( 'get_field' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p>' . esc_html__( '[wp_photobook] 이 숏코드를 쓰려면 ACF(Advanced Custom Fields) 플러그인이 활성화되어 있어야 합니다.', 'wp-photobook' ) . '</p>';
		}
		return '';
	}

	$atts = shortcode_atts(
		array(
			'field'          => 'photo_gallery',
			'post_id'        => get_the_ID(),
			'size'           => 'large',
			'cover'          => 'yes',
			'cover_title'    => get_bloginfo( 'name' ),
			'cover_subtitle' => '',
		),
		$atts,
		'wp_photobook'
	);

	$post_id = absint( $atts['post_id'] );
	$field   = sanitize_key( $atts['field'] );
	$size    = sanitize_key( $atts['size'] );

	$images = get_field( $field, $post_id ? $post_id : null );

	if ( empty( $images ) || ! is_array( $images ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			return '<p>' . sprintf(
				/* translators: %s: ACF field name */
				esc_html__( '[wp_photobook] "%s" 갤러리 필드에 사진이 없습니다. 이 페이지에서 ACF 갤러리 필드를 먼저 채워주세요.', 'wp-photobook' ),
				esc_html( $field )
			) . '</p>';
		}
		return '';
	}

	$urls = array();
	foreach ( $images as $image ) {
		$url = wp_photobook_extract_url( $image, $size );
		if ( $url ) {
			$urls[] = esc_url_raw( $url );
		}
	}

	if ( empty( $urls ) ) {
		return '';
	}

	$leaves = $urls;
	if ( 'no' !== $atts['cover'] ) {
		$subtitle = $atts['cover_subtitle'] ? $atts['cover_subtitle'] : ( count( $urls ) . ' ' . __( 'PHOTOGRAPHS', 'wp-photobook' ) );
		array_unshift( $leaves, wp_photobook_cover_svg( $atts['cover_title'], $subtitle, 'front' ) );
		$leaves[] = wp_photobook_cover_svg( count( $urls ) . ' ' . __( 'PHOTOGRAPHS', 'wp-photobook' ), __( 'END', 'wp-photobook' ), 'back' );
	}

	++$instance;
	$root_id = 'wppb-' . $instance . '-' . wp_generate_uuid4();
	$max     = max( 0, (int) ceil( count( $leaves ) / 2 ) - 1 );
	$json    = wp_json_encode( array_values( $leaves ) );
	$json    = str_replace( '</', '<\\/', $json );

	ob_start();

	if ( ! $assets_printed ) {
		echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&display=swap">';
		echo wp_photobook_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_photobook_script(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$assets_printed = true;
	}
	?>
	<div class="wppb-root" id="<?php echo esc_attr( $root_id ); ?>">
		<div class="wppb-stage">
			<div class="wppb-book-stage">
				<div class="wppb-book">
					<div class="wppb-spine" aria-hidden="true"></div>
					<div class="wppb-slot wppb-slot-a"><img class="wppb-img-a" alt="" loading="eager"></div>
					<div class="wppb-slot wppb-slot-b"><img class="wppb-img-b" alt="" loading="eager"></div>
					<div class="wppb-flipper">
						<div class="wppb-face wppb-face-front"><img alt=""></div>
						<div class="wppb-face wppb-face-back"><img alt=""></div>
					</div>
				</div>
			</div>
			<button class="wppb-nav-zone wppb-nav-prev" type="button" tabindex="-1" aria-hidden="true"></button>
			<button class="wppb-nav-zone wppb-nav-next" type="button" tabindex="-1" aria-hidden="true"></button>
		</div>
		<div class="wppb-controls">
			<button class="wppb-ctl-btn wppb-btn-prev" type="button" aria-label="<?php esc_attr_e( '이전 페이지', 'wp-photobook' ); ?>">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8L10 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<div class="wppb-ctl-mid">
				<input class="wppb-slider" type="range" min="0" max="<?php echo esc_attr( $max ); ?>" value="0" aria-label="<?php esc_attr_e( '페이지 이동', 'wp-photobook' ); ?>">
				<span class="wppb-ctl-count">1 / <?php echo esc_html( $max + 1 ); ?></span>
			</div>
			<button class="wppb-ctl-btn wppb-btn-next" type="button" aria-label="<?php esc_attr_e( '다음 페이지', 'wp-photobook' ); ?>">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3L11 8L6 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</div>
		<p class="wppb-hint"><?php esc_html_e( '← → 방향키, 또는 책 좌우를 클릭해서 넘겨보세요.', 'wp-photobook' ); ?></p>
	</div>
	<script>
	(function(){
		var root = document.getElementById(<?php echo wp_json_encode( $root_id ); ?>);
		var leaves = <?php echo $json; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>;
		if (window.WPPhotobookInit) { window.WPPhotobookInit(root, leaves); }
	})();
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'wp_photobook', 'wp_photobook_shortcode' );
