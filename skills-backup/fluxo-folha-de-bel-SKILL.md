---
name: fluxo-folha-de-bel
description: "Fluxo completo de publicação da Folha de Bel a partir de um vídeo (reels), de uma foto (card de feed) ou de uma arte gerada por IA a partir de um tema (card de opinião): título, montagem, intro, corpo da matéria, agendamento no mLabs, matéria no site e resumo para o canal do WhatsApp, com aprovação do usuário em cada etapa. Use sempre que o usuário mandar um vídeo, foto ou tema para virar post da Folha, ou pedir para rodar o fluxo."
---

# Fluxo da Folha de Bel

Conduz uma pauta do material bruto até a publicação. **Cada etapa termina com uma pergunta ao usuário e só avança com a resposta.** Nunca publique, agende ou envie nada sem o "sim" explícito daquela etapa.

Os scripts ficam em `scripts/` desta skill (com `assets/` e `fonts/` ao lado). Copie a pasta `scripts/` inteira para a pasta de trabalho antes de usar. Dependências: `ffmpeg`, `pillow` e `numpy` (instale com pip se faltar).

Guarde tudo num **objeto de pauta** único, que alimenta todas as etapas: tipo (vídeo/estático), arquivo(s), layout, título (com quebras e destaque), transcrição, contexto do usuário, fontes da busca, corpo aprovado, capa, frame horizontal e links publicados.

---

## Etapa 1: Entrada

**Vídeo**
1. Rode `python3 scripts/reels.py --info --video ARQUIVO`. Ele mostra orientação, tamanho, duração e se há áudio.
2. Diga ao usuário em uma linha: "Vídeo **em pé** (1080x1920, 42 s)" ou "Vídeo **deitado** → layout de duas faixas (recorte em pé em cima, vídeo inteiro embaixo)".
   - Em pé → layout `pe`.
   - Deitado → layout `deitado`. Essa é a única variação do deitado.
3. Avise se a resolução for baixa (lado menor < 720 px), porque fica borrado ao ampliar.

**Estático (imagem)**
1. Abra a imagem e avalie:
   - tamanho (lado menor < 1080 px);
   - nitidez (pixelada ou borrada);
   - orientação (deitada ou quadrada, quando o card é 1080x1350).
2. Se estiver boa e em pé, siga com ela.
3. Se estiver ruim, pixelada ou deitada, **não rode o Gemini por conta própria.** Diga o motivo e **sugira**: "A foto está deitada e com 640 px. Sugiro usar o Gemini para completar em 1080x1350 e melhorar a nitidez. Quer?" Só com o "sim":
   `python3 scripts/gemini.py imagem FOTO --out foto-1080x1350.jpg`
   O prompt completa as bordas para 4:5 e melhora a nitidez sem mudar pessoas nem a cena. Se o usuário quiser algo específico, passe com `--extra "..."`. Se ele recusar, siga com a original (o card recorta).
4. Mostre a original e a tratada lado a lado e avise quais partes a IA criou (bordas, corpo, cenário). Pergunte: **"Aprova a imagem tratada ou uso a original?"** Se a parte criada mostrar algo que não estava na foto e muda o fato (pessoas, objetos, lugar), sugira usar a original.
5. Duas fotos ficam lado a lado no card. Trate cada uma separadamente se precisar.

**Arte gerada do zero (estático de opinião ou análise)**
Use quando o usuário quiser um card estático sem foto pronta, com uma imagem criada por IA a partir de um tema. Como a Folha trabalha: uma composição feita a partir das **referências que o usuário mandar**, com **uma, duas ou várias pessoas** e um **background** ligado ao assunto (ex.: fachada do Hospital do Olho). Quando há lados opostos, pode entrar um rasgo de papel separando. Às vezes entra um elemento simbólico (peça de xadrez, balança). Não há número fixo de pessoas: monte a cena conforme o tema e as referências.
1. Peça o **tema** e as **referências**: uma foto de cada pessoa que vai aparecer e, se houver, a foto do lugar (background). Fotos limpas, de preferência sem texto.
2. **Proponha o conceito antes de gerar**, em 2 ou 3 linhas: quem aparece e onde, o fundo, o elemento simbólico e o clima (tenso, irônico, sério). Pergunte: **"Gero assim?"** Nada é gerado sem esse OK.
3. Com o OK:
   `python3 scripts/gemini.py compor --tema "DESCRIÇÃO DA CENA" --ref ref1.jpg ref2.jpg --out arte.jpg`
   O prompt já pede fotorrealismo de capa de revista, rostos fiéis às referências, terço de baixo escuro para o título, canto da logo livre e **nenhum texto, logo ou marca d'água** (nem copiado das referências).
4. Mostre a arte e pergunte: **"Aprova, ajusto ou gero outra?"** Para ajustar, refaça com o tema corrigido.
5. Cuidado editorial:
   - não coloque pessoas reais fazendo algo que não fizeram, nem em situação humilhante ou que pareça flagrante;
   - a arte é ilustrativa e deve parecer composição, não foto de um fato;
   - em opinião, sugira a tag **OPINIÃO** ou **ANÁLISE**;
   - **não** marque "conteúdo gerado por IA" no mLabs.
