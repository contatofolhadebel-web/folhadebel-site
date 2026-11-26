#!/usr/bin/env python3
"""Reels da Folha de Bel (1080x1920).

Camadas transparentes aplicadas com ffmpeg:
  0-5 s   topo azul com FOLHA vazado + título (entrada e saída com fade e deslize)
  sempre  faixa FOLHADEBEL no meio da tela + assinatura "Siga @folhadebel no Instagram"
Layouts:
  pe       vídeo em tela cheia (recorte central se o vídeo for deitado)
  deitado  duas faixas: recorte em pé em cima e o vídeo deitado inteiro embaixo

Título: linhas separadas por "/". A linha marcada com "*" fica em caixa azul (letra branca);
as outras ficam em caixa branca (letra azul). Ex.: "INTERNAUTA DIZ QUE ATÉ/*WAGUINHO TEM RAZÃO AO/COBRAR MARIANA CANELLA"

Saídas em --out-dir:
  <nome>-folhadebel-<layout>.mp4   vídeo final
  <nome>-capa.jpg                  frame da abertura com o título (capa do mLabs)
  <nome>-site.jpg                  frame horizontal 1200x675 do vídeo original (site)

Uso:
  python3 reels.py --info --video entrada.mp4
  python3 reels.py --titulo "LINHA 1/*LINHA 2/LINHA 3" --video entrada.mp4 --out-dir saida --layout deitado
  python3 reels.py ... --intro-em 4.2      (encaixa assets/intro.mp4 aos 4,2 s, logo depois do gancho)
"""
import argparse, json, pathlib, subprocess, sys
from PIL import Image, ImageDraw, ImageFont, ImageFilter

AQUI = pathlib.Path(__file__).resolve().parent
ASSETS = AQUI / "assets"
FONTS = AQUI / "fonts"

W, H = 1080, 1920
ABERTURA_S = 5.0                    # topo azul + FOLHA e título ficam só nos 5 primeiros segundos
EFEITO_S = 0.45                     # duração da entrada e da saída (fade + deslize)
DESLIZE_PX = 90                     # o título entra pela esquerda e sai para a esquerda
AZUL = (36, 104, 236, 255)          # caixa azul do título / letra azul
AZUL_TOPO = (37, 110, 250)          # degradê do topo da abertura
BRANCO = (255, 255, 255, 255)
MARGEM_X = 80
TITULO_TAM = 58                     # tamanho da fonte do título
TITULO_PAD_X, TITULO_PAD_Y = 14, 8  # respiro da caixa
TITULO_GAP = 7                      # espaço entre caixas
TITULO_Y = {"pe": 1118}            # topo do título no pe; no deitado o título começa na emenda das faixas
ASSINATURA_Y = 1690                 # centro da assinatura "Siga @folhadebel no Instagram" (~88% da altura)
MARCA_MEIO_Y = 960                  # faixa FOLHADEBEL: sempre e só no meio da tela
GRADE_TOPO = 240                    # o grid do perfil do Instagram mostra só o miolo 3:4 (y 240 a 1680): nada essencial acima disso
FOLHA_Y = GRADE_TOPO + 30           # topo do FOLHA vazado, dentro da área visível no grid
LIMITE_BAIXO = 1830                 # a assinatura não desce além disso
SHEAR_ITALICO = 0.2
MARCA_ESCALA = 1.5
FAIXA_BAIXO_MAX = 0.5               # no deitado, a faixa de baixo ocupa no máx. metade da tela


def fonte(peso, tam):
    return ImageFont.truetype(str(FONTS / f"Saira-{peso}.ttf"), tam)


def sonda(video):
    out = subprocess.run(["ffprobe", "-v", "error", "-select_streams", "v:0", "-show_entries",
                          "stream=width,height:stream_side_data=rotation:stream_tags=rotate:format=duration",
                          "-of", "json", str(video)], capture_output=True, text=True, check=True).stdout
    d = json.loads(out)
    s = d["streams"][0]
    w, h = s["width"], s["height"]
    rot = 0
    for sd in s.get("side_data_list", []) or []:
        if "rotation" in sd:
            rot = int(sd["rotation"])
    rot = rot or int((s.get("tags") or {}).get("rotate", 0))
    if abs(rot) % 180 == 90:
        w, h = h, w
    dur = float(d["format"].get("duration", 0))
    tem_audio = bool(json.loads(subprocess.run(
        ["ffprobe", "-v", "error", "-select_streams", "a", "-show_entries", "stream=index", "-of", "json", str(video)],
        capture_output=True, text=True).stdout or "{}").get("streams"))
    return {"largura": w, "altura": h, "duracao": dur, "orientacao": "deitado" if w > h else "pe", "audio": tem_audio}


