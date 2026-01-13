import requests
from bs4 import BeautifulSoup, NavigableString
from urllib.parse import urljoin
import json
import time
import logging

# Attempt to import tqdm for progress bars, fallback to identity if not available
try:
    from tqdm import tqdm
except ImportError:
    def tqdm(iterable, **kwargs):  # fallback: no progress bar
        return iterable

# Configuration
BASE_URL = "https://owen-russia.ru"
SHOP_URL = f"{BASE_URL}/shop/"
HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/115.0.0.0 Safari/537.36"
    )
}
REQUEST_DELAY = 1  # seconds
MAX_RETRIES = 3
OUTPUT_FILE = "all_products.json"

# Logging setup
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

session = requests.Session()
session.headers.update(HEADERS)

def get_soup(url):
    """
    Fetches the URL with retries and returns a BeautifulSoup object.
    Raises an exception after MAX_RETRIES failures.
    """
    last_exc = None
    for attempt in range(1, MAX_RETRIES + 1):
        try:
            resp = session.get(url)
            resp.raise_for_status()
            time.sleep(REQUEST_DELAY)
            return BeautifulSoup(resp.text, "html.parser")
        except requests.RequestException as e:
            logging.warning(f"Attempt {attempt}/{MAX_RETRIES} failed for {url}: {e}")
            last_exc = e
            time.sleep(REQUEST_DELAY)
    logging.error(f"Failed to fetch {url} after {MAX_RETRIES} attempts.")
    if last_exc:
        raise last_exc
    raise RuntimeError(f"Failed to fetch {url}")


def parse_categories():
    """
    Parse top-level product category URLs from the shop main page.
    """
    soup = get_soup(SHOP_URL)
    categories = set()
    # Ограничиваем поиск ссылок категориями только в основном контенте,
    # чтобы не захватывать пункты меню/хедера вроде "Каталог модификаторов"
    container = soup.select_one('div.content-container.site-container') or soup
    for a in container.select('a[href*="/product-category/"]'):
        href = a.get('href')
        if not href:
            continue
        full = urljoin(BASE_URL, href)
        # Исключаем категорию "Каталог модификаторов"
        if "/product-category/katalog-modifikatorov/" in full:
            continue
        categories.add(full)
    categories = sorted(categories)
    logging.info(f"Found {len(categories)} top-level categories.")
    return categories


def parse_subcategories(cat_url):
    """
    Parse subcategory URLs from a category page.
    """
    soup = get_soup(cat_url)
    subcats = []
    container = soup.select_one('div.content-container.site-container') or soup
    # На странице категории подкатегории обычно представлены плитками li.product-category
    links = container.select('li.product-category a, a[href*="/product-category/"]')
    for a in links:
        href = a.get('href')
        if not href:
            continue
        full = urljoin(BASE_URL, href)
        if "/product-category/katalog-modifikatorov/" in full:
            continue
        subcats.append(full)
    logging.info(f"Found {len(subcats)} subcategories under {cat_url}.")
    return subcats


def parse_product(prod_url):
    """
    Parse product details and return a dict according to schema.
    """
    soup = get_soup(prod_url)

    # SKU
    sku = None
    sku_wrap = soup.find('span', class_='sku_wrapper')
    if sku_wrap:
        sku_span = sku_wrap.find('span', class_='sku')
        if sku_span:
            sku = sku_span.get_text(strip=True)

    # Typer block
    typer = soup.find('div', class_='typer js-typer')
    template = []
    mods = []
    if typer:
        # Template sequence
        tpl = typer.find('div', class_='typer__template')
        if tpl:
            for node in tpl.children:
                if isinstance(node, NavigableString):
                    text = node.strip()
                    if text:
                        template.append({"type": "static", "text": text, "modId": None})
                elif node.name == 'span' and 'typer-template-item' in node.get('class', []):
                    mod_id = node.get('data-id')
                    template.append({
                        "type": "modifier",
                        "text": node.get_text(strip=True),
                        "modId": int(mod_id) if mod_id else None
                    })
        # Mods details
        for mod_div in typer.find_all('div', class_='typer-mod js-typer-mod'):
            mod_id = mod_div.get('data-id')
            title_div = mod_div.find('div', class_='typer-mod__header')
            title_text = title_div.get_text(strip=True) if title_div else None
            options = []
            for item in mod_div.find_all('div', class_='typer-mod-item'):
                opt_id = item.get('data-id')
                # Значение модификации находится внутри .typer-mod-item__name > .typer-mod-item__value (span)
                # либо отмечено классом .js-typer-mod-item-name
                val_el = item.select_one('.typer-mod-item__value, .js-typer-mod-item-name')
                if not val_el:
                    name_wrap = item.find('div', class_='typer-mod-item__name')
                    val_text = name_wrap.get_text(strip=True) if name_wrap else ''
                else:
                    val_text = val_el.get_text(strip=True)

                text_div = item.find('div', class_='typer-mod-item__text')
                label_text = text_div.get_text(strip=True) if text_div else None

                options.append({
                    "id": int(opt_id) if opt_id else None,
                    "value": val_text if val_text is not None else '',
                    "label": label_text
                })
            mods.append({
                "id": int(mod_id) if mod_id else None,
                "title": title_text,
                "options": options
            })

    # Custom text block
    custom_div = soup.find('div', class_='typer__custom')
    custom = None
    if custom_div:
        text = custom_div.get_text(separator=' ', strip=True)
        link = custom_div.find('a', href=True)
        contact = link['href'] if link else None
        custom = {"text": text, "contact": contact}

    product_data = {
        "sku": sku,
        "url": prod_url,
        "template": template,
        "mods": mods
    }
    if custom:
        product_data["custom"] = custom

    return product_data