6. Aprovada, a arte vira a foto do card (Etapa 3, estático). Siga o fluxo normal a partir do título.

## Etapa 2: Título

Pergunte: **"Você já tem o título ou quer que eu analise a transcrição?"**

- **Tem título:** use exatamente como o usuário mandar.
- **Analisar:**
  1. Vídeo: `python3 scripts/gemini.py transcrever ARQUIVO` (transcrição com tempos e lista de nomes citados). Estático: peça um texto-base ou contexto.
  2. Carregue a skill `titulo-folha-de-bel` e gere as opções a partir da transcrição.
  3. Mostre as opções **e** faça perguntas de contexto na mesma mensagem:
     - os nomes citados estão certos (grafia de pessoas, bairros, órgãos)?
     - quem é quem no vídeo?
     - quando e onde aconteceu?
     - há algo que não aparece no vídeo e precisa entrar?
  4. Corrija a transcrição e o título com as respostas. Guarde as correções no objeto de pauta.

Formato para o reels: linhas separadas por `/`, com `*` na linha que fica em **caixa azul** (as outras ficam brancas com letra azul). Sugira o destaque na linha de maior impacto e deixe o usuário trocar. Ex.: `INTERNAUTA DIZ QUE ATÉ/*WAGUINHO TEM RAZÃO AO/COBRAR MARIANA CANELLA`.

## Etapa 2b: Intro (só vídeo)

Pergunte: **"Quer a intro da Folha neste vídeo?"** Se não, siga para a Etapa 3.

Se sim:
1. Ache o **gancho mais forte**: a frase ou o momento do começo do vídeo que mais prende (revelação, acusação, frase forte, cena de impacto). Use a transcrição com tempos. Se o usuário trouxe o título pronto e ainda não há transcrição, transcreva agora.
2. A intro entra **sempre logo depois do gancho**, no fim da frase, nunca no meio de uma fala. Normalmente fica entre 2 e 10 s.
3. Sugira em uma linha, citando o trecho: "Gancho: 'Os servidores estão sendo jogados ao limbo' (0:00–0:04). Intro entra em **4,2 s**. Confirma?"
4. Com o OK, use `--intro-em 4.2` na montagem. Se a intro entrar antes dos 5 s, o título e o topo azul saem junto com o gancho (com o efeito de saída) e não voltam depois da intro.
5. A vinheta fica em `scripts/assets/intro.mp4`. Se o arquivo não existir, peça ao usuário para enviar a intro uma vez e salve nesse caminho. Use `--intro ARQUIVO` se ele mandar outra.

## Etapa 3: Montagem + aprovação

**Vídeo (reels):**
1. Prévia rápida (some `--intro-em SEG` se o usuário quis intro):
   `python3 scripts/reels.py --video ARQUIVO --titulo "..." --layout pe|deitado --out-dir saida --so-previa`
   Mostre a capa gerada.
2. Com o OK, gere o vídeo completo (mesmo comando, sem `--so-previa`).
3. Saídas:
   - `*-folhadebel-<layout>.mp4`: vídeo final;
   - `*-capa.jpg`: frame da abertura **com o título**. É a capa do mLabs;
   - `*-site.jpg`: frame horizontal 1200x675 para o site.
4. Modelo:
   - **Só nos 5 primeiros segundos:** topo com degradê azul e "FOLHA" vazado, e o **título**. Os dois entram e saem com efeito (fade, e o título desliza da esquerda). Depois dos 5 s, o título some.
   - **Durante todo o vídeo:** a faixa FOLHADEBEL, **sempre no meio da tela (nunca embaixo)**, e a assinatura "Siga @folhadebel no Instagram" mais abaixo.
   - **Grid do Instagram:** no perfil, o reels aparece recortado em 3:4 (só a faixa de y 240 a 1680 do 1080x1920). O "FOLHA" vazado começa em `FOLHA_Y` (= `GRADE_TOPO` + 30 = 270 px) para aparecer inteiro na capa do grid. Nada essencial da capa (FOLHA, título) pode ficar acima de 240 px nem abaixo de 1680 px.
   - Sem logo. No deitado, o título começa na emenda das duas faixas.
   - Constantes no topo do script: `GRADE_TOPO`, `FOLHA_Y`, `ABERTURA_S` (5 s), `EFEITO_S` (duração da entrada e da saída), `DESLIZE_PX`, `TITULO_TAM`, `ASSINATURA_Y`.
5. Ajustes: `--foco-x 0.3` move o recorte em pé para a esquerda (0 a 1), se o assunto estiver fora do centro.

**Estático (card):**
Siga a skill `card-feed-estatico-folha-de-bel-v2`, usando o script já pronto desta skill:
`python3 scripts/card_feed.py --foto FOTO [FOTO2] --titulo "..." --fontes scripts/fonts --tag "TAG" --out card.png` (ou `--urgente` no lugar de `--tag`).
O próprio card é a capa.

