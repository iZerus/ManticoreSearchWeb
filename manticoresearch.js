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
 * @arg {Number} options.timeout - таймаут после ввода символа
 * @arg {String} options.defaultWidth - ставить ширину по умолчанию (true/false)
 * @arg {Function} options.handleElement - функция обработки результата поиска
 * @arg {Function} options.handleTitle - функция обработки заголовка поиска
 * @arg {String} options.titleSuggest - заголовок подсказки
 * @arg {String} options.titleNotFound - заголовок подсказки
 * @arg {Number} options.limit - лимит на кол-во выдачи
 * @arg {Number} options.z_index - css
 */
function manticore_init(options) {
    if (options == undefined) console.error('options is undefined');
    if (options.url == undefined) console.error('options.url is undefined');
    if (options.inputId == undefined) console.error('options.inputId is undefined');
    if (options.timeout == undefined) options.timeout = 200;
    if (options.defaultWidth == undefined) options.defaultWidth = true;
    if (options.titleSuggest == undefined) options.titleSuggest = 'Возможно, Вы ищите это?';
    if (options.titleNotFound == undefined) options.titleNotFound = 'Ничего не найдено';
    if (options.limit == undefined) options.limit = 10;
    if (options.z_index == undefined) options.z_index = 1;

    let inp = document.getElementById(options.inputId);

    let block = document.createElement('div');
    block.style.display = 'none';
    block.style.zIndex = options.z_index;
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

    document.addEventListener('click', () => {
        block.style.display = 'none';
    });
    
    let addElements = (data, handleElement, kw) => {
        for (const key in data)
            if (Object.hasOwnProperty.call(data, key)) {
                const item = data[key];
                let element = document.createElement('div');
                element.className = '__js-mtcr-search__list-element';
                if (handleElement == undefined) {
                    element.textContent = item.id;
                } else
                    handleElement(element, item, kw);
                list.appendChild(element);
            }
    }

    let addTitle = (title, handleTitle) => {
        let element = document.createElement('div');
        element.className = '__js-mtcr-search__list-element-title';
        if (handleTitle == undefined) {
            element.innerHTML = title;
        } else
            handleTitle(element, title);
        list.appendChild(element);
    }

    let search = function() {
        if (__MANTICORESEARCH_SEMAFOR++ == 0)
        setTimeout(() => {
            list.innerHTML = '';
            block.style.display = 'none';
            __MANTICORESEARCH_SEMAFOR = 0;
            let kw = this.value;
            if (kw) {
                fetch(`${options.url}?kw=${kw}&limit=${options.limit}`)
                    .then(res => {
                        return res.json()
                    })
                    .then(res => {
                        block.style.display = 'block';
                        if (res.match.length) {
                            addElements(res.match, options.handleElement, kw);
                        }
                        // else if (res.keywords.length == undefined) {
                        //     addElements(res.keywords, options.handleElement, kw);
                        // }
                        // else if (res.suggest.length == undefined) {
                        //     addTitle(options.titleSuggest, options.handleTitle);
                        //     addElements(res.suggest, options.handleElement, kw);
                        // }
                        else
                            addTitle(options.titleNotFound, options.handleTitle);
                    });
            }

        }, options.timeout);
    }

    inp.addEventListener('keyup', function() {
        (search.bind(this))();
    });
    inp.addEventListener('click', function() {
        (search.bind(this))();
    });
}



function manticore_highlight(text, keyword, className) {
    let str = text;
    let start = text.toLowerCase().indexOf(keyword.toLowerCase());
    if (start > -1) {
        str = text.substring(0, start);
        str += `<span class='${className}'>`;
        str += text.substring(start, start + keyword.length);
        str += `</span>`;
        str += text.substring(start + keyword.length);
    }
    return str;
}