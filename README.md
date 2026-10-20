# FrogFind (ASCII art)

**English** | [Português (BR)](README.pt-BR.md)

Fork of [FrogFind](https://github.com/ActionRetro/FrogFind), the search engine for vintage computers made by [Action Retro](https://youtube.com/ActionRetro).

> Love the frog. Be the frog.

This fork adds ways to view the images on a page on very limited computers and terminals: black-and-white-only screens, text-mode browsers (such as lynx), and 80- or 40-column terminals.

## What was added

On the image page (`image.php`), besides the original image, there are new options under **View as**:

| Option | What it does | Works on |
|---|---|---|
| **Original** | Compressed image, as in the original FrogFind | Graphical browsers |
| **Black & white** | 1-bit GIF (black and white) with Floyd-Steinberg dithering, 300 px on the longest side | Graphical browsers, including old and monochrome ones |
| **ASCII art, 80 columns** | 78-column text inside `<pre>` | Any browser, including terminal ones |
| **ASCII art, 40 columns** | 38-column text inside `<pre>` | Any browser, including terminal ones |

The ASCII art comes in two polarities:

- **Normal:** the darker the part of the image, the heavier the character. For dark text on a light background.
- **Inverted:** the lighter the part of the image, the heavier the character. For light text on a dark background, like most terminals.

The text is 2 columns narrower than the screen (78 and 38), so lines don't wrap on terminals that wrap at the last column.

GIF was chosen over PNG because the oldest browsers can't read PNG.

## Changed and new files

| File | Change |
|---|---|
| `image.php` | **View as** menu and ASCII art display. The "Back to previous page" link now uses the `r` parameter, so it keeps pointing to the article after switching modes. |
| `image_lib.php` | New. Downloads and validates the image (`frog_load_image`) and generates the ASCII art (`frog_ascii_art`). |
| `image_filter.php` | New. Generates the black & white GIF (`?i=URL&m=bw`). |
| `read.php` | Sends a `User-Agent` with its requests, because some sites (such as Wikipedia) reject requests without one. HTTP header names are now read case-insensitively, and `Content-Length` is no longer required. |

`index.php`, `about.php` and `image_compressed.php` were not changed.

## Running locally

Requirements: PHP 8 with the `gd`, `curl`, `openssl`, `mbstring` and `dom` extensions, and [Composer](https://getcomposer.org).

```bash
# MacPorts (example)
sudo port install php83 php83-gd php83-curl php83-openssl php83-mbstring

composer install
php -d error_reporting="E_ALL & ~E_DEPRECATED" -S localhost:8000
```

Then open `http://localhost:8000`.

The `fivefilters/readability.php` library still raises `Deprecated` warnings on PHP 8.3. The `-d error_reporting=...` flag above hides them.

## The `vendor/` folder and Composer

The `vendor/` folder is **not in this repository**. It is created by [Composer](https://getcomposer.org), the PHP package manager, and holds the third-party libraries the project depends on. It is the equivalent of Node's `node_modules`.

After cloning the repository, run **once**, in the project folder:

```bash
composer install
```

Composer reads [`composer.json`](composer.json) and [`composer.lock`](composer.lock) and downloads the libraries at the same versions. Without this, `read.php` fails on its very first line (`require_once('vendor/autoload.php')`), because the file `vendor/autoload.php` doesn't exist.

If you don't have Composer installed, download `composer.phar` from <https://getcomposer.org/download/> and use `php composer.phar install`. `composer.phar` and `vendor/` are listed in [`.gitignore`](.gitignore) and should not be committed.

Important points:

- **Don't edit anything inside `vendor/`.** A Composer update would erase your change.
- If you delete the folder, `composer install` recreates it.
- The project has a single direct dependency, `fivefilters/readability.php`, which `read.php` uses to extract the main text of a page. The other libraries are its dependencies.

| Package | License | What it's for |
|---|---|---|
| `fivefilters/readability.php` | Apache-2.0 | Extracts the main text of the page (used by `read.php`) |
| `masterminds/html5` | MIT | Readability dependency |
| `league/uri` and `league/uri-interfaces` | MIT | Readability dependencies |
| `psr/log` and `psr/http-message` | MIT | Readability dependencies |

## Known limitations

- Tested on lynx and modern browsers. **Not tested on real old browsers.**
- Only the new options (`image_filter.php` and `image_lib.php`) reject local-network addresses and limit downloads to 10 MB. `image_compressed.php`, which comes from the original project, has no such protection. **Don't expose this server to the internet without reviewing this.**
- `image_compressed.php` has two problems inherited from the original that are not yet fixed: the PNG check is never true, and the output is always JPEG.
- Images are resized and converted on the server on every request, with no cache.

## License and credits

FrogFind was created by **Action Retro**. This fork keeps the original license, **GNU GPL v3** (see [`LICENSE`](LICENSE)), and the changes above are also under that license.

The libraries installed in `vendor/` keep their own licenses (Apache-2.0 and MIT, see the table above), which are compatible with the GPL v3. They are not part of this repository.
