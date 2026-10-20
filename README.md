# FrogFind (ASCII art)

Fork of [FrogFind](https://github.com/ActionRetro/FrogFind), the search engine for vintage computers made by [Action Retro](https://youtube.com/ActionRetro).

> Love the frog. Be the frog.

Este fork acrescenta formas de ver as imagens das páginas em computadores e terminais muito limitados: telas só preto e branco, navegadores de texto (como o lynx) e terminais de 80 ou 40 colunas.

## O que foi acrescentado

Na página de uma imagem (`image.php`), além da imagem original, há novas opções em **View as**:

| Opção | O que faz | Funciona em |
|---|---|---|
| **Original** | Imagem comprimida, como no FrogFind original | Navegadores gráficos |
| **Black & white** | GIF de 1 bit (preto e branco) com dithering Floyd-Steinberg, 300 px no maior lado | Navegadores gráficos, inclusive antigos e monocromáticos |
| **ASCII art, 80 colunas** | Texto de 78 colunas dentro de `<pre>` | Qualquer navegador, inclusive de terminal |
| **ASCII art, 40 colunas** | Texto de 38 colunas dentro de `<pre>` | Qualquer navegador, inclusive de terminal |

O ASCII art tem duas polaridades:

- **Normal:** quanto mais escuro o trecho da imagem, mais pesado o caractere. Para texto escuro em fundo claro.
- **Inverted:** quanto mais claro o trecho, mais pesado o caractere. Para texto claro em fundo escuro, como a maioria dos terminais.

O texto fica 2 colunas mais estreito que a tela (78 e 38), para que as linhas não quebrem em terminais que quebram na última coluna.

O GIF foi escolhido no lugar do PNG porque os navegadores mais antigos não leem PNG.

## Arquivos alterados e novos

| Arquivo | Mudança |
|---|---|
| `image.php` | Menu **View as** e exibição do ASCII art. O link "Back to previous page" passou a usar o parâmetro `r`, para continuar apontando para o artigo depois de trocar de modo. |
| `image_lib.php` | Novo. Baixa e valida a imagem (`frog_load_image`) e gera o ASCII art (`frog_ascii_art`). |
| `image_filter.php` | Novo. Gera o GIF preto e branco (`?i=URL&m=bw`). |
| `read.php` | Envia um `User-Agent` nas requisições, porque alguns sites (como a Wikipedia) recusam requisições sem ele. Os nomes dos cabeçalhos HTTP passam a ser lidos sem diferenciar maiúsculas de minúsculas, e o `Content-Length` deixou de ser obrigatório. |

`index.php`, `about.php` e `image_compressed.php` não foram alterados.

## Como rodar localmente

Requisitos: PHP 8 com as extensões `gd`, `curl`, `openssl`, `mbstring` e `dom`, e o [Composer](https://getcomposer.org).

```bash
# MacPorts (exemplo)
sudo port install php83 php83-gd php83-curl php83-openssl php83-mbstring

composer install
php -d error_reporting="E_ALL & ~E_DEPRECATED" -S localhost:8000
```

Depois abra `http://localhost:8000`.

A biblioteca `fivefilters/readability.php` ainda gera avisos `Deprecated` no PHP 8.3. O `-d error_reporting=...` acima esconde esses avisos.

## Limitações conhecidas

- Testado no lynx e em navegadores modernos. **Não foi testado em navegadores antigos reais.**
- Só as novas opções (`image_filter.php` e `image_lib.php`) recusam endereços de rede local e limitam o download a 10 MB. O `image_compressed.php`, que vem do projeto original, não tem essa proteção. **Não exponha este servidor na internet sem revisar isso.**
- O `image_compressed.php` tem dois problemas herdados do original, ainda não corrigidos: a detecção de PNG nunca é verdadeira e a saída é sempre JPEG.
- As imagens são redimensionadas e convertidas no servidor, a cada requisição, sem cache.

## Licença e créditos

O FrogFind foi criado por **Action Retro**. Este fork mantém a licença original, **GNU GPL v3** (veja [`LICENSE`](LICENSE)), e as alterações acima também estão sob essa licença.
