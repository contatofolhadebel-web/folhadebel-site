/* Folha de Bel 3D */
(function () {
	var d = document, root = d.documentElement;
	var reduz = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var toque = window.matchMedia('(hover: none)').matches;

	function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); localStorage.setItem(k, v); } catch (e) { return null; } }

	/* Tema escuro */
	d.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
		b.addEventListener('click', function () {
			var atual = root.dataset.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
			var novo = atual === 'dark' ? 'light' : 'dark';
			root.dataset.theme = novo;
			store('fdb-tema', novo);
		});
	});

	/* Busca */
	var form = d.querySelector('.search');
	d.querySelectorAll('[data-search]').forEach(function (b) {
		b.addEventListener('click', function () {
			form.hidden = !form.hidden;
			if (!form.hidden) form.querySelector('input').focus();
		});
	});

	/* Menu no celular */
	var burger = d.querySelector('.burger');
	if (burger) burger.addEventListener('click', function () {
		var aberto = d.body.classList.toggle('menu-aberto');
		burger.setAttribute('aria-expanded', aberto ? 'true' : 'false');
	});

	/* Cabeçalho compacto ao rolar */
	var hd = d.querySelector('.hd'), ultimo = 0;
	window.addEventListener('scroll', function () {
		var y = window.scrollY;
		if (hd) hd.classList.toggle('is-compact', y > 80);
		ultimo = y;
	}, { passive: true });

	/* Anúncios que giram */
	d.querySelectorAll('[data-rotate]').forEach(function (st) {
		var s = st.querySelectorAll('.ad__slide');
		if (s.length < 2) return;
		var i = 0;
		setInterval(function () {
			s[i].classList.remove('is-on');
			i = (i + 1) % s.length;
			s[i].classList.add('is-on');
		}, 5000);
	});

	/* Inclinação 3D dos cards seguindo o mouse */
	if (!reduz && !toque) {
		d.querySelectorAll('.tilt').forEach(function (el) {
			var max = el.classList.contains('tilt--soft') ? 3 : 7;
			el.addEventListener('pointermove', function (e) {
				var r = el.getBoundingClientRect();
				var x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
				el.style.setProperty('--rx', (-y * max).toFixed(2) + 'deg');
				el.style.setProperty('--ry', (x * max).toFixed(2) + 'deg');
				el.style.setProperty('--mx', ((x + 0.5) * 100).toFixed(1) + '%');
				el.style.setProperty('--my', ((y + 0.5) * 100).toFixed(1) + '%');
				el.classList.add('is-tilt');
			});
			el.addEventListener('pointerleave', function () {
				el.style.setProperty('--rx', '0deg');
				el.style.setProperty('--ry', '0deg');
				el.classList.remove('is-tilt');
			});
		});
	}

	/* Copiar link */
	d.querySelectorAll('[data-copy]').forEach(function (b) {
		b.addEventListener('click', function () {
			var u = b.getAttribute('data-copy');
			(navigator.clipboard ? navigator.clipboard.writeText(u) : Promise.reject()).then(function () {
				b.classList.add('is-ok');
				setTimeout(function () { b.classList.remove('is-ok'); }, 1600);
			}).catch(function () { window.prompt('Copie o link:', u); });
		});
	});

	/* Conta a leitura (para "Mais lidas") */
	if (window.FDB && FDB.postId) {
		var k = 'fdb-v-' + FDB.postId;
		try { if (sessionStorage.getItem(k)) return; sessionStorage.setItem(k, '1'); } catch (e) {}
		setTimeout(function () {
			fetch(FDB.rest + FDB.postId, { method: 'POST', keepalive: true, credentials: 'omit' }).catch(function () {});
		}, 4000);
	}
})();