Envie o arquivo com SendUserFile e pergunte: **"Aprova?"** Se pedir ajuste, refaça e pergunte de novo.

## Etapa 4: Corpo da matéria

1. Antes de escrever, pergunte: **"Faço uma busca na web para complementar, ou me baseio só nos fatos do vídeo?"**
   - Sim → pesquise (WebSearch). Use só fontes confiáveis e guarde os links.
   - Não → use apenas a transcrição corrigida e o contexto do usuário. Não invente nada.
2. Carregue a skill `legenda-instagram-folha-de-bel` e escreva o corpo (é a legenda do post), seguindo o título aprovado.
3. Regras da Folha: atribua o que é relato ("segundo ele", "afirma"). Quando houver acusação a órgão ou pessoa, inclua "A Folha de Bel segue com o espaço aberto para manifestação de ...". Termine com "Siga a Folha de Bel no Instagram e acompanhe as principais notícias de Belford Roxo e da Baixada Fluminense." e as hashtags.
4. Mostre e pergunte: **"Aprova o corpo?"**

## Etapa 5: mLabs

Pergunte: **"Quer agendar no mLabs?"** Se não, pule para a Etapa 6.

Se sim:
1. Faça uma leitura atual com `list_profiles` (perfil "FOLHA DE BEL") e `list_channels`. Monte as opções a partir dos canais ativos e pergunte **"Para quais redes?"**, para o usuário marcar uma, várias ou todas. Para vídeo:
   - Instagram Reels (`instagram_reels`)
   - Facebook Reels (`facebook_reels`)
   - TikTok (`tiktok_business`)
   - Todas

   Para card:
   - Instagram Feed (`instagram`)
   - Facebook (`facebook`)
   - TikTok foto (`tiktok_business`)
   - Todas

   Use sempre as keys reais devolvidas pelo mLabs.
2. Pergunte: **"Publicar agora ou em data e hora específicas?"**
   - Agora → `scheduledAt` = horário atual + 2 min, fuso America/Sao_Paulo.
   - Data e hora → use exatamente o que o usuário disser, no fuso de Brasília.
3. Suba a mídia com `prepare_media_upload` (envie os bytes por código para a `uploadUrl`, com o método e os headers devolvidos) e confira com `get_media_upload` até `completed`. Se o envio não funcionar daqui, use `attach_media` e peça para o usuário anexar.
4. **Capa do vídeo:** sempre o frame com o título (`*-capa.jpg`). Suba a capa como imagem e passe a URL dela em `videoObjects[].coverUrl` junto com o `videoId`. Depois de criar o post, confira com `get_post` se a capa ficou certa. Se o canal não aceitar capa personalizada, avise.
5. `create_post` com `profileId`, `channels`, `content` = corpo aprovado, mídia, `scheduledAt` e `status: "scheduled"`. Use um `requestId` fixo e nunca repita a criação após erro sem conferir antes.
6. Confirme com o link "Ver agendamento na mLabs" que vem na resposta.

## Etapa 6: Site

Pergunte: **"Quer levar essa matéria para o site?"** Se não, pule para a Etapa 7.

Se sim:
1. Imagem: vídeo → `*-site.jpg` (frame horizontal); estático → recorte horizontal da foto (1200x675).
2. Monte e mostre: **título** (pode ser mais completo que o do card), **subtítulo**, **linha fina**, **corpo** (o corpo aprovado adaptado para texto de site, sem hashtags e sem "siga no Instagram") e imagem com crédito.
3. Pergunte **"Publico?"** e, com o OK, publique direto pelo conector do site (servidor MCP `SITE_FOLHA_DE_BEL`; procure as ferramentas com ToolSearch "SITE_FOLHA_DE_BEL"). Se o conector não estiver conectado, diga isso e entregue o texto pronto para colar. Não invente outro caminho.
4. Guarde o link publicado no objeto de pauta.

## Etapa 7: Canal do WhatsApp

Pergunte: **"Quer mandar o resumo no canal do WhatsApp?"** Se sim, entregue o texto pronto para colar, num bloco só:

```
*TÍTULO EM NEGRITO*

Resumo em 2 ou 3 frases curtas, com o essencial (quem, o quê, onde).

🔗 Leia mais: <link do site, se houver>
```

Por enquanto não há envio automático. Quando a Evolution API estiver instalada na VPS, esta etapa passa a enviar direto pelo endpoint `POST /message/sendText/<instância>` para o ID `...@newsletter` do canal, sempre só depois do OK.

---

## Regras
- Uma pergunta de aprovação por etapa. Não pule etapas nem junte aprovações.
- O título aprovado não muda nas etapas seguintes, exceto se o usuário pedir.
- Nada de fatos fora do vídeo ou da busca autorizada. Na dúvida, pergunte.
- Nunca peça senhas ou chaves no chat. A chave do Gemini fica como segredo de rede do ambiente (host `generativelanguage.googleapis.com`, header `x-goog-api-key`). Se faltar, peça pelo formulário seguro (`request_credential`).
- Ajuste de modelo pedido na conversa (posição, tamanho, cor) vai direto para o script e para esta skill.