def parse_subcategory_products(subcat_url, master_list):
    """
    Parse all products in a subcategory and append to master_list.
    """
    pages_to_visit = [subcat_url]
    visited_pages = set()

    while pages_to_visit:
        page_url = pages_to_visit.pop(0)
        if page_url in visited_pages:
            continue
        visited_pages.add(page_url)
        # Пытаемся получить страницу; при ошибках (например, 500) пропускаем и идем дальше
        try:
            soup = get_soup(page_url)
        except Exception as e:
            logging.error(f"Failed to fetch subcategory page {page_url}: {e}")
            continue

        # Товары на странице
        items = soup.select('li.entry.product, li.product.type-product')
        logging.info(f"Found {len(items)} products in {page_url}.")
        for li in tqdm(items, desc="Products", leave=False):
            a = li.select_one('a[href*="/product/"]')
            if not a:
                continue
            prod_url = a.get('href')
            if not prod_url or '/product/' not in prod_url:
                continue
            # Нормализуем ссылку
            prod_url = urljoin(BASE_URL, prod_url)
            try:
                data = parse_product(prod_url)
                master_list.append(data)
            except Exception as e:
                logging.error(f"Error parsing product {prod_url}: {e}")

        # Пагинация: собираем ссылки на другие страницы категории
        pagination_links = set()
        for a in soup.select('a.page-numbers, nav.woocommerce-pagination a'):
            href = a.get('href')
            if href:
                pagination_links.add(urljoin(BASE_URL, href))
        # Также пытаемся найти ссылку на следующую страницу
        next_link = soup.select_one('a.next, a.next.page-numbers, link[rel="next"]')
        if next_link and next_link.get('href'):
            pagination_links.add(urljoin(BASE_URL, next_link.get('href')))

        # Вложенные подкатегории: если текущая страница показывает только плитки подкатегорий,
        # добавим их в очередь обхода, чтобы не потерять товары глубже.
        # Ограничиваем поиск основным контентом, исключая меню/хедер.
        nested_subcats = set()
        content = soup.select_one('div.content-container.site-container') or soup
        for a in content.select('li.product-category a, a[href*="/product-category/"]'):
            href = a.get('href')
            if not href:
                continue
            full = urljoin(BASE_URL, href)
            if "/product-category/katalog-modifikatorov/" in full:
                continue
            nested_subcats.add(full)

        for link in sorted(nested_subcats):
            if link not in visited_pages and link not in pages_to_visit:
                pages_to_visit.append(link)

        for p in sorted(pagination_links):
            if p not in visited_pages and p not in pages_to_visit:
                pages_to_visit.append(p)


def main():
    all_products = []
    categories = parse_categories()
    for cat_url in tqdm(categories, desc="Categories"):
        subcategories = parse_subcategories(cat_url)
        for subcat_url in tqdm(subcategories, desc="Subcategories", leave=False):
            try:
                parse_subcategory_products(subcat_url, all_products)
            except Exception as e:
                logging.error(f"Failed to parse subcategory {subcat_url}: {e}")

    # Write to JSON
    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(all_products, f, ensure_ascii=False, indent=2)
    logging.info(f"Saved {len(all_products)} products to {OUTPUT_FILE}.")


if __name__ == '__main__':
    main()