def faixa_marca(img, centro_y):
    m = Image.open(ASSETS / "marca-dagua.png").convert("RGBA")
    m = m.resize((int(m.width * MARCA_ESCALA), int(m.height * MARCA_ESCALA)), Image.LANCZOS)
    x = (W - m.width) // 2
    img.alpha_composite(m, (x, centro_y - m.height // 2)) if x >= 0 else img.alpha_composite(
        m.crop((-x, 0, -x + W, m.height)), (0, centro_y - m.height // 2))


def linhas_titulo(titulo, draw, font):
    if "/" in titulo:
        brutas = [l.strip() for l in titulo.split("/") if l.strip()]
    else:  # quebra automática
        brutas, atual = [], ""
        larg = W - 2 * MARGEM_X - 2 * TITULO_PAD_X
        for p in titulo.split():
            t = (atual + " " + p).strip()
            if draw.textlength(t.lstrip("*").upper(), font=font) <= larg:
                atual = t
            else:
                brutas.append(atual); atual = p
        if atual:
            brutas.append(atual)
    linhas = [(l.lstrip("*").strip().upper(), l.startswith("*")) for l in brutas]
    if not any(d for _, d in linhas) and len(linhas) > 1:
        linhas[1] = (linhas[1][0], True)  # padrão do modelo: uma linha azul
    return linhas


def desenhar_titulo(img, titulo, topo):
    d = ImageDraw.Draw(img)
    tam = TITULO_TAM
    while True:
        font = fonte(800, tam)
        linhas = linhas_titulo(titulo, d, font)
        maior = max(d.textlength(t, font=font) for t, _ in linhas)
        if maior + 2 * TITULO_PAD_X <= W - 2 * MARGEM_X + 40 or tam <= 44:
            break
        tam -= 2
    asc, desc = font.getmetrics()
    caixa_h = int(tam * 0.92) + 2 * TITULO_PAD_Y
    y = topo
    for texto, destaque in linhas:
        largura = d.textlength(texto, font=font)
        x0 = MARGEM_X
        d.rectangle([x0, y, x0 + largura + 2 * TITULO_PAD_X, y + caixa_h], fill=AZUL if destaque else BRANCO)
        bbox = d.textbbox((0, 0), texto, font=font, anchor="ls")
        cap_h = -bbox[1]
        base = y + (caixa_h + cap_h) // 2
        d.text((x0 + TITULO_PAD_X, base), texto, font=font, fill=BRANCO if destaque else AZUL, anchor="ls")
        y += caixa_h + TITULO_GAP
    return y


def assinatura(img, centro_y, tipo="siga"):
    if tipo == "redes":
        lg = Image.open(ASSETS / "redes-linha.png").convert("RGBA")
        lg = lg.resize((int(lg.width * 1.45), int(lg.height * 1.45)), Image.LANCZOS)
        img.alpha_composite(lg, ((W - lg.width) // 2, centro_y - lg.height // 2))
        return
    # padrão: ícone do Instagram + "Siga @folhadebel no Instagram" em itálico (igual ao card de feed)
    icone, gap, tam, alt = 42, 18, 34, 52
    partes = [("Siga ", 400), ("@folhadebel", 700), (" no Instagram", 400)]
    larg = sum(fonte(p, tam).getlength(t) for t, p in partes)
    total = round(icone + gap + larg)
    sx = (W - total) // 2
    ic = Image.new("RGBA", (icone * 4, icone * 4), (0, 0, 0, 0))
    di = ImageDraw.Draw(ic)
    k = icone * 4 / 24
    di.rounded_rectangle([2.5 * k, 2.5 * k, 21.5 * k, 21.5 * k], 5.5 * k, outline=BRANCO, width=int(2 * k))
    di.ellipse([(12 - 4.3) * k, (12 - 4.3) * k, (12 + 4.3) * k, (12 + 4.3) * k], outline=BRANCO, width=int(2 * k))
    di.ellipse([(17.6 - 1.1) * k, (6.4 - 1.1) * k, (17.6 + 1.1) * k, (6.4 + 1.1) * k], fill=BRANCO)
    img.alpha_composite(ic.resize((icone, icone), Image.LANCZOS), (sx, centro_y - icone // 2))
    camada = Image.new("RGBA", (round(larg) + int(alt * SHEAR_ITALICO) + 14, alt), (0, 0, 0, 0))
    d = ImageDraw.Draw(camada)
    x = 0
    for t, p in partes:
        f = fonte(p, tam)
        d.text((x, alt // 2), t, font=f, fill=BRANCO, anchor="lm")
        x += f.getlength(t)
    s = SHEAR_ITALICO
    camada = camada.transform(camada.size, Image.AFFINE, (1, s, -s * alt, 0, 1, 0), resample=Image.BICUBIC)
    img.alpha_composite(camada, (sx + icone + gap, centro_y - alt // 2))


def topo_abertura(img):
    # degradê azul do topo
    alt = FOLHA_Y + 840
    grad = Image.new("RGBA", (W, alt))
    gp = grad.load()
    for yy in range(alt):
        a = int(255 * (1 - yy / alt) ** 1.6)
        for xx in range(W):
            gp[xx, yy] = (*AZUL_TOPO, a)
    img.alpha_composite(grad, (0, 0))
    # FOLHA vazado: ocupa a largura e ~16% da altura (letra esticada na vertical, como no modelo)
    font = fonte(900, 300)
    tmp = Image.new("RGBA", (1400, 420), (0, 0, 0, 0))
    td = ImageDraw.Draw(tmp)
    td.text((20, 20), "FOLHA", font=font, fill=(255, 255, 255, 0), stroke_width=3, stroke_fill=(255, 255, 255, 235))
    tmp = tmp.crop(tmp.getbbox())
    alvo_w, alvo_h = W - 100, 318
    tmp = tmp.resize((alvo_w, alvo_h), Image.LANCZOS)
    img.alpha_composite(tmp, ((W - alvo_w) // 2, FOLHA_Y))


def camadas(titulo, layout, tipo_assinatura, pasta, emenda=None):
    """Três camadas: topo (degradê+FOLHA, 5 s), título (5 s, com entrada e saída) e fixa (marca no meio + assinatura)."""
    ty = TITULO_Y["pe"] if layout == "pe" else emenda
    fim = desenhar_titulo(Image.new("RGBA", (W, H)), titulo, ty)
    ay = min(max(ASSINATURA_Y, fim + 90), LIMITE_BAIXO)
    topo = Image.new("RGBA", (W, H), (0, 0, 0, 0)); topo_abertura(topo)
    tit = Image.new("RGBA", (W, H), (0, 0, 0, 0)); desenhar_titulo(tit, titulo, ty)
    fixa = Image.new("RGBA", (W, H), (0, 0, 0, 0)); faixa_marca(fixa, MARCA_MEIO_Y); assinatura(fixa, ay, tipo_assinatura)
    caminhos = [pasta / "_topo.png", pasta / "_titulo.png", pasta / "_fixa.png"]
    for im, c in zip((topo, tit, fixa), caminhos):
        im.save(c)
    return caminhos


def par(n):
    return int(n) // 2 * 2


def altura_faixa_baixo(info):
    return min(par(W * info["altura"] / info["largura"]), int(H * FAIXA_BAIXO_MAX))


def filtro_base(info, layout, foco_x):
    vw, vh = info["largura"], info["altura"]
    def cobrir(lbl_in, lbl_out, cw, ch):
        s = max(cw / vw, ch / vh)
        sw, sh = par(vw * s + 1), par(vh * s + 1)
        cx = int(max(0, min(sw - cw, (sw - cw) * foco_x)))
        return f"[{lbl_in}]scale={sw}:{sh},crop={cw}:{ch}:{cx}:(ih-{ch})/2,setsar=1[{lbl_out}]"
    if layout == "pe":
        return cobrir("0:v", "base", W, H)
    bh = altura_faixa_baixo(info)
    th = H - bh
    return ";".join([
        "[0:v]split=2[va][vb]",
        cobrir("va", "cima", W, th),
        f"[vb]scale={W}:{bh},setsar=1[baixo]",
        "[cima][baixo]vstack=inputs=2[base]",
    ])


def inserir_intro(video, intro, em, saida):
    """Corta o reels pronto no segundo `em` (fim do gancho) e encaixa a vinheta de intro ali."""
    iv, ii = sonda(video), sonda(intro)
    em = max(0.0, min(em, iv["duracao"]))
    fmt = "aformat=sample_rates=48000:channel_layouts=stereo"
    def audio(idx, info, extra):
        if info["audio"]:
            return f"[{idx}:a]{extra}asetpts=PTS-STARTPTS,{fmt}"
        return f"anullsrc=r=48000:cl=stereo,atrim=0:{info['duracao'] if not extra else 0},{fmt}"
    partes = [
        f"[0:v]trim=0:{em},setpts=PTS-STARTPTS,fps=30,setsar=1,format=yuv420p[v0]",
        f"[0:a]atrim=0:{em},asetpts=PTS-STARTPTS,{fmt}[a0]" if iv["audio"] else f"anullsrc=r=48000:cl=stereo,atrim=0:{em}[a0]",
        f"[1:v]scale={W}:{H}:force_original_aspect_ratio=increase,crop={W}:{H},fps=30,setsar=1,format=yuv420p[v1]",
        f"[1:a]asetpts=PTS-STARTPTS,{fmt}[a1]" if ii["audio"] else f"anullsrc=r=48000:cl=stereo,atrim=0:{ii['duracao']}[a1]",
        f"[0:v]trim=start={em},setpts=PTS-STARTPTS,fps=30,setsar=1,format=yuv420p[v2]",
        f"[0:a]atrim=start={em},asetpts=PTS-STARTPTS,{fmt}[a2]" if iv["audio"] else f"anullsrc=r=48000:cl=stereo,atrim=0:{iv['duracao'] - em}[a2]",
        "[v0][a0][v1][a1][v2][a2]concat=n=3:v=1:a=1[v][a]",
    ]
    subprocess.run(["ffmpeg", "-y", "-v", "error", "-i", str(video), "-i", str(intro), "-filter_complex", ";".join(partes),
                    "-map", "[v]", "-map", "[a]", "-c:v", "libx264", "-preset", "medium", "-crf", "20",
                    "-c:a", "aac", "-b:a", "160k", "-movflags", "+faststart", str(saida)], check=True)


def main():
    ap = argparse.ArgumentParser(description="Reels da Folha de Bel")
    ap.add_argument("--video", required=True)
    ap.add_argument("--info", action="store_true", help="só mostra orientação, tamanho e duração")
    ap.add_argument("--titulo")
    ap.add_argument("--layout", choices=["pe", "deitado"], help="padrão: pela orientação do vídeo")
    ap.add_argument("--assinatura", choices=["siga", "redes"], default="siga")
    ap.add_argument("--foco-x", type=float, default=0.5, help="0 a 1: onde fica o recorte em pé")
    ap.add_argument("--out-dir", default="saida")
    ap.add_argument("--so-previa", action="store_true", help="gera só a capa (rápido) para aprovar")
    ap.add_argument("--intro", help="vinheta de intro (padrão: assets/intro.mp4 quando --intro-em for usado)")
    ap.add_argument("--intro-em", type=float, help="segundo do vídeo original onde a intro entra (fim do gancho)")
    a = ap.parse_args()

    info = sonda(a.video)
    if a.info:
        print(json.dumps(info, ensure_ascii=False)); return
    if not a.titulo:
        sys.exit("faltou --titulo")
    layout = a.layout or info["orientacao"]
    out = pathlib.Path(a.out_dir); out.mkdir(parents=True, exist_ok=True)
    nome = pathlib.Path(a.video).stem
    camadas_png = camadas(a.titulo, layout, a.assinatura, out,
                          emenda=H - altura_faixa_baixo(info) if layout == "deitado" else None)
    base = filtro_base(info, layout, a.foco_x)

    # capa: frame do vídeo com topo + título + fixa por cima (título totalmente visível)
    capa = out / f"{nome}-capa.jpg"
    t_capa = min(1.5, max(0.0, info["duracao"] / 3))
    frame = out / "_frame.png"
    subprocess.run(["ffmpeg", "-y", "-v", "error", "-ss", f"{t_capa}", "-i", a.video, "-filter_complex", base,
                    "-map", "[base]", "-frames:v", "1", str(frame)], check=True)
    im = Image.open(frame).convert("RGBA")
    for c in camadas_png:
        im.alpha_composite(Image.open(c).convert("RGBA"))
    im.convert("RGB").save(capa, quality=92)
    frame.unlink(missing_ok=True)
    if a.so_previa:
        for c in camadas_png:
            c.unlink(missing_ok=True)
        print(capa); return

    A, E = ABERTURA_S, EFEITO_S
    if a.intro_em is not None and 1.5 <= a.intro_em < A:
        A = a.intro_em  # o título sai junto com o gancho, antes da intro, e não volta depois dela
    sai = A - E
    desliza = (f"if(lt(t,{E}),-{DESLIZE_PX}*pow(1-t/{E},2),"
               f"if(gt(t,{sai}),-{DESLIZE_PX}*pow((t-{sai})/{E},2),0))")
    graf = (f"{base};"
            f"[1:v]format=rgba,fade=t=in:st=0:d={E}:alpha=1,fade=t=out:st={sai}:d={E}:alpha=1[topo];"
            f"[2:v]format=rgba,fade=t=in:st=0:d={E}:alpha=1,fade=t=out:st={sai}:d={E}:alpha=1[tit];"
            f"[base][topo]overlay=0:0:eof_action=pass[v1];"
            f"[v1][tit]overlay=x='{desliza}':y=0:eof_action=pass[v2];"
            f"[v2][3:v]overlay=0:0,format=yuv420p[v]")
    topo_png, tit_png, fixa_png = camadas_png
    entradas = ["-i", a.video,
                "-loop", "1", "-framerate", "30", "-t", f"{A}", "-i", str(topo_png),
                "-loop", "1", "-framerate", "30", "-t", f"{A}", "-i", str(tit_png),
                "-loop", "1", "-framerate", "30", "-i", str(fixa_png)]
    mp4 = out / f"{nome}-folhadebel-{layout}.mp4"
    cmd = ["ffmpeg", "-y", "-v", "error", *entradas, "-filter_complex", graf, "-map", "[v]"]
    if info["audio"]:
        cmd += ["-map", "0:a:0", "-c:a", "aac", "-b:a", "160k"]
    cmd += ["-c:v", "libx264", "-preset", "medium", "-crf", "20", "-r", "30", "-t", f"{info['duracao']}",
            "-movflags", "+faststart", str(mp4)]
    subprocess.run(cmd, check=True)
    if a.intro_em is not None:
        intro = pathlib.Path(a.intro) if a.intro else ASSETS / "intro.mp4"
        if not intro.exists():
            sys.exit(f"intro não encontrada: {intro}")
        com_intro = out / f"{nome}-folhadebel-{layout}-intro.mp4"
        inserir_intro(mp4, intro, a.intro_em, com_intro)
        mp4.unlink(); com_intro.rename(mp4)
    # frame horizontal para o site
    site = out / f"{nome}-site.jpg"
    t_site = info["duracao"] * 0.3
    subprocess.run(["ffmpeg", "-y", "-v", "error", "-ss", f"{t_site}", "-i", a.video, "-frames:v", "1",
                    "-vf", "scale=1200:675:force_original_aspect_ratio=increase,crop=1200:675", "-q:v", "2",
                    str(site)], check=True)
    for c in camadas_png:
        c.unlink(missing_ok=True)
    print(json.dumps({"video": str(mp4), "capa": str(capa), "site": str(site), "layout": layout}, ensure_ascii=False))


if __name__ == "__main__":
    main()
