var __MANTICORESEARCH_SEMAFOR = 0;

/**
 * Инициализация поиска manticore
 * 
 */


/**
 * 
 * @param {object} options - параметры
 * @arg {String} options.url - адрес для поисковой машины. Пример: https://example.com/search.php
 * @arg {String} options.inputId - id инпута для поиска
 * @arg {String} options.timeout - таймаут после ввода символа
 * @arg {String} options.defaultWidth - ставить ширину по умолчанию (true/false)
 * @arg {Function} options.handleElement - функция обработки результата поиска
 */
function manticore_init(options) {
    if (options == undefined) console.error('options is undefined');
    if (options.url == undefined) console.error('options.url is undefined');
    if (options.inputId == undefined) console.error('options.inputId is undefined');
    if (options.timeout == undefined) options.timeout = 200;
    if (options.defaultWidth == undefined) options.defaultWidth = true;

    let inp = document.getElementById(options.inputId);

    let block = document.createElement('div');
    block.style.display = 'none';
    block.className = '__js-mtcr-search';
    document.body.appendChild(block);

    let px = 'px';
    block.style.top = (inp.offsetTop + inp.offsetHeight) + px;
    block.style.left = inp.offsetLeft + px;
    if (options.defaultWidth) 
        block.style.width = inp.offsetWidth + px;


    let list = document.createElement('div');
    list.className = '__js-mtcr-search__list';
    block.appendChild(list);

    
    let addElements = (data, handleElement) => {
        for (const key in data)
            if (Object.hasOwnProperty.call(data, key)) {
                const item = data[key];
                let element = document.createElement('div');
                element.className = '__js-mtcr-search__list-element';
                if (handleElement == undefined) {
                    element.textContent = item.id;
                } else
                    handleElement(element, item);
                list.appendChild(element);
            }
    }

    inp.addEventListener('keyup', function() {

        if (__MANTICORESEARCH_SEMAFOR++ == 0)
            setTimeout(() => {
                list.innerHTML = '';
                block.style.display = 'none';
                __MANTICORESEARCH_SEMAFOR = 0;
                let kw = this.value;
                if (kw) {
                    fetch(`${options.url}?kw=${kw}`)
                        .then(res => {
                            return res.json()
                        })
                        .then(res => {
                            if (res.match.length == undefined) {
                                block.style.display = 'block';
                                addElements(res.match, options.handleElement);
                            }
                            else if (res.keywords.length == undefined) {
                                block.style.display = 'block';
                                addElements(res.keywords, options.handleElement);
                            }
                            else if (res.suggest.length == undefined) {
                                block.style.display = 'block';
                                addElements(res.suggest, options.handleElement);
                            }
                        });
                }

            }, options.timeout);
    });
}