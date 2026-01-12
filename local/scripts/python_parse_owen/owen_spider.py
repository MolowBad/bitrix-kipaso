import scrapy
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
from scrapy.selector import Selector
import time
import json
import re

class OwenSpider(scrapy.Spider):
    name = 'owen'
    start_urls = ['https://owen.ru/']

    def __init__(self):
        options = Options()
        options.add_argument("--headless")
        options.add_argument("--no-sandbox")
        self.driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)

    def closed(self, reason):
        self.driver.quit()

    def parse(self, response):
        # Ищем все ссылки на товары по шаблону "/product/..."
        product_links = response.xpath('//a[contains(@href, "/product/")]/@href').getall()
        product_links = list(set([link.strip() for link in product_links if re.match(r"^/product/[a-zA-Z0-9_\-]+$", link)]))

        for link in product_links:
            sku = link.strip().split('/')[-1]
            full_url = response.urljoin(link)
            yield scrapy.Request(full_url, callback=self.parse_product, meta={'sku': sku, 'url': full_url})

    def parse_product(self, response):
        sku = response.meta['sku']
        product_url = response.meta['url']

        self.driver.get(product_url + '/price')
        time.sleep(3)  # дождаться загрузки JS (можно заменить на WebDriverWait)

        sel = Selector(text=self.driver.page_source)

        mod_blocks = sel.xpath('//div[contains(@class, "typer-mod js-typer-mod")]')
        mods = []

        for mod in mod_blocks:
            header = mod.xpath('.//div[contains(@class, "typer-mod__header")]/text()').get()
            items = []
            item_nodes = mod.xpath('.//div[contains(@class, "typer-mod-item js-typer-mod-item")]')

            for i, item in enumerate(item_nodes):
                value = item.xpath('.//span[contains(@class, "js-typer-mod-item-name")]/text()').get(default='').strip()
                desc = item.xpath('.//div[contains(@class, "typer-mod-item__text")]/text()').get(default='').strip()
                if value or desc:
                    items.append({f"value{i+1}": value, "description": desc})

            if header and items:
                mods.append({"header": header, "items": items})

        yield {
            "sku": sku,
            "url": product_url,
            "mods": mods
        }
