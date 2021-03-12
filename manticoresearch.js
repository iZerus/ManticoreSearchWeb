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
 * @arg {String} options.titleNotFound - заголовок подсказки
 * @arg {String} options.defaultWidth - ставить ширину по умолчанию (true/false)
 * @arg {Function} options.handleElement - функция обработки результата поиска
 * @arg {Function} options.handleTitle - функция обработки заголовка поиска
 * @arg {Number} options.timeout - таймаут после ввода символа
 * @arg {Number} options.z_index - css
 * @arg {Number} options.indexes - индексы
 * @arg {Number} options.indexes.index - имя индекса
 * @arg {Number} options.indexes.title - заголовок индекса
 */
function manticore_init(options) {
    if (options == undefined) console.error('options is undefined');
    if (options.url == undefined) console.error('options.url is undefined');
    if (options.inputId == undefined) console.error('options.inputId is undefined');
	if (options.titleNotFound == undefined) options.titleNotFound = 'Ничего не найдено';
	if (options.defaultWidth == undefined) options.defaultWidth = true;
	if (options.timeout == undefined) options.timeout = 200;
	if (options.z_index == undefined) options.z_index = 1;
    if (options.indexes == undefined) console.error('options.indexes is undefined');

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

	let addElements = (data, handleElement, kw, index) => {
        for (const key in data)
            if (Object.hasOwnProperty.call(data, key)) {
                const item = data[key];
                let element = document.createElement('div');
                element.className = '__js-mtcr-search__list-element';
                if (handleElement == undefined) {
                    element.textContent = item.id;
                } else
                    handleElement(element, item, kw, index);
                list.appendChild(element);
            }
    }

	let addTitle = (title, handleTitle, index) => {
        let element = document.createElement('div');
        element.className = '__js-mtcr-search__list-element-title';
        if (handleTitle == undefined) {
            element.innerHTML = title;
        } else
            handleTitle(element, title, index);
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
				function seachIndex(i, indexList, finded) {
					fetch(`${options.url}?kw=${kw}&index=${indexList[i].index}`)
						.then(res => {
							return res.json()
						})
						.then(res => {
							if (res.match.length) {
								finded = true;
								addTitle(indexList[i].title, options.handleTitle, indexList[i].index);
								addElements(res.match, options.handleElement, kw, indexList[i].index);
							}
							if (++i < indexList.length)
								seachIndex(i, indexList, finded);
							else {
								block.style.display = 'block';
								if (!finded)
									addTitle(options.titleNotFound, options.handleTitle, '');
							}
						})
				}
				seachIndex(0, options.indexes, false);
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