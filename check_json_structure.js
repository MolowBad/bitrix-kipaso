// Скрипт для проверки структуры JSON и поиска товара
const fs = require('fs');

try {
    console.log('Читаем файл all_products.json...');
    const jsonContent = fs.readFileSync('./all_products.json', 'utf8');
    
    console.log('Парсим JSON...');
    const data = JSON.parse(jsonContent);
    
    console.log(`✓ JSON валиден! Найдено товаров: ${data.length}`);
    
    // Ищем товар
    const productSku = 'dtshh5_termosoprotivleniya_s_kommutatcionnoj_golovkoj';
    const productIndex = data.findIndex(item => item.sku === productSku);
    
    if (productIndex !== -1) {
        const product = data[productIndex];
        console.log(`\n✓ Товар найден (индекс ${productIndex}):`);
        console.log(`  SKU: ${product.sku}`);
        console.log(`  URL: ${product.url}`);
        console.log(`  Template элементов: ${product.template?.length || 0}`);
        console.log(`  Модификаций (mods): ${product.mods?.length || 0}`);
        
        if (product.mods) {
            console.log('\n  Список модификаций:');
            product.mods.forEach((mod, idx) => {
                console.log(`    ${idx + 1}. id: ${mod.id}, title: "${mod.title}", options: ${mod.options?.length || 0}`);
            });
            
            // Проверяем модификацию 1379
            const mod1379 = product.mods.find(m => m.id === 1379);
            if (mod1379) {
                console.log(`\n✓ Модификация с id 1379 НАЙДЕНА!`);
                console.log(`  Title: ${mod1379.title}`);
                console.log(`  Опций: ${mod1379.options?.length || 0}`);
                if (mod1379.options && mod1379.options.length > 0) {
                    console.log(`  Первая опция: id=${mod1379.options[0].id}, value="${mod1379.options[0].value}", label="${mod1379.options[0].label}"`);
                    console.log(`  Последняя опция: id=${mod1379.options[mod1379.options.length-1].id}, value="${mod1379.options[mod1379.options.length-1].value}"`);
                }
            } else {
                console.log(`\n✗ Модификация с id 1379 НЕ НАЙДЕНА в массиве mods!`);
            }
            
            // Проверяем template
            console.log('\n  Проверка template:');
            const templateMod1379 = product.template?.find(t => t.modId === 1379);
            if (templateMod1379) {
                console.log(`  ✓ В template есть ссылка на modId 1379`);
            } else {
                console.log(`  ✗ В template НЕТ ссылки на modId 1379`);
            }
        }
    } else {
        console.log(`\n✗ Товар с SKU "${productSku}" не найден`);
    }
    
} catch (error) {
    console.error('✗ ОШИБКА:', error.message);
    if (error.message.includes('JSON')) {
        console.error('\nJSON файл содержит синтаксические ошибки!');
        console.error('Позиция ошибки:', error.message);
    }
}
